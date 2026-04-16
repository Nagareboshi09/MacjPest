<?php
// Set timezone to ensure correct date calculations
date_default_timezone_set('Asia/Manila'); // Philippines timezone

session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../db_connect.php';

// Set content type to JSON for all responses
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Log the received data for debugging
error_log('Received data: ' . print_r($data, true));
error_log('Raw JSON input: ' . $json_data);
error_log('PHP Version: ' . phpversion());
error_log('MySQL Version: ' . $conn->server_info);

// Check if required fields are present
if (!isset($data['appointment_id']) || !isset($data['status'])) {
    sendJsonResponse(false, 'Missing required fields', null, 400);
}

// Validate status
$allowed_statuses = ['accepted', 'declined'];
if (!in_array($data['status'], $allowed_statuses)) {
    sendJsonResponse(false, 'Invalid status value', null, 400);
}

// Get appointment ID and status
$appointment_id = $data['appointment_id'];
$status = $data['status'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $appointment_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update appointment status: " . $stmt->error);
    }

    // If the appointment is accepted, automatically assign an available technician
    if ($status === 'accepted') {
        // Get appointment details including any pre-selected technician
        $apptStmt = $conn->prepare("SELECT preferred_date, preferred_time, client_name, location_address, technician_id FROM appointments WHERE appointment_id = ?");
        $apptStmt->bind_param("i", $appointment_id);
        $apptStmt->execute();
        $apptResult = $apptStmt->get_result();
        $apptData = $apptResult->fetch_assoc();

        if ($apptData) {
            $date = $apptData['preferred_date'];
            $time = $apptData['preferred_time'];
            $client_name = $apptData['client_name'];
            $location = $apptData['location_address'];
            $preselected_technician_id = $apptData['technician_id'];

            $technician_id = null;
            $technician_name = '';

            // Check if a technician was pre-selected during appointment creation
            if ($preselected_technician_id) {
                // Verify that the pre-selected technician exists
                $techCheckStmt = $conn->prepare("
                    SELECT t.technician_id, t.tech_fname, t.tech_lname
                    FROM technicians t
                    WHERE t.technician_id = ?
                ");
                $techCheckStmt->bind_param("i", $preselected_technician_id);
                $techCheckStmt->execute();
                $techCheckResult = $techCheckStmt->get_result();

                if ($techRow = $techCheckResult->fetch_assoc()) {
                    $technician_id = $techRow['technician_id'];
                    $technician_name = $techRow['tech_fname'] . ' ' . $techRow['tech_lname'];
                    error_log("Using pre-selected technician ID: $technician_id ($technician_name) for appointment ID: $appointment_id");
                } else {
                    error_log("Pre-selected technician ID: $preselected_technician_id is no longer available for appointment ID: $appointment_id");
                }
            }

            // If no pre-selected technician or pre-selected technician is no longer available, find an available one
            if (!$technician_id) {
                error_log("Finding available technician for appointment ID: $appointment_id");

                // Get the day of week (0 = Sunday, 1 = Monday, etc.)
                $dayOfWeek = date('w', strtotime($date));

                // Calculate end time (2 hours after start)
                $selectedTimeObj = new DateTime($time);
                $endTimeObj = clone $selectedTimeObj;
                $endTimeObj->add(new DateInterval('PT2H'));
                $endTime = $endTimeObj->format('H:i:s');

                // Query to find available technicians
                $availableTechQuery = "
                SELECT t.technician_id, t.tech_fname, t.tech_lname
                FROM technicians t
                WHERE 1=1
                -- No status check since the status column doesn't exist
                AND EXISTS (
                    -- Check if technician has availability for this time slot
                    SELECT 1 FROM technician_availability ta
                    WHERE ta.technician_id = t.technician_id
                    AND ta.is_available = 1
                    AND (
                        -- Check weekly availability
                        (ta.day_of_week = ? AND ta.specific_date IS NULL
                         AND ta.start_time <= ? AND ta.end_time >= ?)
                        OR
                        -- Check specific date availability
                        (ta.specific_date = ? AND ta.start_time <= ? AND ta.end_time >= ?)
                    )
                )
                AND NOT EXISTS (
                    -- Check if technician is already assigned to another appointment at this time
                    SELECT 1 FROM appointments a
                    JOIN appointment_technicians at ON a.appointment_id = at.appointment_id
                    WHERE at.technician_id = t.technician_id
                    AND a.preferred_date = ?
                    AND a.status NOT IN ('cancelled', 'declined')
                    AND (
                        -- Check if there's an overlap with existing appointment
                        (a.preferred_time <= ? AND DATE_ADD(a.preferred_time, INTERVAL 2 HOUR) > ?)
                        OR
                        (a.preferred_time < ? AND DATE_ADD(a.preferred_time, INTERVAL 2 HOUR) >= ?)
                    )
                )
                AND NOT EXISTS (
                    -- Check if technician is already assigned to a job order at this time
                    SELECT 1 FROM job_order j
                    JOIN job_order_technicians jt ON j.job_order_id = jt.job_order_id
                    WHERE jt.technician_id = t.technician_id
                    AND j.preferred_date = ?
                    AND j.status NOT IN ('cancelled', 'declined')
                    AND (
                        -- Check if there's an overlap with existing job order
                        (j.preferred_time <= ? AND DATE_ADD(j.preferred_time, INTERVAL 2 HOUR) > ?)
                        OR
                        (j.preferred_time < ? AND DATE_ADD(j.preferred_time, INTERVAL 2 HOUR) >= ?)
                    )
                )
                ORDER BY t.tech_fname, t.tech_lname
                LIMIT 1";

                $availableTechStmt = $conn->prepare($availableTechQuery);
                $availableTechStmt->bind_param(
                    "isssssssssssssss",
                    $dayOfWeek,
                    $time,
                    $time,
                    $date,
                    $time,
                    $time,
                    $date,
                    $time,
                    $time,
                    $endTime,
                    $endTime,
                    $date,
                    $time,
                    $time,
                    $endTime,
                    $endTime
                );

                $availableTechStmt->execute();
                $availableTechResult = $availableTechStmt->get_result();

                if ($availableTechRow = $availableTechResult->fetch_assoc()) {
                    $technician_id = $availableTechRow['technician_id'];
                    $technician_name = $availableTechRow['tech_fname'] . ' ' . $availableTechRow['tech_lname'];
                    error_log("Found available technician ID: $technician_id ($technician_name) for appointment ID: $appointment_id");
                } else {
                    error_log("No available technicians found for appointment ID: $appointment_id on $date at $time");
                }
            }

            // Assign the technician to the appointment
            if ($technician_id) {
                $assignStmt = $conn->prepare("UPDATE appointments SET technician_id = ? WHERE appointment_id = ?");
                $assignStmt->bind_param("ii", $technician_id, $appointment_id);

                if (!$assignStmt->execute()) {
                    throw new Exception("Failed to assign technician: " . $assignStmt->error);
                }

                // Add to appointment_technicians table
                $techAssignStmt = $conn->prepare("INSERT INTO appointment_technicians (appointment_id, technician_id, is_primary) VALUES (?, ?, 1)");
                $techAssignStmt->bind_param("ii", $appointment_id, $technician_id);

                if (!$techAssignStmt->execute()) {
                    throw new Exception("Failed to add technician to appointment_technicians: " . $techAssignStmt->error);
                }

                error_log("Automatically assigned technician ID: $technician_id ($technician_name) to appointment ID: $appointment_id");
            }
        }
    }



    // Commit transaction
    $conn->commit();

    // Send success response
    sendJsonResponse(true, "Appointment $status successfully", [
        'appointment_id' => $appointment_id,
        'status' => $status
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Get detailed error information
    $errorMessage = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();

    // Log detailed error information
    error_log('Error in update_appointment_status.php: ' . $errorMessage);
    error_log('Error file: ' . $errorFile . ' on line ' . $errorLine);
    error_log('Error trace: ' . $errorTrace);

    // Send a more user-friendly error message
    sendJsonResponse(false, "Server error: " . $errorMessage, null, 500);
}
?>

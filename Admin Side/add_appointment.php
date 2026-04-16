<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'office_staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $visibility = $_POST['visibility'] ?? 'public';
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User session expired']);
        exit;
    }
    $created_by = $_SESSION['user_id'];

    if (empty($date) || empty($note)) {
        echo json_encode(['success' => false, 'message' => 'Date and note are required']);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Insert the appointment note
    $appointment_type = 'note';
    $stmt = $conn->prepare("INSERT INTO appointments (appointment_type, preferred_date, preferred_time, note_text, visibility, created_by, status, client_id, client_name, email, contact_number, kind_of_place, location_address) VALUES (?, ?, '00:00:00', ?, ?, ?, 'assigned', 1, 'Admin Note', 'admin@local', '000-000-0000', 'Office', 'Internal Note')");
    $stmt->bind_param("sssss", $appointment_type, $date, $note, $visibility, $created_by);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Appointment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        error_log("Invalid date format: $date");
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Insert the appointment note
    $appointment_type = 'note';
    $stmt = $conn->prepare("INSERT INTO appointments (appointment_type, preferred_date, preferred_time, note_text, visibility, created_by, status, client_id, client_name, email, contact_number, kind_of_place, location_address) VALUES (?, ?, '00:00:00', ?, ?, ?, 'assigned', 1, 'Admin Note', 'admin@local', '000-000-0000', 'Office', 'Internal Note')");
    $stmt->bind_param("sssss", $appointment_type, $date, $note, $visibility, $created_by);

    error_log("About to execute query with params: $appointment_type, $date, $note, $visibility, $created_by");

    if ($stmt->execute()) {
        error_log("Appointment added successfully");
        echo json_encode(['success' => true, 'message' => 'Appointment added successfully']);
    } else {
        error_log("Database error: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
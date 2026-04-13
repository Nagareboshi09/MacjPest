<?php
session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get service ID
$service_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (empty($service_id)) {
    echo json_encode(['success' => false, 'error' => 'Service ID is required']);
    exit;
}

try {
    // Check if this is a default service by name
    $default_service_names = [
        'General Pest Control',
        'Termite Control',
        'Rodent Control',
        'Disinfection',
        'Weed Control'
    ];

    $check_stmt = $conn->prepare("SELECT name FROM services WHERE service_id = ?");
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (in_array($row['name'], $default_service_names)) {
            echo json_encode(['success' => false, 'error' => 'Default services cannot be deleted']);
            exit;
        }
    }
    
    // Check if service exists
    $check_stmt = $conn->prepare("SELECT image FROM services WHERE service_id = ?");
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Service not found']);
        exit;
    }

    $row = $check_result->fetch_assoc();
    $image_path = $row['image'];

    // Delete the service
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        // Check if any rows were actually deleted
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Service could not be deleted']);
            exit;
        }

        // Delete the image file if it exists
        if ($image_path && file_exists("../uploads/services/" . $image_path)) {
            if (!unlink("../uploads/services/" . $image_path)) {
                // Log error but don't fail the deletion
                error_log("Failed to delete image file: ../uploads/services/" . $image_path);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Service and associated image deleted successfully']);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

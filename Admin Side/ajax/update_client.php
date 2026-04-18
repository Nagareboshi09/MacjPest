<?php
session_start();
if (!in_array($_SESSION['role'] ?? '', ['office_staff', 'admin'])) {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $location_address = trim($_POST['location_address']);
    $type_of_place = $_POST['type_of_place'];

    $errors = [];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Check if email already exists (excluding current client)
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE email = ? AND client_id != ?");
    $stmt->bind_param("si", $email, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists.";
    }
    $stmt->close();

    // Check if name already exists (first name + last name combination, excluding current client)
    $full_name = $first_name . ' ' . $last_name;
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE CONCAT(first_name, ' ', last_name) = ? AND client_id != ?");
    $stmt->bind_param("si", $full_name, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Client with the same name already exists.";
    }
    $stmt->close();

    // Validate contact number (basic validation: allow numbers, spaces, dashes, parentheses, plus)
    if (!preg_match('/^[\+\-\(\)\s\d]+$/', $contact_number)) {
        $errors[] = "Contact number contains invalid characters. Only numbers, spaces, dashes, parentheses, and plus sign are allowed.";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE clients SET first_name = ?, last_name = ?, email = ?, contact_number = ?, location_address = ?, type_of_place = ? WHERE client_id = ?");
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $contact_number, $location_address, $type_of_place, $client_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
?>
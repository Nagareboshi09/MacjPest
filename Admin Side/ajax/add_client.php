<?php
session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $location_address = $_POST['location_address'];
    $type_of_place = $_POST['type_of_place'];

    // Handle contract information
    $contract_start_date = null;
    $contract_end_date = null;

    if (isset($_POST['has_contract']) && $_POST['has_contract'] === 'on') {
        $contract_start_date = !empty($_POST['contract_start_date']) ? $_POST['contract_start_date'] : null;
        $contract_end_date = !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null;
    }

    $stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, email, contact_number, location_address, type_of_place, contract_start_date, contract_end_date, registered_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssss", $first_name, $last_name, $email, $contact_number, $location_address, $type_of_place, $contract_start_date, $contract_end_date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
?>
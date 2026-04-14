<?php
session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $location_address = $_POST['location_address'];
    $type_of_place = $_POST['type_of_place'];

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
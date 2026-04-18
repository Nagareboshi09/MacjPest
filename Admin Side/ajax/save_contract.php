<?php
session_start();
if (!in_array($_SESSION['role'] ?? '', ['office_staff', 'admin'])) {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $contract_start_date = $_POST['contract_start_date'];
    $contract_end_date = $_POST['contract_end_date'];

    $stmt = $conn->prepare("UPDATE clients SET contract_start_date = ?, contract_end_date = ? WHERE client_id = ?");
    $stmt->bind_param("ssi", $contract_start_date, $contract_end_date, $client_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
?>
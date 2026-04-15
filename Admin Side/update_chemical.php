<?php
session_start();
require_once '../db_config.php';

try {
    $chemicalId = $_POST['id'];
    $chemical_name = $_POST['chemical_name'] ?? '';
    $type = $_POST['type'] ?? '';
    $target_pest = $_POST['target_pest'] ?? null;
    $quantity = (float)$_POST['quantity'];
    $unit = $_POST['unit'] ?? '';
    $manufacturer = $_POST['manufacturer'] ?? null;
    $supplier = $_POST['supplier'] ?? null;
    $expiration_date = $_POST['expiration_date'] ?? null;
    $description = $_POST['description'] ?? null;
    $safety_info = $_POST['safety_info'] ?? null;
    $dilution_rate = isset($_POST['dilution_rate']) ? (float)$_POST['dilution_rate'] : null;
    $area_coverage = isset($_POST['area_coverage']) ? (float)$_POST['area_coverage'] : 100;
    $manual_area = isset($_POST['manual_area']) ? (float)$_POST['manual_area'] : null;
    $manual_solution_rate = isset($_POST['manual_solution_rate']) ? (float)$_POST['manual_solution_rate'] : null;
    $manual_dilution_ratio = $_POST['manual_dilution_ratio'] ?? null;

    // Calculate status based on quantity
    $status = 'In Stock';
    if ($quantity <= 0) {
        $status = 'Out of Stock';
    } else if ($quantity < 10) {
        $status = 'Low Stock';
    }

    $stmt = $pdo->prepare("UPDATE chemical_inventory
                          SET chemical_name = ?,
                              type = ?,
                              target_pest = ?,
                              quantity = ?,
                              unit = ?,
                              manufacturer = ?,
                              supplier = ?,
                              expiration_date = ?,
                              description = ?,
                              safety_info = ?,
                              dilution_rate = ?,
                              area_coverage = ?,
                              manual_area = ?,
                              manual_solution_rate = ?,
                              manual_dilution_ratio = ?
                          WHERE id = ?");

    $stmt->execute([
        $chemical_name,
        $type,
        $target_pest,
        $quantity,
        $unit,
        $manufacturer,
        $supplier,
        $expiration_date,
        $description,
        $safety_info,
        $dilution_rate,
        $area_coverage,
        $manual_area,
        $manual_solution_rate,
        $manual_dilution_ratio,
        $chemicalId
    ]);

    // Activity logging temporarily disabled - admin_activity_logs table not found
    // $description = "Updated chemical: $chemical_name";
    // $pdo->exec("INSERT INTO admin_activity_logs (staff_id, action_type, entity_type, entity_id, description) VALUES ({$_SESSION['user_id']}, 'edit', 'chemical', $chemicalId, '$description')");

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
<?php
session_start();
if (!in_array($_SESSION['role'] ?? '', ['office_staff', 'admin'])) {
    header("Location: SignIn.php");
    exit;
}
require_once '../db_config.php';


// Get Dashboard Metrics
try {


    // Total Chemicals
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM chemical_inventory");
    $total_chemicals = $stmt->fetchColumn();

    // Low Stock (quantity < 10 and > 0)
    $stmt = $pdo->query("SELECT COUNT(*) AS low_stock FROM chemical_inventory WHERE quantity < 10 AND quantity > 0");
    $low_stock = $stmt->fetchColumn();

    // Out of Stock
    $stmt = $pdo->query("SELECT COUNT(*) AS out_of_stock FROM chemical_inventory WHERE quantity = 0");
    $out_of_stock = $stmt->fetchColumn();

    // Expiring within 30 days
    $stmt = $pdo->query("SELECT COUNT(*) AS expiring_soon
                         FROM chemical_inventory
                         WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)");
    $expiring_soon = $stmt->fetchColumn();

     // Expired chemicals
     $stmt = $pdo->query("SELECT COUNT(*) AS expired
                          FROM chemical_inventory
                          WHERE expiration_date < CURDATE()");
     $expired_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission for NEW chemical
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $data = [
            ':name' => $_POST['chemical_name'],
            ':type' => $_POST['type'],
            ':target_pest' => $_POST['target_pest'] ?? null,
            ':qty' => (float)$_POST['quantity'],
            ':unit' => $_POST['unit'],
            ':manufacturer' => $_POST['manufacturer'] ?? null,
            ':supplier' => $_POST['supplier'] ?? null,
            ':desc' => $_POST['description'] ?? null,
            ':safety' => $_POST['safety_info'] ?? null,
            ':exp_date' => $_POST['expiration_date'],
            ':dilution_rate' => isset($_POST['dilution_rate']) ? (float)$_POST['dilution_rate'] : null,
            ':area_coverage' => isset($_POST['area_coverage']) ? (float)$_POST['area_coverage'] : 100,
            ':manual_area' => isset($_POST['manual_area']) ? (float)$_POST['manual_area'] : null,
            ':manual_solution_rate' => isset($_POST['manual_solution_rate']) ? (float)$_POST['manual_solution_rate'] : null,
            ':manual_dilution_ratio' => $_POST['manual_dilution_ratio'] ?? null
        ];

        $sql = "INSERT INTO chemical_inventory
                (chemical_name, type, target_pest, quantity, unit, manufacturer,
                 supplier, description, safety_info, expiration_date, dilution_rate, area_coverage,
                 manual_area, manual_solution_rate, manual_dilution_ratio)
                VALUES (:name, :type, :target_pest, :qty, :unit, :manufacturer,
                        :supplier, :desc, :safety, :exp_date, :dilution_rate, :area_coverage,
                        :manual_area, :manual_solution_rate, :manual_dilution_ratio)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        echo json_encode(['success' => true, 'message' => 'Chemical added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

    // Get all chemicals
    try {
        $baseQuery = "SELECT *, CASE WHEN quantity = 0 THEN 'Out of Stock' WHEN quantity < 10 THEN 'Low Stock' ELSE 'In Stock' END AS status FROM chemical_inventory";
    $whereClauses = [];
    $params = [];

    // Status filter
    if (isset($_GET['status']) && in_array($_GET['status'], ['In Stock', 'Low Stock', 'Out of Stock'])) {
        $whereClauses[] = "status = :status";
        $params[':status'] = $_GET['status'];
    }

    // Expired filter
    if (isset($_GET['filter']) && $_GET['filter'] === 'expired') {
        $whereClauses[] = "expiration_date < CURDATE()";
    }

    // Chemical name filter
    if (isset($_GET['chemical_filter']) && !empty($_GET['chemical_filter'])) {
        $whereClauses[] = "chemical_name LIKE :chemical_name";
        $params[':chemical_name'] = '%' . $_GET['chemical_filter'] . '%';
    }

    // Build WHERE clause
    if (!empty($whereClauses)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereClauses);
    }

    // Expiration sorting
    $orderBy = " ORDER BY ";
    if (isset($_GET['sort']) && $_GET['sort'] === 'expiration') {
        $orderBy .= "expiration_date ASC";
    } else {
        $orderBy .= "id DESC";
    }

    // Prepare final query
    $query = $baseQuery . $orderBy;
    $stmt = $pdo->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $chemicals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemical Inventory - MacJ Pest Control</title>
    <link rel="stylesheet" href="css/chemical-inventory-page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/modern-modal.css">
    <style>
        /* Action buttons in header */
        .action-buttons {
            display: flex;
            gap: 10px;
        }



        .user-info {
            display: flex;
            align-items: center;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-color);
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-light);
        }



        /* Expired Chemicals Alert Styles */
        .expired-chemicals-alert {
            background-color: #fff0f0;
            border: 1px solid #ffcccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .expired-chemicals-alert i {
            font-size: 24px;
            color: #cc0000;
        }

        .expired-chemicals-alert strong {
            color: #cc0000;
        }

        /* Expired date styling in the main table */
        .expired-date {
            color: #cc0000 !important;
            font-weight: bold;
            position: relative;
        }

        .expired-date::before {
            content: "\f06a"; /* Font Awesome exclamation circle */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 5px;
            color: #cc0000;
        }

        /* Highlight the entire row for expired chemicals */
        tr.expired-chemical {
            background-color: #fff0f0 !important;
        }

        tr.expired-chemical:hover {
            background-color: #ffe6e6 !important;
        }

        /* Dilution Calculator Styles */
        .formula-display {
            font-family: monospace;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin: 5px 0;
            display: inline-block;
        }

        .fraction {
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            margin: 0 5px;
        }

        .fraction .numerator {
            border-bottom: 1px solid #000;
            padding: 0 4px;
            display: block;
        }

        .fraction .denominator {
            padding: 0 4px;
            display: block;
        }

        .dilution-preview {
            font-size: 0.95rem;
        }

        .dilution-preview ol {
            padding-left: 20px;
        }

        .dilution-preview li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
   <!-- Header -->
   <header class="header">
        <div class="header-title">
            <h1>Admin Dashboard</h1>
        </div>
        <div class="user-menu">


            <div class="user-info">
                <?php
                // Check if profile picture exists
                $profile_picture_url = "../assets/default-profile.jpg";
                if (isset($_SESSION['user_id'])) {
                    $staff_id = $_SESSION['user_id'];
                    $profile_picture = '';

                    // Check if the office_staff table has profile_picture column
                    try {
                        $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM office_staff LIKE 'profile_picture'");
                        $checkColumnStmt->execute();
                        if ($checkColumnStmt->rowCount() > 0) {
                            $stmt = $pdo->prepare("SELECT profile_picture FROM office_staff WHERE staff_id = ?");
                            $stmt->execute([$staff_id]);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($row) {
                                $profile_picture = $row['profile_picture'];
                            }
                        }
                    } catch (PDOException $e) {
                        // Log error but continue
                        error_log("Error fetching profile picture: " . $e->getMessage());
                    }

                    $profile_picture_url = !empty($profile_picture)
                        ? "../uploads/admin/" . $profile_picture
                        : "../assets/default-profile.jpg";
                }
                ?>
                <img src="<?php echo $profile_picture_url; ?>" alt="Profile" class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <div>
                    <div class="user-name"><?= $_SESSION['username'] ?? 'Admin' ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>MacJ Pest Control</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li class="active"><a href="chemical_inventory.php"><i class="fas fa-flask"></i> Chemical Inventory</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
                    <li><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                    <li><a href="SignOut.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <div class="chemicals-content">
                <div class="chemicals-header">
                    <h1><i class="fas fa-flask"></i> Chemical Inventory</h1>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#chemicalModal">
                            <i class="fas fa-plus"></i> Add New Chemical
                        </button>
                    </div>
                </div>

                <!-- Inventory Summary -->
                <div class="inventory-summary">
                    <div class="summary-card">
                        <div class="summary-icon" style="background-color: var(--primary-color);">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Total Chemicals</h3>
                            <p><?= $total_chemicals ?></p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon" style="background-color: var(--warning-color);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Low Stock</h3>
                            <p><?= $low_stock ?></p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon" style="background-color: var(--danger-color);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Out of Stock</h3>
                            <p><?= $out_of_stock ?></p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon" style="background-color: var(--info-color);">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Expiring Soon</h3>
                            <p><?= $expiring_soon ?></p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon" style="background-color: #cc0000;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Expired</h3>
                            <p><?= $expired_count ?></p>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="filter-container">
                    <form id="filterForm" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                        <div class="filter-group">
                            <label for="chemical-type">Chemical:</label>
                            <div class="input-group">
                                <input type="text" id="chemical-type" name="chemical_filter" class="form-control" placeholder="Search chemical name" value="<?= isset($_GET['chemical_filter']) ? htmlspecialchars($_GET['chemical_filter']) : '' ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label for="chemical-status">Status:</label>
                            <select id="chemical-status" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="In Stock" <?= isset($_GET['status']) && $_GET['status'] === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                                <option value="Low Stock" <?= isset($_GET['status']) && $_GET['status'] === 'Low Stock' ? 'selected' : '' ?>>Low Stock</option>
                                <option value="Out of Stock" <?= isset($_GET['status']) && $_GET['status'] === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="sort-by">Sort by:</label>
                            <select id="sort-by" name="sort" onchange="this.form.submit()">
                                <option value="">Newest Ordered</option>
                                <option value="expiration" <?= isset($_GET['sort']) && $_GET['sort'] === 'expiration' ? 'selected' : '' ?>>Closest To Expiration Date</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-type">Filter:</label>
                            <select id="filter-type" name="filter" onchange="this.form.submit()">
                                <option value="">All Chemicals</option>
                                <option value="expired" <?= isset($_GET['filter']) && $_GET['filter'] === 'expired' ? 'selected' : '' ?>>Expired Only</option>
                            </select>
                        </div>
                    </form>
                </div>

                <?php if ($expired_count > 0): ?>
                <!-- Expired Chemicals Alert -->
                <div class="expired-chemicals-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Warning:</strong> There are <?= $expired_count ?> expired chemicals in your inventory that should be disposed of properly.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Chemicals Section -->
                <div class="chemicals-section">

                    <div class="chemicals-table-container">
                        <table class="chemicals-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Chemical Name</th>
                                    <th>Type</th>
                                    <th>Target Pest</th>
                                    <th>Quantity</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th>Last Ordered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chemicals as $chemical):
                                    $isExpired = strtotime($chemical['expiration_date']) < strtotime('today');
                                ?>
                                <tr class="<?= $isExpired ? 'expired-chemical' : '' ?>">
                                    <td><?= $chemical['id'] ?></td>
                                    <td><?= htmlspecialchars($chemical['chemical_name']) ?></td>
                                    <td><?= htmlspecialchars($chemical['type']) ?></td>
                                    <td><?= htmlspecialchars($chemical['target_pest'] ?? 'Not specified') ?></td>
                                    <td><?= number_format($chemical['quantity'], 2) ?> <?= $chemical['unit'] ?></td>
                                    <td class="<?= $isExpired ? 'expired-date' : '' ?>">
                                        <?= date('M d, Y', strtotime($chemical['expiration_date'])) ?>
                                    </td>
                                     <td>
                                         <span class="status-badge <?= match($chemical['status']) {
                                             'In Stock' => 'in-stock',
                                             'Low Stock' => 'low-stock',
                                             default => 'out-of-stock'
                                         } ?>"><?= $chemical['status'] ?></span>
                                     </td>
                                     <td><?php
                                         $lastOrdered = $chemical['last_ordered'] ?? $chemical['created_at'] ?? null;
                                         echo $lastOrdered ? date('M d, Y', strtotime($lastOrdered)) : 'Not set';
                                     ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-sm btn-info view-btn" data-id="<?= $chemical['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-sm btn-primary edit-btn" data-id="<?= $chemical['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-sm btn-danger delete-btn" data-id="<?= $chemical['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- Create Modal -->
        <div class="modal fade" id="chemicalModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="chemicalForm">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-flask"></i>Add New Chemical</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body create-chemical-container">
                            <div class="detail-section">
                                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="chemical_name"><i class="fas fa-flask"></i> Chemical Name</label>
                                            <input type="text" class="form-control" id="chemical_name" name="chemical_name" placeholder="Enter Chemical Name" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required" for="type"><i class="fas fa-tag"></i> Type</label>
                                            <select class="form-control" id="type" name="type" required>
                                                <option value="">Select Type</option>
                                                <option>Insecticide</option>
                                                <option>Herbicide</option>
                                                <option>Rodenticide</option>
                                                <option>Fungicide</option>
                                                <option>Disinfection</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="target_pest"><i class="fas fa-bug"></i> Target Pest</label>
                                            <select class="form-control" name="target_pest" id="target_pest">
                                                <option value="">Select Target Pest</option>
                                                <option>Crawling & Flying Pest</option>
                                                <option>Flying Pest</option>
                                                <option>Crawling Pest</option>
                                                <option>Cockroaches</option>
                                                <option>Termites</option>
                                                <option>Rodents</option>
                                                <option>Mosquitoes</option>
                                                <option>Ants</option>
                                                <option>Bed Bugs</option>
                                                <option>Flies</option>
                                                <option>Grass Problems</option>
                                                <option>Weeds</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="quantity"><i class="fas fa-balance-scale"></i> Quantity</label>
                                            <input type="number" step="0.01" class="form-control" id="quantity"
                                                name="quantity" min="0" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required" for="unit"><i class="fas fa-ruler"></i> Unit</label>
                                            <select class="form-control" id="unit" name="unit" required>
                                                <option>Liters</option>
                                                <option>Kilograms</option>
                                                <option>Grams</option>
                                                <option>Pieces</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-building"></i> Supplier Information</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="manufacturer"><i class="fas fa-industry"></i> Manufacturer</label>
                                            <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="Enter manufacturer name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="supplier"><i class="fas fa-truck"></i> Supplier</label>
                                            <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Enter supplier name">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-calculator"></i> Dilution Calculator</h3>
                                <!-- Manual Input Dilution Calculator -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5><i class="fas fa-calculator"></i> Manual Input Dilution Calculator</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="manual_area"><i class="fas fa-ruler-combined"></i> Area in sq. m:</label>
                                                            <input type="number" step="0.01" min="0" class="form-control"
                                                                id="manual_area" placeholder="e.g., 100">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="manual_solution_rate"><i class="fas fa-tint"></i> Solution rate per sq. m:</label>
                                                            <input type="number" step="0.01" min="0" class="form-control"
                                                                id="manual_solution_rate" placeholder="e.g., 5">
                                                            <small class="form-text text-muted">Liters of solution needed per square meter</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="manual_dilution_ratio"><i class="fas fa-balance-scale"></i> Dilution ratio:</label>
                                                            <input type="text" class="form-control"
                                                                id="manual_dilution_ratio" placeholder="e.g., 1:100">
                                                            <small class="form-text text-muted">Format: chemical:water (e.g., 1:100)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <button class="btn btn-primary" id="calculate_manual_dilution">
                                                            <i class="fas fa-calculator"></i> Calculate
                                                        </button>
                                                        <button class="btn btn-secondary" id="clear_manual_dilution">
                                                            <i class="fas fa-eraser"></i> Clear
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div id="manual_dilution_result" class="card bg-light" style="display: none;">
                                                            <div class="card-body">
                                                                <h6><i class="fas fa-check-circle"></i> Calculation Results:</h6>
                                                                <div id="manual_dilution_output"></div>
                                                             </div>
                                                         </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <input type="hidden" name="dilution_rate" id="hidden_dilution_rate">
                                  <input type="hidden" name="area_coverage" id="hidden_area_coverage">
                                  <input type="hidden" name="manual_area" id="hidden_manual_area">
                                  <input type="hidden" name="manual_solution_rate" id="hidden_manual_solution_rate">
                                  <input type="hidden" name="manual_dilution_ratio" id="hidden_manual_dilution_ratio">
                              </div>
                                      </div>
                                  </div>

                             </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-file-alt"></i> Additional Details</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="expiration_date"><i class="fas fa-calendar-alt"></i> Expiration Date</label>
                                            <input type="date" class="form-control" id="expiration_date"
                                                name="expiration_date" required
                                                min="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief description of the chemical"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="safety_info"><i class="fas fa-shield-alt"></i> Safety Information</label>
                                            <textarea class="form-control" id="safety_info" name="safety_info" rows="4" placeholder="Safety precautions and handling instructions"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Chemical</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- View Chemical Modal -->
        <div class="modal fade" id="viewChemicalModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-eye"></i>Chemical Details</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body view-chemical-container">
                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> Chemical Information</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-flask"></i> Chemical Name</div>
                                    <div class="detail-value" id="viewChemicalName"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-tag"></i> Type</div>
                                    <div class="detail-value" id="viewType"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-bug"></i> Target Pest</div>
                                    <div class="detail-value" id="viewTargetPest"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-balance-scale"></i> Quantity</div>
                                    <div class="detail-value" id="viewQuantity"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-industry"></i> Manufacturer</div>
                                    <div class="detail-value" id="viewManufacturer"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-truck"></i> Supplier</div>
                                    <div class="detail-value" id="viewSupplier"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-calendar-alt"></i> Expiration Date</div>
                                    <div class="detail-value" id="viewExpirationDate"></div>
                                </div>
                                 <div class="detail-item">
                                     <div class="detail-label"><i class="fas fa-ruler"></i> Unit</div>
                                     <div class="detail-value" id="viewUnit"></div>
                                 </div>
                             </div>
                         </div>

                             <div class="detail-section">
                             <h3><i class="fas fa-calculator"></i> Dilution Information</h3>
                             <div class="detail-grid">
                                 <div class="detail-item">
                                     <div class="detail-label"><i class="fas fa-ruler-combined"></i> Area</div>
                                     <div class="detail-value" id="viewArea">Not specified</div>
                                 </div>
                                 <div class="detail-item">
                                     <div class="detail-label"><i class="fas fa-tint"></i> Solution Rate per sq. m</div>
                                     <div class="detail-value" id="viewSolutionRate">Not specified</div>
                                 </div>
                                 <div class="detail-item">
                                     <div class="detail-label"><i class="fas fa-balance-scale"></i> Dilution Ratio</div>
                                     <div class="detail-value" id="viewDilutionRatio">Not specified</div>
                                 </div>
                                 <div class="detail-item">
                                     <div class="detail-label"><i class="fas fa-calculator"></i> Calculation Example</div>
                                     <div class="detail-value" id="viewDilutionExample">
                                         <div class="dilution-preview">
                                             <p><strong>For 200 m² area:</strong></p>
                                             <ol>
                                                 <li>Total solution needed: <span id="viewTotalSolution">2</span> liters</li>
                                                 <li>Total chemical required: <span id="viewTotalChemical">40</span> ml</li>
                                             </ol>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                        <div class="detail-section">
                            <h3><i class="fas fa-file-alt"></i> Additional Details</h3>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-align-left"></i> Description</div>
                                <div class="detail-value" id="viewDescription"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-shield-alt"></i> Safety Information</div>
                                <div class="detail-value" id="viewSafetyInfo"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editChemicalModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="editChemicalForm">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-edit"></i>Edit Chemical</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                         <div class="modal-body edit-chemical-container">
                             <input type="hidden" name="id" id="editChemicalId">
                             <input type="hidden" name="dilution_rate" id="edit_hidden_dilution_rate">
                             <input type="hidden" name="area_coverage" id="edit_hidden_area_coverage">
                             <input type="hidden" name="manual_area" id="edit_hidden_manual_area">
                             <input type="hidden" name="manual_solution_rate" id="edit_hidden_manual_solution_rate">
                             <input type="hidden" name="manual_dilution_ratio" id="edit_hidden_manual_dilution_ratio">

                             <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Note:</strong> All fields can now be updated. Make sure to review your changes before submitting.
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="editChemicalName"><i class="fas fa-flask"></i> Chemical Name</label>
                                            <input type="text" class="form-control" id="editChemicalName" name="chemical_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required" for="editType"><i class="fas fa-tag"></i> Type</label>
                                            <select class="form-control" id="editType" name="type" required>
                                                <option value="">Select Type</option>
                                                <option>Insecticide</option>
                                                <option>Herbicide</option>
                                                <option>Rodenticide</option>
                                                <option>Fungicide</option>
                                                <option>Disinfection</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="editTargetPest"><i class="fas fa-bug"></i> Target Pest</label>
                                            <select class="form-control" name="target_pest" id="editTargetPest">
                                                <option value="">Select Target Pest</option>
                                                <option>Crawling & Flying Pest</option>
                                                <option>Flying Pest</option>
                                                <option>Crawling Pest</option>
                                                <option>Cockroaches</option>
                                                <option>Termites</option>
                                                <option>Rodents</option>
                                                <option>Mosquitoes</option>
                                                <option>Ants</option>
                                                <option>Bed Bugs</option>
                                                <option>Flies</option>
                                                <option>Grass Problems</option>
                                                <option>Weeds</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="editQuantity"><i class="fas fa-balance-scale"></i> Quantity</label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="quantity" id="editQuantity" min="0" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required" for="editUnit"><i class="fas fa-ruler"></i> Unit</label>
                                            <select class="form-control" id="editUnit" name="unit" required>
                                                <option>Liters</option>
                                                <option>Kilograms</option>
                                                <option>Grams</option>
                                                <option>Pieces</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-building"></i> Supplier Information</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="editManufacturer"><i class="fas fa-industry"></i> Manufacturer</label>
                                            <input type="text" class="form-control" id="editManufacturer" name="manufacturer">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="editSupplier"><i class="fas fa-truck"></i> Supplier</label>
                                            <input type="text" class="form-control" id="editSupplier" name="supplier">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-calculator"></i> Dilution Calculator</h3>
                                <!-- Manual Input Dilution Calculator -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5><i class="fas fa-calculator"></i> Manual Input Dilution Calculator</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="edit_manual_area"><i class="fas fa-ruler-combined"></i> Area in sq. m:</label>
                                                            <input type="number" step="0.01" min="0" class="form-control"
                                                                id="edit_manual_area" placeholder="e.g., 100">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="edit_manual_solution_rate"><i class="fas fa-tint"></i> Solution rate per sq. m:</label>
                                                            <input type="number" step="0.01" min="0" class="form-control"
                                                                id="edit_manual_solution_rate" placeholder="e.g., 5">
                                                            <small class="form-text text-muted">Liters of solution needed per square meter</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="edit_manual_dilution_ratio"><i class="fas fa-balance-scale"></i> Dilution ratio:</label>
                                                            <input type="text" class="form-control"
                                                                id="edit_manual_dilution_ratio" placeholder="e.g., 1:100">
                                                            <small class="form-text text-muted">Format: chemical:water (e.g., 1:100)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                     <div class="col-12">
                                                         <button type="button" class="btn btn-primary" id="calculate_edit_manual_dilution">
                                                             <i class="fas fa-calculator"></i> Calculate
                                                         </button>
                                                         <button type="button" class="btn btn-secondary" id="clear_edit_manual_dilution">
                                                             <i class="fas fa-eraser"></i> Clear
                                                         </button>
                                                     </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div id="edit_manual_dilution_result" class="card bg-light" style="display: none;">
                                                            <div class="card-body">
                                                                <h6><i class="fas fa-check-circle"></i> Calculation Results:</h6>
                                                                <div id="edit_manual_dilution_output"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3><i class="fas fa-file-alt"></i> Additional Details</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="editExpirationDate"><i class="fas fa-calendar-alt"></i> Expiration Date</label>
                                            <input type="date" class="form-control" id="editExpirationDate" name="expiration_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editDescription"><i class="fas fa-align-left"></i> Description</label>
                                            <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="editSafetyInfo"><i class="fas fa-shield-alt"></i> Safety Information</label>
                                            <textarea class="form-control" id="editSafetyInfo" name="safety_info" rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Information</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
    // Manual Dilution Calculator Function
    function calculateManualDilution() {
        const area = parseFloat($('#manual_area').val());
        const solutionRate = parseFloat($('#manual_solution_rate').val());
        const ratioStr = $('#manual_dilution_ratio').val().trim();

        // Validate inputs
        if (isNaN(area) || area <= 0) {
            alert('Please enter a valid area in square meters.');
            return;
        }
        if (isNaN(solutionRate) || solutionRate <= 0) {
            alert('Please enter a valid solution rate per square meter.');
            return;
        }
        if (!ratioStr || !ratioStr.includes(':')) {
            alert('Please enter a valid dilution ratio (e.g., 1:100).');
            return;
        }

        // Parse dilution ratio (chemical:water)
        const ratioParts = ratioStr.split(':');
        if (ratioParts.length !== 2) {
            alert('Invalid dilution ratio format. Please use format like 1:100.');
            return;
        }

        const chemicalPart = parseFloat(ratioParts[0]);
        const waterPart = parseFloat(ratioParts[1]);

        if (isNaN(chemicalPart) || isNaN(waterPart) || chemicalPart <= 0 || waterPart <= 0) {
            alert('Please enter valid numbers in the dilution ratio.');
            return;
        }

        // Calculate total solution needed
        const totalSolution = area * solutionRate;

        // Calculate chemical needed
        // Dilution ratio 1:100 means 1 part chemical per 100 parts of total solution
        // Chemical needed = (chemicalPart / waterPart) × Total solution
        const chemicalNeeded = (chemicalPart / waterPart) * totalSolution;

        // Calculate water needed
        const waterNeeded = totalSolution - chemicalNeeded;

        // Display results
        const output = `
            <div class="dilution-preview">
                <p><strong>For a ${area} sq. m area:</strong></p>
                <ul>
                    <li><strong>Total solution needed:</strong> ${totalSolution.toFixed(2)} liters</li>
                    <li><strong>Chemical needed:</strong> ${chemicalNeeded.toFixed(2)} liters</li>
                    <li><strong>Water needed:</strong> ${waterNeeded.toFixed(2)} liters</li>
                </ul>
                <p><strong>Explanation:</strong></p>
                <p>
                    With a dilution ratio of ${ratioStr}, for every ${waterPart} parts of total solution, 
                    ${chemicalPart} part(s) is chemical.
                    <br><br>
                    Calculation:
                    <br>
                    • Total solution = Area × Solution rate = ${area} × ${solutionRate} = ${totalSolution.toFixed(2)} liters
                    <br>
                    • Chemical = (Chemical part / Solution part) × Total solution = (${chemicalPart} / ${waterPart}) × ${totalSolution.toFixed(2)} = ${chemicalNeeded.toFixed(2)} liters
                    <br>
                    • Water = Total solution - Chemical = ${totalSolution.toFixed(2)} - ${chemicalNeeded.toFixed(2)} = ${waterNeeded.toFixed(2)} liters
                </p>
            </div>
        `;

        $('#manual_dilution_output').html(output);
        $('#manual_dilution_result').show();

        // Set hidden fields for saving
        const dilutionRate = (chemicalNeeded / totalSolution) * 1000;
        const areaCoverage = area / totalSolution;
        $('#hidden_dilution_rate').val(dilutionRate.toFixed(2));
        $('#hidden_area_coverage').val(areaCoverage.toFixed(2));
        $('#hidden_manual_area').val(area);
        $('#hidden_manual_solution_rate').val(solutionRate);
        $('#hidden_manual_dilution_ratio').val(ratioStr);

        // Mark that calculation was performed
        $('#chemicalForm').data('calculation-performed', true);
    }

    // Clear manual dilution calculator
    function clearManualDilution() {
        $('#manual_area').val('');
        $('#manual_solution_rate').val('');
        $('#manual_dilution_ratio').val('');
        $('#manual_dilution_result').hide();
        $('#manual_dilution_output').html('');
        $('#hidden_dilution_rate').val('');
        $('#hidden_area_coverage').val('');
        $('#hidden_manual_area').val('');
        $('#hidden_manual_solution_rate').val('');
        $('#hidden_manual_dilution_ratio').val('');

        // Reset calculation flag
        $('#chemicalForm').data('calculation-performed', false);
    }

        // Edit Modal Manual Dilution Calculator Function
    function calculateEditManualDilution() {
        const area = parseFloat($('#edit_manual_area').val());
        const solutionRate = parseFloat($('#edit_manual_solution_rate').val());
        const ratioStr = $('#edit_manual_dilution_ratio').val().trim();

        // Hide results initially in case validation fails
        $('#edit_manual_dilution_result').hide();

        // Validate inputs
        if (isNaN(area) || area <= 0) {
            alert('Please enter a valid area in square meters.');
            return;
        }
        if (isNaN(solutionRate) || solutionRate <= 0) {
            alert('Please enter a valid solution rate per square meter.');
            return;
        }
        if (!ratioStr || !ratioStr.includes(':')) {
            alert('Please enter a valid dilution ratio (e.g., 1:100).');
            return;
        }

        // Parse dilution ratio (chemical:water)
        const ratioParts = ratioStr.split(':');
        if (ratioParts.length !== 2) {
            alert('Invalid dilution ratio format. Please use format like 1:100.');
            return;
        }

        const chemicalPart = parseFloat(ratioParts[0]);
        const waterPart = parseFloat(ratioParts[1]);

        if (isNaN(chemicalPart) || isNaN(waterPart) || chemicalPart <= 0 || waterPart <= 0) {
            alert('Please enter valid numbers in the dilution ratio.');
            return;
        }

        // Calculate total solution needed
        const totalSolution = area * solutionRate;

        // Calculate chemical needed
        // Dilution ratio 1:100 means 1 part chemical per 100 parts of total solution
        // Chemical needed = (chemicalPart / waterPart) × Total solution
        const chemicalNeeded = (chemicalPart / waterPart) * totalSolution;

        // Calculate water needed
        const waterNeeded = totalSolution - chemicalNeeded;

        // Display results
        const output = `
            <div class="dilution-preview">
                <p><strong>For a ${area} sq. m area:</strong></p>
                <ul>
                    <li><strong>Total solution needed:</strong> ${totalSolution.toFixed(2)} liters</li>
                    <li><strong>Chemical needed:</strong> ${chemicalNeeded.toFixed(2)} liters</li>
                    <li><strong>Water needed:</strong> ${waterNeeded.toFixed(2)} liters</li>
                </ul>
                <p><strong>Explanation:</strong></p>
                <p>
                    With a dilution ratio of ${ratioStr}, for every ${waterPart} parts of total solution, 
                    ${chemicalPart} part(s) is chemical.
                    <br><br>
                    Calculation:
                    <br>
                    • Total solution = Area × Solution rate = ${area} × ${solutionRate} = ${totalSolution.toFixed(2)} liters
                    <br>
                    • Chemical = (Chemical part / Solution part) × Total solution = (${chemicalPart} / ${waterPart}) × ${totalSolution.toFixed(2)} = ${chemicalNeeded.toFixed(2)} liters
                    <br>
                    • Water = Total solution - Chemical = ${totalSolution.toFixed(2)} - ${chemicalNeeded.toFixed(2)} = ${waterNeeded.toFixed(2)} liters
                </p>
            </div>
        `;

        $('#edit_manual_dilution_output').html(output);
        $('#edit_manual_dilution_result').show();

        // Set hidden fields for saving
        const dilutionRate = (chemicalNeeded / totalSolution) * 1000;
        const areaCoverage = area / totalSolution;
        $('#edit_hidden_dilution_rate').val(dilutionRate.toFixed(2));
        $('#edit_hidden_area_coverage').val(areaCoverage.toFixed(2));
        $('#edit_hidden_manual_area').val(area);
        $('#edit_hidden_manual_solution_rate').val(solutionRate);
        $('#edit_hidden_manual_dilution_ratio').val(ratioStr);

        // Mark that calculation was performed
        $('#editChemicalForm').data('calculation-performed', true);
    }

        // Clear edit modal manual dilution calculator
        function clearEditManualDilution() {
            $('#edit_manual_area').val('');
            $('#edit_manual_solution_rate').val('');
            $('#edit_manual_dilution_ratio').val('');
            $('#edit_manual_dilution_result').hide();
            $('#edit_manual_dilution_output').html('');
            $('#edit_hidden_dilution_rate').val('');
            $('#edit_hidden_area_coverage').val('');
            $('#edit_hidden_manual_area').val('');
            $('#edit_hidden_manual_solution_rate').val('');
            $('#edit_hidden_manual_dilution_ratio').val('');

            // Reset calculation flag
            $('#editChemicalForm').data('calculation-performed', false);
        }

    $(document).ready(function() {
        // Set default values for common chemicals
        $('#target_pest').on('change', function() {
            const pestType = $(this).val();
            const chemicalType = $('select[name="type"]').val();

            // Show helpful hint based on selection
            let hint = '';
            if (pestType === 'Termites') {
                hint = 'Common dilution ratio for termites: 1:100';
            } else if (pestType === 'Cockroaches' || pestType === 'Ants' || pestType === 'Bed Bugs') {
                hint = 'Common dilution ratio: 1:100';
            } else if (pestType === 'Mosquitoes' || pestType === 'Flies') {
                hint = 'Common dilution ratio: 1:100';
            } else if (pestType === 'Grass Problems' || pestType === 'Weeds') {
                hint = 'Common dilution ratio for herbicides: 1:50';
            }

            // You can display this hint if needed
            console.log(hint);
        });

        // Also update when chemical type changes
        $('select[name="type"]').on('change', function() {
            // Trigger the target pest change handler
            $('#target_pest').trigger('change');
        });

        // Manual Dilution Calculator event handlers
        $('#calculate_manual_dilution').on('click', function() {
            calculateManualDilution();
        });

        $('#clear_manual_dilution').on('click', function() {
            clearManualDilution();
        });

        // Edit Modal Manual Dilution Calculator event handlers
        $('#calculate_edit_manual_dilution').on('click', function() {
            calculateEditManualDilution();
        });

        $('#clear_edit_manual_dilution').on('click', function() {
            clearEditManualDilution();
        });

        // Clear hidden fields when inputs change to prevent stale data
        $('#manual_area, #manual_solution_rate, #manual_dilution_ratio').on('input', function() {
            $('#hidden_dilution_rate').val('');
            $('#hidden_area_coverage').val('');
            $('#hidden_manual_area').val('');
            $('#hidden_manual_solution_rate').val('');
            $('#hidden_manual_dilution_ratio').val('');
            $('#chemicalForm').data('calculation-performed', false);
            $('#manual_dilution_result').hide();
        });

        $('#edit_manual_area, #edit_manual_solution_rate, #edit_manual_dilution_ratio').on('input', function() {
            $('#edit_hidden_dilution_rate').val('');
            $('#edit_hidden_area_coverage').val('');
            $('#edit_hidden_manual_area').val('');
            $('#edit_hidden_manual_solution_rate').val('');
            $('#edit_hidden_manual_dilution_ratio').val('');
            $('#editChemicalForm').data('calculation-performed', false);
            $('#edit_manual_dilution_result').hide();
        });

        // Create new chemical
        $('#chemicalForm').submit(function(e) {
            e.preventDefault();

            // Check if calculation was performed before allowing submission
            if (!$('#chemicalForm').data('calculation-performed')) {
                alert('Please perform a dilution calculation using the calculator before adding the chemical.');
                return;
            }

            // Validate dilution calculation consistency
            const currentArea = parseFloat($('#manual_area').val());
            const currentSolutionRate = parseFloat($('#manual_solution_rate').val());
            const currentRatioStr = $('#manual_dilution_ratio').val().trim();

            if (currentArea && currentSolutionRate && currentRatioStr) {
                // Recalculate expected values
                const ratioParts = currentRatioStr.split(':');
                const chemicalPart = parseFloat(ratioParts[0]);
                const waterPart = parseFloat(ratioParts[1]);
                const totalSolution = currentArea * currentSolutionRate;
                const chemicalNeeded = (chemicalPart / waterPart) * totalSolution;
                const expectedDilutionRate = (chemicalNeeded / totalSolution) * 1000;
                const expectedAreaCoverage = currentArea / totalSolution;

                const storedDilutionRate = parseFloat($('#hidden_dilution_rate').val());
                const storedAreaCoverage = parseFloat($('#hidden_area_coverage').val());

                if (Math.abs(expectedDilutionRate - storedDilutionRate) > 0.01 ||
                    Math.abs(expectedAreaCoverage - storedAreaCoverage) > 0.01) {
                    alert('The dilution calculation appears inconsistent with current inputs. Please recalculate using the calculator before submitting.');
                    return;
                }
            }

            const formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'chemical_inventory.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#chemicalModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.error || 'Failed to save chemical'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Error: Could not save chemical. Please try again.');
                }
            });
        });

        // Edit chemical
        $(document).on('click', '.edit-btn', function() {
            const chemicalId = $(this).data('id');

            $.ajax({
                url: 'get_chemical.php',
                method: 'GET',
                data: { id: chemicalId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('#editChemicalId').val(response.data.id);
                        $('#editChemicalName').val(response.data.chemical_name);

                        // Set type dropdown value
                        $('#editType option').each(function() {
                            if ($(this).text() === response.data.type) {
                                $(this).prop('selected', true);
                            }
                        });

                        // Set target pest dropdown value
                        if (response.data.target_pest) {
                            $('#editTargetPest option').each(function() {
                                if ($(this).text() === response.data.target_pest) {
                                    $(this).prop('selected', true);
                                }
                            });
                        } else {
                            $('#editTargetPest').val('');
                        }

                        $('#editQuantity').val(response.data.quantity);

                        // Set unit dropdown value
                        $('#editUnit option').each(function() {
                            if ($(this).text() === response.data.unit) {
                                $(this).prop('selected', true);
                            }
                        });

                        $('#editManufacturer').val(response.data.manufacturer);
                        $('#editSupplier').val(response.data.supplier);
                        $('#editExpirationDate').val(response.data.expiration_date);
                        $('#editDescription').val(response.data.description);
                        $('#editSafetyInfo').val(response.data.safety_info);

                        // Display dilution calculation results in edit modal
                        const dilutionRate = response.data.dilution_rate || 20;
                        const areaCoverage = response.data.area_coverage || 100;

                        let area;
                        if (response.data.manual_area) {
                            area = response.data.manual_area;
                        } else {
                            area = 100;
                        }

                        // Populate the manual calculator inputs with values that produce the stored calculation
                        $('#edit_manual_area').val(area);
                        $('#edit_manual_solution_rate').val((1 / areaCoverage).toFixed(4));
                        $('#edit_manual_dilution_ratio').val(dilutionRate + ':' + (1000 - dilutionRate));

                        // Calculate example values
                        const targetArea = area;
                        const totalSolution = (targetArea / areaCoverage).toFixed(2);
                        const totalChemical = (dilutionRate * totalSolution).toFixed(2);
                        const waterNeeded = (totalSolution - totalChemical / 1000).toFixed(2);

                        const output = `
                            <div class="dilution-preview">
                                <p><strong>For a ${targetArea} sq. m area:</strong></p>
                                <ul>
                                    <li><strong>Total solution needed:</strong> ${totalSolution} liters</li>
                                    <li><strong>Chemical needed:</strong> ${totalChemical} ml</li>
                                    <li><strong>Water needed:</strong> ${waterNeeded} liters</li>
                                </ul>
                                <p><strong>Explanation:</strong></p>
                                <p>
                                    With a dilution rate of ${dilutionRate} ml per liter and area coverage of ${areaCoverage} m² per liter:
                                    <br><br>
                                    Calculation:
                                    <br>
                                    • Total solution = Area ÷ Area coverage = ${targetArea} ÷ ${areaCoverage} = ${totalSolution} liters
                                    <br>
                                    • Chemical = Dilution rate × Total solution = ${dilutionRate} × ${totalSolution} = ${totalChemical} ml
                                    <br>
                                    • Water = Total solution - Chemical = ${totalSolution} - ${totalChemical / 1000} = ${waterNeeded} liters
                                </p>
                            </div>
                        `;

                        $('#edit_manual_dilution_output').html(output);

                        // Set hidden fields
                        $('#edit_hidden_dilution_rate').val(dilutionRate);
                        $('#edit_hidden_area_coverage').val(areaCoverage);

                        if (response.data.manual_area) {
                            $('#edit_manual_area').val(response.data.manual_area);
                            $('#edit_manual_solution_rate').val(response.data.manual_solution_rate);
                            $('#edit_manual_dilution_ratio').val(response.data.manual_dilution_ratio);
                            $('#edit_hidden_manual_area').val(response.data.manual_area);
                            $('#edit_hidden_manual_solution_rate').val(response.data.manual_solution_rate);
                            $('#edit_hidden_manual_dilution_ratio').val(response.data.manual_dilution_ratio);
                        } else {
                            // populate with reverse calculated
                            $('#edit_manual_area').val(targetArea);
                            $('#edit_manual_solution_rate').val((1 / areaCoverage).toFixed(4));
                            $('#edit_manual_dilution_ratio').val(dilutionRate + ':' + (1000 - dilutionRate));
                            $('#edit_hidden_manual_area').val(targetArea);
                            $('#edit_hidden_manual_solution_rate').val((1 / areaCoverage).toFixed(4));
                            $('#edit_hidden_manual_dilution_ratio').val(dilutionRate + ':' + (1000 - dilutionRate));
                        }

                        $('#editChemicalModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Error: Could not load chemical details. Please try again.');
                }
            });
        });

        // Update chemical
        $('#editChemicalForm').submit(function(e) {
            e.preventDefault();

            // Check if calculation was performed before allowing submission
            if (!$('#editChemicalForm').data('calculation-performed')) {
                alert('Please perform a dilution calculation using the calculator before updating the chemical information.');
                return;
            }

            // Validate dilution calculation consistency
            const currentArea = parseFloat($('#edit_manual_area').val());
            const currentSolutionRate = parseFloat($('#edit_manual_solution_rate').val());
            const currentRatioStr = $('#edit_manual_dilution_ratio').val().trim();

            if (currentArea && currentSolutionRate && currentRatioStr) {
                // Recalculate expected values
                const ratioParts = currentRatioStr.split(':');
                const chemicalPart = parseFloat(ratioParts[0]);
                const waterPart = parseFloat(ratioParts[1]);
                const totalSolution = currentArea * currentSolutionRate;
                const chemicalNeeded = (chemicalPart / waterPart) * totalSolution;
                const expectedDilutionRate = (chemicalNeeded / totalSolution) * 1000;
                const expectedAreaCoverage = currentArea / totalSolution;

                const storedDilutionRate = parseFloat($('#edit_hidden_dilution_rate').val());
                const storedAreaCoverage = parseFloat($('#edit_hidden_area_coverage').val());

                if (Math.abs(expectedDilutionRate - storedDilutionRate) > 0.01 ||
                    Math.abs(expectedAreaCoverage - storedAreaCoverage) > 0.01) {
                    alert('The dilution calculation appears inconsistent with current inputs. Please recalculate using the calculator before submitting.');
                    return;
                }
            }

            if(confirm('Are you sure you want to update this chemical?')) {
                const formData = {
                    id: $('#editChemicalId').val(),
                    chemical_name: $('#editChemicalName').val(),
                    type: $('#editType').val(),
                    target_pest: $('#editTargetPest').val(),
                    quantity: $('#editQuantity').val(),
                    unit: $('#editUnit').val(),
                    manufacturer: $('#editManufacturer').val(),
                    supplier: $('#editSupplier').val(),
                    expiration_date: $('#editExpirationDate').val(),
                    description: $('#editDescription').val(),
                    safety_info: $('#editSafetyInfo').val(),
                    dilution_rate: $('#edit_hidden_dilution_rate').val(),
                    area_coverage: $('#edit_hidden_area_coverage').val(),
                    manual_area: $('#edit_hidden_manual_area').val(),
                    manual_solution_rate: $('#edit_hidden_manual_solution_rate').val(),
                    manual_dilution_ratio: $('#edit_hidden_manual_dilution_ratio').val()
                };

                $.ajax({
                    url: 'update_chemical.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            $('#editChemicalModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Failed to update chemical'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert('Error: Could not update chemical. Please try again.');
                    }
                });
            }
        });

        // Delete chemical
        $(document).on('click', '.delete-btn', function() {
            const chemicalId = $(this).data('id');
            if(confirm('WARNING: This will permanently delete the record!\n\nProceed?')) {
                $.ajax({
                    url: 'delete_chemical.php',
                    method: 'POST',
                    data: { id: chemicalId },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Failed to delete chemical'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert('Error: Could not delete chemical. Please try again.');
                    }
                });
            }
        });

        // View Chemical
        $(document).on('click', '.view-btn', function() {
            const chemicalId = $(this).data('id');

            $.ajax({
                url: 'get_chemical.php',
                method: 'GET',
                data: { id: chemicalId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Populate view modal
                        $('#viewChemicalName').text(response.data.chemical_name);
                        $('#viewType').text(response.data.type);
                        $('#viewTargetPest').text(response.data.target_pest || 'Not specified');
                        $('#viewQuantity').text(
                            `${response.data.quantity} ${response.data.unit}`
                        );
                        $('#viewUnit').text(response.data.unit);
                        $('#viewManufacturer').text(response.data.manufacturer || 'N/A');
                        $('#viewSupplier').text(response.data.supplier || 'N/A');
                        $('#viewExpirationDate').text(
                            new Date(response.data.expiration_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            })
                        );
                        $('#viewDescription').text(response.data.description || 'No description');
                        $('#viewSafetyInfo').text(response.data.safety_info || 'No safety information');

                        // Display dilution information
                        const dilutionRate = response.data.dilution_rate || 20;
                        const areaCoverage = response.data.area_coverage || 100;

                        // Calculate manual calculator inputs from stored values
                        let area, solutionRate, ratio;
                        if (response.data.manual_area) {
                            area = response.data.manual_area;
                            solutionRate = response.data.manual_solution_rate;
                            ratio = response.data.manual_dilution_ratio;
                        } else {
                            area = 100;
                            solutionRate = (1 / areaCoverage).toFixed(4);
                            ratio = dilutionRate + ':' + (1000 - dilutionRate);
                        }

                        $('#viewArea').text(area + ' sq. m');
                        $('#viewSolutionRate').text(parseFloat(solutionRate).toString() + ' liters per sq. m');
                        $('#viewDilutionRatio').text(ratio);

                        // Calculate example values
                        const targetArea = area; // Use the displayed area
                        const totalSolution = (targetArea / areaCoverage).toFixed(2);
                        const totalChemical = (dilutionRate * totalSolution).toFixed(2);
                        const waterNeeded = (totalSolution - totalChemical / 1000).toFixed(2);

                        const output = `
                            <div class="dilution-preview">
                                <p><strong>For a ${targetArea} sq. m area:</strong></p>
                                <ul>
                                    <li><strong>Total solution needed:</strong> ${totalSolution} liters</li>
                                    <li><strong>Chemical needed:</strong> ${totalChemical} ml</li>
                                    <li><strong>Water needed:</strong> ${waterNeeded} liters</li>
                                </ul>
                                <p><strong>Explanation:</strong></p>
                                <p>
                                    With a dilution rate of ${dilutionRate} ml per liter and area coverage of ${areaCoverage} m² per liter:
                                    <br><br>
                                    Calculation:
                                    <br>
                                    • Total solution = Area ÷ Area coverage = ${targetArea} ÷ ${areaCoverage} = ${totalSolution} liters
                                    <br>
                                    • Chemical = Dilution rate × Total solution = ${dilutionRate} × ${totalSolution} = ${totalChemical} ml
                                    <br>
                                    • Water = Total solution - Chemical = ${totalSolution} - ${totalChemical / 1000} = ${waterNeeded} liters
                                </p>
                            </div>
                        `;

                        $('#viewDilutionExample').html(output);

                        $('#viewChemicalModal').modal('show');
                    } else {
                        alert('Error: ' + (response.error || 'Failed to load chemical details'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('Error: Could not load chemical details. Please try again.');
                }
            });
        });
    });
    </script>


    <script>
        // Initialize mobile menu and notifications when the page loads
        $(document).ready(function() {
            // Mobile menu toggle
            $('#menuToggle').on('click', function() {
                $('.sidebar').toggleClass('active');
            });


        });
    </script>
</body>
</html>
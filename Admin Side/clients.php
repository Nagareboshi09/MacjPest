<?php
session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../db_connect.php';
require_once '../notification_functions.php';



// Fetch summary statistics
// Total clients
$totalClientsQuery = "SELECT COUNT(*) as total FROM clients";
$totalClientsResult = $conn->query($totalClientsQuery);
$totalClients = $totalClientsResult->fetch_assoc()['total'];

// Total contracts
$totalContractsQuery = "SELECT COUNT(*) as total FROM clients WHERE contract_start_date IS NOT NULL AND contract_end_date IS NOT NULL";
$totalContractsResult = $conn->query($totalContractsQuery);
$totalContracts = $totalContractsResult->fetch_assoc()['total'];

// Ending contracts (next 30 days)
$endingContractsQuery = "SELECT COUNT(*) as total FROM clients WHERE contract_end_date IS NOT NULL AND contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$endingContractsResult = $conn->query($endingContractsQuery);
$endingContracts = $endingContractsResult->fetch_assoc()['total'];

// Expired contracts
$expiredContractsQuery = "SELECT COUNT(*) as total FROM clients WHERE contract_end_date IS NOT NULL AND contract_end_date < CURDATE()";
$expiredContractsResult = $conn->query($expiredContractsQuery);
$expiredContracts = $expiredContractsResult->fetch_assoc()['total'];

// Fetch all clients with contract information
$sql = "SELECT
            c.client_id,
            c.first_name,
            c.last_name,
            c.email,
            c.contact_number,
            c.location_address,
            c.type_of_place,
            c.registered_at,
            c.contract_start_date,
            c.contract_end_date,
            CASE WHEN c.contract_start_date IS NOT NULL AND c.contract_end_date IS NOT NULL THEN 1 ELSE 0 END as has_contract
        FROM clients c
        ORDER BY c.registered_at DESC";
$clients = $conn->query($sql);

// Check for errors
if (!$clients) {
    die("Error fetching clients: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management | MacJ Pest Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/clients-page.css">
    <link rel="stylesheet" href="../css/notifications.css">
        <style>
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>MacJ Pest Control</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li><a href="chemical_inventory.php"><i class="fas fa-flask"></i> Chemical Inventory</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
                    <li><a href="technicians.php"><i class="fas fa-user-md"></i> Technicians</a></li>
                    <li  class="active"><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                    <li><a href="SignOut.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Mobile menu toggle -->
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Header -->
        <header class="header">
            <div class="header-title">
                <h1>Admin Dashboard</h1>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <?php
                    // Check if profile picture exists
                    $staff_id = $_SESSION['user_id'];
                    $profile_picture = '';

                    // Check if the office_staff table has profile_picture column
                    $result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'profile_picture'");
                    if ($result->num_rows > 0) {
                        $stmt = $conn->prepare("SELECT profile_picture FROM office_staff WHERE staff_id = ?");
                        $stmt->bind_param("i", $staff_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $profile_picture = $row['profile_picture'];
                        }
                    }

                    $profile_picture_url = !empty($profile_picture)
                        ? "../uploads/admin/" . $profile_picture
                        : "../assets/default-profile.jpg";
                    ?>
                    <img src="<?php echo $profile_picture_url; ?>" alt="Profile" class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                    <div>
                        <div class="user-name"><?= $_SESSION['username'] ?? 'Admin' ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="clients-content">
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $totalClients; ?></h3>
                            <p>Total Clients</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $totalContracts; ?></h3>
                            <p>Active Contracts</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $endingContracts; ?></h3>
                            <p>Ending Soon (30 days)</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $expiredContracts; ?></h3>
                            <p>Expired Contracts</p>
                        </div>
                    </div>
                </div>

                <div class="clients-header">
                    <h1><i class="fas fa-users"></i> Client Management</h1>
                    <div class="header-actions">
                        <button class="btn btn-primary add-client-btn" id="addClientBtn">
                            <i class="fas fa-plus"></i> Add Client
                        </button>
                        <div class="search-container">
                            <input type="text" id="clientSearch" placeholder="Search clients...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="table-responsive">
                    <table class="table table-hover clients-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Type of Place</th>
                                <th>Registered</th>
                                <th>Contract Status</th>
                                <th>Expiration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                              <?php if ($clients->num_rows > 0): ?>
                                  <?php while($client = $clients->fetch_assoc()): ?>
                                      <?php
                                      $statusClass = '';
                                      if ($client['contract_end_date']) {
                                          $endDate = strtotime($client['contract_end_date']);
                                          if ($endDate < time()) {
                                              $statusClass = 'expired';
                                          } elseif ($endDate <= strtotime('+30 days')) {
                                              $statusClass = 'expiring-soon';
                                          }
                                      }
                                      ?>
                                      <tr>
                                          <td><?= htmlspecialchars($client['client_id']) ?></td>
                                          <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></td>
                                          <td><?= htmlspecialchars($client['email']) ?></td>
                                          <td><?= htmlspecialchars($client['contact_number']) ?></td>
                                          <td><?= htmlspecialchars(preg_replace('/\[[-\d\.]+,[-\d\.]+\]$/', '', $client['location_address'] ?? 'Not set')) ?></td>
                                          <td><?= htmlspecialchars($client['type_of_place'] ?? 'Not set') ?></td>
                                          <td><?= date('M d, Y', strtotime($client['registered_at'])) ?></td>
                                          <td>
                                              <span class="badge <?= $client['has_contract'] ? 'badge-success' : 'badge-secondary' ?>">
                                                  <?= $client['has_contract'] ? 'Yes' : 'No' ?>
                                              </span>
                                          </td>
                                          <td class="<?= $statusClass ?>">
                                              <?= $client['has_contract'] ? date('M d, Y', strtotime($client['contract_end_date'])) : 'N/A' ?>
                                          </td>
                                         <td>
                                             <div class="action-buttons">
                                                 <button class="btn btn-sm btn-warning edit-client-btn" data-id="<?= $client['client_id'] ?>" title="Edit Client">
                                                     <i class="fas fa-edit"></i>
                                                 </button>
                                                 <button class="btn btn-sm btn-primary contract-btn"
                                                         data-id="<?= $client['client_id'] ?>"
                                                         data-has-contract="<?= $client['has_contract'] ?>"
                                                         data-start-date="<?= $client['contract_start_date'] ? date('Y-m-d', strtotime($client['contract_start_date'])) : '' ?>"
                                                         data-end-date="<?= $client['contract_end_date'] ? date('Y-m-d', strtotime($client['contract_end_date'])) : '' ?>"
                                                         title="Manage Contract">
                                                     <i class="fas fa-file-contract"></i>
                                                 </button>
                                                 <button class="btn btn-sm btn-danger delete-client-btn"
                                                         data-id="<?= $client['client_id'] ?>"
                                                         data-name="<?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>"
                                                         title="Delete Client">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </div>
                                         </td>
                                     </tr>
                                 <?php endwhile; ?>
                             <?php else: ?>
                                 <tr>
                                     <td colspan="10" class="text-center">No clients found</td>
                                 </tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>



    <!-- Add Client Modal -->
    <div class="modal" id="addClientModal">
        <div class="modal-content add-client-modal">
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3>Add New Client</h3>
                        <p>Enter client information and service agreement details</p>
                    </div>
                </div>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addClientForm">
                    <div class="form-section">
                        <h4><i class="fas fa-id-card"></i> Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="firstName">
                                    <i class="fas fa-user"></i> First Name
                                </label>
                                <input type="text" id="firstName" name="first_name" required placeholder="Enter first name">
                            </div>
                            <div class="form-group half-width">
                                <label for="lastName">
                                    <i class="fas fa-user"></i> Last Name
                                </label>
                                <input type="text" id="lastName" name="last_name" required placeholder="Enter last name">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-address-book"></i> Contact Information</h4>
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" id="email" name="email" required placeholder="client@example.com">
                            </div>
                            <div class="form-group half-width">
                                <label for="contact">
                                    <i class="fas fa-phone"></i> Contact Number
                                </label>
                                <input type="text" id="contact" name="contact_number" required placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Location Details</h4>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="location">
                                    <i class="fas fa-home"></i> Location Address
                                </label>
                                <input type="text" id="location" name="location_address" required placeholder="Enter complete address">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="typeOfPlace">
                                    <i class="fas fa-building"></i> Type of Place
                                </label>
                                <select id="typeOfPlace" name="type_of_place" required>
                                    <option value="">Select type of place</option>
                                    <option value="Residential">Residential</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Industrial">Industrial</option>
                                    <option value="Office">Office</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-file-contract"></i> Service Agreement</h4>
                        <div class="contract-toggle-section">
                            <div class="toggle-container">
                                <label class="toggle-label">
                                    <input type="checkbox" id="hasContract" name="has_contract">
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-text">Client accepts service contract</span>
                                </label>
                            </div>
                            <p class="contract-description">
                                <i class="fas fa-info-circle"></i>
                                Check this if the client agrees to a service contract. You'll be able to set contract dates below.
                            </p>
                        </div>

                        <div class="contract-fields" id="contractFields" style="display: none;">
                            <div class="contract-notice">
                                <div class="notice-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <div class="notice-content">
                                    <strong>Service Contract Agreement</strong>
                                    <p>Define the contract period for ongoing pest control services</p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="contractStartDate">
                                        <i class="fas fa-play-circle"></i> Contract Start Date
                                    </label>
                                    <input type="date" id="contractStartDate" name="contract_start_date">
                                    <small class="form-hint">When services begin</small>
                                </div>
                                <div class="form-group half-width">
                                    <label for="contractEndDate">
                                        <i class="fas fa-stop-circle"></i> Contract End Date
                                    </label>
                                    <input type="date" id="contractEndDate" name="contract_end_date">
                                    <small class="form-hint">When services conclude</small>
                                </div>
                            </div>

                            <div class="contract-duration" id="addContractDuration">
                                <span class="duration-label">Contract Duration:</span>
                                <span class="duration-value" id="addDurationValue">--</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" id="saveClientBtn">
                    <i class="fas fa-save"></i> Save Client
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal" id="editClientModal">
        <div class="modal-content edit-client-modal">
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h3>Edit Client Information</h3>
                        <p>Update client details and contact information</p>
                    </div>
                </div>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editClientForm">
                    <input type="hidden" id="editClientId" name="client_id">

                    <div class="form-section">
                        <h4><i class="fas fa-id-card"></i> Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="editFirstName">
                                    <i class="fas fa-user"></i> First Name
                                </label>
                                <input type="text" id="editFirstName" name="first_name" required placeholder="Enter first name">
                            </div>
                            <div class="form-group half-width">
                                <label for="editLastName">
                                    <i class="fas fa-user"></i> Last Name
                                </label>
                                <input type="text" id="editLastName" name="last_name" required placeholder="Enter last name">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-address-book"></i> Contact Information</h4>
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="editEmail">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" id="editEmail" name="email" required placeholder="client@example.com">
                            </div>
                            <div class="form-group half-width">
                                <label for="editContact">
                                    <i class="fas fa-phone"></i> Contact Number
                                </label>
                                <input type="text" id="editContact" name="contact_number" required placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Location Details</h4>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="editLocation">
                                    <i class="fas fa-home"></i> Location Address
                                </label>
                                <input type="text" id="editLocation" name="location_address" required placeholder="Enter complete address">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="editTypeOfPlace">
                                    <i class="fas fa-building"></i> Type of Place
                                </label>
                                <select id="editTypeOfPlace" name="type_of_place" required>
                                    <option value="">Select type of place</option>
                                    <option value="Residential">Residential</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Industrial">Industrial</option>
                                    <option value="Office">Office</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" id="updateClientBtn">
                    <i class="fas fa-save"></i> Update Client
                </button>
            </div>
        </div>
    </div>

    <!-- Contract Modal -->
    <div class="modal" id="contractModal">
        <div class="modal-content contract-modal">
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <h3 id="contractModalTitle">Manage Contract</h3>
                        <p>Set or update contract dates for this client</p>
                    </div>
                </div>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="contractForm">
                    <input type="hidden" id="contractClientId" name="client_id">

                    <div class="form-section">
                        <h4><i class="fas fa-calendar-alt"></i> Contract Period</h4>
                        <div class="contract-info" id="contractInfo" style="display: none;">
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <span>Setting contract dates will activate ongoing service for this client</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="contractStartDate">
                                    <i class="fas fa-play-circle"></i> Start Date
                                </label>
                                <input type="date" id="contractStartDate" name="contract_start_date" required>
                                <small class="form-hint">When services begin</small>
                            </div>
                            <div class="form-group half-width">
                                <label for="contractEndDate">
                                    <i class="fas fa-stop-circle"></i> End Date
                                </label>
                                <input type="date" id="contractEndDate" name="contract_end_date" required>
                                <small class="form-hint">When services conclude</small>
                            </div>
                        </div>
                        <div class="contract-duration" id="contractDuration">
                            <span class="duration-label">Contract Duration:</span>
                            <span class="duration-value" id="durationValue">--</span>
                        </div>
                    </div>

                    <div class="contract-status" id="contractStatusSection" style="display: none;">
                        <div class="status-indicator">
                            <div class="status-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="status-text">
                                <strong>Contract Active</strong>
                                <p>Client has an active service agreement</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" id="saveContractBtn">
                    <i class="fas fa-save"></i> Save Contract
                </button>
                <button class="btn btn-danger" id="removeContractBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Remove Contract
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteConfirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the client <strong id="deleteClientName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary cancel-btn">Cancel</button>
                <button class="btn btn-danger confirm-delete-btn">Delete</button>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Mobile menu toggle
            $('#menuToggle').on('click', function() {
                $('.sidebar').toggleClass('active');
            });

            // Initialize client search functionality
            $('#clientSearch').on('keyup', function() {
                const searchValue = $(this).val().toLowerCase().trim();
                $('.clients-table tbody tr').each(function() {
                    const clientId = $(this).find('td:nth-child(1)').text().toLowerCase();
                    const clientName = $(this).find('td:nth-child(2)').text().toLowerCase();
                    const clientEmail = $(this).find('td:nth-child(3)').text().toLowerCase();
                    const clientContact = $(this).find('td:nth-child(4)').text().toLowerCase();
                    const clientLocation = $(this).find('td:nth-child(5)').text().toLowerCase();
                    const clientTypeOfPlace = $(this).find('td:nth-child(6)').text().toLowerCase();
                    const clientRegistered = $(this).find('td:nth-child(7)').text().toLowerCase();
                    const clientContractStatus = $(this).find('td:nth-child(8)').text().toLowerCase();

                    if (clientId.includes(searchValue) ||
                        clientName.includes(searchValue) ||
                        clientEmail.includes(searchValue) ||
                        clientContact.includes(searchValue) ||
                        clientLocation.includes(searchValue) ||
                        clientTypeOfPlace.includes(searchValue) ||
                        clientRegistered.includes(searchValue) ||
                        clientContractStatus.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Add Client Modal
            $('#addClientBtn').on('click', function() {
                // Reset form
                $('#addClientForm')[0].reset();
                $('#contractFields').hide();
                $('#addDurationValue').text('--');
                $('#hasContract').prop('checked', false);
                $('#addClientModal').show();
            });

            // Edit Client Modal
            $('.edit-client-btn').on('click', function() {
                const clientId = $(this).data('id');
                // Load client data via AJAX or populate from table row
                const row = $(this).closest('tr');
                $('#editClientId').val(clientId);
                $('#editFirstName').val(row.find('td:nth-child(2)').text().split(' ')[0]);
                $('#editLastName').val(row.find('td:nth-child(2)').text().split(' ')[1]);
                $('#editEmail').val(row.find('td:nth-child(3)').text());
                $('#editContact').val(row.find('td:nth-child(4)').text());
                $('#editLocation').val(row.find('td:nth-child(5)').text());
                $('#editTypeOfPlace').val(row.find('td:nth-child(6)').text());
                $('#editClientModal').show();
            });

            // Contract Modal
            $('.contract-btn').on('click', function() {
                const clientId = $(this).data('id');
                const hasContract = $(this).data('has-contract');
                const startDate = $(this).data('start-date');
                const endDate = $(this).data('end-date');

                $('#contractClientId').val(clientId);
                if (hasContract) {
                    $('#contractModalTitle').text('Edit Contract');
                    $('#contractStartDate').val(startDate);
                    $('#contractEndDate').val(endDate);
                    $('#removeContractBtn').show();
                    $('#contractInfo').hide();
                    $('#contractStatusSection').show();
                } else {
                    $('#contractModalTitle').text('Create Contract');
                    $('#contractStartDate').val('');
                    $('#contractEndDate').val('');
                    $('#removeContractBtn').hide();
                    $('#contractInfo').show();
                    $('#contractStatusSection').hide();
                }
                calculateContractDuration();
                $('#contractModal').show();
            });

            // Delete Client Modal
            $('.delete-client-btn').on('click', function() {
                const clientId = $(this).data('id');
                const clientName = $(this).data('name');
                $('#deleteClientName').text(clientName);
                $('#deleteConfirmModal').show();
            });

            // Close modals
            $('.close-modal, .close-btn, .cancel-btn').on('click', function() {
                $('.modal').hide();
            });

            // Close modals when clicking outside
            $(window).on('click', function(event) {
                if ($(event.target).hasClass('modal')) {
                    $('.modal').hide();
                }
            });

            // Contract toggle functionality for add client modal
            $('#hasContract').on('change', function() {
                const contractFields = $('#contractFields');
                const contractDates = $('#contractStartDate, #contractEndDate');

                if ($(this).is(':checked')) {
                    contractFields.slideDown(300);
                    contractDates.prop('required', true);
                } else {
                    contractFields.slideUp(300);
                    contractDates.prop('required', false).val('');
                    $('#addDurationValue').text('--');
                }
            });

            // Calculate contract duration for add client modal
            function calculateAddContractDuration() {
                const startDate = new Date($('#addClientModal #contractStartDate').val());
                const endDate = new Date($('#addClientModal #contractEndDate').val());

                if (startDate && endDate && endDate > startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    const months = Math.floor(diffDays / 30);
                    const days = diffDays % 30;

                    let durationText = '';
                    if (months > 0) {
                        durationText += months + ' month' + (months > 1 ? 's' : '');
                    }
                    if (days > 0) {
                        if (months > 0) durationText += ' ';
                        durationText += days + ' day' + (days > 1 ? 's' : '');
                    }

                    $('#addDurationValue').text(durationText);
                } else {
                    $('#addDurationValue').text('--');
                }
            }

            // Calculate contract duration for edit contract modal
            function calculateContractDuration() {
                const startDate = new Date($('#contractModal #contractStartDate').val());
                const endDate = new Date($('#contractModal #contractEndDate').val());

                if (startDate && endDate && endDate > startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    const months = Math.floor(diffDays / 30);
                    const days = diffDays % 30;

                    let durationText = '';
                    if (months > 0) {
                        durationText += months + ' month' + (months > 1 ? 's' : '');
                    }
                    if (days > 0) {
                        if (months > 0) durationText += ' ';
                        durationText += days + ' day' + (days > 1 ? 's' : '');
                    }

                    $('#durationValue').text(durationText);
                } else {
                    $('#durationValue').text('--');
                }
            }

            // Listen for date changes in both modals
            $('#contractModal #contractStartDate, #contractModal #contractEndDate').on('change', calculateContractDuration);
            $('#addClientModal #contractStartDate, #addClientModal #contractEndDate').on('change', calculateAddContractDuration);

            // Save Client
            $('#saveClientBtn').on('click', function() {
                const formData = new FormData(document.getElementById('addClientForm'));
                $.ajax({
                    url: 'ajax/add_client.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Client added successfully!');
                            location.reload();
                        } else {
                            if (result.errors) {
                                alert('Error adding client:\n' + result.errors.join('\n'));
                            } else {
                                alert('Error adding client: ' + (result.error || 'Unknown error'));
                            }
                        }
                    },
                    error: function() {
                        alert('Error adding client.');
                    }
                });
            });

            // Update Client
            $('#updateClientBtn').on('click', function() {
                const formData = new FormData(document.getElementById('editClientForm'));
                $.ajax({
                    url: 'ajax/update_client.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Client updated successfully!');
                            location.reload();
                        } else {
                            if (result.errors) {
                                alert('Error updating client:\n' + result.errors.join('\n'));
                            } else {
                                alert('Error updating client: ' + (result.error || 'Unknown error'));
                            }
                        }
                    },
                    error: function() {
                        alert('Error updating client.');
                    }
                });
            });

            // Save Contract
            $('#saveContractBtn').on('click', function() {
                const formData = new FormData(document.getElementById('contractForm'));
                $.ajax({
                    url: 'ajax/save_contract.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert('Contract saved successfully!');
                        location.reload();
                    },
                    error: function() {
                        alert('Error saving contract.');
                    }
                });
            });

            // Remove Contract
            $('#removeContractBtn').on('click', function() {
                const clientId = $('#contractClientId').val();
                $.ajax({
                    url: 'ajax/remove_contract.php',
                    type: 'POST',
                    data: { client_id: clientId },
                    success: function(response) {
                        alert('Contract removed successfully!');
                        location.reload();
                    },
                    error: function() {
                        alert('Error removing contract.');
                    }
                });
            });

            // Delete Client
            $('.confirm-delete-btn').on('click', function() {
                const clientId = $('.delete-client-btn').data('id');
                $.ajax({
                    url: 'ajax/delete_client.php',
                    type: 'POST',
                    data: { client_id: clientId },
                    success: function(response) {
                        alert('Client deleted successfully!');
                        location.reload();
                    },
                    error: function() {
                        alert('Error deleting client.');
                    }
                });
            });
        });
    </script>
</body>
</html>

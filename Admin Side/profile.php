<?php
session_start();
if ($_SESSION['role'] !== 'office_staff') {
    header("Location: ../SignIn.php");
    exit;
}
require_once '../db_connect.php';

// Get admin profile information
$staff_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if the office_staff table has the necessary columns
$result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'full_name'");
$hasFullName = $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'email'");
$hasEmail = $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'contact_number'");
$hasContactNumber = $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'profile_picture'");
$hasProfilePicture = $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM office_staff LIKE 'last_login'");
$hasLastLogin = $result->num_rows > 0;

// Add missing columns if needed
if (!$hasFullName) {
    $conn->query("ALTER TABLE office_staff ADD COLUMN full_name VARCHAR(100) DEFAULT NULL");
}

if (!$hasEmail) {
    $conn->query("ALTER TABLE office_staff ADD COLUMN email VARCHAR(100) DEFAULT NULL");
}

if (!$hasContactNumber) {
    $conn->query("ALTER TABLE office_staff ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL");
}

if (!$hasProfilePicture) {
    $conn->query("ALTER TABLE office_staff ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
}

if (!$hasLastLogin) {
    $conn->query("ALTER TABLE office_staff ADD COLUMN last_login TIMESTAMP DEFAULT NULL");
}

// Get admin profile data
$stmt = $conn->prepare("SELECT * FROM office_staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle profile update and password change
$success_message = '';
$error_message = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid request token.";
    } else {
        if (isset($_POST['update_profile'])) {
            $full_name = $conn->real_escape_string($_POST['full_name']);
            $email = $conn->real_escape_string($_POST['email']);
            $contact_number = $conn->real_escape_string($_POST['contact_number']);
            $new_username = $conn->real_escape_string($_POST['username']);

            // Check if username is being changed and if it's already taken
            if ($new_username !== $username) {
                $check_username = $conn->prepare("SELECT staff_id FROM office_staff WHERE username = ? AND staff_id != ?");
                $check_username->bind_param("si", $new_username, $staff_id);
                $check_username->execute();
                $result = $check_username->get_result();

                if ($result->num_rows > 0) {
                    $error_message = "Username already taken. Please choose a different one.";
                } else {
                    $username = $new_username;
                    $_SESSION['username'] = $new_username;
                }
            }

            // Handle profile picture upload
            $profile_picture_sql = "";
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = "../uploads/admin/";
                // Security note: Ensure the uploads directory has .htaccess with 'php_flag engine off' or '<Files "*.php"> Deny from all </Files>' to prevent PHP execution
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

                $original_name = $_FILES['profile_picture']['name'];
                $tmp_name = $_FILES['profile_picture']['tmp_name'];

                // Get file extension
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                // Validate extension
                if (!in_array($ext, $allowed_extensions)) {
                    $error_message = "Invalid file extension. Only JPG, JPEG, PNG, and GIF are allowed.";
                } else {
                    // Validate MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);

                    if (!in_array($mime_type, $allowed_mime_types)) {
                        $error_message = "Invalid file type. Only image files are allowed.";
                    } else {
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        // Generate safe filename with validated extension
                        $file_name = uniqid() . '.' . $ext;
                        $target_file = $upload_dir . $file_name;

                        // Additional image validation
                        $check = getimagesize($tmp_name);
                        if ($check !== false) {
                            // Upload file
                            if (move_uploaded_file($tmp_name, $target_file)) {
                                $profile_picture_sql = ", profile_picture = '$file_name'";
                            } else {
                                $error_message = "Failed to upload profile picture.";
                            }
                        } else {
                            $error_message = "Uploaded file is not a valid image.";
                        }
                    }
                }
            }

            // Update profile if no errors
            if (empty($error_message)) {
                // Collect fields to update
                $fields = [
                    'username'        => $new_username,
                    'full_name'       => $full_name,
                    'email'           => $email,
                    'contact_number'  => $contact_number,
                ];

                // If profile picture is being changed, we already computed $file_name above
                if (!empty($profile_picture_sql)) {
                    $fields['profile_picture'] = $file_name;
                }

                // Build SET clause and parameters
                $set_parts = [];
                $params = [];
                $types = '';

                foreach ($fields as $column => $value) {
                    $set_parts[] = "$column = ?";
                    $params[] = $value;
                    $types .= 's';
                }

                $sql = "UPDATE office_staff SET " . implode(', ', $set_parts) . " WHERE staff_id = ?";
                $types .= 'i';
                $params[] = $staff_id;

                $stmt_update = $conn->prepare($sql);
                if ($stmt_update === false) {
                    $error_message = "Failed to prepare profile update: " . $conn->error;
                } else {
                    $stmt_update->bind_param($types, ...$params);

                    if ($stmt_update->execute()) {
                        $success_message = "Profile updated successfully!";
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        // Refresh admin data
                        $stmt = $conn->prepare("SELECT * FROM office_staff WHERE staff_id = ?");
                        $stmt->bind_param("i", $staff_id);
                        $stmt->execute();
                        $admin = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error_message = "Failed to update profile: " . $stmt_update->error;
                    }

                    $stmt_update->close();
                }
            }
        } elseif (isset($_POST['change_password'])) {
            // Handle password change
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Check if current password is correct
            $check_password = $conn->prepare("SELECT password FROM office_staff WHERE staff_id = ?");
            $check_password->bind_param("i", $staff_id);
            $check_password->execute();
            $result = $check_password->get_result();
            $current_hash = $result->fetch_assoc()['password'];

            $password_valid = false;
            if (password_verify($current_password, $current_hash)) {
                $password_valid = true;
            } elseif (strlen($current_hash) == 32 && md5($current_password) === $current_hash) {
                $password_valid = true;
                // Rehash legacy MD5 to bcrypt
                $new_hash = password_hash($current_password, PASSWORD_DEFAULT);
                $rehash_stmt = $conn->prepare("UPDATE office_staff SET password = ? WHERE staff_id = ?");
                $rehash_stmt->bind_param("si", $new_hash, $staff_id);
                $rehash_stmt->execute();
            }

            if (!$password_valid) {
                $error_message = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New password and confirmation do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "New password must be at least 6 characters long.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = $conn->prepare("UPDATE office_staff SET password = ? WHERE staff_id = ?");
                $update_password->bind_param("si", $hashed_password, $staff_id);
                if ($update_password->execute()) {
                    $success_message = "Password changed successfully!";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error_message = "Failed to change password: " . $conn->error;
                }
            }
        }
    }
}

// Get profile picture URL
$profile_picture_url = !empty($admin['profile_picture'])
    ? "../uploads/admin/" . $admin['profile_picture']
    : "../assets/default-profile.jpg";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - MacJ Pest Control</title>
    <link rel="stylesheet" href="css/profile-page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/modern-modal.css">

</head>
<body>
   <!-- Header -->
   <header class="header">
        <div class="header-title">
            <h1>Admin Dashboard</h1>
        </div>
        <div class="user-menu">


            <div class="user-info">
                <img src="<?php echo htmlspecialchars($profile_picture_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="Profile" class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile menu toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>MacJ Pest Control</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li class="active"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li><a href="chemical_inventory.php"><i class="fas fa-flask"></i> Chemical Inventory</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
                    <li><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                    <li><a href="SignOut.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="profile-content">
                <div class="profile-header">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="profile-container">
                    <div class="profile-user-header">
                        <div class="profile-picture-container">
                            <img src="<?php echo htmlspecialchars($profile_picture_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="Profile Picture" class="profile-picture" id="profilePicturePreview">
                            <label for="profile_picture" class="profile-picture-edit" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <div class="profile-info">
                            <h1><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email'] ?? 'No email set', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($admin['contact_number'] ?? 'No contact number set', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                            <p><i class="fas fa-user-shield"></i> Administrator</p>
                            <span class="admin-badge"><i class="fas fa-crown me-1"></i> Admin Access</span>
                        </div>
                    </div>

                    <!-- Stats Section -->
                    <div class="stats-container">
                        <?php
                         // Get some basic stats for the admin
                         $total_technicians = 0;
                         $total_clients = 0;
                         $total_appointments = 0;
                         $total_job_orders = 0;

                         // Count technicians
                         try {
                             $result = $conn->query("SELECT COUNT(*) as count FROM technicians");
                             if ($result && $row = $result->fetch_assoc()) {
                                 $total_technicians = $row['count'];
                             }
                         } catch (Exception $e) {
                             $total_technicians = 0; // Table doesn't exist
                         }

                         // Count clients
                         try {
                             $result = $conn->query("SELECT COUNT(*) as count FROM clients");
                             if ($result && $row = $result->fetch_assoc()) {
                                 $total_clients = $row['count'];
                             }
                         } catch (Exception $e) {
                             $total_clients = 0;
                         }

                         // Count appointments
                         try {
                             $result = $conn->query("SELECT COUNT(*) as count FROM appointments");
                             if ($result && $row = $result->fetch_assoc()) {
                                 $total_appointments = $row['count'];
                             }
                         } catch (Exception $e) {
                             $total_appointments = 0;
                         }

                         // Count job orders
                         try {
                             $result = $conn->query("SELECT COUNT(*) as count FROM job_order");
                             if ($result && $row = $result->fetch_assoc()) {
                                 $total_job_orders = $row['count'];
                             }
                         } catch (Exception $e) {
                             $total_job_orders = 0;
                         }
                         ?>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_technicians; ?></div>
                            <div class="stat-label">Technicians</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #fee2e2; color: #ef4444;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_clients; ?></div>
                            <div class="stat-label">Clients</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #e0f2fe; color: #0ea5e9;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_appointments; ?></div>
                            <div class="stat-label">Appointments</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #dcfce7; color: #10b981;">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_job_orders; ?></div>
                            <div class="stat-label">Job Orders</div>
                        </div>
                    </div>

                    <div class="profile-tabs">
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="true">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">
                                    <i class="fas fa-history me-2"></i>Recent Activity
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="profile-content">
                        <div class="tab-content" id="profileTabsContent">
                            <div class="tab-pane fade show active" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username" class="form-label">Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="contact_number" class="form-label">Contact Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($admin['contact_number'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" pattern="09[0-9]{9}" title="Please enter a valid 11-digit Philippine mobile number starting with '09'">
                                                </div>
                                                <small class="form-text text-muted">Format: 09XXXXXXXXX (11 digits)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="profile_picture" class="form-label">Profile Picture</label>
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewImage(this)" style="display: none;">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-image"></i></span>
                                            <input type="text" class="form-control" id="file-name" readonly placeholder="No file selected">
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profile_picture').click()">
                                                <i class="fas fa-upload me-2"></i>Choose File
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Upload a new profile picture (optional). Recommended size: 200x200 pixels or larger.</small>
                                    </div>



                                    <div class="form-group text-end mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary btn-update">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                             </div>

                             <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                 <div class="security-section">
                                      <form method="POST" action="">
                                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                          <div class="security-card">
                                             <h4><i class="fas fa-lock"></i> Change Password</h4>
                                             <p class="text-muted">Update your password to keep your account secure</p>

                                             <div class="row">
                                                 <div class="col-md-4">
                                                     <div class="form-group">
                                                         <label for="current_password" class="form-label">Current Password</label>
                                                         <div class="input-group">
                                                             <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                             <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                             <span class="input-group-text password-toggle" data-target="current_password" style="cursor: pointer;">
                                                                 <i class="fas fa-eye"></i>
                                                             </span>
                                                         </div>
                                                     </div>
                                                 </div>
                                                 <div class="col-md-4">
                                                     <div class="form-group">
                                                         <label for="new_password" class="form-label">New Password</label>
                                                         <div class="input-group">
                                                             <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                             <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                             <span class="input-group-text password-toggle" data-target="new_password" style="cursor: pointer;">
                                                                 <i class="fas fa-eye"></i>
                                                             </span>
                                                         </div>
                                                     </div>
                                                 </div>
                                                 <div class="col-md-4">
                                                     <div class="form-group">
                                                         <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                         <div class="input-group">
                                                             <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                                             <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                             <span class="input-group-text password-toggle" data-target="confirm_password" style="cursor: pointer;">
                                                                 <i class="fas fa-eye"></i>
                                                             </span>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>

                                             <div class="form-group text-end">
                                                 <button type="submit" name="change_password" class="btn btn-primary">
                                                     <i class="fas fa-save"></i> Change Password
                                                 </button>
                                             </div>
                                         </div>
                                     </form>

                                     <div class="security-card">
                                         <h4><i class="fas fa-clock"></i> Login History</h4>
                                         <div class="last-login-info">
                                             <strong>Last Login:</strong>
                                             <?php
                                              $last_login = $admin['last_login'] ?? null;
                                              if ($last_login) {
                                                  echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($last_login)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                              } else {
                                                  echo htmlspecialchars('No login history available', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                              }
                                             ?>
                                         </div>
                                     </div>

                                     <div class="security-card">
                                         <h4><i class="fas fa-desktop"></i> Active Sessions</h4>
                                         <p class="text-muted">Manage your active login sessions</p>

                                         <div class="session-item">
                                             <div class="session-info">
                                                 <div class="session-device">Current Session</div>
                                                  <div class="session-time"><?php echo date('F j, Y \a\t g:i A'); ?> - <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                             </div>
                                             <button class="btn-revoke" disabled>Current</button>
                                         </div>

                                         <p class="text-muted mt-3">Note: Only one session is currently supported. Enhanced session management will be available in future updates.</p>
                                     </div>
                                 </div>
                             </div>

                             <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                                <div id="activity-loading" class="text-center p-4" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading recent activities...</p>
                                </div>
                                <div id="activity-content">
                                    <div class="text-center p-5">
                                        <i class="fas fa-history fa-3x mb-3"></i>
                                        <h4>Recent Activity</h4>
                                        <p class="text-muted">Your recent activities will appear here when you click the Recent Activity tab.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <!-- Profile specific scripts -->
    <script>
        $(document).ready(function() {
            // Preview profile picture before upload
            function previewImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        $('#profilePicturePreview').attr('src', e.target.result);
                        $('#file-name').val(input.files[0].name);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Assign the previewImage function to the global scope
            window.previewImage = previewImage;

            // Initialize tabs
            $('.nav-tabs .nav-link').on('click', function(e) {
                e.preventDefault();

                // Get the target tab content
                const target = $(this).attr('data-bs-target').replace('#', '');
                const isActivityTab = target === 'activity';

                // If this is the first time clicking the activity tab, show loading state
                if (isActivityTab && !activitiesLoaded) {
                    // Clear the placeholder message
                    $('#activity-content').empty();
                }

                // Remove active class from all tabs
                $('.nav-tabs .nav-link').removeClass('active').attr('aria-selected', 'false');

                // Add active class to clicked tab
                $(this).addClass('active').attr('aria-selected', 'true');

                // Hide all tab contents
                $('.tab-pane').removeClass('show active');

                // Show the target tab content
                $('#' + target).addClass('show active');

                // If the activity tab is clicked, load the activities
                if (isActivityTab) {
                    loadActivities();
                }
            });

            // Track if activities have been loaded
            let activitiesLoaded = false;

            // Function to load activities via AJAX
            function loadActivities() {
                // If activities have already been loaded, don't reload them
                if (activitiesLoaded) {
                    return;
                }

                // Show loading indicator and clear placeholder content
                $('#activity-loading').show();
                $('#activity-content').empty();

                // Fetch activities
                $.ajax({
                    url: 'get_activities.php',
                    method: 'GET',
                    success: function(response) {
                        // Hide loading indicator
                        $('#activity-loading').hide();

                        // Display activities
                        $('#activity-content').html(response);

                        // Initialize hover effects for newly loaded activity cards
                        initActivityCardHoverEffects();

                        // Mark activities as loaded
                        activitiesLoaded = true;
                    },
                    error: function(xhr, status, error) {
                        // Hide loading indicator
                        $('#activity-loading').hide();

                        // Display error message
                        $('#activity-content').html(
                            '<div class="alert alert-danger">' +
                            '<i class="fas fa-exclamation-circle me-2"></i>' +
                            'Error loading activities: ' + error +
                            '</div>'
                        );

                        // Allow retry on error
                        activitiesLoaded = false;
                    }
                });
            }

            // Function to initialize hover effects for activity cards
            function initActivityCardHoverEffects() {
                $('.activity-card').hover(
                    function() {
                        $(this).css({
                            'transform': 'translateY(-5px)',
                            'box-shadow': '0 10px 15px rgba(0, 0, 0, 0.1)'
                        });
                    },
                    function() {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                        });
                    }
                );
            }

            // Add animation effects for stat cards
            $('.stat-card').hover(
                function() {
                    $(this).css({
                        'transform': 'translateY(-10px)',
                        'box-shadow': '0 10px 20px rgba(0, 0, 0, 0.1)'
                    });
                },
                function() {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                    });
                }
            );

            // Mobile menu toggle
            $('#menuToggle').on('click', function() {
                $('.sidebar').toggleClass('active');
            });

            // Password visibility toggle
            $(document).on('click', '.password-toggle', function() {
                const targetId = $(this).data('target');
                const passwordInput = $('#' + targetId);
                const icon = $(this).find('i');

                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });


        });
    </script>


</body>
</html>

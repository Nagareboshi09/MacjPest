<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Connect to database
    try {
        $conn = new mysqli("localhost", "root", "", "macjpest");
    } catch (Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = '';

    // Check office staff
    $stmt = $conn->prepare("SELECT * FROM office_staff WHERE username = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();

    if ($staff && password_verify($password, $staff['password'])) {
        $_SESSION['user_id'] = $staff['staff_id'];
        $_SESSION['role'] = $staff['role'] ?? 'office_staff';
        $_SESSION['username'] = $staff['username']; // Added username

        // Update last login time
        $update_login = $conn->prepare("UPDATE office_staff SET last_login = NOW() WHERE staff_id = ?");
        $update_login->bind_param("i", $staff['staff_id']);
        $update_login->execute();

        header("Location: ../Admin Side/dashboard.php");
        exit;
    }


    // Check clients
    $stmt = $conn->prepare("SELECT * FROM clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $client = $stmt->get_result()->fetch_assoc();

    if ($client && password_verify($password, $client['password'])) {
        $_SESSION['client_id'] = $client['client_id'];
        $_SESSION['role'] = 'client';
        $_SESSION['email'] = $client['email'];
        $_SESSION['fullname'] = $client['first_name'] . ' ' . $client['last_name']; // Updated line
        header("Location: Client Side/schedule.php");
        exit;
    }
    $error = "Invalid credentials!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <title>Login - MacJ Pest Control</title>
</head>
<body>
  <a href="landing_updated.php" class="floating-home-btn">BACK AT LANDING PAGE</a>
<section class="bg-light py-3 py-md-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
        <div class="card border border-light-subtle rounded-3 shadow-sm">
          <div class="card-body p-3 p-md-4 p-xl-5">
            <?php if (isset($error)): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <div class="text-center mb-3">
              <a href="landing_updated.php">
                <img src="assets/img/MACJLOGO.png" alt="Logo" width="175" height="57">
              </a>
            </div>
            <h2 class="fs-6 fw-normal text-center text-secondary mb-4">Sign in to your account</h2>
            <form method="POST">
              <div class="row gy-2 overflow-hidden">
                <div class="col-12">
                  <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                    <label for="email">Email/Username</label>
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    <label for="password">Password</label>
                  </div>
                </div>
                <div class="col-12">
                  <div class="d-flex gap-2 justify-content-between">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="rememberMe" id="rememberMe">
                      <label class="form-check-label text-secondary" for="rememberMe">
                        Keep me logged in
                      </label>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="d-grid my-3">
                    <button class="btn btn-primary btn-lg" type="submit">Log in</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>
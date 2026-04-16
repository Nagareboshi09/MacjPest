<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Admin Dashboard'; ?> | MacJ Pest Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-base.css">
    <link rel="stylesheet" href="css/modern-modal.css">
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <h1>Admin Dashboard</h1>
        </div>
        <div class="user-menu">
            <!-- User Profile -->
            <div class="user-profile">
                <span class="user-name"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?></span>
                <a href="profile.php" class="profile-link">Profile</a>
                <a href="SignOut.php" class="logout-link">Sign Out</a>
            </div>
        </div>
    </header>

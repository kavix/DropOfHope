<?php
// Header include file
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>LifeLink - Blood Donation Network</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-heartbeat"></i>
                <span>LifeLink</span>
            </a>
            <button class="nav-toggle" onclick="toggleNav()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="search_donors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search_donors.php' ? 'active' : ''; ?>"><i class="fas fa-search"></i> Find Donors</a></li>
                <li><a href="view_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_requests.php' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Emergency Requests</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Admin</a></li>
                    <?php else: ?>
                        <li><a href="donor_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'donor_dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> My Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="nav-btn nav-btn-primary"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <?php $alert = getAlert(); if ($alert): ?>
    <div class="alert alert-<?php echo $alert['type']; ?>" id="alertBox">
        <?php echo $alert['message']; ?>
        <button onclick="document.getElementById('alertBox').style.display='none'">&times;</button>
    </div>
    <?php endif; ?>
    
    <main class="main-content">

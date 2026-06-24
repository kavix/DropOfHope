<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin_dashboard.php' : 'donor_dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and Confirm Password do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if user exists with this full name and email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE full_name = ? AND email = ?");
        $stmt->execute([$full_name, $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->execute([$password_hash, $user['id']]);
            
            $success = 'Your password has been successfully updated. You can now login with your new password.';
        } else {
            $error = 'No account found matching this Username and Email.';
        }
    }
}

$pageTitle = 'Forgot Password';
require_once 'includes/header.php';
?>

<div class="grid grid-2" style="max-width: 900px; margin: 0 auto;">
    <div class="card" style="display: flex; flex-direction: column; justify-content: center;">
        <div class="text-center">
            <i class="fas fa-key" style="font-size: 4rem; color: var(--primary); margin-bottom: 20px;"></i>
            <h2 style="color: var(--secondary); margin-bottom: 10px;">Reset Password</h2>
            <p style="color: var(--gray);">Enter your username and email to verify your identity and set a new password.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-lock"></i> New Password</h3>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
            <?php echo $success; ?>
        </div>
        <div class="text-center" style="margin-top: 20px;">
            <a href="login.php" class="btn btn-primary" style="background: var(--primary); color: white;"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
        </div>
        <?php else: ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username (Full Name)</label>
                <input type="text" name="full_name" class="form-control" required placeholder="Enter your registered full name">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="Enter your registered email">
            </div>
            
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter new password (min. 6 characters)">
            </div>
            
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--primary); color: white;">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        
        <div class="text-center mt-2" style="margin-top: 15px;">
            <p>Remembered your password? <a href="login.php" style="color: var(--primary); font-weight: 600;">Back to Login</a></p>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

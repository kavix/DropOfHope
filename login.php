<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin_dashboard.php' : 'donor_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['blood_type'] = $user['blood_type'];
            
            showAlert('Welcome back, ' . $user['full_name'] . '!', 'success');
            redirect($user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'donor_dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="grid grid-2" style="max-width: 900px; margin: 0 auto;">
    <div class="card" style="display: flex; flex-direction: column; justify-content: center;">
        <div class="text-center">
            <i class="fas fa-heartbeat" style="font-size: 4rem; color: var(--primary); margin-bottom: 20px;"></i>
            <h2 style="color: var(--secondary); margin-bottom: 10px;">Welcome Back</h2>
            <p style="color: var(--gray);">Sign in to access your LifeLink account and continue saving lives.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-sign-in-alt"></i> Login</h3>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" onsubmit="return validateForm('loginForm')" id="loginForm">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--primary); color: white;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div class="text-center mt-2">
            <p>Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register as a Donor</a></p>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: var(--light); border-radius: var(--radius); font-size: 0.9rem;">
            <p style="margin-bottom: 8px;"><strong>Demo Accounts:</strong></p>
            <p style="margin-bottom: 5px;">Admin: <code>admin@lifelink.lk</code> / <code>admin123</code></p>
            <p>Donor: <code>kasun@email.com</code> / <code>password</code></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

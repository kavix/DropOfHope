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
        
        <div class="text-center" style="margin-top: 15px;">
            <a href="forgot_password.php" style="color: var(--primary); font-weight: 600;">Forgot Password?</a>
        </div>
        
        <div class="text-center mt-2" style="margin-top: 10px;">
            <p>Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register as a Donor</a></p>
        </div>
    
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

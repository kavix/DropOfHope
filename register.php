<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin_dashboard.php' : 'donor_dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $bloodType = $_POST['blood_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $lastDonation = $_POST['last_donation_date'] ?? null;
    
    // Validation
    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (empty($bloodType)) $errors[] = 'Blood type is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered. Please login instead.';
        }
    }
    
    // Insert user
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $lastDonationValue = empty($lastDonation) ? null : $lastDonation;
        
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, blood_type, location, last_donation_date, user_type, is_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'donor', 0)
        ");
        
        try {
            $stmt->execute([$fullName, $email, $phone, $passwordHash, $bloodType, $location, $lastDonationValue]);
            showAlert('Registration successful! Please login to access your account. Your profile will be verified by an admin soon.', 'success');
            redirect('login.php');
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2><i class="fas fa-user-plus"></i> Become a Donor</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" onsubmit="return validateForm('registerForm')" id="registerForm">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control" required 
                       value="<?php echo $_POST['full_name'] ?? ''; ?>" placeholder="Your full name">
            </div>
            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required 
                       value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="your@email.com">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Phone Number <span class="required">*</span></label>
                <input type="tel" name="phone" class="form-control" required 
                       value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="077xxxxxxx">
            </div>
            <div class="form-group">
                <label>Blood Type <span class="required">*</span></label>
                <select name="blood_type" class="form-control" required>
                    <option value="">Select Blood Type</option>
                    <?php foreach ($BLOOD_TYPES as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($_POST['blood_type'] ?? '') == $type ? 'selected' : ''; ?>>
                        <?php echo $type; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Location <span class="required">*</span></label>
                <select name="location" class="form-control" required>
                    <option value="">Select Location</option>
                    <?php foreach ($LOCATIONS as $loc): ?>
                    <option value="<?php echo $loc; ?>" <?php echo ($_POST['location'] ?? '') == $loc ? 'selected' : ''; ?>>
                        <?php echo $loc; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Last Donation Date (if any)</label>
                <input type="date" name="last_donation_date" class="form-control" 
                       value="<?php echo $_POST['last_donation_date'] ?? ''; ?>">
                <small style="color: var(--gray);">Leave blank if never donated before</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Min 6 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required 
                       placeholder="Repeat password">
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="terms" required>
                <label for="terms">I confirm that I am eligible to donate blood and the information provided is accurate.</label>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--primary); color: white;">
            <i class="fas fa-user-plus"></i> Register as Donor
        </button>
    </form>
    
    <div class="text-center mt-2">
        <p>Already registered? <a href="login.php" style="color: var(--primary); font-weight: 600;">Login here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

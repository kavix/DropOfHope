<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isDonor()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get donor info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$donor = $stmt->fetch();

// Get donation history
$stmt = $pdo->prepare("SELECT * FROM donation_history WHERE donor_id = ? ORDER BY donation_date DESC");
$stmt->execute([$userId]);
$donations = $stmt->fetchAll();

// Get unread messages count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetchColumn();

// Check eligibility
$eligibility = checkEligibility($donor['last_donation_date']);
$daysUntilEligible = getDaysUntilEligible($donor['last_donation_date']);

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    $newStatus = $donor['availability_status'] === 'available' ? 'unavailable' : 'available';
    $stmt = $pdo->prepare("UPDATE users SET availability_status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    showAlert('Availability status updated!', 'success');
    redirect('donor_dashboard.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $location = $_POST['location'] ?? '';
    $lastDonation = $_POST['last_donation_date'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE users SET phone = ?, location = ?, last_donation_date = ? WHERE id = ?");
    $stmt->execute([$phone, $location, empty($lastDonation) ? null : $lastDonation, $userId]);
    showAlert('Profile updated successfully!', 'success');
    redirect('donor_dashboard.php');
}

$pageTitle = 'Donor Dashboard';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-user-circle"></i> Welcome, <?php echo sanitize($donor['full_name']); ?></h1>
    <p>Manage your donor profile, track donations, and respond to emergency requests.</p>
</div>

<div class="stats-row">
    <div class="stat-card">
        <i class="fas fa-tint"></i>
        <div class="stat-info">
            <h3><?php echo $donor['blood_type']; ?></h3>
            <p>Your Blood Type</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-heart"></i>
        <div class="stat-info">
            <h3><?php echo count($donations); ?></h3>
            <p>Total Donations</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-envelope"></i>
        <div class="stat-info">
            <h3><?php echo $unreadCount; ?></h3>
            <p>Unread Messages</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-<?php echo $eligibility ? 'check-circle' : 'clock'; ?>"></i>
        <div class="stat-info">
            <h3 style="font-size: 1.2rem;">
                <?php if ($eligibility): ?>
                <span class="eligible">Eligible</span>
                <?php else: ?>
                <span class="not-eligible"><?php echo $daysUntilEligible; ?> days</span>
                <?php endif; ?>
            </h3>
            <p>Donation Status</p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Profile Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-id-card"></i> My Profile</h3>
            <span class="badge <?php echo $donor['is_verified'] ? 'badge-success' : 'badge-warning'; ?>">
                <?php echo $donor['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
            </span>
        </div>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo sanitize($donor['full_name']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo sanitize($donor['email']); ?>" disabled>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo sanitize($donor['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <select name="location" class="form-control">
                        <?php foreach ($LOCATIONS as $loc): ?>
                        <option value="<?php echo $loc; ?>" <?php echo $donor['location'] == $loc ? 'selected' : ''; ?>>
                            <?php echo $loc; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Last Donation Date</label>
                    <input type="date" name="last_donation_date" class="form-control" 
                           value="<?php echo $donor['last_donation_date']; ?>">
                </div>
                <div class="form-group">
                    <label>Availability</label>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 10px 0;">
                        <span class="badge <?php echo $donor['availability_status'] == 'available' ? 'badge-success' : ($donor['availability_status'] == 'resting' ? 'badge-warning' : 'badge-danger'); ?>">
                            <?php echo ucfirst($donor['availability_status']); ?>
                        </span>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="toggle_availability" value="1">
                            <button type="submit" class="btn btn-sm <?php echo $donor['availability_status'] == 'available' ? 'btn-danger' : 'btn-success'; ?>">
                                <?php echo $donor['availability_status'] == 'available' ? 'Set Unavailable' : 'Set Available'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary" style="background: var(--primary); color: white;">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="search_donors.php" class="btn btn-primary" style="justify-content: center; background: var(--primary); color: white;">
                <i class="fas fa-search"></i> Find Other Donors
            </a>
            <a href="view_requests.php" class="btn btn-success" style="justify-content: center;">
                <i class="fas fa-bell"></i> View Emergency Requests
            </a>
            <a href="messages.php" class="btn btn-info" style="justify-content: center;">
                <i class="fas fa-envelope"></i> My Messages
                <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger" style="margin-left: 8px;"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="donation_history.php" class="btn btn-secondary" style="justify-content: center; border-color: var(--primary); color: var(--primary);">
                <i class="fas fa-history"></i> Full Donation History
            </a>
        </div>
    </div>
</div>

<!-- Donation History -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Donations</h3>
        <a href="donation_history.php" class="btn btn-sm btn-primary" style="background: var(--primary); color: white;">View All</a>
    </div>
    
    <?php if (count($donations) > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Blood Type</th>
                    <th>Units</th>
                    <th>Location</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($donations, 0, 5) as $donation): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                    <td><span class="blood-type"><?php echo $donation['blood_type']; ?></span></td>
                    <td><?php echo $donation['units']; ?></td>
                    <td><?php echo sanitize($donation['location']); ?></td>
                    <td><?php echo sanitize($donation['notes'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding: 30px;">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>No Donations Yet</h3>
        <p>Your donation history will appear here once you start donating through LifeLink.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

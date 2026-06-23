<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get stats
$stats = [
    'total_donors' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor'")->fetchColumn(),
    'verified_donors' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_verified = 1")->fetchColumn(),
    'pending_verification' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_verified = 0")->fetchColumn(),
    'active_requests' => $pdo->query("SELECT COUNT(*) FROM emergency_requests WHERE status = 'active'")->fetchColumn(),
    'total_donations' => $pdo->query("SELECT COUNT(*) FROM donation_history")->fetchColumn(),
    'available_donors' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND availability_status = 'available'")->fetchColumn()
];

// Get pending donors
$pendingDonors = $pdo->query("
    SELECT * FROM users 
    WHERE user_type = 'donor' AND is_verified = 0 
    ORDER BY created_at DESC
")->fetchAll();

// Get all donors
$allDonors = $pdo->query("
    SELECT u.*, COUNT(dh.id) as donation_count 
    FROM users u 
    LEFT JOIN donation_history dh ON u.id = dh.donor_id 
    WHERE u.user_type = 'donor'
    GROUP BY u.id 
    ORDER BY u.created_at DESC
")->fetchAll();

// Handle verification
if (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $donorId = $_GET['verify'];
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$donorId]);
    showAlert('Donor verified successfully!', 'success');
    redirect('admin_dashboard.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $donorId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'donor'");
    $stmt->execute([$donorId]);
    showAlert('Donor removed successfully!', 'success');
    redirect('admin_dashboard.php');
}

// Handle adding donation record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_donation'])) {
    $donorId = $_POST['donor_id'];
    $donationDate = $_POST['donation_date'];
    $bloodType = $_POST['blood_type'];
    $units = $_POST['units'];
    $location = $_POST['location'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("
        INSERT INTO donation_history (donor_id, donation_date, blood_type, units, location, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$donorId, $donationDate, $bloodType, $units, $location, $notes]);
    
    // Update last donation date
    $stmt = $pdo->prepare("UPDATE users SET last_donation_date = ? WHERE id = ?");
    $stmt->execute([$donationDate, $donorId]);
    
    showAlert('Donation record added!', 'success');
    redirect('admin_dashboard.php');
}

$pageTitle = 'Admin Dashboard';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
    <p>Manage donors, verify registrations, and monitor the LifeLink network.</p>
</div>

<div class="stats-row">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <div class="stat-info">
            <h3><?php echo $stats['total_donors']; ?></h3>
            <p>Total Donors</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle"></i>
        <div class="stat-info">
            <h3><?php echo $stats['verified_donors']; ?></h3>
            <p>Verified Donors</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <div class="stat-info">
            <h3><?php echo $stats['pending_verification']; ?></h3>
            <p>Pending Verification</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-bell"></i>
        <div class="stat-info">
            <h3><?php echo $stats['active_requests']; ?></h3>
            <p>Active Requests</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hand-holding-heart"></i>
        <div class="stat-info">
            <h3><?php echo $stats['total_donations']; ?></h3>
            <p>Total Donations</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-heart"></i>
        <div class="stat-info">
            <h3><?php echo $stats['available_donors']; ?></h3>
            <p>Available Now</p>
        </div>
    </div>
</div>

<!-- Pending Verifications -->
<?php if (count($pendingDonors) > 0): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-clock"></i> Pending Verifications (<?php echo count($pendingDonors); ?>)</h3>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Blood Type</th>
                    <th>Location</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingDonors as $donor): ?>
                <tr>
                    <td><?php echo sanitize($donor['full_name']); ?></td>
                    <td><?php echo sanitize($donor['email']); ?></td>
                    <td><?php echo sanitize($donor['phone']); ?></td>
                    <td><span class="blood-type"><?php echo $donor['blood_type']; ?></span></td>
                    <td><?php echo sanitize($donor['location']); ?></td>
                    <td><?php echo timeAgo($donor['created_at']); ?></td>
                    <td>
                        <a href="?verify=<?php echo $donor['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify this donor?')">
                            <i class="fas fa-check"></i> Verify
                        </a>
                        <a href="?delete=<?php echo $donor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('Remove this donor?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- All Donors -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> All Donors</h3>
        <button class="btn btn-sm btn-primary" onclick="openModal('addDonationModal')" style="background: var(--primary); color: white;">
            <i class="fas fa-plus"></i> Add Donation
        </button>
    </div>
    <div class="table-container">
        <table class="table" id="donorsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Blood Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Donations</th>
                    <th>Last Donation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allDonors as $donor): ?>
                <tr>
                    <td><?php echo sanitize($donor['full_name']); ?></td>
                    <td><span class="blood-type"><?php echo $donor['blood_type']; ?></span></td>
                    <td><?php echo sanitize($donor['location']); ?></td>
                    <td>
                        <span class="badge <?php echo $donor['availability_status'] == 'available' ? 'badge-success' : ($donor['availability_status'] == 'resting' ? 'badge-warning' : 'badge-danger'); ?>">
                            <?php echo ucfirst($donor['availability_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($donor['is_verified']): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>
                        <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $donor['donation_count']; ?></td>
                    <td><?php echo $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'Never'; ?></td>
                    <td>
                        <?php if (!$donor['is_verified']): ?>
                        <a href="?verify=<?php echo $donor['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify this donor?')">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $donor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('Remove this donor?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 15px;">
        <button class="btn btn-sm btn-info" onclick="exportTableToCSV('donorsTable', 'donors.csv')">
            <i class="fas fa-download"></i> Export to CSV
        </button>
    </div>
</div>

<!-- Add Donation Modal -->
<div class="modal-overlay" id="addDonationModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add Donation Record</h3>
            <button class="modal-close" onclick="closeModal('addDonationModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label>Donor</label>
                <select name="donor_id" class="form-control" required>
                    <option value="">Select Donor</option>
                    <?php foreach ($allDonors as $donor): ?>
                    <option value="<?php echo $donor['id']; ?>"><?php echo sanitize($donor['full_name'] . ' (' . $donor['blood_type'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Donation Date</label>
                    <input type="date" name="donation_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" class="form-control" required>
                        <?php foreach ($BLOOD_TYPES as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Units</label>
                    <input type="number" name="units" class="form-control" required value="1" min="1">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" required placeholder="Hospital/Blood Bank name">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
            </div>
            <button type="submit" name="add_donation" class="btn btn-primary" style="width: 100%; background: var(--primary); color: white;">
                <i class="fas fa-save"></i> Add Donation Record
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

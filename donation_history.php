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

// Get full donation history with joined request info
$stmt = $pdo->prepare("
    SELECT dh.*, er.requester_name, er.location as request_location
    FROM donation_history dh
    LEFT JOIN emergency_requests er ON dh.request_id = er.id
    WHERE dh.donor_id = ?
    ORDER BY dh.donation_date DESC
");
$stmt->execute([$userId]);
$donations = $stmt->fetchAll();

// Stats
$totalUnits = array_sum(array_column($donations, 'units'));
$totalDonations = count($donations);

$pageTitle = 'Donation History';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-history"></i> Donation History</h1>
    <p>Full record of your blood donations through LifeLink.</p>
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
            <h3><?php echo $totalDonations; ?></h3>
            <p>Total Donations</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-flask"></i>
        <div class="stat-info">
            <h3><?php echo $totalUnits; ?></h3>
            <p>Total Units Donated</p>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-check"></i>
        <div class="stat-info">
            <h3><?php echo $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'N/A'; ?></h3>
            <p>Last Donation</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Donations</h3>
        <a href="donor_dashboard.php" class="btn btn-sm btn-secondary" style="border-color: var(--primary); color: var(--primary);">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($totalDonations > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Blood Type</th>
                    <th>Units</th>
                    <th>Location</th>
                    <th>Linked Request</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $i => $donation): ?>
                <tr>
                    <td><?php echo $totalDonations - $i; ?></td>
                    <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                    <td><span class="blood-type"><?php echo $donation['blood_type']; ?></span></td>
                    <td><?php echo $donation['units']; ?></td>
                    <td><?php echo sanitize($donation['location']); ?></td>
                    <td>
                        <?php if ($donation['requester_name']): ?>
                            <span style="font-size:0.85rem; color: var(--primary);">
                                <i class="fas fa-user"></i> <?php echo sanitize($donation['requester_name']); ?>
                                <br><small><?php echo sanitize($donation['request_location']); ?></small>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--gray); font-size:0.85rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo sanitize($donation['notes'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding: 40px; text-align: center;">
        <i class="fas fa-hand-holding-heart" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
        <h3>No Donations Yet</h3>
        <p>Your donation history will appear here once you start donating through LifeLink.</p>
        <a href="view_requests.php" class="btn btn-primary mt-2" style="background: var(--primary); color: white;">
            <i class="fas fa-bell"></i> View Emergency Requests
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

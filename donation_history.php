<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get all donation history for this donor
$stmt = $pdo->prepare("
    SELECT dh.*, er.requester_name, er.location as request_location
    FROM donation_history dh
    LEFT JOIN emergency_requests er ON dh.request_id = er.id
    WHERE dh.donor_id = ?
    ORDER BY dh.donation_date DESC
");
$stmt->execute([$userId]);
$donations = $stmt->fetchAll();

// Get donor info for eligibility check
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$donor = $stmt->fetch();

$eligibility = checkEligibility($donor['last_donation_date']);
$daysUntilEligible = getDaysUntilEligible($donor['last_donation_date']);

$pageTitle = 'Donation History';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-history"></i> Donation History</h1>
    <p>Track your blood donations and check your eligibility status.</p>
</div>

<!-- Eligibility Card -->
<div class="card" style="border-left: 5px solid <?php echo $eligibility ? 'var(--accent)' : 'var(--warning)'; ?>;">
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <i class="fas fa-<?php echo $eligibility ? 'check-circle' : 'clock'; ?>" style="font-size: 3rem; color: <?php echo $eligibility ? 'var(--accent)' : 'var(--warning)'; ?>;"></i>
        <div>
            <h3 style="margin-bottom: 5px;">
                <?php if ($eligibility): ?>
                <span style="color: var(--accent);">You are eligible to donate!</span>
                <?php else: ?>
                <span style="color: var(--warning);">Not yet eligible to donate</span>
                <?php endif; ?>
            </h3>
            <p style="color: var(--gray); margin: 0;">
                <?php if ($eligibility): ?>
                Your last donation was on <?php echo date('M d, Y', strtotime($donor['last_donation_date'] ?? 'never')); ?>. You can donate again now.
                <?php else: ?>
                You need to wait <?php echo $daysUntilEligible; ?> more day<?php echo $daysUntilEligible > 1 ? 's' : ''; ?> before you can donate again. 
                Last donation: <?php echo date('M d, Y', strtotime($donor['last_donation_date'])); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Donation Records -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Your Donation Records</h3>
        <span class="badge badge-primary"><?php echo count($donations); ?> Total Donations</span>
    </div>
    
    <?php if (count($donations) > 0): ?>
    <div class="table-container">
        <table class="table" id="historyTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Blood Type</th>
                    <th>Units</th>
                    <th>Location</th>
                    <th>Requester</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $index => $donation): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                    <td><span class="blood-type"><?php echo $donation['blood_type']; ?></span></td>
                    <td><?php echo $donation['units']; ?></td>
                    <td><?php echo sanitize($donation['location']); ?></td>
                    <td><?php echo $donation['requester_name'] ? sanitize($donation['requester_name']) : 'Direct Donation'; ?></td>
                    <td><?php echo sanitize($donation['notes'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 15px;">
        <button class="btn btn-sm btn-info" onclick="exportTableToCSV('historyTable', 'my_donation_history.csv')">
            <i class="fas fa-download"></i> Export to CSV
        </button>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding: 40px;">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>No Donations Recorded Yet</h3>
        <p>Your donation history will appear here once you start donating through LifeLink.</p>
        <a href="view_requests.php" class="btn btn-primary mt-2" style="background: var(--primary); color: white;">
            <i class="fas fa-bell"></i> View Emergency Requests
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Donation Facts -->
<div class="grid grid-3">
    <div class="card feature-card">
        <i class="fas fa-heart" style="color: var(--danger);"></i>
        <h3>1 Donation</h3>
        <p>Can save up to 3 lives by providing blood components to different patients.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-clock" style="color: var(--info);"></i>
        <h3>Every 3 Months</h3>
        <p>Is the recommended gap between whole blood donations for your health and safety.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-tint" style="color: var(--primary);"></i>
        <h3>450ml</h3>
        <p>Is the standard amount of blood collected in one donation session.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

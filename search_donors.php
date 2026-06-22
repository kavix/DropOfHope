<?php
require_once 'includes/config.php';

$bloodType = $_GET['blood_type'] ?? '';
$location = $_GET['location'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE user_type = 'donor' AND is_verified = 1";
$params = [];

if (!empty($bloodType)) {
    $sql .= " AND blood_type = ?";
    $params[] = $bloodType;
}

if (!empty($location)) {
    $sql .= " AND location = ?";
    $params[] = $location;
}

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY availability_status = 'available' DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

$pageTitle = 'Find Donors';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-search"></i> Find Blood Donors</h1>
    <p>Search for verified donors by blood type, location, or name.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Search Filters</h3>
    </div>
    <form method="GET" action="">
        <div class="search-bar">
            <div class="form-group">
                <label>Blood Type</label>
                <select name="blood_type" class="form-control">
                    <option value="">All Blood Types</option>
                    <?php foreach ($BLOOD_TYPES as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo $bloodType == $type ? 'selected' : ''; ?>>
                        <?php echo $type; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <select name="location" class="form-control">
                    <option value="">All Locations</option>
                    <?php foreach ($LOCATIONS as $loc): ?>
                    <option value="<?php echo $loc; ?>" <?php echo $location == $loc ? 'selected' : ''; ?>>
                        <?php echo $loc; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Search by Name/Contact</label>
                <input type="text" name="search" class="form-control" value="<?php echo sanitize($search); ?>" placeholder="Enter name, email, or phone...">
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="background: var(--primary); color: white;">
                <i class="fas fa-search"></i> Search Donors
            </button>
            <a href="search_donors.php" class="btn btn-secondary" style="border-color: var(--gray); color: var(--dark);">
                <i class="fas fa-undo"></i> Clear Filters
            </a>
        </div>
    </form>
</div>

<?php if (count($donors) > 0): ?>
<div class="grid">
    <?php foreach ($donors as $donor): 
        $eligibility = checkEligibility($donor['last_donation_date']);
        $isAvailable = $donor['availability_status'] === 'available' && $eligibility;
    ?>
    <div class="card donor-card">
        <div class="blood-type"><?php echo $donor['blood_type']; ?></div>
        <div class="donor-info">
            <h4><?php echo sanitize($donor['full_name']); ?></h4>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($donor['location']); ?></p>
            <p>
                <span class="badge <?php echo $isAvailable ? 'badge-success' : 'badge-warning'; ?>">
                    <?php echo $isAvailable ? 'Available Now' : ucfirst($donor['availability_status']); ?>
                </span>
                <?php if (!$eligibility): ?>
                <span class="badge badge-danger">Not Eligible (<?php echo getDaysUntilEligible($donor['last_donation_date']); ?> days)</span>
                <?php endif; ?>
            </p>
            <?php if ($donor['last_donation_date']): ?>
            <p style="font-size: 0.85rem; color: var(--gray);">
                <i class="fas fa-calendar-alt"></i> Last donation: <?php echo date('M d, Y', strtotime($donor['last_donation_date'])); ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="donor-actions">
            <?php if (isLoggedIn() && $isAvailable): ?>
            <a href="contact_donor.php?donor_id=<?php echo $donor['id']; ?>" class="btn btn-sm btn-primary" style="background: var(--primary); color: white;">
                <i class="fas fa-envelope"></i> Contact
            </a>
            <?php elseif (!isLoggedIn()): ?>
            <a href="login.php" class="btn btn-sm btn-secondary" style="border-color: var(--primary); color: var(--primary);">
                <i class="fas fa-sign-in-alt"></i> Login to Contact
            </a>
            <?php else: ?>
            <span class="btn btn-sm btn-secondary" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-clock"></i> Unavailable
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty-state">
    <i class="fas fa-search"></i>
    <h3>No Donors Found</h3>
    <p>No verified donors match your search criteria. Try adjusting your filters or check back later.</p>
    <a href="register.php" class="btn btn-primary mt-2" style="background: var(--primary); color: white;">
        <i class="fas fa-user-plus"></i> Become a Donor
    </a>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

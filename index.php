<?php
require_once 'includes/config.php';

// Get stats for hero section
$stats = [
    'donors' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_verified = 1")->fetchColumn(),
    'requests' => $pdo->query("SELECT COUNT(*) FROM emergency_requests WHERE status = 'active'")->fetchColumn(),
    'donations' => $pdo->query("SELECT COUNT(*) FROM donation_history")->fetchColumn()
];

$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<section class="hero">
    <h1><i class="fas fa-heartbeat"></i> LifeLink</h1>
    <p>Connecting blood donors with patients in need. Join our community and help save lives within the University of Kelaniya and nearby areas.</p>
    <div class="hero-buttons">
        <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Become a Donor</a>
        <a href="search_donors.php" class="btn btn-secondary btn-lg"><i class="fas fa-search"></i> Find a Donor</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <div class="number"><?php echo $stats['donors']; ?></div>
            <div class="label">Verified Donors</div>
        </div>
        <div class="hero-stat">
            <div class="number"><?php echo $stats['requests']; ?></div>
            <div class="label">Active Requests</div>
        </div>
        <div class="hero-stat">
            <div class="number"><?php echo $stats['donations']; ?></div>
            <div class="label">Lives Saved</div>
        </div>
    </div>
</section>

<section class="section-title">
    <h2>How It Works</h2>
    <p>LifeLink makes blood donation simple, fast, and secure for everyone in our community.</p>
</section>

<div class="grid grid-3">
    <div class="card feature-card">
        <i class="fas fa-user-plus"></i>
        <h3>Register as a Donor</h3>
        <p>Sign up with your blood type, location, and availability. Get verified by our admin team and become part of the life-saving network.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-search"></i>
        <h3>Find Matching Donors</h3>
        <p>Search for donors by blood type and location. Our database helps you find the nearest available donor during emergencies.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>Save a Life</h3>
        <p>Connect securely through our platform. Donate blood and track your donation history. Every drop counts!</p>
    </div>
</div>

<section class="section-title mt-3">
    <h2>Why LifeLink?</h2>
    <p>We address the critical challenges of blood donation in our community.</p>
</section>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Time is Critical</h3>
        </div>
        <p>During medical emergencies, finding the right blood type quickly can mean the difference between life and death. LifeLink provides instant access to a verified database of willing donors, saving precious hours when every minute counts.</p>
    </div>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
        </div>
        <p>Your contact information is protected. Initial communication happens through our secure platform. Phone numbers are only shared after mutual consent, ensuring your privacy is always respected.</p>
    </div>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-check-circle"></i> Verified Donors</h3>
        </div>
        <p>All donors go through an admin verification process. We check eligibility based on last donation dates and ensure accurate blood type information, giving requesters confidence in every match.</p>
    </div>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt"></i> Local Community</h3>
        </div>
        <p>Focused on the University of Kelaniya and surrounding areas. Find donors nearby, reducing travel time and making emergency responses faster and more efficient.</p>
    </div>
</div>

<section class="section-title mt-3">
    <h2>Recent Emergency Requests</h2>
    <p>Help fulfill these urgent blood requests from our community.</p>
</section>

<?php
$recentRequests = $pdo->query("
    SELECT * FROM emergency_requests 
    WHERE status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 3
")->fetchAll();

if (count($recentRequests) > 0):
?>
<div class="grid">
    <?php foreach ($recentRequests as $request): 
        $urgencyClass = $request['urgency_level'];
    ?>
    <div class="card request-card <?php echo $urgencyClass; ?>">
        <div class="request-header">
            <div>
                <span class="badge badge-<?php echo $request['urgency_level'] == 'critical' ? 'danger' : ($request['urgency_level'] == 'urgent' ? 'warning' : 'info'); ?>">
                    <?php echo ucfirst($request['urgency_level']); ?>
                </span>
                <span class="blood-type"><?php echo $request['blood_type']; ?></span>
            </div>
            <small><?php echo timeAgo($request['created_at']); ?></small>
        </div>
        <h4><?php echo sanitize($request['requester_name']); ?></h4>
        <div class="request-details">
            <p><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($request['location']); ?></p>
            <p><i class="fas fa-phone"></i> <?php echo sanitize($request['requester_phone']); ?></p>
        </div>
        <p class="request-message"><?php echo sanitize(substr($request['message'], 0, 150)) . (strlen($request['message']) > 150 ? '...' : ''); ?></p>
        <a href="view_requests.php" class="btn btn-primary btn-sm">View Details</a>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty-state">
    <i class="fas fa-check-circle"></i>
    <h3>No Active Emergency Requests</h3>
    <p>Great news! All current emergency requests have been fulfilled. You can still register as a donor to help future requests.</p>
</div>
<?php endif; ?>

<div class="card mt-3" style="text-align: center; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
    <h2 style="color: white; margin-bottom: 15px;"><i class="fas fa-heart"></i> Ready to Make a Difference?</h2>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 25px;">Join hundreds of donors in the University of Kelaniya community. Your donation can save up to three lives.</p>
    <a href="register.php" class="btn btn-primary btn-lg" style="background: white; color: var(--primary);"><i class="fas fa-user-plus"></i> Register as a Donor</a>
</div>

<?php require_once 'includes/footer.php'; ?>

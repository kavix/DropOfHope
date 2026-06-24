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
    <p>Connecting life-saving blood donors with patients in need within the University of Kelaniya and surrounding areas.</p>
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
    <p>Get connected and start saving lives in three simple steps.</p>
</section>

<div class="grid grid-3">
    <div class="card feature-card">
        <i class="fas fa-user-plus"></i>
        <h3>1. Register</h3>
        <p>Create a profile with your blood type, location, and contact options.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-search"></i>
        <h3>2. Match</h3>
        <p>Search the verified donor database or view active emergency requests.</p>
    </div>
    <div class="card feature-card">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>3. Save Lives</h3>
        <p>Coordinate securely, make your donation, and record your history.</p>
    </div>
</div>

<section class="section-title mt-3">
    <h2>Why LifeLink?</h2>
    <p>Designed for efficiency, privacy, and speed in urgent times.</p>
</section>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="card" style="text-align: center; padding: 20px;">
        <i class="fas fa-clock" style="font-size: 2rem; color: var(--primary); margin-bottom: 15px;"></i>
        <h4 style="margin-bottom: 8px;">Time Critical</h4>
        <p style="font-size: 0.9rem; color: var(--gray);">Instant matching saves precious hours during medical emergencies.</p>
    </div>
    <div class="card" style="text-align: center; padding: 20px;">
        <i class="fas fa-shield-alt" style="font-size: 2rem; color: var(--primary); margin-bottom: 15px;"></i>
        <h4 style="margin-bottom: 8px;">Secure Privacy</h4>
        <p style="font-size: 0.9rem; color: var(--gray);">Initial communications are safe. Contact info is shared only with consent.</p>
    </div>
    <div class="card" style="text-align: center; padding: 20px;">
        <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--primary); margin-bottom: 15px;"></i>
        <h4 style="margin-bottom: 8px;">Verified Donors</h4>
        <p style="font-size: 0.9rem; color: var(--gray);">Admin-verified donor details guarantee accurate blood type information.</p>
    </div>
    <div class="card" style="text-align: center; padding: 20px;">
        <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: var(--primary); margin-bottom: 15px;"></i>
        <h4 style="margin-bottom: 8px;">Local Focus</h4>
        <p style="font-size: 0.9rem; color: var(--gray);">Centered on University of Kelaniya and surrounding neighborhoods.</p>
    </div>
</div>

<section class="section-title mt-3">
    <h2>Recent Emergency Requests</h2>
    <p>Urgent blood requirements needing immediate support.</p>
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
<div class="grid grid-3">
    <?php foreach ($recentRequests as $request): 
        $urgencyClass = $request['urgency_level'];
        $badgeClass = $request['urgency_level'] == 'critical' ? 'danger' : ($request['urgency_level'] == 'urgent' ? 'warning' : 'info');
    ?>
    <div class="card request-card <?php echo $urgencyClass; ?>" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; margin-bottom: 0;">
        <div>
            <div class="request-header" style="align-items: center; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="blood-type" style="width: 38px; height: 38px; font-size: 0.85rem; flex-shrink: 0;"><?php echo $request['blood_type']; ?></span>
                    <div>
                        <h4 style="margin: 0; font-size: 1rem;"><?php echo sanitize($request['requester_name']); ?></h4>
                        <span class="badge badge-<?php echo $badgeClass; ?>" style="font-size: 0.7rem; padding: 3px 8px; margin-top: 4px;">
                            <?php echo ucfirst($request['urgency_level']); ?>
                        </span>
                    </div>
                </div>
                <small style="color: var(--gray); font-size: 0.75rem;"><?php echo timeAgo($request['created_at']); ?></small>
            </div>
            <div class="request-details" style="grid-template-columns: 1fr; gap: 5px; margin-bottom: 12px; border-top: 1px solid var(--light); padding-top: 10px;">
                <p style="margin: 0; font-size: 0.85rem;"><i class="fas fa-map-marker-alt" style="font-size: 0.85rem; width: 15px;"></i> <?php echo sanitize($request['location']); ?></p>
                <p style="margin: 0; font-size: 0.85rem;"><i class="fas fa-phone" style="font-size: 0.85rem; width: 15px;"></i> <?php echo sanitize($request['requester_phone']); ?></p>
            </div>
            <p class="request-message" style="font-size: 0.85rem; padding: 10px; margin-bottom: 15px; border-radius: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; height: 60px;">
                <?php echo sanitize($request['message']); ?>
            </p>
        </div>
        <a href="view_requests.php" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center; background: var(--primary); color: white; border: none; padding: 10px 0;"><i class="fas fa-eye"></i> View Details</a>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty-state">
    <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 15px;"></i>
    <h3>No Active Emergency Requests</h3>
    <p>All current emergency requests have been successfully fulfilled. Register to be ready for the next call.</p>
</div>
<?php endif; ?>

<div class="card mt-3" style="text-align: center; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 40px 20px;">
    <h2 style="color: white; margin-bottom: 10px;"><i class="fas fa-heart"></i> Ready to Make a Difference?</h2>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 20px; font-size: 0.95rem; max-width: 500px; margin-left: auto; margin-right: auto;">Join our life-saving community at University of Kelaniya. Your simple contribution can give someone another tomorrow.</p>
    <a href="register.php" class="btn btn-primary btn-lg" style="background: white; color: var(--primary);"><i class="fas fa-user-plus"></i> Register as a Donor</a>
</div>

<?php require_once 'includes/footer.php'; ?>

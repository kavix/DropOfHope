<?php
require_once 'includes/config.php';

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_request'])) {
    $name = trim($_POST['requester_name'] ?? '');
    $phone = trim($_POST['requester_phone'] ?? '');
    $email = trim($_POST['requester_email'] ?? '');
    $bloodType = $_POST['blood_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $urgency = $_POST['urgency_level'] ?? 'urgent';
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($phone) || empty($bloodType) || empty($location)) {
        showAlert('Please fill in all required fields.', 'error');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO emergency_requests (requester_name, requester_phone, requester_email, blood_type, location, urgency_level, message)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $phone, $email, $bloodType, $location, $urgency, $message]);
        showAlert('Emergency request posted successfully! Donors will be notified.', 'success');
        redirect('view_requests.php');
    }
}

// Get all active requests
$requests = $pdo->query("
    SELECT * FROM emergency_requests 
    WHERE status = 'active' 
    ORDER BY 
        FIELD(urgency_level, 'critical', 'urgent', 'moderate'),
        created_at DESC
")->fetchAll();

$pageTitle = 'Emergency Requests';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-bell"></i> Emergency Blood Requests</h1>
    <p>View and respond to urgent blood donation requests from our community.</p>
</div>

<!-- Post Request Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle"></i> Post an Emergency Request</h3>
    </div>
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Your Name <span class="required">*</span></label>
                <input type="text" name="requester_name" class="form-control" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Phone Number <span class="required">*</span></label>
                <input type="tel" name="requester_phone" class="form-control" required placeholder="077xxxxxxx">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email (optional)</label>
                <input type="email" name="requester_email" class="form-control" placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>Blood Type Needed <span class="required">*</span></label>
                <select name="blood_type" class="form-control" required>
                    <option value="">Select Blood Type</option>
                    <?php foreach ($BLOOD_TYPES as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
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
                    <option value="<?php echo $loc; ?>"><?php echo $loc; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Urgency Level <span class="required">*</span></label>
                <select name="urgency_level" class="form-control" required>
                    <option value="critical">Critical - Immediate Need</option>
                    <option value="urgent" selected>Urgent - Within 24 Hours</option>
                    <option value="moderate">Moderate - Within 48 Hours</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Message / Details <span class="required">*</span></label>
            <textarea name="message" class="form-control" rows="3" required placeholder="Describe the situation, hospital name, patient details, etc."></textarea>
        </div>
        <button type="submit" name="post_request" class="btn btn-danger btn-lg" style="width: 100%;">
            <i class="fas fa-paper-plane"></i> Post Emergency Request
        </button>
    </form>
</div>

<!-- Active Requests -->
<section class="section-title" style="margin-top: 40px;">
    <h2>Active Emergency Requests</h2>
    <p>These patients need blood urgently. If you can help, please reach out.</p>
</section>

<?php if (count($requests) > 0): ?>
<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php foreach ($requests as $request): 
        $urgencyClass = $request['urgency_level'];
    ?>
    <div class="card request-card <?php echo $urgencyClass; ?>" style="border-left: 5px solid <?php echo $request['urgency_level'] == 'critical' ? 'red' : ($request['urgency_level'] == 'urgent' ? 'orange' : 'blue'); ?>; padding: 20px; margin-bottom: 15px;">
        <div class="request-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <span class="badge" style="background: <?php echo $request['urgency_level'] == 'critical' ? 'red' : ($request['urgency_level'] == 'urgent' ? 'orange' : 'blue'); ?>; color: white; padding: 3px 8px; border-radius: 3px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo ucfirst($request['urgency_level']); ?>
                </span>
                <span class="blood-type" style="margin-left: 10px; font-weight: bold; font-size: 1.2rem; color: #cc0000;"><?php echo $request['blood_type']; ?></span>
            </div>
            <small style="color: var(--gray);"><?php echo timeAgo($request['created_at']); ?></small>
        </div>
        <h4 style="margin-bottom: 15px;">
            <i class="fas fa-user"></i> <?php echo sanitize($request['requester_name']); ?>
        </h4>
        <div class="request-details" style="font-size: 0.95rem; margin-bottom: 15px; line-height: 1.6;">
            <p style="margin: 3px 0;"><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($request['location']); ?></p>
            <p style="margin: 3px 0;"><i class="fas fa-phone"></i> <?php echo sanitize($request['requester_phone']); ?></p>
            <?php if ($request['requester_email']): ?>
            <p style="margin: 3px 0;"><i class="fas fa-envelope"></i> <?php echo sanitize($request['requester_email']); ?></p>
            <?php endif; ?>
        </div>
        <div class="request-message" style="background: #f8f9fa; padding: 10px; border-radius: var(--radius); font-style: italic; margin-bottom: 15px;">
            "<?php echo sanitize($request['message']); ?>"
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="search_donors.php?blood_type=<?php echo $request['blood_type']; ?>" class="btn btn-primary btn-sm" style="background: var(--primary); color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 0.85rem;">
                <i class="fas fa-search"></i> Find <?php echo $request['blood_type']; ?> Donors
            </a>
            <?php if (isLoggedIn() && isDonor() && $_SESSION['blood_type'] === $request['blood_type']): ?>
            <a href="contact_requester.php?request_id=<?php echo $request['id']; ?>" class="btn btn-success btn-sm" style="background: green; color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 0.85rem;">
                <i class="fas fa-hand-holding-heart"></i> I Can Donate
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty-state" style="text-align: center; padding: 40px; color: #999;">
    <i class="fas fa-check-circle" style="font-size: 3rem; color: green; margin-bottom: 15px;"></i>
    <h3>No Active Emergency Requests</h3>
    <p>All current requests have been fulfilled. If you need blood, post a request above.</p>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
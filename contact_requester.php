<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isDonor()) {
    showAlert('Only registered donors can respond to requests.', 'error');
    redirect('login.php');
}

$requestId = $_GET['request_id'] ?? 0;
$userId = $_SESSION['user_id'];

// Get request info
$stmt = $pdo->prepare("SELECT * FROM emergency_requests WHERE id = ? AND status = 'active'");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    showAlert('Request not found or already fulfilled.', 'error');
    redirect('view_requests.php');
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');
    $revealPhone = isset($_POST['reveal_phone']) ? 1 : 0;
    
    if (empty($message)) {
        showAlert('Please enter a message.', 'error');
    } else {
        // රික්වෙස්ටර් කෙනෙක් කෙලින්ම User කෙනෙක් නෙවෙයි නම්, අපි මේක Admin (ID: 1) හරහා හෝ පද්ධතියේ සටහන් වෙන විදිහට සකසනවා
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, request_id, message, sender_phone_revealed, is_phone_revealed)
            VALUES (?, 1, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $requestId, $message, $revealPhone, $revealPhone]);
        
        showAlert('Your response has been sent! The requester will contact you soon.', 'success');
        redirect('view_requests.php');
    }
}

$pageTitle = 'Respond to Request';
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2><i class="fas fa-hand-holding-heart"></i> I Can Help!</h2>
    </div>
    
    <div class="card request-card" style="margin-bottom: 25px; padding: 20px; background: #fff5f5; border-left: 5px solid red; border-radius: var(--radius);">
        <div class="request-header" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <div>
                <span class="badge" style="background: orange; color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem;"><?php echo ucfirst($request['urgency_level']); ?></span>
                <span class="blood-type" style="margin-left: 10px; font-weight: bold; color: red;"><?php echo $request['blood_type']; ?></span>
            </div>
            <small style="color: #666;"><?php echo timeAgo($request['created_at']); ?></small>
        </div>
        <h4><?php echo sanitize($request['requester_name']); ?></h4>
        <div class="request-details" style="margin: 10px 0; font-size: 0.95rem;">
            <p><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($request['location']); ?></p>
            <p style="color: green; font-weight: bold;"><i class="fas fa-phone"></i> <?php echo sanitize($request['requester_phone']); ?></p>
        </div>
        <div class="request-message" style="background: white; padding: 10px; border-radius: 5px; font-style: italic;">
            "<?php echo sanitize($request['message']); ?>"
        </div>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Your Message to Requester <span class="required">*</span></label>
            <textarea name="message" class="form-control" rows="5" required placeholder="Hi, I saw your request and I can help..."></textarea>
        </div>
        
        <div class="form-group" style="margin: 15px 0;">
            <div class="form-check">
                <input type="checkbox" name="reveal_phone" id="reveal_phone" value="1" checked>
                <label for="reveal_phone" style="margin-left: 8px; cursor: pointer;">Share my phone number with the requester for direct contact</label>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="send_message" class="btn btn-success" style="flex: 1; background: green; color: white; border: none; padding: 10px; border-radius: var(--radius); cursor: pointer; font-size: 1rem;">
                <i class="fas fa-check-circle"></i> Confirm I Can Donate
            </button>
            <a href="view_requests.php" class="btn btn-secondary" style="border-color: var(--gray); color: var(--dark); text-decoration: none; padding: 10px 15px; border-radius: var(--radius); background: #eee;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
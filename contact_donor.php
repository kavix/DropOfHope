<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    showAlert('Please login to contact donors.', 'error');
    redirect('login.php');
}

$donorId = $_GET['donor_id'] ?? 0;
$userId = $_SESSION['user_id'];

// Get donor info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'donor' AND is_verified = 1");
$stmt->execute([$donorId]);
$donor = $stmt->fetch();

if (!$donor) {
    showAlert('Donor not found or not verified.', 'error');
    redirect('search_donors.php');
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');
    $revealPhone = isset($_POST['reveal_phone']) ? 1 : 0;
    
    if (empty($message)) {
        showAlert('Please enter a message.', 'error');
    } else {
        // කලින් මේ දෙන්නා චැට් කරලා තියෙනවද කියලා බලනවා Phone disclosure එක අප්ඩේට් කරන්න
        $check = $pdo->prepare("SELECT receiver_phone_revealed FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1");
        $check->execute([$userId, $donorId, $donorId, $userId]);
        $existing = $check->fetch();

        $receiverPhoneRevealed = $existing ? $existing['receiver_phone_revealed'] : 0;
        $isPhoneRevealed = ($revealPhone && $receiverPhoneRevealed) ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, sender_phone_revealed, receiver_phone_revealed, is_phone_revealed)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $donorId, $message, $revealPhone, $receiverPhoneRevealed, $isPhoneRevealed]);

        // දෙන්නම එකඟ නම් හැම පරණ මැසේජ් එකකම ෆෝන් නම්බර් එක පෙන්වන්න සලස්වනවා
        if ($isPhoneRevealed) {
            $update = $pdo->prepare("UPDATE messages SET is_phone_revealed = 1, receiver_phone_revealed = 1, sender_phone_revealed = 1 WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $update->execute([$userId, $donorId, $donorId, $userId]);
        }

        showAlert('Message sent successfully! The donor will be notified.', 'success');
        redirect('messages.php');
    }
}

$pageTitle = 'Contact Donor';
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2><i class="fas fa-envelope"></i> Contact Donor</h2>
    </div>
    
    <div class="donor-card" style="margin-bottom: 25px; padding: 20px; background: var(--light); border-radius: var(--radius); display: flex; align-items: center; gap: 20px;">
        <div class="blood-type" style="font-size: 2rem; font-weight: bold; color: white; background: red; padding: 10px 18px; border-radius: 50%;"><?php echo $donor['blood_type']; ?></div>
        <div class="donor-info">
            <h4 style="margin: 0 0 5px 0;"><?php echo sanitize($donor['full_name']); ?></h4>
            <p style="margin: 0 0 5px 0;"><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($donor['location']); ?></p>
            <p style="margin: 0;">
                <span class="badge <?php echo $donor['availability_status'] == 'available' ? 'badge-success' : 'badge-warning'; ?>">
                    <?php echo ucfirst($donor['availability_status']); ?>
                </span>
            </p>
        </div>
    </div>
    
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="fas fa-info-circle"></i> <strong>Privacy Notice:</strong> Your phone number will not be shared unless you check the option below.
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Your Message <span class="required">*</span></label>
            <textarea name="message" class="form-control" rows="5" required placeholder="Introduce yourself, explain the emergency situation..."></textarea>
        </div>
        
        <div class="form-group" style="margin: 15px 0;">
            <div class="form-check">
                <input type="checkbox" name="reveal_phone" id="reveal_phone" value="1">
                <label for="reveal_phone" style="margin-left: 8px; cursor: pointer;">I agree to share my phone number with this donor for faster communication</label>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="send_message" class="btn btn-primary" style="flex: 1; background: var(--primary); color: white;">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
            <a href="search_donors.php" class="btn btn-secondary" style="border-color: var(--gray); color: var(--dark); text-decoration: none; padding: 10px 15px; border-radius: var(--radius); background: #eee;">
                <i class="fas fa-arrow-left"></i> Back to Search
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
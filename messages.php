<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Mark message as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$_GET['read'], $userId]);
}

// Get received messages with sender info
$receivedMessages = $pdo->prepare("
    SELECT m.*, u.full_name as sender_name, u.blood_type as sender_blood_type, u.phone as sender_phone
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$receivedMessages->execute([$userId]);
$received = $receivedMessages->fetchAll();

// Get sent messages with receiver info
$sentMessages = $pdo->prepare("
    SELECT m.*, u.full_name as receiver_name, u.blood_type as receiver_blood_type, u.phone as receiver_phone
    FROM messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.created_at DESC
");
$sentMessages->execute([$userId]);
$sent = $sentMessages->fetchAll();

$pageTitle = 'Messages';
require_once 'includes/header.php';
?>

<div class="dashboard-header">
    <h1><i class="fas fa-envelope"></i> My Messages</h1>
    <p>View and manage your conversations with donors and requesters.</p>
</div>

<div class="grid grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">
    <!-- Received Messages -->
    <div class="card">
        <div class="card-header" style="padding-bottom: 10px; border-bottom: 2px solid #f4f6f9;">
            <h3><i class="fas fa-inbox"></i> Received (<?php echo count($received); ?>)</h3>
        </div>
        
        <?php if (count($received) > 0): ?>
        <div class="message-list">
            <?php foreach ($received as $msg): ?>
            <div class="message-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; gap: 15px; <?php echo !$msg['is_read'] ? 'background: #fff5f5;' : ''; ?>">
                <div class="message-avatar" style="width: 40px; height: 40px; background: red; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; flex-shrink: 0;"><?php echo substr($msg['sender_name'], 0, 1); ?></div>
                <div class="message-content" style="flex-grow: 1;">
                    <h5 style="margin: 0 0 5px 0; font-size: 1rem;"><?php echo sanitize($msg['sender_name']); ?> 
                        <span class="blood-type" style="background: #cc0000; color: white; padding: 2px 5px; font-size: 0.7rem; border-radius: 3px; margin-left: 5px;"><?php echo $msg['sender_blood_type']; ?></span>
                        <?php if (!$msg['is_read']): ?>
                        <span class="badge badge-danger" style="background: red; color: white; font-size: 0.65rem; padding: 2px 5px; border-radius: 3px;">New</span>
                        <?php endif; ?>
                    </h5>
                    <p style="margin: 5px 0; color: #555; font-size: 0.95rem;"><?php echo sanitize($msg['message']); ?></p>
                    
                    <!-- Phone Disclosure Check -->
                    <?php if ($msg['is_phone_revealed'] || $msg['sender_phone_revealed']): ?>
                    <p style="font-size: 0.85rem; color: green; font-weight: bold; margin-top: 5px;"><i class="fas fa-phone"></i> Contact: <?php echo sanitize($msg['sender_phone']); ?></p>
                    <?php else: ?>
                    <p style="font-size: 0.85rem; color: #999; margin-top: 5px;"><i class="fas fa-lock"></i> Phone hidden. Reply and agree to share contact info.</p>
                    <?php endif; ?>
                </div>
                <div class="message-time" style="font-size: 0.8rem; color: #888; text-align: right; flex-shrink: 0;">
                    <?php echo timeAgo($msg['created_at']); ?>
                    <?php if (!$msg['is_read']): ?>
                    <br><a href="?read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-success" style="margin-top: 8px; font-size: 0.75rem; background: green; color: white; padding: 3px 6px; text-decoration: none; border-radius: 3px;"><i class="fas fa-check"></i> Mark Read</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding: 30px; text-align: center; color: #999;">
            <i class="fas fa-inbox" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
            <h3>No Messages</h3>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sent Messages -->
    <div class="card">
        <div class="card-header" style="padding-bottom: 10px; border-bottom: 2px solid #f4f6f9;">
            <h3><i class="fas fa-paper-plane"></i> Sent (<?php echo count($sent); ?>)</h3>
        </div>
        
        <?php if (count($sent) > 0): ?>
        <div class="message-list">
            <?php foreach ($sent as $msg): ?>
            <div class="message-item" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; gap: 15px;">
                <div class="message-avatar" style="width: 40px; height: 40px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; flex-shrink: 0;"><?php echo substr($msg['receiver_name'], 0, 1); ?></div>
                <div class="message-content" style="flex-grow: 1;">
                    <h5 style="margin: 0 0 5px 0; font-size: 1rem;">To: <?php echo sanitize($msg['receiver_name']); ?> 
                        <span class="blood-type" style="background: #cc0000; color: white; padding: 2px 5px; font-size: 0.7rem; border-radius: 3px; margin-left: 5px;"><?php echo $msg['receiver_blood_type']; ?></span>
                    </h5>
                    <p style="margin: 5px 0; color: #555; font-size: 0.95rem;"><?php echo sanitize($msg['message']); ?></p>
                    
                    <?php if ($msg['sender_phone_revealed']): ?>
                    <p style="font-size: 0.85rem; color: #007bff; font-weight: bold;"><i class="fas fa-check-circle"></i> Your phone was shared</p>
                    <?php endif; ?>
                </div>
                <div class="message-time" style="font-size: 0.8rem; color: #888; text-align: right; flex-shrink: 0;">
                    <?php echo timeAgo($msg['created_at']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding: 30px; text-align: center; color: #999;">
            <i class="fas fa-paper-plane" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
            <h3>No Sent Messages</h3>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
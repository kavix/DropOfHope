<?php
// LifeLink - Database Configuration
session_start();

// Read credentials from environment variables (set in .env or server config).
// Fallback to local development defaults so cloning the repo "just works".
$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'lifelink_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

$isProduction = (getenv('APP_ENV') === 'production');

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Do not expose connection details on a live server
    if ($isProduction) {
        die("Database connection failed. Please contact the administrator.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isDonor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'donor';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function checkEligibility($lastDonationDate) {
    if (empty($lastDonationDate)) return true;
    $lastDate = new DateTime($lastDonationDate);
    $today = new DateTime();
    $interval = $lastDate->diff($today);
    $days = $interval->days;
    return $days >= 90; // 3 months minimum gap
}

function getDaysUntilEligible($lastDonationDate) {
    if (empty($lastDonationDate)) return 0;
    $lastDate = new DateTime($lastDonationDate);
    $eligibleDate = clone $lastDate;
    $eligibleDate->modify('+90 days');
    $today = new DateTime();
    if ($today >= $eligibleDate) return 0;
    $diff = $today->diff($eligibleDate);
    return $diff->days;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

$BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$LOCATIONS = [
    'University of Kelaniya',
    'Kelaniya Town',
    'Kiribathgoda',
    'Wattala',
    'Colombo North Teaching Hospital',
    'Colombo South Teaching Hospital',
    'National Hospital Colombo',
    'Ragama Hospital',
    'Gampaha',
    'Other'
];
?>

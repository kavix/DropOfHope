# LifeLink - Blood Donation Network

A web-based platform connecting blood donors with patients in need, built for the University of Kelaniya community using raw PHP, HTML, CSS, and JavaScript.

**Course:** COSC 31103 / BECS 31233 - Web & Internet Technologies  
**Department:** Department of Statistics & Computer Science  
**University:** University of Kelaniya  
**Academic Year:** 2024/2025

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [File-by-File Documentation](#file-by-file-documentation)
   - [Root PHP Files](#root-php-files)
   - [Includes Folder](#includes-folder)
   - [CSS Folder](#css-folder)
   - [JS Folder](#js-folder)
   - [Database File](#database-file)
3. [How Files Connect](#how-files-connect)
4. [Setup Instructions](#setup-instructions)
5. [Demo Accounts](#demo-accounts)
6. [Security Features](#security-features)

---

## Project Overview

LifeLink is a blood donation network platform with three user roles:
- **Donors** - Register, manage profile, track donations, respond to requests
- **Patients/Families** - Post emergency requests, search donors, contact them securely
- **Admins** - Verify donors, manage database, add donation records, view statistics

**Technologies:** Raw PHP (no frameworks), HTML5, CSS3, JavaScript, MySQL, Apache

---

## File-by-File Documentation

### Root PHP Files

---

#### `index.php` (146 lines)

**Purpose:** Landing page / homepage. First page users see.

**What it does:**
- Queries the database for live statistics (verified donors count, active requests count, total donations count)
- Displays a hero banner with the LifeLink branding and call-to-action buttons
- Shows "How It Works" feature cards (3 cards: Register, Find, Save)
- Shows "Why LifeLink?" info cards (4 cards: Time Critical, Privacy, Verified, Local)
- Displays the 3 most recent active emergency requests with urgency badges
- Shows a final CTA banner encouraging registration

**Key Logic:**
```php
$stats = [
    'donors' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_verified = 1")->fetchColumn(),
    'requests' => $pdo->query("SELECT COUNT(*) FROM emergency_requests WHERE status = 'active'")->fetchColumn(),
    'donations' => $pdo->query("SELECT COUNT(*) FROM donation_history")->fetchColumn()
];
```
- Fetches live counts from database for the hero stats section
- Queries last 3 active emergency requests ordered by `created_at DESC`
- Uses `timeAgo()` helper to show relative time (e.g., "2 hours ago")
- Uses `sanitize()` helper to prevent XSS on output

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `login.php` (87 lines)

**Purpose:** User authentication page.

**What it does:**
- Shows a login form with email and password fields
- If already logged in, redirects to appropriate dashboard (admin or donor)
- Validates credentials against database
- Uses `password_verify()` to check hashed passwords
- Sets session variables on successful login: `user_id`, `user_name`, `user_type`, `blood_type`
- Shows error message for invalid credentials
- Displays demo account credentials for testing

**Key Logic:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['blood_type'] = $user['blood_type'];
    // Redirect based on user_type
}
```

**Form Validation:** Client-side via `validateForm('loginForm')` JavaScript function

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `register.php` (160 lines)

**Purpose:** Donor registration form.

**What it does:**
- Shows a registration form with fields: full name, email, phone, blood type, location, last donation date, password, confirm password
- Validates all inputs server-side:
  - Full name required
  - Valid email format
  - Phone required
  - Password minimum 6 characters
  - Passwords must match
  - Blood type and location required
- Checks if email already exists in database
- Hashes password with `password_hash($password, PASSWORD_DEFAULT)`
- Inserts new user with `user_type = 'donor'` and `is_verified = 0` (pending admin approval)
- On success, shows alert and redirects to login page
- Preserves form values on validation errors (sticky form)

**Key Logic:**
```php
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, blood_type, location, last_donation_date, user_type, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 'donor', 0)");
```

**Blood Type Dropdown:** Populated from `$BLOOD_TYPES` array in config (`['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']`)

**Location Dropdown:** Populated from `$LOCATIONS` array in config (10 locations including University of Kelaniya, hospitals, etc.)

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `logout.php` (6 lines)

**Purpose:** Destroy user session and redirect to homepage.

**What it does:**
- Calls `session_destroy()` to clear all session data
- Redirects to `index.php`

**Content:**
```php
<?php
require_once 'includes/config.php';
session_destroy();
redirect('index.php');
?>
```

**Dependencies:** `includes/config.php`

---

#### `donor_dashboard.php` (226 lines)

**Purpose:** Personal dashboard for logged-in donors.

**What it does:**
- **Authentication check:** Redirects non-donors to login page
- **Fetches donor info:** Name, blood type, availability status, verification status, last donation date
- **Fetches donation history:** All past donations for this donor
- **Counts unread messages:** Number of unread messages in inbox
- **Checks eligibility:** Uses `checkEligibility()` to determine if donor can donate (90-day gap)
- **Handles availability toggle:** POST handler to switch between available/unavailable
- **Handles profile update:** POST handler to update phone, location, last donation date

**Dashboard Stats Cards (4 cards):**
1. Blood Type - Shows donor's blood type
2. Total Donations - Count from donation_history table
3. Unread Messages - Count of unread messages
4. Donation Status - "Eligible" or "X days" until eligible

**Profile Section:**
- Displays read-only fields (name, email)
- Editable fields: phone, location (dropdown), last donation date
- Availability toggle button (switches between Available/Unavailable)
- Verification badge (Verified/Pending)

**Quick Actions Panel:**
- Find Other Donors → `search_donors.php`
- View Emergency Requests → `view_requests.php`
- My Messages → `messages.php` (with unread count badge)
- Full Donation History → `donation_history.php`

**Recent Donations Table:** Shows last 5 donations with date, blood type, units, location, notes

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `admin_dashboard.php` (290 lines)

**Purpose:** Admin management panel.

**What it does:**
- **Authentication check:** Only admins can access (redirects others to login)
- **Statistics row (6 stat cards):**
  1. Total Donors - All donor accounts
  2. Verified Donors - `is_verified = 1`
  3. Pending Verification - `is_verified = 0`
  4. Active Requests - Emergency requests with `status = 'active'`
  5. Total Donations - All donation records
  6. Available Now - Donors with `availability_status = 'available'`

- **Pending Verifications Table:** Shows unverified donors with Verify/Delete buttons
- **All Donors Table:** Complete donor list with blood type, location, status, verification, donation count, last donation date
- **Verify donor:** `GET ?verify=ID` → sets `is_verified = 1`
- **Delete donor:** `GET ?delete=ID` → removes donor with confirmation
- **Add Donation Modal:** Popup form to manually add donation records for any donor
  - Select donor from dropdown
  - Set date, blood type, units, location, notes
  - Updates donor's `last_donation_date` automatically
- **Export to CSV:** Button calls `exportTableToCSV('donorsTable', 'donors.csv')` JavaScript function

**Key Logic:**
```php
// Verify donor
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");

// Delete donor
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'donor'");

// Add donation + update last donation date
$stmt = $pdo->prepare("INSERT INTO donation_history (...) VALUES (...)");
$stmt = $pdo->prepare("UPDATE users SET last_donation_date = ? WHERE id = ?");
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `search_donors.php` (143 lines)

**Purpose:** Search and filter verified donors.

**What it does:**
- **Search Filters (3 fields):**
  1. Blood Type dropdown - filter by exact match
  2. Location dropdown - filter by exact match
  3. Text search - searches `full_name`, `email`, `phone` with `LIKE %search%`
- **Dynamic SQL Builder:** Constructs query based on which filters are active, uses prepared statements with parameter array
- **Results sorted by:** Available donors first, then by registration date
- **Donor Cards:** Each result shows:
  - Blood type badge (colored circle)
  - Name, location
  - Availability badge (Available/Unavailable/Resting)
  - Eligibility badge (if not eligible, shows days remaining)
  - Last donation date
  - Contact button (if logged in and donor is available + eligible)
  - Login prompt (if not logged in)
  - Unavailable label (if donor not available)

**Key Logic:**
```php
$sql = "SELECT * FROM users WHERE user_type = 'donor' AND is_verified = 1";
$params = [];

if (!empty($bloodType)) {
    $sql .= " AND blood_type = ?";
    $params[] = $bloodType;
}
// ... more conditions
$sql .= " ORDER BY availability_status = 'available' DESC, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

**Eligibility Check:**
```php
$eligibility = checkEligibility($donor['last_donation_date']);
$isAvailable = $donor['availability_status'] === 'available' && $eligibility;
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `view_requests.php` (161 lines)

**Purpose:** Emergency request board - post and view requests.

**What it does:**
- **Post Request Form (top half):**
  - Fields: requester name, phone, email (optional), blood type, location, urgency level, message
  - Urgency levels: Critical (immediate), Urgent (24h), Moderate (48h)
  - Character counter on message field (JavaScript)
  - On submit: inserts into `emergency_requests` table with `status = 'active'`

- **Active Requests List (bottom half):**
  - Sorted by urgency (Critical → Urgent → Moderate) then by date
  - Each request card shows:
    - Urgency badge (color-coded: red=critical, orange=urgent, blue=moderate)
    - Blood type badge
    - Requester name, location, phone, email
    - Message content
    - "Find [BloodType] Donors" button → links to search with pre-filled blood type
    - "I Can Donate" button (only visible to logged-in donors with matching blood type)

**Key Logic:**
```php
// Insert new request
$stmt = $pdo->prepare("INSERT INTO emergency_requests (requester_name, requester_phone, requester_email, blood_type, location, urgency_level, message) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Fetch active requests sorted by urgency
$requests = $pdo->query("SELECT * FROM emergency_requests WHERE status = 'active' ORDER BY FIELD(urgency_level, 'critical', 'urgent', 'moderate'), created_at DESC")->fetchAll();
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `contact_donor.php` (89 lines)

**Purpose:** Secure contact form for messaging a specific donor.

**What it does:**
- **Authentication check:** Must be logged in
- **Fetches donor info:** By `donor_id` from URL parameter, verifies donor exists and is verified
- **Shows donor preview card:** Blood type, name, location, availability status
- **Privacy notice:** Explains phone number won't be shared unless opted in
- **Message form:** Textarea for message + checkbox to reveal phone number
- **On submit:** Inserts into `messages` table with `sender_id` (current user), `receiver_id` (donor)
- **Redirects to messages page** after successful send

**Key Logic:**
```php
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_phone_revealed) VALUES (?, ?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $donorId, $message, $revealPhone]);
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `contact_requester.php` (111 lines)

**Purpose:** Allow donors to respond to emergency requests.

**What it does:**
- **Authentication check:** Must be logged in as donor
- **Fetches request info:** By `request_id` from URL, verifies request is active
- **Blood type mismatch warning:** If donor's blood type doesn't match request, shows warning alert but still allows response
- **Shows request details card:** Full request information with urgency styling
- **Message form:** Textarea + phone reveal checkbox
- **On submit:** Creates message in database (receiver_id = 1, which is the admin/system account for requester messages)

**Key Logic:**
```php
// Check blood type match
if ($_SESSION['blood_type'] !== $request['blood_type']) {
    showAlert('Your blood type does not match...', 'warning');
}

// Insert response message
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, request_id, message, sender_phone_revealed) VALUES (?, 1, ?, ?, ?)");
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `messages.php` (128 lines)

**Purpose:** Inbox and sent messages page.

**What it does:**
- **Authentication check:** Must be logged in
- **Mark as read:** `GET ?read=ID` marks a specific message as read
- **Received Messages Panel (left):**
  - Joins `messages` with `users` to get sender name and blood type
  - Shows avatar (first letter of name), sender name, blood type badge
  - Shows "New" badge for unread messages
  - Shows message preview (first 100 characters)
  - Shows "Phone shared" indicator if sender revealed phone
  - Shows relative time with "Mark Read" button for unread messages

- **Sent Messages Panel (right):**
  - Joins `messages` with `users` to get receiver name and blood type
  - Shows similar layout for sent messages
  - Shows "Your phone was shared" indicator

**Key Logic:**
```php
// Mark as read
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");

// Get received messages
$receivedMessages = $pdo->prepare("SELECT m.*, u.full_name as sender_name, u.blood_type as sender_blood_type FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC");

// Get sent messages
$sentMessages = $pdo->prepare("SELECT m.*, u.full_name as receiver_name, u.blood_type as receiver_blood_type FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = ? ORDER BY m.created_at DESC");
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

#### `donation_history.php` (134 lines)

**Purpose:** Full donation history page for donors.

**What it does:**
- **Authentication check:** Must be logged in
- **Fetches all donations:** For current user, joined with emergency_requests to show requester name
- **Eligibility banner:** Large card showing eligibility status with color coding
  - Green border + checkmark = eligible
  - Orange border + clock = not eligible, shows days remaining
- **Donation Records Table:** Complete table with all donations
  - Columns: #, Date, Blood Type, Units, Location, Requester, Notes
  - Export to CSV button
- **Donation Facts Cards (3 cards):**
  1. "1 Donation can save up to 3 lives"
  2. "Every 3 months is the recommended gap"
  3. "450ml is the standard donation amount"

**Key Logic:**
```php
$eligibility = checkEligibility($donor['last_donation_date']);
$daysUntilEligible = getDaysUntilEligible($donor['last_donation_date']);
```

**Dependencies:** `includes/config.php`, `includes/header.php`, `includes/footer.php`

---

### Includes Folder

---

#### `includes/config.php` (99 lines)

**Purpose:** Central configuration file. Included at the top of EVERY PHP page.

**What it contains:**

**1. Session Management:**
```php
session_start();
```
- Starts PHP session for all pages

**2. Database Connection:**
```php
$host = 'localhost';
$dbname = 'lifelink_db';
$username = 'root';
$password = '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
```
- Creates PDO connection to MySQL
- Sets error mode to throw exceptions
- Sets default fetch mode to associative arrays

**3. Authentication Helper Functions:**
```php
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'; }
function isDonor() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'donor'; }
```

**4. Redirect Function:**
```php
function redirect($url) {
    header("Location: $url");
    exit();
}
```

**5. Alert System (Flash Messages):**
```php
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
```
- Stores alert in session, displays once, then auto-deletes
- Types: success (green), error (red), warning (yellow)

**6. Eligibility Checker Functions:**
```php
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
```
- `checkEligibility()` returns true/false based on 90-day gap
- `getDaysUntilEligible()` returns number of days until donor can donate again

**7. Sanitization Function:**
```php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
```
- Trims whitespace, strips HTML tags, converts special chars to entities
- Prevents XSS attacks on output

**8. Time Ago Function:**
```php
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
```
- Converts timestamp to human-readable relative time

**9. Global Arrays:**
```php
$BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$LOCATIONS = ['University of Kelaniya', 'Kelaniya Town', 'Kiribathgoda', 'Wattala', 
              'Colombo North Teaching Hospital', 'Colombo South Teaching Hospital', 
              'National Hospital Colombo', 'Ragama Hospital', 'Gampaha', 'Other'];
```
- Used to populate dropdown menus across all forms

**Dependencies:** None (this is the base file everything else depends on)

---

#### `includes/header.php` (52 lines)

**Purpose:** Shared HTML header and navigation. Included at the top of every page after config.

**What it contains:**

**1. Config Check:**
```php
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}
```
- Ensures config is loaded (safety check)

**2. HTML Head:**
- Sets page title: `{pageTitle} - LifeLink - Blood Donation Network`
- Includes Font Awesome 6.4.0 CDN for icons
- Includes local stylesheet: `css/style.css`
- Viewport meta tag for responsive design

**3. Navigation Bar:**
- **Logo:** Heartbeat icon + "LifeLink" text → links to `index.php`
- **Hamburger toggle button:** For mobile menu (calls `toggleNav()` JavaScript)
- **Navigation links:**
  - Home → `index.php`
  - Find Donors → `search_donors.php`
  - Emergency Requests → `view_requests.php`
  - If logged in as Admin: Admin → `admin_dashboard.php`
  - If logged in as Donor: My Dashboard → `donor_dashboard.php`
  - If logged in: Logout → `logout.php`
  - If NOT logged in: Login → `login.php`, Register → `register.php`
- **Active state:** Current page gets `active` CSS class (checked via `basename($_SERVER['PHP_SELF'])`)

**4. Alert Display:**
```php
<?php $alert = getAlert(); if ($alert): ?>
<div class="alert alert-<?php echo $alert['type']; ?>" id="alertBox">
    <?php echo $alert['message']; ?>
    <button onclick="document.getElementById('alertBox').style.display='none'">&times;</button>
</div>
<?php endif; ?>
```
- Displays flash messages from session
- Auto-dismisses via JavaScript after 5 seconds

**5. Main Content Wrapper:**
```html
<main class="main-content">
```
- Opens the main content area (closed in footer.php)

**Dependencies:** `includes/config.php` (indirectly), `css/style.css`, `js/main.js`

---

#### `includes/footer.php` (38 lines)

**Purpose:** Shared HTML footer. Included at the bottom of every page.

**What it contains:**

**1. Closes Main Content:**
```html
</main>
```

**2. Footer Section:**
- **3-column layout:**
  - **Column 1:** LifeLink branding, description, social media icons (Facebook, Twitter, Instagram)
  - **Column 2:** Quick Links (Home, Find Donors, Emergency Requests, Become a Donor)
  - **Column 3:** Contact info (email, phone, address)

**3. Footer Bottom:**
- Copyright notice with current year: `&copy; <?php echo date('Y'); ?>`
- Department and university name

**4. JavaScript Include:**
```html
<script src="js/main.js"></script>
```
- Loads all JavaScript utilities

**5. Closes HTML Document:**
```html
</body>
</html>
```

**Dependencies:** None (but relies on `main.js` being present)

---

### CSS Folder

---

#### `css/style.css` (1032 lines)

**Purpose:** Complete stylesheet for the entire application. Single file, no external CSS frameworks.

**What it contains (organized by section):**

**1. CSS Variables (Lines 1-23):**
```css
:root {
    --primary: #c0392b;        /* Blood red */
    --primary-dark: #a93226;   /* Darker red */
    --secondary: #2c3e50;      /* Dark blue-gray */
    --accent: #27ae60;         /* Green for success */
    --warning: #f39c12;        /* Orange for warnings */
    --danger: #e74c3c;        /* Red for errors */
    --info: #3498db;           /* Blue for info */
    --light: #ecf0f1;          /* Light gray background */
    --shadow: 0 2px 10px rgba(0,0,0,0.1);
    --radius: 8px;             /* Border radius */
    --transition: all 0.3s ease;
}
```

**2. Base Styles (Lines 25-39):**
- Reset margins/padding
- Set font family to Segoe UI
- Body uses flexbox column layout (footer sticks to bottom)

**3. Navbar (Lines 41-120):**
- Sticky positioning at top
- White background with shadow
- Flexbox layout: logo left, menu right
- Mobile hamburger button (hidden on desktop)
- Nav links with hover effects (red background, white text)
- Active state styling

**4. Hero Section (Lines 174-236):**
- Gradient background (red to dark red)
- Large title, subtitle, CTA buttons
- Stats row with 3 numbers (donors, requests, donations)
- Decorative circle overlay

**5. Buttons (Lines 238-305):**
- 6 button variants: primary, secondary, danger, success, info
- Size variants: sm, lg
- Hover effects: lift up, color change
- Flexbox with icon + text gap

**6. Cards (Lines 307-341):**
- White background, rounded corners, shadow
- Hover effect: larger shadow
- Card header with bottom border

**7. Grid System (Lines 343-356):**
- CSS Grid with auto-fit
- 3 column variants: default (280px min), grid-2 (400px min), grid-3 (300px min)

**8. Feature Cards (Lines 358-378):**
- Centered text, large icon, title, description
- Used on homepage "How It Works" section

**9. Forms (Lines 380-437):**
- Form groups with labels
- Input styling: border, padding, focus state with red glow
- Form row: 2-column grid
- Checkbox styling

**10. Tables (Lines 439-472):**
- Full width, collapsed borders
- Header row with light gray background
- Hover effect on rows

**11. Badges (Lines 474-506):**
- 5 color variants: success, warning, danger, info, primary
- Pill-shaped (border-radius: 20px)

**12. Blood Type Badge (Lines 508-520):**
- Circular badge (45x45px)
- Red background, white text, bold
- Used throughout to display blood types

**13. Donor Card (Lines 522-553):**
- Flexbox layout: blood badge + info + actions
- Used in search results

**14. Request Card (Lines 555-607):**
- Left border color indicates urgency (red=critical, orange=urgent, blue=moderate)
- Header with badges and time
- Details grid, message box

**15. Dashboard Stats (Lines 609-660):**
- Stat cards with icon + number + label
- Icon in circular background

**16. Eligibility Status (Lines 662-671):**
- `.eligible` = green text
- `.not-eligible` = red text

**17. Messages (Lines 673-721):**
- Message list with scroll
- Message item: avatar (circle with initial) + content + time
- Unread messages have yellow background

**18. Search Bar (Lines 723-735):**
- Flexbox row of filter inputs
- Responsive: stacks on mobile

**19. Footer (Lines 737-818):**
- Dark background (secondary color)
- 3-column grid for content
- Social media icon circles
- Bottom bar with copyright

**20. Responsive Design (Lines 820-891):**
- **Mobile breakpoint: max-width 768px**
- Hamburger menu becomes visible
- Nav menu becomes dropdown
- Grids become single column
- Donor cards stack vertically
- Stats cards stack vertically
- Form rows become single column

**21. Utility Classes (Lines 893-914):**
- Text alignment: `.text-center`, `.text-right`
- Margin utilities: `.mb-0` to `.mb-3`, `.mt-0` to `.mt-3`
- `.hidden` display none

**22. Section Title (Lines 916-932):**
- Centered heading with subtitle
- Used before content sections

**23. Empty State (Lines 934-950):**
- Centered icon + heading + text
- Used when no data to display

**24. Pagination (Lines 952-975):**
- Flexbox row of page numbers
- Hover/active states with red background

**25. Modal (Lines 977-1032):**
- Fixed overlay with dark background (50% opacity)
- Centered white card
- Scale animation on open/close
- Close button in header

**Dependencies:** None (pure CSS, no frameworks)

---

### JS Folder

---

#### `js/main.js` (193 lines)

**Purpose:** JavaScript utilities for the entire application.

**What it contains:**

**1. Mobile Navigation Toggle (Lines 1-4):**
```javascript
function toggleNav() {
    const navMenu = document.getElementById('navMenu');
    navMenu.classList.toggle('active');
}
```
- Toggles `.active` class on nav menu for mobile hamburger button

**2. Auto-Hide Alerts (Lines 6-13):**
```javascript
setTimeout(() => {
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        alertBox.style.opacity = '0';
        setTimeout(() => alertBox.style.display = 'none', 300);
    }
}, 5000);
```
- Fades out alert messages after 5 seconds

**3. Modal Functions (Lines 15-38):**
```javascript
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('active');
    document.body.style.overflow = '';
}
```
- Opens/closes modal by ID
- Prevents body scroll when modal is open
- Click on overlay closes modal

**4. Form Validation (Lines 40-58):**
```javascript
function validateForm(formId) {
    const form = document.getElementById(formId);
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#e74c3c';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    return isValid;
}
```
- Checks all required fields have values
- Highlights empty fields with red border
- Returns true/false for form submission

**5. Blood Type Compatibility (Lines 60-74):**
```javascript
const BLOOD_COMPATIBILITY = {
    'A+': ['A+', 'A-', 'O+', 'O-'],
    'A-': ['A-', 'O-'],
    // ... all 8 blood types
};

function getCompatibleBloodTypes(bloodType) {
    return BLOOD_COMPATIBILITY[bloodType] || [];
}
```
- Lookup table for blood type compatibility
- Function to get compatible types (ready for future expansion)

**6. Eligibility Checker (Lines 76-89):**
```javascript
function checkEligibility(lastDonationDate) {
    if (!lastDonationDate) return { eligible: true, days: 0 };
    const lastDate = new Date(lastDonationDate);
    const today = new Date();
    const diffDays = Math.ceil(Math.abs(today - lastDate) / (1000 * 60 * 60 * 24));
    return {
        eligible: diffDays >= 90,
        days: Math.max(0, 90 - diffDays)
    };
}
```
- Client-side version of eligibility check
- Returns object with `eligible` boolean and `days` remaining

**7. Smooth Scroll (Lines 91-97):**
```javascript
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}
```

**8. Confirm Delete (Lines 99-102):**
```javascript
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}
```
- Browser confirm dialog for destructive actions

**9. Character Counter (Lines 104-116):**
```javascript
function charCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    input.addEventListener('input', function() {
        const remaining = maxLength - this.value.length;
        counter.textContent = remaining + ' characters remaining';
        counter.style.color = remaining < 20 ? '#e74c3c' : '#95a5a6';
    });
}
```
- Live character count for textareas
- Turns red when under 20 characters remaining

**10. Password Toggle (Lines 118-131):**
```javascript
function togglePassword(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    toggle.addEventListener('click', function() {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
}
```
- Toggles password field between visible/hidden
- Swaps eye icon

**11. AJAX Helper (Lines 133-144):**
```javascript
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            callback(xhr.responseText);
        }
    };
    xhr.send(data);
}
```
- Generic XMLHttpRequest wrapper for future AJAX features

**12. Print Page (Lines 146-149):**
```javascript
function printPage() {
    window.print();
}
```

**13. Export Table to CSV (Lines 151-175):**
```javascript
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        let rowData = [];
        row.querySelectorAll('th, td').forEach(cell => {
            rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    // Create download link and trigger click
}
```
- Converts HTML table to CSV format
- Handles quotes properly (doubles them)
- Creates temporary download link
- Used in admin dashboard and donation history

**14. DOM Ready Initialization (Lines 177-193):**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Add active class to current nav item
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-menu a').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Initialize character counters
    document.querySelectorAll('[data-char-counter]').forEach(el => {
        const maxLength = el.getAttribute('maxlength') || 500;
        const counterId = el.getAttribute('data-char-counter');
        charCounter(el.id, counterId, maxLength);
    });
});
```
- Runs when page loads
- Highlights current nav item
- Initializes all character counters

**Dependencies:** None (vanilla JavaScript, no libraries)

---

### Database File

---

#### `database.sql` (100 lines)

**Purpose:** Complete MySQL database schema with sample data.

**What it contains:**

**1. Database Creation (Line 4):**
```sql
CREATE DATABASE IF NOT EXISTS lifelink_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Users Table (Lines 8-22):**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    location VARCHAR(150) NOT NULL,
    last_donation_date DATE DEFAULT NULL,
    availability_status ENUM('available', 'unavailable', 'resting') DEFAULT 'available',
    user_type ENUM('donor', 'admin') DEFAULT 'donor',
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
- Stores both donors and admins
- `user_type` distinguishes role
- `is_verified` controls whether donor appears in search
- `availability_status`: available, unavailable, or resting
- `updated_at` auto-updates on any change

**3. Emergency Requests Table (Lines 25-37):**
```sql
CREATE TABLE emergency_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(100) NOT NULL,
    requester_phone VARCHAR(20) NOT NULL,
    requester_email VARCHAR(100),
    blood_type ENUM(...) NOT NULL,
    location VARCHAR(150) NOT NULL,
    urgency_level ENUM('critical', 'urgent', 'moderate') DEFAULT 'urgent',
    message TEXT,
    status ENUM('active', 'fulfilled', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL
);
```
- Stores blood requests from patients/families
- `status` tracks if request is still active
- `urgency_level` for sorting priority

**4. Donation History Table (Lines 40-52):**
```sql
CREATE TABLE donation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    request_id INT DEFAULT NULL,
    donation_date DATE NOT NULL,
    blood_type ENUM(...) NOT NULL,
    units INT DEFAULT 1,
    location VARCHAR(150),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES emergency_requests(id) ON DELETE SET NULL
);
```
- Tracks all donations
- Links to donor and optionally to emergency request
- `ON DELETE CASCADE` removes history if donor deleted
- `ON DELETE SET NULL` keeps history if request deleted

**5. Messages Table (Lines 55-69):**
```sql
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    request_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_phone_revealed TINYINT(1) DEFAULT 0,
    sender_phone_revealed TINYINT(1) DEFAULT 0,
    receiver_phone_revealed TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES emergency_requests(id) ON DELETE SET NULL
);
```
- Secure messaging system
- `is_read` tracks unread messages
- `sender_phone_revealed` tracks if sender opted to share phone
- Links to request if message is about a specific emergency

**6. Sample Data (Lines 72-100):**
- **1 Admin account:** `admin@lifelink.lk` / `admin123` (password hashed)
- **5 Donor accounts:** Various blood types, locations, availability statuses
  - Kasun Perera (O+, available, verified)
  - Nimali Fernando (A+, available, verified)
  - Sajith Silva (B+, resting, verified)
  - Dilani Weerasinghe (AB-, available, NOT verified)
  - Ruwan Bandara (O-, available, verified)
- **1 Emergency request:** Mrs. Jayawardena needs O+ blood at Colombo North Teaching Hospital (critical)
- **3 Donation records:** Linked to donors 1, 2, and 3

**Dependencies:** MySQL 5.7+ or MariaDB

---

## How Files Connect

### Page Load Flow

```
User Request → [PHP File] → includes/config.php → includes/header.php
                                              ↓
                                        [Page Content]
                                              ↓
                                        includes/footer.php
```

### Authentication Flow

```
register.php → Insert user (is_verified=0) → admin_dashboard.php (admin verifies)
                                                    ↓
login.php → password_verify() → Set session → donor_dashboard.php / admin_dashboard.php
                                                    ↓
logout.php → session_destroy() → index.php
```

### Contact Flow

```
search_donors.php → contact_donor.php → messages.php (inbox)
     or
view_requests.php → contact_requester.php → messages.php
```

### Data Flow

```
[User Action] → [PHP Handler] → [MySQL via PDO] → [HTML Output]
                                    ↑
                              database.sql (schema)
```

---

## Setup Instructions

### 1. Install XAMPP/WAMP
Download and install [XAMPP](https://www.apachefriends.org/) or WAMP server.

### 2. Place Project Files
Copy the project folder to your web server directory:
- **XAMPP:** `C:\xampp\htdocs\lifelink`
- **WAMP:** `C:\wamp64\www\lifelink`

### 3. Create Database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Import the `database.sql` file
4. Or run the SQL script manually in MySQL

### 4. Configure Database (if needed)
Edit `includes/config.php` if your MySQL credentials differ from default:
```php
$host = 'localhost';
$dbname = 'lifelink_db';
$username = 'root';
$password = '';  // Your MySQL password
```

### 5. Access the Application
Open your browser and go to:
```
http://localhost/lifelink/
```

---

## Demo Accounts

| Role | Email | Password | Blood Type |
|------|-------|----------|------------|
| **Admin** | `admin@lifelink.lk` | `admin123` | O+ |
| **Donor** | `kasun@email.com` | `password` | O+ |
| **Donor** | `nimali@email.com` | `password` | A+ |
| **Donor** | `sajith@email.com` | `password` | B+ |
| **Donor** | `dilani@email.com` | `password` | AB- |
| **Donor** | `ruwan@email.com` | `password` | O- |

**Note:** All passwords are hashed in the database. The plain text passwords shown above work for login.

---

## Security Features

1. **Password Hashing:** All passwords stored with `password_hash()` using bcrypt. Verified with `password_verify()`.

2. **SQL Injection Prevention:** All database queries use PDO prepared statements with parameter binding.

3. **XSS Prevention:** All user output passes through `sanitize()` function which uses `htmlspecialchars()` and `strip_tags()`.

4. **Session Security:** PHP sessions used for authentication. Session destroyed on logout.

5. **Phone Privacy:** Phone numbers are never displayed publicly. Only shared via opt-in checkbox in messages.

6. **Admin Verification:** New donors require admin approval (`is_verified = 1`) before appearing in search results.

7. **Eligibility Check:** 90-day gap enforced between donations. Ineligible donors cannot be contacted.

8. **Role-Based Access:** Admin and donor pages check `user_type` session variable and redirect unauthorized users.

---

**Project:** LifeLink - Blood Donation Network  
**Course:** COSC 31103 / BECS 31233 - Web & Internet Technologies  
**Department:** Department of Statistics & Computer Science  
**University:** University of Kelaniya  
**Academic Year:** 2024/2025

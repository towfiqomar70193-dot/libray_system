<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("<div style='font-family:sans-serif;padding:20px;color:red;'>
        <h3>Database Connection Failed</h3>
        <p>" . mysqli_connect_error() . "</p>
        <p>Please make sure XAMPP is running and you have imported <code>database.sql</code></p>
    </div>");
}

mysqli_set_charset($conn, "utf8");

// Session start helper
function start_session_if_needed() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if admin is logged in
function check_admin() {
    start_session_if_needed();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        redirect('../index.php');
    }
}

// Check if user is logged in
function check_user() {
    start_session_if_needed();
    if (!isset($_SESSION['user_id'])) {
        redirect('../index.php');
    }
}

// Calculate fine (2 taka per day overdue). Returns decimal with 2 places.
function calculate_fine($due_date) {
    $today = new DateTime();
    $due = new DateTime($due_date);
    if ($today > $due) {
        $diff = $today->diff($due);
        $days = (int)$diff->days;
        $fine = $days * 2.00;
        return number_format($fine, 2, '.', '');
    }
    return number_format(0, 2, '.', '');
}
?>

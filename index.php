<?php
require_once 'includes/db.php';
start_session_if_needed();

// Already logged in? redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';
$success = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = MD5(trim($_POST['password']));

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) === 1) {
        $user = mysqli_fetch_assoc($res);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];

        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('user/dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
        $tab   = 'login';
    }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = MD5(trim($_POST['password']));
    $class    = mysqli_real_escape_string($conn, trim($_POST['class']));
    $roll_no  = mysqli_real_escape_string($conn, trim($_POST['roll_no']));
    $tab      = 'register';

    // Check email exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'This email is already registered.';
    } elseif (empty($name) || empty($email) || empty($_POST['password'])) {
        $error = 'Please fill in all required fields.';
    } else {
        $sql = "INSERT INTO users (name, email, password, class, roll_no, role) 
                VALUES ('$name', '$email', '$password', '$class', '$roll_no', 'user')";
        if (mysqli_query($conn, $sql)) {
            $success = 'Registration successful! Please login.';
            $tab = 'login';
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-page::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(200,151,58,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(42,80,128,0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        .school-name {
            font-size: 0.8rem;
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 6px;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <div class="school-name">📚 Rajabari Model High School</div>
            <h1>Library System</h1>
            <p>Manage books, issues & returns</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="auth-tabs">
            <a href="?tab=login" class="<?= $tab === 'login' ? 'active' : '' ?>">Login</a>
            <a href="?tab=register" class="<?= $tab === 'register' ? 'active' : '' ?>">Register</a>
        </div>

        <?php if ($tab === 'login'): ?>
        <!-- LOGIN FORM -->
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Login</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:0.82rem;color:var(--text-muted);">
            Admin login: admin@library.com / admin123
        </p>

        <?php else: ?>
        <!-- REGISTER FORM -->
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required placeholder="Your full name">
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Min 6 characters">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Class</label>
                    <select name="class">
                        <option value="">-- Select --</option>
                        <option>Play Group</option>
                        <option>KG</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option>Class <?= $i ?></option>
                        <?php endfor; ?>
                        <option>Teacher</option>
                        <option>Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Roll No.</label>
                    <input type="text" name="roll_no" placeholder="e.g. 15">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Create Account</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

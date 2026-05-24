<?php
require_once '../includes/db.php';
start_session_if_needed();
check_user();

$is_admin     = false;
$current_page = 'dashboard';
$user_id      = $_SESSION['user_id'];

// User's currently issued books
$my_issued = mysqli_query($conn, "
    SELECT i.*, b.title, b.author
    FROM issues i JOIN books b ON i.book_id = b.id
    WHERE i.user_id = $user_id AND i.status = 'issued'
    ORDER BY i.due_date ASC
");

// User profile
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));

// Stats
$total_borrowed = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE user_id=$user_id"))[0];
$active         = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE user_id=$user_id AND status='issued'"))[0];
$overdue_count  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE user_id=$user_id AND status='issued' AND due_date < CURDATE()"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Library</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
            <p style="color:var(--text-muted);font-size:0.88rem;margin-top:4px;">
                <?= htmlspecialchars($user['class'] ?: '') ?> 
                <?= $user['roll_no'] ? '| Roll: ' . htmlspecialchars($user['roll_no']) : '' ?>
            </p>
        </div>
        <a href="books.php" class="btn btn-primary">Browse Books</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num"><?= $total_borrowed ?></div>
            <div class="label">Books Borrowed (Total)</div>
        </div>
        <div class="stat-card accent">
            <div class="num"><?= $active ?></div>
            <div class="label">Currently With Me</div>
        </div>
        <div class="stat-card danger">
            <div class="num"><?= $overdue_count ?></div>
            <div class="label">Overdue Books</div>
        </div>
    </div>

    <?php if ($overdue_count > 0): ?>
    <div class="alert alert-danger">
        ⚠️ You have <strong><?= $overdue_count ?> overdue book(s)</strong>. Please return them to the librarian immediately to avoid additional fines (৳2/day).
    </div>
    <?php endif; ?>

    <!-- Currently Issued -->
    <div class="page-header" style="margin-top:0;">
        <h3 style="font-size:1.2rem;">My Current Books</h3>
        <a href="my_issues.php" style="font-size:0.88rem;">View History →</a>
    </div>

    <?php if (mysqli_num_rows($my_issued) === 0): ?>
        <div class="card">
            <div class="empty-state">
                <div style="font-size:2rem;">📖</div>
                <p>You have no books currently issued.</p>
                <a href="books.php" class="btn btn-primary" style="margin-top:14px;">Browse Available Books</a>
            </div>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Fine (৳)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($my_issued)):
                    $is_overdue = $row['due_date'] < date('Y-m-d');
                    $fine       = calculate_fine($row['due_date']);
                    $days_left  = (new DateTime($row['due_date']))->diff(new DateTime())->days;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                    <td><?= htmlspecialchars($row['author']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td style="<?= $is_overdue ? 'color:var(--danger);font-weight:600;' : '' ?>">
                        <?= $row['due_date'] ?>
                        <?php if (!$is_overdue): ?>
                            <br><small style="color:var(--text-muted)"><?= $days_left ?> day(s) left</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $fine > 0 ? "<strong style='color:var(--danger)'>৳$fine</strong>" : '-' ?>
                    </td>
                    <td>
                        <?php if ($is_overdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-primary">Issued</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <p style="font-size:0.82rem;color:var(--text-muted);margin-top:10px;">
        * To return a book, please visit the library. Fine: ৳2 per day after due date.
    </p>
    <?php endif; ?>
</div>
</body>
</html>

<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin    = true;
$current_page = 'dashboard';

// Stats
$total_books    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM books"))[0];
$total_users    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='user'"))[0];
$total_issued   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status='issued'"))[0];
$total_overdue  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status='issued' AND due_date < CURDATE()"))[0];

// Recent issues
$recent = mysqli_query($conn, "
    SELECT i.*, u.name AS student_name, b.title AS book_title
    FROM issues i
    JOIN users u ON i.user_id = u.id
    JOIN books b ON i.book_id = b.id
    ORDER BY i.id DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Admin Dashboard</h2>
        <span style="color:var(--text-muted);font-size:0.88rem;">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num"><?= $total_books ?></div>
            <div class="label">Total Book Titles</div>
        </div>
        <div class="stat-card accent">
            <div class="num"><?= $total_users ?></div>
            <div class="label">Registered Students</div>
        </div>
        <div class="stat-card success">
            <div class="num"><?= $total_issued ?></div>
            <div class="label">Currently Issued</div>
        </div>
        <div class="stat-card danger">
            <div class="num"><?= $total_overdue ?></div>
            <div class="label">Overdue Books</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:14px;font-size:1.1rem;">Quick Actions</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="books.php?action=add" class="btn btn-primary">+ Add Book</a>
            <a href="issues.php?action=issue" class="btn btn-accent">Issue a Book</a>
            <a href="overdue.php" class="btn btn-danger">View Overdue</a>
            <a href="reports.php" class="btn btn-outline">Generate Report</a>
        </div>
    </div>

    <!-- Recent Issues -->
    <div class="page-header" style="margin-top:0;">
        <h3 style="font-size:1.2rem;">Recent Book Issues</h3>
        <a href="issues.php" style="font-size:0.88rem;">View All →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent) === 0): ?>
                <tr><td colspan="6" class="empty-state">No issues recorded yet.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($recent)): 
                    $is_overdue = ($row['status'] === 'issued' && $row['due_date'] < date('Y-m-d'));
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td>
                        <?php if ($row['status'] === 'returned'): ?>
                            <span class="badge badge-success">Returned</span>
                        <?php elseif ($is_overdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-primary">Issued</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

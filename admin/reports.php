<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'reports';

// Summary stats
$total_books     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM books"))[0];
$total_copies    = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(total_copies) FROM books"))[0];
$avail_copies    = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(available_copies) FROM books"))[0];
$total_students  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='user'"))[0];
$total_issues    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues"))[0];
$active_issues   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status='issued'"))[0];
$returned_issues = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status='returned'"))[0];
$overdue_count   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status='issued' AND due_date < CURDATE()"))[0];
$total_fines     = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(fine),0) FROM issues WHERE status='returned'"))[0];

// Most borrowed books
$top_books = mysqli_query($conn, "
    SELECT b.title, b.author, COUNT(i.id) AS borrow_count
    FROM issues i JOIN books b ON i.book_id = b.id
    GROUP BY i.book_id ORDER BY borrow_count DESC LIMIT 5
");

// Currently issued list
$issued_list = mysqli_query($conn, "
    SELECT i.*, u.name AS student_name, u.class, b.title AS book_title
    FROM issues i
    JOIN users u ON i.user_id = u.id
    JOIN books b ON i.book_id = b.id
    WHERE i.status = 'issued'
    ORDER BY i.due_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @media print {
            .navbar, .btn, .no-print { display: none !important; }
            body { background: #fff; }
            .container { max-width: 100%; padding: 10px; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Library Reports</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">🖨 Print Report</button>
    </div>

    <div style="margin-bottom:8px;color:var(--text-muted);font-size:0.85rem;">
        Generated: <?= date('d F Y, h:i A') ?>
    </div>

    <!-- Summary Stats -->
    <h3 style="margin:20px 0 14px;font-size:1.1rem;">Overall Summary</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num"><?= $total_books ?></div>
            <div class="label">Book Titles</div>
        </div>
        <div class="stat-card accent">
            <div class="num"><?= $total_copies ?></div>
            <div class="label">Total Copies</div>
        </div>
        <div class="stat-card success">
            <div class="num"><?= $avail_copies ?></div>
            <div class="label">Available Copies</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= $total_students ?></div>
            <div class="label">Registered Students</div>
        </div>
        <div class="stat-card accent">
            <div class="num"><?= $total_issues ?></div>
            <div class="label">Total Issues (All Time)</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= $active_issues ?></div>
            <div class="label">Currently Issued</div>
        </div>
        <div class="stat-card success">
            <div class="num"><?= $returned_issues ?></div>
            <div class="label">Returned</div>
        </div>
        <div class="stat-card danger">
            <div class="num"><?= $overdue_count ?></div>
            <div class="label">Overdue</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <p style="font-size:1rem;">Total Fines Collected: <strong style="color:var(--danger);font-size:1.2rem;">৳<?= $total_fines ?></strong></p>
    </div>

    <!-- Top Borrowed Books -->
    <h3 style="margin:0 0 14px;font-size:1.1rem;">Most Borrowed Books</h3>
    <div class="table-wrap" style="margin-bottom:28px;">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Times Borrowed</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                if (mysqli_num_rows($top_books) === 0): ?>
                <tr><td colspan="4" class="empty-state">No borrow history yet.</td></tr>
                <?php else: while ($b = mysqli_fetch_assoc($top_books)): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><span class="badge badge-primary"><?= $b['borrow_count'] ?></span></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Currently Issued Books List -->
    <h3 style="margin:0 0 14px;font-size:1.1rem;">Currently Issued Books</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Book Title</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($issued_list) === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><p>No books currently issued.</p></div></td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($issued_list)):
                    $is_overdue = $row['due_date'] < date('Y-m-d');
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['class'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td>
                        <?php if ($is_overdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-primary">On Time</span>
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

<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'overdue';
$msg = '';

// Return from overdue page
if (isset($_GET['return'])) {
    $issue_id = (int)$_GET['return'];
    $issue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM issues WHERE id=$issue_id"));
    if ($issue && $issue['status'] === 'issued') {
        $fine = calculate_fine($issue['due_date']);
        $return_date = date('Y-m-d');
        mysqli_query($conn, "UPDATE issues SET status='returned', return_date='$return_date', fine=$fine WHERE id=$issue_id");
        mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id={$issue['book_id']}");
        $msg = "Book returned. Fine collected: ৳$fine";
    }
}

$overdue = mysqli_query($conn, "
    SELECT i.*, u.name AS student_name, u.class, u.roll_no, b.title AS book_title
    FROM issues i
    JOIN users u ON i.user_id = u.id
    JOIN books b ON i.book_id = b.id
    WHERE i.status = 'issued' AND i.due_date < CURDATE()
    ORDER BY i.due_date ASC
");

$total_fine = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COALESCE(SUM(DATEDIFF(CURDATE(), due_date) * 2), 0) 
    FROM issues WHERE status='issued' AND due_date < CURDATE()
"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Books - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Overdue Books</h2>
        <span style="background:#f8d7da;color:#721c24;padding:6px 16px;border-radius:20px;font-size:0.88rem;font-weight:600;">
            Total Pending Fine: ৳<?= $total_fine ?>
        </span>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <?php if (mysqli_num_rows($overdue) === 0): ?>
        <div class="card">
            <div class="empty-state">
                <div style="font-size:2.5rem;">✅</div>
                <p>No overdue books at this time!</p>
            </div>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Days Late</th>
                    <th>Fine (৳)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($overdue)):
                    $days_late = (new DateTime())->diff(new DateTime($row['due_date']))->days;
                    $fine = $days_late * 2;
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= htmlspecialchars($row['class'] ?: '') ?> | Roll: <?= htmlspecialchars($row['roll_no'] ?: '-') ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td style="color:var(--danger);font-weight:600;"><?= $row['due_date'] ?></td>
                    <td><span class="badge badge-danger"><?= $days_late ?> day(s)</span></td>
                    <td><strong style="color:var(--danger);">৳<?= $fine ?></strong></td>
                    <td>
                        <a href="?return=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as returned and collect fine of ৳<?= $fine ?>?')">
                            Return & Collect Fine
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
require_once '../includes/db.php';
start_session_if_needed();
check_user();

$is_admin     = false;
$current_page = 'my_issues';
$user_id      = $_SESSION['user_id'];

// Handle return submitted by user
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_issue_id'])) {
    $issue_id = (int)$_POST['return_issue_id'];
    $issue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM issues WHERE id=$issue_id AND user_id=$user_id"));
    if (!$issue) {
        $err = 'Issue record not found.';
    } elseif ($issue['status'] !== 'issued') {
        $err = 'This book is not currently issued.';
    } else {
        $fine = calculate_fine($issue['due_date']);
        $return_date = date('Y-m-d');
        // update issue
        mysqli_query($conn, "UPDATE issues SET status='returned', return_date='$return_date', fine=$fine WHERE id=$issue_id");
        // increment book availability
        mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id={$issue['book_id']}");
        $msg = "Book returned successfully. Fine: ৳$fine";
    }
}

$all_issues = mysqli_query($conn, "
    SELECT i.*, b.title, b.author, b.category
    FROM issues i JOIN books b ON i.book_id = b.id
    WHERE i.user_id = $user_id
    ORDER BY i.id DESC
");

$total_fine_paid = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(fine),0) FROM issues WHERE user_id=$user_id AND status='returned'"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Book History - Library</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>My Borrowing History</h2>
        <?php if ($total_fine_paid > 0): ?>
        <span style="background:#f8d7da;color:#721c24;padding:6px 16px;border-radius:20px;font-size:0.88rem;">
            Total Fines Paid: ৳<?= $total_fine_paid ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if (!empty($msg)): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if (!empty($err)): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <?php if (mysqli_num_rows($all_issues) === 0): ?>
        <div class="card">
            <div class="empty-state">
                <div style="font-size:2rem;">📚</div>
                <p>You haven't borrowed any books yet.</p>
                <a href="books.php" class="btn btn-primary" style="margin-top:14px;">Browse Books</a>
            </div>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine (৳)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($all_issues)):
                    $is_overdue = ($row['status'] === 'issued' && $row['due_date'] < date('Y-m-d'));
                    $live_fine  = ($row['status'] === 'issued') ? calculate_fine($row['due_date']) : $row['fine'];
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                    <td><?= htmlspecialchars($row['author']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td style="<?= $is_overdue ? 'color:var(--danger);font-weight:600;' : '' ?>">
                        <?= $row['due_date'] ?>
                    </td>
                    <td><?= $row['return_date'] ?: '-' ?></td>
                    <td>
                        <?= (float)$live_fine > 0 ? "<span style='color:var(--danger);font-weight:600;'>৳" . number_format((float)$live_fine,2) . "</span>" : '-' ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'returned'): ?>
                            <span class="badge badge-success">Returned</span>
                        <?php elseif ($is_overdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-primary">Issued</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'issued'): ?>
                            <form method="POST" style="display:inline;margin-right:6px;">
                                <input type="hidden" name="return_issue_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this book as returned?')">Return</button>
                            </form>
                            <a href="report.php?issue_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline">Report</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <p style="font-size:0.82rem;color:var(--text-muted);margin-top:10px;">
        * To return a book, please visit the library. Fines are ৳2 per day after the due date.
    </p>
    <?php endif; ?>
</div>
</body>
</html>

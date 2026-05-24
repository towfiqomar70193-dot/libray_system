<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin = true;
$current_page = 'book_reports';
$msg = '';
$error = '';

// Resolve report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $rid = (int)$_POST['resolve_id'];
    $note = isset($_POST['admin_note']) ? mysqli_real_escape_string($conn, trim($_POST['admin_note'])) : '';
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM book_reports WHERE id=$rid"));
    if (!$r) {
        $error = 'Report not found.';
    } elseif ($r['status'] === 'resolved') {
        $error = 'Report already resolved.';
    } else {
        $admin_id = $_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE book_reports SET status='resolved', admin_id=$admin_id, admin_note='$note', resolved_at='$now' WHERE id=$rid");
        $msg = 'Report marked as resolved.';
    }
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where = $search ? "AND (u.name LIKE '%$search%' OR b.title LIKE '%$search%' OR r.report_text LIKE '%$search%')" : '';
$reports = mysqli_query($conn, "SELECT r.*, u.name AS user_name, b.title AS book_title FROM book_reports r JOIN users u ON r.user_id=u.id JOIN books b ON r.book_id=b.id WHERE 1=1 $where ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container">
    <div class="page-header">
        <h2>Book Reports</h2>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by user, book or text..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="book_reports.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Book</th>
                    <th>Report</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($reports) === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><p>No reports found.</p></div></td></tr>
                <?php else: while ($r = mysqli_fetch_assoc($reports)): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['user_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['book_title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['report_text'])) ?></td>
                    <td><?= $r['created_at'] ?></td>
                    <td><?= $r['status'] === 'open' ? '<span class="badge badge-primary">Open</span>' : '<span class="badge badge-success">Resolved</span>' ?></td>
                    <td>
                        <?php if ($r['status'] === 'open'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="resolve_id" value="<?= $r['id'] ?>">
                                <input type="text" name="admin_note" placeholder="Note (optional)" style="padding:6px;margin-right:6px;">
                                <button type="submit" class="btn btn-sm btn-accent" onclick="return confirm('Mark as resolved?')">Resolve</button>
                            </form>
                        <?php else: ?>
                            <small style="color:var(--text-muted);">Resolved</small>
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

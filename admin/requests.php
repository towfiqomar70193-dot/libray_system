<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'requests';
$msg = '';
$error = '';

// Approve request: create issue if copies available
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $req_id = (int)$_POST['approve_id'];
    $req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM requests WHERE id=$req_id"));
    if (!$req) {
        $error = 'Request not found.';
    } elseif ($req['status'] !== 'pending') {
        $error = 'Request already processed.';
    } else {
        $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id={$req['book_id']}"));
        if (!$book || $book['available_copies'] < 1) {
            $error = 'No available copies to approve this request.';
        } else {
            // Check if user has overdue issued books
            $overdue_res = mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE user_id={$req['user_id']} AND status='issued' AND due_date < CURDATE()");
            $overdue_count = $overdue_res ? mysqli_fetch_row($overdue_res)[0] : 0;
            if ($overdue_count > 0) {
                $error = 'User has overdue books/unpaid fines. Cannot approve until cleared.';
            } else {
            $issue_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+14 days'));
            mysqli_query($conn, "INSERT INTO issues (user_id, book_id, issue_date, due_date) VALUES ({$req['user_id']}, {$req['book_id']}, '$issue_date', '$due_date')");
            $issue_id = mysqli_insert_id($conn);
            mysqli_query($conn, "UPDATE books SET available_copies = available_copies - 1 WHERE id={$req['book_id']}");
                $admin_id = $_SESSION['user_id'];
                $now = date('Y-m-d H:i:s');
                mysqli_query($conn, "UPDATE requests SET status='approved', admin_id=$admin_id, admin_action_date='$now', issue_id=$issue_id WHERE id=$req_id");
                $msg = 'Request approved and book issued.';
            }
        }
    }
}

// Reject request (POST with note)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    $req_id = (int)$_POST['reject_id'];
    $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
    $req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM requests WHERE id=$req_id"));
    if (!$req) {
        $error = 'Request not found.';
    } elseif ($req['status'] !== 'pending') {
        $error = 'Request already processed.';
    } else {
        $admin_id = $_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE requests SET status='rejected', admin_id=$admin_id, admin_action_date='$now', note='$note' WHERE id=$req_id");
        $msg = 'Request rejected.';
    }
}

// Load requests
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where = $search ? "AND (u.name LIKE '%$search%' OR b.title LIKE '%$search%')" : '';
$requests = mysqli_query($conn, "SELECT r.*, u.name AS user_name, b.title AS book_title, b.author FROM requests r JOIN users u ON r.user_id=u.id JOIN books b ON r.book_id=b.id WHERE 1=1 $where ORDER BY r.request_date DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>User Book Requests</h2>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by user or book..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="requests.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Book</th>
                    <th>Requested On</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($requests) === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><p>No requests found.</p></div></td></tr>
                <?php else: while ($r = mysqli_fetch_assoc($requests)): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['user_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['book_title']) ?><br><small><?= htmlspecialchars($r['author']) ?></small></td>
                    <td><?= $r['request_date'] ?></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <span class="badge badge-primary">Pending</span>
                        <?php elseif ($r['status'] === 'approved'): ?>
                            <span class="badge badge-success">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['note'] ?: '-') ?></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="approve_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-accent" onclick="return confirm('Approve and issue this book?')">Approve</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('reject-form-<?= $r['id'] ?>').style.display='block';">Reject</button>
                            <form method="POST" id="reject-form-<?= $r['id'] ?>" style="display:none;margin-top:6px;">
                                <input type="hidden" name="reject_id" value="<?= $r['id'] ?>">
                                <input type="text" name="note" placeholder="Rejection note (optional)" style="padding:6px;margin-right:6px;">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">Confirm Reject</button>
                            </form>
                        <?php else: ?>
                            <small style="color:var(--text-muted)">Processed</small>
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

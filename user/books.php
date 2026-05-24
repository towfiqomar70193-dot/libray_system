<?php
require_once '../includes/db.php';
start_session_if_needed();
check_user();

$is_admin     = false;
$current_page = 'books';

// Search
$search   = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

$where_parts = [];
if ($search)   $where_parts[] = "(title LIKE '%$search%' OR author LIKE '%$search%')";
if ($category) $where_parts[] = "category = '$category'";
$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : '';

$books = mysqli_query($conn, "SELECT * FROM books $where ORDER BY title ASC");
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM books WHERE category != '' ORDER BY category");

// Handle book request by user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_book_id'])) {
    $book_id = (int)$_POST['request_book_id'];
    $user_id = $_SESSION['user_id'];

    // Block requests if user has overdue issued books
    $overdue_count_res = mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE user_id=$user_id AND status='issued' AND due_date < CURDATE()");
    $overdue_count = $overdue_count_res ? mysqli_fetch_row($overdue_count_res)[0] : 0;
    if ($overdue_count > 0) {
        $req_msg = 'You have overdue books. Please return them and settle any fines before requesting new books.';
    } else {
        // Check if user already has a pending or approved request for this book
        $exists = mysqli_query($conn, "SELECT id, status FROM requests WHERE user_id=$user_id AND book_id=$book_id AND status IN ('pending','approved')");
        if (mysqli_num_rows($exists) > 0) {
            $req_msg = 'You already have an active request for this book.';
        } else {
            mysqli_query($conn, "INSERT INTO requests (user_id, book_id) VALUES ($user_id, $book_id)");
            $req_msg = 'Request submitted. You will be notified when an admin processes it.';
        }
    }
}

// Load user requests to show status
$user_id = $_SESSION['user_id'];
$user_reqs = mysqli_query($conn, "SELECT r.*, b.title, b.author FROM requests r JOIN books b ON r.book_id=b.id WHERE r.user_id=$user_id ORDER BY r.request_date DESC");
$user_reqs_data = [];
$user_request_map = [];
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
while ($rq = mysqli_fetch_assoc($user_reqs)) {
    $user_reqs_data[] = $rq;
    $user_request_map[$rq['book_id']] = $rq;
    if ($rq['status'] === 'pending') {
        $pending_count++;
    } elseif ($rq['status'] === 'approved') {
        $approved_count++;
    } else {
        $rejected_count++;
    }
}

// Handle return from books list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_issue_id'])) {
    $issue_id = (int)$_POST['return_issue_id'];
    $issue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM issues WHERE id=$issue_id AND user_id=$user_id"));
    if (!$issue) {
        $return_msg = 'Issue record not found.';
    } elseif ($issue['status'] !== 'issued') {
        $return_msg = 'This book is not currently issued.';
    } else {
        $fine = calculate_fine($issue['due_date']);
        $return_date = date('Y-m-d');
        mysqli_query($conn, "UPDATE issues SET status='returned', return_date='$return_date', fine=$fine WHERE id=$issue_id");
        mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id={$issue['book_id']}");
        $return_msg = "Book returned successfully. Fine: ৳$fine";
        // reload books result set to reflect updated availability
        $books = mysqli_query($conn, "SELECT * FROM books $where ORDER BY title ASC");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Library</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Browse Books</h2>
        <span style="color:var(--text-muted);font-size:0.88rem;">Check availability before visiting the library</span>
    </div>

    <!-- Search & Filter -->
    <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <input type="text" name="search" placeholder="Search by title or author..." 
               value="<?= htmlspecialchars($search) ?>" 
               style="flex:1;min-width:200px;padding:9px 14px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:0.93rem;outline:none;">
        <select name="category" style="padding:9px 14px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:0.93rem;outline:none;background:#fff;">
            <option value="">All Categories</option>
            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                <option <?= $category === $cat['category'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search || $category): ?><a href="books.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <div class="card" style="margin-bottom:20px;">
        <h3>Your Request Summary</h3>
        <?php if (count($user_reqs_data) === 0): ?>
            <p style="color:var(--text-muted);margin:0">You have not requested any books yet.</p>
        <?php else: ?>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                <div class="badge badge-primary" style="padding:10px 14px;display:inline-flex;align-items:center;gap:6px;">Pending: <?= $pending_count ?></div>
                <div class="badge badge-success" style="padding:10px 14px;display:inline-flex;align-items:center;gap:6px;">Approved: <?= $approved_count ?></div>
                <div class="badge badge-danger" style="padding:10px 14px;display:inline-flex;align-items:center;gap:6px;">Rejected: <?= $rejected_count ?></div>
            </div>
            <p style="margin:0;color:var(--text-muted);font-size:0.95rem;">Approved requests will be processed and reflected in your issued books section. Pending and rejected requests are shown below.</p>
        <?php endif; ?>
    </div>

    <!-- Books Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Total Copies</th>
                    <th>Availability</th>
                    <th>Request Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($books) === 0): ?>
                <tr><td colspan="8"><div class="empty-state"><p>No books found matching your search.</p></div></td></tr>
                <?php else: while ($b = mysqli_fetch_assoc($books)): 
                    $user_request = isset($user_request_map[$b['id']]) ? $user_request_map[$b['id']] : null;
                ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($b['category'] ?: 'N/A') ?></span></td>
                    <td><?= $b['total_copies'] ?></td>
                    <td>
                        <?php if ($b['available_copies'] > 0): ?>
                            <span class="badge badge-success">✓ Available (<?= $b['available_copies'] ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-danger">✗ Not Available</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user_request): ?>
                            <?php if ($user_request['status'] === 'pending'): ?>
                                <span class="badge badge-primary">Pending</span>
                            <?php elseif ($user_request['status'] === 'approved'): ?>
                                <span class="badge badge-success">Approved<?= $user_request['issue_id'] ? ' (Issue #' . $user_request['issue_id'] . ')' : '' ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger">Rejected</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-secondary">None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$user_request || $user_request['status'] === 'rejected'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="request_book_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Request</button>
                            </form>
                        <?php elseif ($user_request['status'] === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-secondary" disabled>Pending</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-success" disabled>Approved</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:20px;background:#f9f6f0;">
        <p style="font-size:0.88rem;color:var(--text-muted);">
            📌 To borrow a book, please visit the library and ask the librarian to issue it to you. 
            Books are issued for <strong>14 days</strong>. Late returns incur a fine of <strong>৳2 per day</strong>.
        </p>
    </div>

    <?php if (isset($req_msg)): ?>
        <div class="alert alert-info" style="margin-top:12px;"><?= htmlspecialchars($req_msg) ?></div>
    <?php endif; ?>
    <?php if (isset($return_msg)): ?>
        <div class="alert alert-success" style="margin-top:12px;"><?= htmlspecialchars($return_msg) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-top:18px;">
        <h3>Your Book Requests</h3>
        <?php if (count($user_reqs_data) === 0): ?>
            <p style="color:var(--text-muted)">You have no book requests.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Book</th>
                            <th>Requested On</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_reqs_data as $rq): ?>
                        <tr>
                            <td><?= $rq['id'] ?></td>
                            <td><strong><?= htmlspecialchars($rq['title']) ?></strong><br><small><?= htmlspecialchars($rq['author']) ?></small></td>
                            <td><?= $rq['request_date'] ?></td>
                            <td>
                                <?php if ($rq['status'] === 'pending'): ?>
                                    <span class="badge badge-primary">Pending</span>
                                <?php elseif ($rq['status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($rq['note'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'issues';
$msg   = '';
$error = '';

// Issue a book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'issue') {
    $user_id    = (int)$_POST['user_id'];
    $book_id    = (int)$_POST['book_id'];
    $issue_date = $_POST['issue_date'];
    $due_date   = $_POST['due_date'];

    // Check available copies
    $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id=$book_id"));
    if (!$book) {
        $error = 'Book not found.';
    } elseif ($book['available_copies'] < 1) {
        $error = 'No available copies of this book.';
    } else {
        // Check if student already has this book
        $already = mysqli_query($conn, "SELECT id FROM issues WHERE user_id=$user_id AND book_id=$book_id AND status='issued'");
        if (mysqli_num_rows($already) > 0) {
            $error = 'This student already has this book issued.';
        } else {
            mysqli_query($conn, "INSERT INTO issues (user_id, book_id, issue_date, due_date) VALUES ($user_id, $book_id, '$issue_date', '$due_date')");
            mysqli_query($conn, "UPDATE books SET available_copies = available_copies - 1 WHERE id=$book_id");
            $msg = 'Book issued successfully.';
        }
    }
}

// Return a book
if (isset($_GET['return'])) {
    $issue_id = (int)$_GET['return'];
    $issue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM issues WHERE id=$issue_id"));
    if ($issue && $issue['status'] === 'issued') {
        $fine = calculate_fine($issue['due_date']);
        $return_date = date('Y-m-d');
        mysqli_query($conn, "UPDATE issues SET status='returned', return_date='$return_date', fine=$fine WHERE id=$issue_id");
        mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id={$issue['book_id']}");
        $msg = "Book returned successfully. Fine: ৳$fine";
    }
}

// Load students and books for form
$students = mysqli_query($conn, "SELECT id, name, class, roll_no FROM users WHERE role='user' ORDER BY name");
$avail_books = mysqli_query($conn, "SELECT * FROM books WHERE available_copies > 0 ORDER BY title");

// All current issues
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where  = $search ? "AND (u.name LIKE '%$search%' OR b.title LIKE '%$search%')" : '';
$issues = mysqli_query($conn, "
    SELECT i.*, u.name AS student_name, u.class, u.roll_no, b.title AS book_title
    FROM issues i
    JOIN users u ON i.user_id = u.id
    JOIN books b ON i.book_id = b.id
    WHERE 1=1 $where
    ORDER BY i.id DESC
");

$action = isset($_GET['action']) ? $_GET['action'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue / Return - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Issue & Return Books</h2>
        <a href="?action=issue" class="btn btn-accent">+ Issue a Book</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Issue Form -->
    <?php if ($action === 'issue'): ?>
    <div class="card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:18px;font-size:1.15rem;">Issue a Book to Student</h3>
        <form method="POST">
            <input type="hidden" name="action" value="issue">
            <div class="form-row">
                <div class="form-group">
                    <label>Student *</label>
                    <select name="user_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($s = mysqli_fetch_assoc($students)): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['name']) ?> 
                                (<?= htmlspecialchars($s['class'] ?: 'N/A') ?> | Roll: <?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Book *</label>
                    <select name="book_id" required>
                        <option value="">-- Select Book --</option>
                        <?php while ($bk = mysqli_fetch_assoc($avail_books)): ?>
                            <option value="<?= $bk['id'] ?>">
                                <?= htmlspecialchars($bk['title']) ?> (<?= $bk['available_copies'] ?> available)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Issue Date *</label>
                    <input type="date" name="issue_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-accent">Issue Book</button>
                <a href="issues.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by student name or book title..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="issues.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <!-- Issues Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine (৳)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($issues) === 0): ?>
                <tr><td colspan="9"><div class="empty-state"><p>No issue records found.</p></div></td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($issues)):
                    $is_overdue = ($row['status'] === 'issued' && $row['due_date'] < date('Y-m-d'));
                    $live_fine  = ($row['status'] === 'issued') ? calculate_fine($row['due_date']) : $row['fine'];
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= htmlspecialchars($row['class'] ?: '') ?> | Roll: <?= htmlspecialchars($row['roll_no'] ?: '-') ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                    <td><?= $row['issue_date'] ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td><?= $row['return_date'] ?: '-' ?></td>
                    <td><?= $live_fine > 0 ? "৳$live_fine" : '-' ?></td>
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
                            <a href="?return=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as returned?')">Return</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);font-size:0.82rem;">Done</span>
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

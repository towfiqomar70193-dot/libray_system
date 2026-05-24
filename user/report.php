<?php
require_once '../includes/db.php';
start_session_if_needed();
check_user();

$user_id = $_SESSION['user_id'];

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : null;
    $book_id  = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $text     = isset($_POST['report_text']) ? mysqli_real_escape_string($conn, trim($_POST['report_text'])) : '';

    if (!$book_id || !$text) {
        $msg = 'Please provide details of the issue.';
    } else {
        mysqli_query($conn, "INSERT INTO book_reports (user_id, issue_id, book_id, report_text) VALUES ($user_id, " . ($issue_id ?: 'NULL') . ", $book_id, '$text')");
        $msg = 'Report submitted. The admin will review it shortly.';
    }
}

// If issue_id passed via GET, prefill
$issue_id = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : null;
$book = null;
if ($issue_id) {
    $issue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM issues WHERE id=$issue_id AND user_id=$user_id"));
    if ($issue) {
        $book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id={$issue['book_id']}"));
    } else {
        $err = 'Issue not found or not owned by you.';
    }
}

// If no book determined, allow selecting from user's issued books
$issued_books = mysqli_query($conn, "SELECT i.id AS issue_id, b.* FROM issues i JOIN books b ON i.book_id=b.id WHERE i.user_id=$user_id AND i.status='issued'");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue - Library</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Report an Issue</h2>
    </div>

    <?php if (isset($msg)): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if (isset($err)): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="issue_id" value="<?= htmlspecialchars($issue_id) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Book *</label>
                    <?php if ($book): ?>
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <div><strong><?= htmlspecialchars($book['title']) ?></strong><br><small><?= htmlspecialchars($book['author']) ?></small></div>
                    <?php else: ?>
                        <select name="book_id" required>
                            <option value="">-- Select Book --</option>
                            <?php while ($b = mysqli_fetch_assoc($issued_books)): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> (Issue #<?= $b['issue_id'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Describe the issue *</label>
                    <textarea name="report_text" rows="5" required placeholder="Describe the problem: damage, missing pages, other..." style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border);"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Submit Report</button>
                <a href="my_issues.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

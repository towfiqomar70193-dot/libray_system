<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'books';
$msg = '';
$error = '';

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $author   = mysqli_real_escape_string($conn, trim($_POST['author']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $copies   = (int)$_POST['copies'];

    if (empty($title) || empty($author)) {
        $error = 'Title and Author are required.';
    } else {
        $sql = "INSERT INTO books (title, author, category, total_copies, available_copies) 
                VALUES ('$title','$author','$category',$copies,$copies)";
        if (mysqli_query($conn, $sql)) {
            $msg = 'Book added successfully.';
        } else {
            $error = 'Failed to add book.';
        }
    }
}

// Handle Edit Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    $id       = (int)$_POST['id'];
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $author   = mysqli_real_escape_string($conn, trim($_POST['author']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $copies   = (int)$_POST['copies'];

    $sql = "UPDATE books SET title='$title', author='$author', category='$category', total_copies=$copies WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        $msg = 'Book updated successfully.';
    } else {
        $error = 'Failed to update book.';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if currently issued
    $check = mysqli_query($conn, "SELECT id FROM issues WHERE book_id=$id AND status='issued'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Cannot delete: this book is currently issued.';
    } else {
        mysqli_query($conn, "DELETE FROM books WHERE id=$id");
        $msg = 'Book deleted.';
    }
}

// Fetch book for edit
$edit_book = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM books WHERE id=$eid");
    $edit_book = mysqli_fetch_assoc($res);
}

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where  = $search ? "WHERE title LIKE '%$search%' OR author LIKE '%$search%' OR category LIKE '%$search%'" : '';
$books  = mysqli_query($conn, "SELECT * FROM books $where ORDER BY title ASC");

$action = isset($_GET['action']) ? $_GET['action'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Book Management</h2>
        <a href="?action=add" class="btn btn-primary">+ Add New Book</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Add/Edit Form -->
    <?php if ($action === 'add' || $edit_book): ?>
    <div class="card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:18px;font-size:1.15rem;"><?= $edit_book ? 'Edit Book' : 'Add New Book' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $edit_book ? 'edit' : 'add' ?>">
            <?php if ($edit_book): ?>
                <input type="hidden" name="id" value="<?= $edit_book['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Book Title *</label>
                    <input type="text" name="title" required value="<?= $edit_book ? htmlspecialchars($edit_book['title']) : '' ?>" placeholder="Enter book title">
                </div>
                <div class="form-group">
                    <label>Author *</label>
                    <input type="text" name="author" required value="<?= $edit_book ? htmlspecialchars($edit_book['author']) : '' ?>" placeholder="Author name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">-- Select --</option>
                        <?php
                        $cats = ['Academic','Reference','Story','General Knowledge','Exam Preparation','Others'];
                        foreach ($cats as $cat) {
                            $sel = ($edit_book && $edit_book['category'] === $cat) ? 'selected' : '';
                            echo "<option $sel>$cat</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Number of Copies</label>
                    <input type="number" name="copies" min="1" value="<?= $edit_book ? $edit_book['total_copies'] : 1 ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><?= $edit_book ? 'Update Book' : 'Add Book' ?></button>
                <a href="books.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by title, author or category..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="books.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <!-- Books Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Total</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($books) === 0): ?>
                <tr><td colspan="7"><div class="empty-state"><p>No books found.</p></div></td></tr>
                <?php else: while ($b = mysqli_fetch_assoc($books)): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($b['category'] ?: 'N/A') ?></span></td>
                    <td><?= $b['total_copies'] ?></td>
                    <td>
                        <?php if ($b['available_copies'] > 0): ?>
                            <span class="badge badge-success"><?= $b['available_copies'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger">0</span>
                        <?php endif; ?>
                    </td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="?edit=<?= $b['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

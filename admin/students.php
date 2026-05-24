<?php
require_once '../includes/db.php';
start_session_if_needed();
check_admin();

$is_admin     = true;
$current_page = 'students';
$msg   = '';
$error = '';

// Delete student
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = mysqli_query($conn, "SELECT id FROM issues WHERE user_id=$id AND status='issued'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Cannot delete: student has books currently issued.';
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='user'");
        $msg = 'Student deleted.';
    }
}

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where  = $search ? "AND (name LIKE '%$search%' OR email LIKE '%$search%' OR roll_no LIKE '%$search%' OR class LIKE '%$search%')" : '';
$students = mysqli_query($conn, "SELECT u.*, 
    (SELECT COUNT(*) FROM issues WHERE user_id=u.id AND status='issued') AS active_issues
    FROM users u WHERE role='user' $where ORDER BY u.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>Registered Students</h2>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name, email, class or roll no..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="students.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Roll No.</th>
                    <th>Active Issues</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($students) === 0): ?>
                <tr><td colspan="8"><div class="empty-state"><p>No students found.</p></div></td></tr>
                <?php else: while ($s = mysqli_fetch_assoc($students)): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['class'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($s['roll_no'] ?: '-') ?></td>
                    <td>
                        <?php if ($s['active_issues'] > 0): ?>
                            <span class="badge badge-warning"><?= $s['active_issues'] ?> book(s)</span>
                        <?php else: ?>
                            <span class="badge badge-success">None</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                    <td>
                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this student?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

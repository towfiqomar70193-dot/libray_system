<?php
// This file builds the navbar. 
// $is_admin = true for admin nav, false for user nav
// $current_page = page name string for active state
?>
<nav class="navbar">
    <div class="brand">
        📚 <span>Library</span> System
    </div>
    <nav>
        <?php if (isset($is_admin) && $is_admin): ?>
            <a href="dashboard.php" class="<?= ($current_page==='dashboard') ? 'active' : '' ?>">Dashboard</a>
            <a href="books.php"     class="<?= ($current_page==='books')     ? 'active' : '' ?>">Books</a>
            <a href="students.php"  class="<?= ($current_page==='students')  ? 'active' : '' ?>">Students</a>
            <a href="issues.php"    class="<?= ($current_page==='issues')    ? 'active' : '' ?>">Issues</a>
            <a href="requests.php"   class="<?= ($current_page==='requests')   ? 'active' : '' ?>">Requests</a>
            <a href="book_reports.php" class="<?= ($current_page==='book_reports') ? 'active' : '' ?>">Book Reports</a>
            <a href="overdue.php"   class="<?= ($current_page==='overdue')   ? 'active' : '' ?>">Overdue</a>
            <a href="reports.php"   class="<?= ($current_page==='reports')   ? 'active' : '' ?>">Reports</a>
        <?php else: ?>
            <a href="dashboard.php" class="<?= ($current_page==='dashboard') ? 'active' : '' ?>">Home</a>
            <a href="books.php"     class="<?= ($current_page==='books')     ? 'active' : '' ?>">Browse Books</a>
            <a href="my_issues.php" class="<?= ($current_page==='my_issues') ? 'active' : '' ?>">My Books</a>
        <?php endif; ?>
    </nav>
    <div class="user-info">
        👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

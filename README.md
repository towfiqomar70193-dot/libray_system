# Library System

A simple PHP library management system built for XAMPP.

## Features

- User book browsing with search and category filters
- User book request submission and request status tracking
- Admin approval and rejection workflow for requests
- Issue tracking for borrowed books, returns, and overdue handling
- Separate user and admin interfaces

## Requirements

- PHP 7.4+ (or compatible)
- MySQL / MariaDB
- XAMPP (recommended for local development)

## Installation

1. Place the `library_system` folder inside your web server root (for example, `C:\xampp\htdocs\`).
2. Start Apache and MySQL in XAMPP.
3. Import `database.sql` into your MySQL server using phpMyAdmin or a command-line client.
4. Update database connection settings in `includes/db.php` if needed.
5. Open your browser and go to `http://localhost/library_system/user/books.php` for the user interface or `http://localhost/library_system/admin/dashboard.php` for admin.

## Project Structure

- `admin/` - Admin dashboard and request management pages
- `user/` - User-facing book browsing and request pages
- `includes/` - Shared database and navigation includes
- `assets/css/` - Stylesheet for the app
- `database.sql` - Database schema and initial data
- `index.php` - Entry point for the application

## Notes

- The application assumes session-based authentication from `includes/db.php`.
- Approved requests are issued as library book loans by the admin.
- Returned books update availability counts automatically.

## License

This project is provided as-is for learning and development purposes.
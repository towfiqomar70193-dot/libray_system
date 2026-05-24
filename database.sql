-- Library Management System Database
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Users table (students/members)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    class VARCHAR(50),
    roll_no VARCHAR(50),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Book issues table
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    fine DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('issued', 'returned') DEFAULT 'issued',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Requests table: users can request books which admins can approve/reject
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin_id INT DEFAULT NULL,
    admin_action_date DATETIME DEFAULT NULL,
    issue_id INT DEFAULT NULL,
    note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Reports submitted by users about books/issues (damage, lost, other problems)
CREATE TABLE IF NOT EXISTS book_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT DEFAULT NULL,
    book_id INT NOT NULL,
    report_text TEXT NOT NULL,
    status ENUM('open','resolved') DEFAULT 'open',
    admin_id INT DEFAULT NULL,
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Default admin account (password: admin123)
-- Insert only if an account with this email does not already exist to avoid duplicate key errors
INSERT INTO users (name, email, password, role)
SELECT 'Admin', 'admin@library.com', MD5('admin123'), 'admin'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@library.com');

-- Sample books
INSERT INTO books (title, author, category, total_copies, available_copies) VALUES
('Introduction to Computer Science', 'Forouzan', 'Academic', 3, 3),
('Mathematics for Class 10', 'R.D. Sharma', 'Academic', 5, 5),
('English Grammar', 'Wren & Martin', 'Reference', 4, 4),
('The Alchemist', 'Paulo Coelho', 'Story', 2, 2),
('General Knowledge 2024', 'Manohar Pandey', 'General Knowledge', 3, 3),
('Physics Textbook Class 9', 'H.C. Verma', 'Academic', 4, 4),
('Bangladesh History', 'Various Authors', 'Reference', 2, 2),
('Chemistry Lab Manual', 'NCTB', 'Academic', 3, 3);

# Northstar Fees — Student Fees Management System

A responsive student-fees portal built with PHP 8+, MySQL, PDO, and Bootstrap 5. It supports administrator and student accounts, course/semester setup, fee records, partial payment requests, administrator verification, payment history, and printable PDF receipts.

## Requirements

- XAMPP with PHP 8.0+ and MySQL/MariaDB
- PHP extension `pdo_mysql` enabled
- A modern browser and an internet connection for Bootstrap and Google Fonts CDN assets

## XAMPP installation

1. Copy the project folder into `C:\xampp\htdocs\StudentFeesManagement`.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Visit `http://localhost/phpmyadmin`, select **Import**, choose this project's `database.sql`, and import it. The script creates the `student_fees_management` database, tables, and sample courses/semesters.
4. If your local MySQL credentials are not the XAMPP defaults (`root` with an empty password), update `app/config.php` or set `DB_DSN`, `DB_USER`, and `DB_PASSWORD` in the PHP environment.
5. Open `http://localhost/StudentFeesManagement/index.php?page=setup` and create the first administrator. Use a unique email and a password of at least 12 characters. The setup page locks as soon as an administrator exists.
6. Sign in at `http://localhost/StudentFeesManagement/index.php?page=login`. The sample BCA and BBA courses and semesters are ready; manage these from **Courses**.

## Payment workflow

Students submit full or partial payment requests and may add a transaction reference. A request does not count as paid until an administrator verifies it under **Payments**. The verified amount updates the balance; the student and administrators can then open the receipt and use **Print / save as PDF** in the browser's print dialog. This project does not connect to a bank or card processor.

## Security and operations

- Passwords use PHP's `password_hash` / `password_verify`; database access uses PDO prepared statements.
- POST actions require a session CSRF token. Sessions use HTTP-only, SameSite cookies and regenerate their ID after sign-in.
- Student records are archived instead of physically deleted, preserving payment and receipt history.
- Fee amounts are copied into a student's fee record at enrollment. Editing the standard course/semester fee affects new enrollments only.
- For deployment beyond a local XAMPP development setup, use HTTPS, configure a least-privilege database user, keep PHP and MySQL patched, and restrict administrator creation to trusted operators.

## Project layout

```text
StudentFeesManagement/
├── actions.php
├── app/
│   ├── bootstrap.php
│   └── config.php
├── assets/
│   ├── css/app.css
│   └── js/app.js
├── database.sql
├── index.php
└── views/
    ├── dashboard.php
    ├── students.php
    ├── courses.php
    ├── payments.php
    ├── student.php
    └── receipt.php
```

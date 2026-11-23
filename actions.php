<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
require_csrf();
$action = post_string('action');
$user = current_user();

try {
    switch ($action) {
        case 'login':
            $email = strtolower(post_string('email'));
            $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $account = $stmt->fetch();
            if (!$account || !password_verify((string)($_POST['password'] ?? ''), $account['password_hash'])) {
                flash('danger', 'The email or password you entered is incorrect.');
                redirect('index.php?page=login');
            }
            session_regenerate_id(true);
            unset($account['password_hash']);
            $_SESSION['user'] = $account;
            redirect($account['role'] === 'admin' ? 'index.php?page=dashboard' : 'index.php?page=student');

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'You have been securely signed out.');
            redirect('index.php');

        case 'admin_setup':
            $name = post_string('name');
            $email = strtolower(post_string('email'));
            $password = (string)($_POST['password'] ?? '');
            $adminCount = (int)db()->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
            if ($adminCount > 0) {
                flash('danger', 'An administrator already exists. Sign in instead.');
                redirect('index.php?page=login');
            }
            if ($name === '' || !valid_email($email) || strlen($password) < 12) {
                flash('danger', 'Enter a name, valid email, and a password of at least 12 characters.');
                redirect('index.php?page=setup');
            }
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Administrator created. Sign in with your new account.');
            redirect('index.php?page=login');

        case 'register':
        case 'admin_create_student':
            $isAdmin = $action === 'admin_create_student';
            if ($isAdmin) {
                require_admin();
            }
            $name = post_string('name');
            $email = strtolower(post_string('email'));
            $phone = post_string('phone');
            $roll = post_string('roll_number');
            $password = (string)($_POST['password'] ?? '');
            $semesterId = filter_var($_POST['course_semester_id'] ?? null, FILTER_VALIDATE_INT);
            if ($name === '' || !valid_email($email) || $roll === '' || !$semesterId || strlen($password) < 8) {
                flash('danger', 'Enter a name, valid email, roll number, valid course and semester, and a password of at least 8 characters.');
                redirect($isAdmin ? 'index.php?page=students' : 'index.php?page=register');
            }
            $sem = db()->prepare('SELECT cs.id, cs.fee_amount, cs.semester_name, c.name AS course_name FROM course_semesters cs JOIN courses c ON c.id = cs.course_id WHERE cs.id = ? AND cs.is_active = 1 AND c.is_active = 1');
            $sem->execute([$semesterId]);
            $courseSemester = $sem->fetch();
            if (!$courseSemester) {
                throw new RuntimeException('Select an active course and semester.');
            }
            db()->beginTransaction();
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "student")');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int)db()->lastInsertId();
            $stmt = db()->prepare('INSERT INTO students (user_id, roll_number, phone, course_semester_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $roll, $phone, $semesterId]);
            $stmt = db()->prepare('INSERT INTO student_fees (user_id, course_semester_id, course_name, semester_name, amount) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $semesterId, $courseSemester['course_name'], $courseSemester['semester_name'], $courseSemester['fee_amount']]);
            db()->commit();
            flash('success', $isAdmin ? 'Student record added.' : 'Registration complete. You can now sign in.');
            redirect($isAdmin ? 'index.php?page=students' : 'index.php?page=login');

        case 'admin_update_student':
            require_admin();
            $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            $semesterId = filter_var($_POST['course_semester_id'] ?? null, FILTER_VALIDATE_INT);
            $name = post_string('name');
            $email = strtolower(post_string('email'));
            $phone = post_string('phone');
            $roll = post_string('roll_number');
            $password = (string)($_POST['password'] ?? '');
            if (!$userId || !$semesterId || $name === '' || !valid_email($email) || $roll === '' || ($password !== '' && strlen($password) < 8)) {
                throw new RuntimeException('Check the student details and try again. Passwords must contain at least 8 characters.');
            }
            $sem = db()->prepare('SELECT cs.id, cs.fee_amount, cs.semester_name, c.name AS course_name FROM course_semesters cs JOIN courses c ON c.id = cs.course_id WHERE cs.id = ? AND cs.is_active = 1 AND c.is_active = 1');
            $sem->execute([$semesterId]);
            $courseSemester = $sem->fetch();
            if (!$courseSemester) {
                throw new RuntimeException('Select an active course and semester.');
            }
            $stmt = db()->prepare('SELECT id FROM users WHERE id = ? AND role = "student" AND is_active = 1');
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('That active student record could not be found.');
            }
            db()->beginTransaction();
            $stmt = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = "student"');
            $stmt->execute([$name, $email, $userId]);
            $stmt = db()->prepare('UPDATE students SET roll_number = ?, phone = ? WHERE user_id = ?');
            $stmt->execute([$roll, $phone, $userId]);
            if ($password !== '') {
                $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
            }
            $stmt = db()->prepare('SELECT id, course_semester_id FROM student_fees WHERE user_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute([$userId]);
            $currentFee = $stmt->fetch();
            if (!$currentFee || (int)$currentFee['course_semester_id'] !== $semesterId) {
                $stmt = db()->prepare('INSERT INTO student_fees (user_id, course_semester_id, course_name, semester_name, amount) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $semesterId, $courseSemester['course_name'], $courseSemester['semester_name'], $courseSemester['fee_amount']]);
            }
            db()->commit();
            flash('success', 'Student details updated.');
            redirect('index.php?page=students');

        case 'admin_archive_student':
            require_admin();
            $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$userId) {
                throw new RuntimeException('Select a valid student account.');
            }
            $stmt = db()->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "student"');
            $stmt->execute([$userId]);
            flash('success', 'Student account archived. Payment records have been retained.');
            redirect('index.php?page=students');

        case 'admin_restore_student':
            require_admin();
            $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = db()->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role = "student"');
            $stmt->execute([$userId]);
            flash('success', 'Student account restored.');
            redirect('index.php?page=students&archived=1');

        case 'course_save':
            require_admin();
            $courseId = filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT);
            $name = post_string('name');
            $code = strtoupper(post_string('code'));
            if ($name === '' || $code === '') {
                throw new RuntimeException('Course name and code are required.');
            }
            if ($courseId) {
                $stmt = db()->prepare('UPDATE courses SET name = ?, code = ? WHERE id = ?');
                $stmt->execute([$name, $code, $courseId]);
            } else {
                $stmt = db()->prepare('INSERT INTO courses (name, code) VALUES (?, ?)');
                $stmt->execute([$name, $code]);
            }
            flash('success', 'Course saved.');
            redirect('index.php?page=courses');

        case 'course_toggle':
            require_admin();
            $stmt = db()->prepare('UPDATE courses SET is_active = 1 - is_active WHERE id = ?');
            $stmt->execute([filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT)]);
            flash('success', 'Course availability updated.');
            redirect('index.php?page=courses');

        case 'semester_save':
            require_admin();
            $semesterId = filter_var($_POST['semester_id'] ?? null, FILTER_VALIDATE_INT);
            $courseId = filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT);
            $semesterName = post_string('semester_name');
            $feeAmount = filter_var($_POST['fee_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$courseId || $semesterName === '' || $feeAmount === false || $feeAmount <= 0) {
                throw new RuntimeException('Enter a semester name and a fee greater than zero.');
            }
            if ($semesterId) {
                $stmt = db()->prepare('UPDATE course_semesters SET course_id = ?, semester_name = ?, fee_amount = ? WHERE id = ?');
                $stmt->execute([$courseId, $semesterName, $feeAmount, $semesterId]);
            } else {
                $stmt = db()->prepare('INSERT INTO course_semesters (course_id, semester_name, fee_amount) VALUES (?, ?, ?)');
                $stmt->execute([$courseId, $semesterName, $feeAmount]);
            }
            flash('success', 'Semester and fee saved. Existing student fees remain unchanged.');
            redirect('index.php?page=courses');

        case 'semester_toggle':
            require_admin();
            $stmt = db()->prepare('UPDATE course_semesters SET is_active = 1 - is_active WHERE id = ?');
            $stmt->execute([filter_var($_POST['semester_id'] ?? null, FILTER_VALIDATE_INT)]);
            flash('success', 'Semester availability updated.');
            redirect('index.php?page=courses');

        case 'payment_submit':
            $student = require_login();
            if ($student['role'] !== 'student') {
                throw new RuntimeException('Only students can submit a payment request.');
            }
            $feeId = filter_var($_POST['fee_id'] ?? null, FILTER_VALIDATE_INT);
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $method = post_string('payment_method');
            $reference = post_string('reference');
            if (!$feeId || $amount === false || $amount <= 0 || !in_array($method, ['bank_transfer', 'cash', 'other'], true)) {
                throw new RuntimeException('Enter a valid payment amount and method.');
            }
            $stmt = db()->prepare('SELECT id FROM student_fees WHERE id = ? AND user_id = ?');
            $stmt->execute([$feeId, $student['id']]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('That fee record does not belong to your account.');
            }
            db()->beginTransaction();
            $lock = db()->prepare('SELECT id FROM student_fees WHERE id = ? FOR UPDATE');
            $lock->execute([$feeId]);
            $fee = fee_balance($feeId);
            $stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE student_fee_id = ? AND status = "pending"');
            $stmt->execute([$feeId]);
            $pending = (float)$stmt->fetchColumn();
            if ($amount > (float)$fee['balance'] - $pending + 0.00001) {
                throw new RuntimeException('The amount exceeds the remaining balance available for a payment request.');
            }
            $receipt = 'SFM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
            $stmt = db()->prepare('INSERT INTO payments (student_fee_id, amount, payment_method, reference, receipt_number, status) VALUES (?, ?, ?, ?, ?, "pending")');
            $stmt->execute([$feeId, $amount, $method, $reference !== '' ? $reference : null, $receipt]);
            db()->commit();
            flash('success', 'Payment request submitted. The balance changes after an administrator verifies it.');
            redirect('index.php?page=student');

        case 'payment_review':
            require_admin();
            $paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
            $decision = post_string('decision');
            if (!$paymentId || !in_array($decision, ['paid', 'rejected'], true)) {
                throw new RuntimeException('Invalid payment review request.');
            }
            db()->beginTransaction();
            $stmt = db()->prepare('SELECT p.*, f.user_id FROM payments p JOIN student_fees f ON f.id = p.student_fee_id WHERE p.id = ? FOR UPDATE');
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            if (!$payment || $payment['status'] !== 'pending') {
                throw new RuntimeException('This payment request has already been reviewed.');
            }
            if ($decision === 'paid') {
                $lock = db()->prepare('SELECT id FROM student_fees WHERE id = ? FOR UPDATE');
                $lock->execute([$payment['student_fee_id']]);
                $fee = fee_balance((int)$payment['student_fee_id']);
                if ((float)$payment['amount'] > (float)$fee['balance'] + 0.00001) {
                    throw new RuntimeException('Approving this payment would exceed the fee amount. Review the fee record first.');
                }
            }
            $stmt = db()->prepare('UPDATE payments SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
            $stmt->execute([$decision, $user['id'], $paymentId]);
            db()->commit();
            flash('success', $decision === 'paid' ? 'Payment approved and added to the student balance.' : 'Payment request rejected.');
            redirect('index.php?page=payments');

        default:
            http_response_code(400);
            exit('Unknown action.');
    }
} catch (PDOException $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    $message = $exception->getCode() === '23000'
        ? 'That email, course code, roll number, or semester is already in use.'
        : 'The request could not be completed. Check the details and try again.';
    flash('danger', $message);
    $fallback = current_user() ? (current_user()['role'] === 'admin' ? 'index.php?page=students' : 'index.php?page=student') : 'index.php?page=register';
    redirect($fallback);
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('danger', $exception->getMessage());
    $fallback = current_user() ? (current_user()['role'] === 'admin' ? 'index.php?page=payments' : 'index.php?page=student') : 'index.php?page=register';
    redirect($fallback);
}

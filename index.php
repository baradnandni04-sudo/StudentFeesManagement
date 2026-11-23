<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$page = (string)($_GET['page'] ?? 'home');
$user = current_user();

if ($page === 'home' && $user) {
    redirect($user['role'] === 'admin' ? 'index.php?page=dashboard' : 'index.php?page=student');
}

$publicPages = ['home', 'login', 'register', 'setup'];
$adminPages = ['dashboard', 'students', 'courses', 'payments'];
$studentPages = ['student', 'receipt'];

if (!in_array($page, array_merge($publicPages, $adminPages, $studentPages), true)) {
    http_response_code(404);
    $page = '404';
}
if (in_array($page, $adminPages, true)) {
    require_admin();
}
if (in_array($page, $studentPages, true)) {
    require_login();
    if ($page === 'student' && current_user()['role'] !== 'student') {
        redirect('index.php?page=dashboard');
    }
}

$titleMap = [
    'home' => 'Welcome', 'login' => 'Sign in', 'register' => 'Student registration',
    'setup' => 'Create administrator', 'dashboard' => 'Overview', 'students' => 'Students',
    'courses' => 'Courses & semesters', 'payments' => 'Payments', 'student' => 'My account',
    'receipt' => 'Payment receipt', '404' => 'Page not found',
];
$title = $titleMap[$page] ?? 'Fees portal';
$activePage = $page;
require __DIR__ . '/views/header.php';
require __DIR__ . '/views/' . $page . '.php';
require __DIR__ . '/views/footer.php';

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title><?= e($title) ?> · Northstar Fees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg app-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="brand-mark">N</span><span>northstar<span class="brand-light">.fees</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (current_user()): ?>
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <?php if (current_user()['role'] === 'admin'): ?>
                        <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">Overview</a>
                        <a class="nav-link <?= $activePage === 'students' ? 'active' : '' ?>" href="index.php?page=students">Students</a>
                        <a class="nav-link <?= $activePage === 'courses' ? 'active' : '' ?>" href="index.php?page=courses">Courses</a>
                        <a class="nav-link <?= $activePage === 'payments' ? 'active' : '' ?>" href="index.php?page=payments">Payments</a>
                    <?php else: ?>
                        <a class="nav-link <?= $activePage === 'student' ? 'active' : '' ?>" href="index.php?page=student">My account</a>
                    <?php endif; ?>
                    <span class="user-chip ms-lg-3"><?= e(current_user()['name']) ?></span>
                    <form action="actions.php" method="post" class="ms-lg-2">
                        <?= csrf_field() ?><input type="hidden" name="action" value="logout">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Sign out</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <a class="nav-link" href="index.php?page=login">Sign in</a>
                    <a class="btn btn-sm btn-primary px-3" href="index.php?page=register">Student registration</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container app-container">
<?php if ($flash = take_flash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mt-3" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

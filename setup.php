<?php $hasAdmin = (int)db()->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn() > 0; ?>
<section class="auth-wrap">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-dot"></span> Initial setup</div>
        <h1 class="h2 mt-3">Create administrator</h1>
        <?php if ($hasAdmin): ?>
            <p class="text-secondary">An administrator account already exists. This setup page is locked.</p>
            <a class="btn btn-primary" href="index.php?page=login">Go to sign in</a>
        <?php else: ?>
            <p class="text-secondary mb-4">Create the first administrator account. Use a unique email and a strong password.</p>
            <form action="actions.php" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="admin_setup">
                <div class="mb-3"><label class="form-label" for="name">Administrator name</label><input class="form-control" id="name" name="name" maxlength="120" required></div>
                <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" required></div>
                <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" minlength="12" required><div class="form-text">At least 12 characters.</div></div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Create administrator</button>
            </form>
        <?php endif; ?>
    </div>
</section>

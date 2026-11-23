<section class="auth-wrap">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-dot"></span> Welcome back</div>
        <h1 class="h2 mt-3">Sign in to your account</h1>
        <p class="text-secondary mb-4">Manage your student fees or administer your campus.</p>
        <form action="actions.php" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="login">
            <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control form-control-lg" id="email" name="email" type="email" autocomplete="username" required autofocus></div>
            <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="btn btn-primary btn-lg w-100" type="submit">Sign in securely</button>
        </form>
        <p class="text-center text-secondary mt-4 mb-0">New student? <a href="index.php?page=register">Create an account</a></p>
    </div>
    <p class="auth-footnote">Your account is protected with encrypted password storage.</p>
</section>

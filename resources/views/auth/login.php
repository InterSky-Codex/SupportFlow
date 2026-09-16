<section class="auth-card" aria-labelledby="login-title">
    <div class="auth-heading">
        <span class="section-label">Welcome back</span>
        <h1 id="login-title">Sign in to your workspace</h1>
        <p>Manage support requests with clarity and confidence.</p>
    </div>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success" role="status"><?= e($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <form action="/login" method="post" class="auth-form">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div>
            <label class="form-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="email" required autofocus>
        </div>
        <div>
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Sign in <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
    </form>

    <p class="auth-switch">New to SupportFlow? <a href="/register">Create an account</a></p>
</section>

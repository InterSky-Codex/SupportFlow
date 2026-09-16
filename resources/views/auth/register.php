<section class="auth-card" aria-labelledby="register-title">
    <div class="auth-heading">
        <span class="section-label">Get started</span>
        <h1 id="register-title">Create your account</h1>
        <p>Set up secure access to your support workspace.</p>
    </div>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <form action="/register" method="post" class="auth-form">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div>
            <label class="form-label" for="name">Full name</label>
            <input class="form-control" id="name" name="name" type="text" autocomplete="name" minlength="2" maxlength="100" required autofocus>
        </div>
        <div>
            <label class="form-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
        </div>
        <div>
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            <div class="form-text">Use at least 8 characters.</div>
        </div>
        <div>
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Create account <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
    </form>

    <p class="auth-switch">Already have an account? <a href="/login">Sign in</a></p>
</section>

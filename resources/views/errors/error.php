<main class="text-center">
    <p class="error-code"><?= e($statusCode ?? 500) ?></p>
    <h1 class="h3 mb-3"><?= e($title ?? 'Something went wrong') ?></h1>
    <p class="text-secondary mb-4">The page you requested is unavailable. Please return to the workspace.</p>
    <a class="btn btn-primary" href="/">Return to SupportFlow</a>
</main>

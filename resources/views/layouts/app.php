<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SupportFlow helpdesk ticket management system">
    <title><?= e(($pageTitle ?? 'Support desk') . ' · ' . $appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/../components/sidebar.php'; ?>

        <div class="app-main">
            <?php require __DIR__ . '/../components/navbar.php'; ?>

            <main class="app-content" id="main-content">
                <?= $content ?>
            </main>

            <?php require __DIR__ . '/../components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <?php if (($activeNavigation ?? '') === 'reports'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script src="/assets/js/reports.js"></script>
    <?php endif; ?>
</body>
</html>

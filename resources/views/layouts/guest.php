<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SupportFlow secure account access">
    <title><?= e(($pageTitle ?? 'Account access') . ' · ' . $appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="guest-page">
    <main class="guest-shell">
        <a class="brand guest-brand" href="/login" aria-label="SupportFlow home">
            <span class="brand-mark"><i class="bi bi-life-preserver" aria-hidden="true"></i></span>
            <span>SupportFlow</span>
        </a>
        <?= $content ?>
    </main>
</body>
</html>

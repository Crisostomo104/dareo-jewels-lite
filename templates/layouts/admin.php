<?php
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
$__base = base_path();
if ($__base !== '' && str_starts_with($__path, $__base)) {
    $__path = substr($__path, strlen($__base));
}
$__path = '/' . trim($__path, '/');
$__section = explode('/', trim($__path, '/'))[1] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> · <?= e(Database::setting('business_name', 'DaREO Jewels')) ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('assets/logo/favicon.png').'?v=1') ?>">
    <link rel="apple-touch-icon" href="<?= e(url('assets/logo/apple-touch-icon.png').'?v=1') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css').'?v=46') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/powered-by.css')) ?>">
</head>
<body class="admin-site">
<a class="skip-link" href="#main">Skip to content</a>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('admin')) ?>" aria-label="Admin home">
            <img class="brand-logo" src="<?= e(url('assets/logo/dareo-logo-wo-tagline.png')) ?>" alt="<?= e(Database::setting('business_name', 'DaREO Jewels')) ?>">
        </a>
        <nav aria-label="Admin navigation">
            <a href="<?= e(url('admin')) ?>" class="<?= $__section === '' || $__path === '/admin' ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= e(url('admin/products')) ?>" class="<?= $__section === 'products' ? 'active' : '' ?>">Products</a>
            <a href="<?= e(url('admin/sales')) ?>" class="<?= $__section === 'sales' ? 'active' : '' ?>">Sales</a>
            <a href="<?= e(url('admin/expenses')) ?>" class="<?= $__section === 'expenses' ? 'active' : '' ?>">Expenses</a>
            <a href="<?= e(url('admin/reports')) ?>" class="<?= $__section === 'reports' ? 'active' : '' ?>">Reports</a>
            <a href="<?= e(url('admin/calculator')) ?>" class="<?= $__section === 'calculator' ? 'active' : '' ?>">Pricing calculator</a>
            <a href="<?= e(url('admin/settings')) ?>" class="<?= $__section === 'settings' ? 'active' : '' ?>">Settings</a>
            <a href="<?= e(url('admin/account')) ?>" class="<?= $__section === 'account' ? 'active' : '' ?>">My account</a>
            <a href="<?= e(url()) ?>" target="_blank" rel="noopener noreferrer">View gallery ↗</a>
        </nav>
        <div class="sidebar-footer">
            <form method="post" action="<?= e(url('admin/logout')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="topbar">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation">☰</button>
            <div class="topbar-clock"><time id="admin-clock" datetime="<?= e(date('Y-m-d\TH:i:sP')) ?>"><?= e(date('D, M j, Y · g:i A')) ?></time></div>
        </header>
        <main id="main" class="content">
            <?php foreach (take_flash() as $notice): ?>
                <div class="flash <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(url('assets/js/app.js').'?v=46') ?>"></script>
</body>
</html>

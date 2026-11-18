<?php
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__base = base_path();
if ($__base !== '' && str_starts_with($__path, $__base)) {
    $__path = substr($__path, strlen($__base));
}
$__path = '/' . trim($__path, '/');
$__active = $__path === '/' ? 'home'
    : ($__path === '/about' ? 'about'
    : ($__path === '/contact' ? 'contact'
    : ((($__path === '/collection') || preg_match('#^/p/\d+$#D', $__path)) ? 'collection' : '')));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(Database::setting('business_name', 'DaREO Jewels')) ?> · Jewelry Collection</title>
    <link rel="icon" type="image/png" href="<?= e(url('assets/logo/favicon.png').'?v=1') ?>">
    <link rel="apple-touch-icon" href="<?= e(url('assets/logo/apple-touch-icon.png').'?v=1') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css').'?v=46') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/powered-by.css')) ?>">
</head>
<body class="public-site">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <a class="brand" href="<?= e(url()) ?>" data-admin-shortcut data-admin-url="<?= e(url('admin')) ?>" aria-label="<?= e(Database::setting('business_name', 'DaREO Jewels')) ?> home">
        <img class="brand-logo" src="<?= e(url('assets/logo/dareo-logo-wo-tagline.png')) ?>" alt="<?= e(Database::setting('business_name', 'DaREO Jewels')) ?>">
    </a>
    <nav id="public-nav" class="public-nav" aria-label="Main navigation">
        <a href="<?= e(url()) ?>" class="<?= $__active === 'home' ? 'active' : '' ?>" <?= $__active === 'home' ? 'aria-current="page"' : '' ?> data-en="Home" data-tl="Home">Home</a>
        <a href="<?= e(url('collection')) ?>" class="<?= $__active === 'collection' ? 'active' : '' ?>" <?= $__active === 'collection' ? 'aria-current="page"' : '' ?> data-en="Collection" data-tl="Koleksyon">Collection</a>
        <a href="<?= e(url('about')) ?>" class="<?= $__active === 'about' ? 'active' : '' ?>" <?= $__active === 'about' ? 'aria-current="page"' : '' ?> data-en="About" data-tl="Tungkol">About</a>
        <a href="<?= e(url('contact')) ?>" class="<?= $__active === 'contact' ? 'active' : '' ?>" <?= $__active === 'contact' ? 'aria-current="page"' : '' ?> data-en="Contact" data-tl="Makipag-ugnayan">Contact</a>
    </nav>
    <div class="header-actions">
        <button class="lang-button" type="button" data-lang-toggle aria-label="Switch language">Tagalog</button>
        <button class="hamburger" type="button" data-menu-toggle aria-expanded="false" aria-controls="public-nav" aria-label="Open navigation menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
<main id="main">
<?php foreach (take_flash() as $notice): ?>
    <div class="flash <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endforeach; ?>
<?= $content ?>
</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> <?= e(Database::setting('business_name', 'DaREO Jewels')) ?></p>
    <?php require dirname(__DIR__) . '/components/powered-by.php'; ?>
</footer>
<script src="<?= e(url('assets/js/app.js').'?v=46') ?>"></script>
<script src="<?= e(url('assets/js/powered-by.js')) ?>"></script>
</body>
</html>

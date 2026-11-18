<div class="auth-card">
    <p class="eyebrow">ADMIN ACCESS</p>
    <h1>Sign in</h1>
    <?php foreach (take_flash() as $notice): ?>
        <div class="flash <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
    <?php endforeach; ?>
    <?php if (!Auth::hasUsers()): ?>
        <p class="muted">No admin account yet. <a href="<?= e(url('setup')) ?>">Create one here.</a></p>
    <?php endif; ?>
    <form method="post" action="<?= e(url('admin/login')) ?>">
        <?= csrf_field() ?>
        <label>Username<input name="username" value="<?= e(old('username')) ?>" autocomplete="off" required></label>
        <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
        <?php if (Auth::turnstileEnabled()): ?>
            <div class="captcha-wrap">
                <div class="cf-turnstile" data-sitekey="<?= e(Auth::turnstileSiteKey()) ?>" data-theme="light"></div>
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php endif; ?>
        <button class="btn btn-gold" type="submit">Sign in</button>
    </form>
</div>

<div class="auth-card">
    <p class="eyebrow">FIRST-TIME SETUP</p>
    <h1>Create the admin account</h1>
    <?php foreach (take_flash() as $notice): ?>
        <div class="flash <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('setup')) ?>">
        <?= csrf_field() ?>
        <label>Name<input name="name" maxlength="100" required></label>
        <label>Username<input name="username" minlength="3" maxlength="60" autocomplete="username" required></label>
        <label>Password<input name="password" type="password" minlength="8" autocomplete="new-password" required></label>
        <button class="btn btn-gold" type="submit">Create account</button>
    </form>
</div>

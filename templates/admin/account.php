<div class="page-head"><div><p class="eyebrow">ACCOUNT</p><h1>My account</h1></div></div>
<div class="account-grid">
<section class="panel narrow">
    <h2>Profile details</h2>
    <p class="muted">Your username is the login identity and cannot be changed here.</p>
    <form method="post" action="<?= e(url('admin/account')) ?>">
        <?= csrf_field() ?>
        <label>Username
            <input type="text" value="<?= e($account['username'] ?? '') ?>" readonly>
        </label>
        <label>Display name <span class="req" aria-hidden="true">*</span>
            <input name="name" maxlength="100" value="<?= e($account['name'] ?? '') ?>" required>
        </label>
        <label>Email address
            <input name="email" type="email" maxlength="150" value="<?= e($account['email'] ?? '') ?>" placeholder="optional">
        </label>
        <div class="form-actions"><button class="btn btn-gold" type="submit">Save changes</button></div>
    </form>
</section>
<section class="panel narrow">
    <h2>Change password</h2>
    <p class="muted">Use at least 8 characters. You will keep using the same username after changing it.</p>
    <form method="post" action="<?= e(url('admin/account/password')) ?>">
        <?= csrf_field() ?>
        <label>Current password <span class="req" aria-hidden="true">*</span>
            <input name="current_password" type="password" autocomplete="current-password" required>
        </label>
        <label>New password <span class="req" aria-hidden="true">*</span>
            <input name="new_password" type="password" minlength="8" autocomplete="new-password" required>
        </label>
        <label>Confirm new password <span class="req" aria-hidden="true">*</span>
            <input name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
        </label>
        <div class="form-actions"><button class="btn btn-gold" type="submit">Change password</button></div>
    </form>
</section>
</div>

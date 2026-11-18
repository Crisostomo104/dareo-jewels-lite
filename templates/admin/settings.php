<div class="page-head"><div><p class="eyebrow">CONFIGURATION</p><h1>Settings</h1></div></div>
<section class="panel wide">
    <form method="post" action="<?= e(url('admin/settings')) ?>">
        <?= csrf_field() ?>
        <div class="admin-form-grid">
            <div>
                <label>Business name <span class="req" aria-hidden="true">*</span><input name="business_name" maxlength="100" value="<?= e(Database::setting('business_name', 'DaREO Jewels')) ?>" required></label>
                <label>Messenger link (https only)<input name="messenger" type="url" placeholder="https://m.me/..." value="<?= e(Database::setting('messenger')) ?>"></label>
            </div>
            <div>
                <div class="form-row">
                    <label>Default profit margin (%)<input name="target_profit_percent" type="number" step="0.01" min="0" max="99" value="<?= e(Database::setting('target_profit_percent', '25')) ?>"></label>
                    <label>Installment markup (%)<input name="installment_markup_percent" type="number" step="0.01" min="0" max="100" value="<?= e(Database::setting('installment_markup_percent', '15')) ?>"></label>
                </div>
                <label class="check"><input type="checkbox" name="powered_by" value="1" <?= Database::setting('powered_by', '1') === '1' ? 'checked' : '' ?>> Show “Powered by: Loadsss” footer modal</label>
                <div class="form-actions"><button class="btn btn-gold" type="submit">Save settings</button></div>
            </div>
        </div>
    </form>
</section>

<div class="page-head"><div>
    <p class="eyebrow">INVENTORY</p>
    <h1><?= $product ? 'Edit product' : 'Add product' ?></h1>
</div></div>
<section class="panel wide">
    <form method="post" action="<?= e(url('admin/products')) ?>" enctype="multipart/form-data" data-product-form>
        <?= csrf_field() ?>
        <?php if ($product): ?><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><?php endif; ?>
        <div class="editor-grid">
            <div class="editor-media">
                <?php
                $existing = json_decode((string) ($product['images'] ?? '[]'), true);
                $existing = is_array($existing) ? $existing : [];
                ?>
                <div class="preview-stage" data-preview-stage>
                    <span class="preview-empty" data-preview-empty>◇<br><small>No product images yet</small></span>
                    <div class="preview-grid" data-preview-grid data-count="<?= count($existing) ?>">
                        <?php foreach ($existing as $image): ?>
                            <figure><img src="<?= e(url('assets/uploads/' . $image)) ?>" alt="<?= e($product['name'] ?? '') ?>"><figcaption>Current</figcaption></figure>
                        <?php endforeach; ?>
                    </div>
                </div>
                <label>Photos <small>(JPG, PNG or WebP · up to 6 · 3 MB each)</small>
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-image-input>
                </label>
            </div>
            <div class="editor-fields">
                <label>Product name <span class="req" aria-hidden="true">*</span>
                    <input name="name" maxlength="160" value="<?= e($product['name'] ?? '') ?>" data-name-input required>
                </label>
                <div class="form-row">
                    <label>Acquisition cost (₱) <span class="req" aria-hidden="true">*</span>
                        <input name="acquisition" inputmode="decimal" value="<?= e(isset($product['acquisition_cents']) ? Money::input((int) $product['acquisition_cents']) : '0.00') ?>" required>
                    </label>
                    <label>Direct item cost (₱) <span class="req" aria-hidden="true">*</span>
                        <input name="direct" inputmode="decimal" value="<?= e(isset($product['direct_cents']) ? Money::input((int) $product['direct_cents']) : '0.00') ?>" required>
                    </label>
                </div>
                <label>Acquisition date <span class="req" aria-hidden="true">*</span>
                    <input name="acquired_on" type="date" value="<?= e($product['acquired_on'] ?? date('Y-m-d')) ?>" required>
                </label>
                <div class="form-row">
                    <label>Acquisition paid on <small>(blank if unpaid)</small>
                        <input name="acquisition_paid_on" type="date" value="<?= e($product['acquisition_paid_on'] ?? '') ?>">
                    </label>
                    <label>Direct cost paid on <small>(blank if unpaid)</small>
                        <input name="direct_paid_on" type="date" value="<?= e($product['direct_paid_on'] ?? '') ?>">
                    </label>
                </div>
            </div>
            <div class="editor-side">
                <label>SKU <span class="req" aria-hidden="true">*</span>
                    <input type="text" name="sku" id="sku-field" value="<?= e($product['sku'] ?? ($nextSku ?? '')) ?>" readonly aria-describedby="sku-help">
                </label>
                <small id="sku-help" class="hint">Generated automatically from the product number.</small>
                <label>Category <span class="req" aria-hidden="true">*</span>
                    <select name="category" required>
                        <?php foreach (['Necklaces','Rings','Bracelets','Earrings','Pendants','Other Gold Jewelry'] as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= ($product['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Notes <small>(optional)</small>
                    <textarea name="description" rows="5" maxlength="2000"><?= e($product['description'] ?? '') ?></textarea>
                </label>
                <?php if ($quote !== null): ?>
                    <p class="hint">Suggested at current settings — cash <strong><?= e(Money::show($quote['cash'])) ?></strong>, installment <strong><?= e(Money::show($quote['installment'])) ?></strong>. These are estimates only.</p>
                <?php endif; ?>
                <div class="form-actions">
                    <button class="btn btn-gold" type="submit">Save product</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/products')) ?>">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</section>

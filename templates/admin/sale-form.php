<div class="page-head"><div><p class="eyebrow">ORDERS</p><h1>New sale</h1></div></div>
<section class="panel narrow">
    <?php if ($products === []): ?>
        <p class="empty">No available products. Add a product first.</p>
        <a class="btn btn-outline" href="<?= e(url('admin/products/new')) ?>">Add product</a>
    <?php else: ?>
    <form method="post" action="<?= e(url('admin/sales')) ?>">
        <?= csrf_field() ?>
        <label>Product <span class="req" aria-hidden="true">*</span>
            <select name="product_id" id="sale-product" class="sale-product-native" required tabindex="-1" aria-hidden="true">
                <?php foreach ($products as $product): ?>
                    <?php
                    $cost = (int) $product['acquisition_cents'] + (int) $product['direct_cents'];
                    $hint = $cost > 0 ? Pricing::quote($cost, $settings['margin'], $settings['markup']) : null;
                    $pictures = json_decode((string) $product['images'], true);
                    $pictures = is_array($pictures) ? $pictures : [];
                    ?>
                    <option value="<?= (int) $product['id'] ?>"
                        data-cash="<?= $hint ? (int) $hint['cash'] : 0 ?>"
                        data-installment="<?= $hint ? (int) $hint['installment'] : 0 ?>"
                        data-down="<?= $hint ? (int) $hint['cash'] : 0 ?>"
                        data-thumb="<?= $pictures !== [] ? e(url('assets/uploads/' . $pictures[0])) : '' ?>"
                        data-sub="<?= e($product['sku'] . ' · ' . $product['category']) ?>"><?= e($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="product-picker" data-product-picker>
                <button type="button" class="product-picker-toggle" data-picker-toggle aria-haspopup="listbox" aria-expanded="false">
                    <span class="picker-thumb" data-picker-thumb aria-hidden="true">◇</span>
                    <span class="picker-name" data-picker-name>Choose a product</span>
                    <span class="picker-caret" aria-hidden="true">▾</span>
                </button>
                <div class="product-picker-menu" data-picker-menu hidden>
                    <input type="search" class="picker-search" data-picker-search placeholder="Search product…" aria-label="Search products">
                    <div class="picker-options" data-picker-options role="listbox"></div>
                </div>
            </div>
        </label>
        <label>Customer name <span class="req" aria-hidden="true">*</span><input name="customer_name" maxlength="120" required></label>
        <div class="form-row">
            <label>Payment type
                <select name="payment_type" id="sale-type"><option value="cash">Cash</option><option value="installment">Installment</option></select>
            </label>
            <label>Date of sale <span class="req" aria-hidden="true">*</span><input name="sold_on" type="date" value="<?= date('Y-m-d') ?>" required></label>
        </div>
        <label id="installment-count-row" hidden>Number of installments <span class="req" aria-hidden="true">*</span>
            <input name="installment_count" id="sale-count" type="number" min="1" step="1" value="2" inputmode="numeric">
        </label>
        <div class="form-row">
            <label>Agreed price (₱) <span class="req" aria-hidden="true">*</span><input name="price" id="sale-price" inputmode="decimal" required></label>
            <label>Downpayment (₱) <span class="req" aria-hidden="true">*</span><input name="downpayment" id="sale-down" inputmode="decimal" value="0.00" required></label>
            <label>Per installment (₱) <small>(balance ÷ count)</small>
                <input id="sale-per-installment" type="text" value="" readonly aria-describedby="sale-per-hint">
            </label>
        </div>
        <p class="hint" id="sale-per-hint">Computed from (agreed price − downpayment) ÷ number of installments. The final payment takes any remainder.</p>
        <p class="hint" id="sale-hint">Prices are suggestions from current settings. The downpayment is recorded as a collection on the sale date; extra collections are added with the Payment button.</p>
        <div class="form-actions"><button class="btn btn-gold" type="submit">Record sale</button><a class="btn btn-outline" href="<?= e(url('admin/sales')) ?>">Cancel</a></div>
    </form>
    <?php endif; ?>
</section>

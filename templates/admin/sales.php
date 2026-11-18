<div class="page-head">
    <div><p class="eyebrow">ORDERS</p><h1>Sales</h1></div>
    <a class="btn btn-gold" href="<?= e(url('admin/sales/new')) ?>">+ New sale</a>
</div>
<section class="panel">
    <?php if ($sales === []): ?><p class="empty">No sales recorded yet.</p><?php else: ?>
    <div class="table-scroll sales-table-scroll"><table class="data-table">
        <thead><tr><th>ID</th><th>Image</th><th>Customer</th><th>Product</th><th class="num">Total</th><th class="num">Balance</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $dialogsHtml = ''; ?>
        <?php foreach ($sales as $sale): ?>
            <?php
            $balance = (int) $sale['total_cents'] - (int) $sale['collected_cents'];
            $tableImages = json_decode((string) ($sale['product_images'] ?? '[]'), true);
            $tableImages = is_array($tableImages) ? $tableImages : [];
            ?>
            <tr>
                <td>#<?= (int) $sale['id'] ?></td>
                <td><?php if ($tableImages !== []): ?><img class="thumb" src="<?= e(url('assets/uploads/' . $tableImages[0])) ?>" alt=""><?php else: ?><span class="placeholder small" aria-hidden="true">◇</span><?php endif; ?></td>
                <td><?= e($sale['customer_name']) ?></td>
                <td><?= e($sale['product_name']) ?></td>
                <td class="num"><?= e(Money::show((int) $sale['total_cents'])) ?></td>
                <td class="num"><?= e(Money::show($balance)) ?></td>
                <td><?= e($sale['payment_type']) ?></td>
                <td><span class="badge"><?= e($sale['status']) ?></span></td>
                <td class="actions">
                    <div class="action-buttons">
                    <?php if ($sale['status'] === 'Confirmed'): ?>
                        <button class="btn btn-ghost small" type="button" data-dialog="pay-<?= (int) $sale['id'] ?>">Payment</button>
                        <form method="post" action="<?= e(url('admin/sales/' . (int) $sale['id'] . '/receive')) ?>" data-confirm="Mark this sale Received? Revenue and cost will enter the P&L.">
                            <?= csrf_field() ?><button class="btn btn-ghost small" type="submit">Receive</button>
                        </form>
                        <button class="btn btn-ghost small" type="button" data-dialog="cancel-<?= (int) $sale['id'] ?>">Cancel</button>
                    <?php else: ?>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php ob_start(); ?>
            <dialog class="modal" id="pay-<?= (int) $sale['id'] ?>" aria-labelledby="pay-title-<?= (int) $sale['id'] ?>">
                <div class="modal-head"><h2 id="pay-title-<?= (int) $sale['id'] ?>">Record payment · #<?= (int) $sale['id'] ?></h2><button class="icon-button" type="button" data-close aria-label="Close">×</button></div>
                <p class="muted">Remaining balance: <strong><?= e(Money::show($balance)) ?></strong></p>
                <?php if (!empty($payments[(int) $sale['id']] ?? [])): ?>
                    <?php $latestPaymentId = (int) $payments[(int) $sale['id']][0]['id']; ?>
                    <div class="payment-list">
                        <p class="muted">Recorded payments</p>
                        <?php foreach ($payments[(int) $sale['id']] as $payment): ?>
                            <div class="payment-row">
                                <span><?= e(Money::show((int) $payment['amount_cents'])) ?> · <?= e($payment['paid_on']) ?><?php if (!empty($payment['installment_no'])): ?> · #<?= (int) $payment['installment_no'] ?><?php endif; ?></span>
                                <?php if ((int) $payment['id'] === $latestPaymentId): ?>
                                    <form method="post" action="<?= e(url('admin/sales/' . (int) $sale['id'] . '/payment/' . (int) $payment['id'] . '/reverse')) ?>" data-confirm="Reverse this payment?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost small" type="submit">Reverse</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                $paidNumbers = [];
                $paidByNumber = [];
                foreach ($payments[(int) $sale['id']] ?? [] as $existingPayment) {
                    if (!empty($existingPayment['installment_no'])) {
                        $number = (int) $existingPayment['installment_no'];
                        $paidNumbers[$number] = true;
                        $paidByNumber[$number] = (int) $existingPayment['amount_cents'];
                    }
                }
                ?>
                <?php if ($sale['payment_type'] === 'installment'): ?>
                    <?php $schedule = installment_schedule((int) $sale['total_cents'], (int) $sale['downpayment_cents'], (int) ($sale['installment_count'] ?? 0), $sale['sold_on'], $paidByNumber); ?>
                    <div class="installment-schedule">
                        <p class="muted">Installment schedule · 15th &amp; 30th</p>
                        <?php if ($schedule === []): ?>
                            <p class="muted">No schedule set on this sale.</p>
                        <?php else: ?>
                            <?php foreach ($schedule as $entry): ?>
                                <div class="schedule-row <?= $entry['paid'] ? 'is-paid' : '' ?>">
                                    <span><?= (int) $entry['number'] ?>. <?= e($entry['date']) ?></span>
                                    <strong><?= e(Money::show($entry['amount_cents'])) ?><?php if ($entry['paid']): ?> · paid <?= e(Money::show((int) $entry['paid_cents'])) ?><?php endif; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php $nextUnpaid = null; ?>
                    <?php foreach ($schedule as $entry): ?>
                        <?php if (!$entry['paid']): ?>
                            <?php $nextUnpaid = $entry; break; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <form method="post" action="<?= e(url('admin/sales/' . (int) $sale['id'] . '/payment')) ?>">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <label>Amount (₱)<input name="amount" inputmode="decimal" value="<?= $nextUnpaid ? e(number_format($nextUnpaid['amount_cents'] / 100, 2, '.', '')) : '' ?>" placeholder="0.00" required></label>
                        <label>Date paid<input name="paid_on" type="date" value="<?= date('Y-m-d') ?>" required></label>
                        <?php if ($sale['payment_type'] === 'installment'): ?>
                            <label>Installment no.
                                <select name="installment_no" data-payment-number required>
                                    <option value="">Choose…</option>
                                    <?php foreach ($schedule as $entry): ?>
                                        <?php if ($entry['paid']): ?>
                                            <option value="<?= (int) $entry['number'] ?>" disabled><?= (int) $entry['number'] ?> · paid</option>
                                        <?php else: ?>
                                            <option value="<?= (int) $entry['number'] ?>" data-amount="<?= e(number_format($entry['amount_cents'] / 100, 2, '.', '')) ?>" <?= $nextUnpaid && (int) $entry['number'] === (int) $nextUnpaid['number'] ? 'selected' : '' ?>><?= (int) $entry['number'] ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions"><button class="btn btn-gold" type="submit">Save payment</button><button class="btn btn-outline" type="button" data-close>Cancel</button></div>
                </form>
            </dialog>

            <dialog class="modal" id="cancel-<?= (int) $sale['id'] ?>" aria-labelledby="cancel-title-<?= (int) $sale['id'] ?>">
                <div class="modal-head"><h2 id="cancel-title-<?= (int) $sale['id'] ?>">Cancel sale #<?= (int) $sale['id'] ?>?</h2><button class="icon-button" type="button" data-close aria-label="Close">×</button></div>
                <p>Reverse every recorded payment first. Then the confirmed sale can be cancelled and the product becomes available again.</p>
                <form method="post" action="<?= e(url('admin/sales/' . (int) $sale['id'] . '/cancel')) ?>">
                    <?= csrf_field() ?>
                    <div class="form-actions"><button class="btn btn-danger" type="submit">Cancel sale</button><button class="btn btn-outline" type="button" data-close>Keep sale</button></div>
                </form>
            </dialog>
            <?php $dialogsHtml .= ob_get_clean(); ?>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="sales-card-list">
        <?php foreach ($sales as $sale): ?>
            <?php $balance = (int) $sale['total_cents'] - (int) $sale['collected_cents']; ?>
            <article class="sale-card">
                <div class="sc-head">
                    <span class="sc-id">#<?= (int) $sale['id'] ?></span>
                    <span class="badge"><?= e($sale['status']) ?></span>
                </div>
                <?php
                $saleImages = json_decode((string) ($sale['product_images'] ?? '[]'), true);
                $saleImages = is_array($saleImages) ? $saleImages : [];
                ?>
                <div class="sc-product-row">
                    <span class="sc-thumb" aria-hidden="true">
                        <?php if ($saleImages !== []): ?>
                            <img src="<?= e(url('assets/uploads/' . $saleImages[0])) ?>" alt="">
                        <?php else: ?>◇<?php endif; ?>
                    </span>
                    <div>
                        <h3><?= e($sale['customer_name']) ?></h3>
                        <p class="sc-product"><?= e($sale['product_name']) ?></p>
                    </div>
                </div>
                <div class="sc-grid">
                    <div><small>Total</small><strong><?= e(Money::show((int) $sale['total_cents'])) ?></strong></div>
                    <div><small>Balance</small><strong><?= e(Money::show($balance)) ?></strong></div>
                    <div><small>Type</small><strong><?= e($sale['payment_type']) ?></strong></div>
                </div>
                <?php if ($sale['status'] === 'Confirmed'): ?>
                    <div class="sc-actions">
                        <button class="btn btn-ghost small" type="button" data-dialog="pay-<?= (int) $sale['id'] ?>">Payment</button>
                        <form method="post" action="<?= e(url('admin/sales/' . (int) $sale['id'] . '/receive')) ?>" data-confirm="Mark this sale Received? Revenue and cost will enter the P&L.">
                            <?= csrf_field() ?><button class="btn btn-ghost small" type="submit">Receive</button>
                        </form>
                        <button class="btn btn-ghost small" type="button" data-dialog="cancel-<?= (int) $sale['id'] ?>">Cancel</button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?= $dialogsHtml ?>
    <?php endif; ?>
    <?= pagination($pager, url('admin/sales')) ?>
</section>

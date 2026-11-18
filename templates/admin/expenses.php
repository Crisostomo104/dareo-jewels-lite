<div class="page-head">
    <div><p class="eyebrow">OPERATIONS</p><h1>Expenses</h1></div>
    <button class="btn btn-gold" type="button" data-dialog="new-expense">+ Add expense</button>
</div>
<section class="panel">
    <?php if ($expenses === []): ?><p class="empty">No expenses recorded yet.</p><?php else: ?>
    <div class="table-scroll"><table class="data-table">
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th class="num">Amount</th><th>Actions</th></tr></thead>
        <tbody><?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?= e($expense['spent_on']) ?></td>
                <td><?= e($expense['category']) ?></td>
                <td><?= e($expense['description']) ?></td>
                <td class="num"><?= e(Money::show((int) $expense['amount_cents'])) ?></td>
                <td><form method="post" action="<?= e(url('admin/expenses/' . (int) $expense['id'] . '/delete')) ?>" data-confirm="Delete this expense?">
                    <?= csrf_field() ?><button class="btn btn-ghost small" type="submit">Delete</button>
                </form></td>
            </tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <?= pagination($pager, url('admin/expenses')) ?>
</section>
<dialog class="modal" id="new-expense" aria-labelledby="new-expense-title">
    <div class="modal-head"><h2 id="new-expense-title">Add expense</h2><button class="icon-button" type="button" data-close aria-label="Close">×</button></div>
    <form method="post" action="<?= e(url('admin/expenses')) ?>">
        <?= csrf_field() ?>
        <label>Category
            <select name="category">
                <?php foreach (['Rent','Utilities','Transport','Marketing','Supplies','Salaries','Other'] as $cat): ?>
                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Description<input name="description" maxlength="300" placeholder="Optional"></label>
        <div class="form-row">
            <label>Amount (₱) <span class="req" aria-hidden="true">*</span><input name="amount" inputmode="decimal" placeholder="0.00" required></label>
            <label>Date spent <span class="req" aria-hidden="true">*</span><input name="spent_on" type="date" value="<?= date('Y-m-d') ?>" required></label>
        </div>
        <div class="form-actions"><button class="btn btn-gold" type="submit">Save expense</button><button class="btn btn-outline" type="button" data-close>Cancel</button></div>
    </form>
</dialog>

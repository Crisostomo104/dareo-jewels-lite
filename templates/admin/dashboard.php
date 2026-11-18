<div class="page-head">
    <div><p class="eyebrow">OVERVIEW</p><h1>Dashboard</h1></div>
    <form class="filter-bar" method="get" action="<?= e(url('admin')) ?>">
        <label class="sr-only" for="month">Report month</label>
        <input id="month" type="month" name="month" value="<?= e($month) ?>" data-auto-submit aria-label="Report month">
    </form>
</div>
<div class="kpi-grid">
    <article class="kpi <?= $stats['net'] < 0 ? 'negative' : 'positive' ?>">
        <p>Net profit / (loss)</p><strong><?= e(Money::show($stats['net'])) ?></strong>
        <span><?= e(number_format($stats['margin'], 2)) ?>% net margin</span>
    </article>
    <article class="kpi"><p>Sales revenue</p><strong><?= e(Money::show($stats['revenue'])) ?></strong><span><?= (int) $stats['sales_count'] ?> received sales</span></article>
    <article class="kpi"><p>Cost of sold jewelry</p><strong><?= e(Money::show($stats['cogs'])) ?></strong><span>acquisition + direct cost</span></article>
    <article class="kpi"><p>Operating expenses</p><strong><?= e(Money::show($stats['expenses'])) ?></strong><span>recorded this month</span></article>
    <article class="kpi"><p>Collections</p><strong><?= e(Money::show($stats['collections'])) ?></strong><span>customer payments this month</span></article>
    <article class="kpi"><p>Outstanding receivables</p><strong><?= e(Money::show($stats['receivables'])) ?></strong><span>all recognized sales</span></article>
</div>
<section class="panel">
    <div class="section-head"><h2>Profit or loss · <?= e(month_label($month)) ?></h2><a class="btn btn-ghost" href="<?= e(url('admin/reports')) ?>?month=<?= e($month) ?>">Full report →</a></div>
    <table class="data-table"><tbody>
        <tr><th>Sales revenue</th><td><?= e(Money::show($stats['revenue'])) ?></td></tr>
        <tr><th>Less: cost of sold jewelry</th><td>(<?= e(Money::show($stats['cogs'])) ?>)</td></tr>
        <tr><th>Gross profit</th><td><?= e(Money::show($stats['gross'])) ?></td></tr>
        <tr><th>Less: operating expenses</th><td>(<?= e(Money::show($stats['expenses'])) ?>)</td></tr>
        <tr class="total"><th>Net profit / (loss)</th><td><?= e(Money::show($stats['net'])) ?></td></tr>
    </tbody></table>
    <p class="muted">Unsold inventory value: <strong><?= e(Money::show($stats['inventory_value'])) ?></strong>. Sales and cost are recognized when a sale is marked Received.</p>
</section>
<section class="panel">
    <h2>Recent sales</h2>
    <?php if ($recent === []): ?><p class="empty">No sales recorded yet.</p><?php else: ?>
    <div class="table-scroll"><table class="data-table">
        <thead><tr><th>ID</th><th>Customer</th><th>Product</th><th>Status</th><th class="num">Total</th><th class="num">Collected</th></tr></thead>
        <tbody><?php foreach ($recent as $sale): ?>
            <tr><td>#<?= (int) $sale['id'] ?></td><td><?= e($sale['customer_name']) ?></td><td><?= e($sale['product_name']) ?></td>
            <td><span class="badge"><?= e($sale['status']) ?></span></td>
            <td class="num"><?= e(Money::show((int) $sale['total_cents'])) ?></td>
            <td class="num"><?= e(Money::show((int) $sale['collected_cents'])) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>

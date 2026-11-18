<div class="page-head">
    <div><p class="eyebrow">FINANCIALS</p><h1>Reports</h1></div>
    <form class="report-actions" method="get" action="<?= e(url('admin/reports')) ?>">
        <label class="sr-only" for="month">Report month</label>
        <input id="month" type="month" name="month" value="<?= e($month) ?>" data-auto-submit aria-label="Report month">
        <button class="btn btn-ghost no-print" type="button" onclick="window.print()">Print / Save PDF</button>
    </form>
</div>
<section class="panel">
    <h2>Statement of profit or loss · <?= e(month_label($month)) ?></h2>
    <p class="muted">Management report. Sales and cost of goods sold are recognized when a sale is marked Received; downpayments alone are not sales.</p>
    <table class="data-table"><tbody>
        <tr><th>Sales revenue</th><td class="num"><?= e(Money::show($stats['revenue'])) ?></td></tr>
        <tr><th>Less: cost of sold jewelry</th><td class="num">(<?= e(Money::show($stats['cogs'])) ?>)</td></tr>
        <tr><th>Gross profit</th><td class="num"><?= e(Money::show($stats['gross'])) ?></td></tr>
        <tr><th>Less: operating expenses</th><td class="num">(<?= e(Money::show($stats['expenses'])) ?>)</td></tr>
        <tr class="total"><th>Net profit / (loss)</th><td class="num"><?= e(Money::show($stats['net'])) ?></td></tr>
        <tr><th>Net margin</th><td class="num"><?= e(number_format($stats['margin'], 2)) ?>%</td></tr>
    </tbody></table>
</section>
<div class="two-col">
    <section class="panel">
        <h2>Recorded cash movement</h2>
        <table class="data-table"><tbody>
            <tr><th>Customer collections</th><td class="num"><?= e(Money::show($stats['collections'])) ?></td></tr>
            <tr><th>Operating expense payments</th><td class="num">(<?= e(Money::show($stats['expenses'])) ?>)</td></tr>
            <tr class="total"><th>Net cash movement</th><td class="num"><?= e(Money::show($stats['cash_flow'])) ?></td></tr>
        </tbody></table>
        <p class="muted">Lite edition tracks collections and operating expenses only. Inventory purchase cash flow and reversals are not tracked here.</p>
    </section>
    <section class="panel">
        <h2>Current position</h2>
        <table class="data-table"><tbody>
            <tr><th>Unsold inventory value</th><td class="num"><?= e(Money::show($stats['inventory_value'])) ?></td></tr>
            <tr><th>Outstanding receivables</th><td class="num"><?= e(Money::show($stats['receivables'])) ?></td></tr>
        </tbody></table>
        <p class="muted">Management summary, not a balance sheet. Reconcile with actual cash and invoices.</p>
    </section>
</div>

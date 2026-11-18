<div class="page-head"><div><p class="eyebrow">PRICING</p><h1>Pricing calculator</h1></div></div>
<section class="panel wide">
    <div class="admin-form-grid">
        <div>
            <p class="muted">Target profit is a margin on the selling price, not a markup on cost. The installment markup is applied once to the cash price.</p>
            <form method="post" action="<?= e(url('admin/calculator')) ?>">
                <?= csrf_field() ?>
                <label>Acquisition + direct cost (₱)<input name="cost" inputmode="decimal" value="<?= e($cost) ?>" placeholder="0.00" required></label>
                <div class="form-row">
                    <label>Profit margin (%)<input name="margin" type="number" step="0.01" min="0" max="99" value="<?= e($margin) ?>" required></label>
                    <label>Installment markup (%)<input name="markup" type="number" step="0.01" min="0" max="100" value="<?= e($markup) ?>" required></label>
                </div>
                <button class="btn btn-gold" type="submit">Calculate</button>
            </form>
        </div>
        <div>
            <?php if ($quote !== null): ?>
                <div class="result-grid">
                    <article class="kpi"><p>Cash price</p><strong><?= e(Money::show($quote['cash'])) ?></strong><span>profit <?= e(Money::show($quote['cash_profit'])) ?></span></article>
                    <article class="kpi"><p>Installment price</p><strong><?= e(Money::show($quote['installment'])) ?></strong><span>profit <?= e(Money::show($quote['installment_profit'])) ?></span></article>
                </div>
            <?php else: ?>
                <p class="muted">Enter a cost and the profit/markup percentages, then calculate to see the suggested cash and installment prices.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

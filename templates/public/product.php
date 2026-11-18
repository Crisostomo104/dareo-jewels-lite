<div class="detail-wrap">
<a class="back-link" href="<?= e(url('collection')) ?>">← Back to collection</a>
<div class="detail-grid">
    <div>
        <div class="product-image large">
            <?php if ($images !== []): ?>
                <img src="<?= e(url('assets/uploads/' . $images[0])) ?>" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <span class="placeholder" aria-hidden="true">◇</span>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="thumbnails">
                <?php foreach ($images as $image): ?>
                    <a href="<?= e(url('assets/uploads/' . $image)) ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?= e(url('assets/uploads/' . $image)) ?>" alt="<?= e($product['name']) ?> thumbnail">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="detail-info">
        <p class="eyebrow"><?= e($product['category']) ?> · <?= e($product['sku']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <?php if ($product['karat'] !== '' || $product['grams'] !== ''): ?>
            <p class="muted"><?= e(trim((string) $product['karat'] . ' ' . (string) $product['grams'])) ?></p>
        <?php endif; ?>
        <?php if (trim((string) ($product['description'] ?? '')) !== ''): ?>
        <section class="panel">
            <h2>Product notes</h2>
            <p><?= e($product['description']) ?></p>
        </section>
        <?php endif; ?>
        <section class="panel inquiry">
            <h2>Interested in this piece?</h2>
            <?php if ($messenger !== ''): ?>
                <p class="muted">Message us on Messenger and mention this piece:</p>
                <p class="messenger-url"><?= e($messenger) ?></p>
                <a class="btn btn-gold" href="<?= e($messenger) ?>" target="_blank" rel="noopener noreferrer">Continue to Messenger ↗</a>
            <?php else: ?>
                <p class="muted">Messenger link is not configured yet. Please contact the shop directly.</p>
                <a class="btn btn-outline" href="<?= e(url('contact')) ?>">Contact us</a>
            <?php endif; ?>
        </section>
    </div>
</div>
</div>

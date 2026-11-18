<section class="home-hero">
    <div class="home-hero-inner">
        <p class="eyebrow">THE GOLD COLLECTION</p>
        <h1>Small treasures,<br><em>forever yours.</em></h1>
        <p>Gold jewelry for everyday moments and life's most meaningful occasions. Browse the collection, then message us about the piece you love.</p>
        <a class="btn btn-gold" href="<?= e(url('collection')) ?>">Explore the collection <span aria-hidden="true">↘</span></a>
    </div>
    <img class="home-hero-mark-img" src="<?= e(url('assets/logo/dareo-logo-w-tagline.png')) ?>" alt="" aria-hidden="true">
</section>

<section class="home-featured" aria-labelledby="featured-heading">
    <div class="section-head">
        <div>
            <p class="eyebrow">HAND-PICKED</p>
            <h2 id="featured-heading">Pieces worth keeping</h2>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('collection')) ?>">View all pieces →</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $product): ?>
            <?php $images = json_decode((string) $product['images'], true); ?>
            <a class="product-card" href="<?= e(url('p/' . (int) $product['id'])) ?>">
                <div class="product-image">
                    <?php if (is_array($images) && $images !== []): ?>
                        <img src="<?= e(url('assets/uploads/' . $images[0])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="placeholder" aria-hidden="true">◇</span>
                    <?php endif; ?>
                    <span class="status-pill">Available</span>
                </div>
                <p class="card-category"><?= e($product['category']) ?></p>
                <h3><?= e($product['name']) ?></h3>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-note">
    <p class="eyebrow">PERSONAL SERVICE</p>
    <h2>Questions about a piece?</h2>
    <p>Every item in the collection is shown with photos and details. Pick the one you like and continue to Messenger from its page — no account needed.</p>
    <a class="btn btn-outline" href="<?= e(url('contact')) ?>">Contact us</a>
</section>

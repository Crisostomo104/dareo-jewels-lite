<section id="collection" class="collection" aria-labelledby="collection-heading">
    <p class="collection-count" data-result-count><?= count($products) ?> <?= count($products) === 1 ? 'piece' : 'pieces' ?></p>
    <form class="filter-bar" id="catalog-filters" role="search" onsubmit="return false">
        <label class="sr-only" for="q">Search jewelry</label>
        <input id="q" name="q" type="search" placeholder="Search jewelry" value="<?= e($q) ?>" maxlength="100" autocomplete="off" data-search>
        <label class="sr-only" for="category">Category</label>
        <select id="category" name="category" data-category>
            <option value="">All categories</option>
            <?php foreach ($categories as $option): ?>
                <option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="product-grid" id="product-grid" data-products>
        <?php foreach ($products as $product): ?>
            <?php $images = json_decode((string) $product['images'], true); ?>
            <a class="product-card" href="<?= e(url('p/' . (int) $product['id'])) ?>"
               data-name="<?= e(mb_strtolower((string) $product['name'])) ?>"
               data-category="<?= e($product['category']) ?>">
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
    <p class="empty" data-empty hidden>No pieces match your search.</p>
    <nav class="pagination catalog-pagination" data-pagination aria-label="Collection pages"></nav>
</section>

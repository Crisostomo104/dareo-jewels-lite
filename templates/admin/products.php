<div class="page-head">
    <div><p class="eyebrow">INVENTORY</p><h1>Products</h1></div>
    <a class="btn btn-gold" href="<?= e(url('admin/products/new')) ?>">+ Add product</a>
</div>
<form class="filter-bar admin-filter" method="get" action="<?= e(url('admin/products')) ?>" role="search">
    <label class="sr-only" for="product-q">Search products</label>
    <input id="product-q" name="q" type="search" placeholder="Search name or SKU" value="<?= e($term) ?>" maxlength="100" data-admin-filter>
    <label class="sr-only" for="product-status">Status</label>
    <select id="product-status" name="status" data-admin-filter-submit>
        <option value="">All statuses</option>
        <?php foreach (['Available', 'Reserved', 'Sold', 'Archived'] as $option): ?>
            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Search</button>
</form>
<section class="panel">
    <?php if ($products === []): ?><p class="empty">No products yet. Add your first piece.</p><?php else: ?>
    <div class="table-scroll"><table class="data-table card-list">
        <thead><tr><th>Image</th><th>Product</th><th>Category</th><th class="num">Cost</th><th class="num">Est. cash price</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $settings = Pricing::settings(); ?>
        <?php foreach ($products as $product): ?>
            <?php $images = json_decode((string) $product['images'], true); ?>
            <tr>
                <td data-label="Image"><?php if (is_array($images) && $images !== []): ?><img class="thumb" src="<?= e(url('assets/uploads/' . $images[0])) ?>" alt=""><?php else: ?><span class="placeholder small" aria-hidden="true">◇</span><?php endif; ?></td>
                <td data-label="Product"><div><strong><?= e($product['name']) ?></strong><br><span class="muted"><?= e($product['sku']) ?></span></div></td>
                <td data-label="Category"><?= e($product['category']) ?></td>
                <td class="num" data-label="Cost"><?= e(Money::show((int) $product['acquisition_cents'] + (int) $product['direct_cents'])) ?></td>
                <td class="num" data-label="Est. cash price"><?php
                    $cost = (int) $product['acquisition_cents'] + (int) $product['direct_cents'];
                    echo $cost > 0 ? e(Money::show(Pricing::quote($cost, $settings['margin'], $settings['markup'])['cash'])) : '—';
                ?></td>
                <td data-label="Status"><span class="badge"><?= e($product['status']) ?></span></td>
                <td class="actions" data-label="Actions"><div class="action-buttons">
                    <a class="btn btn-ghost small" href="<?= e(url('admin/products/' . (int) $product['id'] . '/edit')) ?>">Edit</a>
                    <?php if ($product['status'] !== 'Archived'): ?>
                        <form method="post" action="<?= e(url('admin/products/' . (int) $product['id'] . '/archive')) ?>" data-confirm="Archive this product and hide it from the gallery?">
                            <?= csrf_field() ?><button class="btn btn-ghost small" type="submit">Archive</button>
                        </form>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="card-list-mobile">
        <?php foreach ($products as $product): ?>
            <?php
            $images = json_decode((string) $product['images'], true);
            $images = is_array($images) ? $images : [];
            $cost = (int) $product['acquisition_cents'] + (int) $product['direct_cents'];
            $price = $cost > 0 ? Money::show(Pricing::quote($cost, $settings['margin'], $settings['markup'])['cash']) : '—';
            ?>
            <article class="product-card">
                <div class="pc-top">
                    <span class="pc-name"><?= e($product['name']) ?></span>
                    <span class="pc-cat">· <?= e($product['category']) ?></span>
                </div>
                <div class="pc-mid">
                    <div class="pc-img">
                        <?php if ($images !== []): ?>
                            <img src="<?= e(url('assets/uploads/' . $images[0])) ?>" alt="">
                        <?php else: ?>
                            <span class="placeholder small" aria-hidden="true">◇</span>
                        <?php endif; ?>
                    </div>
                    <div class="pc-meta">
                        <span class="pc-status"><?= e($product['status']) ?></span>
                        <span class="pc-price">· Price: <?= e($price) ?></span>
                        <span class="pc-cost">· Cost: <?= e(Money::show($cost)) ?></span>
                    </div>
                </div>
                <div class="pc-sku"><?= e($product['sku']) ?></div>
                <div class="pc-actions">
                    <a class="btn btn-ghost small" href="<?= e(url('admin/products/' . (int) $product['id'] . '/edit')) ?>">Edit</a>
                    <?php if ($product['status'] !== 'Archived'): ?>
                        <form method="post" action="<?= e(url('admin/products/' . (int) $product['id'] . '/archive')) ?>" data-confirm="Archive this product and hide it from the gallery?">
                            <?= csrf_field() ?><button class="btn btn-ghost small" type="submit">Archive</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?= pagination($pager, url('admin/products'), ['q' => $term, 'status' => $status]) ?>
</section>

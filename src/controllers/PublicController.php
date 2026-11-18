<?php
declare(strict_types=1);

namespace App;

final class PublicController
{
    public function home(): void
    {
        $featured = Database::all(
            "SELECT id, name, category, description, images FROM products
             WHERE status = 'Available' AND images NOT IN ('[]', '')
             ORDER BY id DESC LIMIT 4"
        );
        render('public/home', ['featured' => $featured], 'layouts/public');
    }

    public function catalog(): void
    {
        $q = request_get('q');
        $category = request_get('category');
        $sql = "SELECT id, name, category, karat, grams, description, images FROM products WHERE status = 'Available'";
        $params = [];
        if ($q !== '') {
            $sql .= ' AND name LIKE ?';
            $params[] = '%' . mb_substr($q, 0, 100) . '%';
        }
        if ($category !== '') {
            $sql .= ' AND category = ?';
            $params[] = mb_substr($category, 0, 60);
        }
        $sql .= ' ORDER BY id DESC';
        $products = Database::all($sql, $params);
        $categories = ['Necklaces', 'Rings', 'Bracelets', 'Earrings', 'Pendants', 'Other Gold Jewelry'];
        render('public/catalog', compact('products', 'q', 'category', 'categories'), 'layouts/public');
    }

    public function about(): void
    {
        render('public/about', [], 'layouts/public');
    }

    public function contact(): void
    {
        render('public/contact', [], 'layouts/public');
    }

    public function product(int $id): void
    {
        $product = Database::one("SELECT * FROM products WHERE id = ? AND status = 'Available'", [$id]);
        if (!$product) {
            abort_404();
        }
        $decoded = json_decode((string) $product['images'], true);
        $images = is_array($decoded) ? $decoded : [];
        $messenger = Database::setting('messenger');
        if ($messenger === '' || !filter_var($messenger, FILTER_VALIDATE_URL) || parse_url($messenger, PHP_URL_SCHEME) !== 'https') {
            $messenger = '';
        }
        render('public/product', compact('product', 'images', 'messenger'), 'layouts/public');
    }
}

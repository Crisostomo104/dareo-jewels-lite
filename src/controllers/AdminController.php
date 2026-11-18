<?php
declare(strict_types=1);

namespace App;

final class AdminController
{
    private function guard(): void
    {
        Auth::requireLogin();
    }

    private function validDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
            && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4));
    }

    private function collected(int $saleId): int
    {
        return (int) Database::scalar('SELECT COALESCE(SUM(amount_cents), 0) FROM payments WHERE sale_id = ?', [$saleId]);
    }

    private function uploadImages(): array
    {
        $saved = [];
        if (empty($_FILES['images']) || !is_array($_FILES['images'])) {
            return $saved;
        }
        $names = $_FILES['images']['name'] ?? [];
        if (!is_array($names)) {
            $names = [$names];
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        foreach ($names as $index => $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $tmp = $_FILES['images']['tmp_name'][$index] ?? null;
            if (!is_string($tmp) || !is_uploaded_file($tmp)) {
                continue;
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!isset($allowed[$mime]) || filesize($tmp) > 3 * 1024 * 1024) {
                continue;
            }
            $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
            if (move_uploaded_file($tmp, dirname(__DIR__) . '/assets/uploads/' . $filename)) {
                $saved[] = $filename;
            }
        }
        return $saved;
    }

    /* ---------- Setup / auth ---------- */

    public function setupForm(): void
    {
        if (Auth::hasUsers()) {
            redirect('admin/login');
        }
        render('admin/setup', [], 'layouts/public');
    }

    public function setup(): void
    {
        if (Auth::hasUsers()) {
            redirect('admin/login');
        }
        $username = request_post('username');
        $password = request_post('password');
        $name = request_post('name', 'Owner');
        if (mb_strlen($username) < 3 || mb_strlen($password) < 8) {
            flash('Username must be at least 3 characters and password at least 8.', 'error');
            redirect('setup');
        }
        Auth::createFirstUser($username, $password, $name !== '' ? $name : 'Owner');
        flash('Admin account created. Please sign in.');
        redirect('admin/login');
    }

    public function loginForm(): void
    {
        forget_old();
        render('admin/login', [], 'layouts/public');
    }

    public function login(): void
    {
        $username = request_post('username');
        $password = request_post('password');
        if (Auth::loginLocked($username)) {
            flash('Too many failed sign-in attempts. Please try again later.', 'error');
            redirect('admin/login');
        }
        if (Auth::turnstileEnabled() && !Auth::verifyTurnstile(request_post('cf-turnstile-response', ''))) {
            flash('Captcha verification failed. Please try again.', 'error');
            redirect('admin/login');
        }
        if (!Auth::attempt($username, $password)) {
            Auth::recordFailedLogin($username);
            flash('Invalid username or password.', 'error');
            redirect('admin/login');
        }
        Auth::clearLoginAttempts($username);
        forget_old();
        redirect('admin');
    }

    public function logout(): void
    {
        $this->guard();
        Auth::logout();
        redirect('admin/login');
    }

    /* ---------- Dashboard / calculator ---------- */

    public function dashboard(): void
    {
        $this->guard();
        $month = Report::normalizeMonth(request_get('month', date('Y-m')));
        $stats = Report::month($month);
        $recent = Report::recentSales(6);
        render('admin/dashboard', compact('month', 'stats', 'recent'), 'layouts/admin');
    }

    public function calculator(): void
    {
        $this->guard();
        $settings = Pricing::settings();
        render('admin/calculator', [
            'quote' => null,
            'cost' => '',
            'margin' => (string) $settings['margin'],
            'markup' => (string) $settings['markup'],
        ], 'layouts/admin');
    }

    public function calculate(): void
    {
        $this->guard();
        $cost = request_post('cost');
        $margin = request_post('margin');
        $markup = request_post('markup');
        try {
            $quote = Pricing::quote(Money::cents($cost), (float) $margin, (float) $markup);
        } catch (\Throwable $e) {
            flash('Check the cost and percentages.', 'error');
            redirect('admin/calculator');
        }
        render('admin/calculator', compact('quote', 'cost', 'margin', 'markup'), 'layouts/admin');
    }

    /* ---------- Products ---------- */

    public function products(): void
    {
        $this->guard();
        $term = mb_substr(request_get('q'), 0, 100);
        $status = request_get('status');
        $where = [];
        $params = [];
        if ($term !== '') {
            $where[] = '(name LIKE ? OR sku LIKE ?)';
            $params[] = '%' . $term . '%';
            $params[] = '%' . $term . '%';
        }
        if (in_array($status, ['Available', 'Reserved', 'Sold', 'Archived'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $sql = 'SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC';
        $all = Database::all($sql, $params);
        $pager = paginate($all, (int) request_get('page', '1'), 10);
        render('admin/products', [
            'products' => $pager['items'],
            'pager' => $pager,
            'term' => $term,
            'status' => $status,
        ], 'layouts/admin');
    }

    public function productForm(?int $id = null): void
    {
        $this->guard();
        $product = $id ? Database::one('SELECT * FROM products WHERE id = ?', [$id]) : null;
        if ($id && !$product) {
            abort_404();
        }
        $settings = Pricing::settings();
        $nextSku = 'PRDCT-' . str_pad((string) ((int) Database::scalar('SELECT COALESCE(MAX(id), 0) + 1 FROM products')), 6, '0', STR_PAD_LEFT);
        $quote = null;
        if ($product && (int) $product['acquisition_cents'] > 0) {
            $quote = Pricing::quote((int) $product['acquisition_cents'] + (int) $product['direct_cents'], $settings['margin'], $settings['markup']);
        }
        render('admin/product-form', compact('product', 'quote', 'nextSku'), 'layouts/admin');
    }

    public function saveProduct(): void
    {
        $this->guard();
        $id = (int) request_post('id');
        $name = request_post('name');
        if ($name === '' || mb_strlen($name) > 150) {
            flash('Product name is required (max 150 characters).', 'error');
            redirect($id > 0 ? 'admin/products/' . $id . '/edit' : 'admin/products/new');
        }
        try {
            $acquisition = Money::cents(request_post('acquisition', '0'));
            $direct = Money::cents(request_post('direct', '0'));
        } catch (\Throwable $e) {
            flash('Invalid cost amount.', 'error');
            redirect($id > 0 ? 'admin/products/' . $id . '/edit' : 'admin/products/new');
        }
        $acquiredOn = request_post('acquired_on');
        if (!$this->validDate($acquiredOn)) {
            flash('Acquisition date is required.', 'error');
            redirect($id > 0 ? 'admin/products/' . $id . '/edit' : 'admin/products/new');
        }
        $paidAcquisition = request_post('acquisition_paid_on');
        $paidDirect = request_post('direct_paid_on');
        foreach ([[$paidAcquisition, 'acquisition payment date'], [$paidDirect, 'direct cost payment date']] as [$value, $label]) {
            if ($value !== '' && !$this->validDate($value)) {
                flash('Invalid ' . $label . '.', 'error');
                redirect($id > 0 ? 'admin/products/' . $id . '/edit' : 'admin/products/new');
            }
        }
        $data = [
            'name' => $name,
            'sku' => mb_substr(request_post('sku'), 0, 60),
            'category' => mb_substr(request_post('category', 'Other Gold Jewelry'), 0, 60),
            'description' => mb_substr(request_post('description'), 0, 2000),
            'acquisition_cents' => $acquisition,
            'direct_cents' => $direct,
            'acquired_on' => $acquiredOn,
            'acquisition_paid_on' => $paidAcquisition !== '' ? $paidAcquisition : null,
            'direct_paid_on' => $paidDirect !== '' ? $paidDirect : null,
        ];
        try {
            if ($id > 0) {
                $fields = [];
                $params = [];
                foreach ($data as $key => $value) {
                    $fields[] = $key . ' = ?';
                    $params[] = $value;
                }
                $params[] = $id;
                Database::execute('UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
            } else {
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($columns), '?');
                Database::execute(
                    'INSERT INTO products (' . implode(', ', $columns) . ', created_at) VALUES (' . implode(', ', $placeholders) . ', ?)',
                    [...array_values($data), date('Y-m-d H:i:s')]
                );
                $id = (int) Database::pdo()->lastInsertId();
                Database::execute('UPDATE products SET sku = ? WHERE id = ?', ['PRDCT-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT), $id]);
            }
            $uploaded = $this->uploadImages();
            if ($uploaded !== []) {
                $existing = json_decode((string) Database::scalar('SELECT images FROM products WHERE id = ?', [$id]), true);
                $existing = is_array($existing) ? $existing : [];
                Database::execute('UPDATE products SET images = ? WHERE id = ?', [json_encode(array_merge($existing, $uploaded)), $id]);
            }
            flash('Product saved.');
        } catch (\Throwable $e) {
            flash('Could not save the product: ' . $e->getMessage(), 'error');
        }
        redirect('admin/products');
    }

    public function archiveProduct(int $id): void
    {
        $this->guard();
        Database::execute("UPDATE products SET status = 'Archived' WHERE id = ?", [$id]);
        flash('Product archived and hidden from the gallery.');
        redirect('admin/products');
    }

    /* ---------- Sales ---------- */

    public function sales(): void
    {
        $this->guard();
        $all = Database::all(
            'SELECT s.*, p.name AS product_name, p.images AS product_images,
                                COALESCE((SELECT SUM(amount_cents) FROM payments WHERE sale_id = s.id), 0) AS collected_cents
             FROM sales s JOIN products p ON p.id = s.product_id
             ORDER BY s.id DESC'
        );
        $pager = paginate($all, (int) request_get('page', '1'), 10);
        $bySale = [];
        foreach (Database::all('SELECT * FROM payments ORDER BY id DESC') as $payment) {
            $bySale[(int) $payment['sale_id']][] = $payment;
        }
        render('admin/sales', ['sales' => $pager['items'], 'pager' => $pager, 'payments' => $bySale], 'layouts/admin');
    }

    public function saleForm(): void
    {
        $this->guard();
        $products = Database::all("SELECT * FROM products WHERE status = 'Available' ORDER BY name");
        $settings = Pricing::settings();
        render('admin/sale-form', compact('products', 'settings'), 'layouts/admin');
    }

    public function createSale(): void
    {
        $this->guard();
        $productId = (int) request_post('product_id');
        $customer = mb_substr(request_post('customer_name'), 0, 120);
        $type = request_post('payment_type') === 'installment' ? 'installment' : 'cash';
        $count = max(0, (int) request_post('installment_count', '0'));
        $soldOn = request_post('sold_on', date('Y-m-d'));
        $product = Database::one("SELECT * FROM products WHERE id = ? AND status = 'Available'", [$productId]);
        if (!$product || $customer === '' || !$this->validDate($soldOn)) {
            flash('Choose an available product, enter the customer and a valid date.', 'error');
            redirect('admin/sales/new');
        }
        try {
            $total = Money::cents(request_post('price'));
            $down = Money::cents(request_post('downpayment', '0'));
        } catch (\Throwable $e) {
            flash('Invalid amount.', 'error');
            redirect('admin/sales/new');
        }
        if ($total < 1) {
            flash('Selling price must be greater than zero.', 'error');
            redirect('admin/sales/new');
        }
        if ($type === 'cash') {
            $down = $total;
        } elseif ($down <= 0 || $down >= $total) {
            flash('Installment downpayment must be greater than zero and less than the total.', 'error');
            redirect('admin/sales/new');
        } elseif ($down < (int) ceil($total / 2)) {
            flash('Installment downpayment must be at least 50% of the agreed price.', 'error');
            redirect('admin/sales/new');
        } elseif ($type === 'installment' && $count < 1) {
            flash('Enter the number of installments for this sale.', 'error');
            redirect('admin/sales/new');
        }
        try {
            Database::execute(
                'INSERT INTO sales (product_id, customer_name, total_cents, cost_cents, downpayment_cents, payment_type, status, sold_on, installment_count, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, \'Confirmed\', ?, ?, ?)',
                [$productId, $customer, $total, (int) $product['acquisition_cents'] + (int) $product['direct_cents'], $down, $type, $soldOn, $type === 'installment' ? $count : 0, date('Y-m-d H:i:s')]
            );
            $saleId = (int) Database::pdo()->lastInsertId();
            if ($down > 0) {
                Database::execute(
                    'INSERT INTO payments (sale_id, amount_cents, paid_on, created_at) VALUES (?, ?, ?, ?)',
                    [$saleId, $down, $soldOn, date('Y-m-d H:i:s')]
                );
            }
            Database::execute("UPDATE products SET status = 'Reserved' WHERE id = ?", [$productId]);
            flash('Sale recorded with the downpayment collected. Mark it Received when the jewelry is handed over.');
        } catch (\Throwable $e) {
            flash('Could not record the sale: ' . $e->getMessage(), 'error');
        }
        redirect('admin/sales');
    }

    public function recordPayment(int $id): void
    {
        $this->guard();
        $sale = Database::one("SELECT * FROM sales WHERE id = ? AND status != 'Cancelled'", [$id]);
        $paidOn = request_post('paid_on', date('Y-m-d'));
        $installmentNo = max(0, (int) request_post('installment_no', '0'));
        if (!$sale || !$this->validDate($paidOn)) {
            flash('Invalid sale or payment date.', 'error');
            redirect('admin/sales');
        }
        if ($sale['payment_type'] === 'installment') {
            $count = (int) $sale['installment_count'];
            if ($installmentNo < 1 || $installmentNo > $count) {
                flash('Choose a valid installment number.', 'error');
                redirect('admin/sales');
            }
            $used = Database::one('SELECT id FROM payments WHERE sale_id = ? AND installment_no = ?', [$id, $installmentNo]);
            if ($used) {
                flash('That installment is already paid. Choose the next unpaid number.', 'error');
                redirect('admin/sales');
            }
        } else {
            $installmentNo = 0;
        }
        try {
            $amount = Money::cents(request_post('amount'));
        } catch (\Throwable $e) {
            flash('Invalid amount.', 'error');
            redirect('admin/sales');
        }
        $remaining = (int) $sale['total_cents'] - $this->collected($id);
        if ($amount < 1 || $amount > $remaining) {
            flash('Payment must be positive and cannot exceed the remaining balance.', 'error');
            redirect('admin/sales');
        }
        Database::execute(
            'INSERT INTO payments (sale_id, amount_cents, paid_on, installment_no, created_at) VALUES (?, ?, ?, ?, ?)',
            [$id, $amount, $paidOn, $installmentNo > 0 ? $installmentNo : null, date('Y-m-d H:i:s')]
        );
        flash('Payment recorded.');
        redirect('admin/sales');
    }

    public function reversePayment(int $saleId, int $paymentId): void
    {
        $this->guard();
        $sale = Database::one('SELECT * FROM sales WHERE id = ?', [$saleId]);
        $payment = Database::one('SELECT * FROM payments WHERE id = ? AND sale_id = ?', [$paymentId, $saleId]);
        if (!$sale || !$payment || $sale['status'] === 'Received') {
            flash('That payment can no longer be reversed.', 'error');
            redirect('admin/sales');
        }
        $latest = Database::one('SELECT id FROM payments WHERE sale_id = ? ORDER BY id DESC LIMIT 1', [$saleId]);
        if (!$latest || (int) $latest['id'] !== (int) $payment['id']) {
            flash('Only the latest payment can be reversed.', 'error');
            redirect('admin/sales');
        }
        Database::execute('DELETE FROM payments WHERE id = ?', [$paymentId]);
        flash('Payment reversed. Cancel the sale again if no payments remain.');
        redirect('admin/sales');
    }

    public function receiveSale(int $id): void
    {
        $this->guard();
        $sale = Database::one("SELECT * FROM sales WHERE id = ? AND status = 'Confirmed'", [$id]);
        if (!$sale) {
            flash('Sale is not in a confirmable state.', 'error');
            redirect('admin/sales');
        }
        if ($this->collected($id) < (int) $sale['downpayment_cents']) {
            flash('Collect at least the downpayment before marking Received.', 'error');
            redirect('admin/sales');
        }
        Database::execute(
            "UPDATE sales SET status = 'Received', recognized_at = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $id]
        );
        Database::execute("UPDATE products SET status = 'Sold' WHERE id = ?", [(int) $sale['product_id']]);
        flash('Sale received — revenue and cost now count in the P&L.');
        redirect('admin/sales');
    }

    public function cancelSale(int $id): void
    {
        $this->guard();
        $sale = Database::one("SELECT * FROM sales WHERE id = ? AND status = 'Confirmed'", [$id]);
        if (!$sale) {
            flash('Only confirmed sales with no payments can be cancelled.', 'error');
            redirect('admin/sales');
        }
        if ($this->collected($id) > 0) {
            flash('Reverse or remove payments before cancelling.', 'error');
            redirect('admin/sales');
        }
        Database::execute("UPDATE sales SET status = 'Cancelled' WHERE id = ?", [$id]);
        Database::execute("UPDATE products SET status = 'Available' WHERE id = ?", [(int) $sale['product_id']]);
        flash('Sale cancelled and product is available again.');
        redirect('admin/sales');
    }

    /* ---------- Expenses ---------- */

    public function expenses(): void
    {
        $this->guard();
        $all = Database::all('SELECT * FROM expenses ORDER BY spent_on DESC, id DESC');
        $pager = paginate($all, (int) request_get('page', '1'), 10);
        render('admin/expenses', ['expenses' => $pager['items'], 'pager' => $pager], 'layouts/admin');
    }

    public function createExpense(): void
    {
        $this->guard();
        $category = mb_substr(request_post('category', 'Other'), 0, 40);
        $description = mb_substr(request_post('description'), 0, 300);
        $spentOn = request_post('spent_on', date('Y-m-d'));
        if ($category === '' || !$this->validDate($spentOn)) {
            flash('Enter a category and a valid date.', 'error');
            redirect('admin/expenses');
        }
        try {
            $amount = Money::cents(request_post('amount'));
        } catch (\Throwable $e) {
            flash('Invalid amount.', 'error');
            redirect('admin/expenses');
        }
        Database::execute(
            'INSERT INTO expenses (category, description, amount_cents, spent_on, created_at) VALUES (?, ?, ?, ?, ?)',
            [mb_strtoupper($category), $description, $amount, $spentOn, date('Y-m-d H:i:s')]
        );
        flash('Expense recorded.');
        redirect('admin/expenses');
    }

    public function deleteExpense(int $id): void
    {
        $this->guard();
        Database::execute('DELETE FROM expenses WHERE id = ?', [$id]);
        flash('Expense deleted.');
        redirect('admin/expenses');
    }

    /* ---------- Reports ---------- */

    public function reports(): void
    {
        $this->guard();
        $month = Report::normalizeMonth(request_get('month', date('Y-m')));
        $stats = Report::month($month);
        render('admin/reports', compact('month', 'stats'), 'layouts/admin');
    }

    public function exportCsv(): void
    {
        $this->guard();
        $month = Report::normalizeMonth(request_get('month', date('Y-m')));
        $stats = Report::month($month);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="dareo-pl-' . $month . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['DaREO Jewels Lite - Profit & Loss', $month]);
        $rows = [
            ['Sales Revenue', Money::input($stats['revenue'])],
            ['Less: COGS (cost of sold jewelry)', '-' . Money::input($stats['cogs'])],
            ['Gross Profit', Money::input($stats['gross'])],
            ['Less: Operating Expenses', '-' . Money::input($stats['expenses'])],
            ['Net Profit / (Loss)', Money::input($stats['net'])],
            ['Net Margin (%)', number_format($stats['margin'], 2)],
            ['Collections', Money::input($stats['collections'])],
            ['Outstanding Receivables', Money::input($stats['receivables'])],
            ['Unsold Inventory Value', Money::input($stats['inventory_value'])],
        ];
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /* ---------- Settings ---------- */

    public function settingsForm(): void
    {
        $this->guard();
        render('admin/settings', [], 'layouts/admin');
    }

    public function saveSettings(): void
    {
        $this->guard();
        $business = mb_substr(request_post('business_name', 'DaREO Jewels'), 0, 100);
        $messenger = request_post('messenger');
        if ($messenger !== '' && (!filter_var($messenger, FILTER_VALIDATE_URL) || parse_url($messenger, PHP_URL_SCHEME) !== 'https')) {
            $messenger = '';
            flash('Messenger link must be a valid https:// URL or left blank.', 'error');
        }
        $margin = request_post('target_profit_percent', '25');
        $markup = request_post('installment_markup_percent', '15');
        if (!is_numeric($margin) || (float) $margin < 0 || (float) $margin >= 100) {
            $margin = '25';
            flash('Profit margin must be between 0 and 99.', 'error');
        }
        if (!is_numeric($markup) || (float) $markup < 0 || (float) $markup > 100) {
            $markup = '15';
            flash('Installment markup must be between 0 and 100.', 'error');
        }
        Database::saveSetting('business_name', $business !== '' ? $business : 'DaREO Jewels');
        Database::saveSetting('messenger', $messenger);
        Database::saveSetting('target_profit_percent', (string) (float) $margin);
        Database::saveSetting('installment_markup_percent', (string) (float) $markup);
        Database::saveSetting('powered_by', isset($_POST['powered_by']) ? '1' : '0');
        flash('Settings saved.');
        redirect('admin/settings');
    }

    /* ---------- Admin account ---------- */

    public function accountForm(): void
    {
        $this->guard();
        $user = Auth::user();
        $row = Database::one('SELECT id, username, name, email FROM users WHERE id = ?', [(int) $user['id']]);
        render('admin/account', ['account' => $row], 'layouts/admin');
    }

    public function saveAccount(): void
    {
        $this->guard();
        $user = Auth::user();
        $name = mb_substr(request_post('name'), 0, 100);
        $email = mb_substr(request_post('email'), 0, 150);
        if ($name === '') {
            flash('Name is required.', 'error');
            redirect('admin/account');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Enter a valid email address or leave it blank.', 'error');
            redirect('admin/account');
        }
        Auth::updateProfile((int) $user['id'], $name, $email);
        flash('Account details saved.');
        redirect('admin/account');
    }

    public function changePassword(): void
    {
        $this->guard();
        $user = Auth::user();
        $error = Auth::changePassword(
            (int) $user['id'],
            request_post('current_password'),
            request_post('new_password'),
            request_post('confirm_password')
        );
        flash($error !== '' ? $error : 'Password changed successfully.', $error !== '' ? 'error' : 'ok');
        redirect('admin/account');
    }
}

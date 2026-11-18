<?php
declare(strict_types=1);

// Global helpers: templates run in the global namespace, and PHP's function
// fallback lets App\ controllers call these unqualified as well.

function base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    // Windows PHP can return "\" from dirname("/x.php"); normalise it.
    $dir = str_replace('\\', '/', dirname($script));
    return ($dir === '/' || $dir === '.') ? '' : $dir;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please reload the page and try again.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flash(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($items) ? $items : [];
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function remember(array $values): void
{
    $_SESSION['old'] = $values;
}

function forget_old(): void
{
    unset($_SESSION['old']);
}

function request_post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function request_get(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function render(string $view, array $data = [], string $layout = ''): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/templates/' . $view . '.php';
    $content = (string) ob_get_clean();
    if ($layout !== '') {
        require dirname(__DIR__) . '/templates/' . $layout . '.php';
        return;
    }
    echo $content;
}

function paginate(array $items, int $page, int $perPage = 10): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, $page));
    return ['items' => array_slice($items, ($page - 1) * $perPage, $perPage), 'page' => $page, 'pages' => $pages, 'total' => $total];
}

function pagination(array $pager, string $base, array $query = []): string
{
    if ($pager['pages'] <= 1) {
        return '<p class="muted">' . $pager['total'] . ' item(s)</p>';
    }
    $link = function (int $page) use ($base, $query): string {
        $params = array_merge($query, ['page' => $page]);
        return e($base) . '?' . http_build_query($params);
    };
    $html = '<nav class="pagination" aria-label="Pages"><span class="muted">' . $pager['total'] . ' items · page ' . $pager['page'] . ' of ' . $pager['pages'] . '</span>';
    if ($pager['page'] > 1) {
        $html .= '<a class="btn btn-outline small" href="' . $link($pager['page'] - 1) . '">← Prev</a>';
    }
    $from = max(1, $pager['page'] - 2);
    $to = min($pager['pages'], $from + 4);
    for ($i = $from; $i <= $to; $i++) {
        $html .= '<a class="page-num' . ($i === $pager['page'] ? ' current' : '') . '" href="' . $link($i) . '"' . ($i === $pager['page'] ? ' aria-current="page"' : '') . '>' . $i . '</a>';
    }
    if ($pager['page'] < $pager['pages']) {
        $html .= '<a class="btn btn-outline small" href="' . $link($pager['page'] + 1) . '">Next →</a>';
    }
    return $html . '</nav>';
}

function month_label(string $month): string
{
    $value = preg_match('/^\d{4}-\d{2}$/D', $month) ? $month . '-01' : date('Y-m-01');
    return date('F Y', strtotime($value));
}

function installment_schedule(int $totalCents, int $downCents, int $count, string $soldOn, array $paidByNumber = []): array
{
    if ($count < 1) {
        return [];
    }
    $balance = $totalCents - $downCents;
    if ($balance <= 0) {
        return [];
    }
    $base = intdiv($balance, $count);
    $zone = new DateTimeZone('Asia/Manila');
    try {
        $start = new DateTimeImmutable($soldOn, $zone);
    } catch (\Throwable $e) {
        $start = new DateTimeImmutable('today', $zone);
    }
    $cursor = $start->modify('first day of this month');
    $dates = [];
    for ($month = 0; $month < 36 && count($dates) < $count; $month++) {
        $year = (int) $cursor->format('Y');
        $monthNumber = (int) $cursor->format('m');
        $lastDay = (int) $cursor->format('t');
        foreach ([15, 30] as $day) {
            if (count($dates) >= $count) {
                break;
            }
            $due = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $monthNumber, min($day, $lastDay)), $zone);
            if ($due >= $start->setTime(0, 0)) {
                $dates[] = $due->format('Y-m-d');
            }
        }
        $cursor = $cursor->modify('first day of next month');
    }
    $schedule = [];
    $carry = 0;
    foreach ($dates as $index => $date) {
        $number = $index + 1;
        $amount = $index === $count - 1 ? $balance - $base * ($count - 1) : $base;
        $amount = max(0, $amount - $carry);
        $paid = array_key_exists($number, $paidByNumber);
        $paidCents = $paid ? (int) $paidByNumber[$number] : null;
        if ($paid) {
            $carry = $paidCents - $amount;
        } else {
            $carry = 0;
        }
        $schedule[] = [
            'number' => $number,
            'date' => $date,
            'amount_cents' => $amount,
            'paid' => $paid,
            'paid_cents' => $paidCents,
        ];
    }
    return $schedule;
}

function abort_404(): never
{
    http_response_code(404);
    render('public/notfound', [], 'layouts/public');
    exit;
}

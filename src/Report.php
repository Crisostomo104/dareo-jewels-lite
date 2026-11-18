<?php
declare(strict_types=1);

namespace App;

/** Management P&L. Revenue and COGS are recognised when a sale is marked Received. */
final class Report
{
    public static function normalizeMonth(string $month): string
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $month) ? $month : date('Y-m');
    }

    public static function month(string $month): array
    {
        $month = self::normalizeMonth($month);
        $start = $month . '-01';
        $end = date('Y-m-01', strtotime($start . ' +1 month'));
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total_cents), 0) AS revenue,
                    COALESCE(SUM(cost_cents), 0) AS cogs,
                    COUNT(*) AS sales_count
             FROM sales
             WHERE status = 'Received' AND recognized_at >= ? AND recognized_at < ?"
        );
        $stmt->execute([$start, $end]);
        $sales = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_cents), 0) FROM expenses WHERE spent_on >= ? AND spent_on < ?');
        $stmt->execute([$start, $end]);
        $expenses = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_cents), 0) FROM payments WHERE paid_on >= ? AND paid_on < ?');
        $stmt->execute([$start, $end]);
        $collections = (int) $stmt->fetchColumn();

        $owed = (int) Database::scalar("SELECT COALESCE(SUM(total_cents), 0) FROM sales WHERE status = 'Received'");
        $paid = (int) Database::scalar(
            "SELECT COALESCE(SUM(p.amount_cents), 0) FROM payments p
             JOIN sales s ON s.id = p.sale_id WHERE s.status = 'Received'"
        );

        $inventory = (int) Database::scalar(
            "SELECT COALESCE(SUM(acquisition_cents + direct_cents), 0) FROM products WHERE status IN ('Available','Reserved')"
        );

        $revenue = (int) $sales['revenue'];
        $cogs = (int) $sales['cogs'];
        $gross = $revenue - $cogs;
        $net = $gross - $expenses;

        return [
            'month' => $month,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross' => $gross,
            'expenses' => $expenses,
            'net' => $net,
            'margin' => $revenue > 0 ? ($net / $revenue) * 100 : 0.0,
            'sales_count' => (int) $sales['sales_count'],
            'collections' => $collections,
            'cash_flow' => $collections - $expenses,
            'receivables' => max(0, $owed - $paid),
            'inventory_value' => $inventory,
        ];
    }

    public static function recentSales(int $limit = 6): array
    {
        return Database::all(
            'SELECT s.*, p.name AS product_name,
                    COALESCE((SELECT SUM(amount_cents) FROM payments WHERE sale_id = s.id), 0) AS collected_cents
             FROM sales s JOIN products p ON p.id = s.product_id
             ORDER BY s.id DESC LIMIT ' . max(1, min(50, $limit))
        );
    }
}

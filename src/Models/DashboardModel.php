<?php
namespace App\Models;

use PDO;
use DateTime;
use DateTimeZone;
use PDOException;
use Throwable;

class DashboardModel extends BaseModel
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function getOverview(): array
    {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
                (SELECT COUNT(*) FROM users WHERE role = 'admin') AS total_admins,
                (SELECT COUNT(*) FROM posts WHERE status = 'published') AS total_posts,
                (SELECT COUNT(*) FROM products WHERE status = 'active') AS total_products,
                (SELECT COUNT(*) FROM orders WHERE status = 'completed') AS total_completed_orders,
                (SELECT COUNT(*) FROM orders WHERE status = 'cancelled') AS total_cancelled_orders,
                (SELECT COUNT(*) FROM comments WHERE status = 'approved') AS total_comments,
                (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed') AS total_revenue
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRevenueByDateRange(string $start, string $end): array
    {
        $sql = "
            SELECT 
                DATE(created_at) AS date,
                COALESCE(SUM(total_price), 0) AS revenue
            FROM orders 
            WHERE status = 'completed' 
              AND created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'start' => $start . ' 00:00:00',
            'end' => $end . ' 23:59:59'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopBuyers(int $limit = 5): array
    {
        $sql = "
            SELECT 
                u.id, u.username, u.email,
                COUNT(o.id) AS total_orders,
                COALESCE(SUM(o.total_price), 0) AS total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
            WHERE u.role = 'user'
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopCancellers(int $limit = 5): array
    {
        $sql = "
            SELECT 
                u.id, u.username, u.email,
                COUNT(o.id) AS cancelled_orders
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'cancelled'
            WHERE u.role = 'user'
            GROUP BY u.id
            HAVING cancelled_orders > 0
            ORDER BY cancelled_orders DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentOrders(int $limit = 10): array
    {
        $sql = "
            SELECT 
                o.id, o.order_code, o.total_price, o.status, o.created_at,
                u.username, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentComments(int $limit = 10): array
    {
        $sql = "
            SELECT 
                c.id, c.content, c.status, c.created_at,
                p.title AS post_title,
                u.username
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopSellingProducts(int $limit = 5): array
    {
        $sql = "
            SELECT 
                p.id, p.name,
                SUM(oi.quantity) AS total_sold,
                COALESCE(SUM(oi.price_at_purchase * oi.quantity), 0) AS revenue
            FROM products p
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê nhanh - LINH HOẠT & AN TOÀN
     */
public function getQuickStats(array $periods = ['today', 'yesterday', '7days', '30days'], ?array $customRange = null): array
{
    $stats = [];
    $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));

    foreach ($periods as $period) {
        $params = []; // MỖI LẦN MỚI
        $sql = $this->buildPeriodCase($period, $now, $params);

        if (!$sql) {
            error_log("[getQuickStats] buildPeriodCase failed for: $period");
            continue;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $stats[$row['period']] = [
                    'revenue'   => (float)$row['revenue'],
                    'orders'    => (int)$row['orders'],
                    'completed' => (int)$row['completed']
                ];
            }
        } catch (PDOException $e) {
            error_log("[PDO ERROR] $period: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params));
        }
    }

    // Custom range
    if ($customRange && !empty($customRange['start']) && !empty($customRange['end'])) {
        $params = [
            ':start' => $customRange['start'] . ' 00:00:00',
            ':end'   => $customRange['end'] . ' 23:59:59'
        ];
        $sql = "SELECT 'custom' AS period, 
            COALESCE(SUM(CASE WHEN created_at BETWEEN :start AND :end AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
            COUNT(CASE WHEN created_at BETWEEN :start AND :end THEN 1 END) AS orders,
            COUNT(CASE WHEN created_at BETWEEN :start AND :end AND status = 'completed' THEN 1 END) AS completed
            FROM orders";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $stats['custom'] = [
                    'revenue'   => (float)$row['revenue'],
                    'orders'    => (int)$row['orders'],
                    'completed' => (int)$row['completed']
                ];
            }
        } catch (PDOException $e) {
            error_log("[PDO ERROR] custom: " . $e->getMessage());
        }
    }

    return $stats;
}
 private function buildPeriodCase(string $period, DateTime $now, array &$params): ?string
{
    $tz = self::TIMEZONE;

    try {
        switch ($period) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end   = $now->format('Y-m-d 23:59:59');

                $params[':start1'] = $start;
                $params[':end1']   = $end;
                $params[':start2'] = $start;
                $params[':end2']   = $end;
                $params[':start3'] = $start;
                $params[':end3']   = $end;

                return "SELECT 'today' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND created_at <= :end1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 AND created_at <= :end2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND created_at <= :end3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case '7days':
                $start = (clone $now)->modify('-6 days')->format('Y-m-d 00:00:00');
                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT '7days' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case '30days':
                $start = (clone $now)->modify('-29 days')->format('Y-m-d 00:00:00');
                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT '30days' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'this_week':
                $dt = (clone $now)->setISODate($now->format('Y'), $now->format('W'), 1);
                if ($dt === false) return null;
                $start = $dt->format('Y-m-d 00:00:00');

                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT 'this_week' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'last_week':
                $lastWeek = (clone $now)->modify('-7 days');
                $year = $lastWeek->format('Y');
                $week = $lastWeek->format('W');

                $startDt = new DateTime();
                $startDt->setTimezone(new DateTimeZone($tz));
                if ($startDt->setISODate($year, $week, 1) === false) return null;
                $start = $startDt->format('Y-m-d 00:00:00');

                $endDt = clone $startDt;
                if ($endDt->setISODate($year, $week, 7) === false) return null;
                $end = $endDt->format('Y-m-d 23:59:59');

                $params[':start1'] = $start;
                $params[':end1']   = $end;
                $params[':start2'] = $start;
                $params[':end2']   = $end;
                $params[':start3'] = $start;
                $params[':end3']   = $end;

                return "SELECT 'last_week' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND created_at <= :end1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 AND created_at <= :end2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND created_at <= :end3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'this_month':
                $start = (clone $now)->modify('first day of this month')->format('Y-m-d 00:00:00');
                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT 'this_month' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'last_month':
                $start = (clone $now)->modify('first day of last month')->format('Y-m-d 00:00:00');
                $end   = (clone $now)->modify('last day of last month')->format('Y-m-d 23:59:59');

                $params[':start1'] = $start;
                $params[':end1']   = $end;
                $params[':start2'] = $start;
                $params[':end2']   = $end;
                $params[':start3'] = $start;
                $params[':end3']   = $end;

                return "SELECT 'last_month' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND created_at <= :end1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 AND created_at <= :end2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND created_at <= :end3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'this_quarter':
                $month = $now->format('n');
                $quarterStart = $this->getQuarterStartMonth($month);
                $start = $now->format("Y-{$quarterStart}-01 00:00:00");

                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT 'this_quarter' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'last_quarter':
                $currentQuarter = ceil($now->format('n') / 3);
                $lastQuarter = $currentQuarter - 1;
                $year = $now->format('Y');

                if ($lastQuarter < 1) {
                    $lastQuarter = 4;
                    $year--;
                }

                $startMonth = $this->getQuarterStartMonth(($lastQuarter - 1) * 3 + 1);
                $start = sprintf('%d-%02d-01 00:00:00', $year, $startMonth);
                $end = (new DateTime($start, new DateTimeZone($tz)))
                    ->modify('+3 months -1 day')
                    ->format('Y-m-d 23:59:59');

                $params[':start1'] = $start;
                $params[':end1']   = $end;
                $params[':start2'] = $start;
                $params[':end2']   = $end;
                $params[':start3'] = $start;
                $params[':end3']   = $end;

                return "SELECT 'last_quarter' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND created_at <= :end1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 AND created_at <= :end2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND created_at <= :end3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'this_year':
                $start = $now->format('Y-01-01 00:00:00');
                $params[':start1'] = $start;
                $params[':start2'] = $start;
                $params[':start3'] = $start;

                return "SELECT 'this_year' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            case 'last_year':
                $year = $now->format('Y') - 1;
                $start = "$year-01-01 00:00:00";
                $end   = "$year-12-31 23:59:59";

                $params[':start1'] = $start;
                $params[':end1']   = $end;
                $params[':start2'] = $start;
                $params[':end2']   = $end;
                $params[':start3'] = $start;
                $params[':end3']   = $end;

                return "SELECT 'last_year' AS period, 
                    COALESCE(SUM(CASE WHEN created_at >= :start1 AND created_at <= :end1 AND status = 'completed' THEN total_price ELSE 0 END), 0) AS revenue,
                    COUNT(CASE WHEN created_at >= :start2 AND created_at <= :end2 THEN 1 END) AS orders,
                    COUNT(CASE WHEN created_at >= :start3 AND created_at <= :end3 AND status = 'completed' THEN 1 END) AS completed
                    FROM orders";

            default:
                return null;
        }
    } catch (Throwable $e) {
        $trace = $e->getTraceAsString();
        $msg = sprintf(
            "[%s] buildPeriodCase ERROR | period=%s | message=%s | file=%s:%d | trace:\n%s",
            date('Y-m-d H:i:s'),
            $period,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $trace
        );
        error_log($msg);
        return null;
    }
}
    private function getQuarterStartMonth(int $month): string
    {
        $quarter = ceil($month / 3);
        return match($quarter) {
            1 => '01',
            2 => '04',
            3 => '07',
            4 => '10',
            default => '01'
        };
    }

    public function getActivationKeyStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_keys,
                COUNT(CASE WHEN status = 'used' THEN 1 END) AS used_keys,
                COUNT(CASE WHEN status = 'unused' THEN 1 END) AS unused_keys,
                COUNT(CASE WHEN type = 'bán lẻ' THEN 1 END) AS retail_keys,
                COUNT(CASE WHEN type = 'bán sỉ' THEN 1 END) AS wholesale_keys
            FROM activation_keys
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getNewUsersByDateRange(string $start, string $end): array
    {
        $sql = "
            SELECT DATE(created_at) AS date, COUNT(*) AS new_users
            FROM users
            WHERE created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'start' => $start . ' 00:00:00',
            'end' => $end . ' 23:59:59'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
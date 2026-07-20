<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use App\Config\Database;

class GlobalRateLimitMiddleware {
    private int $maxRequests;
    private int $timeWindowSeconds;

    public function __construct(int $maxRequests = 100, int $timeWindowSeconds = 60) {
        $this->maxRequests = $maxRequests;
        $this->timeWindowSeconds = $timeWindowSeconds;
    }

    public function __invoke(Request $request, Handler $handler): ResponseInterface {
        // Bỏ qua Rate Limit cho các request OPTIONS (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        $ip = $this->getClientIp($request);
        $currentTime = time();
        $pdo = Database::getConnection();

        try {
            $this->recordRequest($pdo, $ip, $currentTime);
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), "Table") !== false) {
                $this->createTable($pdo);
                $this->recordRequest($pdo, $ip, $currentTime);
            } else {
                throw $e;
            }
        }

        // Tự động dọn dẹp các bản ghi cũ (tỷ lệ 5% để không làm chậm mọi request)
        if (rand(1, 100) <= 5) {
            $this->cleanup($pdo, $currentTime);
        }

        $requestCount = $this->getRequestCount($pdo, $ip, $currentTime);

        if ($requestCount > $this->maxRequests) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Hệ thống đang quá tải hoặc bạn đang gửi quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.'
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(429)->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $handler->handle($request);
    }

    private function getClientIp(Request $request): string {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['HTTP_CF_CONNECTING_IP'] 
            ?? $serverParams['HTTP_X_FORWARDED_FOR'] 
            ?? $serverParams['REMOTE_ADDR'] 
            ?? 'unknown';
            
        // Nếu có nhiều IP do đi qua nhiều Proxy, lấy IP gốc đầu tiên
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }
        return trim($ip);
    }

    private function recordRequest(\PDO $pdo, string $ip, int $time): void {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, request_time) VALUES (?, ?)");
        $stmt->execute([$ip, $time]);
    }

    private function getRequestCount(\PDO $pdo, string $ip, int $time): int {
        $startTime = $time - $this->timeWindowSeconds;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND request_time > ?");
        $stmt->execute([$ip, $startTime]);
        return (int)$stmt->fetchColumn();
    }

    private function cleanup(\PDO $pdo, int $time): void {
        $startTime = $time - $this->timeWindowSeconds;
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE request_time <= ?");
        $stmt->execute([$startTime]);
    }

    private function createTable(\PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                request_time INT NOT NULL,
                INDEX idx_ip_time (ip_address, request_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}

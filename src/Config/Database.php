<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Settings::get('DB_HOST', 'localhost');
        $port = Settings::get('DB_PORT', '4000');
        $dbname = Settings::get('DB_NAME', 'personal_blog_shop');
        $user = Settings::get('DB_USER', 'root');
        $pass = Settings::get('DB_PASS', '');
        $charset = Settings::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_CA       => __DIR__ . '/cacert.pem',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            self::$pdo->exec("SET time_zone = '+07:00';");
            return self::$pdo;
        } catch (PDOException $e) {
            $message = "Database Connection Failed: " . $e->getMessage();
            error_log($message);

            if (Settings::get('APP_ENV', 'development') === 'production') {
                throw new PDOException("Không thể kết nối đến cơ sở dữ liệu.", 0, $e);
            }

            throw $e; 
        }
    }
}
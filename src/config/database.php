<?php
namespace App\config;

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

        $host = settings::get('DB_HOST', 'localhost');
        $dbname = settings::get('DB_NAME', 'personal_blog_shop');
        $user = settings::get('DB_USER', 'root');
        $pass = settings::get('DB_PASS', '');
        $charset = settings::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            self::$pdo->exec("SET time_zone = '+07:00';");
            return self::$pdo;
        } catch (PDOException $e) {
            $message = "Database Connection Failed: " . $e->getMessage();
            error_log($message);

            if (settings::get('APP_ENV', 'development') === 'production') {
                throw new PDOException("Không thể kết nối đến cơ sở dữ liệu.", 0, $e);
            }

            throw $e; 
        }
    }
}
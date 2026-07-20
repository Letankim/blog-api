<?php
namespace App\Config;

use Dotenv\Dotenv;

class Settings
{
    private static ?array $config = null;

    public static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $envFile = __DIR__ . '/../../.env';
        $env = $_ENV;

        if (file_exists($envFile)) {
            try {
                $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
                $dotenv->load();
                $env = $_ENV;
            } catch (\Exception $e) {
                error_log("Settings: Không thể load .env: " . $e->getMessage());
            }
        } else {
            error_log("Settings: File .env không tồn tại tại: $envFile");
        }

        $keysToCheck = [
            'APP_URL', 'APP_ENV', 'JWT_SECRET', 'LOG_LEVEL', 
            'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET', 
            'ABLE_API_KEY', 'GEMINI_API_KEY', 'GROQ_API_KEY',
            'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_PORT', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME', 
            'PAYMENT_CLIENT_ID', 'PAYMENT_SECRET_API_KEY', 'PAYMENT_CHECK_SUM_KEY', 'PAYMENT_RETURN_URL', 'PAYMENT_CANCEL_URL',
            'CLOUDINARY_URL', 'CLOUDINARY_CLOUD_NAME', 'CLOUDINARY_API_KEY', 'CLOUDINARY_API_SECRET',
            'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI',
            'TURNSTILE_SECRET_KEY'
        ];
        
        $osEnv = getenv();
        if (is_array($osEnv)) {
            $env = array_merge($env, $osEnv);
        }

        foreach ($keysToCheck as $key) {
            $val = getenv($key);
            if ($val !== false && !isset($env[$key])) {
                $env[$key] = $val;
            }
        }

       $default = [
        'APP_URL'     => 'http://localhost/personal_blog_shop',
        'APP_ENV'     => 'development',
        'JWT_SECRET'  => 'fallback-secret-change-in-production-1234567890',
        'LOG_LEVEL'   => 'DEBUG',

        'DB_HOST'     => 'localhost',
        'DB_PORT'     => '4000',
        'DB_NAME'     => 'personal_blog_shop',
        'DB_USER'     => 'root',
        'DB_PASS'     => '',
        'DB_CHARSET'  => 'utf8mb4',
        'ABLE_API_KEY'  => '',

        'MAIL' => [
            'HOST'       => 'smtp.gmail.com',
            'USERNAME'   => '',
            'PASSWORD'   => '',
            'ENCRYPTION' => 'tls',
            'PORT'       => 587,
            'FROM_EMAIL' => '',
            'FROM_NAME'  => '3DO Blog'
        ],

        'PAYOS' => [
                'CLIENT_ID'     => '',
                'API_KEY'       => '',
                'CHECKSUM_KEY'  => '',
                'RETURN_URL'    => 'http://localhost/personal_blog_shop/payment/callback',
                'CANCEL_URL'    => 'http://localhost/personal_blog_shop/payment/callback',
            ]
        ,
        'google_oauth' => [
            'clientId'     => getenv('GOOGLE_CLIENT_ID') ?: '',
            'clientSecret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'redirectUri'  => getenv('GOOGLE_REDIRECT_URI') ?: '',
        ],
    ];

        $mailMap = [
            'MAIL_HOST' => 'HOST',
            'MAIL_USERNAME' => 'USERNAME',
            'MAIL_PASSWORD' => 'PASSWORD',
            'MAIL_ENCRYPTION' => 'ENCRYPTION',
            'MAIL_PORT' => 'PORT',
            'MAIL_FROM_EMAIL' => 'FROM_EMAIL',
            'MAIL_FROM_NAME' => 'FROM_NAME',
        ];

        $payosMap = [
            'PAYMENT_CLIENT_ID'     => ['PAYOS', 'CLIENT_ID'],
            'PAYMENT_SECRET_API_KEY'=> ['PAYOS', 'API_KEY'],
            'PAYMENT_CHECK_SUM_KEY' => ['PAYOS', 'CHECKSUM_KEY'],
            'PAYMENT_RETURN_URL'    => ['PAYOS', 'RETURN_URL'],
            'PAYMENT_CANCEL_URL'    => ['PAYOS', 'CANCEL_URL'],
        ];

        $generalMap = [
            'APP_URL'     => 'APP_URL',
            'APP_ENV'     => 'APP_ENV',
            'JWT_SECRET'  => 'JWT_SECRET',
            'LOG_LEVEL'   => 'LOG_LEVEL',
            'DB_HOST'     => 'DB_HOST',
            'DB_PORT'     => 'DB_PORT',
            'DB_NAME'     => 'DB_NAME',
            'DB_USER'     => 'DB_USER',
            'DB_PASS'     => 'DB_PASS',
            'DB_CHARSET'  => 'DB_CHARSET',
            'ABLE_API_KEY'  => 'ABLE_API_KEY',
            'TURNSTILE_SECRET_KEY' => 'TURNSTILE_SECRET_KEY',
            'GROQ_API_KEY'  => 'GROQ_API_KEY',
            'GEMINI_API_KEY' => 'GEMINI_API_KEY',
        ];

       foreach ($generalMap as $envKey => $configKey) {
            if (isset($env[$envKey])) {
                $default[$configKey] = $env[$envKey];
            }
        }

        foreach ($mailMap as $envKey => $path) {
            if (isset($env[$envKey])) {
                $default['MAIL'][$path] = $env[$envKey];
            }
        }

        foreach ($payosMap as $envKey => $path) {
            if (isset($env[$envKey])) {
                $default[$path[0]][$path[1]] = $env[$envKey];
            }
        }
        $config = array_replace_recursive($default, $env);

        if (empty($config['MAIL']['FROM_EMAIL']) && !empty($config['MAIL']['USERNAME'])) {
            $config['MAIL']['FROM_EMAIL'] = $config['MAIL']['USERNAME'];
        }

        if (empty($config['MAIL']['FROM_EMAIL'])) {
            $host = parse_url($config['APP_URL'], PHP_URL_HOST) ?: 'localhost';
            $config['MAIL']['FROM_EMAIL'] = "no-reply@{$host}";
            error_log(message: "Settings: FROM_EMAIL rỗng → dùng fallback: " . $config['MAIL']['FROM_EMAIL']);
        }

        if (empty($config['PAYOS']['RETURN_URL'])) {
            $config['PAYOS']['RETURN_URL'] = $config['APP_URL'] . '/payment/success';
        }
        if (empty($config['PAYOS']['CANCEL_URL'])) {
            $config['PAYOS']['CANCEL_URL'] = $config['APP_URL'] . '/payment/cancel';
        }

      $required = [
            'APP_URL', 'JWT_SECRET',
            'DB_HOST', 'DB_NAME',
            'PAYOS.CLIENT_ID', 'PAYOS.API_KEY', 'PAYOS.CHECKSUM_KEY'
        ];
        
        foreach ($required as $key) {
            $parts = explode('.', $key);
            $value = $config;
            $found = true;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    error_log("Settings: Thiếu giá trị bắt buộc: $key");
                    $found = false;
                    break;
                }
                $value = $value[$part];
            }
            if ($found && empty($value)) {
                error_log("Settings: Giá trị rỗng cho key bắt buộc: $key = '$value'");
            }
        }

        self::$config = $config;
        return self::$config;
    }

    public static function get(string $key, $default = null)
    {
        $config = self::load();
        $parts = explode('.', $key);
        $value = $config;
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
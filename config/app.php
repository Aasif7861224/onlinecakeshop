<?php

require_once __DIR__ . '/env.php';

if (!function_exists('app_config')) {
    function app_config()
    {
        static $config = null;

        if ($config === null) {
            $config = array(
                'app' => array(
                    'name' => env_value('APP_NAME', 'Cake Shop'),
                    'base_path' => env_value('APP_BASE_PATH', '/onlinecakeshop'),
                    'timezone' => env_value('APP_TIMEZONE', 'Asia/Kolkata'),
                ),
                'database' => array(
                    'host' => env_value('DB_HOST', 'localhost'),
                    'port' => (int) env_value('DB_PORT', 4306),
                    'username' => env_value('DB_USERNAME', 'root'),
                    'password' => env_value('DB_PASSWORD', ''),
                    'name' => env_value('DB_DATABASE', 'onlinecakeshop'),
                ),
                'security' => array(
                    'session_name' => env_value('SESSION_NAME', 'CAKE_SHOP_SESSION'),
                    'remember_cookie' => env_value('REMEMBER_COOKIE', 'cake_shop_remember'),
                    'remember_days' => (int) env_value('REMEMBER_DAYS', 30),
                ),
                'payment' => array(
                    'razorpay_key_id' => env_value('RAZORPAY_KEY_ID', env_value('RAZORPAY_KEY', '')),
                    'razorpay_key_secret' => env_value('RAZORPAY_KEY_SECRET', env_value('RAZORPAY_SECRET', '')),
                    'currency' => env_value('PAYMENT_CURRENCY', 'INR'),
                ),
                'mail' => array(
                    'enabled' => in_array(strtolower((string) env_value('MAIL_ENABLED', 'false')), array('1', 'true', 'yes', 'on'), true),
                    'host' => env_value('SMTP_HOST', env_value('MAIL_HOST', '')),
                    'port' => (int) env_value('SMTP_PORT', env_value('MAIL_PORT', 587)),
                    'username' => env_value('SMTP_USER', env_value('MAIL_USERNAME', '')),
                    'password' => env_value('SMTP_PASS', env_value('MAIL_PASSWORD', '')),
                    'encryption' => env_value('SMTP_ENCRYPTION', env_value('MAIL_ENCRYPTION', 'tls')),
                    'from_email' => env_value('MAIL_FROM_EMAIL', 'noreply@localhost'),
                    'from_name' => env_value('MAIL_FROM_NAME', env_value('APP_NAME', 'Cake Shop')),
                ),
                'defaults' => array(
                    'admin_username' => env_value('DEFAULT_ADMIN_USERNAME', 'admin'),
                    'admin_email' => env_value('DEFAULT_ADMIN_EMAIL', 'admin@cakeshop.local'),
                    'admin_password' => env_value('DEFAULT_ADMIN_PASSWORD', 'admin123'),
                ),
            );
        }

        return $config;
    }
}

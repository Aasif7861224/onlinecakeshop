<?php

if (!function_exists('app_config')) {
    function app_config()
    {
        static $config = null;

        if ($config === null) {
            $config = array(
                'app' => array(
                    'name' => 'Cake Shop',
                    'base_path' => '/onlinecakeshop',
                    'timezone' => 'Asia/Kolkata',
                ),
                'database' => array(
                    'host' => getenv('CAKE_SHOP_DB_HOST') ?: 'localhost',
                    'port' => (int) (getenv('CAKE_SHOP_DB_PORT') ?: 4306),
                    'username' => getenv('CAKE_SHOP_DB_USER') ?: 'root',
                    'password' => getenv('CAKE_SHOP_DB_PASSWORD') ?: '',
                    'name' => getenv('CAKE_SHOP_DB_NAME') ?: 'onlinecakeshop',
                ),
                'security' => array(
                    'session_name' => 'CAKE_SHOP_SESSION',
                    'remember_cookie' => 'cake_shop_remember',
                    'remember_days' => 30,
                ),
                'payment' => array(
                    'razorpay_key_id' => getenv('RAZORPAY_KEY_ID') ?: '',
                    'razorpay_key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '',
                    'currency' => 'INR',
                ),
                'mail' => array(
                    'enabled' => (bool) (getenv('MAIL_ENABLED') ?: false),
                    'host' => getenv('MAIL_HOST') ?: '',
                    'port' => (int) (getenv('MAIL_PORT') ?: 587),
                    'username' => getenv('MAIL_USERNAME') ?: '',
                    'password' => getenv('MAIL_PASSWORD') ?: '',
                    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
                    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@localhost',
                    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Cake Shop',
                ),
                'defaults' => array(
                    'admin_username' => 'admin',
                    'admin_email' => 'admin@cakeshop.local',
                    'admin_password' => 'admin123',
                ),
            );
        }

        return $config;
    }
}

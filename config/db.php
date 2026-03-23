<?php

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/schema.php';

if (!function_exists('db')) {
    function db()
    {
        static $connection = null;

        if ($connection instanceof mysqli) {
            return $connection;
        }

        $config = app_config();
        date_default_timezone_set($config['app']['timezone']);

        $serverConnection = new mysqli(
            $config['database']['host'],
            $config['database']['username'],
            $config['database']['password'],
            '',
            $config['database']['port']
        );

        if ($serverConnection->connect_error) {
            throw new RuntimeException('Database connection failed.');
        }

        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['database']['name']);
        $serverConnection->query("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $serverConnection->select_db($databaseName);
        $serverConnection->set_charset('utf8mb4');

        ensure_database_schema($serverConnection);

        $connection = $serverConnection;

        return $connection;
    }
}

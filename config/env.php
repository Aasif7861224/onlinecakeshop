<?php

if (!function_exists('load_env_file')) {
    function load_env_file($filePath)
    {
        if (!is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (!function_exists('env_value')) {
    function env_value($key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}

if (!function_exists('app_error_log_path')) {
    function app_error_log_path()
    {
        $logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        return $logDir . DIRECTORY_SEPARATOR . 'app-error.log';
    }
}

if (!function_exists('app_render_error_response')) {
    function app_render_error_response($message)
    {
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Something went wrong</title><style>body{font-family:Arial,sans-serif;background:#fff8ef;color:#221714;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}.card{max-width:520px;background:#fff;border-radius:20px;padding:32px;box-shadow:0 18px 40px rgba(90,45,39,.12)}h1{margin-top:0}p{line-height:1.6;color:#6e5a56}</style></head><body><div class="card"><h1>Something went wrong</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
    }
}

if (!function_exists('app_exception_handler')) {
    function app_exception_handler($exception)
    {
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL . PHP_EOL;
        file_put_contents(app_error_log_path(), $entry, FILE_APPEND);
        app_render_error_response('Something went wrong. Please try again.');
    }
}

if (!function_exists('app_shutdown_handler')) {
    function app_shutdown_handler()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
            $entry = '[' . date('Y-m-d H:i:s') . '] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL . PHP_EOL;
            file_put_contents(app_error_log_path(), $entry, FILE_APPEND);
            app_render_error_response('A fatal error occurred. Please try again.');
        }
    }
}

if (!function_exists('configure_app_error_handling')) {
    function configure_app_error_handling()
    {
        static $configured = false;
        if ($configured) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_exception_handler('app_exception_handler');
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        register_shutdown_function('app_shutdown_handler');

        $configured = true;
    }
}

load_env_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
load_env_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env.local');
configure_app_error_handling();

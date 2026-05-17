<?php

/**
 * Minimal CodeIgniter database bootstrap for model integration tests.
 */
class CiIntegrationBootstrap
{
    /** @var CI_DB_mysqli_driver|null */
    private static $db;

    public static function isConfigured(): bool
    {
        return (bool) getenv('TEST_DATABASE_HOST');
    }

    public static function database(): CI_DB_mysqli_driver
    {
        if (self::$db !== null) {
            return self::$db;
        }

        self::mapTestDatabaseEnv();
        self::defineCoreConstants();

        require_once BASEPATH . 'core/Common.php';
        require_once BASEPATH . 'database/DB.php';

        self::$db = DB('default', true);

        return self::$db;
    }

    public static function bookingModel(): IntegrationBookingModel
    {
        require_once __DIR__ . '/IntegrationBookingModel.php';

        $model = new IntegrationBookingModel();
        $model->setDatabase(self::database());

        return $model;
    }

    private static function mapTestDatabaseEnv(): void
    {
        $map = array(
            'TEST_DATABASE_HOST' => 'DATABASE_HOST',
            'TEST_DATABASE_USER' => 'DATABASE_USER',
            'TEST_DATABASE_PASS' => 'DATABASE_PASS',
            'TEST_DATABASE_NAME' => 'DATABASE_NAME',
        );

        foreach ($map as $from => $to) {
            $value = getenv($from);
            if ($value !== false && $value !== '') {
                putenv($to . '=' . $value);
                $_ENV[$to] = $value;
                $_SERVER[$to] = $value;
            }
        }

        putenv('ENVIRONMENT=testing');
        $_ENV['ENVIRONMENT'] = 'testing';
        $_SERVER['ENVIRONMENT'] = 'testing';
    }

    private static function defineCoreConstants(): void
    {
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'testing');
        }

        if (!defined('BASEPATH')) {
            define('BASEPATH', dirname(__DIR__, 2) . '/public/system/');
        }

        if (!defined('APPPATH')) {
            define('APPPATH', dirname(__DIR__, 2) . '/public/application/');
        }

        if (!defined('FCPATH')) {
            define('FCPATH', dirname(__DIR__, 2) . '/public/');
        }

        if (!function_exists('show_error')) {
            function show_error($message)
            {
                throw new RuntimeException($message);
            }
        }

        if (!function_exists('log_message')) {
            function log_message($level, $message)
            {
            }
        }

        if (!class_exists('CI_Model', false)) {
            require_once BASEPATH . 'core/Model.php';
        }
    }
}


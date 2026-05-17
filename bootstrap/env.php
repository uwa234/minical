<?php

/**
 * Load .env and expose variables to getenv() for legacy miniCal code.
 *
 * phpdotenv v5 no longer calls putenv() with createImmutable(); this project
 * still reads configuration via getenv() in many places.
 */
function minical_load_dotenv(string $projectRoot): void
{
    $autoload = $projectRoot . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return;
    }

    require_once $autoload;

    if (!is_file($projectRoot . '/.env')) {
        return;
    }

    Dotenv\Dotenv::createUnsafeImmutable($projectRoot)->load();
}

/**
 * Read an environment variable (works after minical_load_dotenv()).
 */
function minical_env(string $key, $default = '')
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    $value = getenv($key);

    return $value !== false ? $value : $default;
}

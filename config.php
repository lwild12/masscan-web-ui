<?php
declare(strict_types=1);

/**
 * General settings
 */
date_default_timezone_set('UTC');
set_time_limit(0);
ini_set('memory_limit', '-1');

/**
 * Debug / error reporting
 * Set APP_DEBUG=true in your environment to enable detailed errors.
 */
$app_debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $app_debug ? 'On' : 'Off');
error_reporting($app_debug ? E_ALL : E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);

/**
 * Database configuration
 * Set via environment variables (recommended) or edit the fallback defaults below.
 * For DB_DRIVER use 'mysql' for MySQL/MariaDB or 'pgsql' for PostgreSQL.
 */
define('DB_DRIVER',   getenv('DB_DRIVER')   ?: 'mysql');
define('DB_HOST',     getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'masscan');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'changem3');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'masscan');

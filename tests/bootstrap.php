<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

if (getenv('WP_TESTS_DIR')) {
    $testsDir = rtrim((string) getenv('WP_TESTS_DIR'), '/');
    $polyfillsDir = $root . '/vendor/yoast/phpunit-polyfills';

    if (!is_file($polyfillsDir . '/phpunitpolyfills-autoload.php')) {
        throw new RuntimeException(
            'The WordPress integration suite requires yoast/phpunit-polyfills. Run composer install first.'
        );
    }

    if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
        define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfillsDir);
    }

    require_once $testsDir . '/includes/functions.php';
    tests_add_filter('muplugins_loaded', static function () use ($root): void { require $root . '/acorn-safety-healthcheck.php'; });
    require $testsDir . '/includes/bootstrap.php';
}

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

if (getenv('WP_TESTS_DIR')) {
    $testsDir = rtrim((string) getenv('WP_TESTS_DIR'), '/');
    require_once $testsDir . '/includes/functions.php';
    tests_add_filter('muplugins_loaded', static function () use ($root): void { require $root . '/acorn-safety-healthcheck.php'; });
    require $testsDir . '/includes/bootstrap.php';
}

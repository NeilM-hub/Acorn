<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ReleaseVersionTest extends TestCase
{
    public function test_plugin_header_and_runtime_version_match_v1_0_2_zip(): void
    {
        $bootstrap = file_get_contents(dirname(__DIR__, 2) . '/acorn-safety-healthcheck.php');

        self::assertMatchesRegularExpression('/Version:\\s*1\\.0\\.2/', $bootstrap);
        self::assertStringContainsString("define('ACORN_HC_VERSION', '1.0.2')", $bootstrap);
        self::assertStringContainsString(
            'acorn-safety-healthcheck-1.0.2.zip',
            file_get_contents(dirname(__DIR__, 2) . '/.github/workflows/ci.yml')
        );
    }
}

<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ReleaseVersionTest extends TestCase
{
    public function test_plugin_header_runtime_and_release_zip_match_v1_2_3(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = file_get_contents($root . '/acorn-safety-healthcheck.php');
        $workflow = file_get_contents($root . '/.github/workflows/ci.yml');
        $build = file_get_contents($root . '/bin/build-zip.sh');
        $smoke = file_get_contents($root . '/bin/smoke-release-zip.sh');

        self::assertMatchesRegularExpression('/Version:\\s*1\\.2\\.2/', $bootstrap);
        self::assertStringContainsString("define('ACORN_HC_VERSION', '1.2.3')", $bootstrap);
        self::assertStringContainsString('acorn-safety-healthcheck-1.2.3.zip', $workflow);
        self::assertStringContainsString('acorn-safety-healthcheck-1.2.3', $workflow);
        self::assertStringContainsString('[[ "$version" == "1.2.3" ]]', $build);
        self::assertStringContainsString('acorn-safety-healthcheck-1.2.3.zip', $smoke);
    }
}

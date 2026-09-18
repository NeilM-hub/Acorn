<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Acorn\SafetyHealthcheck\Plugin;

final class PluginSmokeTest extends TestCase
{
    public function test_plugin_class_can_be_constructed(): void
    {
        $plugin = new Plugin();
        self::assertInstanceOf(Plugin::class, $plugin);
    }
}

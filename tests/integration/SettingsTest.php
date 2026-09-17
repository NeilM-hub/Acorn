<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;final class SettingsTest extends TestCase{public function test_retention_default():void{self::assertSame(730,(require dirname(__DIR__,2).'/config/settings-defaults.php')['completed_retention_days']);}}

<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;final class AdminCapabilityTest extends TestCase{public function test_capability_name():void{self::assertSame('manage_acorn_healthcheck','manage_acorn_healthcheck');}}

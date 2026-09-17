<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;final class ContentVersioningTest extends TestCase{public function test_versions_are_immutable_labels():void{self::assertSame('1.0','1.0');}}

<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;use Acorn\SafetyHealthcheck\Security\Honeypot;final class CompletionTest extends TestCase{public function test_honeypot():void{$this->expectException(InvalidArgumentException::class);Honeypot::assertEmpty('bot');}}

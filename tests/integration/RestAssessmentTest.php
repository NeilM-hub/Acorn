<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;final class RestAssessmentTest extends TestCase{public function test_namespace_is_stable():void{self::assertSame('acorn-healthcheck/v1','acorn-healthcheck/v1');}}

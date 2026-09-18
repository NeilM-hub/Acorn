<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;use Acorn\SafetyHealthcheck\Reports\ReportAccess;final class ReportAccessTest extends TestCase{public function test_unknown_token():void{self::assertNull((new ReportAccess)->findAssessmentId('unknown'));}}

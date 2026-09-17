<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;final class PdfGeneratorTest extends TestCase{public function test_dependency_is_declared():void{self::assertArrayHasKey('dompdf/dompdf',json_decode(file_get_contents(dirname(__DIR__,2).'/composer.json'),true)['require']);}}

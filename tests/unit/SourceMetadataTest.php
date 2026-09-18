<?php

declare(strict_types=1);use Acorn\SafetyHealthcheck\Content\SourceMetadata;use PHPUnit\Framework\TestCase;
final class SourceMetadataTest extends TestCase{public function test_jurisdiction_sources_use_official_regulators():void{self::assertStringContainsString('gov.scot',SourceMetadata::forQuestion('F02_FIRE_RA','scotland')['url']);self::assertStringContainsString('nifrs.org',SourceMetadata::forQuestion('F02_FIRE_RA','northern_ireland')['url']);self::assertStringContainsString('hseni.gov.uk',SourceMetadata::forQuestion('L01_LEGIONELLA_RA','northern_ireland')['url']);self::assertStringContainsString('hse.gov.uk/asbestos',SourceMetadata::forQuestion('AS01_ASBESTOS_INFORMATION','england')['url']);}}

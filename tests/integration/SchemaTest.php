<?php
declare(strict_types=1); use PHPUnit\Framework\TestCase; use Acorn\SafetyHealthcheck\Database\Schema;
final class SchemaTest extends TestCase {public function test_install_creates_tables():void {Schema::install();global $wpdb;foreach(['content_versions','questions','recommendations','assessments','answers','contacts'] as $s)self::assertSame(Schema::table($s),$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',Schema::table($s))));self::assertSame('1',get_option('acorn_hc_schema_version'));}}

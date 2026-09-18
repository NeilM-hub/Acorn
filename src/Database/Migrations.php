<?php
declare(strict_types=1); namespace Acorn\SafetyHealthcheck\Database;
final class Migrations { public static function run():void {if(get_option('acorn_hc_schema_version')!==Schema::VERSION)Schema::install();}}

<?php
declare(strict_types=1); namespace Acorn\SafetyHealthcheck;
use Acorn\SafetyHealthcheck\Database\Schema;
final class Activation {public static function activate():void {Schema::install();(new \Acorn\SafetyHealthcheck\Reports\ReportController())->registerRewriteRules();flush_rewrite_rules();if(class_exists(Content\SeedContent::class))Content\SeedContent::installIfEmpty();if(!wp_next_scheduled('acorn_hc_daily_cleanup'))wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','acorn_hc_daily_cleanup');$r=get_role('administrator');if($r)$r->add_cap('manage_acorn_healthcheck');}}

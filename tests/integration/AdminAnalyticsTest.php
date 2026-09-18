<?php
declare(strict_types=1);use Acorn\SafetyHealthcheck\Assessment\AssessmentService;use Acorn\SafetyHealthcheck\Database\Schema;
final class AdminAnalyticsTest extends IntegrationTestCase{public function test_funnel_counts_are_derived_from_database_state():void{(new AssessmentService)->start();global$wpdb;self::assertSame('1',$wpdb->get_var('SELECT COUNT(*) FROM '.Schema::table('assessments')));self::assertSame('0',$wpdb->get_var("SELECT COUNT(*) FROM ".Schema::table('assessments')." WHERE status='completed'"));}}

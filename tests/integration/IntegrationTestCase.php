<?php

declare(strict_types=1);
use Acorn\SafetyHealthcheck\Content\SeedContent;use Acorn\SafetyHealthcheck\Database\Schema;use Acorn\SafetyHealthcheck\Assessment\AssessmentService;
abstract class IntegrationTestCase extends WP_UnitTestCase
{
 public function set_up():void{parent::set_up();Schema::install();global$wpdb;foreach(['answers','assessments','contacts','recommendations','questions','content_versions']as$table)$wpdb->query('TRUNCATE TABLE '.Schema::table($table));SeedContent::installIfEmpty();}
 protected function profile(array$changes=[]):array{return array_merge(['jurisdiction'=>'england','employee_band'=>'1_4','sector'=>'office_professional','workplace_types'=>['office'],'premises_responsibility'=>'yes','shared_premises'=>'yes','risk_flags'=>['dse'],'water_system_responsibility'=>'no','maintenance_repair_responsibility'=>'no','building_pre_2000'=>'not_relevant','intrusive_work_planned'=>'no'],$changes);}
 protected function assessed():array{$service=new AssessmentService();$start=$service->start();$state=$service->updateProfile($start['token'],$this->profile());foreach($state->questions as$q)$service->saveAnswer($start['token'],$q['question_key'],'yes');$service->assess($start['token']);return[$service,$start['token']];}
}

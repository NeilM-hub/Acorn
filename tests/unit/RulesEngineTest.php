<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Acorn\SafetyHealthcheck\Domain\{RulesEngine, ApplicabilityContext};

final class RulesEngineTest extends TestCase
{
    private function keys(array $overrides): array
    {
        $profile = array_merge(['jurisdiction'=>'england','employee_band'=>'10_49','sector'=>'office_professional','workplace_types'=>['office'],'premises_responsibility'=>'yes','shared_premises'=>'yes','risk_flags'=>[],'water_system_responsibility'=>'no','maintenance_repair_responsibility'=>'no','building_pre_2000'=>'not_relevant','intrusive_work_planned'=>'no'], $overrides);
        $questions = require dirname(__DIR__, 2) . '/config/questions.php';
        foreach ($questions as &$q) $q['variant_json'] = json_encode($q['question_key'] === 'M02_POLICY' ? ['under_5'=>'small','5_plus'=>'large'] : []);
        return array_column((new RulesEngine())->applicableQuestions(ApplicabilityContext::fromProfile($profile), $questions), 'question_key');
    }
    public function test_specialist_modules_are_independent(): void { $keys=$this->keys(['risk_flags'=>['dse','work_at_height']]); self::assertContains('D01_DSE',$keys);self::assertContains('WAH01_WORK_AT_HEIGHT',$keys);self::assertNotContains('C01_COSHH',$keys); }
    public function test_legionella_includes_unclear_responsibility(): void { self::assertContains('L01_LEGIONELLA_RA',$this->keys(['water_system_responsibility'=>'not_sure'])); }
    public function test_asbestos_intrusive_question_is_conditional(): void { $base=['maintenance_repair_responsibility'=>'yes','building_pre_2000'=>'not_sure'];self::assertNotContains('AS04_ASBESTOS_INTRUSIVE',$this->keys($base));self::assertContains('AS04_ASBESTOS_INTRUSIVE',$this->keys(array_merge($base,['intrusive_work_planned'=>'yes']))); }
    public function test_home_only_without_responsibility_excludes_fire(): void { self::assertNotContains('F02_FIRE_RA',$this->keys(['workplace_types'=>['home_working'],'premises_responsibility'=>'no'])); }
    public function test_no_employees_excludes_people_questions(): void { self::assertNotContains('T01_INDUCTION',$this->keys(['employee_band'=>'none'])); }
}

<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Domain\ApplicabilityContext;
use PHPUnit\Framework\TestCase;

final class ProfileValidationTest extends TestCase
{
    public function test_at_least_one_workplace_type_is_required_but_risk_flags_may_be_empty(): void
    {
        $profile=['jurisdiction'=>'england','employee_band'=>'1_4','sector'=>'office_professional','workplace_types'=>['office'],'premises_responsibility'=>'yes','shared_premises'=>'no','risk_flags'=>[],'water_system_responsibility'=>'no','maintenance_repair_responsibility'=>'no','building_pre_2000'=>'not_relevant','intrusive_work_planned'=>'no'];
        self::assertSame([], ApplicabilityContext::fromProfile($profile)->profile['risk_flags']);
        $profile['workplace_types']=[];
        $this->expectException(InvalidArgumentException::class);
        ApplicabilityContext::fromProfile($profile);
    }
}

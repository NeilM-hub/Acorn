<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Content;

final class SourceMetadata
{
    private const SOURCES = [
        'competent' => ['title' => 'HSE: Getting competent help', 'url' => 'https://www.hse.gov.uk/simple-health-safety/gettinghelp/'],
        'policy' => ['title' => 'HSE: Prepare a health and safety policy', 'url' => 'https://www.hse.gov.uk/simple-health-safety/policy/'],
        'risk' => ['title' => 'HSE: Manage risks and risk assessment', 'url' => 'https://www.hse.gov.uk/simple-health-safety/risk/'],
        'consult' => ['title' => 'HSE: Consult your workers', 'url' => 'https://www.hse.gov.uk/simple-health-safety/consult.htm'],
        'training' => ['title' => 'HSE: Provide information and training', 'url' => 'https://www.hse.gov.uk/simple-health-safety/training/'],
        'first_aid' => ['title' => 'HSE: First aid at work', 'url' => 'https://www.hse.gov.uk/firstaid/what-employers-need-to-do.htm'],
        'riddor' => ['title' => 'HSE: RIDDOR', 'url' => 'https://www.hse.gov.uk/riddor/'],
        'workplace' => ['title' => 'HSE: Workplace facilities', 'url' => 'https://www.hse.gov.uk/simple-health-safety/workplace-facilities/'],
        'equipment' => ['title' => 'HSE: Provision and Use of Work Equipment Regulations', 'url' => 'https://www.hse.gov.uk/work-equipment-machinery/puwer.htm'],
        'coshh' => ['title' => 'HSE: COSHH assessment', 'url' => 'https://www.hse.gov.uk/coshh/basics/assessment.htm'],
        'dse' => ['title' => 'HSE: DSE workstation assessment', 'url' => 'https://www.hse.gov.uk/msd/dse/assessment.htm'],
        'manual' => ['title' => 'HSE: Manual handling', 'url' => 'https://www.hse.gov.uk/msd/manual-handling/'],
        'lone' => ['title' => 'HSE: Lone working', 'url' => 'https://www.hse.gov.uk/lone-working/'],
        'height' => ['title' => 'HSE: Work at height', 'url' => 'https://www.hse.gov.uk/work-at-height/'],
        'young' => ['title' => 'HSE: Young workers', 'url' => 'https://www.hse.gov.uk/young-workers/employer/'],
        'driving' => ['title' => 'HSE: Work-related road safety', 'url' => 'https://www.hse.gov.uk/work-related-road-safety/'],
        'fire_england' => ['title' => 'GOV.UK: Workplace fire safety', 'url' => 'https://www.gov.uk/workplace-fire-safety-your-responsibilities'],
        'fire_wales' => ['title' => 'Welsh Government: Fire safety guidance', 'url' => 'https://www.gov.wales/fire-safety-guidance-businesses-and-workplaces'],
        'fire_scotland' => ['title' => 'Scottish Government: Non-domestic fire safety', 'url' => 'https://www.gov.scot/policies/fire-and-rescue/non-domestic-fire-safety/'],
        'fire_northern_ireland' => ['title' => 'NIFRS: Fire risk assessments', 'url' => 'https://www.nifrs.org/home/staying-safe/business-fire-safety/fire-risk-assessments/'],
        'legionella_gb' => ['title' => 'HSE: Legionella workplace risks', 'url' => 'https://www.hse.gov.uk/legionnaires/workplace-risks.htm'],
        'legionella_northern_ireland' => ['title' => 'HSENI: Legionella', 'url' => 'https://www.hseni.gov.uk/topics/legionella'],
        'asbestos_gb' => ['title' => 'HSE: Duty to manage asbestos', 'url' => 'https://www.hse.gov.uk/asbestos/duty/'],
        'asbestos_northern_ireland' => ['title' => 'HSENI: Asbestos', 'url' => 'https://www.hseni.gov.uk/topics/asbestos'],
    ];

    public static function forQuestion(string $key, string $jurisdiction = '*'): array
    {
        if (str_starts_with($key, 'F')) return self::SOURCES['fire_' . ($jurisdiction === '*' ? 'england' : $jurisdiction)];
        if (str_starts_with($key, 'L')) return self::SOURCES[$jurisdiction === 'northern_ireland' ? 'legionella_northern_ireland' : 'legionella_gb'];
        if (str_starts_with($key, 'AS')) return self::SOURCES[$jurisdiction === 'northern_ireland' ? 'asbestos_northern_ireland' : 'asbestos_gb'];
        $map = ['M01'=>'competent','M02'=>'policy','M03'=>'policy','M04'=>'consult','R'=>'risk','T'=>'training','A01'=>'first_aid','A02'=>'riddor','A03'=>'riddor','W01'=>'workplace','W02'=>'equipment','C'=>'coshh','D'=>'dse','MH'=>'manual','LW'=>'lone','WAH'=>'height','YW'=>'young','DRV'=>'driving','CT'=>'risk'];
        foreach ($map as $prefix => $source) if (str_starts_with($key, $prefix)) return self::SOURCES[$source];
        return self::SOURCES['risk'];
    }
}

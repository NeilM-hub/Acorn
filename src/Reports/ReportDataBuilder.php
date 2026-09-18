<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Reports;

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, ContactRepository};
use RuntimeException;

final class ReportDataBuilder
{
    private const MAP = ['fire'=>'fire','legionella'=>'legionella','asbestos'=>'asbestos','management'=>'health_safety','risk'=>'health_safety','people'=>'health_safety','incidents'=>'health_safety','workplace'=>'health_safety','specialist'=>'health_safety'];

    public function build(int $id): array
    {
        $assessment = (new AssessmentRepository())->find($id);
        if (!$assessment || $assessment['status'] !== 'completed' || !$assessment['snapshot_json']) throw new RuntimeException('A completed report is required.');
        $snapshot = json_decode($assessment['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $contact = (new ContactRepository())->find((int) $assessment['contact_id']);
        $settings = array_merge(require dirname(__DIR__, 2) . '/config/settings-defaults.php', get_option('acorn_hc_settings', []));
        $logoId=(int)$settings['report_logo_attachment_id'];$logoPath=$logoId?get_attached_file($logoId):false;$logoData='';if($logoPath&&is_readable($logoPath)){$mime=mime_content_type($logoPath)?:'image/png';$logoData='data:'.$mime.';base64,'.base64_encode((string)file_get_contents($logoPath));}
        $groups = ['addressed'=>[], 'review'=>[], 'priority'=>[]];
        $pillarStatus = []; $rank = ['addressed'=>1, 'review'=>2, 'priority'=>3];
        foreach ($snapshot['findings'] as $finding) {
            $groups[$finding['finding_status']][] = $finding;
            $pillar = self::MAP[$finding['module_key']];
            if (!isset($pillarStatus[$pillar]) || $rank[$finding['finding_status']] > $rank[$pillarStatus[$pillar]]) $pillarStatus[$pillar] = $finding['finding_status'];
        }
        foreach ($groups as &$group) usort($group, fn(array $a, array $b): int => $a['sort_rank'] <=> $b['sort_rank']);
        $pillars = [];
        foreach (['health_safety'=>'Health & Safety','fire'=>'Fire Safety','legionella'=>'Legionella','asbestos'=>'Asbestos'] as $key=>$label) $pillars[]=['key'=>$key,'label'=>$label,'status'=>$pillarStatus[$key]??'not_assessed'];
        return [
            'meta'=>['company'=>$contact['company'],'assessment_date'=>substr($assessment['completed_at'],0,10),'jurisdiction'=>$assessment['jurisdiction'],'content_version'=>(string)$snapshot['content_version'],'areas_assessed'=>array_sum(array_map('count',$groups))],
            'branding'=>['logo_url'=>$logoId ? (string) wp_get_attachment_image_url($logoId,'full') : '','logo_data'=>$logoData,'phone'=>$settings['report_contact_phone'],'website'=>$settings['report_website'],'pdf_footer'=>$settings['pdf_footer']],
            'summary'=>$snapshot['summary'],'pillars'=>$pillars,
            'sections'=>['addressed'=>['label'=>'What appears to be working'],'review'=>['label'=>'Things worth checking'],'priority'=>['label'=>'Priority actions']],
            'addressed'=>$groups['addressed'],'review'=>$groups['review'],'priority'=>$groups['priority'],'action_summary'=>array_merge($groups['priority'],$groups['review']),
            'disclaimer'=>'This Healthcheck is based on information supplied through an online self-assessment. It is intended to highlight areas that may merit further review and does not constitute a formal audit, legal advice or confirmation of compliance.',
            'privacy_policy_url'=>$settings['privacy_policy_url'],
            'support'=>['heading'=>'Need help with any of the actions identified?','cta_label'=>'Request a free Health & Safety Compliance Audit','cta_url'=>$settings['audit_cta_url']],
        ];
    }
}

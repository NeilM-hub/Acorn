<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Reports;

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, ContactRepository};
use RuntimeException;

final class ReportDataBuilder
{
    private const MAP = [
        'fire' => 'fire',
        'legionella' => 'legionella',
        'asbestos' => 'asbestos',
        'management' => 'health_safety',
        'risk' => 'health_safety',
        'people' => 'health_safety',
        'incidents' => 'health_safety',
        'workplace' => 'health_safety',
        'specialist' => 'health_safety',
    ];

    public function build(int $id): array
    {
        $assessment = (new AssessmentRepository())->find($id);
        if (!$assessment || $assessment['status'] !== 'completed' || !$assessment['snapshot_json']) {
            throw new RuntimeException('A completed report is required.');
        }

        $snapshot = json_decode($assessment['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $contact = (new ContactRepository())->find((int) $assessment['contact_id']);
        $settings = array_merge(
            require dirname(__DIR__, 2) . '/config/settings-defaults.php',
            get_option('acorn_hc_settings', [])
        );

        $logoId = (int) ($settings['report_logo_attachment_id'] ?? 0);
        $defaultLogoUrl = (string) ($settings['report_logo_url'] ?? '');
        $logoUrl = $logoId ? (string) wp_get_attachment_image_url($logoId, 'full') : $defaultLogoUrl;

        if (!$logoId && $defaultLogoUrl !== '' && function_exists('attachment_url_to_postid')) {
            $logoId = (int) attachment_url_to_postid($defaultLogoUrl);
        }

        $logoPath = $logoId ? get_attached_file($logoId) : false;
        $logoData = '';
        if ($logoPath && is_readable($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoData = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        $groups = ['addressed' => [], 'review' => [], 'priority' => []];
        $pillarStatus = [];
        $rank = ['addressed' => 1, 'review' => 2, 'priority' => 3];

        foreach ($snapshot['findings'] as $finding) {
            $finding = $this->decorateFinding($finding);
            $groups[$finding['finding_status']][] = $finding;

            $pillar = self::MAP[$finding['module_key']];
            if (
                !isset($pillarStatus[$pillar])
                || $rank[$finding['finding_status']] > $rank[$pillarStatus[$pillar]]
            ) {
                $pillarStatus[$pillar] = $finding['finding_status'];
            }
        }

        foreach ($groups as &$group) {
            usort($group, static fn(array $a, array $b): int => $a['sort_rank'] <=> $b['sort_rank']);
        }
        unset($group);

        $pillars = [];
        foreach (
            [
                'health_safety' => 'Health & Safety',
                'fire' => 'Fire Safety',
                'legionella' => 'Legionella',
                'asbestos' => 'Asbestos',
            ] as $key => $label
        ) {
            $status = $pillarStatus[$key] ?? 'not_assessed';
            if (($snapshot['content_version'] ?? '') === '1.2' && $status === 'not_assessed') {
                continue;
            }
            $pillars[] = [
                'key' => $key,
                'label' => $label,
                'status' => $status,
                'display_status' => $this->statusLabel($status),
            ];
        }

        $summary = $snapshot['summary'];
        $topActions = array_slice(
            $groups['priority'] ?: $groups['review'],
            0,
            3
        );
        $supportOptions = $this->supportOptions(array_merge($groups['priority'], $groups['review']));

        return [
            'meta' => [
                'company' => $contact['company'],
                'assessment_date' => substr($assessment['completed_at'], 0, 10),
                'jurisdiction' => $assessment['jurisdiction'],
                'content_version' => (string) $snapshot['content_version'],
                'areas_assessed' => array_sum(array_map('count', $groups)),
            ],
            'branding' => [
                'logo_url' => $logoUrl,
                'logo_data' => $logoData,
                'horizontal_logo_url' => (string) ($settings['report_horizontal_logo_url'] ?? ''),
                'phone' => $settings['report_contact_phone'],
                'website' => $settings['report_website'],
                'pdf_footer' => $settings['pdf_footer'],
            ],
            'summary' => $summary,
            'executive' => [
                'summary_text' => $this->summaryText($summary),
                'priority_label' => $summary['priority_count'] === 1 ? 'Priority action' : 'Priority actions',
                'review_label' => $summary['review_count'] === 1 ? 'Review recommended' : 'Reviews recommended',
                'addressed_label' => $summary['addressed_count'] === 1 ? 'Area looking good' : 'Areas looking good',
                'top_actions' => $topActions,
            ],
            'pillars' => $pillars,
            'sections' => [
                'addressed' => ['label' => "What you're already doing well"],
                'review' => ['label' => 'Other things worth reviewing'],
                'priority' => ['label' => 'Your priority action plan'],
            ],
            'addressed' => $groups['addressed'],
            'review' => $groups['review'],
            'priority' => $groups['priority'],
            'action_summary' => array_merge($groups['priority'], $groups['review']),
            'next_steps' => [
                [
                    'title' => 'Tackle priority actions first',
                    'body' => 'Start with the priority actions in this report. Confirm what needs to change and deal with the most important gaps before moving on to lower-priority review items.',
                ],
                [
                    'title' => 'Give each action an owner',
                    'body' => 'Assign each action to a responsible person, agree what good looks like and record when the action has been completed.',
                ],
                [
                    'title' => 'Work through the review items',
                    'body' => 'Check the areas marked for review and confirm that the arrangements you already have are current, suitable and understood by the people who rely on them.',
                ],
                [
                    'title' => 'Keep the plan live',
                    'body' => 'Keep evidence of the action taken and review your arrangements when people, premises, equipment or the way you work changes.',
                ],
            ],
            'disclaimer' => 'This Healthcheck is based on information supplied through an online self-assessment. It highlights areas that may merit attention and does not constitute a formal audit, legal advice or confirmation of compliance.',
            'privacy_policy_url' => $settings['privacy_policy_url'],
            'support' => [
                'heading' => 'Want help turning this into an action plan?',
                'body' => 'Acorn Safety Services can review the areas highlighted in your Healthcheck, help you confirm the gaps and turn the findings into a practical action plan.',
                'options' => $supportOptions,
                'cta_label' => 'Request a free Health & Safety Compliance Audit',
                'cta_url' => $settings['audit_cta_url'],
                'secondary_cta_label' => 'Talk to us about ongoing Health & Safety support',
                'secondary_cta_url' => 'tel:' . preg_replace('/[^0-9+]/', '', (string) $settings['report_contact_phone']),
            ],
        ];
    }

    private function decorateFinding(array $finding): array
    {
        if ($finding['finding_status'] === 'addressed') {
            $heading = preg_replace(
                '/\s+appears to be addressed based on your answer\.?$/i',
                '',
                (string) ($finding['positive_text'] ?? '')
            );
            $finding['display_heading'] = ucfirst(trim((string) $heading));
            $finding['display_action'] = '';
            $finding['display_owner'] = '';
            $finding['display_priority'] = '';
            return $finding;
        }

        $recommendation = $finding['recommendation'] ?? [];
        $action = (string) ($recommendation['next_step_text'] ?? '');
        $action = preg_replace('/^Confirm the current position and who is responsible\.\s*Then:\s*/i', '', $action);
        $action = preg_replace('/^Confirm the current position and who is responsible\.\s*/i', '', (string) $action);

        $finding['display_heading'] = (string) ($recommendation['heading'] ?? '');
        $finding['display_action'] = trim((string) $action);
        $finding['display_identified'] = (string) ($recommendation['identified_text'] ?? '');
        $finding['display_why'] = (string) ($recommendation['why_text'] ?? '');
        $finding['display_good_looks'] = (string) ($recommendation['good_looks_text'] ?? '');
        $finding['display_owner'] = $this->ownerLabel((string) ($finding['module_key'] ?? ''));
        $finding['display_priority'] = $finding['finding_status'] === 'priority' ? 'Address first' : 'Review and confirm';
        $finding['display_acorn_help'] = $this->acornHelp(
            (string) ($finding['question_key'] ?? ''),
            (string) ($finding['module_key'] ?? '')
        );

        return $finding;
    }

    private function acornHelp(string $questionKey, string $module): string
    {
        return match ($questionKey) {
            'M01_COMPETENT_PERSON' => 'Acorn Safety Services can act as your external competent person, giving you practical ongoing support without the cost of employing a dedicated health and safety professional.',
            'F02_FIRE_RA' => 'Acorn Safety Services can complete or review your Fire Risk Assessment and help you turn any findings into a practical action plan.',
            'F06_FIRE_ARRANGEMENTS' => 'Acorn Safety Services can review your fire emergency arrangements, fire precautions and related inspection or maintenance requirements.',
            'L04_LEGIONELLA_MANAGEMENT' => 'Acorn Safety Services can carry out or review your Legionella risk assessment and help you put proportionate monitoring and control arrangements in place.',
            'AS05_ASBESTOS_MANAGEMENT' => 'Acorn can review your existing asbestos information, identify gaps and help you put suitable management arrangements in place before maintenance or refurbishment work is carried out.',
            'E01_EMPLOYERS_LIABILITY' => 'Acorn can help you review the wider health and safety management arrangements highlighted by this Healthcheck. Employers\' Liability cover itself should be confirmed with your insurer or broker.',
            default => match ($module) {
                'management', 'risk', 'people', 'incidents', 'workplace', 'specialist' => 'Acorn Safety Services can help you review and improve the relevant policies, risk assessments, training, records and management arrangements, with ongoing competent-person support where needed.',
                'fire' => 'Acorn Safety Services can review the relevant fire-safety arrangements and help you address the actions identified.',
                'legionella' => 'Acorn Safety Services can review the relevant Legionella arrangements and help you put suitable controls in place.',
                'asbestos' => 'Acorn can review the relevant asbestos information and management arrangements and help you address identified gaps.',
                default => 'Acorn Safety Services can help you review the issue and turn it into a practical action.',
            },
        };
    }

    private function supportOptions(array $findings): array
    {
        $areas = [];
        foreach ($findings as $finding) {
            $module = (string) ($finding['module_key'] ?? '');
            if (in_array($module, ['management', 'risk', 'people', 'incidents', 'workplace', 'specialist'], true)) {
                $areas['health_safety'] = true;
            } elseif (in_array($module, ['fire', 'legionella', 'asbestos'], true)) {
                $areas[$module] = true;
            }
        }

        $options = [];
        if (isset($areas['health_safety'])) {
            $options[] = [
                'key' => 'health_safety',
                'label' => 'Health & Safety support',
                'body' => 'Competent-person support, policies, risk assessments, training, inspections and practical help keeping your arrangements up to date.',
            ];
        }
        if (isset($areas['fire'])) {
            $options[] = [
                'key' => 'fire',
                'label' => 'Fire safety',
                'body' => 'Fire Risk Assessments, fire-safety reviews and practical support addressing actions and maintaining suitable arrangements.',
            ];
        }
        if (isset($areas['legionella'])) {
            $options[] = [
                'key' => 'legionella',
                'label' => 'Legionella',
                'body' => 'Legionella risk assessments, advice on responsibilities and support putting suitable monitoring and control measures in place.',
            ];
        }
        if (isset($areas['asbestos'])) {
            $options[] = [
                'key' => 'asbestos',
                'label' => 'Asbestos',
                'body' => 'Help reviewing existing asbestos information, identifying gaps and putting suitable survey or management arrangements in place.',
            ];
        }

        return $options;
    }

    private function ownerLabel(string $module): string
    {
        return match ($module) {
            'fire' => 'Responsible person / premises management',
            'legionella' => 'Premises / water-system responsible person',
            'asbestos' => 'Dutyholder / premises management',
            'people', 'incidents' => 'Management / HR',
            'workplace', 'specialist' => 'Management / responsible manager',
            default => 'Management',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'priority' => 'Priority action',
            'review' => 'Review recommended',
            'addressed' => 'No obvious gap',
            default => 'Not assessed',
        };
    }

    private function summaryText(array $summary): string
    {
        $priority = (int) ($summary['priority_count'] ?? 0);
        $review = (int) ($summary['review_count'] ?? 0);
        $addressed = (int) ($summary['addressed_count'] ?? 0);

        if ($priority > 0) {
            return sprintf(
                'Your responses highlight %d priority %s to address first, with %d further %s worth reviewing. %d %s showed no obvious gap.',
                $priority,
                $priority === 1 ? 'action' : 'actions',
                $review,
                $review === 1 ? 'area' : 'areas',
                $addressed,
                $addressed === 1 ? 'area' : 'areas'
            );
        }

        if ($review > 0) {
            return sprintf(
                'No priority actions were identified. %d %s worth reviewing, while %d %s showed no obvious gap.',
                $review,
                $review === 1 ? 'area is' : 'areas are',
                $addressed,
                $addressed === 1 ? 'area' : 'areas'
            );
        }

        return sprintf(
            'No obvious gaps were identified in the %d %s covered by this Healthcheck.',
            $addressed,
            $addressed === 1 ? 'area' : 'areas'
        );
    }
}

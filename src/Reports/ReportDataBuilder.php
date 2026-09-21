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
        $logoPath = $logoId ? get_attached_file($logoId) : false;
        $logoData = '';
        $logoUrl = $logoId ? (string) wp_get_attachment_image_url($logoId, 'full') : (string) ($settings['report_logo_url'] ?? '');

        if ($logoPath && is_readable($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoData = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        } elseif ($logoUrl !== '') {
            $logoData = $this->remoteImageData($logoUrl);
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
            'disclaimer' => 'This Healthcheck is based on information supplied through an online self-assessment. It highlights areas that may merit attention and does not constitute a formal audit, legal advice or confirmation of compliance.',
            'privacy_policy_url' => $settings['privacy_policy_url'],
            'support' => [
                'heading' => 'Want help turning this into an action plan?',
                'body' => 'Acorn Safety Services can review the areas highlighted in your Healthcheck and help you decide what needs attention first.',
                'cta_label' => 'Request a free Health & Safety Compliance Audit',
                'cta_url' => $settings['audit_cta_url'],
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

        return $finding;
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

    private function remoteImageData(string $url): string
    {
        $cacheKey = 'acorn_hc_logo_' . md5($url);
        $cached = get_transient($cacheKey);
        if (is_string($cached)) {
            return $cached === '__failed__' ? '' : $cached;
        }

        $response = wp_remote_get($url, ['timeout' => 2, 'redirection' => 2]);
        if (is_wp_error($response)) {
            set_transient($cacheKey, '__failed__', HOUR_IN_SECONDS);
            return '';
        }

        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '') {
            set_transient($cacheKey, '__failed__', HOUR_IN_SECONDS);
            return '';
        }

        $contentType = (string) wp_remote_retrieve_header($response, 'content-type');
        $mime = str_starts_with($contentType, 'image/') ? explode(';', $contentType)[0] : 'image/png';
        $data = 'data:' . $mime . ';base64,' . base64_encode($body);
        set_transient($cacheKey, $data, DAY_IN_SECONDS);

        return $data;
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

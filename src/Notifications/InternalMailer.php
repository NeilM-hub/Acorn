<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Notifications;

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, ContactRepository};
use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;

final class InternalMailer
{
    public function send(int $assessmentId, ?string $attachment = null): bool
    {
        $assessment = (new AssessmentRepository())->find($assessmentId);
        $contact = (new ContactRepository())->find((int) $assessment['contact_id']);
        $report = (new ReportDataBuilder())->build($assessmentId);
        $to = 'info@acornhealthandsafety.co.uk';
        $priorityCount = (int) ($assessment['priority_count'] ?? 0);
        $priorityLabel = $priorityCount === 1 ? 'Priority Action' : 'Priority Actions';

        $subject = sprintf(
            'New Healthcheck completed | %s | %d %s%s',
            $contact['company'],
            $priorityCount,
            $priorityLabel,
            $contact['audit_requested'] ? ' | Audit Requested' : ''
        );

        $data = [
            'assessment' => $assessment,
            'contact' => $contact,
            'report' => $report,
            'admin_url' => admin_url('admin.php?page=acorn-healthcheck-assessments&assessment=' . $assessmentId),
        ];

        ob_start();
        require ACORN_HC_DIR . 'templates/email-internal.php';
        $body = (string) ob_get_clean();

        return wp_mail(
            $to,
            $subject,
            $body,
            ['Content-Type: text/html; charset=UTF-8'],
            $attachment ? [$attachment] : []
        );
    }
}

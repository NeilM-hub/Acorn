<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Notifications;

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, ContactRepository};
use Acorn\SafetyHealthcheck\Reports\{PdfGenerator, ReportDataBuilder};
use Throwable;

final class CustomerMailer
{
    public function send(int $assessmentId, string $reportUrl, ?string $attachment = null): bool
    {
        $assessment = (new AssessmentRepository())->find($assessmentId);
        $contact = (new ContactRepository())->find((int) $assessment['contact_id']);
        $report = (new ReportDataBuilder())->build($assessmentId);
        $settings = get_option('acorn_hc_settings', []);
        $subject = $settings['customer_email_subject'] ?? 'Your Acorn Health & Safety Healthcheck';
        $data = $report;
        ob_start();
        require ACORN_HC_DIR . 'templates/email-customer.php';
        $body = (string) ob_get_clean();
        return wp_mail($contact['email'], $subject, $body, ['Content-Type: text/html; charset=UTF-8'], $attachment ? [$attachment] : []);
    }
}

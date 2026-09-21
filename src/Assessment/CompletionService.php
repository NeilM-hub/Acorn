<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Assessment;

use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Notifications\{CustomerMailer, InternalMailer};
use Acorn\SafetyHealthcheck\Reports\{PdfGenerator, ReportDataBuilder};
use Acorn\SafetyHealthcheck\Security\Honeypot;
use RuntimeException;
use Throwable;

final class CompletionService
{
    public function complete(string $token, array $payload): array
    {
        global $wpdb;
        Honeypot::assertEmpty((string) ($payload['website'] ?? ''));
        $assessment = (new AssessmentRepository())->findByToken($token);
        if (!$assessment || $assessment['status'] !== 'assessed') throw new RuntimeException('Assessment must be assessed first.');
        foreach (['first_name', 'last_name', 'company', 'email'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') throw new RuntimeException("Missing $field");
        }
        if (!is_email($payload['email'])) throw new RuntimeException('Invalid work email.');
        $audit = filter_var($payload['audit_requested'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($audit && (empty($payload['telephone']) || empty($payload['postcode']))) throw new RuntimeException('Telephone and postcode are required when an audit is requested.');

        $contactId = (new ContactRepository())->create([
            'first_name' => sanitize_text_field($payload['first_name']),
            'last_name' => sanitize_text_field($payload['last_name']),
            'company' => sanitize_text_field($payload['company']),
            'email' => sanitize_email($payload['email']),
            'telephone' => sanitize_text_field($payload['telephone'] ?? ''),
            'postcode' => sanitize_text_field($payload['postcode'] ?? ''),
            'audit_requested' => $audit ? 1 : 0,
            'marketing_consent' => !empty($payload['marketing_consent']) ? 1 : 0,
            'marketing_consent_at' => !empty($payload['marketing_consent']) ? current_time('mysql') : null,
            'created_at' => current_time('mysql'),
        ]);
        $rawReportToken = AssessmentRepository::token();
        $snapshot = (new SnapshotBuilder())->build((int) $assessment['id']);
        $wpdb->update(Schema::table('assessments'), [
            'status' => 'completed', 'contact_id' => $contactId,
            'report_token_hash' => hash('sha256', $rawReportToken),
            'snapshot_json' => wp_json_encode($snapshot, JSON_UNESCAPED_SLASHES),
            'completed_at' => current_time('mysql'),
        ], ['id' => $assessment['id']]);
        $reportUrl = home_url('/healthcheck/report/' . rawurlencode($rawReportToken) . '/');
        $emailErrors = []; $attachment = null;
        try {
            $attachment = (new PdfGenerator())->toTempFile((new ReportDataBuilder())->build((int) $assessment['id']));
            $wpdb->update(Schema::table('assessments'), ['pdf_status' => 'generated', 'pdf_last_error' => null], ['id' => $assessment['id']]);
        } catch (Throwable $error) {
            $wpdb->update(Schema::table('assessments'), ['pdf_status' => 'failed', 'pdf_last_error' => $error->getMessage()], ['id' => $assessment['id']]);
        }
        try { if (!(new CustomerMailer())->send((int) $assessment['id'], $reportUrl, $attachment)) $emailErrors['customer'] = 'wp_mail returned false'; } catch (Throwable $error) { $emailErrors['customer'] = $error->getMessage(); }
        try { if (!(new InternalMailer())->send((int) $assessment['id'])) $emailErrors['internal'] = 'wp_mail returned false'; } catch (Throwable $error) { $emailErrors['internal'] = $error->getMessage(); }
        (new PdfGenerator())->cleanup($attachment);
        $emailStatus = !$emailErrors ? 'sent' : ((isset($emailErrors['customer']) && isset($emailErrors['internal'])) ? 'failed' : 'partial');
        $wpdb->update(Schema::table('assessments'), ['email_status' => $emailStatus, 'email_last_error' => $emailErrors ? wp_json_encode($emailErrors) : null], ['id' => $assessment['id']]);
        do_action('acorn_healthcheck_completed', (int) $assessment['id']);
        return ['report_token' => $rawReportToken, 'report_url' => $reportUrl, 'assessment_id' => (int) $assessment['id']];
    }
}

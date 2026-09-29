<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Notifications;

use Acorn\SafetyHealthcheck\Assessment\AssessmentRepository;
use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Reports\{PdfGenerator, ReportDataBuilder};
use RuntimeException;
use Throwable;

final class ReportResender
{
    public function send(int $assessmentId, string $reportUrl): bool
    {
        $attachment = null;

        try {
            try {
                $attachment = (new PdfGenerator())->toTempFile(
                    (new ReportDataBuilder())->build($assessmentId)
                );
            } catch (Throwable $pdfError) {
                $attachment = null;
            }

            return (new CustomerMailer())->send($assessmentId, $reportUrl, $attachment);
        } finally {
            (new PdfGenerator())->cleanup($attachment);
        }
    }

    public function sendWithFreshToken(int $assessmentId): bool
    {
        global $wpdb;

        $assessment = (new AssessmentRepository())->find($assessmentId);
        if (!$assessment || ($assessment['status'] ?? '') !== 'completed') {
            throw new RuntimeException('A completed report is required.');
        }

        $previousHash = (string) ($assessment['report_token_hash'] ?? '');
        if ($previousHash === '') {
            throw new RuntimeException('The existing report link is unavailable.');
        }

        $rawToken = AssessmentRepository::token();
        $newHash = hash('sha256', $rawToken);
        $updated = $wpdb->update(
            Schema::table('assessments'),
            ['report_token_hash' => $newHash],
            ['id' => $assessmentId]
        );
        if ($updated === false) {
            throw new RuntimeException('A fresh report link could not be created.');
        }

        $reportUrl = home_url('/healthcheck/report/' . rawurlencode($rawToken) . '/');

        try {
            $sent = $this->send($assessmentId, $reportUrl);
            if (!$sent) {
                $this->restoreReportTokenHash($assessmentId, $previousHash);
                return false;
            }

            return true;
        } catch (Throwable $error) {
            $this->restoreReportTokenHash($assessmentId, $previousHash);
            throw $error;
        }
    }

    private function restoreReportTokenHash(int $assessmentId, string $previousHash): void
    {
        global $wpdb;

        $wpdb->update(
            Schema::table('assessments'),
            ['report_token_hash' => $previousHash],
            ['id' => $assessmentId]
        );
    }
}

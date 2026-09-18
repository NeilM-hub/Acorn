<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Notifications;

use Acorn\SafetyHealthcheck\Reports\{PdfGenerator, ReportDataBuilder};
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
            if ($attachment && is_file($attachment)) {
                unlink($attachment);
            }
        }
    }
}

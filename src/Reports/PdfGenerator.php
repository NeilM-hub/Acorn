<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Reports;

use Dompdf\Dompdf;
use RuntimeException;

final class PdfGenerator
{
    public function toTempFile(array $data): string
    {
        if (!class_exists(Dompdf::class)) throw new RuntimeException('PDF renderer unavailable.');

        $report = $data;
        ob_start();
        require ACORN_HC_DIR . 'templates/report-pdf.php';
        $html = (string) ob_get_clean();

        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        $path = apply_filters('acorn_hc_pdf_temp_path', wp_tempnam('acorn-healthcheck.pdf'), $data);
        if (!$path || !is_string($path) || file_put_contents($path, $pdf->output()) === false) {
            throw new RuntimeException('Could not create PDF.');
        }

        return $path;
    }

    public function stream(array $data, string $filename): void
    {
        $path = $this->toTempFile($data);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        readfile($path);
        unlink($path);
    }
}

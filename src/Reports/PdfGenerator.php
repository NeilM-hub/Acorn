<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Reports;

use Dompdf\Dompdf;
use RuntimeException;

final class PdfGenerator
{
    public function toTempFile(array $data): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new RuntimeException('PDF renderer unavailable.');
        }

        $report = $data;
        ob_start();
        require ACORN_HC_DIR . 'templates/report-pdf.php';
        $html = (string) ob_get_clean();

        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        $generatedPath = $this->attachmentPath($data);
        $path = apply_filters('acorn_hc_pdf_temp_path', $generatedPath, $data);

        if (!$path || !is_string($path)) {
            $this->cleanup($generatedPath);
            throw new RuntimeException('Could not create PDF.');
        }

        if (file_put_contents($path, $pdf->output()) === false) {
            $this->cleanup($path);
            if ($path !== $generatedPath) {
                $this->cleanup($generatedPath);
            }
            throw new RuntimeException('Could not create PDF.');
        }

        if ($path !== $generatedPath) {
            $this->cleanup($generatedPath);
        }

        return $path;
    }

    public function stream(array $data, string $filename): void
    {
        $path = $this->toTempFile($data);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        readfile($path);
        $this->cleanup($path);
    }

    public function cleanup(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (is_file($path)) {
            unlink($path);
        }

        $directory = dirname($path);
        if (str_starts_with(basename($directory), 'acorn-healthcheck-') && is_dir($directory)) {
            @rmdir($directory);
        }
    }

    private function attachmentPath(array $data): string
    {
        $marker = wp_tempnam('acorn-healthcheck');
        if (!$marker || !is_string($marker)) {
            throw new RuntimeException('Could not create PDF temp location.');
        }

        if (is_file($marker)) {
            unlink($marker);
        }

        $directory = $marker . '-files';
        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException('Could not create PDF temp directory.');
        }

        $company = trim((string) ($data['meta']['company'] ?? 'Report'));
        $filename = sanitize_file_name('Acorn-Safety-Healthcheck-' . $company . '.pdf');
        if ($filename === '' || !str_ends_with(strtolower($filename), '.pdf')) {
            $filename = 'Acorn-Safety-Healthcheck.pdf';
        }

        return trailingslashit($directory) . $filename;
    }
}

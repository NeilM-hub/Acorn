<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Reports\PdfGenerator;

final class PdfGeneratorTest extends IntegrationTestCase
{
    public function test_pdf_contains_real_pdf_signature(): void
    {
        $data = require dirname(__DIR__) . '/fixtures/canonical-report.php';
        $path = (new PdfGenerator())->toTempFile($data);

        try {
            self::assertSame('%PDF', file_get_contents($path, false, null, 0, 4));
            self::assertGreaterThan(1000, filesize($path));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

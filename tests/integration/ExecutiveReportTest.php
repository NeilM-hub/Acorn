<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentService, CompletionService};
use Acorn\SafetyHealthcheck\Content\ConciseContentUpgrade;
use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;

final class ExecutiveReportTest extends IntegrationTestCase
{
    public function test_report_data_is_condensed_for_an_executive_healthcheck(): void
    {
        ConciseContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], $this->profile());

        foreach ($state->questions as $index => $question) {
            $answer = $index === 0 ? 'no' : ($index === 1 ? 'partly' : 'yes');
            $service->saveAnswer($start['token'], $question['question_key'], $answer);
        }

        $service->assess($start['token']);
        add_filter('pre_wp_mail', '__return_true');
        $completed = (new CompletionService())->complete($start['token'], [
            'first_name' => 'Executive',
            'last_name' => 'Tester',
            'company' => 'Executive Report Ltd',
            'email' => 'executive@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $report = (new ReportDataBuilder())->build($completed['assessment_id']);

        self::assertArrayHasKey('executive', $report);
        self::assertNotEmpty($report['executive']['summary_text']);
        self::assertNotEmpty($report['priority'][0]['display_heading']);
        self::assertNotEmpty($report['priority'][0]['display_good_looks']);
        self::assertNotEmpty($report['review'][0]['display_heading']);
        self::assertStringNotContainsString('appears to be addressed based on your answer', strtolower($report['addressed'][0]['display_heading'] ?? ''));

        ob_start();
        require dirname(__DIR__, 2) . '/templates/report-pdf.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Your Healthcheck at a glance', $html);
        self::assertStringContainsString('Your priority action plan', $html);
        self::assertStringContainsString('What good looks like:', $html);
        self::assertStringContainsString('Other things worth reviewing', $html);
        self::assertStringContainsString("What you're already doing well", $html);
        self::assertStringContainsString('Request a free Health &amp; Safety Compliance Audit', $html);
        self::assertStringContainsString('href="https://acornhealthandsafety.co.uk/health-and-safety-compliance-audit/"', $html);
        self::assertStringNotContainsString('Action summary', $html);
    }
}

<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentService, CompletionService};
use Acorn\SafetyHealthcheck\Content\{ConciseContentUpgrade, SimplifiedContentUpgrade};
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

    public function test_v1_2_report_uses_acorn_safety_branding_and_action_focussed_layout(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], [
            'jurisdiction' => 'england',
            'employee_band' => '10_49',
            'fire_safety_responsibility' => 'yes',
            'water_system_responsibility' => 'yes',
            'asbestos_responsibility' => 'yes',
            'risk_flags' => ['dse', 'contractors'],
        ]);

        $answers = [
            'M01_COMPETENT_PERSON' => 'no',
            'R02_ACTION_REVIEW' => 'partly',
            'F02_FIRE_RA' => 'no',
            'F06_FIRE_ARRANGEMENTS' => 'no',
            'L04_LEGIONELLA_MANAGEMENT' => 'not_sure',
            'AS05_ASBESTOS_MANAGEMENT' => 'no',
            'S01_SELECTED_RISK_CONTROLS' => 'not_sure',
            'E01_EMPLOYERS_LIABILITY' => 'not_sure',
        ];

        foreach ($state->questions as $question) {
            $service->saveAnswer(
                $start['token'],
                $question['question_key'],
                $answers[$question['question_key']] ?? 'yes'
            );
        }

        $service->assess($start['token']);
        add_filter('pre_wp_mail', '__return_true');
        $completed = (new CompletionService())->complete($start['token'], [
            'first_name' => 'Report',
            'last_name' => 'Tester',
            'company' => 'Acorn Analytical Services',
            'email' => 'report-v12@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $report = (new ReportDataBuilder())->build($completed['assessment_id']);

        self::assertSame(
            'https://acornhealthandsafety.co.uk/wp-content/uploads/2019/04/Final-Small.png',
            $report['branding']['logo_url']
        );
        self::assertSame(
            'https://acornhealthandsafety.co.uk/wp-content/uploads/2020/03/Acorn-Safety-Logo-1-Small.png',
            $report['branding']['horizontal_logo_url']
        );
        self::assertCount(3, $report['executive']['top_actions']);
        self::assertSame('Competent health and safety support', $report['executive']['top_actions'][0]['display_heading']);
        self::assertNotEmpty($report['priority'][0]['display_owner']);
        self::assertSame('Address first', $report['priority'][0]['display_priority']);
        self::assertStringNotContainsString('Then:', implode(' ', array_column($report['review'], 'display_action')));
        self::assertStringNotContainsString(
            'Confirm the current position and who is responsible.',
            implode(' ', array_column($report['review'], 'display_action'))
        );

        self::assertSame(
            ['Tackle priority actions first', 'Give each action an owner', 'Work through the review items', 'Keep the plan live'],
            array_column($report['next_steps'], 'title')
        );
        self::assertNotEmpty($report['priority'][0]['display_acorn_help']);

        $supportLabels = array_column($report['support']['options'], 'label');
        self::assertContains('Health & Safety support', $supportLabels);
        self::assertContains('Fire safety', $supportLabels);
        self::assertContains('Legionella', $supportLabels);
        self::assertContains('Asbestos', $supportLabels);
        self::assertSame('Request a free Health & Safety Compliance Audit', $report['support']['cta_label']);
        self::assertSame('Speak to an Acorn specialist', $report['support']['secondary_cta_label']);
        self::assertStringStartsWith('tel:', $report['support']['secondary_cta_url']);
        self::assertSame("Don't leave the gaps sitting on a page.", $report['support']['heading']);
        self::assertSame('One specialist team. Four critical compliance disciplines.', $report['support']['authority_heading']);
        self::assertCount(3, $report['support']['authority_points']);

        ob_start();
        require dirname(__DIR__, 2) . '/templates/report-pdf.php';
        $pdfHtml = (string) ob_get_clean();

        self::assertStringContainsString('What needs your attention first', $pdfHtml);
        self::assertStringContainsString('Do this next', $pdfHtml);
        self::assertStringContainsString('Suggested owner', $pdfHtml);
        self::assertStringContainsString('Prepared by Acorn Safety Services', $pdfHtml);
        self::assertStringContainsString('class="cover-hero"', $pdfHtml);
        self::assertStringContainsString('Your Healthcheck is complete.', $pdfHtml);
        self::assertStringContainsString('Now turn the findings into action.', $pdfHtml);
        self::assertStringContainsString('Specialist guidance across Health &amp; Safety, Fire Safety, Legionella and Asbestos.', $pdfHtml);
        self::assertStringContainsString('How Acorn can help', $pdfHtml);
        self::assertStringContainsString('What should you do next?', $pdfHtml);
        self::assertStringContainsString('How Acorn Safety Services can help', $pdfHtml);
        self::assertStringContainsString('Don&#039;t leave the gaps sitting on a page.', $pdfHtml);
        self::assertStringContainsString('One specialist team. Four critical compliance disciplines.', $pdfHtml);
        self::assertStringContainsString('Speak to an Acorn specialist', $pdfHtml);
        self::assertStringNotContainsString('section-break', $pdfHtml);

        $data = $report;
        $reportUrl = 'https://example.test/secure-report/';
        ob_start();
        require dirname(__DIR__, 2) . '/templates/email-customer.php';
        $emailHtml = (string) ob_get_clean();

        self::assertStringContainsString('Acorn-Safety-Logo-1-Small.png', $emailHtml);
        self::assertStringContainsString('Your first priority', $emailHtml);
        self::assertStringContainsString('Competent health and safety support', $emailHtml);
    }


    public function test_v1_2_pdf_uses_full_width_for_a_single_applicable_pillar(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], [
            'jurisdiction' => 'england',
            'employee_band' => 'none',
            'fire_safety_responsibility' => 'no',
            'water_system_responsibility' => 'no',
            'asbestos_responsibility' => 'no',
            'risk_flags' => [],
        ]);

        foreach ($state->questions as $question) {
            $service->saveAnswer($start['token'], $question['question_key'], 'yes');
        }

        $service->assess($start['token']);
        add_filter('pre_wp_mail', '__return_true');
        $completed = (new CompletionService())->complete($start['token'], [
            'first_name' => 'Single',
            'last_name' => 'Pillar',
            'company' => 'Single Pillar Ltd',
            'email' => 'single-pillar@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $report = (new ReportDataBuilder())->build($completed['assessment_id']);
        self::assertCount(1, $report['pillars']);

        ob_start();
        require dirname(__DIR__, 2) . '/templates/report-pdf.php';
        $pdfHtml = (string) ob_get_clean();

        self::assertStringContainsString('class="pillar-wide"', $pdfHtml);
        self::assertStringNotContainsString('class="pillar-empty"', $pdfHtml);
        self::assertStringNotContainsString('Your priority action plan', $pdfHtml);
        self::assertStringNotContainsString('Other things worth reviewing', $pdfHtml);
        self::assertStringContainsString('class="support support--full-page"', $pdfHtml);
    }

}

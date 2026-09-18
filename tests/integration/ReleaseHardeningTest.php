<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, CompletionService};
use Acorn\SafetyHealthcheck\Activation;
use Acorn\SafetyHealthcheck\Admin\ContentPage;
use Acorn\SafetyHealthcheck\Content\{ContentPublisher, ContentVersionRepository, QuestionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Notifications\ReportResender;
use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;

final class ReleaseHardeningTest extends IntegrationTestCase
{
    public function test_pdf_failure_does_not_change_successful_email_status(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        add_filter('acorn_hc_pdf_temp_path', '__return_false');

        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Failure Recovery Ltd',
            'email' => 'pdf-failure@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);

        remove_filter('acorn_hc_pdf_temp_path', '__return_false');
        remove_filter('pre_wp_mail', '__return_true');

        $assessment = (new AssessmentRepository())->find($result['assessment_id']);
        self::assertSame('completed', $assessment['status']);
        self::assertSame('failed', $assessment['pdf_status']);
        self::assertSame('sent', $assessment['email_status']);
        self::assertNotEmpty($assessment['pdf_last_error']);
    }

    public function test_customer_and_internal_mail_failures_are_recorded_independently(): void
    {
        $calls = 0;
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$calls) {
            $calls++;
            return $calls === 1 ? false : true;
        }, 10, 2);

        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Mail Recovery Ltd',
            'email' => 'mail-failure@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);

        remove_all_filters('pre_wp_mail');

        $assessment = (new AssessmentRepository())->find($result['assessment_id']);
        $errors = json_decode((string) $assessment['email_last_error'], true);
        self::assertSame('partial', $assessment['email_status']);
        self::assertArrayHasKey('customer', $errors);
        self::assertArrayNotHasKey('internal', $errors);
    }

    public function test_internal_mail_failure_is_recorded_without_marking_customer_mail_failed(): void
    {
        $calls = 0;
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$calls) {
            $calls++;
            return $calls === 2 ? false : true;
        }, 10, 2);

        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Internal Mail Recovery Ltd',
            'email' => 'internal-failure@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);

        remove_all_filters('pre_wp_mail');

        $assessment = (new AssessmentRepository())->find($result['assessment_id']);
        $errors = json_decode((string) $assessment['email_last_error'], true);
        self::assertSame('partial', $assessment['email_status']);
        self::assertArrayNotHasKey('customer', $errors);
        self::assertArrayHasKey('internal', $errors);
    }

    public function test_report_resender_attaches_pdf_when_available(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Resend Ltd',
            'email' => 'resend-service@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $mail = [];
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$mail) {
            $mail = $atts;
            $mail['attachment_exists'] = !empty($atts['attachments'][0]) && is_file($atts['attachments'][0]);
            return true;
        }, 10, 2);

        self::assertTrue((new ReportResender())->send($result['assessment_id'], $result['report_url']));
        remove_all_filters('pre_wp_mail');

        self::assertTrue($mail['attachment_exists']);
        self::assertCount(1, $mail['attachments']);
        self::assertSame('resend-service@example.test', $mail['to']);
    }

    public function test_report_resender_sends_without_attachment_if_pdf_generation_fails(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Resend PDF Failure Ltd',
            'email' => 'resend-no-pdf@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        add_filter('acorn_hc_pdf_temp_path', '__return_false');
        $mail = [];
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$mail) {
            $mail = $atts;
            return true;
        }, 10, 2);

        self::assertTrue((new ReportResender())->send($result['assessment_id'], $result['report_url']));

        remove_all_filters('pre_wp_mail');
        remove_filter('acorn_hc_pdf_temp_path', '__return_false');

        self::assertSame([], $mail['attachments']);
    }

    public function test_failed_admin_fresh_link_resend_restores_previous_report_token(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Admin Resend Failure Ltd',
            'email' => 'admin-resend-failure@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $repository = new AssessmentRepository();
        $before = $repository->find($result['assessment_id']);
        $oldHash = $before['report_token_hash'];

        add_filter('pre_wp_mail', '__return_false');
        self::assertFalse((new ReportResender())->sendWithFreshToken($result['assessment_id']));
        remove_filter('pre_wp_mail', '__return_false');

        $after = $repository->find($result['assessment_id']);
        self::assertSame($oldHash, $after['report_token_hash']);
        self::assertSame(
            $result['assessment_id'],
            (new \Acorn\SafetyHealthcheck\Reports\ReportAccess())->findAssessmentId($result['report_token'])
        );
    }

    public function test_successful_admin_fresh_link_resend_rotates_report_token(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Admin Resend Success Ltd',
            'email' => 'admin-resend-success@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $repository = new AssessmentRepository();
        $oldHash = $repository->find($result['assessment_id'])['report_token_hash'];

        $mail = [];
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$mail) {
            $mail = $atts;
            return true;
        }, 10, 2);

        self::assertTrue((new ReportResender())->sendWithFreshToken($result['assessment_id']));
        remove_all_filters('pre_wp_mail');

        $newHash = $repository->find($result['assessment_id'])['report_token_hash'];
        self::assertNotSame($oldHash, $newHash);
        self::assertStringContainsString('/healthcheck/report/', $mail['message']);
        self::assertNull((new \Acorn\SafetyHealthcheck\Reports\ReportAccess())->findAssessmentId($result['report_token']));
    }

    public function test_draft_question_editor_persists_full_question_controls(): void
    {
        global $wpdb;

        $publisher = new ContentPublisher();
        $draft = $publisher->createDraftFromPublished(1);

        $publisher->updateDraftQuestion($draft, 'M02_POLICY', [
            'question_text' => 'Updated question?',
            'help_text' => 'Updated help.',
            'module_key' => 'management',
            'sort_order' => 777,
            'is_active' => false,
            'variants' => [
                'under_5' => 'Small organisation wording?',
                '5_plus' => 'Larger organisation wording?',
            ],
            'applicability' => ['all' => []],
            'source' => [
                'default' => ['title' => 'Default source', 'url' => 'https://example.test/default'],
                'jurisdictions' => [
                    'scotland' => ['title' => 'Scotland source', 'url' => 'https://example.test/scotland'],
                ],
            ],
            'reviewed_by' => 'Named reviewer',
            'last_reviewed' => '2026-09-18',
            'next_review' => '2027-09-18',
        ]);

        $question = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . Schema::table('questions') . ' WHERE content_version_id=%d AND question_key=%s',
            $draft,
            'M02_POLICY'
        ), ARRAY_A);

        self::assertSame('777', (string) $question['sort_order']);
        self::assertSame('0', (string) $question['is_active']);
        self::assertSame('Larger organisation wording?', json_decode($question['variant_json'], true)['5_plus']);
        $source = json_decode($question['source_json'], true);
        self::assertSame('Default source', $source['default']['title']);
        self::assertSame('Scotland source', $source['jurisdictions']['scotland']['title']);
    }

    public function test_content_admin_exposes_full_draft_controls_and_inactive_questions(): void
    {
        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);
        Activation::activate();

        $publisher = new ContentPublisher();
        $draft = $publisher->createDraftFromPublished($admin);
        $question = (new QuestionRepository())->get($draft, 'M02_POLICY');
        $publisher->updateDraftQuestion($draft, 'M02_POLICY', [
            'question_text' => $question['question_text'],
            'help_text' => $question['help_text'],
            'module_key' => $question['module_key'],
            'sort_order' => $question['sort_order'],
            'is_active' => false,
            'variants' => json_decode($question['variant_json'], true),
            'applicability' => json_decode($question['applicability_json'], true),
            'source' => json_decode($question['source_json'], true),
            'reviewed_by' => $question['reviewed_by'],
            'last_reviewed' => $question['last_reviewed'],
            'next_review' => $question['next_review'],
        ]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => 'acorn-healthcheck-content', 'question' => 'M02_POLICY'];

        ob_start();
        (new ContentPage())->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('value="M02_POLICY"', $html);
        self::assertStringContainsString('name="sort_order"', $html);
        self::assertStringContainsString('name="is_active"', $html);
        self::assertStringContainsString('name="variant_json"', $html);
        self::assertStringContainsString('name="source_jurisdictions_json"', $html);
        self::assertStringContainsString('value="create_recommendation"', $html);
    }

    public function test_historical_completed_report_survives_later_content_publish(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Historic',
            'last_name' => 'Tester',
            'company' => 'Historic Ltd',
            'email' => 'historic@example.test',
            'audit_requested' => false,
        ]);
        remove_filter('pre_wp_mail', '__return_true');

        $before = (new ReportDataBuilder())->build($result['assessment_id']);

        global $wpdb;
        $publisher = new ContentPublisher();
        $draft = $publisher->createDraftFromPublished(1);
        $wpdb->update(Schema::table('questions'), [
            'reviewed_by' => 'Named reviewer',
            'last_reviewed' => '2026-09-18',
            'next_review' => '2027-09-18',
        ], ['content_version_id' => $draft]);
        $wpdb->update(Schema::table('questions'), [
            'question_text' => 'Completely different future wording?',
        ], ['content_version_id' => $draft, 'question_key' => 'M01_COMPETENT_PERSON']);
        $publisher->publish($draft, 1);

        $after = (new ReportDataBuilder())->build($result['assessment_id']);
        self::assertSame($before, $after);
    }

    public function test_draft_recommendation_variants_can_be_created_and_removed(): void
    {
        global $wpdb;

        $publisher = new ContentPublisher();
        $draft = $publisher->createDraftFromPublished(1);

        $id = $publisher->createDraftRecommendation($draft, [
            'question_key' => 'M01_COMPETENT_PERSON',
            'answer_value' => 'no',
            'jurisdiction' => 'scotland',
            'finding_status' => 'priority',
            'heading' => 'Scotland variant',
            'identified_text' => 'Identified.',
            'next_step_text' => 'Next step.',
            'why_text' => 'Why.',
            'good_looks_text' => 'Good looks.',
            'service_tags' => ['health_safety'],
            'source' => ['title' => 'Source', 'url' => 'https://example.test/source'],
            'sort_rank' => 10,
        ]);

        self::assertGreaterThan(0, $id);
        self::assertSame('1', (string) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE id=%d',
            $id
        )));

        $publisher->deleteDraftRecommendation($draft, $id);

        self::assertSame('0', (string) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE id=%d',
            $id
        )));
    }
}

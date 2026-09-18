<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, CompletionService};
use Acorn\SafetyHealthcheck\Content\{ContentPublisher, ContentVersionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;

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

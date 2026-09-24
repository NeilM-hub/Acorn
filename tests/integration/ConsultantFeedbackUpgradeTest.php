<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, AssessmentService};
use Acorn\SafetyHealthcheck\Content\{
    ChecklistCoverageUpgrade,
    ConciseContentUpgrade,
    ConsultantFeedbackUpgrade,
    ContentVersionRepository,
    QuestionRepository,
    SimplifiedContentUpgrade
};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ConsultantFeedbackUpgradeTest extends IntegrationTestCase
{
    public function test_upgrade_makes_first_aiders_fire_wardens_and_law_poster_explicit(): void
    {
        global $wpdb;

        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();
        ChecklistCoverageUpgrade::installIfNeeded();

        $versions = new ContentVersionRepository();
        $questions = new QuestionRepository();
        $v121 = $versions->getPublished();

        self::assertSame('1.2.1', $v121['version_key']);

        $before = [];
        foreach ($questions->forVersion((int) $v121['id']) as $question) {
            $before[$question['question_key']] = $question;
        }

        ConsultantFeedbackUpgrade::installIfNeeded();

        $live = $versions->getPublished();
        self::assertSame('1.2.2', $live['version_key']);
        self::assertSame('retired', (string) $wpdb->get_var($wpdb->prepare(
            'SELECT status FROM ' . Schema::table('content_versions') . ' WHERE id=%d',
            $v121['id']
        )));

        $after = [];
        foreach ($questions->forVersion((int) $live['id']) as $question) {
            $after[$question['question_key']] = $question;
        }

        self::assertCount(16, $after);
        self::assertStringContainsString('trained first-aiders', $after['A01_FIRST_AID']['question_text']);
        self::assertStringContainsString('trained fire wardens or marshals', $after['F06_FIRE_ARRANGEMENTS']['question_text']);
        self::assertStringContainsString('Health and Safety Law poster', $after['E02_LAW_INFORMATION']['question_text']);

        foreach ($before as $key => $question) {
            self::assertSame($question['applicability_json'], $after[$key]['applicability_json']);
            self::assertSame($question['answer_rules_json'], $after[$key]['answer_rules_json']);
        }

        self::assertStringNotContainsString('trained first-aiders', $before['A01_FIRST_AID']['question_text']);
        self::assertStringNotContainsString('trained fire wardens or marshals', $before['F06_FIRE_ARRANGEMENTS']['question_text']);
        self::assertStringNotContainsString('Health and Safety Law poster', $before['E02_LAW_INFORMATION']['question_text']);
    }

    public function test_existing_v1_2_1_assessment_stays_on_old_content_and_new_assessment_uses_v1_2_2(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();
        ChecklistCoverageUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $before = $service->start();
        $beforeAssessment = (new AssessmentRepository())->findByToken($before['token']);

        ConsultantFeedbackUpgrade::installIfNeeded();

        $after = $service->start();
        $afterAssessment = (new AssessmentRepository())->findByToken($after['token']);
        $versions = new ContentVersionRepository();

        self::assertSame('1.2.1', $versions->find((int) $beforeAssessment['content_version_id'])['version_key']);
        self::assertSame('1.2.2', $versions->find((int) $afterAssessment['content_version_id'])['version_key']);
    }
}

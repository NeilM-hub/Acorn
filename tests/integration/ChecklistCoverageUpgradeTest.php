<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, AssessmentService};
use Acorn\SafetyHealthcheck\Content\{ChecklistCoverageUpgrade, ConciseContentUpgrade, ContentVersionRepository, QuestionRepository, SimplifiedContentUpgrade};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ChecklistCoverageUpgradeTest extends IntegrationTestCase
{
    public function test_upgrade_creates_v1_2_1_without_mutating_v1_2_logic(): void
    {
        global $wpdb;

        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $versions = new ContentVersionRepository();
        $questions = new QuestionRepository();
        $v12 = $versions->getPublished();

        self::assertSame('1.2', $v12['version_key']);

        $before = [];
        foreach ($questions->forVersion((int) $v12['id']) as $question) {
            $before[$question['question_key']] = $question;
        }

        ChecklistCoverageUpgrade::installIfNeeded();

        $live = $versions->getPublished();
        self::assertSame('1.2.1', $live['version_key']);
        self::assertSame('retired', (string) $wpdb->get_var($wpdb->prepare(
            'SELECT status FROM ' . Schema::table('content_versions') . ' WHERE id=%d',
            $v12['id']
        )));

        $after = [];
        foreach ($questions->forVersion((int) $live['id']) as $question) {
            $after[$question['question_key']] = $question;
        }

        self::assertCount(16, $after);
        self::assertStringContainsString('refresher or update training', $after['P01_TRAINING_INDUCTION']['help_text']);
        self::assertStringContainsString('fire wardens or marshals', $after['F06_FIRE_ARRANGEMENTS']['help_text']);

        foreach ($before as $key => $question) {
            self::assertSame($question['question_text'], $after[$key]['question_text']);
            self::assertSame($question['applicability_json'], $after[$key]['applicability_json']);
            self::assertSame($question['answer_rules_json'], $after[$key]['answer_rules_json']);
        }

        self::assertStringNotContainsString('refresher or update training', $before['P01_TRAINING_INDUCTION']['help_text']);
        self::assertStringNotContainsString('fire wardens or marshals', $before['F06_FIRE_ARRANGEMENTS']['help_text']);
    }

    public function test_existing_v1_2_assessment_stays_on_old_content_and_new_assessment_uses_v1_2_1(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $before = $service->start();
        $beforeAssessment = (new AssessmentRepository())->findByToken($before['token']);

        ChecklistCoverageUpgrade::installIfNeeded();

        $after = $service->start();
        $afterAssessment = (new AssessmentRepository())->findByToken($after['token']);
        $versions = new ContentVersionRepository();

        self::assertSame('1.2', $versions->find((int) $beforeAssessment['content_version_id'])['version_key']);
        self::assertSame('1.2.1', $versions->find((int) $afterAssessment['content_version_id'])['version_key']);
    }
}

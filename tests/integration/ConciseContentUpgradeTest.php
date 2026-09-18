<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\AssessmentService;
use Acorn\SafetyHealthcheck\Content\{ConciseContentUpgrade, ContentVersionRepository, QuestionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ConciseContentUpgradeTest extends IntegrationTestCase
{
    public function test_upgrade_creates_versioned_concise_content_without_rewriting_v1(): void
    {
        global $wpdb;

        $liveBefore = (new ContentVersionRepository())->getPublished();
        self::assertSame('1.0', $liveBefore['version_key']);

        $oldQuestion = (new QuestionRepository())->get((int) $liveBefore['id'], 'M01_COMPETENT_PERSON');
        $oldRecommendationCount = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d',
            $liveBefore['id']
        ));

        ConciseContentUpgrade::installIfNeeded();

        $liveAfter = (new ContentVersionRepository())->getPublished();
        self::assertSame('1.1', $liveAfter['version_key']);
        self::assertSame(
            'retired',
            (string) $wpdb->get_var($wpdb->prepare(
                'SELECT status FROM ' . Schema::table('content_versions') . ' WHERE id=%d',
                $liveBefore['id']
            ))
        );

        $newQuestion = (new QuestionRepository())->get((int) $liveAfter['id'], 'M01_COMPETENT_PERSON');
        self::assertSame('Do you have competent health and safety support in place?', $newQuestion['question_text']);
        self::assertSame($oldQuestion['question_text'], $newQuestion['help_text']);
        self::assertSame($oldQuestion['question_text'], (new QuestionRepository())->get((int) $liveBefore['id'], 'M01_COMPETENT_PERSON')['question_text']);

        $questions = (new QuestionRepository())->forVersion((int) $liveAfter['id']);
        $wordCounts = array_map(static fn(array $question): int => count(preg_split('/\\s+/', trim($question['question_text']))), $questions);
        self::assertLessThanOrEqual(14, max($wordCounts));
        self::assertLessThanOrEqual(11.0, array_sum($wordCounts) / count($wordCounts));

        $newRecommendationCount = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d',
            $liveAfter['id']
        ));
        self::assertSame($oldRecommendationCount, $newRecommendationCount);

        ConciseContentUpgrade::installIfNeeded();
        self::assertSame(
            2,
            (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Schema::table('content_versions'))
        );
    }

    public function test_existing_assessment_keeps_v1_while_new_assessments_use_v1_1(): void
    {
        $service = new AssessmentService();
        $before = $service->start();

        ConciseContentUpgrade::installIfNeeded();

        $after = $service->start();

        self::assertSame('1.0', $before['content_version']);
        self::assertSame('1.1', $after['content_version']);
    }
}

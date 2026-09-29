<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository, AssessmentService};
use Acorn\SafetyHealthcheck\Content\{ConciseContentUpgrade, ContentVersionRepository, QuestionRepository, SimplifiedContentUpgrade};
use Acorn\SafetyHealthcheck\Database\Schema;

final class SimplifiedContentUpgradeTest extends IntegrationTestCase
{
    public function test_upgrade_creates_v1_2_without_mutating_v1_1(): void
    {
        global $wpdb;

        ConciseContentUpgrade::installIfNeeded();
        $v11 = (new ContentVersionRepository())->getPublished();
        self::assertSame('1.1', $v11['version_key']);

        $v11Question = (new QuestionRepository())->get((int) $v11['id'], 'M01_COMPETENT_PERSON');

        SimplifiedContentUpgrade::installIfNeeded();

        $live = (new ContentVersionRepository())->getPublished();
        self::assertSame('1.2', $live['version_key']);
        self::assertSame('retired', (string) $wpdb->get_var($wpdb->prepare(
            'SELECT status FROM ' . Schema::table('content_versions') . ' WHERE id=%d',
            $v11['id']
        )));

        $questions = (new QuestionRepository())->forVersion((int) $live['id']);
        self::assertCount(16, $questions);
        self::assertSame([
            'M01_COMPETENT_PERSON',
            'M02_POLICY',
            'R01_GENERAL_RA',
            'R02_ACTION_REVIEW',
            'P01_TRAINING_INDUCTION',
            'M04_CONSULTATION',
            'A01_FIRST_AID',
            'A04_INCIDENT_REPORTING',
            'W03_WORKPLACE_EQUIPMENT',
            'F02_FIRE_RA',
            'F06_FIRE_ARRANGEMENTS',
            'L04_LEGIONELLA_MANAGEMENT',
            'AS05_ASBESTOS_MANAGEMENT',
            'S01_SELECTED_RISK_CONTROLS',
            'E01_EMPLOYERS_LIABILITY',
            'E02_LAW_INFORMATION',
        ], array_column($questions, 'question_key'));

        self::assertSame(
            $v11Question['question_text'],
            (new QuestionRepository())->get((int) $v11['id'], 'M01_COMPETENT_PERSON')['question_text']
        );

        $recommendationCount = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d',
            $live['id']
        ));
        self::assertGreaterThanOrEqual(48, $recommendationCount);

        SimplifiedContentUpgrade::installIfNeeded();
        self::assertSame(3, (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Schema::table('content_versions')));
    }

    public function test_existing_assessment_keeps_its_version_and_new_assessment_uses_v1_2(): void
    {
        $service = new AssessmentService();
        $before = $service->start();
        $beforeAssessment = (new AssessmentRepository())->findByToken($before['token']);

        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $after = $service->start();
        $afterAssessment = (new AssessmentRepository())->findByToken($after['token']);

        self::assertSame(
            '1.0',
            (new ContentVersionRepository())->find((int) $beforeAssessment['content_version_id'])['version_key']
        );
        self::assertSame(
            '1.2',
            (new ContentVersionRepository())->find((int) $afterAssessment['content_version_id'])['version_key']
        );
    }

    public function test_v1_2_accepts_progressive_context_and_does_not_require_sector(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $start = $service->start();

        $state = $service->updateProfile($start['token'], [
            'jurisdiction' => 'england',
            'employee_band' => '10_49',
        ]);

        self::assertSame('england', $state->profile['jurisdiction']);
        self::assertSame('10_49', $state->profile['employee_band']);
        self::assertArrayNotHasKey('sector', $state->profile);

        $keys = array_column($state->questions, 'question_key');
        self::assertContains('M01_COMPETENT_PERSON', $keys);
        self::assertContains('P01_TRAINING_INDUCTION', $keys);
        self::assertNotContains('F02_FIRE_RA', $keys);
        self::assertNotContains('L04_LEGIONELLA_MANAGEMENT', $keys);
        self::assertNotContains('AS05_ASBESTOS_MANAGEMENT', $keys);

        $state = $service->updateProfile($start['token'], ['fire_safety_responsibility' => 'yes']);
        self::assertContains('F02_FIRE_RA', array_column($state->questions, 'question_key'));

        $state = $service->updateProfile($start['token'], ['water_system_responsibility' => 'yes']);
        self::assertContains('L04_LEGIONELLA_MANAGEMENT', array_column($state->questions, 'question_key'));

        $state = $service->updateProfile($start['token'], ['asbestos_responsibility' => 'yes']);
        self::assertContains('AS05_ASBESTOS_MANAGEMENT', array_column($state->questions, 'question_key'));

        $state = $service->updateProfile($start['token'], ['risk_flags' => ['dse', 'contractors']]);
        self::assertContains('S01_SELECTED_RISK_CONTROLS', array_column($state->questions, 'question_key'));
    }

    public function test_progressive_context_change_removes_answers_that_become_hidden(): void
    {
        ConciseContentUpgrade::installIfNeeded();
        SimplifiedContentUpgrade::installIfNeeded();

        $service = new AssessmentService();
        $start = $service->start();
        $service->updateProfile($start['token'], [
            'jurisdiction' => 'england',
            'employee_band' => '10_49',
            'fire_safety_responsibility' => 'yes',
        ]);
        $service->saveAnswer($start['token'], 'F02_FIRE_RA', 'yes');

        $state = $service->updateProfile($start['token'], ['fire_safety_responsibility' => 'no']);
        self::assertArrayNotHasKey('F02_FIRE_RA', $state->answers);
        self::assertNotContains('F02_FIRE_RA', array_column($state->questions, 'question_key'));
    }
}

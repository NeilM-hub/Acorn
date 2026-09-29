<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Admin\AssessmentsPage;
use Acorn\SafetyHealthcheck\Assessment\{AssessmentDeletionService, AssessmentRepository, AssessmentService};
use Acorn\SafetyHealthcheck\Database\Schema;

final class AssessmentDeletionTest extends IntegrationTestCase
{
    public function test_deletion_removes_assessment_answers_and_unshared_contact(): void
    {
        global $wpdb;

        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], $this->profile());

        foreach ($state->questions as $question) {
            $service->saveAnswer($start['token'], $question['question_key'], 'yes');
        }

        $assessment = (new AssessmentRepository())->findByToken($start['token']);

        $wpdb->insert(Schema::table('contacts'), [
            'first_name' => 'Test',
            'last_name' => 'Record',
            'company' => 'DELETE ME TEST LTD',
            'email' => 'delete-me@example.test',
            'telephone' => '',
            'postcode' => '',
            'audit_requested' => 0,
            'marketing_consent' => 0,
            'marketing_consent_at' => null,
            'created_at' => current_time('mysql'),
        ]);
        $contactId = (int) $wpdb->insert_id;
        $wpdb->update(Schema::table('assessments'), ['contact_id' => $contactId], ['id' => (int) $assessment['id']]);

        self::assertGreaterThan(0, (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('answers') . ' WHERE assessment_id=%d',
            $assessment['id']
        )));

        self::assertTrue((new AssessmentDeletionService())->delete((int) $assessment['id']));

        self::assertSame('0', $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('assessments') . ' WHERE id=%d',
            $assessment['id']
        )));
        self::assertSame('0', $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('answers') . ' WHERE assessment_id=%d',
            $assessment['id']
        )));
        self::assertSame('0', $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('contacts') . ' WHERE id=%d',
            $contactId
        )));
    }

    public function test_deletion_does_not_remove_contact_still_used_by_another_assessment(): void
    {
        global $wpdb;

        $first = (new AssessmentService())->start();
        $second = (new AssessmentService())->start();
        $firstRow = (new AssessmentRepository())->findByToken($first['token']);
        $secondRow = (new AssessmentRepository())->findByToken($second['token']);

        $wpdb->insert(Schema::table('contacts'), [
            'first_name' => 'Shared',
            'last_name' => 'Contact',
            'company' => 'Shared Contact Ltd',
            'email' => 'shared@example.test',
            'telephone' => '',
            'postcode' => '',
            'audit_requested' => 0,
            'marketing_consent' => 0,
            'marketing_consent_at' => null,
            'created_at' => current_time('mysql'),
        ]);
        $contactId = (int) $wpdb->insert_id;

        $wpdb->update(Schema::table('assessments'), ['contact_id' => $contactId], ['id' => (int) $firstRow['id']]);
        $wpdb->update(Schema::table('assessments'), ['contact_id' => $contactId], ['id' => (int) $secondRow['id']]);

        self::assertTrue((new AssessmentDeletionService())->delete((int) $firstRow['id']));
        self::assertSame('1', $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . Schema::table('contacts') . ' WHERE id=%d',
            $contactId
        )));
    }

    public function test_assessments_admin_shows_bulk_and_single_delete_controls(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        get_role('administrator')->add_cap('manage_acorn_healthcheck');

        $start = (new AssessmentService())->start();
        $assessment = (new AssessmentRepository())->findByToken($start['token']);

        $_GET = ['page' => 'acorn-healthcheck-assessments'];
        ob_start();
        (new AssessmentsPage())->render();
        $listHtml = (string) ob_get_clean();

        self::assertStringContainsString('Delete selected', $listHtml);
        self::assertStringContainsString('name="assessment_ids[]"', $listHtml);
        self::assertStringContainsString('Date', $listHtml);
        self::assertStringContainsString('This permanently deletes the selected assessment data', $listHtml);

        $_GET = ['page' => 'acorn-healthcheck-assessments', 'assessment' => (string) $assessment['id']];
        ob_start();
        (new AssessmentsPage())->render();
        $detailHtml = (string) ob_get_clean();

        self::assertStringContainsString('Delete assessment', $detailHtml);
        self::assertStringContainsString('name="assessment_ids[]"', $detailHtml);
    }
}

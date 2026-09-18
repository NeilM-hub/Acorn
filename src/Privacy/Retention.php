<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Privacy;

use Acorn\SafetyHealthcheck\Database\Schema;

final class Retention
{
    public function cleanup(): void
    {
        global $wpdb;
        $settings = array_merge(require dirname(__DIR__, 2) . '/config/settings-defaults.php', get_option('acorn_hc_settings', []));
        $incompleteCutoff = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * (int) $settings['incomplete_retention_days']);
        $completedCutoff = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * (int) $settings['completed_retention_days']);
        $incompleteIds = $wpdb->get_col($wpdb->prepare(
            'SELECT id FROM ' . Schema::table('assessments') . ' WHERE status IN (%s,%s) AND last_activity_at<%s',
            'in_progress', 'assessed', $incompleteCutoff
        ));
        foreach ($incompleteIds as $id) $this->deleteAssessment((int) $id);
        $completedIds = $wpdb->get_col($wpdb->prepare(
            'SELECT id FROM ' . Schema::table('assessments') . ' WHERE status=%s AND completed_at<%s', 'completed', $completedCutoff
        ));
        foreach ($completedIds as $id) $this->deleteAssessment((int) $id);
    }

    private function deleteAssessment(int $id): void
    {
        global $wpdb;
        $contactId = (int) $wpdb->get_var($wpdb->prepare('SELECT contact_id FROM ' . Schema::table('assessments') . ' WHERE id=%d', $id));
        $wpdb->delete(Schema::table('answers'), ['assessment_id' => $id]);
        $wpdb->delete(Schema::table('assessments'), ['id' => $id]);
        if ($contactId) $wpdb->delete(Schema::table('contacts'), ['id' => $contactId]);
    }
}

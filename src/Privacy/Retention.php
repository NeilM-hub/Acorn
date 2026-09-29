<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Privacy;

use Acorn\SafetyHealthcheck\Assessment\AssessmentDeletionService;
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
        $deletion = new AssessmentDeletionService();
        foreach ($incompleteIds as $id) $deletion->delete((int) $id);
        $completedIds = $wpdb->get_col($wpdb->prepare(
            'SELECT id FROM ' . Schema::table('assessments') . ' WHERE status=%s AND completed_at<%s', 'completed', $completedCutoff
        ));
        foreach ($completedIds as $id) $deletion->delete((int) $id);
    }
}

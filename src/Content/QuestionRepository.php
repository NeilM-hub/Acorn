<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;

final class QuestionRepository
{
    public function forVersion(int $versionId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . Schema::table('questions') . ' WHERE content_version_id=%d AND is_active=1 ORDER BY sort_order',
            $versionId
        ), ARRAY_A);
    }

    public function allForVersion(int $versionId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . Schema::table('questions') . ' WHERE content_version_id=%d ORDER BY sort_order',
            $versionId
        ), ARRAY_A);
    }

    public function get(int $versionId, string $questionKey): array
    {
        global $wpdb;
        return (array) $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . Schema::table('questions') . ' WHERE content_version_id=%d AND question_key=%s',
            $versionId,
            $questionKey
        ), ARRAY_A);
    }
}

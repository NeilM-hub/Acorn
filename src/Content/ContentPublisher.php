<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;
use RuntimeException;

final class ContentPublisher
{
    public function createDraftFromPublished(int $userId): int
    {
        global $wpdb;
        $live = (new ContentVersionRepository())->getPublished();
        if (!$live) throw new RuntimeException('No published content exists.');
        $parts = explode('.', $live['version_key']);
        $versionKey = $parts[0] . '.' . ((int) ($parts[1] ?? 0) + 1);
        $wpdb->insert(Schema::table('content_versions'), [
            'version_key' => $versionKey, 'status' => 'draft', 'created_by' => $userId,
            'created_at' => current_time('mysql'), 'notes' => 'Draft cloned from ' . $live['version_key'],
        ]);
        $draftId = (int) $wpdb->insert_id;
        foreach (['questions', 'recommendations'] as $suffix) {
            $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Schema::table($suffix) . ' WHERE content_version_id=%d', $live['id']), ARRAY_A);
            foreach ($rows as $row) {
                unset($row['id']); $row['content_version_id'] = $draftId;
                $wpdb->insert(Schema::table($suffix), $row);
            }
        }
        return $draftId;
    }

    public function publish(int $draftId, int $userId): int
    {
        global $wpdb;
        $version = (new ContentVersionRepository())->find($draftId);
        if (($version['status'] ?? '') !== 'draft') throw new RuntimeException('Only a draft can be published.');
        $questions = (new QuestionRepository())->forVersion($draftId);
        foreach ($questions as $question) {
            if (trim($question['reviewed_by']) === '' || $question['last_reviewed'] === '1970-01-01' || trim($question['source_json']) === '') {
                throw new RuntimeException('Technical review and source metadata are required before publishing.');
            }
            foreach (['partly', 'no', 'not_sure'] as $answer) {
                $count = (int) $wpdb->get_var($wpdb->prepare(
                    'SELECT COUNT(*) FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d AND question_key=%s AND answer_value=%s',
                    $draftId, $question['question_key'], $answer
                ));
                if ($count === 0) throw new RuntimeException("{$question['question_key']} is missing the {$answer} recommendation.");
            }
        }
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->update(Schema::table('content_versions'), ['status' => 'retired'], ['status' => 'published']);
            $wpdb->update(Schema::table('content_versions'), ['status' => 'published', 'published_at' => current_time('mysql')], ['id' => $draftId]);
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK'); throw $error;
        }
        return $draftId;
    }
}

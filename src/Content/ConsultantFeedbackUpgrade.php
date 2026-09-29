<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;

final class ConsultantFeedbackUpgrade
{
    private const VERSION_KEY = '1.2.2';

    private const QUESTION_UPDATES = [
        'A01_FIRST_AID' => 'Have you assessed your first-aid needs and, where required, do you have trained first-aiders and suitable first-aid arrangements?',
        'F06_FIRE_ARRANGEMENTS' => 'Are your fire emergency arrangements understood, including trained fire wardens or marshals where required, and are the relevant fire precautions checked and maintained?',
        'E02_LAW_INFORMATION' => 'Is the Health and Safety Law poster displayed where appropriate, or have workers been given the equivalent information?',
    ];

    public static function installIfNeeded(): void
    {
        global $wpdb;

        $versions = Schema::table('content_versions');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $versions WHERE version_key=%s LIMIT 1",
            self::VERSION_KEY
        ));
        if ($existing) {
            return;
        }

        $live = (new ContentVersionRepository())->getPublished();
        if (!$live || ($live['version_key'] ?? '') !== '1.2.1') {
            return;
        }

        $now = current_time('mysql');
        $wpdb->query('START TRANSACTION');

        try {
            $wpdb->insert($versions, [
                'version_key' => self::VERSION_KEY,
                'status' => 'published',
                'created_by' => null,
                'created_at' => $now,
                'published_at' => $now,
                'notes' => 'Makes trained first-aiders, trained fire wardens or marshals, and Health and Safety Law poster/equivalent information explicit in the customer-facing questions. No scoring, applicability or branching changes.',
            ]);
            $newVersionId = (int) $wpdb->insert_id;

            $questionRows = $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM ' . Schema::table('questions') . ' WHERE content_version_id=%d',
                $live['id']
            ), ARRAY_A);

            foreach ($questionRows as $row) {
                unset($row['id']);
                $row['content_version_id'] = $newVersionId;

                $key = (string) $row['question_key'];
                if (isset(self::QUESTION_UPDATES[$key])) {
                    $row['question_text'] = self::QUESTION_UPDATES[$key];
                }

                $wpdb->insert(Schema::table('questions'), $row);
            }

            $recommendationRows = $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d',
                $live['id']
            ), ARRAY_A);

            foreach ($recommendationRows as $row) {
                unset($row['id']);
                $row['content_version_id'] = $newVersionId;
                $wpdb->insert(Schema::table('recommendations'), $row);
            }

            $wpdb->update($versions, ['status' => 'retired'], ['id' => (int) $live['id']]);
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        }
    }
}

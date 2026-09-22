<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;

final class ChecklistCoverageUpgrade
{
    private const VERSION_KEY = '1.2.1';

    private const HELP_UPDATES = [
        'P01_TRAINING_INDUCTION' => 'People should understand the risks associated with their work, the precautions they need to take and what to do in an emergency. New starters should receive suitable information before or as they begin work, and refresher or update training should be provided where needed to keep knowledge and competence current.\n\nWhat good looks like: People know the controls relevant to their role, receive suitable induction and training, including refresher or update training where needed, and are supervised appropriately while gaining competence.',
        'F06_FIRE_ARRANGEMENTS' => 'People should know what to do if there is a fire. Relevant alarms, emergency lighting, escape routes, firefighting equipment and other precautions should be checked and maintained as appropriate for the premises. Where the fire arrangements identify a need for fire wardens or marshals, suitable people should be appointed and given appropriate training.\n\nWhat good looks like: Emergency arrangements are practical and communicated, any required fire wardens or marshals are in place and appropriately trained, required systems are maintained, defects are acted on and relevant records are available.',
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
        if (!$live || ($live['version_key'] ?? '') !== '1.2') {
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
                'notes' => 'Clarifies that refresher/update training and fire wardens or marshals are covered where relevant. No scoring, applicability or branching changes.',
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
                if (isset(self::HELP_UPDATES[$key])) {
                    $row['help_text'] = self::HELP_UPDATES[$key];
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

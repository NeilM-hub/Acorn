<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Assessment;

use Acorn\SafetyHealthcheck\Database\Schema;
use RuntimeException;

final class AssessmentDeletionService
{
    public function delete(int $assessmentId): bool
    {
        global $wpdb;

        $assessment = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id,contact_id FROM ' . Schema::table('assessments') . ' WHERE id=%d',
                $assessmentId
            ),
            ARRAY_A
        );

        if (!$assessment) {
            return false;
        }

        $contactId = (int) ($assessment['contact_id'] ?? 0);

        $wpdb->query('START TRANSACTION');

        try {
            $wpdb->delete(Schema::table('answers'), ['assessment_id' => $assessmentId]);
            $deleted = $wpdb->delete(Schema::table('assessments'), ['id' => $assessmentId]);

            if ($deleted === false) {
                throw new RuntimeException('Assessment deletion failed.');
            }

            if ($contactId > 0) {
                $references = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT COUNT(*) FROM ' . Schema::table('assessments') . ' WHERE contact_id=%d',
                        $contactId
                    )
                );

                if ($references === 0) {
                    $wpdb->delete(Schema::table('contacts'), ['id' => $contactId]);
                }
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        }
    }

    public function deleteMany(array $assessmentIds): int
    {
        $deleted = 0;

        foreach (array_values(array_unique(array_filter(array_map('absint', $assessmentIds)))) as $assessmentId) {
            if ($assessmentId > 0 && $this->delete($assessmentId)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

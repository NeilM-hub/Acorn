<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;

final class ConciseContentUpgrade
{
    private const VERSION_KEY = '1.1';

    private const QUESTIONS = [
        'M01_COMPETENT_PERSON' => 'Do you have competent health and safety support in place?',
        'M02_POLICY' => 'Are your health and safety arrangements clear and up to date?',
        'M03_RESPONSIBILITIES' => 'Are health and safety responsibilities clearly assigned?',
        'M04_CONSULTATION' => 'Do you consult employees about health and safety?',
        'R01_GENERAL_RA' => 'Have your main workplace risks been assessed and controlled?',
        'R02_ACTION_REVIEW' => 'Do you track actions from your risk assessments?',
        'R03_HIGHER_NEEDS' => 'Do you consider workers who may need extra protection?',
        'T01_INDUCTION' => 'Do new starters receive a suitable health and safety induction?',
        'T02_TRAINING_SUPERVISION' => 'Do workers get the training and supervision they need?',
        'T03_TRAINING_RECORDS' => 'Do you keep training records and review refresher needs?',
        'A01_FIRST_AID' => 'Do your first-aid arrangements reflect your workplace needs?',
        'A02_INCIDENTS' => 'Do you record and review accidents and near misses?',
        'A03_RIDDOR' => 'Do relevant people know when a RIDDOR report is required?',
        'W01_WELFARE' => 'Are workplace welfare facilities and conditions suitable?',
        'W02_WORK_EQUIPMENT' => 'Is work equipment kept safe and properly maintained?',
        'F01_FIRE_RESPONSIBILITY' => 'Are fire safety responsibilities clearly defined?',
        'F02_FIRE_RA' => 'Do you have a current fire risk assessment?',
        'F03_FIRE_ACTIONS' => 'Do you track actions from your fire risk assessment?',
        'F04_FIRE_EMERGENCY_TRAINING' => 'Are fire emergency arrangements clear and understood?',
        'F05_FIRE_SYSTEMS' => 'Are fire safety systems checked and maintained?',
        'L01_LEGIONELLA_RA' => 'Do you have a suitable Legionella risk assessment?',
        'L02_LEGIONELLA_CONTROLS' => 'Are required Legionella controls clearly assigned and carried out?',
        'L03_LEGIONELLA_RECORDS' => 'Are Legionella monitoring and records kept up to date?',
        'AS01_ASBESTOS_INFORMATION' => 'Do you have reliable information about asbestos in the building?',
        'AS02_ASBESTOS_MANAGEMENT' => 'Is your asbestos register and management plan up to date?',
        'AS03_ASBESTOS_MONITORING_INFO' => 'Is asbestos condition monitored and information shared before work?',
        'AS04_ASBESTOS_INTRUSIVE' => 'Is suitable asbestos information available before intrusive work starts?',
        'C01_COSHH' => 'Are hazardous substances properly assessed and controlled?',
        'D01_DSE' => 'Are DSE workstation assessments completed and followed up?',
        'MH01_MANUAL_HANDLING' => 'Are manual-handling risks assessed and controlled?',
        'LW01_LONE_WORKING' => 'Are lone-working risks assessed and managed?',
        'WAH01_WORK_AT_HEIGHT' => 'Is work at height properly planned and controlled?',
        'YW01_YOUNG_WORKERS' => 'Are risks to workers under 18 specifically assessed?',
        'CT01_CONTRACTORS' => 'Are contractors properly selected, briefed and monitored?',
        'DRV01_DRIVING' => 'Are driving-for-work risks properly managed?',
    ];

    public static function installIfNeeded(): void
    {
        global $wpdb;

        $versions = Schema::table('content_versions');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $versions WHERE version_key=%s LIMIT 1",
            self::VERSION_KEY
        ));
        if ($existing) return;

        $live = (new ContentVersionRepository())->getPublished();
        if (!$live || ($live['version_key'] ?? '') !== '1.0') return;

        $now = current_time('mysql');
        $wpdb->query('START TRANSACTION');

        try {
            $wpdb->insert($versions, [
                'version_key' => self::VERSION_KEY,
                'status' => 'published',
                'created_by' => null,
                'created_at' => $now,
                'published_at' => $now,
                'notes' => 'Concise customer wording and executive report presentation; rules and recommendation logic unchanged from 1.0. Technical sign-off still required before live launch.',
            ]);
            $newVersionId = (int) $wpdb->insert_id;

            foreach (['questions', 'recommendations'] as $suffix) {
                $rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT * FROM ' . Schema::table($suffix) . ' WHERE content_version_id=%d',
                    $live['id']
                ), ARRAY_A);

                foreach ($rows as $row) {
                    unset($row['id']);
                    $row['content_version_id'] = $newVersionId;

                    if ($suffix === 'questions') {
                        $key = (string) $row['question_key'];
                        $original = (string) $row['question_text'];
                        if (isset(self::QUESTIONS[$key])) {
                            $row['question_text'] = self::QUESTIONS[$key];
                            $row['help_text'] = $original;
                        }
                        if ($key === 'M02_POLICY') {
                            $row['variant_json'] = wp_json_encode([
                                'under_5' => 'Are your health and safety arrangements clear and up to date?',
                                '5_plus' => 'Do you have a current written health and safety policy?',
                            ]);
                        }
                    }

                    if ($suffix === 'recommendations' && ($row['answer_value'] ?? '') === 'not_sure') {
                        $prefix = 'Confirm the current position and who is responsible. Then consider this next step: ';
                        if (str_starts_with((string) $row['next_step_text'], $prefix)) {
                            $row['next_step_text'] = substr((string) $row['next_step_text'], strlen($prefix));
                        }
                        $row['identified_text'] = 'You indicated that you are not sure whether this arrangement is currently in place.';
                    }

                    $wpdb->insert(Schema::table($suffix), $row);
                }
            }

            $wpdb->update($versions, ['status' => 'retired'], ['id' => (int) $live['id']]);
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        }
    }
}

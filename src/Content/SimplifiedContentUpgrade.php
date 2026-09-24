<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;

final class SimplifiedContentUpgrade
{
    private const VERSION_KEY = '1.2';

    private const QUESTIONS = [
        [
            'key' => 'M01_COMPETENT_PERSON',
            'module' => 'management',
            'order' => 10,
            'text' => 'Do you have someone competent helping you manage health and safety?',
            'help' => 'Every business needs access to someone with suitable knowledge, skills and experience to help manage health and safety. This could be someone within your business or external support.\n\nWhat good looks like: You know who provides competent advice, they understand your business and you can access support when needed.',
            'applicability' => ['all' => []],
            'subject' => 'competent health and safety support',
            'no_status' => 'priority',
            'next' => 'Confirm who provides competent health and safety support and that they have suitable knowledge, skills, experience and resources for the work you do.',
            'why' => 'Competent assistance helps a business understand what it needs to do and keep suitable health and safety arrangements in place.',
            'good' => 'You know who provides competent advice, they understand your business and you can access support when needed.',
        ],
        [
            'key' => 'M02_POLICY',
            'module' => 'management',
            'order' => 20,
            'text' => 'Are your health and safety arrangements clear and kept up to date?',
            'help' => 'Your arrangements should explain how health and safety is managed, who is responsible and how important controls are put into practice. Businesses with five or more employees should have their policy in writing.\n\nWhat good looks like: Responsibilities are clear, the arrangements reflect how the business actually operates, and the policy is reviewed when things change.',
            'variants' => [
                'under_5' => 'Are your health and safety arrangements clear and kept up to date?',
                '5_plus' => 'Do you have a current written health and safety policy and clear arrangements for putting it into practice?',
            ],
            'applicability' => ['all' => []],
            'subject' => 'clear and current health and safety arrangements',
            'no_status' => 'review',
            'next' => 'Review how health and safety is organised, make responsibilities clear and ensure the written-policy requirement is met where it applies.',
            'why' => 'Clear arrangements help everyone understand how health and safety is managed and who is responsible for what.',
            'good' => 'Responsibilities are clear, the arrangements reflect how the business actually operates, and the policy is reviewed when things change.',
        ],
        [
            'key' => 'R01_GENERAL_RA',
            'module' => 'risk',
            'order' => 30,
            'text' => 'Have you assessed the main health and safety risks in your workplace?',
            'help' => 'A suitable risk assessment identifies the significant hazards, who could be harmed, the controls already in place and anything else that needs to be done.\n\nWhat good looks like: The assessment reflects the work people actually do and the controls are practical, proportionate and kept under review.',
            'applicability' => ['all' => []],
            'subject' => 'assessment of the main workplace risks',
            'no_status' => 'priority',
            'next' => 'Identify the significant hazards in your work, who could be harmed, the controls already in place and any further action needed.',
            'why' => 'Risk assessment provides the basis for deciding what controls are needed to prevent harm.',
            'good' => 'The assessment reflects the work people actually do and the controls are practical, proportionate and kept under review.',
        ],
        [
            'key' => 'R02_ACTION_REVIEW',
            'module' => 'risk',
            'order' => 40,
            'text' => 'Do you make sure actions from risk assessments are completed?',
            'help' => 'Finding an issue is only useful if something happens next. Actions should be assigned, followed up and the assessment reviewed when work, equipment, people or circumstances change.\n\nWhat good looks like: Important actions have an owner and a clear status, and completed actions can be evidenced.',
            'applicability' => ['all' => []],
            'subject' => 'follow-up of risk-assessment actions',
            'no_status' => 'priority',
            'next' => 'Assign outstanding actions to named owners, track them to completion and review assessments when relevant circumstances change.',
            'why' => 'Risk assessments only improve safety when the actions they identify are actually followed through.',
            'good' => 'Important actions have an owner and a clear status, and completed actions can be evidenced.',
        ],
        [
            'key' => 'P01_TRAINING_INDUCTION',
            'module' => 'people',
            'order' => 50,
            'text' => 'Do employees get the induction, information, training and supervision they need to work safely?',
            'help' => 'People should understand the risks associated with their work, the precautions they need to take and what to do in an emergency. New starters should receive suitable information before or as they begin work.\n\nWhat good looks like: People know the controls relevant to their role, receive suitable training and are supervised appropriately while gaining competence.',
            'applicability' => ['has_employees' => true],
            'subject' => 'suitable induction, information, training and supervision',
            'no_status' => 'priority',
            'next' => 'Review what people need to know for their roles and provide suitable induction, information, training and supervision where gaps exist.',
            'why' => 'People need to understand the risks and controls relevant to the work they actually perform.',
            'good' => 'People know the controls relevant to their role, receive suitable training and are supervised appropriately while gaining competence.',
        ],
        [
            'key' => 'M04_CONSULTATION',
            'module' => 'people',
            'order' => 60,
            'text' => 'Do you consult employees about health and safety?',
            'help' => 'Employees should have a practical way to raise concerns and contribute to health and safety matters that affect them. This can happen through meetings, representatives, briefings or normal day-to-day consultation.\n\nWhat good looks like: Workers know how to raise concerns and are involved when decisions or changes could affect their health and safety.',
            'applicability' => ['has_employees' => true],
            'subject' => 'employee consultation on health and safety',
            'no_status' => 'review',
            'next' => 'Put in place a practical way for workers to raise concerns and contribute to health and safety matters that affect them.',
            'why' => 'Workers often understand day-to-day risks and should have a meaningful way to contribute to decisions that affect their safety.',
            'good' => 'Workers know how to raise concerns and are involved when decisions or changes could affect their health and safety.',
        ],
        [
            'key' => 'A01_FIRST_AID',
            'module' => 'incidents',
            'order' => 70,
            'text' => 'Do you have suitable first-aid arrangements for your workplace?',
            'help' => 'The level of first-aid provision should reflect your workplace, workforce and risks. This can include suitable equipment, an appointed person and trained first-aiders where the needs assessment shows they are required.\n\nWhat good looks like: Your first-aid needs have been considered and the right people, equipment and arrangements are available whenever people are at work.',
            'applicability' => ['has_employees' => true],
            'subject' => 'suitable first-aid arrangements',
            'no_status' => 'priority',
            'next' => 'Review your first-aid needs and make sure suitable people, equipment and arrangements are available for the circumstances of your workplace.',
            'why' => 'First-aid arrangements should be proportionate to the actual workplace, workforce and foreseeable risks.',
            'good' => 'Your first-aid needs have been considered and the right people, equipment and arrangements are available whenever people are at work.',
        ],
        [
            'key' => 'A04_INCIDENT_REPORTING',
            'module' => 'incidents',
            'order' => 80,
            'text' => 'Do you record incidents and know when something needs to be reported?',
            'help' => 'Recording accidents and relevant near misses helps you learn from what happened. Certain work-related injuries, diseases and dangerous occurrences may also need to be formally reported under the relevant RIDDOR regime.\n\nWhat good looks like: People know how to report an incident, important events are reviewed, actions are followed up and the right person knows how to identify a reportable event.',
            'applicability' => ['has_employees' => true],
            'subject' => 'incident recording, learning and RIDDOR awareness',
            'no_status' => 'review',
            'next' => 'Make it straightforward to record relevant incidents, review what happened and confirm who is responsible for identifying and making any required RIDDOR report.',
            'why' => 'Learning from incidents can reveal weak controls and some specified events have formal reporting requirements.',
            'good' => 'People know how to report an incident, important events are reviewed, actions are followed up and the right person knows how to identify a reportable event.',
        ],
        [
            'key' => 'W03_WORKPLACE_EQUIPMENT',
            'module' => 'workplace',
            'order' => 90,
            'text' => 'Are your workplace, welfare facilities and work equipment kept safe and properly maintained?',
            'help' => 'This includes the general condition of the workplace, toilets and welfare facilities, housekeeping and any work equipment that needs maintenance or inspection.\n\nWhat good looks like: Facilities are suitable and usable, equipment is appropriate for the task, defects are dealt with and required maintenance or inspections are kept up to date.',
            'applicability' => ['all' => []],
            'subject' => 'safe workplace, welfare facilities and work equipment',
            'no_status' => 'review',
            'next' => 'Review workplace conditions, welfare facilities and work equipment, then address defects and any overdue maintenance or inspections.',
            'why' => 'Poor workplace conditions or poorly maintained equipment can create avoidable risks to people at work.',
            'good' => 'Facilities are suitable and usable, equipment is appropriate for the task, defects are dealt with and required maintenance or inspections are kept up to date.',
        ],
        [
            'key' => 'F02_FIRE_RA',
            'module' => 'fire',
            'order' => 100,
            'text' => 'Do you have a current fire risk assessment for the premises you are responsible for?',
            'help' => 'A fire risk assessment should identify fire hazards, people at risk, the precautions already in place and any further action required. It should be reviewed when relevant circumstances change.\n\nWhat good looks like: The assessment reflects the premises, people and activities, is recorded where required and any actions are being followed up.',
            'applicability' => ['field' => 'fire_safety_responsibility', 'in' => ['yes','partly','not_sure']],
            'subject' => 'a suitable current fire risk assessment',
            'no_status' => 'priority',
            'next' => 'Confirm that a suitable fire risk assessment covers the premises and activities within your responsibility and arrange or review one where needed.',
            'why' => 'The fire risk assessment is the basis for deciding what fire precautions and emergency arrangements are needed.',
            'good' => 'The assessment reflects the premises, people and activities, is recorded where required and any actions are being followed up.',
        ],
        [
            'key' => 'F06_FIRE_ARRANGEMENTS',
            'module' => 'fire',
            'order' => 110,
            'text' => 'Are your fire emergency arrangements understood and are the relevant fire precautions checked and maintained?',
            'help' => 'People should know what to do if there is a fire. Relevant alarms, emergency lighting, escape routes, firefighting equipment and other precautions should also be checked and maintained as appropriate for the premises.\n\nWhat good looks like: Emergency arrangements are practical and communicated, required systems are maintained, defects are acted on and relevant records are available.',
            'applicability' => ['field' => 'fire_safety_responsibility', 'in' => ['yes','partly','not_sure']],
            'subject' => 'fire emergency arrangements and maintenance of relevant precautions',
            'no_status' => 'priority',
            'next' => 'Review emergency and evacuation arrangements and confirm that the fire precautions relevant to the premises are checked, maintained and acted on when defects are found.',
            'why' => 'People need to know what to do in a fire and fire-safety measures need to work when they are required.',
            'good' => 'Emergency arrangements are practical and communicated, required systems are maintained, defects are acted on and relevant records are available.',
        ],
        [
            'key' => 'L04_LEGIONELLA_MANAGEMENT',
            'module' => 'legionella',
            'order' => 120,
            'text' => 'Do you have a suitable Legionella risk assessment and the required controls in place?',
            'help' => 'The assessment should consider whether Legionella could grow or spread in water systems you control and identify any management, monitoring, maintenance or other precautions that are needed.\n\nWhat good looks like: Responsibilities are clear, the risk has been assessed, required controls are carried out and the arrangements are reviewed when circumstances change.',
            'applicability' => ['field' => 'water_system_responsibility', 'in' => ['yes','partly','not_sure']],
            'subject' => 'suitable Legionella risk assessment and controls',
            'no_status' => 'priority',
            'next' => 'Confirm which water systems are within your responsibility and arrange or review a suitable Legionella risk assessment and any controls it requires.',
            'why' => 'Dutyholders need to understand whether relevant water systems present a foreseeable Legionella risk and what proportionate controls are needed.',
            'good' => 'Responsibilities are clear, the risk has been assessed, required controls are carried out and the arrangements are reviewed when circumstances change.',
        ],
        [
            'key' => 'AS05_ASBESTOS_MANAGEMENT',
            'module' => 'asbestos',
            'order' => 130,
            'text' => 'Do you have suitable asbestos information and management arrangements for the areas you are responsible for?',
            'help' => 'You should know whether asbestos is present or presumed to be present, where it is located and what condition it is in. That information should be kept current and made available before work that could disturb it.\n\nWhat good looks like: There is a current record or register, responsibilities and actions are clear, known or presumed materials are monitored and relevant information reaches anyone who could disturb them.',
            'applicability' => ['field' => 'asbestos_responsibility', 'in' => ['yes','partly','not_sure']],
            'subject' => 'suitable asbestos information and management arrangements',
            'no_status' => 'priority',
            'next' => 'Establish what reliable asbestos information exists, keep the record and management arrangements current and make sure relevant information is provided before work that could disturb suspect materials.',
            'why' => 'Asbestos may be safely managed when it is known and left undisturbed, but accidental disturbance can release hazardous fibres.',
            'good' => 'There is a current record or register, responsibilities and actions are clear, known or presumed materials are monitored and relevant information reaches anyone who could disturb them.',
        ],
        [
            'key' => 'S01_SELECTED_RISK_CONTROLS',
            'module' => 'specialist',
            'order' => 140,
            'text' => 'Have the additional risks you selected been assessed and properly controlled?',
            'help' => 'Different activities need different controls. The important point is that the relevant risks have been considered, suitable precautions are in place and those precautions are reviewed when things change.\n\nWhat good looks like: The selected risks have proportionate assessments and controls, people understand what is expected of them and significant actions are followed through.',
            'applicability' => ['any' => [
                ['risk_flag' => 'dse'],
                ['risk_flag' => 'manual_handling'],
                ['risk_flag' => 'hazardous_substances'],
                ['risk_flag' => 'lone_working'],
                ['risk_flag' => 'work_at_height'],
                ['risk_flag' => 'machinery'],
                ['risk_flag' => 'contractors'],
                ['risk_flag' => 'young_workers'],
                ['risk_flag' => 'driving_for_work'],
            ]],
            'subject' => 'assessment and control of the additional workplace risks selected',
            'no_status' => 'priority',
            'next' => 'Review the additional risks you selected and confirm that each has proportionate assessment, suitable controls and clear follow-up of significant actions.',
            'why' => 'Different work activities create different risks, and relevant hazards need controls that match the work being done.',
            'good' => 'The selected risks have proportionate assessments and controls, people understand what is expected of them and significant actions are followed through.',
        ],
        [
            'key' => 'E01_EMPLOYERS_LIABILITY',
            'module' => 'management',
            'order' => 150,
            'text' => "Do you have Employers' Liability insurance where it is required?",
            'help' => "Most employers are required to insure against liability for injury or disease suffered by employees because of their work, although exemptions can apply in some circumstances.\n\nWhat good looks like: You have confirmed whether the requirement applies to your organisation and, where it does, suitable cover is in force.",
            'applicability' => ['has_employees' => true],
            'subject' => "Employers' Liability insurance where required",
            'no_status' => 'priority',
            'next' => "Confirm whether Employers' Liability insurance is required for your organisation and make sure suitable cover is in force where it applies.",
            'why' => "Employers' Liability insurance is a legal requirement for most employers and protects against certain employee injury or disease claims.",
            'good' => 'You have confirmed whether the requirement applies to your organisation and, where it does, suitable cover is in force.',
        ],
        [
            'key' => 'E02_LAW_INFORMATION',
            'module' => 'management',
            'order' => 160,
            'text' => 'Have workers been given the required health and safety law information?',
            'help' => 'Employers can provide the required information by displaying the approved Health and Safety Law poster in a suitable position or by giving workers the equivalent leaflet or information.\n\nWhat good looks like: Workers can easily access the required health and safety law information.',
            'applicability' => ['has_employees' => true],
            'subject' => 'required health and safety law information for workers',
            'no_status' => 'review',
            'next' => 'Confirm that workers can access the required health and safety law information using the appropriate poster or equivalent information for the jurisdiction.',
            'why' => 'Workers should have access to the statutory health and safety information that explains key rights and responsibilities.',
            'good' => 'Workers can easily access the required health and safety law information.',
        ],
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
        if (!$live || ($live['version_key'] ?? '') !== '1.1') {
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
                'notes' => 'Simplified guided Healthcheck UX/content. Customer-facing technical wording requires Acorn competent-person sign-off before production launch.',
            ]);
            $versionId = (int) $wpdb->insert_id;

            foreach (self::QUESTIONS as $question) {
                self::insertQuestion($versionId, $question);
                self::insertRecommendations($versionId, $question);
            }

            $wpdb->update($versions, ['status' => 'retired'], ['id' => (int) $live['id']]);
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        }
    }

    private static function insertQuestion(int $versionId, array $question): void
    {
        global $wpdb;

        $wpdb->insert(Schema::table('questions'), [
            'content_version_id' => $versionId,
            'question_key' => $question['key'],
            'module_key' => $question['module'],
            'sort_order' => $question['order'],
            'question_text' => $question['text'],
            'help_text' => $question['help'],
            'variant_json' => wp_json_encode($question['variants'] ?? []),
            'applicability_json' => wp_json_encode($question['applicability']),
            'answer_rules_json' => wp_json_encode([
                'yes' => 'addressed',
                'partly' => 'review',
                'no' => $question['no_status'],
                'not_sure' => 'review',
                'subject' => $question['subject'],
            ]),
            'source_json' => wp_json_encode(SourceMetadata::versionedQuestionSources($question['key'])),
            'last_reviewed' => '1970-01-01',
            'next_review' => '2026-12-31',
            'reviewed_by' => '',
            'is_active' => 1,
        ]);
    }

    private static function insertRecommendations(int $versionId, array $question): void
    {
        global $wpdb;

        $jurisdictions = str_starts_with($question['key'], 'F')
            ? ['england', 'wales', 'scotland', 'northern_ireland']
            : ((str_starts_with($question['key'], 'L') || str_starts_with($question['key'], 'AS'))
                ? ['*', 'northern_ireland']
                : ['*']);

        foreach (['partly', 'no', 'not_sure'] as $answer) {
            $status = $answer === 'no' ? $question['no_status'] : 'review';
            $identified = match ($answer) {
                'partly' => "Your answer indicates that {$question['subject']} may only be partly in place or may need updating.",
                'no' => "Your answer indicates that {$question['subject']} may not currently be in place.",
                'not_sure' => 'You indicated that you are not sure whether this arrangement is currently in place.',
            };

            foreach ($jurisdictions as $jurisdiction) {
                $wpdb->insert(Schema::table('recommendations'), [
                    'content_version_id' => $versionId,
                    'question_key' => $question['key'],
                    'answer_value' => $answer,
                    'jurisdiction' => $jurisdiction,
                    'finding_status' => $status,
                    'heading' => ucfirst($question['subject']),
                    'identified_text' => $identified,
                    'next_step_text' => ($answer === 'not_sure' ? 'Confirm the current position and who is responsible. Then: ' : '') . $question['next'],
                    'why_text' => $question['why'],
                    'good_looks_text' => $question['good'],
                    'service_tags_json' => wp_json_encode(self::tags($question['module'])),
                    'source_json' => wp_json_encode(SourceMetadata::forQuestion($question['key'], $jurisdiction)),
                    'sort_rank' => $question['order'],
                ]);
            }
        }
    }

    private static function tags(string $module): array
    {
        return match ($module) {
            'fire' => ['fire'],
            'legionella' => ['legionella'],
            'asbestos' => ['asbestos'],
            default => ['health_safety'],
        };
    }
}

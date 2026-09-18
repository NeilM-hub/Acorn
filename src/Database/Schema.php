<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Database;

final class Schema
{
    public const VERSION = '1';

    public static function table(string $suffix): string
    {
        global $wpdb;

        return $wpdb->prefix . 'acorn_hc_' . $suffix;
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charsetCollate = $wpdb->get_charset_collate();

        $statements = [
            "CREATE TABLE " . self::table('content_versions') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                version_key varchar(32) NOT NULL,
                status varchar(16) NOT NULL,
                created_by bigint unsigned NULL,
                created_at datetime NOT NULL,
                published_at datetime NULL,
                notes text NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY version_key (version_key),
                KEY status (status)
            ) $charsetCollate;",
            "CREATE TABLE " . self::table('questions') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                content_version_id bigint unsigned NOT NULL,
                question_key varchar(64) NOT NULL,
                module_key varchar(32) NOT NULL,
                sort_order int NOT NULL,
                question_text text NOT NULL,
                help_text text NOT NULL,
                variant_json longtext NULL,
                applicability_json longtext NOT NULL,
                answer_rules_json longtext NOT NULL,
                source_json longtext NOT NULL,
                last_reviewed date NOT NULL,
                next_review date NOT NULL,
                reviewed_by varchar(120) NOT NULL,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY  (id),
                UNIQUE KEY version_question (content_version_id,question_key),
                KEY content_version_id (content_version_id),
                KEY next_review (next_review)
            ) $charsetCollate;",
            "CREATE TABLE " . self::table('recommendations') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                content_version_id bigint unsigned NOT NULL,
                question_key varchar(64) NOT NULL,
                answer_value varchar(16) NOT NULL,
                jurisdiction varchar(24) NOT NULL DEFAULT '*',
                finding_status varchar(16) NOT NULL,
                heading text NOT NULL,
                identified_text text NOT NULL,
                next_step_text text NOT NULL,
                why_text text NOT NULL,
                good_looks_text text NOT NULL,
                service_tags_json longtext NOT NULL,
                source_json longtext NOT NULL,
                sort_rank int NOT NULL DEFAULT 100,
                PRIMARY KEY  (id),
                UNIQUE KEY version_recommendation (content_version_id,question_key,answer_value,jurisdiction),
                KEY question_lookup (content_version_id,question_key)
            ) $charsetCollate;",
            "CREATE TABLE " . self::table('assessments') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                assessment_token_hash char(64) NOT NULL,
                report_token_hash char(64) NULL,
                status varchar(16) NOT NULL,
                content_version_id bigint unsigned NOT NULL,
                jurisdiction varchar(24) NULL,
                employee_band varchar(16) NULL,
                sector varchar(64) NULL,
                profile_json longtext NOT NULL,
                priority_count int NOT NULL DEFAULT 0,
                review_count int NOT NULL DEFAULT 0,
                addressed_count int NOT NULL DEFAULT 0,
                overall_status varchar(32) NULL,
                service_tags_json longtext NOT NULL,
                snapshot_json longtext NULL,
                contact_id bigint unsigned NULL,
                email_status varchar(16) NOT NULL DEFAULT 'not_sent',
                email_last_error text NULL,
                pdf_status varchar(16) NOT NULL DEFAULT 'not_generated',
                pdf_last_error text NULL,
                started_at datetime NOT NULL,
                assessed_at datetime NULL,
                completed_at datetime NULL,
                last_activity_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY assessment_token_hash (assessment_token_hash),
                UNIQUE KEY report_token_hash (report_token_hash),
                KEY status (status),
                KEY content_version_id (content_version_id),
                KEY contact_id (contact_id),
                KEY last_activity_at (last_activity_at),
                KEY completed_at (completed_at)
            ) $charsetCollate;",
            "CREATE TABLE " . self::table('answers') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                assessment_id bigint unsigned NOT NULL,
                question_key varchar(64) NOT NULL,
                question_row_id bigint unsigned NOT NULL,
                answer_value varchar(16) NOT NULL,
                finding_status varchar(16) NOT NULL,
                recommendation_row_id bigint unsigned NULL,
                answered_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY assessment_question (assessment_id,question_key),
                KEY question_row_id (question_row_id),
                KEY recommendation_row_id (recommendation_row_id),
                KEY finding_status (finding_status)
            ) $charsetCollate;",
            "CREATE TABLE " . self::table('contacts') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                first_name varchar(120) NOT NULL,
                last_name varchar(120) NOT NULL,
                company varchar(190) NOT NULL,
                email varchar(190) NOT NULL,
                telephone varchar(40) NULL,
                postcode varchar(16) NULL,
                audit_requested tinyint(1) NOT NULL DEFAULT 0,
                marketing_consent tinyint(1) NOT NULL DEFAULT 0,
                marketing_consent_at datetime NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY email (email),
                KEY audit_requested (audit_requested),
                KEY created_at (created_at)
            ) $charsetCollate;",
        ];

        foreach ($statements as $statement) {
            dbDelta($statement);
        }

        update_option('acorn_hc_schema_version', self::VERSION);
    }
}

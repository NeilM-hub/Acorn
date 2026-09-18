<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Database\Schema;

final class SchemaTest extends WP_UnitTestCase
{
    private const REQUIRED_COLUMNS = [
        'content_versions' => ['id', 'version_key', 'status', 'created_at', 'published_at'],
        'questions' => ['id', 'content_version_id', 'question_key', 'question_text', 'applicability_json', 'answer_rules_json', 'source_json'],
        'recommendations' => ['id', 'content_version_id', 'question_key', 'answer_value', 'jurisdiction', 'finding_status', 'good_looks_text'],
        'assessments' => ['id', 'assessment_token_hash', 'report_token_hash', 'status', 'content_version_id', 'profile_json', 'snapshot_json', 'contact_id'],
        'answers' => ['id', 'assessment_id', 'question_key', 'question_row_id', 'answer_value', 'finding_status', 'recommendation_row_id'],
        'contacts' => ['id', 'first_name', 'last_name', 'company', 'email', 'audit_requested', 'marketing_consent'],
    ];

    private const REQUIRED_INDEXES = [
        'content_versions' => ['PRIMARY' => false, 'version_key' => true],
        'questions' => ['PRIMARY' => false, 'version_question' => true],
        'recommendations' => ['PRIMARY' => false, 'version_recommendation' => true],
        'assessments' => ['PRIMARY' => false, 'assessment_token_hash' => true, 'report_token_hash' => true],
        'answers' => ['PRIMARY' => false, 'assessment_question' => true],
        'contacts' => ['PRIMARY' => false, 'email' => false],
    ];

    public function test_install_creates_required_tables_columns_and_indexes(): void
    {
        global $wpdb;

        Schema::install();

        foreach (self::REQUIRED_COLUMNS as $suffix => $requiredColumns) {
            $table = Schema::table($suffix);
            self::assertSame(
                $table,
                $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))),
                "The $suffix table was not created."
            );

            $columns = array_column($wpdb->get_results("SHOW COLUMNS FROM $table", ARRAY_A), 'Field');
            foreach ($requiredColumns as $column) {
                self::assertContains($column, $columns, "$table is missing $column.");
            }

            $indexes = [];
            foreach ($wpdb->get_results("SHOW INDEX FROM $table", ARRAY_A) as $index) {
                $indexes[$index['Key_name']] = (int) $index['Non_unique'] === 0;
            }
            foreach (self::REQUIRED_INDEXES[$suffix] as $indexName => $mustBeUnique) {
                self::assertArrayHasKey($indexName, $indexes, "$table is missing index $indexName.");
                if ($mustBeUnique) {
                    self::assertTrue($indexes[$indexName], "$table index $indexName must be unique.");
                }
            }
        }

        self::assertSame('1', get_option('acorn_hc_schema_version'));
        self::assertSame('', $wpdb->last_error);
    }

    public function test_install_is_idempotent(): void
    {
        global $wpdb;

        Schema::install();
        $before = $this->schemaSignatures();
        $wpdb->last_error = '';

        Schema::install();

        self::assertSame('', $wpdb->last_error);
        self::assertSame($before, $this->schemaSignatures());
    }

    private function schemaSignatures(): array
    {
        global $wpdb;

        $signatures = [];
        foreach (array_keys(self::REQUIRED_COLUMNS) as $suffix) {
            $table = Schema::table($suffix);
            $signatures[$suffix] = [
                'columns' => $wpdb->get_results("SHOW COLUMNS FROM $table", ARRAY_A),
                'indexes' => $wpdb->get_results("SHOW INDEX FROM $table", ARRAY_A),
            ];
        }

        return $signatures;
    }
}

<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Privacy;

use Acorn\SafetyHealthcheck\Database\Schema;

final class ExportEraser
{
    public function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters', fn(array $exporters): array => $exporters + ['acorn-healthcheck' => ['exporter_friendly_name' => 'Acorn Healthcheck', 'callback' => [$this, 'export']]]);
        add_filter('wp_privacy_personal_data_erasers', fn(array $erasers): array => $erasers + ['acorn-healthcheck' => ['eraser_friendly_name' => 'Acorn Healthcheck', 'callback' => [$this, 'erase']]]);
    }

    public function export(string $email, int $page = 1): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT c.*,a.id assessment_id,a.completed_at,a.overall_status,a.priority_count,a.review_count,a.addressed_count FROM ' . Schema::table('contacts') . ' c LEFT JOIN ' . Schema::table('assessments') . ' a ON a.contact_id=c.id WHERE c.email=%s LIMIT 100 OFFSET %d',
            sanitize_email($email), max(0, $page - 1) * 100
        ), ARRAY_A);
        $data = [];
        foreach ($rows as $row) $data[] = ['group_id' => 'acorn-healthcheck', 'group_label' => 'Acorn Healthcheck', 'item_id' => 'assessment-' . $row['assessment_id'], 'data' => [
            ['name' => 'Name', 'value' => trim($row['first_name'] . ' ' . $row['last_name'])],
            ['name' => 'Company', 'value' => $row['company']], ['name' => 'Email', 'value' => $row['email']],
            ['name' => 'Telephone', 'value' => $row['telephone']], ['name' => 'Postcode', 'value' => $row['postcode']],
            ['name' => 'Assessment date', 'value' => $row['completed_at']], ['name' => 'Overall result', 'value' => $row['overall_status']],
            ['name' => 'Finding counts', 'value' => "{$row['priority_count']} priority; {$row['review_count']} review; {$row['addressed_count']} addressed"],
        ]];
        return ['data' => $data, 'done' => count($rows) < 100];
    }

    public function erase(string $email, int $page = 1): array
    {
        global $wpdb;
        $contacts = $wpdb->get_results($wpdb->prepare('SELECT id FROM ' . Schema::table('contacts') . ' WHERE email=%s LIMIT 100', sanitize_email($email)), ARRAY_A);
        foreach ($contacts as $contact) {
            $assessments = $wpdb->get_results($wpdb->prepare('SELECT id,snapshot_json FROM ' . Schema::table('assessments') . ' WHERE contact_id=%d', $contact['id']), ARRAY_A);
            foreach ($assessments as $assessment) {
                $snapshot = json_decode($assessment['snapshot_json'], true) ?: [];
                $snapshot['profile']['company'] = 'Removed following privacy request';
                $wpdb->update(Schema::table('assessments'), ['report_token_hash' => null, 'snapshot_json' => wp_json_encode($snapshot)], ['id' => $assessment['id']]);
            }
            $wpdb->update(Schema::table('contacts'), ['first_name' => 'Removed following privacy request', 'last_name' => '', 'company' => 'Removed following privacy request', 'email' => '', 'telephone' => '', 'postcode' => ''], ['id' => $contact['id']]);
        }
        return ['items_removed' => (bool) $contacts, 'items_retained' => false, 'messages' => [], 'done' => count($contacts) < 100];
    }
}

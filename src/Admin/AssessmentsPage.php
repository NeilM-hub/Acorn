<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Admin;

use Acorn\SafetyHealthcheck\Assessment\AssessmentDeletionService;
use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Reports\{PdfGenerator, ReportDataBuilder};

final class AssessmentsPage
{
    public function render(): void
    {
        if (!current_user_can('manage_acorn_healthcheck')) {
            wp_die('Forbidden', 403);
        }

        if (isset($_GET['export'])) {
            $this->export();
            return;
        }

        if (!empty($_GET['assessment'])) {
            $this->detail(absint($_GET['assessment']));
            return;
        }

        global $wpdb;

        [$where, $args] = $this->filters();
        $page = max(1, absint($_GET['paged'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $from = ' FROM ' . Schema::table('assessments') . ' a'
            . ' LEFT JOIN ' . Schema::table('contacts') . ' c ON c.id=a.contact_id'
            . ' WHERE ' . implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*)' . $from;
        $select = 'SELECT a.id,a.status,a.jurisdiction,a.sector,a.overall_status,a.priority_count,a.review_count,'
            . 'a.started_at,a.completed_at,c.company,c.audit_requested'
            . $from . ' ORDER BY a.id DESC LIMIT %d OFFSET %d';

        $total = (int) $wpdb->get_var($args ? $wpdb->prepare($countSql, $args) : $countSql);
        $queryArgs = array_merge($args, [$perPage, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($select, $queryArgs), ARRAY_A);

        echo '<div class="wrap"><h1>Assessments</h1>';

        if (isset($_GET['deleted'])) {
            $deleted = absint($_GET['deleted']);
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html(sprintf(_n('%d assessment deleted.', '%d assessments deleted.', $deleted), $deleted))
                . '</p></div>';
        }

        $this->filterForm();

        echo '<p><a href="' . esc_url(wp_nonce_url(add_query_arg('export', 'csv'), 'acorn_hc_export')) . '">Export filtered CSV</a></p>';
        echo '<p class="description"><strong>Delete carefully:</strong> This permanently deletes the selected assessment data, its answers and any unshared contact record. It cannot be undone.</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Permanently delete the selected assessments? This cannot be undone.\');">';
        echo '<input type="hidden" name="action" value="acorn_hc_delete_assessments">';
        wp_nonce_field('acorn_hc_delete_assessments');

        echo '<table class="widefat striped"><thead><tr>';
        echo '<td class="check-column"><input type="checkbox" aria-label="Select all assessments" onclick="this.closest(\'table\').querySelectorAll(\'tbody input[type=checkbox]\').forEach(box => box.checked = this.checked)"></td>';
        echo '<th>Company</th><th>Status</th><th>Date</th><th>Jurisdiction</th><th>Sector</th><th>Findings</th><th>Actions</th>';
        echo '</tr></thead><tbody>';

        if (!$rows) {
            echo '<tr><td colspan="8">No assessments found.</td></tr>';
        }

        foreach ($rows as $row) {
            $date = $row['completed_at'] ?: $row['started_at'];
            echo '<tr>';
            echo '<th scope="row" class="check-column"><input type="checkbox" name="assessment_ids[]" value="' . (int) $row['id'] . '" aria-label="Select assessment ' . (int) $row['id'] . '"></th>';
            echo '<td>' . esc_html($row['company'] ?: 'Anonymous') . '</td>';
            echo '<td>' . esc_html($row['status']) . '</td>';
            echo '<td>' . esc_html($date ? mysql2date('j M Y H:i', $date) : '') . '</td>';
            echo '<td>' . esc_html($row['jurisdiction']) . '</td>';
            echo '<td>' . esc_html($row['sector']) . '</td>';
            echo '<td>' . (int) $row['priority_count'] . ' priority, ' . (int) $row['review_count'] . ' review</td>';
            echo '<td><a href="' . esc_url(add_query_arg('assessment', (int) $row['id'])) . '">View details</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        if ($rows) {
            submit_button('Delete selected', 'delete', 'submit', false);
        }

        echo '</form>';

        echo paginate_links([
            'total' => max(1, (int) ceil($total / $perPage)),
            'current' => $page,
        ]);

        echo '</div>';
    }

    public function deleteAssessments(): void
    {
        if (!current_user_can('manage_acorn_healthcheck')) {
            wp_die('Forbidden', 403);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die('Invalid request method.', 405);
        }

        check_admin_referer('acorn_hc_delete_assessments');

        $rawIds = $_POST['assessment_ids'] ?? [];
        $ids = is_array($rawIds) ? array_map('absint', wp_unslash($rawIds)) : [];
        $deleted = (new AssessmentDeletionService())->deleteMany($ids);

        wp_safe_redirect(admin_url('admin.php?page=acorn-healthcheck-assessments&deleted=' . $deleted));
        exit;
    }

    public function downloadPdf(): void
    {
        $id = $this->authorisedAssessment('acorn_hc_pdf');
        (new PdfGenerator())->stream((new ReportDataBuilder())->build($id), 'Acorn-Healthcheck-' . $id . '.pdf');
        exit;
    }

    public function resend(): void
    {
        $id = $this->authorisedAssessment('acorn_hc_resend');
        global $wpdb;

        $assessment = $wpdb->get_row(
            $wpdb->prepare('SELECT status FROM ' . Schema::table('assessments') . ' WHERE id=%d', $id),
            ARRAY_A
        );

        if (($assessment['status'] ?? '') !== 'completed') {
            wp_die('A completed report is required.', 409);
        }

        try {
            $sent = (new \Acorn\SafetyHealthcheck\Notifications\ReportResender())->sendWithFreshToken($id);
        } catch (\Throwable $error) {
            $sent = false;
        }

        if (!$sent) {
            $wpdb->update(
                Schema::table('assessments'),
                [
                    'email_status' => 'failed',
                    'email_last_error' => wp_json_encode([
                        'customer' => 'Admin resend failed; the previous secure report link remains valid.',
                    ]),
                ],
                ['id' => $id]
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=acorn-healthcheck-assessments&assessment=' . $id));
        exit;
    }

    private function detail(int $id): void
    {
        global $wpdb;

        $assessment = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . Schema::table('assessments') . ' WHERE id=%d', $id),
            ARRAY_A
        );

        if (!$assessment) {
            echo '<div class="wrap"><p>Assessment not found.</p></div>';
            return;
        }

        $answers = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT question_key,answer_value,finding_status,answered_at FROM ' . Schema::table('answers') . ' WHERE assessment_id=%d ORDER BY id',
                $id
            ),
            ARRAY_A
        );

        $contact = $assessment['contact_id']
            ? $wpdb->get_row(
                $wpdb->prepare('SELECT * FROM ' . Schema::table('contacts') . ' WHERE id=%d', $assessment['contact_id']),
                ARRAY_A
            )
            : [];

        echo '<div class="wrap"><h1>Assessment #' . (int) $id . '</h1>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=acorn-healthcheck-assessments')) . '">&larr; Back to assessments</a></p>';
        echo '<h2>Overview</h2><p>'
            . esc_html($assessment['overall_status'] ?: $assessment['status'])
            . ' — ' . (int) $assessment['priority_count'] . ' priority, '
            . (int) $assessment['review_count'] . ' review, '
            . (int) $assessment['addressed_count'] . ' addressed</p>';

        echo '<h2>Answers</h2><table class="widefat"><tr><th>Question</th><th>Answer</th><th>Finding</th></tr>';
        foreach ($answers as $answer) {
            echo '<tr><td>' . esc_html($answer['question_key']) . '</td><td>'
                . esc_html($answer['answer_value']) . '</td><td>'
                . esc_html($answer['finding_status']) . '</td></tr>';
        }
        echo '</table>';

        echo '<h2>Contact</h2><p>'
            . esc_html(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '') . ' · ' . ($contact['email'] ?? ''))
            . '</p>';

        if ($assessment['status'] === 'completed') {
            $report = (new ReportDataBuilder())->build($id);
            echo '<h2>Canonical recommendations</h2><ul>';
            foreach (array_merge($report['priority'], $report['review']) as $finding) {
                echo '<li><strong>' . esc_html($finding['recommendation']['heading']) . '</strong>: '
                    . esc_html($finding['recommendation']['next_step_text']) . '</li>';
            }
            echo '</ul><p><a class="button" href="'
                . esc_url(wp_nonce_url(admin_url('admin-post.php?action=acorn_hc_pdf&assessment=' . $id), 'acorn_hc_pdf_' . $id))
                . '">Download PDF</a> <a class="button" href="'
                . esc_url(wp_nonce_url(admin_url('admin-post.php?action=acorn_hc_resend&assessment=' . $id), 'acorn_hc_resend_' . $id))
                . '">Resend report</a></p>';
        }

        echo '<p>Delivery status: ' . esc_html($assessment['email_status']) . '; PDF: ' . esc_html($assessment['pdf_status']) . '.</p>';

        echo '<hr><h2>Delete assessment</h2>';
        echo '<p class="description">This permanently deletes this assessment, its answers and any unshared contact record. This cannot be undone.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Permanently delete this assessment? This cannot be undone.\');">';
        echo '<input type="hidden" name="action" value="acorn_hc_delete_assessments">';
        echo '<input type="hidden" name="assessment_ids[]" value="' . (int) $id . '">';
        wp_nonce_field('acorn_hc_delete_assessments');
        submit_button('Delete assessment', 'delete', 'submit', false);
        echo '</form></div>';
    }

    private function filters(): array
    {
        global $wpdb;

        $where = ['1=1'];
        $args = [];
        $fields = [
            'status' => 'a.status',
            'jurisdiction' => 'a.jurisdiction',
            'sector' => 'a.sector',
            'overall_result' => 'a.overall_status',
            'audit_requested' => 'c.audit_requested',
        ];

        foreach ($fields as $key => $column) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $where[] = "$column=%s";
                $args[] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }

        if (!empty($_GET['company'])) {
            $where[] = 'c.company LIKE %s';
            $args[] = '%' . $wpdb->esc_like(sanitize_text_field(wp_unslash($_GET['company']))) . '%';
        }

        if (!empty($_GET['date_from'])) {
            $where[] = 'a.started_at >= %s';
            $args[] = sanitize_text_field(wp_unslash($_GET['date_from'])) . ' 00:00:00';
        }

        if (!empty($_GET['date_to'])) {
            $where[] = 'a.started_at <= %s';
            $args[] = sanitize_text_field(wp_unslash($_GET['date_to'])) . ' 23:59:59';
        }

        if (!empty($_GET['opportunity_tag'])) {
            $where[] = 'a.service_tags_json LIKE %s';
            $args[] = '%"' . $wpdb->esc_like(sanitize_key($_GET['opportunity_tag'])) . '"%';
        }

        return [$where, $args];
    }

    private function filterForm(): void
    {
        echo '<form method="get"><input type="hidden" name="page" value="acorn-healthcheck-assessments">';

        foreach (['date_from', 'date_to', 'company', 'overall_result', 'jurisdiction', 'sector', 'opportunity_tag'] as $field) {
            echo '<input name="' . esc_attr($field) . '" placeholder="'
                . esc_attr(ucwords(str_replace('_', ' ', $field)))
                . '" value="' . esc_attr($_GET[$field] ?? '') . '">';
        }

        echo '<select name="audit_requested"><option value="">Any audit status</option>'
            . '<option value="1">Requested</option><option value="0">Not requested</option></select>';

        submit_button('Filter', 'secondary', '', false);
        echo '</form>';
    }

    private function export(): void
    {
        check_admin_referer('acorn_hc_export');
        global $wpdb;

        [$where, $args] = $this->filters();
        $sql = 'SELECT a.id,a.status,a.jurisdiction,a.sector,a.overall_status,a.priority_count,a.review_count,a.addressed_count,a.completed_at,c.company,c.email,c.audit_requested'
            . ' FROM ' . Schema::table('assessments') . ' a'
            . ' LEFT JOIN ' . Schema::table('contacts') . ' c ON c.id=a.contact_id'
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY a.id DESC';

        $rows = $args
            ? $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A)
            : $wpdb->get_results($sql, ARRAY_A);

        header('Content-Type:text/csv');
        header('Content-Disposition:attachment; filename=healthcheck-assessments.csv');

        $out = fopen('php://output', 'wb');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }

    private function authorisedAssessment(string $action): int
    {
        if (!current_user_can('manage_acorn_healthcheck')) {
            wp_die('Forbidden', 403);
        }

        $id = absint($_GET['assessment'] ?? 0);
        check_admin_referer($action . '_' . $id);

        return $id;
    }
}

<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Reports;

final class ReportController
{
    public function register(): void
    {
        add_action('init', [$this, 'registerRewriteRules']);
        add_action('template_redirect', [$this, 'render']);
    }

    public function registerRewriteRules(): void
    {
        add_rewrite_rule('^healthcheck/report/([A-Za-z0-9_-]+)/pdf/?$', 'index.php?acorn_hc_report=$matches[1]&acorn_hc_pdf=1', 'top');
        add_rewrite_rule('^healthcheck/report/([A-Za-z0-9_-]+)/?$', 'index.php?acorn_hc_report=$matches[1]', 'top');
        add_rewrite_tag('%acorn_hc_report%', '([A-Za-z0-9_-]+)');
        add_rewrite_tag('%acorn_hc_pdf%', '1');
    }

    public function render(): void
    {
        $token = (string) get_query_var('acorn_hc_report');
        if ($token === '') return;
        $assessmentId = (new ReportAccess())->findAssessmentId($token);
        if (!$assessmentId) { status_header(404); nocache_headers(); exit; }
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: private, no-store, max-age=0');
        $report = (new ReportDataBuilder())->build($assessmentId);
        wp_enqueue_style('acorn-healthcheck', plugins_url('assets/css/healthcheck.css', ACORN_HC_FILE), [], ACORN_HC_VERSION);
        if ((string) get_query_var('acorn_hc_pdf') === '1') {
            (new PdfGenerator())->stream($report, 'Acorn-Healthcheck-' . sanitize_file_name($report['meta']['company']) . '.pdf');
            exit;
        }
        $reportUrl = home_url('/healthcheck/report/' . rawurlencode($token) . '/');
        require ACORN_HC_DIR . 'templates/report-web.php';
        exit;
    }
}

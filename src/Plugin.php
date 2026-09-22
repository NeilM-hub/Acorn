<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck;

final class Plugin
{
    public function boot(): void
    {
        \Acorn\SafetyHealthcheck\Content\ConciseContentUpgrade::installIfNeeded();
        \Acorn\SafetyHealthcheck\Content\SimplifiedContentUpgrade::installIfNeeded();
        \Acorn\SafetyHealthcheck\Content\ChecklistCoverageUpgrade::installIfNeeded();
        add_action('admin_enqueue_scripts', [$this, 'adminAssets']);
        add_action('admin_menu', [new \Acorn\SafetyHealthcheck\Admin\Menu(), 'register']);
        add_action('admin_post_acorn_hc_pdf', [new \Acorn\SafetyHealthcheck\Admin\AssessmentsPage(), 'downloadPdf']);
        add_action('admin_post_acorn_hc_resend', [new \Acorn\SafetyHealthcheck\Admin\AssessmentsPage(), 'resend']);
        add_shortcode('acorn_safety_healthcheck', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('rest_api_init', [new \Acorn\SafetyHealthcheck\Rest\AssessmentController(), 'register']);
        add_action('rest_api_init', [new \Acorn\SafetyHealthcheck\Rest\CompletionController(), 'register']);
        (new \Acorn\SafetyHealthcheck\Reports\ReportController())->register();
        add_action('acorn_hc_daily_cleanup', [new \Acorn\SafetyHealthcheck\Privacy\Retention(), 'cleanup']);
        (new \Acorn\SafetyHealthcheck\Privacy\ExportEraser())->register();
        do_action('acorn_hc_booted', $this);
    }

    public function adminAssets(string $hook): void
    {
        if (str_contains($hook, 'acorn-healthcheck')) {
            wp_enqueue_style(
                'acorn-healthcheck-admin',
                plugins_url('assets/css/admin.css', ACORN_HC_FILE),
                [],
                ACORN_HC_VERSION
            );
        }

        if (str_contains($hook, 'acorn-healthcheck-settings')) {
            wp_enqueue_media();
            wp_enqueue_script(
                'acorn-healthcheck-admin-settings',
                plugins_url('assets/js/admin-settings.js', ACORN_HC_FILE),
                [],
                ACORN_HC_VERSION,
                true
            );
        }
    }

    public function shortcode(): string
    {
        $landingDefaults = require ACORN_HC_DIR . 'config/landing-page-defaults.php';
        $savedLanding = get_option('acorn_hc_landing_content', []);
        $landingContent = array_merge(
            $landingDefaults,
            is_array($savedLanding) ? $savedLanding : []
        );

        $settingsDefaults = require ACORN_HC_DIR . 'config/settings-defaults.php';
        $savedSettings = get_option('acorn_hc_settings', []);
        $healthcheckSettings = array_merge(
            $settingsDefaults,
            is_array($savedSettings) ? $savedSettings : []
        );

        $landingLogoUrl = (string) $healthcheckSettings['report_logo_url'];
        $landingLogoId = (int) ($healthcheckSettings['report_logo_attachment_id'] ?? 0);
        if ($landingLogoId) {
            $attachmentLogoUrl = wp_get_attachment_image_url($landingLogoId, 'full');
            if ($attachmentLogoUrl) {
                $landingLogoUrl = (string) $attachmentLogoUrl;
            }
        }

        ob_start();
        require ACORN_HC_DIR . 'templates/shortcode-shell.php';

        return (string) ob_get_clean();
    }

    public function assets(): void
    {
        global $post;

        if (!$post || !has_shortcode((string) $post->post_content, 'acorn_safety_healthcheck')) {
            return;
        }

        wp_enqueue_style(
            'acorn-healthcheck',
            plugins_url('assets/css/healthcheck.css', ACORN_HC_FILE),
            [],
            ACORN_HC_VERSION
        );

        wp_enqueue_script_module(
            'acorn-healthcheck',
            plugins_url('assets/js/healthcheck.js', ACORN_HC_FILE),
            [],
            ACORN_HC_VERSION
        );
    }
}

<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Reports\ReportController;

final class ReportControllerLifecycleTest extends WP_UnitTestCase
{
    public function test_register_only_adds_lifecycle_hooks_when_rewrite_is_unavailable(): void
    {
        global $wp_rewrite;

        $originalRewrite = $wp_rewrite;
        $wp_rewrite = null;
        $controller = new ReportController();

        try {
            $controller->register();

            self::assertNotFalse(has_action('init', [$controller, 'registerRewriteRules']));
            self::assertNotFalse(has_action('template_redirect', [$controller, 'render']));
            self::assertNull($wp_rewrite);
        } finally {
            remove_action('init', [$controller, 'registerRewriteRules']);
            remove_action('template_redirect', [$controller, 'render']);
            $wp_rewrite = $originalRewrite;
        }
    }

    public function test_rewrite_rules_are_registered_explicitly(): void
    {
        global $wp_rewrite;

        $originalRewrite = $wp_rewrite;
        $wp_rewrite = new WP_Rewrite();

        try {
            (new ReportController())->registerRewriteRules();

            self::assertArrayHasKey(
                '^healthcheck/report/([A-Za-z0-9_-]+)/pdf/?$',
                $wp_rewrite->extra_rules_top
            );
            self::assertArrayHasKey(
                '^healthcheck/report/([A-Za-z0-9_-]+)/?$',
                $wp_rewrite->extra_rules_top
            );
        } finally {
            $wp_rewrite = $originalRewrite;
        }
    }
}

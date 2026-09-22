<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Admin\Menu;
use Acorn\SafetyHealthcheck\Admin\LandingPage;
use Acorn\SafetyHealthcheck\Plugin;

final class LandingPageAdminTest extends IntegrationTestCase
{
    public function tear_down(): void
    {
        delete_option('acorn_hc_landing_content');
        parent::tear_down();
    }

    public function test_shortcode_exposes_saved_landing_copy_to_frontend(): void
    {
        update_option('acorn_hc_landing_content', [
            'hero_title_line_1' => 'A custom Healthcheck heading',
        ]);

        $html = (new Plugin())->shortcode();

        self::assertStringContainsString('A custom Healthcheck heading', $html);
    }

    public function test_admin_menu_registers_landing_page_editor(): void
    {
        global $submenu;
        $submenu = [];

        (new Menu())->register();

        $slugs = array_column($submenu['acorn-healthcheck'] ?? [], 2);
        self::assertContains('acorn-healthcheck-landing', $slugs);
    }

    public function test_canonical_defaults_match_v1_2_5_landing_copy(): void
    {
        $defaults = require dirname(__DIR__, 2) . '/config/landing-page-defaults.php';

        self::assertSame('Find the gaps.', $defaults['hero_title_line_1']);
        self::assertSame('Know what to do next.', $defaults['hero_title_line_2']);
        self::assertSame('Priority actions', $defaults['preview_priority_label']);
        self::assertSame('Request a free Compliance Audit', $defaults['audit_cta_text']);
        self::assertFileDoesNotExist(dirname(__DIR__, 2) . '/config/landing-defaults.php');
    }

    public function test_landing_editor_sanitizes_copy_and_section_switches(): void
    {
        $defaults = require dirname(__DIR__, 2) . '/config/landing-page-defaults.php';
        $saved = (new LandingPage())->sanitize([
            'hero_title_line_1' => '<b>Safer heading</b>',
            'benefits_intro' => "First line\nSecond line<script>alert(1)</script>",
            'show_benefits' => '1',
        ], $defaults);

        self::assertSame('Safer heading', $saved['hero_title_line_1']);
        self::assertStringNotContainsString('<script>', $saved['benefits_intro']);
        self::assertSame(1, $saved['show_benefits']);
        self::assertSame(0, $saved['show_faq']);
    }
}

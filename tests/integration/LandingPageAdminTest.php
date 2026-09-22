<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Admin\Menu;
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
}

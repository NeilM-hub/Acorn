<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Admin\SettingsPage;

final class SettingsAdminTest extends IntegrationTestCase
{
    public function test_settings_page_groups_agreed_general_and_operational_settings(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        get_role('administrator')->add_cap('manage_acorn_healthcheck');

        ob_start();
        (new SettingsPage())->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('General Settings', $html);
        self::assertStringContainsString('Logo', $html);
        self::assertStringContainsString('Choose logo', $html);
        self::assertStringContainsString('name="report_logo_attachment_id"', $html);
        self::assertStringContainsString('name="report_logo_url"', $html);
        self::assertStringContainsString('Phone number', $html);
        self::assertStringContainsString('Website', $html);
        self::assertStringContainsString('Free Compliance Audit URL', $html);
        self::assertStringContainsString('Privacy policy URL', $html);
        self::assertStringContainsString('Customer email subject', $html);
        self::assertStringContainsString('Operational Settings', $html);
        self::assertStringContainsString('Completed assessment retention', $html);
    }
}

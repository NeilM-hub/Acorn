<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Admin;

final class SettingsPage
{
    public function render(): void
    {
        if (!current_user_can('manage_acorn_healthcheck')) {
            wp_die('Forbidden', 403);
        }

        $defaults = require dirname(__DIR__, 2) . '/config/settings-defaults.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('acorn_hc_settings');
            $settings = [];

            foreach ($defaults as $key => $default) {
                if (is_int($default)) {
                    $settings[$key] = absint($_POST[$key] ?? $default);
                } elseif (str_ends_with($key, '_url') || $key === 'report_website') {
                    $settings[$key] = esc_url_raw((string) wp_unslash($_POST[$key] ?? $default));
                } elseif ($key === 'internal_recipient') {
                    $settings[$key] = sanitize_email((string) wp_unslash($_POST[$key] ?? $default));
                } else {
                    $settings[$key] = sanitize_text_field((string) wp_unslash($_POST[$key] ?? $default));
                }
            }

            update_option('acorn_hc_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        $settings = array_merge($defaults, get_option('acorn_hc_settings', []));

        $general = [
            'report_contact_phone' => ['Phone number', 'text'],
            'report_website' => ['Website', 'url'],
            'audit_cta_url' => ['Free Compliance Audit URL', 'url'],
            'privacy_policy_url' => ['Privacy policy URL', 'url'],
            'customer_email_subject' => ['Customer email subject', 'text'],
        ];

        $operational = [
            'report_horizontal_logo_url' => ['Horizontal logo URL', 'url'],
            'internal_recipient' => ['Internal notification email', 'email'],
            'completed_retention_days' => ['Completed assessment retention (days)', 'number'],
            'incomplete_retention_days' => ['Incomplete assessment retention (days)', 'number'],
            'pdf_footer' => ['PDF footer', 'text'],
        ];

        echo '<div class="wrap acorn-hc-settings"><h1>Healthcheck Settings</h1>';
        echo '<form method="post">';
        wp_nonce_field('acorn_hc_settings');

        echo '<section class="acorn-hc-editor__section"><h2>General Settings</h2><p>Customer-facing contact, branding and link settings.</p>';
        $this->logoField($settings);
        echo '<div class="acorn-hc-editor__fields">';
        $this->fields($general, $settings);
        echo '</div></section>';

        $this->section('Operational Settings', 'Existing technical and retention settings used by reports, notifications and cleanup.', $operational, $settings);

        submit_button();
        echo '</form></div>';
    }

    private function logoField(array $settings): void
    {
        $url = (string) ($settings['report_logo_url'] ?? '');
        $attachmentId = (int) ($settings['report_logo_attachment_id'] ?? 0);

        echo '<div class="acorn-hc-logo-field">';
        echo '<strong>Logo</strong>';
        echo '<div class="acorn-hc-logo-field__preview">';
        if ($url !== '') {
            echo '<img src="' . esc_url($url) . '" alt="Current Healthcheck logo">';
        }
        echo '</div>';
        echo '<input type="hidden" name="report_logo_attachment_id" value="' . esc_attr((string) $attachmentId) . '">';
        echo '<input type="hidden" name="report_logo_url" value="' . esc_attr($url) . '">';
        echo '<p><button type="button" class="button" data-acorn-logo-choose>Choose logo</button> ';
        echo '<button type="button" class="button-link-delete" data-acorn-logo-remove>Remove logo</button></p>';
        echo '<p class="description">Choose an image from the WordPress Media Library. This logo is used on the Healthcheck landing page and reports.</p>';
        echo '</div>';
    }

    private function section(string $title, string $description, array $fields, array $settings): void
    {
        echo '<section class="acorn-hc-editor__section">';
        echo '<h2>' . esc_html($title) . '</h2><p>' . esc_html($description) . '</p>';
        echo '<div class="acorn-hc-editor__fields">';
        $this->fields($fields, $settings);
        echo '</div></section>';
    }

    private function fields(array $fields, array $settings): void
    {
        foreach ($fields as $key => [$label, $type]) {
            echo '<label><strong>' . esc_html($label) . '</strong>';
            echo '<input class="regular-text" type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . esc_attr((string) $settings[$key]) . '">';
            echo '</label>';
        }
    }
}

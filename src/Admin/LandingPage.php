<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Admin;

final class LandingPage
{
    private const OPTION = 'acorn_hc_landing_content';

    public function sanitize(array $input, array $defaults): array
    {
        $clean = [];

        foreach ($defaults as $key => $default) {
            if (str_starts_with($key, 'show_')) {
                $clean[$key] = isset($input[$key]) && (string) $input[$key] === '1' ? 1 : 0;
                continue;
            }

            $value = wp_unslash($input[$key] ?? $default);
            $clean[$key] = str_contains($key, '_body')
                || str_contains($key, '_intro')
                || str_contains($key, '_answer')
                || in_array($key, ['hero_intro', 'disclaimer'], true)
                ? sanitize_textarea_field((string) $value)
                : sanitize_text_field((string) $value);
        }

        return $clean;
    }

    public function render(): void
    {
        if (!current_user_can('manage_acorn_healthcheck')) {
            wp_die('Forbidden', 403);
        }

        $defaults = require dirname(__DIR__, 2) . '/config/landing-page-defaults.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('acorn_hc_landing');

            if (($_POST['acorn_hc_action'] ?? '') === 'restore') {
                delete_option(self::OPTION);
                echo '<div class="notice notice-success"><p>Default landing page wording restored.</p></div>';
            } else {
                update_option(self::OPTION, $this->sanitize($_POST, $defaults));
                echo '<div class="notice notice-success"><p>Landing page changes saved and are now live.</p></div>';
            }
        }

        $saved = get_option(self::OPTION, []);
        $values = array_merge($defaults, is_array($saved) ? $saved : []);

        $sections = [
            'Hero' => [
                'hero_badge' => 'Badge',
                'hero_title_line_1' => 'Heading line 1',
                'hero_title_line_2' => 'Heading line 2',
                'hero_intro' => 'Introduction',
                'primary_cta' => 'Start button',
                'hero_micro' => 'Supporting line',
                'hero_topic_1' => 'Topic 1',
                'hero_topic_2' => 'Topic 2',
                'hero_topic_3' => 'Topic 3',
                'hero_topic_4' => 'Topic 4',
            ],
            'Built by specialists' => [
                'authority_eyebrow' => 'Eyebrow',
                'authority_heading' => 'Heading',
                'authority_body' => 'Paragraph',
            ],
            'Benefits' => [
                'benefits_eyebrow' => 'Eyebrow',
                'benefits_heading' => 'Heading',
                'benefits_intro' => 'Introduction',
                'benefit_1_title' => 'Card 1 heading',
                'benefit_1_body' => 'Card 1 text',
                'benefit_2_title' => 'Card 2 heading',
                'benefit_2_body' => 'Card 2 text',
                'benefit_3_title' => 'Card 3 heading',
                'benefit_3_body' => 'Card 3 text',
                'benefit_4_title' => 'Card 4 heading',
                'benefit_4_body' => 'Card 4 text',
            ],
            'How It Works' => [
                'how_eyebrow' => 'Eyebrow',
                'how_heading' => 'Heading',
                'how_1_title' => 'Step 1 heading',
                'how_1_body' => 'Step 1 text',
                'how_2_title' => 'Step 2 heading',
                'how_2_body' => 'Step 2 text',
                'how_3_title' => 'Step 3 heading',
                'how_3_body' => 'Step 3 text',
            ],
            'Expertise' => [
                'expertise_eyebrow' => 'Eyebrow',
                'expertise_heading' => 'Heading',
                'expertise_hs_title' => 'Health & Safety heading',
                'expertise_hs_body' => 'Health & Safety text',
                'expertise_fire_title' => 'Fire heading',
                'expertise_fire_body' => 'Fire text',
                'expertise_legionella_title' => 'Legionella heading',
                'expertise_legionella_body' => 'Legionella text',
                'expertise_asbestos_title' => 'Asbestos heading',
                'expertise_asbestos_body' => 'Asbestos text',
            ],
            'Insight / Callout' => [
                'insight_eyebrow' => 'Eyebrow',
                'insight_heading' => 'Heading',
                'insight_body_1' => 'Paragraph 1',
                'insight_body_2' => 'Paragraph 2',
                'insight_callout_heading' => 'Callout heading',
                'insight_callout_body' => 'Callout text',
            ],
            'Example Results' => [
                'preview_eyebrow' => 'Eyebrow',
                'preview_heading' => 'Heading',
                'preview_intro' => 'Introduction',
                'preview_priority_count' => 'Priority count',
                'preview_priority_label' => 'Priority label',
                'preview_review_count' => 'Review count',
                'preview_review_label' => 'Review label',
                'preview_good_count' => 'Good count',
                'preview_good_label' => 'Good label',
                'preview_card_eyebrow' => 'Example eyebrow',
                'preview_card_heading' => 'Example heading',
                'preview_card_body' => 'Example text',
                'preview_card_next_label' => 'Next-step label',
                'preview_card_next_text' => 'Next-step text',
            ],
            'Support' => [
                'support_eyebrow' => 'Eyebrow',
                'support_heading' => 'Heading',
                'support_intro' => 'Introduction',
                'support_hs_title' => 'Health & Safety heading',
                'support_hs_body' => 'Health & Safety text',
                'support_fire_title' => 'Fire heading',
                'support_fire_body' => 'Fire text',
                'support_legionella_title' => 'Legionella heading',
                'support_legionella_body' => 'Legionella text',
                'support_asbestos_title' => 'Asbestos heading',
                'support_asbestos_body' => 'Asbestos text',
                'audit_cta_text' => 'Compliance Audit button',
            ],
            'FAQs' => [
                'faq_eyebrow' => 'Eyebrow',
                'faq_heading' => 'Heading',
                'faq_1_question' => 'Question 1',
                'faq_1_answer' => 'Answer 1',
                'faq_2_question' => 'Question 2',
                'faq_2_answer' => 'Answer 2',
                'faq_3_question' => 'Question 3',
                'faq_3_answer' => 'Answer 3',
                'faq_4_question' => 'Question 4',
                'faq_4_answer' => 'Answer 4',
                'faq_5_question' => 'Question 5',
                'faq_5_answer' => 'Answer 5',
            ],
            'Final CTA & Disclaimer' => [
                'final_eyebrow' => 'Eyebrow',
                'final_heading' => 'Heading',
                'final_body' => 'Paragraph',
                'final_micro' => 'Supporting line',
                'disclaimer' => 'Disclaimer',
            ],
        ];

        $switches = [
            'Built by specialists' => 'show_authority',
            'Benefits' => 'show_benefits',
            'How It Works' => 'show_how_it_works',
            'Expertise' => 'show_expertise',
            'Insight / Callout' => 'show_insight',
            'Example Results' => 'show_preview',
            'Support' => 'show_support',
            'FAQs' => 'show_faq',
            'Final CTA & Disclaimer' => 'show_final_cta',
        ];

        echo '<div class="wrap acorn-hc-editor"><h1>Landing Page</h1>';
        echo '<p>Edit the wording shown on the Healthcheck landing page. Layout, colours and responsive design remain fixed.</p>';
        echo '<form method="post">';
        wp_nonce_field('acorn_hc_landing');

        foreach ($sections as $title => $fields) {
            echo '<section class="acorn-hc-editor__section">';
            echo '<div class="acorn-hc-editor__section-head"><h2>' . esc_html($title) . '</h2>';
            if (isset($switches[$title])) {
                $key = $switches[$title];
                echo '<label class="acorn-hc-editor__toggle"><input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . checked(1, (int) $values[$key], false) . '> Show this section</label>';
            }
            echo '</div><div class="acorn-hc-editor__fields">';

            foreach ($fields as $key => $label) {
                $long = str_contains($key, '_body')
                    || str_contains($key, '_intro')
                    || str_contains($key, '_answer')
                    || in_array($key, ['hero_intro', 'disclaimer'], true);

                echo '<label><strong>' . esc_html($label) . '</strong>';
                if ($long) {
                    echo '<textarea name="' . esc_attr($key) . '" rows="3">' . esc_textarea((string) $values[$key]) . '</textarea>';
                } else {
                    echo '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr((string) $values[$key]) . '">';
                }
                echo '</label>';
            }

            echo '</div></section>';
        }

        echo '<div class="acorn-hc-editor__actions">';
        submit_button('Save Changes', 'primary', 'submit', false);
        echo ' ';
        submit_button('Restore default wording', 'secondary', 'acorn_hc_action', false, ['value' => 'restore', 'onclick' => "return confirm('Restore all landing page wording to the v1.2.5 defaults?');"]);
        echo '</div></form></div>';
    }
}

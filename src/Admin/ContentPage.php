<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Admin;

use Acorn\SafetyHealthcheck\Content\{ContentPublisher, ContentVersionRepository, QuestionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ContentPage
{
    public function render(): void
    {
        if (!current_user_can('manage_acorn_healthcheck')) wp_die('Forbidden', 403);

        global $wpdb;
        $publisher = new ContentPublisher();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            check_admin_referer('acorn_hc_content');
            $action = sanitize_key($_POST['content_action'] ?? '');

            try {
                if ($action === 'create_draft') {
                    $publisher->createDraftFromPublished(get_current_user_id());
                } elseif ($action === 'publish') {
                    $publisher->publish(absint($_POST['version_id']), get_current_user_id());
                } elseif ($action === 'question') {
                    $jurisdictions = $this->jsonArray('source_jurisdictions_json', []);
                    $publisher->updateDraftQuestion(
                        absint($_POST['version_id']),
                        $this->questionKey($_POST['question_key'] ?? ''),
                        [
                            'question_text' => wp_unslash($_POST['question_text'] ?? ''),
                            'help_text' => wp_unslash($_POST['help_text'] ?? ''),
                            'module_key' => $_POST['module_key'] ?? '',
                            'sort_order' => $_POST['sort_order'] ?? 0,
                            'is_active' => isset($_POST['is_active']),
                            'variants' => $this->jsonArray('variant_json', []),
                            'applicability' => $this->jsonArray('applicability_json'),
                            'source' => [
                                'default' => [
                                    'title' => wp_unslash($_POST['source_title'] ?? ''),
                                    'url' => wp_unslash($_POST['source_url'] ?? ''),
                                ],
                                'jurisdictions' => $jurisdictions,
                            ],
                            'reviewed_by' => wp_unslash($_POST['reviewed_by'] ?? ''),
                            'last_reviewed' => $_POST['last_reviewed'] ?? '',
                            'next_review' => $_POST['next_review'] ?? '',
                        ]
                    );
                } elseif ($action === 'recommendation') {
                    $publisher->updateDraftRecommendation(
                        absint($_POST['version_id']),
                        absint($_POST['recommendation_id']),
                        $this->recommendationValues()
                    );
                } elseif ($action === 'create_recommendation') {
                    $publisher->createDraftRecommendation(
                        absint($_POST['version_id']),
                        array_merge(
                            ['question_key' => $this->questionKey($_POST['question_key'] ?? '')],
                            $this->recommendationValues(),
                            ['answer_value' => sanitize_key($_POST['answer_value'] ?? '')]
                        )
                    );
                } elseif ($action === 'delete_recommendation') {
                    $publisher->deleteDraftRecommendation(
                        absint($_POST['version_id']),
                        absint($_POST['recommendation_id'])
                    );
                }

                echo '<div class="notice notice-success"><p>Content action completed.</p></div>';
            } catch (\Throwable $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error->getMessage()) . '</p></div>';
            }
        }

        $published = (new ContentVersionRepository())->getPublished();
        $draft = $wpdb->get_row(
            "SELECT * FROM " . Schema::table('content_versions') . " WHERE status='draft' ORDER BY id DESC LIMIT 1",
            ARRAY_A
        );

        echo '<div class="wrap"><h1>Questions &amp; Recommendations</h1>';
        echo '<p>Published version ' . esc_html($published['version_key']) . ' is immutable. All edits below target a draft.</p>';

        if (!$draft) {
            echo '<form method="post">';
            wp_nonce_field('acorn_hc_content');
            echo '<input type="hidden" name="content_action" value="create_draft">';
            submit_button('Create draft from published');
            echo '</form></div>';
            return;
        }

        $questions = (new QuestionRepository())->allForVersion((int) $draft['id']);
        $selected = $this->questionKey($_GET['question'] ?? ($questions[0]['question_key'] ?? ''));
        $question = (new QuestionRepository())->get((int) $draft['id'], $selected);

        echo '<p>Editing draft ' . esc_html($draft['version_key']) . '</p>';
        echo '<form method="get"><input type="hidden" name="page" value="acorn-healthcheck-content"><select name="question">';
        foreach ($questions as $item) {
            $label = $item['question_key'] . ((int) $item['is_active'] === 1 ? '' : ' (inactive)');
            echo '<option ' . selected($selected, $item['question_key'], false) . ' value="' . esc_attr($item['question_key']) . '">' . esc_html($label) . '</option>';
        }
        echo '</select>';
        submit_button('Edit', 'secondary', '', false);
        echo '</form>';

        if ($question) {
            $sourceTree = json_decode($question['source_json'], true) ?: [];
            $defaultSource = $sourceTree['default'] ?? $sourceTree;
            $jurisdictionSources = $sourceTree['jurisdictions'] ?? [];

            echo '<h2>Question</h2><form method="post">';
            wp_nonce_field('acorn_hc_content');
            echo '<input type="hidden" name="content_action" value="question">';
            echo '<input type="hidden" name="version_id" value="' . (int) $draft['id'] . '">';
            echo '<input type="hidden" name="question_key" value="' . esc_attr($question['question_key']) . '">';

            $this->textarea('question_text', $question['question_text']);
            $this->textarea('help_text', $question['help_text']);
            $this->input('module_key', $question['module_key']);
            $this->input('sort_order', $question['sort_order'], 'number');

            echo '<p><label><input type="checkbox" name="is_active" value="1" ' . checked((int) $question['is_active'], 1, false) . '> <strong>Active question</strong></label></p>';

            $this->textarea(
                'variant_json',
                wp_json_encode(json_decode($question['variant_json'], true) ?: [], JSON_PRETTY_PRINT),
                false
            );
            $this->textarea(
                'applicability_json',
                wp_json_encode(json_decode($question['applicability_json'], true), JSON_PRETTY_PRINT)
            );
            $this->input('source_title', $defaultSource['title'] ?? '');
            $this->input('source_url', $defaultSource['url'] ?? '');
            $this->textarea(
                'source_jurisdictions_json',
                wp_json_encode($jurisdictionSources, JSON_PRETTY_PRINT),
                false
            );
            $this->input('reviewed_by', $question['reviewed_by']);
            $this->input('last_reviewed', $question['last_reviewed'], 'date');
            $this->input('next_review', $question['next_review'], 'date');
            submit_button('Save draft question');
            echo '</form>';

            $recommendations = $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d AND question_key=%s ORDER BY answer_value,jurisdiction',
                (int) $draft['id'],
                $question['question_key']
            ), ARRAY_A);

            echo '<h2>Recommendation variants</h2>';
            foreach ($recommendations as $recommendation) {
                $recSource = json_decode($recommendation['source_json'], true) ?: [];
                echo '<details><summary>' . esc_html($recommendation['answer_value'] . ' / ' . $recommendation['jurisdiction']) . '</summary>';
                echo '<form method="post">';
                wp_nonce_field('acorn_hc_content');
                echo '<input type="hidden" name="content_action" value="recommendation">';
                echo '<input type="hidden" name="version_id" value="' . (int) $draft['id'] . '">';
                echo '<input type="hidden" name="recommendation_id" value="' . (int) $recommendation['id'] . '">';
                $this->input('jurisdiction', $recommendation['jurisdiction']);
                $this->input('finding_status', $recommendation['finding_status']);
                $this->input('heading', $recommendation['heading']);
                foreach (['identified_text', 'next_step_text', 'why_text', 'good_looks_text'] as $field) {
                    $this->textarea($field, $recommendation[$field]);
                }
                $this->input('service_tags', implode(',', json_decode($recommendation['service_tags_json'], true) ?: []));
                $this->input('source_title', $recSource['title'] ?? '');
                $this->input('source_url', $recSource['url'] ?? '');
                $this->input('sort_rank', $recommendation['sort_rank'], 'number');
                submit_button('Save recommendation');
                echo '</form>';

                echo '<form method="post" onsubmit="return confirm(\'Delete this draft recommendation variant?\');">';
                wp_nonce_field('acorn_hc_content');
                echo '<input type="hidden" name="content_action" value="delete_recommendation">';
                echo '<input type="hidden" name="version_id" value="' . (int) $draft['id'] . '">';
                echo '<input type="hidden" name="recommendation_id" value="' . (int) $recommendation['id'] . '">';
                submit_button('Delete draft variant', 'delete', '', false);
                echo '</form></details>';
            }

            echo '<details><summary>Add recommendation variant</summary><form method="post">';
            wp_nonce_field('acorn_hc_content');
            echo '<input type="hidden" name="content_action" value="create_recommendation">';
            echo '<input type="hidden" name="version_id" value="' . (int) $draft['id'] . '">';
            echo '<input type="hidden" name="question_key" value="' . esc_attr($question['question_key']) . '">';
            $this->select('answer_value', ['partly' => 'Partly', 'no' => 'No', 'not_sure' => 'Not sure'], 'partly');
            $this->input('jurisdiction', '*');
            $this->select('finding_status', ['review' => 'Review', 'priority' => 'Priority'], 'review');
            $this->input('heading', '');
            foreach (['identified_text', 'next_step_text', 'why_text', 'good_looks_text'] as $field) {
                $this->textarea($field, '');
            }
            $this->input('service_tags', 'health_safety');
            $this->input('source_title', '');
            $this->input('source_url', '');
            $this->input('sort_rank', $question['sort_order'], 'number');
            submit_button('Add recommendation variant');
            echo '</form></details>';
        }

        echo '<h2>Publish</h2>';
        echo '<p>Publishing is blocked until every technical-review and content-completeness check passes.</p>';
        echo '<form method="post">';
        wp_nonce_field('acorn_hc_content');
        echo '<input type="hidden" name="content_action" value="publish">';
        echo '<input type="hidden" name="version_id" value="' . (int) $draft['id'] . '">';
        submit_button('Publish reviewed draft', 'primary');
        echo '</form></div>';
    }

    private function recommendationValues(): array
    {
        return [
            'jurisdiction' => $_POST['jurisdiction'] ?? '*',
            'finding_status' => $_POST['finding_status'] ?? '',
            'heading' => wp_unslash($_POST['heading'] ?? ''),
            'identified_text' => wp_unslash($_POST['identified_text'] ?? ''),
            'next_step_text' => wp_unslash($_POST['next_step_text'] ?? ''),
            'why_text' => wp_unslash($_POST['why_text'] ?? ''),
            'good_looks_text' => wp_unslash($_POST['good_looks_text'] ?? ''),
            'service_tags' => array_filter(array_map('trim', explode(',', wp_unslash($_POST['service_tags'] ?? '')))),
            'source' => [
                'title' => wp_unslash($_POST['source_title'] ?? ''),
                'url' => wp_unslash($_POST['source_url'] ?? ''),
            ],
            'sort_rank' => $_POST['sort_rank'] ?? 100,
        ];
    }

    private function questionKey($value): string
    {
        return strtoupper(sanitize_key((string) $value));
    }

    private function jsonArray(string $field, ?array $default = null): array
    {
        $raw = trim((string) wp_unslash($_POST[$field] ?? ''));
        if ($raw === '' && $default !== null) return $default;
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) throw new \RuntimeException(ucwords(str_replace('_', ' ', $field)) . ' must be JSON data.');
        return $decoded;
    }

    private function input(string $name, $value, string $type = 'text', bool $required = true): void
    {
        echo '<p><label><strong>' . esc_html(ucwords(str_replace('_', ' ', $name))) . '</strong><br>';
        echo '<input class="regular-text" type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . ($required ? ' required' : '') . '></label></p>';
    }

    private function textarea(string $name, string $value, bool $required = true): void
    {
        echo '<p><label><strong>' . esc_html(ucwords(str_replace('_', ' ', $name))) . '</strong><br>';
        echo '<textarea class="large-text" rows="5" name="' . esc_attr($name) . '"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea></label></p>';
    }

    private function select(string $name, array $options, string $selected): void
    {
        echo '<p><label><strong>' . esc_html(ucwords(str_replace('_', ' ', $name))) . '</strong><br><select name="' . esc_attr($name) . '" required>';
        foreach ($options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label></p>';
    }
}

<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Content;

use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Domain\FindingStatus;
use Acorn\SafetyHealthcheck\Domain\RulesEngine;
use RuntimeException;

final class ContentPublisher
{
    public function updateDraftQuestion(int $versionId, string $questionKey, array $values): void
    {
        global $wpdb;
        $this->assertDraft($versionId);
        $applicability = $values['applicability'] ?? null;
        if (!is_array($applicability)) throw new RuntimeException('Applicability must be structured data.');
        (new RulesEngine())->assertSupportedRule($applicability);
        $source = $values['source'] ?? [];
        if (!is_array($source)) throw new RuntimeException('Source metadata must be structured data.');
        $existing=(new QuestionRepository())->get($versionId,$questionKey);$existingSource=json_decode($existing['source_json']??'[]',true)?:[];$sourcePayload=isset($existingSource['default'])?['default'=>['title'=>sanitize_text_field($source['title']??''),'url'=>esc_url_raw($source['url']??'')],'jurisdictions'=>$existingSource['jurisdictions']??[]]:['title'=>sanitize_text_field($source['title']??''),'url'=>esc_url_raw($source['url']??'')];
        $wpdb->update(Schema::table('questions'), [
            'question_text' => sanitize_textarea_field($values['question_text'] ?? ''),
            'help_text' => sanitize_textarea_field($values['help_text'] ?? ''),
            'module_key' => sanitize_key($values['module_key'] ?? ''),
            'applicability_json' => wp_json_encode($applicability),
            'source_json' => wp_json_encode($sourcePayload),
            'reviewed_by' => sanitize_text_field($values['reviewed_by'] ?? ''),
            'last_reviewed' => sanitize_text_field($values['last_reviewed'] ?? ''),
            'next_review' => sanitize_text_field($values['next_review'] ?? ''),
        ], ['content_version_id'=>$versionId,'question_key'=>$questionKey]);
    }

    public function updateDraftRecommendation(int $versionId, int $recommendationId, array $values): void
    {
        global $wpdb;
        $this->assertDraft($versionId);
        $source = $values['source'] ?? [];
        $wpdb->update(Schema::table('recommendations'), [
            'jurisdiction'=>sanitize_key($values['jurisdiction']??'*') ?: '*',
            'finding_status'=>FindingStatus::assert((string)($values['finding_status']??'')),
            'heading'=>sanitize_text_field($values['heading']??''),
            'identified_text'=>sanitize_textarea_field($values['identified_text']??''),
            'next_step_text'=>sanitize_textarea_field($values['next_step_text']??''),
            'why_text'=>sanitize_textarea_field($values['why_text']??''),
            'good_looks_text'=>sanitize_textarea_field($values['good_looks_text']??''),
            'service_tags_json'=>wp_json_encode(array_values(array_filter(array_map('sanitize_key',(array)($values['service_tags']??[]))))),
            'source_json'=>wp_json_encode(['title'=>sanitize_text_field($source['title']??''),'url'=>esc_url_raw($source['url']??'')]),
            'sort_rank'=>intval($values['sort_rank']??100),
        ], ['id'=>$recommendationId,'content_version_id'=>$versionId]);
    }

    public function updateReviewMetadata(int $versionId, string $questionKey, string $reviewer, string $lastReviewed, string $nextReview): void
    {
        global $wpdb;
        $this->assertDraft($versionId);
        $wpdb->update(Schema::table('questions'), [
            'reviewed_by'=>sanitize_text_field($reviewer),
            'last_reviewed'=>sanitize_text_field($lastReviewed),
            'next_review'=>sanitize_text_field($nextReview),
        ], ['content_version_id'=>$versionId,'question_key'=>$questionKey]);
    }

    public function createDraftFromPublished(int $userId): int
    {
        global $wpdb;
        $live = (new ContentVersionRepository())->getPublished();
        if (!$live) throw new RuntimeException('No published content exists.');
        $parts = explode('.', $live['version_key']);
        $versionKey = $parts[0] . '.' . ((int) ($parts[1] ?? 0) + 1);
        $wpdb->insert(Schema::table('content_versions'), [
            'version_key' => $versionKey, 'status' => 'draft', 'created_by' => $userId,
            'created_at' => current_time('mysql'), 'notes' => 'Draft cloned from ' . $live['version_key'],
        ]);
        $draftId = (int) $wpdb->insert_id;
        foreach (['questions', 'recommendations'] as $suffix) {
            $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Schema::table($suffix) . ' WHERE content_version_id=%d', $live['id']), ARRAY_A);
            foreach ($rows as $row) {
                unset($row['id']); $row['content_version_id'] = $draftId;
                $wpdb->insert(Schema::table($suffix), $row);
            }
        }
        return $draftId;
    }

    public function publish(int $draftId, int $userId): int
    {
        global $wpdb;
        $version = (new ContentVersionRepository())->find($draftId);
        if (($version['status'] ?? '') !== 'draft') throw new RuntimeException('Only a draft can be published.');
        $questions = (new QuestionRepository())->forVersion($draftId);
        foreach ($questions as $question) {
            $source = json_decode($question['source_json'], true);
            if (trim($question['reviewed_by']) === '' || empty($question['last_reviewed']) || $question['last_reviewed'] === '1970-01-01' || empty($question['next_review']) || !is_array($source) || !$this->sourceComplete($source)) {
                throw new RuntimeException('Technical review and source metadata are required before publishing.');
            }
            $applicability = json_decode($question['applicability_json'], true);
            if (!is_array($applicability)) throw new RuntimeException('Applicability must be valid structured JSON.');
            try { (new RulesEngine())->assertSupportedRule($applicability); }
            catch (\InvalidArgumentException $error) { throw new RuntimeException($error->getMessage(), 0, $error); }
            foreach (['partly', 'no', 'not_sure'] as $answer) {
                $rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT * FROM ' . Schema::table('recommendations') . ' WHERE content_version_id=%d AND question_key=%s AND answer_value=%s',
                    $draftId, $question['question_key'], $answer
                ), ARRAY_A);
                if ($rows === []) throw new RuntimeException("{$question['question_key']} is missing the {$answer} recommendation.");
                foreach ($rows as $recommendation) {
                    FindingStatus::assert($recommendation['finding_status']);
                    foreach (['heading', 'identified_text', 'next_step_text', 'why_text', 'good_looks_text'] as $field) {
                        if (trim($recommendation[$field]) === '') throw new RuntimeException("{$question['question_key']} has incomplete recommendation content.");
                    }
                    $recommendationSource = json_decode($recommendation['source_json'], true);
                    if (!is_array($recommendationSource) || empty($recommendationSource['title']) || empty($recommendationSource['url'])) {
                        throw new RuntimeException("{$question['question_key']} has incomplete recommendation source metadata.");
                    }
                }
            }
        }
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->update(Schema::table('content_versions'), ['status' => 'retired'], ['status' => 'published']);
            $wpdb->update(Schema::table('content_versions'), ['status' => 'published', 'published_at' => current_time('mysql')], ['id' => $draftId]);
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK'); throw $error;
        }
        return $draftId;
    }

    private function sourceComplete(array $source): bool
    {
        $default = $source['default'] ?? $source;
        if (empty($default['title']) || empty($default['url'])) return false;
        foreach (($source['jurisdictions'] ?? []) as $variant) if (empty($variant['title']) || empty($variant['url'])) return false;
        return true;
    }

    private function assertDraft(int $versionId): void
    {
        $version = (new ContentVersionRepository())->find($versionId);
        if (($version['status'] ?? '') !== 'draft') throw new RuntimeException('Published content is immutable; edit a draft version.');
    }
}

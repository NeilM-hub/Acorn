<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Assessment;

use Acorn\SafetyHealthcheck\Content\{ContentVersionRepository, QuestionRepository, RecommendationRepository};
use Acorn\SafetyHealthcheck\Domain\{ApplicabilityContext, RulesEngine};
use RuntimeException;

final class SnapshotBuilder
{
    public function build(int $assessmentId): array
    {
        $assessment = (new AssessmentRepository())->find($assessmentId);
        if (!$assessment) throw new RuntimeException('Assessment not found.');

        $answers = (new AnswerRepository())->all($assessmentId);
        $profile = json_decode($assessment['profile_json'], true, 512, JSON_THROW_ON_ERROR);
        $questions = (new RulesEngine())->applicableQuestions(ApplicabilityContext::fromProfile($profile), (new QuestionRepository())->forVersion((int) $assessment['content_version_id']));
        $questionsByKey = array_column($questions, null, 'question_key');
        $version = (new ContentVersionRepository())->find((int) $assessment['content_version_id']);
        $items = [];

        foreach ($answers as $answer) {
            $question = $questionsByKey[$answer['question_key']] ?? null;
            if (!$question) throw new RuntimeException('Snapshot question is unavailable.');
            $rules = json_decode($question['answer_rules_json'], true, 512, JSON_THROW_ON_ERROR);
            $subject = (string) ($rules['subject'] ?? $this->humanSubject($question['question_text']));
            $recommendation = null;
            if ($answer['answer_value'] !== 'yes') {
                $recommendation = (new RecommendationRepository())->resolve(
                    (int) $assessment['content_version_id'],
                    $answer['question_key'],
                    $answer['answer_value'],
                    (string) $assessment['jurisdiction']
                );
                if (!$recommendation) throw new RuntimeException('Snapshot recommendation is unavailable.');
            }
            $items[] = [
                'question_key' => $answer['question_key'],
                'question_text' => $question['question_text'],
                'help_text' => $question['help_text'],
                'module_key' => $question['module_key'],
                'sort_rank' => $recommendation ? (int) $recommendation['sort_rank'] : (int) $question['sort_order'],
                'answer' => $answer['answer_value'],
                'finding_status' => $answer['finding_status'],
                'positive_text' => ucfirst($subject) . ' appears to be addressed based on your answer.',
                'recommendation' => $recommendation ? [
                    'heading' => $recommendation['heading'],
                    'identified_text' => $recommendation['identified_text'],
                    'next_step_text' => $recommendation['next_step_text'],
                    'why_text' => $recommendation['why_text'],
                    'good_looks_text' => $recommendation['good_looks_text'],
                    'source' => json_decode($recommendation['source_json'], true, 512, JSON_THROW_ON_ERROR),
                ] : null,
                'source' => $this->questionSource($question['source_json'], (string) $assessment['jurisdiction']),
            ];
        }

        return [
            'content_version' => $version['version_key'],
            'profile' => $profile,
            'summary' => [
                'overall_status' => $assessment['overall_status'],
                'priority_count' => (int) $assessment['priority_count'],
                'review_count' => (int) $assessment['review_count'],
                'addressed_count' => (int) $assessment['addressed_count'],
            ],
            'displayed_questions' => $items,
            'findings' => $items,
        ];
    }

    private function humanSubject(string $question): string
    {
        return rtrim(preg_replace('/^(Has|Have|Are|Is|Do|Does)\s+(you\s+|the organisation\s+)?/i', '', $question) ?: $question, '?');
    }

    private function questionSource(string $json, string $jurisdiction): array
    {
        $sources = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (isset($sources['jurisdictions'][$jurisdiction])) return $sources['jurisdictions'][$jurisdiction];
        if (isset($sources['default'])) return $sources['default'];
        return $sources;
    }
}

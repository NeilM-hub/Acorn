<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Assessment;

use Acorn\SafetyHealthcheck\Content\{ContentVersionRepository, QuestionRepository, RecommendationRepository};
use Acorn\SafetyHealthcheck\Database\Schema;
use Acorn\SafetyHealthcheck\Domain\{ApplicabilityContext, AssessmentState, AnswerValue, RulesEngine, ResultsEngine, OpportunityTagger};
use RuntimeException;

final class AssessmentService
{
    private AssessmentRepository $repo;
    private AnswerRepository $answers;

    public function __construct()
    {
        $this->repo = new AssessmentRepository();
        $this->answers = new AnswerRepository();
    }

    public function start(): array
    {
        global $wpdb;

        $version = (new ContentVersionRepository())->getPublished();
        $token = AssessmentRepository::token();
        $now = current_time('mysql');

        $wpdb->insert(Schema::table('assessments'), [
            'assessment_token_hash' => hash('sha256', $token),
            'status' => 'in_progress',
            'content_version_id' => $version['id'],
            'profile_json' => '{}',
            'service_tags_json' => '[]',
            'started_at' => $now,
            'last_activity_at' => $now,
        ]);

        return ['token' => $token, 'state' => $this->resume($token)];
    }

    public function resume(string $token): AssessmentState
    {
        $assessment = $this->require($token);
        $profile = json_decode($assessment['profile_json'], true) ?: [];
        $questions = [];

        if ($profile) {
            $questions = (new RulesEngine())->applicableQuestions(
                ApplicabilityContext::fromProfile($profile),
                (new QuestionRepository())->forVersion((int) $assessment['content_version_id'])
            );
        }

        $answers = [];
        foreach ($this->answers->all((int) $assessment['id']) as $answer) {
            $answers[$answer['question_key']] = $answer['answer_value'];
        }

        $headline = in_array($assessment['status'], ['assessed', 'completed'], true)
            ? $this->headline($assessment)
            : null;

        return new AssessmentState($assessment['status'], $profile, $questions, $answers, $headline);
    }

    public function updateProfile(string $token, array $changes): AssessmentState
    {
        global $wpdb;

        $assessment = $this->requireMutable($token);
        $current = json_decode($assessment['profile_json'], true) ?: [];
        $profile = array_merge($current, $changes);
        $context = ApplicabilityContext::fromProfile($profile);

        if (!isset($profile['jurisdiction'], $profile['employee_band'])) {
            throw new RuntimeException('Jurisdiction and employee count are required before starting the questions.');
        }

        $questions = (new RulesEngine())->applicableQuestions(
            $context,
            (new QuestionRepository())->forVersion((int) $assessment['content_version_id'])
        );

        $wpdb->update(Schema::table('assessments'), [
            'profile_json' => wp_json_encode($profile),
            'jurisdiction' => $profile['jurisdiction'],
            'employee_band' => $profile['employee_band'],
            'sector' => $profile['sector'] ?? null,
            'last_activity_at' => current_time('mysql'),
        ], ['id' => $assessment['id']]);

        $this->answers->deleteExcluded((int) $assessment['id'], array_column($questions, 'question_key'));

        return $this->resume($token);
    }

    public function saveAnswer(string $token, string $key, string $answer): AssessmentState
    {
        global $wpdb;

        $assessment = $this->requireMutable($token);
        $state = $this->resume($token);
        $question = null;

        foreach ($state->questions as $candidate) {
            if ($candidate['question_key'] === $key) {
                $question = $candidate;
                break;
            }
        }

        if (!$question) {
            throw new RuntimeException('Question is not applicable.');
        }

        $answer = AnswerValue::assert($answer);
        $recommendation = $answer === 'yes'
            ? null
            : (new RecommendationRepository())->resolve(
                (int) $assessment['content_version_id'],
                $key,
                $answer,
                (string) $assessment['jurisdiction']
            );

        $status = $answer === 'yes' ? 'addressed' : $recommendation['finding_status'];

        $wpdb->replace(Schema::table('answers'), [
            'assessment_id' => $assessment['id'],
            'question_key' => $key,
            'question_row_id' => $question['id'],
            'answer_value' => $answer,
            'finding_status' => $status,
            'recommendation_row_id' => $recommendation['id'] ?? null,
            'answered_at' => current_time('mysql'),
        ]);

        return $this->resume($token);
    }

    public function assess(string $token): array
    {
        global $wpdb;

        $assessment = $this->requireMutable($token);
        $state = $this->resume($token);
        $answers = $state->answers;

        if (count($answers) !== count($state->questions)) {
            throw new RuntimeException('Answer all applicable questions.');
        }

        $result = (new ResultsEngine())->evaluate(
            $state->questions,
            $answers,
            (string) $assessment['jurisdiction'],
            (int) $assessment['content_version_id']
        );

        $tags = (new OpportunityTagger())->tags($result['findings']);

        $wpdb->update(Schema::table('assessments'), [
            'status' => 'assessed',
            'priority_count' => $result['priority_count'],
            'review_count' => $result['review_count'],
            'addressed_count' => $result['addressed_count'],
            'overall_status' => $result['overall_status'],
            'service_tags_json' => wp_json_encode($tags),
            'assessed_at' => current_time('mysql'),
        ], ['id' => $assessment['id']]);

        return $this->resume($token)->jsonSerialize();
    }

    private function headline(array $assessment): array
    {
        global $wpdb;

        $answers = Schema::table('answers');
        $recommendations = Schema::table('recommendations');
        $top = $wpdb->get_row($wpdb->prepare(
            "SELECT a.finding_status,r.heading,r.identified_text
             FROM $answers a
             LEFT JOIN $recommendations r ON r.id=a.recommendation_row_id
             WHERE a.assessment_id=%d AND a.finding_status IN ('priority','review')
             ORDER BY CASE a.finding_status WHEN 'priority' THEN 0 ELSE 1 END,
                      COALESCE(r.sort_rank,9999) ASC,
                      a.id ASC
             LIMIT 1",
            $assessment['id']
        ), ARRAY_A);

        return [
            'overall_status' => $assessment['overall_status'],
            'priority_count' => (int) $assessment['priority_count'],
            'review_count' => (int) $assessment['review_count'],
            'addressed_count' => (int) $assessment['addressed_count'],
            'top_action' => $top ? [
                'status' => $top['finding_status'],
                'heading' => (string) $top['heading'],
                'summary' => (string) $top['identified_text'],
            ] : null,
        ];
    }

    private function require(string $token): array
    {
        $assessment = $this->repo->findByToken($token);
        if (!$assessment) {
            throw new RuntimeException('Assessment not found.');
        }
        return $assessment;
    }

    private function requireMutable(string $token): array
    {
        $assessment = $this->require($token);
        if ($assessment['status'] !== 'in_progress') {
            throw new RuntimeException('Assessment is locked.');
        }
        return $assessment;
    }
}

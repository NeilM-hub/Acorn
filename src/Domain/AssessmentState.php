<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Domain;

/** Public, deliberately allow-listed wizard state. */
final readonly class AssessmentState implements \JsonSerializable
{
    public function __construct(
        public string $status,
        public array $profile,
        public array $questions,
        public array $answers,
        public ?array $headline = null,
    ) {}

    public function jsonSerialize(): array
    {
        $total = count($this->questions);
        $answered = count($this->answers);
        return [
            'status' => $this->status,
            'profile' => $this->profile,
            'questions' => array_map(static fn(array $q): array => [
                'key' => $q['question_key'], 'module' => $q['module_key'],
                'text' => $q['question_text'], 'help' => $q['help_text'],
            ], $this->questions),
            'answers' => $this->answers,
            'progress' => ['answered' => $answered, 'total' => $total,
                'percent' => $total ? (int) floor($answered / $total * 100) : 0],
            'headline' => $this->headline,
        ];
    }
}

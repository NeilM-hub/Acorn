<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Domain;

use InvalidArgumentException;

final class RulesEngine
{
    public function applicableQuestions(ApplicabilityContext $context, array $questions): array
    {
        return array_values(array_map(function (array $question) use ($context): array {
            $question['question_text'] = $this->resolveQuestionText($context, $question);
            return $question;
        }, array_filter($questions, fn(array $question): bool => $this->isApplicable($context, $question))));
    }

    public function isApplicable(ApplicabilityContext $context, array $question): bool
    {
        $rule = json_decode((string) ($question['applicability_json'] ?? ''), true);
        if (!is_array($rule)) {
            throw new InvalidArgumentException('Question applicability must be structured JSON.');
        }
        $this->assertSupportedRule($rule);

        return $this->evaluate($rule, $context);
    }

    public function assertSupportedRule(array $rule): void
    {
        if (array_key_exists('all', $rule) || array_key_exists('any', $rule)) {
            $operator = array_key_exists('all', $rule) ? 'all' : 'any';
            if (count($rule) !== 1 || !is_array($rule[$operator])) {
                throw new InvalidArgumentException("Invalid $operator applicability rule.");
            }
            foreach ($rule[$operator] as $child) {
                if (!is_array($child)) throw new InvalidArgumentException('Applicability children must be rules.');
                $this->assertSupportedRule($child);
            }
            return;
        }
        if (array_key_exists('risk_flag', $rule)) {
            if (array_keys($rule) !== ['risk_flag'] || !is_string($rule['risk_flag'])) throw new InvalidArgumentException('Invalid risk_flag rule.');
            return;
        }
        foreach (['has_employees', 'non_home_workplace'] as $booleanOperator) {
            if (array_key_exists($booleanOperator, $rule)) {
                if (array_keys($rule) !== [$booleanOperator] || !is_bool($rule[$booleanOperator])) throw new InvalidArgumentException("Invalid $booleanOperator rule.");
                return;
            }
        }
        if (isset($rule['field'])) {
            $comparison = array_key_exists('in', $rule) ? 'in' : (array_key_exists('not_in', $rule) ? 'not_in' : null);
            if ($comparison === null || count($rule) !== 2 || !is_string($rule['field']) || !is_array($rule[$comparison])) {
                throw new InvalidArgumentException('Invalid field applicability rule.');
            }
            return;
        }
        throw new InvalidArgumentException('Unsupported applicability operator.');
    }

    public function resolveQuestionText(ApplicabilityContext $context, array $question): string
    {
        if ($question['question_key'] !== 'M02_POLICY') return $question['question_text'];
        $variants = json_decode($question['variant_json'] ?? '[]', true);
        $variant = in_array($context->profile['employee_band'], ['5_9', '10_49', '50_249', '250_plus'], true) ? '5_plus' : 'under_5';
        return $variants[$variant] ?? $question['question_text'];
    }

    private function evaluate(array $rule, ApplicabilityContext $context): bool
    {
        if (isset($rule['all'])) {
            foreach ($rule['all'] as $child) if (!$this->evaluate($child, $context)) return false;
            return true;
        }
        if (isset($rule['any'])) {
            foreach ($rule['any'] as $child) if ($this->evaluate($child, $context)) return true;
            return false;
        }
        if (isset($rule['risk_flag'])) return $context->hasRiskFlag($rule['risk_flag']);
        if (array_key_exists('has_employees', $rule)) return $context->hasEmployees() === $rule['has_employees'];
        if (array_key_exists('non_home_workplace', $rule)) return $context->nonHomeWorkplace() === $rule['non_home_workplace'];

        $value = $context->profile[$rule['field']] ?? null;
        $allowed = $rule['in'] ?? $rule['not_in'];
        $matches = is_array($value) ? array_intersect($value, $allowed) !== [] : in_array($value, $allowed, true);
        return array_key_exists('in', $rule) ? $matches : !$matches;
    }
}

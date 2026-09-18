<?php

declare(strict_types=1);
use Acorn\SafetyHealthcheck\Domain\AssessmentState;
use PHPUnit\Framework\TestCase;

final class AssessmentStateTest extends TestCase
{
    public function test_public_shape_excludes_all_internal_database_fields(): void
    {
        $state = new AssessmentState('in_progress', ['jurisdiction' => 'england'], [[
            'id' => 44, 'question_key' => 'M01_COMPETENT_PERSON', 'module_key' => 'management',
            'question_text' => 'Question?', 'help_text' => 'Help', 'source_json' => '{"secret":true}',
        ]], ['M01_COMPETENT_PERSON' => 'yes']);
        $json = json_encode($state, JSON_THROW_ON_ERROR);
        self::assertSame(['status','profile','questions','answers','progress','headline'], array_keys($state->jsonSerialize()));
        foreach (['assessment_token_hash','report_token_hash','snapshot_json','contact_id','service_tags_json','email_last_error','pdf_last_error','source_json','"id"'] as $internal) {
            self::assertStringNotContainsString($internal, $json);
        }
    }
}

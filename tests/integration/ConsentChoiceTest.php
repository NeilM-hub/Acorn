<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\CompletionService;

final class ConsentChoiceTest extends IntegrationTestCase
{
    public function test_completion_requires_explicit_audit_contact_choice(): void
    {
        [, $token] = $this->assessed();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Choose whether you would like Acorn Safety Services to contact you.');

        (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Analytical Ltd',
            'email' => 'ada@example.test',
            'marketing_consent' => false,
            'website' => '',
        ]);
    }
}

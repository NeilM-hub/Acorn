<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\CompletionService;

final class MailerTest extends IntegrationTestCase
{
    public function test_customer_and_internal_mail_are_attempted_without_marketing_consent(): void
    {
        $messages = [];
        add_filter('pre_wp_mail', static function ($return, $atts) use (&$messages) {
            $messages[] = $atts;
            return true;
        }, 10, 2);

        [$service, $token] = $this->assessed();
        $result = (new CompletionService())->complete($token, [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Analytical',
            'email' => 'mail@example.test',
            'audit_requested' => false,
            'marketing_consent' => false,
        ]);

        self::assertCount(2, $messages);
        self::assertStringContainsString($result['report_token'], $messages[0]['message']);
        self::assertStringContainsString('Your Healthcheck at a glance', $messages[0]['message']);
        self::assertStringContainsString('View your secure report', $messages[0]['message']);
        self::assertStringContainsString('Priority actions', $messages[0]['message']);
        self::assertSame('mail@example.test', $messages[0]['to']);
    }
}

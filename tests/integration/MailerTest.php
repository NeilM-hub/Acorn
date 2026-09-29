<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentService, CompletionService};

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


    public function test_internal_completion_notification_goes_to_acorn_with_contact_results_and_pdf(): void
    {
        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], $this->profile());

        foreach ($state->questions as $index => $question) {
            $service->saveAnswer($start['token'], $question['question_key'], $index === 0 ? 'no' : 'yes');
        }
        $service->assess($start['token']);

        $messages = [];
        add_filter('pre_wp_mail', static function ($return, array $atts) use (&$messages) {
            $attachment = $atts['attachments'][0] ?? '';
            $atts['attachment_exists'] = is_string($attachment) && is_file($attachment);
            $atts['attachment_basename'] = is_string($attachment) ? basename($attachment) : '';
            $messages[] = $atts;
            return true;
        }, 10, 2);

        (new CompletionService())->complete($start['token'], [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company' => 'Follow Up Ltd',
            'email' => 'ada@followup.example',
            'telephone' => '01604 123456',
            'postcode' => 'NN3 6QB',
            'audit_requested' => true,
            'marketing_consent' => true,
        ]);

        remove_all_filters('pre_wp_mail');

        self::assertCount(2, $messages);
        $internal = $messages[1];

        self::assertSame('info@acornhealthandsafety.co.uk', $internal['to']);
        self::assertStringContainsString('New Healthcheck completed | Follow Up Ltd | 1 Priority Action', $internal['subject']);
        self::assertStringContainsString('Audit Requested', $internal['subject']);

        self::assertStringContainsString('New Healthcheck lead', $internal['message']);
        self::assertStringContainsString('Ada Lovelace', $internal['message']);
        self::assertStringContainsString('ada@followup.example', $internal['message']);
        self::assertStringContainsString('01604 123456', $internal['message']);
        self::assertStringContainsString('NN3 6QB', $internal['message']);
        self::assertStringContainsString('Marketing consent', $internal['message']);
        self::assertStringContainsString('1', $internal['message']);
        self::assertStringContainsString('Priority action', $internal['message']);
        self::assertStringContainsString('Recommended follow-up', $internal['message']);
        self::assertStringContainsString('View assessment in WordPress', $internal['message']);

        self::assertTrue($internal['attachment_exists']);
        self::assertSame('Acorn-Safety-Healthcheck-Follow-Up-Ltd.pdf', $internal['attachment_basename']);
        self::assertSame($messages[0]['attachment_basename'], $internal['attachment_basename']);
    }

}

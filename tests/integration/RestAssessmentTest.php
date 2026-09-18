<?php

declare(strict_types=1);

final class RestAssessmentTest extends IntegrationTestCase
{
    private WP_REST_Server $server;

    public function set_up(): void
    {
        parent::set_up();

        global $wp_rest_server;
        $this->server = new WP_REST_Server();
        $wp_rest_server = $this->server;
        do_action('rest_api_init', $this->server);
    }

    public function tear_down(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;

        parent::tear_down();
    }

    public function test_start_and_resume_never_expose_internal_fields(): void
    {
        $response = $this->server->dispatch(
            new WP_REST_Request('POST', '/acorn-healthcheck/v1/assessments')
        );

        self::assertSame(201, $response->get_status());
        $data = $response->get_data();
        $json = wp_json_encode($data);
        self::assertNotEmpty($data['token']);

        foreach (['assessment_token_hash', 'report_token_hash', 'snapshot_json', 'contact_id', 'service_tags_json', 'email_last_error', 'pdf_last_error'] as $field) {
            self::assertStringNotContainsString($field, $json);
        }

        $resume = $this->server->dispatch(
            new WP_REST_Request('GET', '/acorn-healthcheck/v1/assessments/' . $data['token'])
        );
        self::assertSame(200, $resume->get_status());
        self::assertStringNotContainsString('assessment_token_hash', wp_json_encode($resume->get_data()));
    }

    public function test_invalid_answer_is_rejected(): void
    {
        [, $token] = $this->assessed();
        $request = new WP_REST_Request(
            'PUT',
            "/acorn-healthcheck/v1/assessments/$token/answers/M01_COMPETENT_PERSON"
        );
        $request->set_body_params(['answer' => 'compliant']);

        $response = $this->server->dispatch($request);

        self::assertContains($response->get_status(), [400, 409]);
    }

    public function test_completion_response_hides_internal_id_and_secure_data_route_returns_report(): void
    {
        add_filter('pre_wp_mail', '__return_true');
        [, $token] = $this->assessed();
        $request = new WP_REST_Request('POST', "/acorn-healthcheck/v1/assessments/$token/complete");
        $request->set_header('content-type', 'application/json');
        $request->set_body(wp_json_encode([
            'first_name'=>'Ada','last_name'=>'Lovelace','company'=>'REST Ltd',
            'email'=>'rest@example.test','audit_requested'=>false,'marketing_consent'=>false,
        ]));
        $response = $this->server->dispatch($request);
        self::assertSame(200, $response->get_status());
        $public = $response->get_data();
        self::assertSame(['report_token','report_url'], array_keys($public));
        self::assertArrayNotHasKey('assessment_id', $public);

        $data = $this->server->dispatch(new WP_REST_Request('GET', '/acorn-healthcheck/v1/reports/' . $public['report_token'] . '/data'));
        self::assertSame(200, $data->get_status());
        self::assertArrayHasKey('summary', $data->get_data());
        self::assertArrayNotHasKey('service_tags', $data->get_data());
    }
}

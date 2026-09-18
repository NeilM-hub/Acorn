<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Rest;

use Acorn\SafetyHealthcheck\Assessment\CompletionService;
use Acorn\SafetyHealthcheck\Notifications\ReportResender;
use Acorn\SafetyHealthcheck\Reports\ReportAccess;
use Acorn\SafetyHealthcheck\Security\RateLimiter;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;

final class CompletionController
{
    public function register(): void
    {
        register_rest_route('acorn-healthcheck/v1', '/assessments/(?P<token>[A-Za-z0-9_-]+)/complete', [
            'methods' => 'POST',
            'callback' => [$this, 'complete'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('acorn-healthcheck/v1', '/reports/(?P<token>[A-Za-z0-9_-]+)/resend', [
            'methods' => 'POST',
            'callback' => [$this, 'resend'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('acorn-healthcheck/v1', '/reports/(?P<token>[A-Za-z0-9_-]+)/data', [
            'methods' => 'GET',
            'callback' => [$this, 'data'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function complete(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $result = (new CompletionService())->complete($request['token'], $request->get_json_params());
            return new WP_REST_Response([
                'report_token' => $result['report_token'],
                'report_url' => $result['report_url'],
            ], 200);
        } catch (Throwable $error) {
            return new WP_REST_Response([
                'code' => 'completion_error',
                'message' => $error->getMessage(),
            ], 400);
        }
    }

    public function data(WP_REST_Request $request): WP_REST_Response
    {
        $assessmentId = (new ReportAccess())->findAssessmentId($request['token']);
        if (!$assessmentId) {
            return new WP_REST_Response([
                'code' => 'not_found',
                'message' => 'Report not found.',
            ], 404);
        }

        return new WP_REST_Response(
            (new \Acorn\SafetyHealthcheck\Reports\ReportDataBuilder())->build($assessmentId),
            200,
            [
                'Cache-Control' => 'private, no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }

    public function resend(WP_REST_Request $request): WP_REST_Response
    {
        if (!(new RateLimiter())->allow('resend:' . hash('sha256', $request['token']), 3, HOUR_IN_SECONDS)) {
            return new WP_REST_Response([
                'code' => 'rate_limited',
                'message' => 'The resend limit has been reached. Please try again later.',
            ], 429);
        }

        $assessmentId = (new ReportAccess())->findAssessmentId($request['token']);
        if (!$assessmentId) {
            return new WP_REST_Response([
                'code' => 'not_found',
                'message' => 'Report not found.',
            ], 404);
        }

        $url = home_url('/healthcheck/report/' . rawurlencode($request['token']) . '/');

        try {
            if (!(new ReportResender())->send($assessmentId, $url)) {
                throw new \RuntimeException('Email could not be sent.');
            }

            return new WP_REST_Response([
                'message' => 'Your report email has been resent.',
            ]);
        } catch (Throwable $error) {
            return new WP_REST_Response([
                'code' => 'mail_failed',
                'message' => 'Your report remains available, but email could not be sent.',
            ], 503);
        }
    }
}

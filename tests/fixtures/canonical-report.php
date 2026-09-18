<?php

declare(strict_types=1);

return [
    'meta' => [
        'company' => 'Test Company Ltd',
        'assessment_date' => '2026-09-18',
        'jurisdiction' => 'england',
        'content_version' => '1.0',
        'areas_assessed' => 0,
    ],
    'branding' => ['logo_url' => '', 'logo_data' => '', 'phone' => '01604 930380', 'website' => 'https://example.test/', 'pdf_footer' => 'Acorn Safety Services | Health & Safety Healthcheck'],
    'summary' => [
        'overall_status' => 'no_obvious_gaps',
        'priority_count' => 0,
        'review_count' => 0,
        'addressed_count' => 0,
    ],
    'pillars' => [],
    'sections' => [
        'addressed' => ['label' => 'What appears to be working'],
        'review' => ['label' => 'Things worth checking'],
        'priority' => ['label' => 'Priority actions'],
    ],
    'addressed' => [],
    'review' => [],
    'priority' => [],
    'action_summary' => [],
    'disclaimer' => 'This is an indicative self-assessment, not a formal audit or confirmation of compliance.',
    'privacy_policy_url' => 'https://example.test/privacy/',
    'support' => [
        'heading' => 'Need help with any of the actions identified?',
        'cta_label' => 'Request a free Health & Safety Compliance Audit',
        'cta_url' => 'https://example.test/health-and-safety-compliance-audit/',
    ],
];

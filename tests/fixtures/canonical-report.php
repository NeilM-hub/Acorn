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
    'support' => [
        'heading' => 'Need help with any of the actions identified?',
        'cta_label' => 'Request a free Health & Safety Compliance Audit',
        'cta_url' => 'https://example.test/health-and-safety-compliance-audit/',
    ],
];

# Acorn Safety Healthcheck Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an installable WordPress plugin that delivers Acorn Safety Services' client-first Health & Safety Healthcheck, including conditional assessment logic, deterministic recommendations, secure lead capture, an on-screen report, a branded PDF, email delivery, admin management, versioning, analytics, and tests.

**Architecture:** A bespoke WordPress plugin owns the assessment lifecycle. A lightweight vanilla-JavaScript wizard talks to versioned WordPress REST endpoints; PHP domain services remain authoritative for applicability, statuses, recommendations, report data, and security. Dedicated database tables store assessments, answers, contacts, versioned content, and immutable completion snapshots; the browser report, PDF, email, and admin detail view all consume the same canonical report-data builder.

**Tech Stack:** WordPress plugin; PHP 8.1+ with strict types and PSR-4 autoloading; WordPress REST API; `$wpdb`/`dbDelta`; vanilla ES modules and CSS; Composer; Dompdf 3.x for server-side PDF; PHPUnit 10 for domain tests; WordPress core test suite for integration tests; `@wordpress/env` for local WordPress; Playwright + axe-core for browser/accessibility tests.

**Spec:** `docs/superpowers/specs/2026-09-17-acorn-safety-healthcheck-design.md` (source copy for this planning workspace: `/mnt/data/acorn-healthcheck-spec/2026-09-17-acorn-safety-healthcheck-design.md`)

## Global Constraints

- Client value first: every question must be useful to the client, not merely useful for selling an Acorn service.
- No customer-facing compliance percentage, score, pass/fail verdict, or certification language.
- Customer-facing statuses are: `Priority action`, `Review recommended`, `No obvious gap identified`, and where relevant `Not assessed`, `Not applicable`, or `Responsibility unclear`.
- No AI is used for applicability, legal/compliance classifications, recommendations, or report content in V1.
- No service-selling messages appear during the assessment.
- Typical completion target is 4–6 minutes; typical users see 18–24 scored questions; more complex users should remain around 25–30.
- Fire, Legionella, Asbestos, and specialist-risk modules are conditional and proportionate.
- Positive findings are explicitly included in the report.
- The tool states that it is an indicative self-assessment based on user-supplied information and is not a formal audit, legal advice, or confirmation of compliance.
- Completed assessments preserve the exact content version, question wording, answers, statuses, recommendations, and source metadata used at completion.
- The browser report, PDF, email summary, and admin view must derive from the same canonical report-data object.
- The server recalculates applicability and results; client-supplied counts or statuses are never trusted.
- Full recommendations unlock only after the user has already seen a meaningful headline result and then supplies contact details.
- Audit request and marketing consent remain separate.
- The plugin works independently of Gravity Forms.
- Front-end assets load only on pages containing `[acorn_safety_healthcheck]` or a secure report route.
- Incomplete anonymous assessments expire after 7 days by default; PDF files are temporary/reproducible; completed-retention duration is configurable.
- The launch page is `/health-and-safety-healthcheck/`; the existing Compliance Audit page remains separate.

---

## File and Responsibility Map

Create the plugin as a standalone repository/directory named `acorn-safety-healthcheck`:

```text
acorn-safety-healthcheck/
├── acorn-safety-healthcheck.php              # Plugin bootstrap only
├── composer.json                              # PHP deps/autoload/test scripts
├── package.json                               # wp-env + Playwright tooling
├── playwright.config.ts                       # Browser test base URL/global setup
├── phpunit.xml.dist                           # Domain/unit tests
├── README.md                                  # Install/build/admin notes
├── src/
│   ├── Plugin.php                             # Service wiring, hooks, shortcode/asset registration
│   ├── Activation.php                         # Schema install + initial content seed
│   ├── Database/
│   │   ├── Schema.php                         # dbDelta table definitions and schema version
│   │   └── Migrations.php                     # Future schema upgrades
│   ├── Domain/
│   │   ├── AssessmentStatus.php               # Lifecycle constants/value validation
│   │   ├── AnswerValue.php                    # yes/partly/no/not_sure allow-list
│   │   ├── FindingStatus.php                  # addressed/review/priority/not_assessed
│   │   ├── OverallStatus.php                  # overall copy/status resolution
│   │   ├── ApplicabilityContext.php           # Typed profile/context object
│   │   ├── AssessmentState.php                # Current question/progress state DTO
│   │   ├── RulesEngine.php                    # Applicability + variant selection
│   │   ├── ResultsEngine.php                  # Finding/section/overall result computation
│   │   └── OpportunityTagger.php              # Internal-only service tags
│   ├── Content/
│   │   ├── ContentVersionRepository.php       # Draft/published content versions
│   │   ├── QuestionRepository.php             # Version-scoped questions
│   │   ├── RecommendationRepository.php       # Version-scoped recommendation variants
│   │   ├── ContentPublisher.php               # Clone/edit/publish version workflow
│   │   └── SeedContent.php                    # Approved V1 seed data
│   ├── Assessment/
│   │   ├── AssessmentRepository.php           # Assessment persistence + secure token lookup
│   │   ├── AnswerRepository.php               # Answer persistence/invalidation
│   │   ├── ContactRepository.php              # Contact persistence
│   │   ├── AssessmentService.php              # Start/profile/answer/assess lifecycle
│   │   ├── CompletionService.php              # Contact capture + immutable snapshot
│   │   └── SnapshotBuilder.php                # Reproducible completion snapshot
│   ├── Reports/
│   │   ├── ReportDataBuilder.php              # Canonical report object
│   │   ├── ReportAccess.php                   # Report token issue/verify
│   │   ├── PdfGenerator.php                   # Dompdf HTML->PDF
│   │   └── ReportController.php               # Secure browser/PDF routes
│   ├── Notifications/
│   │   ├── CustomerMailer.php                 # Customer email + attachment
│   │   └── InternalMailer.php                 # Acorn lead notification
│   ├── Rest/
│   │   ├── AssessmentController.php           # Start/resume/profile/answer/assess endpoints
│   │   └── CompletionController.php           # Contact/complete/resend endpoints
│   ├── Admin/
│   │   ├── Menu.php                           # Admin menus/capabilities
│   │   ├── DashboardPage.php                  # Funnel/common findings/opportunities
│   │   ├── AssessmentsPage.php                # Filters/list/detail actions
│   │   ├── ContentPage.php                    # Question/recommendation editor
│   │   ├── ReviewPage.php                     # Content-review dashboard
│   │   └── SettingsPage.php                   # Branding/email/retention/CTA/privacy settings
│   ├── Privacy/
│   │   ├── Retention.php                      # Scheduled cleanup
│   │   └── ExportEraser.php                   # WordPress personal-data hooks
│   └── Security/
│       ├── RateLimiter.php                    # Public endpoint throttling
│       └── Honeypot.php                       # Contact-form abuse check
├── config/
│   ├── profile-fields.php                     # Stable profile fields/options
│   └── settings-defaults.php                  # Defaults from approved spec
├── templates/
│   ├── shortcode-shell.php                    # App mount/landing shell
│   ├── report-web.php                         # Secure browser report
│   ├── report-pdf.php                         # PDF template
│   ├── email-customer.php                     # HTML email
│   └── email-internal.php                     # Internal notification
├── assets/
│   ├── js/
│   │   ├── healthcheck.js                     # Wizard orchestration
│   │   ├── api.js                             # REST transport only
│   │   ├── state.js                           # Browser state/local recovery
│   │   └── ui.js                              # Rendering/accessibility helpers
│   └── css/
│       ├── healthcheck.css                    # Public wizard/report styles
│       └── admin.css                          # Admin-only styles
├── tests/
│   ├── bootstrap.php
│   ├── unit/
│   │   ├── RulesEngineTest.php
│   │   ├── ResultsEngineTest.php
│   │   ├── OpportunityTaggerTest.php
│   │   └── ReportDataBuilderTest.php
│   ├── integration/
│   │   ├── SchemaTest.php
│   │   ├── AssessmentLifecycleTest.php
│   │   ├── RestAssessmentTest.php
│   │   ├── CompletionTest.php
│   │   ├── ReportAccessTest.php
│   │   ├── MailerTest.php
│   │   ├── AdminCapabilityTest.php
│   │   └── RetentionTest.php
│   └── e2e/
│       ├── global-setup.ts                    # Create/refresh test page in wp-env
│       ├── healthcheck.spec.ts
│       ├── backtracking.spec.ts
│       ├── report.spec.ts
│       └── accessibility.spec.ts
└── .wp-env.json                               # Local WP test site
```

### Core database tables

Use the WordPress prefix plus these suffixes:

1. `acorn_hc_content_versions`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `version_key VARCHAR(32) NOT NULL UNIQUE`
   - `status VARCHAR(16) NOT NULL` (`draft|published|retired`)
   - `created_by BIGINT UNSIGNED NULL`
   - `created_at DATETIME NOT NULL`
   - `published_at DATETIME NULL`
   - `notes TEXT NULL`

2. `acorn_hc_questions`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `content_version_id BIGINT UNSIGNED NOT NULL`
   - `question_key VARCHAR(64) NOT NULL`
   - `module_key VARCHAR(32) NOT NULL`
   - `sort_order INT NOT NULL`
   - `question_text TEXT NOT NULL`
   - `help_text TEXT NOT NULL`
   - `variant_json LONGTEXT NULL`
   - `applicability_json LONGTEXT NOT NULL`
   - `answer_rules_json LONGTEXT NOT NULL`
   - `source_json LONGTEXT NOT NULL`
   - `last_reviewed DATE NOT NULL`
   - `next_review DATE NOT NULL`
   - `reviewed_by VARCHAR(120) NOT NULL`
   - `is_active TINYINT(1) NOT NULL DEFAULT 1`
   - unique key on `(content_version_id, question_key)`

3. `acorn_hc_recommendations`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `content_version_id BIGINT UNSIGNED NOT NULL`
   - `question_key VARCHAR(64) NOT NULL`
   - `answer_value VARCHAR(16) NOT NULL`
   - `jurisdiction VARCHAR(24) NOT NULL DEFAULT '*'`
   - `finding_status VARCHAR(16) NOT NULL`
   - `heading TEXT NOT NULL`
   - `identified_text TEXT NOT NULL`
   - `next_step_text TEXT NOT NULL`
   - `why_text TEXT NOT NULL`
   - `good_looks_text TEXT NOT NULL`
   - `service_tags_json LONGTEXT NOT NULL`
   - `source_json LONGTEXT NOT NULL`
   - `sort_rank INT NOT NULL DEFAULT 100`
   - unique key on `(content_version_id, question_key, answer_value, jurisdiction)`

4. `acorn_hc_assessments`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `assessment_token_hash CHAR(64) NOT NULL UNIQUE`
   - `report_token_hash CHAR(64) NULL UNIQUE`
   - `status VARCHAR(16) NOT NULL` (`in_progress|assessed|completed|expired`)
   - `content_version_id BIGINT UNSIGNED NOT NULL`
   - `jurisdiction VARCHAR(24) NULL`
   - `employee_band VARCHAR(16) NULL`
   - `sector VARCHAR(64) NULL`
   - `profile_json LONGTEXT NOT NULL`
   - `priority_count INT NOT NULL DEFAULT 0`
   - `review_count INT NOT NULL DEFAULT 0`
   - `addressed_count INT NOT NULL DEFAULT 0`
   - `overall_status VARCHAR(32) NULL`
   - `service_tags_json LONGTEXT NOT NULL`
   - `snapshot_json LONGTEXT NULL`
   - `contact_id BIGINT UNSIGNED NULL`
   - `email_status VARCHAR(16) NOT NULL DEFAULT 'not_sent'`
   - `email_last_error TEXT NULL`
   - `pdf_status VARCHAR(16) NOT NULL DEFAULT 'not_generated'`
   - `pdf_last_error TEXT NULL`
   - `started_at DATETIME NOT NULL`
   - `assessed_at DATETIME NULL`
   - `completed_at DATETIME NULL`
   - `last_activity_at DATETIME NOT NULL`

5. `acorn_hc_answers`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `assessment_id BIGINT UNSIGNED NOT NULL`
   - `question_key VARCHAR(64) NOT NULL`
   - `question_row_id BIGINT UNSIGNED NOT NULL`
   - `answer_value VARCHAR(16) NOT NULL`
   - `finding_status VARCHAR(16) NOT NULL`
   - `recommendation_row_id BIGINT UNSIGNED NULL`
   - `answered_at DATETIME NOT NULL`
   - unique key on `(assessment_id, question_key)`

6. `acorn_hc_contacts`
   - `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
   - `first_name VARCHAR(120) NOT NULL`
   - `last_name VARCHAR(120) NOT NULL`
   - `company VARCHAR(190) NOT NULL`
   - `email VARCHAR(190) NOT NULL`
   - `telephone VARCHAR(40) NULL`
   - `postcode VARCHAR(16) NULL`
   - `audit_requested TINYINT(1) NOT NULL DEFAULT 0`
   - `marketing_consent TINYINT(1) NOT NULL DEFAULT 0`
   - `marketing_consent_at DATETIME NULL`
   - `created_at DATETIME NOT NULL`

No database foreign-key constraints: use WordPress-style application-level integrity so installs remain compatible with normal WordPress/MySQL/MariaDB hosting.

### Public REST contract

Namespace: `/wp-json/acorn-healthcheck/v1`

- `POST /assessments` -> create anonymous assessment; return raw 32-byte URL-safe token once.
- `GET /assessments/{token}` -> resume an unexpired assessment; return profile, applicable answered questions, next question, progress.
- `PATCH /assessments/{token}/profile` -> validate/update profile; recalculate applicability and invalidate excluded answers.
- `PUT /assessments/{token}/answers/{question_key}` -> validate answer and current applicability; persist; return new state/progress.
- `POST /assessments/{token}/assess` -> require all currently applicable questions answered; compute headline result; set `assessed`.
- `POST /assessments/{token}/complete` -> validate contact/audit/consent; create contact; snapshot result; issue raw 32-byte report token; set `completed`; trigger PDF/email/internal notification best-effort.
- `POST /reports/{report_token}/resend` -> rate-limited customer resend.
- `GET /reports/{report_token}/data` -> full canonical report data for secure report page only after completion.

The raw assessment/report token is generated with `random_bytes(32)` and `rtrim(strtr(base64_encode(...), '+/', '-_'), '=')`. Store only `hash('sha256', $rawToken)` in the database.

### Canonical report-data shape

`ReportDataBuilder::build(int $assessmentId): array` must return:

```php
[
    'meta' => [
        'company' => 'ABC Engineering Ltd',
        'assessment_date' => '2026-09-17',
        'jurisdiction' => 'england',
        'content_version' => '1.0',
        'areas_assessed' => 24,
    ],
    'summary' => [
        'overall_status' => 'priority_actions_identified',
        'priority_count' => 3,
        'review_count' => 4,
        'addressed_count' => 17,
    ],
    'pillars' => [
        ['key' => 'health_safety', 'label' => 'Health & Safety', 'status' => 'review'],
        ['key' => 'fire', 'label' => 'Fire Safety', 'status' => 'priority'],
        ['key' => 'legionella', 'label' => 'Legionella', 'status' => 'addressed'],
        ['key' => 'asbestos', 'label' => 'Asbestos', 'status' => 'not_assessed'],
    ],
    'sections' => [
        'addressed' => ['label' => 'What appears to be working'],
        'review' => ['label' => 'Things worth checking'],
        'priority' => ['label' => 'Priority actions'],
    ],
    'addressed' => [],
    'review' => [],
    'priority' => [],
    'action_summary' => [],
    'disclaimer' => 'This Healthcheck is based on information supplied through an online self-assessment. It is intended to highlight areas that may merit further review and does not constitute a formal audit, legal advice or confirmation of compliance.',
    'support' => [
        'heading' => 'Need help with any of the actions identified?',
        'cta_label' => 'Request a free Health & Safety Compliance Audit',
        'cta_url' => '',
    ],
];
```

### V1 profile fields

`config/profile-fields.php` is code-owned, not editable in admin for V1. It contains these exact keys:

- `jurisdiction`: `england|wales|scotland|northern_ireland`
- `employee_band`: `none|1_4|5_9|10_49|50_249|250_plus`
- `sector`: `office_professional|retail|hospitality|manufacturing|warehouse_logistics|construction_trades|property_fm|education|healthcare_care|automotive|leisure|charity|other`
- `workplace_types[]`: `office|retail|warehouse|workshop_manufacturing|education|care|construction_site|client_premises|home_working|other`
- `premises_responsibility`: `yes|partly|no|not_sure`
- `shared_premises`: `yes|no|not_sure`
- `risk_flags[]`: `dse|manual_handling|hazardous_substances|lone_working|work_at_height|machinery|contractors|young_workers|driving_for_work`
- `water_system_responsibility`: `yes|partly|no|not_sure`
- `maintenance_repair_responsibility`: `yes|partly|no|not_sure`
- `building_pre_2000`: `yes|no|not_sure|not_relevant`
- `intrusive_work_planned`: `yes|no|not_sure`

### V1 scored question set

Seed exactly these 35 question keys. The applicability engine may choose a wording variant by jurisdiction/employee band, but a completed snapshot stores the actual displayed text.

| Key | Module | Question | Applicability | No / Not sure default |
|---|---|---|---|---|
| `M01_COMPETENT_PERSON` | management | Has a competent person been appointed to help the organisation manage health and safety? | core | priority / review |
| `M02_POLICY` | management | Are clear health and safety arrangements in place, including a current written policy where the size of the organisation requires one? | core | review / review |
| `M03_RESPONSIBILITIES` | management | Are health and safety responsibilities clearly allocated and understood? | core | review / review |
| `M04_CONSULTATION` | management | If you employ people, do you consult them about health and safety risks and controls? | employee band != none | review / review |
| `R01_GENERAL_RA` | risk | Have the significant hazards associated with your work been assessed and suitable controls put in place? | core | priority / priority |
| `R02_ACTION_REVIEW` | risk | Are actions from risk assessments tracked, and are assessments reviewed when work, people, equipment or circumstances change? | core | review / review |
| `R03_HIGHER_NEEDS` | risk | Where relevant, do your arrangements consider people who may need additional protection, such as young, new, inexperienced or otherwise vulnerable workers? | employee band != none | review / review |
| `T01_INDUCTION` | people | Do new starters receive a health and safety induction appropriate to their role and workplace? | employee band != none | review / review |
| `T02_TRAINING_SUPERVISION` | people | Do workers receive the information, instruction, training and supervision they need to work safely? | employee band != none | priority / review |
| `T03_TRAINING_RECORDS` | people | Are training records maintained and refresher needs reviewed where appropriate? | employee band != none | review / review |
| `A01_FIRST_AID` | incidents | Have you assessed your first-aid needs and provided suitable equipment, facilities and people for your circumstances? | employee band != none | priority / review |
| `A02_INCIDENTS` | incidents | Are accidents and near misses recorded, reviewed and investigated where appropriate? | employee band != none | review / review |
| `A03_RIDDOR` | incidents | Do the appropriate people know which work-related incidents must be reported under the relevant RIDDOR regime and who is responsible for reporting them? | employee band != none | review / review |
| `W01_WELFARE` | workplace | Are suitable welfare facilities and general workplace conditions maintained for people using the workplace? | non-home-only workplace OR premises responsibility != no | review / review |
| `W02_WORK_EQUIPMENT` | workplace | Where work equipment is provided, is it suitable, maintained and inspected where necessary to keep it safe? | machinery risk OR workplace is not home-only | review / review |
| `F01_FIRE_RESPONSIBILITY` | fire | Are you clear who holds the relevant fire-safety responsibilities for the premises, including how responsibilities are shared where more than one party has control? | premises responsibility != no AND non-private-home workplace | review / review |
| `F02_FIRE_RA` | fire | Is there a suitable current fire risk assessment covering the premises and activities for which your organisation has responsibility? | fire module | priority / priority |
| `F03_FIRE_ACTIONS` | fire | Are actions identified by the fire risk assessment tracked and appropriately addressed? | fire module | priority / review |
| `F04_FIRE_EMERGENCY_TRAINING` | fire | Are suitable emergency and evacuation arrangements in place, and do relevant workers receive appropriate fire-safety information and training? | fire module | priority / review |
| `F05_FIRE_SYSTEMS` | fire | Are relevant fire detection, warning, emergency lighting, firefighting and other fire-safety measures checked and maintained as required for the premises? | fire module | priority / review |
| `L01_LEGIONELLA_RA` | legionella | Has the risk from legionella in relevant water systems been assessed by someone competent to do so? | water responsibility yes/partly/not_sure | priority / review |
| `L02_LEGIONELLA_CONTROLS` | legionella | Where the assessment identifies controls, are responsibilities clear and are the required control measures being implemented? | legionella module | priority / review |
| `L03_LEGIONELLA_RECORDS` | legionella | Where ongoing controls are required, are monitoring, maintenance and relevant records kept up to date? | legionella module | review / review |
| `AS01_ASBESTOS_INFORMATION` | asbestos | For buildings that may contain asbestos, have you established whether asbestos-containing materials are present or are being presumed present in the areas for which you are responsible? | maintenance responsibility yes/partly/not_sure AND building_pre_2000 != no | priority / review |
| `AS02_ASBESTOS_MANAGEMENT` | asbestos | Where asbestos is present or presumed, is there an up-to-date record/register and a plan for managing the risk? | asbestos module | priority / review |
| `AS03_ASBESTOS_MONITORING_INFO` | asbestos | Are known or presumed asbestos-containing materials kept under review, and is relevant information made available to people who could disturb them? | asbestos module | priority / review |
| `AS04_ASBESTOS_INTRUSIVE` | asbestos | If refurbishment, intrusive maintenance or demolition work is planned, is appropriate asbestos information available for the areas the work could disturb? | asbestos module AND intrusive_work_planned yes/not_sure | priority / review |
| `C01_COSHH` | specialist | Where hazardous substances are used or generated, have the risks been assessed and suitable exposure controls put in place and reviewed? | hazardous_substances flag | priority / review |
| `D01_DSE` | specialist | For workers who regularly use display screen equipment, have workstation risks been assessed and identified actions addressed? | dse flag | review / review |
| `MH01_MANUAL_HANDLING` | specialist | Have hazardous manual-handling activities been avoided where reasonably practicable and remaining risks assessed and controlled? | manual_handling flag | review / review |
| `LW01_LONE_WORKING` | specialist | Have the additional risks from lone working been assessed, including communication, supervision and emergency arrangements? | lone_working flag | review / review |
| `WAH01_WORK_AT_HEIGHT` | specialist | Is work at height properly planned, appropriately supervised and carried out by competent people using suitable equipment? | work_at_height flag | priority / review |
| `YW01_YOUNG_WORKERS` | specialist | If workers under 18 are employed, have the additional risks arising from their age, experience and maturity been considered and controlled? | young_workers flag | priority / review |
| `CT01_CONTRACTORS` | specialist | Where contractors work under your control or at your premises, do you have arrangements to select, brief, coordinate and monitor them? | contractors flag | review / review |
| `DRV01_DRIVING` | specialist | Where people drive for work, are the journey, driver and vehicle risks managed as part of your health and safety arrangements? | driving_for_work flag | review / review |

All questions use `Yes`, `Partly`, `No`, `Not sure`. `Yes => addressed`; `Partly => review`. The table above supplies the `No` and `Not sure` defaults. A jurisdiction-specific recommendation may override copy but not silently upgrade/downgrade status without a new published content version.

### Official-source metadata for V1 content

Seed source metadata with official primary regulator/government URLs. At minimum:

- HSE competent assistance: `https://www.hse.gov.uk/simple-health-safety/gettinghelp/`
- HSE policy: `https://www.hse.gov.uk/simple-health-safety/policy/`
- HSE risk management: `https://www.hse.gov.uk/simple-health-safety/risk/`
- HSE consultation: `https://www.hse.gov.uk/simple-health-safety/consult.htm`
- HSE training: `https://www.hse.gov.uk/simple-health-safety/training/`
- HSE first aid: `https://www.hse.gov.uk/firstaid/what-employers-need-to-do.htm`
- HSE RIDDOR: `https://www.hse.gov.uk/riddor/`
- HSE workplace facilities: `https://www.hse.gov.uk/simple-health-safety/workplace-facilities/`
- HSE work equipment/Puwer: `https://www.hse.gov.uk/work-equipment-machinery/puwer.htm`
- HSE COSHH: `https://www.hse.gov.uk/coshh/basics/assessment.htm`
- HSE DSE: `https://www.hse.gov.uk/msd/dse/assessment.htm`
- HSE manual handling: `https://www.hse.gov.uk/msd/manual-handling/`
- HSE lone working: `https://www.hse.gov.uk/lone-working/`
- HSE work at height: `https://www.hse.gov.uk/work-at-height/`
- HSE young workers: `https://www.hse.gov.uk/young-workers/employer/`
- HSE work-related road safety: `https://www.hse.gov.uk/work-related-road-safety/`
- England fire: `https://www.gov.uk/workplace-fire-safety-your-responsibilities`
- Wales fire: `https://www.gov.wales/fire-safety-guidance-businesses-and-workplaces`
- Scotland fire: `https://www.gov.scot/policies/fire-and-rescue/non-domestic-fire-safety/`
- Northern Ireland fire: `https://www.nifrs.org/home/staying-safe/business-fire-safety/fire-risk-assessments/`
- Great Britain Legionella: `https://www.hse.gov.uk/legionnaires/workplace-risks.htm`
- Northern Ireland Legionella: `https://www.hseni.gov.uk/topics/legionella`
- Great Britain asbestos duty to manage: `https://www.hse.gov.uk/asbestos/duty/`
- Northern Ireland asbestos: `https://www.hseni.gov.uk/topics/asbestos`

Before publishing content version `1.0`, an Acorn technical reviewer signs off the wording and source metadata for Health & Safety, Fire, Legionella, and Asbestos in the Content Review admin screen. This is a launch gate, not a runtime dependency.

---

### Task 1: Bootstrap the plugin and local test harness

**Files:**
- Create: `acorn-safety-healthcheck.php`
- Create: `composer.json`
- Create: `package.json`
- Create: `.wp-env.json`
- Create: `phpunit.xml.dist`
- Create: `src/Plugin.php`
- Create: `tests/bootstrap.php`
- Create: `tests/unit/PluginSmokeTest.php`

**Interfaces:**
- Produces: `Acorn\SafetyHealthcheck\Plugin::boot(): void`
- Produces: plugin constant `ACORN_HC_VERSION`
- Produces: Composer PSR-4 namespace `Acorn\SafetyHealthcheck\ => src/`

- [ ] **Step 1: Write the failing smoke test**

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Acorn\SafetyHealthcheck\Plugin;

final class PluginSmokeTest extends TestCase
{
    public function test_plugin_class_can_be_constructed(): void
    {
        $plugin = new Plugin();
        self::assertInstanceOf(Plugin::class, $plugin);
    }
}
```

- [ ] **Step 2: Run the test and verify it fails because the namespace/class does not exist**

Run: `composer install && vendor/bin/phpunit tests/unit/PluginSmokeTest.php`
Expected: FAIL with class-not-found/autoload failure.

- [ ] **Step 3: Add the bootstrap, autoloading, WordPress hook wiring, and development scripts**

`acorn-safety-healthcheck.php` should do no application work beyond loading Composer, defining version/path constants, and calling `Plugin::boot()` on `plugins_loaded`.

```php
<?php
/**
 * Plugin Name: Acorn Safety Healthcheck
 * Description: Client-first Health & Safety Healthcheck for Acorn Safety Services.
 * Version: 0.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('ACORN_HC_VERSION', '0.1.0');
define('ACORN_HC_FILE', __FILE__);
define('ACORN_HC_DIR', plugin_dir_path(__FILE__));

require ACORN_HC_DIR . 'vendor/autoload.php';

add_action('plugins_loaded', static function (): void {
    (new Acorn\SafetyHealthcheck\Plugin())->boot();
});
```

`composer.json` must include PSR-4 autoload and scripts `test:unit`, `test:integration`, `test`.

`composer.json` scripts must be exactly `test:unit => phpunit --testsuite unit`, `test:integration => npx wp-env run tests-cli --env-cwd=wp-content/plugins/acorn-safety-healthcheck vendor/bin/phpunit --testsuite integration`, and `test => @test:unit && @test:integration`. `package.json` must include `wp:start => wp-env start`, `wp:stop => wp-env stop`, `test:e2e => playwright test`, `test:a11y => playwright test tests/e2e/accessibility.spec.ts`, with dev dependencies `@wordpress/env`, `@playwright/test`, and `@axe-core/playwright`. `playwright.config.ts` uses base URL `http://localhost:8888` and `tests/e2e/global-setup.ts`; global setup creates or updates a published `/health-and-safety-healthcheck/` page containing `[acorn_safety_healthcheck]` through `wp-env run cli` before browser tests.

- [ ] **Step 4: Run the smoke test and start wp-env**

Run: `composer dump-autoload && composer test:unit`
Expected: PASS.

Run: `npm install && npm run wp:start`
Expected: WordPress test site starts with the plugin directory mounted.

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "chore: bootstrap Acorn Safety Healthcheck plugin"
```

---

### Task 2: Install dedicated database tables and schema versioning

**Files:**
- Create: `src/Database/Schema.php`
- Create: `src/Database/Migrations.php`
- Create: `src/Activation.php`
- Modify: `acorn-safety-healthcheck.php`
- Test: `tests/integration/SchemaTest.php`

**Interfaces:**
- Produces: `Schema::install(): void`
- Produces: `Schema::table(string $suffix): string`
- Produces: option `acorn_hc_schema_version = 1`
- Consumes: WordPress `$wpdb`, `dbDelta()`

- [ ] **Step 1: Write an integration test that activates the plugin and asserts all six tables exist with the expected unique indexes**

```php
public function test_activation_creates_healthcheck_tables(): void
{
    Schema::install();
    global $wpdb;

    foreach (['content_versions', 'questions', 'recommendations', 'assessments', 'answers', 'contacts'] as $suffix) {
        $table = Schema::table($suffix);
        self::assertSame($table, $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)));
    }

    self::assertSame('1', get_option('acorn_hc_schema_version'));
}
```

- [ ] **Step 2: Run the integration test and confirm it fails**

Run: `composer test:integration -- --filter SchemaTest`
Expected: FAIL because schema classes/tables do not exist.

- [ ] **Step 3: Implement `Schema::install()` using `dbDelta()` with the exact table definitions in this plan**

Register activation from the plugin root:

```php
register_activation_hook(ACORN_HC_FILE, [Acorn\SafetyHealthcheck\Activation::class, 'activate']);
```

`Activation::activate()` calls `Schema::install()` and later, after Task 4 exists, `SeedContent::installIfEmpty()`.

- [ ] **Step 4: Run the integration test and inspect the schema**

Run: `composer test:integration -- --filter SchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add acorn-safety-healthcheck.php src/Database src/Activation.php tests/integration/SchemaTest.php
git commit -m "feat: add healthcheck database schema"
```

---

### Task 3: Add domain value objects and deterministic status rules

**Files:**
- Create: `src/Domain/AssessmentStatus.php`
- Create: `src/Domain/AnswerValue.php`
- Create: `src/Domain/FindingStatus.php`
- Create: `src/Domain/OverallStatus.php`
- Create: `src/Domain/ApplicabilityContext.php`
- Test: `tests/unit/DomainValuesTest.php`

**Interfaces:**
- Produces: `AnswerValue::assert(string $value): string`
- Produces: `FindingStatus::assert(string $value): string`
- Produces: `OverallStatus::fromCounts(int $priority, int $review): string`
- Produces: `ApplicabilityContext::fromProfile(array $profile): ApplicabilityContext`

- [ ] **Step 1: Write failing tests for allowed answers, invalid answers, and overall result copy states**

```php
public function test_overall_status_is_deterministic(): void
{
    self::assertSame('priority_actions_identified', OverallStatus::fromCounts(1, 0));
    self::assertSame('several_areas_to_review', OverallStatus::fromCounts(0, 3));
    self::assertSame('some_areas_to_review', OverallStatus::fromCounts(0, 2));
    self::assertSame('no_obvious_gaps', OverallStatus::fromCounts(0, 0));
}

public function test_invalid_answer_is_rejected(): void
{
    $this->expectException(InvalidArgumentException::class);
    AnswerValue::assert('compliant');
}
```

- [ ] **Step 2: Run the tests and verify failure**

Run: `composer test:unit -- --filter DomainValuesTest`
Expected: FAIL because classes are missing.

- [ ] **Step 3: Implement immutable/readonly value handling**

```php
final class AnswerValue
{
    public const YES = 'yes';
    public const PARTLY = 'partly';
    public const NO = 'no';
    public const NOT_SURE = 'not_sure';

    public static function assert(string $value): string
    {
        if (!in_array($value, [self::YES, self::PARTLY, self::NO, self::NOT_SURE], true)) {
            throw new InvalidArgumentException('Invalid healthcheck answer.');
        }
        return $value;
    }
}
```

`ApplicabilityContext` validates exact profile keys/options from this plan and exposes convenience methods such as `hasRiskFlag(string $flag): bool`, `hasEmployees(): bool`, `hasFireResponsibility(): bool`, `hasWaterResponsibility(): bool`, `hasAsbestosResponsibility(): bool`.

- [ ] **Step 4: Run unit tests**

Run: `composer test:unit -- --filter DomainValuesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Domain tests/unit/DomainValuesTest.php
git commit -m "feat: add healthcheck domain value rules"
```

---

### Task 4: Seed versioned V1 questions, recommendations, and official-source metadata

**Files:**
- Create: `src/Content/ContentVersionRepository.php`
- Create: `src/Content/QuestionRepository.php`
- Create: `src/Content/RecommendationRepository.php`
- Create: `src/Content/SeedContent.php`
- Modify: `src/Activation.php`
- Test: `tests/integration/SeedContentTest.php`

**Interfaces:**
- Produces: `ContentVersionRepository::getPublished(): array`
- Produces: `QuestionRepository::forVersion(int $contentVersionId): array`
- Produces: `QuestionRepository::get(int $contentVersionId, string $questionKey): array`
- Produces: `RecommendationRepository::resolve(int $contentVersionId, string $questionKey, string $answer, string $jurisdiction): ?array`
- Produces: published content version `1.0`

- [ ] **Step 1: Write a failing seed-content integration test**

```php
public function test_v1_seed_contains_exact_question_keys_and_recommendations(): void
{
    SeedContent::installIfEmpty();
    $version = (new ContentVersionRepository())->getPublished();
    self::assertSame('1.0', $version['version_key']);

    $questions = (new QuestionRepository())->forVersion((int) $version['id']);
    self::assertCount(35, $questions);
    self::assertContains('F02_FIRE_RA', array_column($questions, 'question_key'));
    self::assertContains('AS04_ASBESTOS_INTRUSIVE', array_column($questions, 'question_key'));

    $rec = (new RecommendationRepository())->resolve(
        (int) $version['id'], 'R01_GENERAL_RA', 'no', 'england'
    );
    self::assertSame('priority', $rec['finding_status']);
    self::assertNotSame('', trim($rec['good_looks_text']));
}
```

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter SeedContentTest`
Expected: FAIL because content repositories/seed do not exist.

- [ ] **Step 3: Implement the V1 seed using the 35-question table and source list in this plan**

For each question create recommendation rows for `partly`, `no`, and `not_sure`. `yes` does not need a recommendation row; its positive output comes from the question's positive label/heading. Use neutral, practical copy and include all five report fields for non-green findings.

Use this exact content pattern for every recommendation:

```php
[
    'question_key' => 'R01_GENERAL_RA',
    'answer_value' => 'no',
    'jurisdiction' => '*',
    'finding_status' => 'priority',
    'heading' => 'Review your workplace risk assessments',
    'identified_text' => 'Your answer indicates that suitable risk assessments may not currently be in place for all significant hazards and activities relevant to the organisation.',
    'next_step_text' => 'Review the significant hazards associated with your work, who may be affected, the controls already in place and any additional measures needed. Record and communicate the resulting actions where required.',
    'why_text' => 'Risk assessment provides the basis for deciding what controls are needed to prevent harm and for keeping those controls under review.',
    'good_looks_text' => 'The significant hazards are understood, proportionate controls are in place, actions are owned, and the assessments are reviewed when relevant circumstances change.',
    'service_tags' => ['health_safety', 'risk_assessment'],
    'sort_rank' => 10,
]
```

Generate all `partly`, `no`, and `not_sure` rows from the exact subject/next-step/why/good-looks bases and answer wrappers in Appendix A. Set `heading` to `ucfirst(subject)`. Set `sort_rank` to the question's seed order multiplied by 10, so ranking is deterministic and editable in later content versions. Apply jurisdiction-specific source/copy overrides where Appendix A or the Fire/NI source rules require them. Never generate text that says the user is non-compliant.

Add jurisdiction-specific recommendation/source variants for Fire (`england`, `wales`, `scotland`, `northern_ireland`) and use HSENI/NIFRS source metadata for Northern Ireland Legionella/Asbestos/Fire.

- [ ] **Step 4: Run the seed test and add a content-completeness assertion**

Add a test loop asserting every active question has non-empty `partly`, `no`, and `not_sure` recommendations and each recommendation has `next_step_text`, `why_text`, `good_looks_text`, source metadata, and a valid finding status.

Run: `composer test:integration -- --filter SeedContentTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Content src/Activation.php tests/integration/SeedContentTest.php
git commit -m "feat: seed versioned healthcheck content"
```

---

### Task 5: Implement the applicability and wording-variant rules engine

**Files:**
- Create: `src/Domain/RulesEngine.php`
- Test: `tests/unit/RulesEngineTest.php`

**Interfaces:**
- Consumes: `ApplicabilityContext`, version-scoped question rows
- Produces: `RulesEngine::applicableQuestions(ApplicabilityContext $context, array $questions): array`
- Produces: `RulesEngine::isApplicable(ApplicabilityContext $context, array $question): bool`
- Produces: `RulesEngine::resolveQuestionText(ApplicabilityContext $context, array $question): string`

- [ ] **Step 1: Write failing scenario tests covering the main conditional branches**

```php
public function test_small_office_sees_dse_and_fire_but_not_coshh_or_work_at_height(): void
{
    $context = ApplicabilityContext::fromProfile([
        'jurisdiction' => 'england',
        'employee_band' => '10_49',
        'sector' => 'office_professional',
        'workplace_types' => ['office'],
        'premises_responsibility' => 'yes',
        'shared_premises' => 'yes',
        'risk_flags' => ['dse'],
        'water_system_responsibility' => 'no',
        'maintenance_repair_responsibility' => 'no',
        'building_pre_2000' => 'not_relevant',
        'intrusive_work_planned' => 'no',
    ]);

    $keys = array_column($this->engine->applicableQuestions($context, $this->questions), 'question_key');
    self::assertContains('D01_DSE', $keys);
    self::assertContains('F02_FIRE_RA', $keys);
    self::assertNotContains('C01_COSHH', $keys);
    self::assertNotContains('WAH01_WORK_AT_HEIGHT', $keys);
    self::assertNotContains('L01_LEGIONELLA_RA', $keys);
}
```

Also add scenarios for:
- 1–4 employees versus 5+ policy wording.
- no employees: consultation/training/first-aid/RIDDOR employee questions excluded as appropriate.
- water responsibility `partly` and `not_sure`: Legionella module included.
- maintenance responsibility plus pre-2000/unknown building: Asbestos module included.
- intrusive work `yes`: `AS04_ASBESTOS_INTRUSIVE` included.
- intrusive work `no`: `AS04_ASBESTOS_INTRUSIVE` excluded.
- each specialist risk flag independently enables its one specialist question.
- home-working-only with no controlled non-domestic premises: Fire/premises modules excluded.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:unit -- --filter RulesEngineTest`
Expected: FAIL because `RulesEngine` does not exist.

- [ ] **Step 3: Implement explicit condition evaluation without `eval()` or arbitrary expression execution**

Represent applicability rules as structured JSON such as:

```json
{
  "all": [
    {"field":"maintenance_repair_responsibility","in":["yes","partly","not_sure"]},
    {"field":"building_pre_2000","in":["yes","not_sure"]}
  ]
}
```

Support only these operators: `all`, `any`, `field in`, `field not_in`, `risk_flag`, `has_employees`, `non_home_workplace`. Reject unknown operators when content is seeded/published.

Use explicit wording variants for `M02_POLICY` so 5+ users see written-policy wording while smaller organisations are not marked down merely for not having a written document.

- [ ] **Step 4: Run all rule scenarios**

Run: `composer test:unit -- --filter RulesEngineTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Domain/RulesEngine.php tests/unit/RulesEngineTest.php
git commit -m "feat: add healthcheck applicability rules"
```

---

### Task 6: Implement deterministic findings, section statuses, overall status, and internal opportunity tags

**Files:**
- Create: `src/Domain/ResultsEngine.php`
- Create: `src/Domain/OpportunityTagger.php`
- Test: `tests/unit/ResultsEngineTest.php`
- Test: `tests/unit/OpportunityTaggerTest.php`

**Interfaces:**
- Produces: `ResultsEngine::evaluate(array $applicableQuestions, array $answers, string $jurisdiction, int $contentVersionId): array`
- Return keys: `findings`, `section_statuses`, `priority_count`, `review_count`, `addressed_count`, `overall_status`
- Produces: `OpportunityTagger::tags(array $findings): array`

- [ ] **Step 1: Write failing tests for answer mapping and section roll-up**

```php
public function test_priority_beats_review_for_section_status(): void
{
    $result = $this->engine->rollUp([
        ['module_key' => 'fire', 'finding_status' => 'review'],
        ['module_key' => 'fire', 'finding_status' => 'priority'],
        ['module_key' => 'people', 'finding_status' => 'addressed'],
    ]);

    self::assertSame('priority', $result['section_statuses']['fire']);
    self::assertSame('addressed', $result['section_statuses']['people']);
}
```

Add a test proving a client-supplied count is irrelevant because counts are derived exclusively from persisted answers and versioned recommendation rules.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:unit -- --filter 'ResultsEngineTest|OpportunityTaggerTest'`
Expected: FAIL.

- [ ] **Step 3: Implement result evaluation and neutral internal tags**

`ResultsEngine` must:
1. Treat `yes` as `addressed`.
2. Treat `partly/no/not_sure` according to the resolved recommendation row.
3. Refuse answers to non-applicable questions.
4. Return section status by `priority > review > addressed > not_assessed`.
5. Return overall status using the exact `OverallStatus::fromCounts()` rules.

`OpportunityTagger` must derive internal-only tags from non-green findings' curated `service_tags_json`, de-duplicate them, and never feed tags back into customer-facing content.

- [ ] **Step 4: Run tests**

Run: `composer test:unit -- --filter 'ResultsEngineTest|OpportunityTaggerTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Domain/ResultsEngine.php src/Domain/OpportunityTagger.php tests/unit
git commit -m "feat: add deterministic healthcheck results"
```

---

### Task 7: Build assessment persistence, secure tokens, answer invalidation, and lifecycle service

**Files:**
- Create: `src/Assessment/AssessmentRepository.php`
- Create: `src/Assessment/AnswerRepository.php`
- Create: `src/Assessment/AssessmentService.php`
- Create: `src/Domain/AssessmentState.php`
- Test: `tests/integration/AssessmentLifecycleTest.php`

**Interfaces:**
- Produces: `AssessmentService::start(): array{token:string,state:array}`
- Produces: `AssessmentService::resume(string $rawToken): AssessmentState`
- Produces: `AssessmentService::updateProfile(string $rawToken, array $profile): AssessmentState`
- Produces: `AssessmentService::saveAnswer(string $rawToken, string $questionKey, string $answer): AssessmentState`
- Produces: `AssessmentService::assess(string $rawToken): array`
- `AssessmentRepository::findByToken(string $rawToken): ?array` hashes input before lookup.

- [ ] **Step 1: Write failing lifecycle tests**

Cover:
- `start()` binds the current published content version and stores only a SHA-256 token hash.
- resume with an invalid token returns not found.
- answer to a currently non-applicable question is rejected.
- changing profile from `work_at_height` selected to unselected deletes/excludes `WAH01_WORK_AT_HEIGHT` answer.
- changing pre-2000 building to post-2000 removes now-inapplicable asbestos answers.
- `assess()` refuses to finish while an applicable question is unanswered.
- `assess()` stores counts/overall status recalculated server-side.

Example backtracking assertion:

```php
$service->updateProfile($token, $profileWithWorkAtHeight);
$service->saveAnswer($token, 'WAH01_WORK_AT_HEIGHT', 'no');
$service->updateProfile($token, $profileWithoutWorkAtHeight);

self::assertNull($answers->findByQuestion($assessmentId, 'WAH01_WORK_AT_HEIGHT'));
```

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter AssessmentLifecycleTest`
Expected: FAIL.

- [ ] **Step 3: Implement repositories with prepared `$wpdb` queries and transactional-style ordering**

When profile changes:
1. persist validated profile;
2. compute new applicable key set;
3. delete answer rows whose keys are no longer applicable;
4. touch `last_activity_at`;
5. return a fresh `AssessmentState`.

When an answer is saved, persist the exact `question_row_id`, answer, generated finding status, and resolved `recommendation_row_id` so later audits can show exactly what rule was used even before completion.

- [ ] **Step 4: Run lifecycle tests**

Run: `composer test:integration -- --filter AssessmentLifecycleTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Assessment src/Domain/AssessmentState.php tests/integration/AssessmentLifecycleTest.php
git commit -m "feat: add assessment lifecycle and persistence"
```

---

### Task 8: Expose the public REST assessment API with strict validation and rate limits

**Files:**
- Create: `src/Rest/AssessmentController.php`
- Create: `src/Security/RateLimiter.php`
- Modify: `src/Plugin.php`
- Test: `tests/integration/RestAssessmentTest.php`

**Interfaces:**
- Consumes: `AssessmentService`
- Produces REST routes listed in the Public REST contract through `/assess`
- Produces consistent JSON errors `{code, message}` with HTTP 400/404/409/429 as appropriate

- [ ] **Step 1: Write failing REST integration tests**

Test:
- POST start returns 201 and a non-empty token.
- invalid profile option returns 400.
- invalid answer value such as `compliant` returns 400.
- unknown question key returns 400.
- non-applicable known question returns 409.
- manipulated fields such as `priority_count: 0` in the request body are ignored.
- invalid token returns 404.
- burst requests beyond the configured public rate limit return 429.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter RestAssessmentTest`
Expected: FAIL.

- [ ] **Step 3: Implement REST route registration and server-only result authority**

Use `permission_callback => '__return_true'` only for these anonymous routes; security comes from unguessable bearer tokens, strict validation, throttling, and no exposure of contact/report data before completion.

Use a transient-backed limiter keyed by `hash('sha256', $routeGroup . '|' . clientIpPrefix)` with defaults:
- assessment writes: 120 requests / 10 minutes / IP prefix;
- assessment starts: 20 / hour / IP prefix.

Do not persist raw IP addresses in the healthcheck tables.

- [ ] **Step 4: Run REST tests**

Run: `composer test:integration -- --filter RestAssessmentTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Rest/AssessmentController.php src/Security/RateLimiter.php src/Plugin.php tests/integration/RestAssessmentTest.php
git commit -m "feat: expose secure healthcheck assessment API"
```

---

### Task 9: Build the shortcode shell, conditional asset loading, and accessible wizard UI

**Files:**
- Create: `templates/shortcode-shell.php`
- Create: `assets/js/api.js`
- Create: `assets/js/state.js`
- Create: `assets/js/ui.js`
- Create: `assets/js/healthcheck.js`
- Create: `assets/css/healthcheck.css`
- Modify: `src/Plugin.php`
- Create: `tests/e2e/healthcheck.spec.ts`

**Interfaces:**
- Produces shortcode `[acorn_safety_healthcheck]`
- Front end consumes the REST API only; it never computes authoritative result classifications.
- Browser recovery key: `acorn_hc_session_v1` containing `{token,lastSeenAt}` only; never store contact details in localStorage.

- [ ] **Step 1: Write a failing Playwright happy-path test**

```ts
test('visitor can start, answer profile/questions and reach headline result without giving contact details', async ({ page }) => {
  await page.goto('/health-and-safety-healthcheck/');
  await page.getByRole('button', { name: 'Start my Healthcheck' }).click();
  await page.getByLabel('England').check();
  // Complete profile using accessible labels, then loop through returned questions.
  await expect(page.getByRole('heading', { name: /Your Healthcheck is complete/i })).toBeVisible();
  await expect(page.getByText(/Priority actions|Areas to review|No obvious gap/i)).toBeVisible();
  await expect(page.getByLabel('Work email')).not.toBeVisible();
});
```

The final assertion should occur only before the result is reached; once the headline result is shown, contact fields become visible. Structure the test accordingly.

- [ ] **Step 2: Run and verify failure**

Run: `npm run test:e2e -- healthcheck.spec.ts`
Expected: FAIL because shortcode/UI does not exist.

- [ ] **Step 3: Implement the mobile-first wizard**

Requirements in code:
- Landing screen with approved disclaimer and `Start my Healthcheck` CTA.
- Profile collected in no more than three compact screens rather than one question per profile field.
- Scored assessment is one question at a time.
- Answer cards are visually styled labels around native radio inputs.
- `What does this mean?` toggles an accessible region with `aria-expanded` and `aria-controls`.
- Progress includes textual `Question X of Y` plus visual progress bar.
- `Back` restores previous applicable question from server-backed state.
- Save indicator states: `Saving…`, `Saved`, `Working offline — progress kept on this device`.
- No Acorn service promotion appears between start and headline result.
- CSS uses custom properties mapped to the current theme where available and safe fallbacks; statuses include text/icons so colour is never the sole cue.

Do not bundle React/Vue. Render from server-returned state with small pure DOM helpers.

- [ ] **Step 4: Run happy-path E2E and verify plugin assets are absent from unrelated pages**

Run: `npm run test:e2e -- healthcheck.spec.ts`
Expected: PASS.

Add an assertion on a normal WordPress page that neither `healthcheck.js` nor `healthcheck.css` is enqueued.

- [ ] **Step 5: Commit**

```bash
git add templates/shortcode-shell.php assets src/Plugin.php tests/e2e/healthcheck.spec.ts
git commit -m "feat: add accessible healthcheck wizard"
```

---

### Task 10: Add offline/local recovery and robust backtracking UX

**Files:**
- Modify: `assets/js/state.js`
- Modify: `assets/js/api.js`
- Modify: `assets/js/healthcheck.js`
- Create: `tests/e2e/backtracking.spec.ts`

**Interfaces:**
- Produces: `saveLocalPendingMutation(mutation): void`
- Produces: `flushPendingMutations(): Promise<void>`
- Produces: resume prompt for an unexpired local token

- [ ] **Step 1: Write failing browser tests for resume, offline save, and profile backtracking**

Test:
- closing/reloading after several answers offers `Continue Healthcheck` and resumes at the correct position;
- `Start again` discards local token and creates a new server assessment;
- simulated network failure retains the selected answer locally and shows the offline message;
- reconnect flushes pending mutations in order;
- changing `work_at_height` from selected to unselected removes that question and changes progress/counts accordingly.

- [ ] **Step 2: Run and verify failure**

Run: `npm run test:e2e -- backtracking.spec.ts`
Expected: FAIL.

- [ ] **Step 3: Implement queued local recovery with server reconciliation**

Store only:

```js
{
  token: 'raw-public-assessment-token',
  lastSeenAt: '2026-09-17T13:00:00Z',
  pending: [
    { type: 'answer', questionKey: 'R01_GENERAL_RA', answer: 'partly', clientId: 'uuid' }
  ]
}
```

On reconnect/resume, fetch server state first, replay pending mutations, then fetch server state again. Server state always wins on applicability/result logic. Do not store name/email/phone in localStorage.

- [ ] **Step 4: Run backtracking/recovery tests**

Run: `npm run test:e2e -- backtracking.spec.ts`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add assets/js tests/e2e/backtracking.spec.ts
git commit -m "feat: add healthcheck recovery and backtracking"
```

---

### Task 11: Add contact capture, honeypot, immutable completion snapshot, and report tokens

**Files:**
- Create: `src/Assessment/ContactRepository.php`
- Create: `src/Assessment/SnapshotBuilder.php`
- Create: `src/Assessment/CompletionService.php`
- Create: `src/Reports/ReportAccess.php`
- Create: `src/Security/Honeypot.php`
- Create: `src/Rest/CompletionController.php`
- Modify: `src/Plugin.php`
- Test: `tests/integration/CompletionTest.php`

**Interfaces:**
- Produces: `CompletionService::complete(string $assessmentToken, array $contactPayload): array{report_token:string,assessment_id:int}`
- Produces: `SnapshotBuilder::build(int $assessmentId): array`
- Produces: `ReportAccess::findAssessmentId(string $rawReportToken): ?int`

- [ ] **Step 1: Write failing completion tests**

Cover:
- contact cannot be submitted before assessment status is `assessed`;
- required fields: first name, last name, company, valid work email;
- audit request `yes` makes telephone and postcode required;
- marketing consent can remain false while report delivery succeeds;
- honeypot non-empty rejects the request without creating a contact;
- completion generates a report token whose raw value is never stored;
- `snapshot_json` contains exact displayed question text, answer, finding status, recommendation text, source metadata, content version, and profile;
- completed assessment cannot be mutated through profile/answer endpoints.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter CompletionTest`
Expected: FAIL.

- [ ] **Step 3: Implement completion as an idempotent operation**

Within the service, sequence:
1. lock/read assessment and require `assessed` or return the existing completed report token result if a safe idempotency key matches;
2. validate honeypot/contact/audit-consent values;
3. insert contact;
4. build immutable snapshot from bound content rows and answers;
5. issue/store report-token hash;
6. update assessment to `completed` with `completed_at`, `contact_id`, `snapshot_json`;
7. fire `do_action('acorn_healthcheck_completed', $assessmentId);` after commit-like persistence succeeds.

Do not make email/PDF success a prerequisite for completion.

- [ ] **Step 4: Run completion tests**

Run: `composer test:integration -- --filter CompletionTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Assessment src/Reports/ReportAccess.php src/Security/Honeypot.php src/Rest/CompletionController.php src/Plugin.php tests/integration/CompletionTest.php
git commit -m "feat: complete assessments and lock report snapshots"
```

---

### Task 12: Build the canonical report-data object and secure browser report

**Files:**
- Create: `src/Reports/ReportDataBuilder.php`
- Create: `src/Reports/ReportController.php`
- Create: `templates/report-web.php`
- Modify: `src/Plugin.php`
- Test: `tests/unit/ReportDataBuilderTest.php`
- Test: `tests/integration/ReportAccessTest.php`
- Create: `tests/e2e/report.spec.ts`

**Interfaces:**
- Produces: `ReportDataBuilder::build(int $assessmentId): array` with the exact canonical shape in this plan.
- Produces secure route: `/healthcheck/report/{rawReportToken}/`
- Produces secure PDF route later consumed by Task 13: `/healthcheck/report/{rawReportToken}/pdf/`
- Consumes: immutable snapshot only for completed reports; never re-evaluates old completed assessments against current live content.

- [ ] **Step 1: Write failing unit/integration tests for report ordering, pillar roll-up, and token access**

```php
public function test_report_is_balanced_and_sorted(): void
{
    $report = $this->builder->build($this->completedAssessmentId);

    self::assertSame(17, $report['summary']['addressed_count']);
    self::assertSame('What appears to be working', $report['sections']['addressed']['label']);
    self::assertSame('Things worth checking', $report['sections']['review']['label']);
    self::assertSame('Priority actions', $report['sections']['priority']['label']);
    self::assertLessThanOrEqual(
        $report['priority'][1]['sort_rank'],
        $report['priority'][0]['sort_rank']
    );
}
```

Add integration tests proving:
- wrong report token returns 404;
- valid token shows the completed report;
- report data contains no internal `service_tags`;
- report responses send `X-Robots-Tag: noindex, nofollow`, `Referrer-Policy: no-referrer`, and private/no-store cache headers;
- a completed report is unchanged after publishing a newer content version.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:unit -- --filter ReportDataBuilderTest && composer test:integration -- --filter ReportAccessTest`
Expected: FAIL.

- [ ] **Step 3: Implement report-data building from `snapshot_json`**

Pillar mapping:

```php
private const PILLAR_MAP = [
    'fire' => 'fire',
    'legionella' => 'legionella',
    'asbestos' => 'asbestos',
    'management' => 'health_safety',
    'risk' => 'health_safety',
    'people' => 'health_safety',
    'incidents' => 'health_safety',
    'workplace' => 'health_safety',
    'specialist' => 'health_safety',
];
```

Pillar roll-up uses the same precedence `priority > review > addressed > not_assessed`.

Web report sections appear in this client-first order:
1. executive summary/pillars;
2. what appears to be working;
3. things worth checking;
4. priority actions;
5. action summary;
6. single low-pressure support CTA.

Do not print internal opportunity tags in the customer report.

- [ ] **Step 4: Run unit/integration/E2E report tests**

Run: `composer test:unit -- --filter ReportDataBuilderTest`
Run: `composer test:integration -- --filter ReportAccessTest`
Run: `npm run test:e2e -- report.spec.ts`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Reports templates/report-web.php src/Plugin.php tests
git commit -m "feat: add canonical secure healthcheck report"
```

---

### Task 13: Generate branded PDFs from the canonical report data

**Files:**
- Modify: `composer.json`
- Create: `src/Reports/PdfGenerator.php`
- Create: `templates/report-pdf.php`
- Modify: `src/Reports/ReportController.php`
- Test: `tests/integration/PdfGeneratorTest.php`

**Interfaces:**
- Consumes: `ReportDataBuilder::build()` output only.
- Produces: `PdfGenerator::toTempFile(array $reportData): string`
- Produces: `PdfGenerator::stream(array $reportData, string $filename): void`
- Uses Dompdf locally; no external PDF API.

- [ ] **Step 1: Add Dompdf and write a failing PDF test**

Add Composer dependency: `dompdf/dompdf:^3.1`.

```php
public function test_pdf_is_created_from_same_report_data(): void
{
    $data = $this->reportBuilder->build($this->completedAssessmentId);
    $path = $this->pdf->toTempFile($data);

    self::assertFileExists($path);
    self::assertGreaterThan(10000, filesize($path));
    self::assertSame('%PDF', file_get_contents($path, false, null, 0, 4));

    unlink($path);
}
```

- [ ] **Step 2: Run and verify failure**

Run: `composer update dompdf/dompdf && composer test:integration -- --filter PdfGeneratorTest`
Expected: FAIL because `PdfGenerator`/template does not exist.

- [ ] **Step 3: Implement the PDF template and generator**

PDF requirements:
- cover: Acorn Safety Services, report title, four-pillar subtitle, company, date, `Self-assessment report`;
- executive summary and pillar statuses;
- positive findings first;
- review findings;
- priority findings;
- action summary table;
- final support CTA;
- disclaimer and page footer;
- selectable text and clickable links;
- no screenshots/canvas rendering;
- use a temporary file created via `wp_tempnam()` for mail attachment and delete it after use;
- direct PDF downloads render on demand and are never written to a predictable public uploads URL.

If rendering fails, set `pdf_status=failed`, save a concise error in `pdf_last_error`, and leave the browser report fully usable.

- [ ] **Step 4: Run PDF test and manually inspect one representative PDF**

Run: `composer test:integration -- --filter PdfGeneratorTest`
Expected: PASS.

Manual check using a seeded completed assessment: confirm page breaks, long recommendations, links, footer, and no clipped text.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock src/Reports/PdfGenerator.php src/Reports/ReportController.php templates/report-pdf.php tests/integration/PdfGeneratorTest.php
git commit -m "feat: generate branded healthcheck PDFs"
```

---

### Task 14: Send customer reports and actionable internal notifications without blocking completion

**Files:**
- Create: `src/Notifications/CustomerMailer.php`
- Create: `src/Notifications/InternalMailer.php`
- Create: `templates/email-customer.php`
- Create: `templates/email-internal.php`
- Modify: `src/Assessment/CompletionService.php`
- Modify: `src/Rest/CompletionController.php`
- Test: `tests/integration/MailerTest.php`

**Interfaces:**
- Produces: `CustomerMailer::send(int $assessmentId): bool`
- Produces: `InternalMailer::send(int $assessmentId): bool`
- Produces: resend endpoint `POST /reports/{report_token}/resend`
- Uses `wp_mail()` and the site's existing mail transport/SMTP configuration.

- [ ] **Step 1: Write failing mail tests using the `pre_wp_mail` filter**

Assert customer email contains:
- recipient email from contact;
- subject default `Your Acorn Health & Safety Healthcheck`;
- priority/review headline counts;
- secure report URL;
- generated PDF attachment when PDF succeeds;
- no requirement for marketing consent.

Assert internal email contains:
- company, sector, employee band, postcode if captured;
- priority/review findings;
- internal service opportunity tags;
- audit request status;
- contact details;
- admin assessment URL.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter MailerTest`
Expected: FAIL.

- [ ] **Step 3: Implement best-effort sending and resend throttling**

Completion sequence after persistence:
1. try PDF temp generation;
2. send customer email with PDF if available, otherwise send without attachment and link to web report;
3. delete temp PDF;
4. send internal notification;
5. store `email_status` as `sent`, `partial`, or `failed`; store a short JSON string in `email_last_error` with `customer` and/or `internal` error messages.

Resend endpoint limit: 3 sends per report token per hour. A resend failure returns a clear user message but does not invalidate the report.

- [ ] **Step 4: Run mail tests**

Run: `composer test:integration -- --filter MailerTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Notifications templates/email-* src/Assessment/CompletionService.php src/Rest/CompletionController.php tests/integration/MailerTest.php
git commit -m "feat: send healthcheck reports and lead alerts"
```

---

### Task 15: Build the WordPress assessment admin, dashboard analytics, CSV export, and resend/download actions

**Files:**
- Create: `src/Admin/Menu.php`
- Create: `src/Admin/DashboardPage.php`
- Create: `src/Admin/AssessmentsPage.php`
- Create: `assets/css/admin.css`
- Modify: `src/Plugin.php`
- Test: `tests/integration/AdminCapabilityTest.php`
- Test: `tests/integration/AdminAnalyticsTest.php`

**Interfaces:**
- Produces custom capability: `manage_acorn_healthcheck` assigned to administrators on activation.
- Produces admin pages: Healthcheck Dashboard and Assessments.
- Produces CSV export endpoint protected by capability + nonce.

- [ ] **Step 1: Write failing capability and analytics tests**

Test:
- administrator with `manage_acorn_healthcheck` can view dashboard/list/detail;
- subscriber receives `wp_die`/403;
- dashboard counts starts/completions/report captures/audit requests from database state;
- common-findings report counts `review` + `priority` answer rows by question;
- service opportunity counts come from assessment `service_tags_json` only;
- CSV export contains no raw token hashes or snapshot internals.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter 'AdminCapabilityTest|AdminAnalyticsTest'`
Expected: FAIL.

- [ ] **Step 3: Implement dashboard and assessment screens with server-side pagination**

Dashboard widgets:
- started;
- assessed/completed;
- report captures;
- audit requests;
- conversion percentages;
- common findings;
- Health & Safety / Fire / Legionella / Asbestos opportunity counts.

Assessment list filters:
- date range;
- company;
- overall result;
- audit requested;
- jurisdiction;
- sector;
- opportunity tag.

Assessment detail tabs:
- Overview;
- Answers;
- Recommendations;
- Contact;
- Report.

Actions:
- secure PDF download generated on demand;
- resend customer report;
- CSV export from filtered list.

Escape all admin output and use `$wpdb->prepare()` for filters.

- [ ] **Step 4: Run admin tests**

Run: `composer test:integration -- --filter 'AdminCapabilityTest|AdminAnalyticsTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Admin assets/css/admin.css src/Plugin.php tests/integration/AdminCapabilityTest.php tests/integration/AdminAnalyticsTest.php
git commit -m "feat: add healthcheck admin and analytics"
```

---

### Task 16: Add editable versioned content, technical-review controls, and plugin settings

**Files:**
- Create: `src/Content/ContentPublisher.php`
- Create: `src/Admin/ContentPage.php`
- Create: `src/Admin/ReviewPage.php`
- Create: `src/Admin/SettingsPage.php`
- Create: `config/settings-defaults.php`
- Modify: `src/Plugin.php`
- Test: `tests/integration/ContentVersioningTest.php`
- Test: `tests/integration/SettingsTest.php`

**Interfaces:**
- Produces: `ContentPublisher::createDraftFromPublished(int $userId): int`
- Produces: `ContentPublisher::publish(int $draftVersionId, int $userId): int`
- Produces: option `acorn_hc_settings`
- Published content is immutable through admin; edits occur only in a draft clone.

- [ ] **Step 1: Write failing content-versioning tests**

Test:
- V1 `1.0` remains published after activation;
- clicking/editing live content first clones it into a draft `1.1`;
- changing draft question/recommendation leaves assessments bound to `1.0` untouched;
- publish fails if any active question lacks Partly/No/Not sure recommendation rows;
- publish fails if source metadata, reviewer, last-reviewed date, or next-review date is missing;
- successful publish retires `1.0`, publishes `1.1`, and new assessments bind to `1.1`;
- old completed reports remain byte-for-byte equivalent in canonical report data except runtime support CTA URL if settings are intentionally live.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter 'ContentVersioningTest|SettingsTest'`
Expected: FAIL.

- [ ] **Step 3: Implement admin content/settings workflow**

Content editor fields:
- question text;
- help text;
- module;
- applicability controls chosen from supported operators only;
- answer statuses;
- recommendation fields for Partly/No/Not sure;
- jurisdiction variants;
- source URL/title;
- service tags (internal only);
- sort rank;
- reviewer;
- last reviewed;
- next review.

Content Review page shows overdue/due-soon items and blocks publishing until required review metadata is complete.

Settings defaults:

```php
return [
    'report_logo_attachment_id' => 0,
    'report_contact_phone' => '01604 930380',
    'report_website' => 'https://acornhealthandsafety.co.uk/',
    'customer_email_subject' => 'Your Acorn Health & Safety Healthcheck',
    'internal_recipient' => get_option('admin_email'),
    'audit_cta_url' => 'https://acornhealthandsafety.co.uk/health-and-safety-compliance-audit/',
    'privacy_policy_url' => get_privacy_policy_url(),
    'completed_retention_days' => 730,
    'incomplete_retention_days' => 7,
    'pdf_footer' => 'Acorn Safety Services | Health & Safety Healthcheck',
];
```

`730` days is a configurable two-year default chosen for data minimisation and practical report access, not a claim that this period is legally required. Acorn should align the final value with its published privacy/retention policy before launch.

- [ ] **Step 4: Run content/settings tests**

Run: `composer test:integration -- --filter 'ContentVersioningTest|SettingsTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Content/ContentPublisher.php src/Admin config/settings-defaults.php src/Plugin.php tests/integration/ContentVersioningTest.php tests/integration/SettingsTest.php
git commit -m "feat: add versioned healthcheck content management"
```

---

### Task 17: Add retention cleanup, WordPress privacy hooks, and final abuse/security hardening

**Files:**
- Create: `src/Privacy/Retention.php`
- Create: `src/Privacy/ExportEraser.php`
- Modify: `src/Activation.php`
- Modify: `src/Plugin.php`
- Test: `tests/integration/RetentionTest.php`
- Test: `tests/integration/PrivacyTest.php`
- Test: `tests/integration/SecurityRegressionTest.php`

**Interfaces:**
- Produces cron hook: `acorn_hc_daily_cleanup`
- Produces WordPress personal-data exporter and eraser for Healthcheck contact data.
- Erasure revokes report access and removes PII while preserving non-personal aggregate assessment findings where lawful/configured.

- [ ] **Step 1: Write failing retention/privacy/security tests**

Retention:
- in-progress assessments older than configured 7 days are deleted with answers;
- recent incomplete assessments remain;
- completed assessments older than configured completed retention are deleted/anonymised according to setting.

Privacy:
- exporter finds records by email and exports contact fields plus assessment date/result summary;
- eraser blanks first name, last name, email, telephone, postcode, replaces customer/company display data in snapshot with `Removed following privacy request`, and clears `report_token_hash` so old report links stop working;
- aggregated counts remain usable without PII.

Security regression:
- invalid question IDs/answers rejected;
- HTML/script in contact/company input is stored sanitised and rendered escaped;
- SQL-injection strings do not alter queries;
- raw token hashes never appear in REST/admin CSV/browser HTML;
- one report token cannot retrieve another report;
- admin actions require both capability and nonce.

- [ ] **Step 2: Run and verify failure**

Run: `composer test:integration -- --filter 'RetentionTest|PrivacyTest|SecurityRegressionTest'`
Expected: FAIL.

- [ ] **Step 3: Implement daily cleanup, privacy registration, and security fixes**

Schedule cleanup on activation if not already scheduled; unschedule it on uninstall only if an uninstall routine is later added. Register suggested privacy-policy text with `wp_add_privacy_policy_content()` describing the assessment/contact/report purpose in plain English.

Do not log full contact payloads, raw tokens, or snapshot JSON to PHP error logs.

- [ ] **Step 4: Run retention/privacy/security tests**

Run: `composer test:integration -- --filter 'RetentionTest|PrivacyTest|SecurityRegressionTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Privacy src/Activation.php src/Plugin.php tests/integration/RetentionTest.php tests/integration/PrivacyTest.php tests/integration/SecurityRegressionTest.php
git commit -m "feat: add healthcheck privacy and retention controls"
```

---

### Task 18: Complete accessibility, cross-output consistency, and failure-recovery browser tests

**Files:**
- Create: `tests/e2e/accessibility.spec.ts`
- Create: `tests/e2e/failure-recovery.spec.ts`
- Create: `tests/integration/ReportConsistencyTest.php`
- Modify: public templates/assets only where tests expose defects

**Interfaces:**
- Consumes the complete plugin.
- Produces no new public API; this task hardens acceptance behaviour.

- [ ] **Step 1: Write/complete failing acceptance tests**

Accessibility with axe-core:
- landing;
- profile;
- question screen;
- headline result/contact gate;
- full report.

Keyboard tests:
- tab order reaches answer radios/help/back/continue sensibly;
- radio options can be selected by keyboard;
- focus moves to the next question heading after save;
- validation errors receive focus and are announced.

Failure recovery:
- mock customer mail failure: full report still unlocks and admin shows failure;
- mock PDF failure: web report remains available and email sends without attachment if mail works;
- resend succeeds later;
- network interruption/recovery path remains intact.

Consistency:

```php
public function test_all_outputs_use_same_canonical_counts_and_findings(): void
{
    $data = $this->builder->build($this->completedAssessmentId);
    self::assertSame($data['summary'], $this->extractSummaryFromAdmin($this->completedAssessmentId));
    self::assertStringContainsString((string) $data['summary']['priority_count'], $this->renderCustomerEmail($data));
    self::assertStringContainsString($data['priority'][0]['heading'], $this->renderPdfHtml($data));
}
```

- [ ] **Step 2: Run the full acceptance subset and record failures**

Run: `npm run test:a11y`
Run: `npm run test:e2e -- failure-recovery.spec.ts`
Run: `composer test:integration -- --filter ReportConsistencyTest`
Expected initially: any remaining defects become explicit failures.

- [ ] **Step 3: Fix only defects exposed by these tests**

Do not add new product features. Typical fixes here are focus handling, missing labels, colour contrast, retry-state copy, or output divergence.

- [ ] **Step 4: Re-run the full PHP and browser suites**

Run: `composer test`
Run: `npm run test:e2e`
Run: `npm run test:a11y`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add tests assets templates src
git commit -m "test: harden healthcheck accessibility and recovery"
```

---

### Task 19: Technical content sign-off, launch configuration, documentation, and installable ZIP

**Files:**
- Create: `README.md`
- Create: `bin/build-zip.sh`
- Create: `docs/release-checklist.md`
- Modify: content rows only if Acorn technical review requests wording/source corrections before publish

**Interfaces:**
- Produces: `dist/acorn-safety-healthcheck-1.0.0.zip`
- Produces: release checklist proving all 24 approved acceptance criteria are met.

- [ ] **Step 1: Write the release checklist before the final verification run**

`docs/release-checklist.md` must include explicit checks for every acceptance criterion from the design spec, plus:
- Health & Safety content reviewed by an Acorn competent H&S reviewer;
- Fire content reviewed against England, Wales, Scotland, and Northern Ireland source metadata;
- Legionella content reviewed for GB and Northern Ireland;
- Asbestos content reviewed for GB and Northern Ireland;
- privacy/retention setting approved;
- internal notification email approved;
- report logo/contact/CTA settings populated;
- no sales copy appears during the assessment;
- representative completions remain within the target question count and 4–6 minute range.

- [ ] **Step 2: Run all automated verification**

Run:

```bash
composer validate --strict
composer test
npm run test:e2e
npm run test:a11y
```

Expected: all commands PASS with zero failing tests.

- [ ] **Step 3: Perform four manual reference journeys**

Journey A — small England office, 1–4 staff, DSE, leased/shared office, no water/building-maintenance responsibility.
Expected: concise H&S + Fire/DSE path; no Legionella/Asbestos; no written-policy penalty based solely on under-five status.

Journey B — 10–49 employee manufacturer, England, COSHH/manual handling/work-at-height/machinery, own premises, water responsibility, pre-2000 building.
Expected: more complex path but roughly <=30 scored questions after conditional suppression; all four pillars potentially assessed.

Journey C — Scotland property/FM business in shared premises, water + maintenance responsibility, unknown building age.
Expected: Scotland fire source/copy, Legionella and Asbestos uncertainty handled as practical review actions rather than immediate sales prompts.

Journey D — Northern Ireland business with premises/water/asbestos responsibilities.
Expected: NIFRS/HSENI metadata, NI-specific fire/Legionella/asbestos content, no GB-only legal wording presented as NI law.

For all journeys, compare browser report, PDF, customer email counts, and admin counts.

- [ ] **Step 4: Build the production ZIP**

`bin/build-zip.sh` must:
1. run `composer install --no-dev --optimize-autoloader` in a clean build directory;
2. copy plugin PHP, `vendor/`, `assets/`, `config/`, and `templates/`;
3. exclude `.git`, `.wp-env.json`, tests, `node_modules`, docs, source maps, and local temp files;
4. create `dist/acorn-safety-healthcheck-1.0.0.zip` with the top-level folder `acorn-safety-healthcheck/`.

Run: `bash bin/build-zip.sh`
Expected: ZIP exists and can be installed through WordPress Plugins > Add New > Upload Plugin.

- [ ] **Step 5: Install the ZIP into a clean wp-env instance and repeat smoke acceptance**

Run a clean environment, install/activate the ZIP, create a page containing `[acorn_safety_healthcheck]`, set its slug to `/health-and-safety-healthcheck/`, and complete one end-to-end assessment including report email/PDF generation.

Expected: no PHP warnings/notices; no missing vendor assets; plugin activates cleanly; report and admin work.

- [ ] **Step 6: Commit release artefacts/documentation, but do not commit the distributable ZIP unless the project intentionally tracks binaries**

```bash
git add README.md bin/build-zip.sh docs/release-checklist.md
git commit -m "docs: prepare Healthcheck v1 release"
```

---

## Appendix A — Exact V1 recommendation bases

The seed builder uses the answer-specific wrappers below around each question's base content. This avoids inconsistent hand-written variants while keeping the result specific and useful.

### Answer wrappers

For each question define `subject`, `next_step`, `why`, and `good_looks` from the table below.

- `partly` identified text: `Your answer indicates that {subject} is only partly in place or may not fully reflect the organisation's current circumstances.`
- `no` identified text: `Your answer indicates that {subject} may not currently be in place.`
- `not_sure` identified text: `You were not sure whether {subject} is currently in place.`
- `partly` next step: use the table's `next_step` unchanged.
- `no` next step: use the table's `next_step` unchanged.
- `not_sure` next step: `Confirm the current position and who is responsible. Then consider this next step: ` + the table's `next_step`.
- `yes`: no recommendation row. Positive finding text is `ucfirst(subject) + ' appears to be addressed based on your answer.'`.
- recommendation heading: `ucfirst(subject)`.

The wrappers are a build-time content-generation rule used by `SeedContent`; the generated final strings are stored in the versioned recommendation rows and therefore frozen in completed snapshots.

| Key | Subject | Next step | Why it matters | What good looks like |
|---|---|---|---|---|
| M01 | competent health and safety assistance | Confirm who provides competent health and safety assistance and that they have suitable skills, knowledge, experience and resources for the organisation. | Competent assistance helps the organisation identify what it needs to do and maintain suitable arrangements. | The organisation knows who provides competent assistance, their responsibilities are clear, and their competence/resources are appropriate to the work. |
| M02 | clear health and safety management arrangements | Review the organisation's health and safety policy/arrangements, define responsibilities and arrangements clearly, and ensure the written-policy requirement is met where applicable. | A clear policy and arrangements explain how health and safety is organised and put into practice. | The organisation's approach, responsibilities and practical arrangements are clear, current and documented where required. |
| M03 | clear allocation of health and safety responsibilities | Define who owns the key health and safety responsibilities and make sure the people involved understand what is expected of them. | Unclear ownership can leave important controls or follow-up actions unmanaged. | Named people understand their responsibilities, have authority to act, and know how their duties fit together. |
| M04 | employee consultation on health and safety | Put in place a practical way to consult workers about risks, controls and changes that may affect their health and safety. | Workers often understand day-to-day risks and should have a way to contribute to decisions that affect them. | Workers know how consultation happens and have meaningful opportunities to raise concerns and influence relevant controls. |
| R01 | suitable assessment and control of significant workplace risks | Review the significant hazards associated with the work, who may be affected, the controls already in place and any additional measures needed. | Risk assessment provides the basis for deciding what controls are needed to prevent harm. | Significant hazards are understood, proportionate controls are in place, and the assessment reflects the actual work being done. |
| R02 | tracking and review of risk-assessment actions | Assign outstanding actions to named owners, track completion, and review assessments when relevant work, people, equipment or circumstances change. | Assessments only improve safety when identified actions are implemented and the assessment remains current. | Actions have owners and status, and reviews take place after significant change, incidents or evidence that controls may no longer be effective. |
| R03 | consideration of people who may need additional protection | Check whether any workers need additional consideration because of age, experience, health, pregnancy, disability or other relevant circumstances, and adjust controls where appropriate. | The same workplace risk can affect different people differently. | Relevant individual or group needs are considered without assumptions, and controls/supervision are adapted where necessary. |
| T01 | a suitable health and safety induction for new starters | Create or review a consistent induction covering the person's role, workplace hazards, emergency arrangements, reporting routes and required controls. | New starters may be unfamiliar with the workplace and how risks are managed. | New starters receive relevant information before or as they begin work, with completion recorded where appropriate. |
| T02 | suitable information, instruction, training and supervision | Review the competence needed for each role and provide appropriate information, instruction, training and supervision where gaps exist. | People need to understand the risks and controls relevant to the work they actually perform. | Workers are competent for their tasks or appropriately supervised while gaining competence, and know the controls they are expected to follow. |
| T03 | training records and refresher review | Maintain a simple training record/matrix and review refresher needs when competence may have changed or when work, equipment, guidance or risk changes. | Records help the organisation understand who has been trained and where gaps may exist. | Relevant training is recorded, current and linked to role/risk rather than repeated solely because a fixed date has arrived. |
| A01 | first-aid provision based on the organisation's needs | Review first-aid needs based on the workforce, work activities, premises and foreseeable circumstances, then provide suitable equipment, facilities and people. | First-aid arrangements should be proportionate to the actual workplace and workforce rather than a one-size-fits-all rule. | A current needs assessment supports suitable kits/facilities and enough appropriately trained or appointed people for the circumstances. |
| A02 | recording and learning from accidents and near misses | Make it straightforward to record relevant accidents and near misses, review what happened and identify proportionate corrective actions where needed. | Incident learning can reveal weak controls before similar events cause greater harm. | Relevant events are recorded consistently, investigated in proportion to risk, and resulting actions are followed through. |
| A03 | a clear process for identifying and making RIDDOR reports | Confirm who decides whether an event is reportable, which jurisdictional reporting rules apply, and how required reports/records are made and retained. | Only specified events are reportable, so the organisation needs a clear route for recognising and handling them correctly. | Responsible people know the reporting criteria, who submits reports, and where supporting records are kept. |
| W01 | suitable workplace welfare and general conditions | Review toilets/washing, drinking water, rest/eating arrangements, housekeeping, access, lighting, ventilation and temperature as relevant to the workplace. | Basic welfare and workplace conditions directly affect health, safety and day-to-day wellbeing. | Facilities and workplace conditions are suitable for the people and activities present and are maintained in a usable condition. |
| W02 | safe provision, maintenance and inspection of work equipment | Identify equipment that requires maintenance or inspection, set suitable arrangements, and address overdue or defective items. | Equipment can become unsafe through damage, deterioration, unsuitable use or inadequate maintenance. | Equipment is suitable for its task, maintained in safe condition and inspected where the risk or legal framework requires it. |
| F01 | clear fire-safety responsibility and coordination | Confirm who holds the relevant fire-safety duties for the premises and, where control is shared, how responsible parties cooperate and coordinate arrangements. | Fire precautions can fail where different parties assume someone else owns the responsibility. | Relevant dutyholders understand the parts within their control, share necessary information and coordinate measures for the premises. |
| F02 | a suitable current fire risk assessment | Confirm that a suitable fire risk assessment exists for the premises/activities within the organisation's responsibility and arrange or review one where needed. | The fire risk assessment is the basis for deciding what fire precautions and emergency arrangements are needed. | The assessment reflects the premises, people and activities, is appropriately recorded for the jurisdiction, and is reviewed when necessary. |
| F03 | management of actions arising from the fire risk assessment | Review outstanding fire-safety actions, assign ownership and track them to appropriate completion or documented management. | Identified fire precautions only reduce risk when the resulting actions are implemented. | Fire actions have owners, priorities and evidence of completion or justified ongoing management. |
| F04 | suitable fire emergency arrangements and worker information/training | Review emergency procedures, evacuation arrangements and relevant worker information/training, including arrangements for people who may need assistance. | People need to know what to do if a fire occurs and the arrangements must work for the actual occupants and premises. | Evacuation arrangements are practical, communicated and rehearsed/reviewed as appropriate, with relevant assistance needs considered. |
| F05 | inspection and maintenance of relevant fire-safety measures | Confirm which fire-safety systems/measures require checks or maintenance and ensure those arrangements and records are current. | Detection, warning, lighting, firefighting and other precautions need to remain effective when required. | Required systems and measures are appropriately tested/maintained, defects are acted on, and relevant records are available. |
| L01 | a suitable assessment of Legionella risk where relevant water systems are controlled | Confirm the water systems within the organisation's responsibility and arrange or review a suitable Legionella risk assessment by someone competent where a foreseeable risk exists. | Dutyholders need to understand whether their water systems present a foreseeable risk and what proportionate controls are needed. | The systems and responsibilities are understood, the risk has been assessed, and the assessment is reviewed when relevant circumstances change. |
| L02 | clear responsibility and implementation of required Legionella controls | Confirm who is responsible for managing any identified Legionella risk and ensure the control measures required by the assessment are being carried out. | A risk assessment is only effective if its required control measures are actually managed. | A responsible person is clear, required controls are documented and carried out, and failures trigger corrective action. |
| L03 | appropriate Legionella monitoring, maintenance and records where required | Review the control scheme and ensure any monitoring, inspection, maintenance and record keeping required by the assessment are current. | Not every system needs elaborate monitoring, but controls identified as necessary need evidence that they are operating effectively. | The level of monitoring matches the assessed risk, relevant checks are current, and results/actions are recorded where required. |
| AS01 | reliable information about asbestos presence or presumed presence in relevant pre-2000/uncertain premises | Establish what reliable asbestos information exists for the areas within your maintenance/repair responsibility and presume suspect materials contain asbestos where appropriate until evidence shows otherwise. | People carrying out maintenance or other work need reliable information so asbestos-containing materials are not accidentally disturbed. | The dutyholder knows what information supports the asbestos position, where known/presumed ACMs are located and what condition they are in. |
| AS02 | an up-to-date asbestos record/register and management plan where asbestos is present or presumed | Review the asbestos record/register and management plan so they reflect current information, responsibilities, priorities and required actions. | The duty to manage is about knowing where asbestos is and having a practical plan to prevent exposure. | The record is current, risks are assessed, actions/responsibilities are clear and the management plan is being implemented. |
| AS03 | ongoing review of asbestos condition and provision of information before disturbance | Review how known/presumed ACMs are monitored and how location/condition information is given to employees, contractors or others before work that could disturb them. | Asbestos that is safely managed and left undisturbed may present low risk, but accidental disturbance can release fibres. | Condition is monitored at a suitable frequency and reliable information reaches anyone who may disturb affected materials before work begins. |
| AS04 | suitable asbestos information before refurbishment, intrusive maintenance or demolition | Before intrusive work starts, confirm that the asbestos information is suitable for the exact areas and scope that could be disturbed and obtain an appropriate survey/investigation where existing information is insufficient. | Management information alone may not identify concealed asbestos that intrusive work could disturb. | The project team has suitable, sufficiently intrusive asbestos information for the planned scope before the building fabric is disturbed. |
| C01 | suitable assessment and control of hazardous substances | Identify hazardous substances used or generated, assess the exposure risk and review whether elimination/substitution, engineering controls, procedures and PPE are adequate. | Hazardous substances can cause immediate or long-term harm if exposure is not properly controlled. | Relevant substances/processes are identified, controls follow the hierarchy of control, and exposure controls are checked/reviewed. |
| D01 | suitable DSE workstation assessment and follow-up | Identify regular DSE users, complete suitable workstation assessments and address issues relating to layout, posture, equipment, breaks/changes of activity and relevant user needs. | Poorly arranged or prolonged display-screen work can contribute to musculoskeletal discomfort and other problems. | Regular DSE users have suitable setups, know how to adjust them, and reported problems/actions are addressed. |
| MH01 | proportionate control of hazardous manual handling | Identify significant manual-handling tasks, avoid hazardous handling where reasonably practicable and assess/control the remaining risk. | Lifting, carrying, pushing or pulling can cause musculoskeletal injury when demands exceed people's capability or controls are poor. | High-risk handling is avoided or reduced, remaining tasks are designed sensibly, and workers have suitable information/equipment. |
| LW01 | suitable management of lone-working risks | Review tasks performed alone, foreseeable emergencies, communication, supervision/check-in arrangements and how assistance would be obtained. | Lone workers can face increased consequences if something goes wrong and support is not immediately available. | Lone-working tasks are risk assessed, contact/escalation arrangements are proportionate, and workers know what to do in an emergency. |
| WAH01 | properly planned and controlled work at height | Avoid work at height where reasonably practicable; where it remains necessary, ensure it is planned, supervised as appropriate and carried out by competent people using suitable equipment. | Falls from height can cause serious or fatal injury and require effective prevention/protection. | The hierarchy for work at height is applied, equipment is suitable, competence is clear, and the work is properly planned. |
| YW01 | specific consideration of risks to workers under 18 | Review the work given to young workers and account for possible lack of experience, awareness and maturity, with suitable restrictions, training and supervision. | Young workers may be less experienced at recognising hazards and may require additional control or supervision. | Work allocated to under-18s is suitable for them, risks are specifically considered and supervision/training reflect their experience. |
| CT01 | suitable contractor selection, briefing, coordination and monitoring | Review how contractors are selected, what risk/site information is exchanged, how work is coordinated and how the organisation checks agreed controls are followed. | Contractor work can introduce unfamiliar hazards and create interface risks with the host organisation's activities. | Competence is checked proportionately, relevant hazards are shared both ways, responsibilities are clear and work is monitored appropriately. |
| DRV01 | management of occupational driving risks | Review how work-related journeys, driver competence/fitness, vehicle suitability/condition and scheduling pressures are managed. | Driving for work is a work activity and needs proportionate management of driver, vehicle and journey risks. | Journeys are planned sensibly, drivers and vehicles are suitable, and work demands do not encourage unsafe driving behaviour. |

### Internal service tags by question

These tags are never displayed during the assessment:

- `M01`–`W02`, `C01`, `D01`, `MH01`, `LW01`, `WAH01`, `YW01`, `CT01`, `DRV01` -> `health_safety`; add `training` only for `T01`, `T02`, `T03`.
- `F01`–`F05` -> `fire`.
- `L01`–`L03` -> `legionella`.
- `AS01`–`AS04` -> `asbestos`.

Service tags are created only for `review` or `priority` findings.

---

## Appendix B — Acceptance mapping

| Approved requirement | Implemented/tested in |
|---|---|
| No account required | Tasks 7–9 |
| Relevant conditional questions only | Tasks 5, 7, 9, 10 |
| 4–6 minute target | Task 19 manual journeys |
| Back navigation safe | Tasks 7, 10 |
| Profile changes invalidate hidden answers | Tasks 5, 7, 10 |
| Server-side results | Tasks 6–8 |
| No percentage/pass-fail | Tasks 3, 6, 12, 18 |
| Recommendation completeness | Tasks 4, 16 |
| Positive findings included | Tasks 12–13 |
| Meaningful result before contact gate | Tasks 9, 11 |
| Separate audit/marketing consent | Task 11 |
| Same canonical browser/PDF data | Tasks 12, 13, 18 |
| Email/internal notification | Task 14 |
| Failures do not lose assessment | Tasks 10, 13, 14, 18 |
| Admin assessment visibility | Task 15 |
| Secure report links | Tasks 11–12, 17 |
| Content version history | Tasks 4, 16 |
| Historical report reproducibility | Tasks 11, 12, 16 |
| Accessibility/keyboard | Tasks 9, 18 |
| Conditional asset loading | Task 9 |
| No Gravity Forms dependency | All tasks |
| No selling during assessment | Tasks 9, 18, 19 |
| Fire/Legionella/Asbestos proportionate | Tasks 4–5, 19 |
| Standalone client value | Tasks 4, 12–13, 19 |
| Privacy/retention | Task 17 |

## Execution Notes

- At implementation time, create an isolated working tree before Task 1 if working inside an existing repository.
- Follow TDD strictly: do not write production behaviour until the task's failing test demonstrates the requirement.
- Commit after every task using the commit message shown, unless the repository's contribution rules require a different prefix.
- Do not add AI, booking, CRM, Make/Zapier, Alpha Tracker, customer accounts, or chat in this V1 plan.
- If a hosting constraint makes PHP 8.1 or Dompdf unsuitable, stop before implementation and revise the plan rather than silently substituting architecture.

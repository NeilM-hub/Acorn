# Acorn Safety Healthcheck V1 release audit

This audit maps every approved acceptance criterion. Automated verification does **not** replace the remaining Acorn technical, privacy, branding, deliverability, usability, and client-value launch checks below.

Engineering verification reference: GitHub Actions **V1 verification run #42** on commit `e97a9ff5a57037ca8d35e73b1d7f27b823b5c714` completed successfully, including PHP 8.1, 16 unit tests / 38 assertions, 43 WordPress integration tests / 182 assertions, 11 browser tests, accessibility verification, production dependency audit, production ZIP verification, and a clean non-source-mounted ZIP smoke test.

| # | Acceptance criterion | Implementation | Automated evidence | Manual launch check | Status |
|---|---|---|---|---|---|
| 1 | Complete without an account on desktop/mobile | REST assessment lifecycle; shortcode wizard | `healthcheck.spec.ts`, `mobile.spec.ts` | Representative real-device staging journey | Implemented and automated |
| 2 | Only relevant conditional questions | Structured `RulesEngine`; versioned `applicability_json` | `RulesEngineTest`, `AssessmentLifecycleTest` | Review four reference journeys | Implemented |
| 3 | Typical completion around 4–6 minutes | Conditional 35-question library | Browser journeys | Time representative journeys | Manual timing gate outstanding |
| 4 | Back navigation does not corrupt results | Wizard Back and server state | `backtracking.spec.ts` | Real-device spot check | Implemented and automated |
| 5 | Profile changes invalidate hidden answers | `AssessmentService::updateProfile()` | lifecycle and backtracking tests | Reference journey | Implemented |
| 6 | Results calculated server-side | `ResultsEngine`, assessment REST service | unit/integration REST tests | Inspect representative staging request | Implemented |
| 7 | No percentage/pass/fail claim | Status value objects and report templates | domain/report tests | Editorial review | Implemented |
| 8 | Every Priority/Review has approved recommendation content | seed completeness/publish validation | seed/versioning tests | Acorn technical sign-off | Sign-off outstanding |
| 9 | Positive findings in report | immutable snapshot/report builder | completion/report tests | Review sample report | Implemented |
| 10 | Full recommendations gated after useful headline | wizard headline/contact stages | full E2E journeys | UX review | Implemented and automated |
| 11 | Audit request and marketing consent separate | contact UI and completion validation | completion tests | Consent-copy approval | Implemented |
| 12 | Browser and PDF share canonical data | `ReportDataBuilder` | consistency/PDF tests | Compare representative output | Implemented |
| 13 | Customer email and internal notification | completion mailers | mailer tests | Deliverability/configuration check | Implemented |
| 14 | Email/PDF failure retains assessment | best-effort completion flow | release-hardening failure tests | Optional staging SMTP failure exercise | Implemented and automated |
| 15 | Completed assessments in admin | filtered/paginated list, canonical detail, CSV, report actions | admin/integration tests | Admin usability review | Implemented |
| 16 | Secure non-predictable report links | random tokens; hashes at rest | security/report tests | Optional penetration review | Implemented |
| 17 | Questions/recommendations have version history | version tables, full draft editor, review/publish workflow | content-versioning/release-hardening tests | Editorial workflow review | Implemented |
| 18 | Historical reports reproducible | immutable completion snapshot | historical publish regression test | Representative staging comparison | Implemented and automated |
| 19 | Accessibility and keyboard operation | native controls/focus management | axe/keyboard E2E | Assistive-technology/real-device review | Automated checks passed |
| 20 | Assets only on Healthcheck/report pages | conditional enqueue/report controller | E2E asset assertion | Inspect production page | Implemented |
| 21 | Independent of Gravity Forms | standalone REST/plugin architecture | smoke/E2E tests | Plugin inventory check | Implemented |
| 22 | No service-selling during assessment | wizard content | E2E/editorial check | Acorn UX review | Implemented |
| 23 | Fire/Legionella/Asbestos conditional/proportionate | structured applicability and curated copy | rules/source tests | Acorn technical review | Engineering complete; sign-off outstanding |
| 24 | Standalone client value without contacting Acorn | headline plus practical report | full journey/report tests | Client-value review | Engineering complete; manual validation outstanding |

## Outstanding human launch gates

- [ ] Health & Safety content signed off by a named Acorn competent H&S reviewer.
- [ ] Fire content and sources reviewed for England, Wales, Scotland, and Northern Ireland.
- [ ] Legionella and Asbestos content reviewed for Great Britain and Northern Ireland.
- [ ] Privacy policy and completed/incomplete retention settings approved.
- [ ] Internal notification recipients and customer/internal email content approved and deliverability checked.
- [ ] Report logo, contact phone, website, privacy link, PDF footer, and CTA populated and approved.
- [ ] Four representative journeys completed, timed, and compared across browser/PDF/email/admin.
- [ ] Final client-value/UX review completed on staging.

## Engineering verification/remediation

- [x] Committed verified `composer.lock` and `package-lock.json`; CI uses `npm ci`.
- [x] Full draft question/recommendation editor and review/publish controls implemented and integration-tested.
- [x] Automated customer/internal/PDF failure-state and resend-attachment assertions added, including rollback protection so a failed admin resend does not invalidate the customer’s existing secure report link.
- [x] Customer-facing report resend control implemented and browser-tested.
- [x] Mobile browser completion journey added.
- [x] Clean, non-source-mounted WordPress install/activation/completion/report smoke test added for the production ZIP.
- [x] PHP 8.1, unit, integration, Playwright, axe, ZIP build/verification, dependency audit, and clean-install smoke test passed from one commit.
- [x] Exact tested `acorn-safety-healthcheck-1.0.0.zip` is uploaded by CI as the release artifact.

## Release decision

The engineering build is a **release candidate for staging**, not yet a live-production approval. Production launch remains blocked only by the human launch gates above; the seed content deliberately remains marked as pending Acorn technical sign-off until a named reviewer records that approval.

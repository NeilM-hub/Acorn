# Acorn Safety Healthcheck V1 release audit

This audit maps every approved acceptance criterion. A checked implementation/test does **not** replace the manual launch gates below.

| # | Acceptance criterion | Implementation | Automated evidence | Manual launch check | Status |
|---|---|---|---|---|---|
| 1 | Complete without an account on desktop/mobile | REST assessment lifecycle; shortcode wizard | `healthcheck.spec.ts` | Complete desktop/mobile journeys | Implemented; browser verification pending |
| 2 | Only relevant conditional questions | Structured `RulesEngine`; versioned `applicability_json` | `RulesEngineTest`, `AssessmentLifecycleTest` | Review four reference journeys | Implemented |
| 3 | Typical completion around 4–6 minutes | Conditional 35-question library | Browser journey | Time representative journeys | Manual gate outstanding |
| 4 | Back navigation does not corrupt results | Wizard Back and server state | `backtracking.spec.ts` | Mobile keyboard check | Implemented; browser verification pending |
| 5 | Profile changes invalidate hidden answers | `AssessmentService::updateProfile()` | lifecycle and backtracking tests | Reference journey | Implemented |
| 6 | Results calculated server-side | `ResultsEngine`, assessment REST service | unit/integration REST tests | Inspect network payloads | Implemented |
| 7 | No percentage/pass/fail claim | Status value objects and report templates | domain/report tests | Editorial review | Implemented |
| 8 | Every Priority/Review has approved recommendation content | seed completeness/publish validation | seed/versioning tests | Acorn technical sign-off | Sign-off outstanding |
| 9 | Positive findings in report | immutable snapshot/report builder | completion/report tests | Review sample report | Implemented |
| 10 | Full recommendations gated after useful headline | wizard headline/contact stages | full E2E journey | UX review | Implemented; browser verification pending |
| 11 | Audit request and marketing consent separate | contact UI and completion validation | completion tests | Consent-copy approval | Implemented |
| 12 | Browser and PDF share canonical data | `ReportDataBuilder` | consistency/PDF tests | Compare representative output | Implemented |
| 13 | Customer email and internal notification | completion mailers | mailer tests | Deliverability/configuration check | Implemented |
| 14 | Email/PDF failure retains assessment | best-effort completion flow | failure recovery tests | SMTP failure exercise | Partially automated; final verification pending |
| 15 | Completed assessments in admin | assessments list/detail | admin tests | Admin usability review | Partially implemented; filter/editor audit outstanding |
| 16 | Secure non-predictable report links | random tokens; hashes at rest | security/report tests | Penetration review | Implemented |
| 17 | Questions/recommendations have version history | version tables/draft publisher | versioning tests | Editorial workflow review | Backend implemented; full editor UI incomplete |
| 18 | Historical reports reproducible | immutable completion snapshot | completion/versioning tests | Compare old report after publish | Implemented; expanded proof pending |
| 19 | Accessibility and keyboard operation | native controls/focus management | axe/keyboard E2E | Assistive-technology review | Automated run pending |
| 20 | Assets only on Healthcheck/report pages | conditional enqueue/report controller | E2E asset assertion | Inspect production page | Implemented |
| 21 | Independent of Gravity Forms | standalone REST/plugin architecture | smoke/E2E tests | Plugin inventory check | Implemented |
| 22 | No service-selling during assessment | wizard content | E2E/editorial check | Acorn UX review | Implemented |
| 23 | Fire/Legionella/Asbestos conditional/proportionate | structured applicability and curated copy | rules/source tests | Acorn technical review | Sign-off outstanding |
| 24 | Standalone client value without contacting Acorn | headline plus practical report | full journey/report tests | Client-value review | Manual validation outstanding |

## Outstanding human launch gates

- [ ] Health & Safety content signed off by a named Acorn competent H&S reviewer.
- [ ] Fire content and sources reviewed for England, Wales, Scotland, and Northern Ireland.
- [ ] Legionella and Asbestos content reviewed for Great Britain and Northern Ireland.
- [ ] Privacy policy and completed/incomplete retention settings approved.
- [ ] Internal notification recipients and email content approved.
- [ ] Report logo, contact phone, website, privacy link, PDF footer, and CTA populated and approved.
- [ ] Four representative journeys completed, timed, and compared across browser/PDF/email/admin.
- [ ] Clean production ZIP installed and smoke-tested without PHP warnings/notices/fatals.

## Outstanding engineering verification/remediation

- [ ] Generate and commit verified `composer.lock` and `package-lock.json`; switch CI Node install to `npm ci`.
- [ ] Complete and browser-test the full draft question/recommendation editor and review/publish controls.
- [ ] Add automated customer/internal/PDF failure-state and resend-attachment assertions.
- [ ] Add a clean, non-source-mounted WordPress install/activation/completion smoke test for the production ZIP.
- [ ] Run PHP 8.1, unit, integration, Playwright, axe, ZIP build, and clean-install jobs successfully from a single commit.

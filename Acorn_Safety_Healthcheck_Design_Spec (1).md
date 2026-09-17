# Acorn Safety Healthcheck — Product & Technical Design Specification

**Date:** 17 September 2026  
**Status:** Approved design, pending implementation plan  
**Product:** Acorn Safety Services — Health & Safety Healthcheck  
**Target site:** acornhealthandsafety.co.uk  
**Platform:** WordPress / GeneratePress / GenerateBlocks, delivered as a bespoke WordPress plugin

## 1. Purpose

Build a client-first online Health & Safety Healthcheck that helps UK businesses quickly understand where their current arrangements appear sound, where there may be gaps or uncertainty, and what practical next steps they should consider.

The tool must be useful even if the user never becomes an Acorn client. It must not feel like a four-service sales questionnaire. Commercial value should arise naturally from the usefulness and credibility of the assessment.

The product should preserve the spirit of Acorn's existing checklist: a practical starter guide that helps businesses spot potential issues, while making the experience significantly more tailored, actionable, and professional.

## 2. Non-negotiable product principles

1. **Client value first.** Every question must earn its place because the answer is useful to the client, not because Acorn sells a related service.
2. **No compliance percentage.** The tool must never claim that a business is a certain percentage compliant.
3. **No pass/fail claims.** Customer-facing statuses are limited to: Priority action, Review recommended, No obvious gap identified, Not assessed / Not applicable / Responsibility unclear.
4. **No AI legal decisions.** Applicability, classification, recommendations, and report content are deterministic and rules-based.
5. **Low-sales experience.** No service promotion during the assessment. Acorn support is mentioned lightly in the final report and CTA only after value has been delivered.
6. **Short and easy to complete.** Typical completion target: 4–6 minutes. Typical user sees 18–24 questions; more complex businesses may see up to roughly 25–30.
7. **Conditional relevance.** Fire, Legionella, Asbestos, COSHH, DSE, manual handling, lone working, work at height, young workers, contractors, and other specialist content appear only where relevant.
8. **Balanced reporting.** The report explicitly recognises areas that appear to be addressed, not just gaps.
9. **Professional caveat.** The tool is an indicative self-assessment based on user-supplied information; it is not a formal audit, legal advice, or confirmation of legal compliance.
10. **Historical reproducibility.** Completed assessments retain the exact question/recommendation versions used at the time.

## 3. Core service pillars

The Healthcheck remains one overall product, but the result can surface four high-value compliance areas when relevant:

- Health & Safety
- Fire Safety
- Legionella
- Asbestos

These are assessment pillars, not sales sections. Users should only see specialist questions that are relevant to their business profile and responsibilities.

### 3.1 Health & Safety

Core assessment area covering:

- competent person arrangements
- policy and management arrangements
- risk assessment and control
- employee consultation
- training and induction
- first aid
- accidents and near misses
- RIDDOR awareness/process
- workplace welfare
- equipment and maintenance
- relevant specialist risks such as COSHH, DSE, manual handling, lone working, work at height, young workers, contractors, and driving for work

### 3.2 Fire Safety

Conditional module where the organisation has relevant responsibility for non-domestic premises or workplace fire arrangements.

Typical checks:

- responsibility for fire safety is understood
- suitable current fire risk assessment exists
- identified actions are managed
- emergency and evacuation arrangements are suitable
- fire information/training is provided
- relevant systems and precautions are maintained
- coordination exists where premises are shared

### 3.3 Legionella

Conditional module where the organisation owns, manages, or has responsibility for relevant water systems.

Typical checks:

- legionella risk has been assessed
- responsibilities and proportionate controls are identified
- required monitoring, maintenance, and records are maintained where the assessment requires them

The tool must not imply that every premises needs elaborate routine monitoring; recommendations should remain proportionate to the risk assessment.

### 3.4 Asbestos

Conditional module where the organisation owns, manages, or has responsibility for maintenance/repair of relevant non-domestic premises.

Typical checks:

- building age / refurbishment history is known or uncertainty is recognised
- asbestos presence has been established or presumed where appropriate
- where asbestos is present or presumed, an up-to-date register and management plan are in place
- known/presumed ACMs are monitored and arrangements are reviewed
- asbestos information is provided to contractors before intrusive work
- where refurbishment, intrusive maintenance, or demolition is planned, appropriate asbestos information/survey is available for affected areas

The output must not jump from uncertainty straight to a sales recommendation. It should first explain what the user should establish or verify.

## 4. User journey

### Stage 0 — Landing screen

Working headline:

> **Free Health & Safety Healthcheck**

Supporting copy:

> Get a clearer picture of your current arrangements across Health & Safety, Fire Safety, Legionella and Asbestos. Receive a personalised action plan showing what appears to be in place, what may need reviewing, and what to consider next.

Benefits:

- personalised recommendations
- priority action plan
- downloadable report
- around 5 minutes
- free and no obligation

Primary CTA:

> **Start my Healthcheck**

Disclaimer:

> This tool provides an indicative self-assessment based on the information you enter. It does not constitute a formal health and safety audit, legal advice or confirmation of compliance.

No personal/contact details are requested before the assessment starts.

### Stage 1 — Business profile

Purpose: establish applicability and personalise later questions.

Profile fields:

1. Jurisdiction: England / Wales / Scotland / Northern Ireland
2. Employee count: None / 1–4 / 5–9 / 10–49 / 50–249 / 250+
3. Organisation type / sector
4. Workplace type(s)
5. Responsibility for premises
6. Shared premises / landlord or managing-agent involvement
7. Relevant workplace risks: DSE, manual handling, hazardous substances, lone working, work at height, machinery/equipment, contractors, young workers, occupational driving, etc.
8. Responsibility for hot/cold water systems
9. Responsibility for maintenance/repair of non-domestic premises
10. Relevant building age / refurbishment uncertainty where needed for asbestos screening

### Stages 2–6 — Assessment

The tool presents one question at a time.

Each question uses large accessible choices:

- Yes
- Partly
- No
- Not sure

`Not applicable` is generally determined by the rules engine rather than chosen by the user.

Interface includes:

- section title
- question count and progress bar
- short question wording
- optional “What does this mean?” explanation
- Back control
- autosave status

There is no sales messaging during the assessment.

### Stage 7 — Headline result before contact capture

Show genuine value before asking for details.

Example:

- 3 Priority actions
- 4 Areas to review
- 15 Areas where no obvious gap was identified

Show the category/pillar summary but not the full recommendation detail yet.

### Stage 8 — Contact capture

Required:

- first name
- last name
- company
- work email

Optional initially:

- telephone

Question:

> Would you like Acorn Safety Services to contact you to discuss any of the issues identified or arrange a free compliance audit?

Options:

- Yes please
- Not at the moment

If Yes:

- telephone becomes required
- postcode is requested for routing

Separate optional marketing consent checkbox:

> I’d also like to receive occasional health and safety guidance and updates from Acorn Safety Services.

The marketing checkbox is independent of receiving the requested report.

### Stage 9 — Full report unlocked

Immediately show:

- full on-screen report
- downloadable PDF
- option to resend report email
- low-pressure Acorn CTA near the end only

## 5. Assessment structure

The question library may contain roughly 35–40 possible questions, but a normal user should see only those relevant to their profile.

### Core modules

- Managing Health & Safety
- Risk Management
- People & Training
- First Aid & Incidents
- Workplace & Equipment

### Conditional modules

- Fire Safety
- Legionella
- Asbestos
- COSHH
- DSE
- Manual Handling
- Lone Working
- Work at Height
- Young Workers
- Contractors
- Driving for Work

## 6. Rules engine

The rules engine decides:

- which questions apply
- which wording variant applies
- employee-threshold logic
- jurisdiction-specific content
- whether specialist modules appear
- whether prior answers must be invalidated after profile changes

The front end must not make final legal/compliance classifications.

### Example applicability logic

- 5+ employees: written policy / recorded-significant-findings wording becomes relevant
- hazardous substances selected: COSHH module appears
- DSE selected: DSE question appears
- lone working selected: lone-working module appears
- relevant water-system responsibility: Legionella module appears
- relevant premises/building responsibility: Fire and/or Asbestos modules appear

If a user changes an earlier answer so that a module no longer applies, hidden answers from that module are excluded from results and reports.

## 7. Result model

No numerical compliance score is shown to the customer.

### Answer states

- `addressed`
- `review`
- `priority`
- `not_assessed`

Typical mapping:

- Yes → addressed
- Partly → review
- Not sure → review or priority depending on question
- No → review or priority depending on question

The exact consequence is defined by the question/recommendation configuration, not by a universal point score.

### Section result

- any priority item → Priority action
- no priority but at least one review item → Review recommended
- all applicable items addressed → No obvious gap identified
- nothing applicable → Not assessed / Not applicable

### Overall result

- one or more priority items → “Priority actions identified”
- no priorities and several review items → “Several areas would benefit from review”
- one or two review items → “Some areas would benefit from review”
- no gaps → “No obvious gaps identified from your answers”

The positive result is always qualified so it cannot be interpreted as certification of compliance.

## 8. Recommendation engine

Recommendations are curated and versioned.

Each question/answer combination can have:

- customer-facing status
- report heading
- “What we identified” text
- “Recommended next step” text
- “Why this matters” text
- “What good looks like” text
- Acorn internal service tags
- jurisdiction variant
- source/reference metadata
- editorial sort rank

### What good looks like

Each recommendation should include a concise description of the desired state.

Example:

> **What good looks like:** The organisation knows who is providing competent health and safety assistance, their responsibilities are clear, and they have the skills, knowledge, experience and resources appropriate to the business.

This is a client-benefit feature and should be treated as a core report component.

## 9. Report structure

The browser report and PDF are generated from the same report data object.

### Cover

- Acorn Safety Services branding
- Health & Safety Healthcheck Report
- “Covering Health & Safety · Fire · Legionella · Asbestos”
- company name
- assessment date
- self-assessment report label

### Executive summary

- number of areas assessed
- priority count
- review count
- addressed count
- pillar/category overview
- clear disclaimer

### Section 1 — What appears to be working

Start positively.

Show concise positive findings such as:

- competent-person arrangements appear to be in place
- induction appears to be addressed
- first-aid arrangements appear to be in place

Qualify that these findings are based on information supplied by the user and have not been independently verified.

### Section 2 — Things worth checking

Show uncertainty, partial arrangements, and non-priority gaps with:

- what was identified
- recommended next step
- why it matters
- what good looks like

### Section 3 — Priority actions

Only include genuinely important management issues that merit priority review.

Do not claim the workplace itself has been assessed as “high risk”.

### Action summary

Table containing:

- status
- area
- recommended action

Avoid arbitrary deadlines unless there is a specific and defensible basis.

### Final support CTA

One low-pressure section only:

> **Need help with any of the actions identified?**
>
> If you do not have the relevant expertise internally, Acorn can assist with areas including Health & Safety, Fire Safety, Legionella and Asbestos.

Primary CTA:

> **Request a free Health & Safety Compliance Audit**

No repeated sales callouts after every recommendation.

## 10. WordPress plugin architecture

Plugin name: **Acorn Safety Healthcheck**

Shortcode:

`[acorn_safety_healthcheck]`

The plugin owns the assessment experience. GeneratePress/GenerateBlocks provide the surrounding site chrome.

### Components

1. Assessment UI
2. Rules engine
3. Results engine
4. Lead capture
5. Report generator
6. WordPress admin

These components should have clean boundaries and be independently testable.

### Front-end approach

Use a lightweight JavaScript state machine and WordPress REST endpoints. Do not require a large SPA framework. Avoid loading plugin assets on pages that do not contain the Healthcheck.

### Server authority

The server recalculates applicability, statuses, counts, and recommendations. Client-supplied result totals are never trusted.

## 11. Data model

Use dedicated database tables rather than storing completed assessments as posts.

### Assessments table

Stores:

- internal ID
- public UUID/token
- status
- jurisdiction
- company size
- sector
- profile data
- assessment version
- timestamps
- priority/review/addressed counts
- overall result
- service tags
- contact link
- report generation metadata

### Answers table

Stores:

- assessment ID
- question ID
- question version
- answer
- generated status
- answered timestamp

### Contacts table

Stores:

- contact identity
- company
- email
- telephone
- postcode
- audit request flag
- marketing consent flag/timestamp
- created timestamp

### Immutable completion snapshot

On completion, store the exact:

- questions shown
- wording versions
- answers
- statuses
- recommendations
- source metadata

This ensures old reports remain reproducible after content changes.

## 12. WordPress admin

Menu: **Healthcheck**

### Dashboard

Show:

- starts
- completions
- report captures
- audit requests
- funnel conversion
- common findings
- service opportunity counts

### Assessments

Filter by:

- date
- company
- overall result
- audit request
- jurisdiction
- sector
- internal service opportunity

Assessment detail tabs:

- Overview
- Answers
- Recommendations
- Contact
- Report

Actions:

- download PDF
- resend report

### Questions

Editable/versioned question library.

### Recommendations

Editable/versioned output library.

### Content Review

Track:

- last reviewed date
- reviewer
- next review date
- official source/reference

Admin should flag items due for review.

### Settings

Configure:

- logo/report branding
- report contact details
- customer email subject/body template
- internal notification recipient
- audit CTA URL
- privacy policy URL
- PDF footer
- data retention

## 13. Internal lead intelligence

The customer experience remains client-first, but the completed assessment may be tagged internally for:

- Health & Safety opportunity
- Fire opportunity
- Legionella opportunity
- Asbestos opportunity
- Training opportunity where appropriate

These tags must not alter the customer-facing recommendations.

Example internal notification:

> Healthcheck Lead | ABC Engineering | 3 Priority Actions | Audit Requested

Include:

- business profile
- priority and review findings
- internal service tags
- audit request status
- customer contact information
- admin link to the full assessment

## 14. Email and PDF

### Customer email

Send immediately after contact capture.

Include:

- headline result
- secure link to report
- PDF attachment where available
- low-pressure invitation to contact Acorn

### PDF

Generate server-side from the same canonical report data object used by the browser report.

Prefer a bundled, established PHP renderer so customer data is not sent to an unnecessary external PDF service.

Generated PDFs should be temporary/reproducible rather than permanently public in predictable upload paths.

## 15. Security and privacy

Requirements:

- WordPress nonces/request protections
- server-side validation
- strict allow-lists for question IDs and answer values
- prepared SQL queries
- output escaping
- secure, non-predictable report tokens
- admin capability checks
- rate limiting / abuse protection
- honeypot initially, with CAPTCHA only if necessary
- no trust in client-side result calculations
- no public sequential report URLs

### Retention

Suggested defaults:

- incomplete anonymous assessments: delete after 7 days
- temporary generated PDFs: remove after a short period because they are reproducible
- completed assessment/contact retention: configurable to match Acorn's privacy policy and business requirements

Collect no unnecessary personal data.

## 16. Error and recovery behaviour

### Network/save failure

Keep local progress and retry saving when connectivity returns.

### Browser closed

Within the incomplete-assessment retention period, offer:

- Continue Healthcheck
- Start again

### Customer email failure

The on-screen report still unlocks. Admin records the failure and provides a resend action.

### PDF failure

The web report remains available and the assessment stays saved. Admin records the PDF error.

## 17. Accessibility and UX

Requirements:

- mobile-first layout
- keyboard operation
- native-accessible radio semantics under visual cards
- visible focus states
- screen-reader labels
- status conveyed with words/icons as well as colour
- sufficient contrast
- progress shown textually and visually
- no unnecessary distractions during the assessment

## 18. Analytics

V1 analytics should include:

### Funnel

- Healthchecks started
- Assessments completed
- Reports requested
- Audits requested

### Common findings

Aggregate counts/rates by topic.

### Internal service opportunities

- Health & Safety
- Fire
- Legionella
- Asbestos

Analytics are secondary to the client experience and must not change the assessment wording or result logic.

## 19. V1 scope exclusions

Do not include in V1 unless separately approved:

- AI-generated legal/compliance advice
- customer accounts
- online booking
- CRM integrations
- Make/Zapier workflows
- Alpha Tracker integration
- complex marketing automation
- live chat inside the assessment

Provide clean internal hooks/events so these can be added later without redesigning the core engine.

Suggested completion hook:

`acorn_healthcheck_completed`

## 20. Acceptance criteria

V1 is ready only when all of the following are true:

1. A user can complete the assessment on desktop and mobile without creating an account.
2. Only relevant conditional questions appear.
3. Typical completion time remains around 4–6 minutes.
4. Back navigation does not corrupt the result.
5. Changing profile answers correctly recalculates applicability and removes invalidated hidden answers.
6. Results are calculated server-side.
7. No percentage-compliance or pass/fail claim appears.
8. Every Priority/Review result has approved recommendation content.
9. Positive findings are included in the report.
10. Full recommendations require contact capture, after a meaningful headline result has already been shown.
11. Audit request and marketing consent are separate.
12. Browser result and PDF are generated from the same canonical report data.
13. Customer email and internal notification work.
14. Email/PDF failures do not lose assessments.
15. Completed assessments appear in WordPress admin.
16. Secure non-predictable report links are used.
17. Questions and recommendations have version history.
18. Historical completed reports remain reproducible.
19. Basic accessibility and keyboard operation work.
20. Plugin assets load only on pages containing the Healthcheck.
21. The tool works independently of Gravity Forms.
22. The assessment does not contain service-selling messages while the user is answering questions.
23. Fire, Legionella and Asbestos remain conditional, proportionate specialist checks rather than forced sales sections.
24. A client can complete the Healthcheck, use the report independently, and derive genuine value without ever contacting Acorn.

## 21. Testing strategy

### Rules tests

Create scenario-based tests across:

- jurisdiction
- employee thresholds
- workplace types
- fire responsibility
- legionella responsibility
- asbestos/building responsibility
- conditional specialist risks

### Answer/result tests

For every question:

- Yes produces expected state
- Partly produces expected state/recommendation
- No produces expected state/recommendation
- Not sure produces expected state/recommendation

### Backtracking tests

Confirm that changing earlier profile answers invalidates and excludes no-longer-applicable downstream answers.

### Security tests

At minimum:

- manipulated client totals ignored
- invalid question IDs rejected
- invalid answer values rejected
- SQL injection safely handled
- XSS escaped
- report tokens non-predictable
- cross-assessment access blocked
- admin capabilities enforced

### Report consistency tests

Confirm that browser, PDF, email summary, and admin all agree on the same canonical result.

## 22. Launch page

Recommended URL:

`/health-and-safety-healthcheck/`

Recommended H1:

> **Free Health & Safety Healthcheck for UK Businesses**

The existing Compliance Audit page remains separate and becomes the natural human follow-up after the Healthcheck.

## 23. Product success test

The Healthcheck succeeds when a user can complete it quickly, understand the result without specialist knowledge, leave with a useful practical action plan, and feel that Acorn has helped them before asking for anything in return.

Commercial opportunities should be a consequence of that usefulness, not the visible purpose of the assessment.

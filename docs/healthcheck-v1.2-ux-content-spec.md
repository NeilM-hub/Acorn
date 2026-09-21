# Acorn Safety Healthcheck v1.2 — UX, Content and Implementation Specification

Status: design specification only. No production logic changed by this document.

Base release: v1.0.3 on `main`.

## 1. Product objective

Build a simpler, more visually impactful client-facing Health & Safety Healthcheck that gives a useful result in roughly 3–4 minutes without feeling like a formal audit or a long compliance questionnaire.

The product should sit between:

- the original Acorn checklist — quick, understandable and easy to scan; and
- the current v1.0.3 Healthcheck — technically robust, versioned, deterministic, reportable and auditable.

The goal is not to remove the strong v1.0.3 architecture. The goal is to replace the customer journey and active question set with a shorter, better-designed v1.2 experience.

## 2. Non-negotiable principles

1. No percentage compliance score.
2. No pass/fail or "compliant" verdict.
3. No AI legal/compliance judgement.
4. Results remain deterministic and server-side.
5. Customer-facing finding statuses remain:
   - Priority action
   - Review recommended
   - No obvious gap identified
6. "Not sure" remains a legitimate answer.
7. Specialist Fire / Legionella / Asbestos checks appear only when relevant.
8. Historical completed reports remain immutable.
9. v1.0/v1.1 content is retained; v1.2 is a new content version.
10. The tool must remain useful even if the user never contacts Acorn.
11. Technical/legal wording remains subject to named competent-person sign-off before full live launch.

## 3. Source basis

The customer-experience direction is based on two Acorn artefacts:

- **Acorn Safety handy checklist**: its strength is immediacy. It covers familiar small-business checks such as risk assessments, policies, training, incidents/RIDDOR, fire, Legionella, first aid, competent-person support, consultation, induction and welfare in a very simple checklist format.
- **Current Acorn Healthcheck report**: its strongest feature is the action structure: "What we found", "What to do next", "Why it matters" and "What good looks like", plus the priority / review / positive hierarchy.

Current official references used to sense-check the draft structure include:

- HSE — Health and safety basics for your business: https://www.hse.gov.uk/simple-health-safety/
- HSE — Prepare a health and safety policy: https://www.hse.gov.uk/simple-health-safety/policy/
- HSE — First aid at work: https://www.hse.gov.uk/simple-health-safety/firstaid/
- HSE — Employers' Liability (Compulsory Insurance) Act guide: https://www.hse.gov.uk/pubns/hse40.htm
- HSE — Legionnaires' disease, L8: https://www.hse.gov.uk/pubns/books/l8.htm
- HSE — Duty to manage asbestos: https://www.hse.gov.uk/asbestos/duty/
- GOV.UK — Fire risk assessments: https://www.gov.uk/workplace-fire-safety-your-responsibilities/fire-risk-assessments

These sources do not replace the required Acorn competent-person review, particularly for jurisdiction-specific Fire / Legionella / Asbestos wording.

## 4. Target journey length

### Typical user

- 2 short tailoring fields at the start.
- 12 core scored questions.
- 0–4 conditional scored questions.
- 3–4 unscored applicability/selection steps embedded naturally in the relevant section.
- Typical journey: approximately 14–17 screens after Start.
- Maximum normal journey: approximately 20 screens.
- Target completion time: 3–4 minutes.

The interface must never describe this as "30 questions" or show a percentage progress indicator that could be mistaken for a compliance percentage.

## 5. Overall customer journey

1. Landing / value proposition
2. Quick tailoring
3. Managing Health & Safety
4. People & Workplace
5. Fire Safety
6. Premises risks
7. Additional workplace risks
8. Final checks
9. Results reveal
10. Contact capture
11. Secure web report
12. PDF / email / admin record

## 6. Screen 1 — Landing

### Purpose

Make the tool feel like a useful digital product, not a WordPress form.

### Recommended content

Eyebrow:
**Free Health & Safety Healthcheck**

Hero:
**Find the gaps. Know what to do next.**

Supporting copy:
**Answer a few straightforward questions and get a practical snapshot of what looks good, what may need attention and what to do next.**

Topic chips:
- Health & Safety
- Fire Safety
- Legionella
- Asbestos

Value points:
- Around 3–4 minutes
- No account needed
- Personalised action plan
- Downloadable report

Primary CTA:
**Start my Healthcheck**

Small disclaimer:
**This is an indicative self-assessment based on the information you provide. It is not a formal audit, legal advice or confirmation of compliance.**

### Visual treatment

- Hero width: up to 1040px.
- Main assessment width after Start: 760–860px.
- Large headline with generous white space.
- Four small topic chips, not four boxed sales cards.
- One clear primary CTA.
- No contact fields on landing.

## 7. Screen 2 — Quick tailoring

This replaces the current large organisation-profile form.

Heading:
**A couple of details so we can tailor the Healthcheck**

Supporting text:
**We only use these answers to show the checks that are relevant to you.**

Fields:

### T0A — Jurisdiction
**Where is your main workplace?**
- England
- Wales
- Scotland
- Northern Ireland

### T0B — Employee band
**How many people do you employ?**
- None
- 1–4
- 5–9
- 10–49
- 50–249
- 250+

Primary CTA:
**Start the questions**

No sector, water responsibility, asbestos, intrusive work, risk flags or other specialist profile questions appear here.

Those checks move into the assessment at the point where they make sense to the user.

## 8. Standard question-screen pattern

Each scored question uses:

1. Section label
2. Segmented progress indicator
3. One short question
4. Four large answer cards
5. Expandable explainer
6. Back control
7. Automatic save

Example:

**FIRE SAFETY**

[section progress segments]

### Do you have a current fire risk assessment?

[ Yes ] [ Partly ]
[ No ] [ Not sure ]

**ⓘ What does this mean?**

Expanded help contains:

- a short plain-English explanation; and
- where useful, a separate **What good looks like** sentence.

### Interaction rules

- One question per screen.
- Desktop answer layout: 2×2 grid.
- Mobile answer layout: 1×4 or 2×2 where comfortable.
- Minimum touch target 48px.
- On selection:
  1. card visibly selects;
  2. answer is saved;
  3. after a short 200–350ms confirmation, move to next screen.
- Back returns to the previous visible screen and preserves answers.
- The help panel does not advance the journey.
- `prefers-reduced-motion` removes non-essential transitions.
- No separate Next button for ordinary Yes / Partly / No / Not sure questions.
- Multi-select and tailoring screens retain an explicit Continue button.

## 9. Section navigation

Use five meaningful stages, not "Question 11 of 30":

1. Managing Safety
2. People & Workplace
3. Fire
4. Premises & Risks
5. Finish

Display:
- current section name;
- five-segment progress rail;
- optional text such as **Section 3 of 5**.

Do not display a numeric percentage.

At the first question of a new section, a small inline transition banner may appear:

**Next up: Fire Safety**
**Two quick checks about your current fire arrangements.**

No extra click should be required purely for a section transition.

---

# 10. v1.2 question set

The wording below is the customer-facing draft. Each item also defines the help content and proposed deterministic finding behaviour.

## Section A — Managing Safety

### Q1 — Competent support
Key: `M01_COMPETENT_PERSON`

Question:
**Do you have someone competent helping you manage health and safety?**

What does this mean?
**Every business needs access to someone with suitable knowledge, skills and experience to help manage health and safety. This could be someone within your business or external support.**

What good looks like:
**You know who provides competent advice, they understand your business and you can access support when needed.**

Proposed status mapping:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q2 — Health & Safety arrangements / policy
Key: `M02_POLICY`

Under 5 employees:
**Are your health and safety arrangements clear and kept up to date?**

5+ employees:
**Do you have a current written health and safety policy and clear arrangements for putting it into practice?**

What does this mean?
**Your arrangements should explain how health and safety is managed, who is responsible and how the important controls are put into practice. Businesses with five or more employees should have their policy in writing.**

What good looks like:
**Responsibilities are clear, the arrangements reflect how the business actually operates, and the policy is reviewed when things change.**

Proposed status mapping:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Review recommended for under 5; Priority action for 5+
- Not sure → Review recommended

### Q3 — Main risk assessment
Key: `R01_GENERAL_RA`

Question:
**Have you assessed the main health and safety risks in your workplace?**

What does this mean?
**A suitable risk assessment identifies the significant hazards, who could be harmed, the controls already in place and anything else that needs to be done.**

What good looks like:
**The assessment reflects the work people actually do and the controls are practical, proportionate and kept under review.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q4 — Follow-up actions
Key: `R02_ACTION_REVIEW`

Question:
**Do you make sure actions from risk assessments are completed?**

What does this mean?
**Finding an issue is only useful if something happens next. Actions should be assigned, followed up and the assessment reviewed when work, equipment, people or circumstances change.**

What good looks like:
**Important actions have an owner and a clear status, and completed actions can be evidenced.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

## Section B — People & Workplace

### Q5 — Induction, training and supervision
New combined key: `P01_TRAINING_INDUCTION`

Question:
**Do employees get the induction, information, training and supervision they need to work safely?**

What does this mean?
**People should understand the risks associated with their work, the precautions they need to take and what to do in an emergency. New starters should receive suitable information before or as they begin work.**

What good looks like:
**People know the controls relevant to their role, receive suitable training and are supervised appropriately while gaining competence.**

Applicability:
- employee_band != none

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q6 — Worker consultation
Key: `M04_CONSULTATION`

Question:
**Do you consult employees about health and safety?**

What does this mean?
**Employees should have a practical way to raise concerns and contribute to health and safety matters that affect them. This can happen through meetings, representatives, briefings or normal day-to-day consultation.**

What good looks like:
**Workers know how to raise concerns and are involved when decisions or changes could affect their health and safety.**

Applicability:
- employee_band != none

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Review recommended
- Not sure → Review recommended

### Q7 — First aid
Key: `A01_FIRST_AID`

Question:
**Do you have suitable first-aid arrangements for your workplace?**

What does this mean?
**The level of first-aid provision should reflect your workplace, workforce and risks. This can include suitable equipment, an appointed person and trained first-aiders where the needs assessment shows they are required.**

What good looks like:
**Your first-aid needs have been considered and the right people, equipment and arrangements are available whenever people are at work.**

Applicability:
- employee_band != none, subject to competent-person review for edge cases

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q8 — Accidents, near misses and RIDDOR
New combined key: `A04_INCIDENT_REPORTING`

Question:
**Do you record incidents and know when something needs to be reported?**

What does this mean?
**Recording accidents and relevant near misses helps you learn from what happened. Certain work-related injuries, diseases and dangerous occurrences may also need to be formally reported under the relevant RIDDOR regime.**

What good looks like:
**People know how to report an incident, important events are reviewed, actions are followed up and the right person knows how to identify a reportable event.**

Applicability:
- employee_band != none, subject to competent-person review

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Review recommended
- Not sure → Review recommended

### Q9 — Workplace and equipment
New combined key: `W03_WORKPLACE_EQUIPMENT`

Question:
**Are your workplace, welfare facilities and work equipment kept safe and properly maintained?**

What does this mean?
**This includes the general condition of the workplace, toilets and welfare facilities, housekeeping and any work equipment that needs maintenance or inspection.**

What good looks like:
**Facilities are suitable and usable, equipment is appropriate for the task, defects are dealt with and required maintenance or inspections are kept up to date.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Review recommended
- Not sure → Review recommended

## Section C — Fire Safety

### Gate F0 — Fire responsibility
Unscored profile/applicability step.

Question:
**Are you responsible, fully or partly, for fire safety at any workplace or premises?**

Answers:
- Yes
- Partly / shared responsibility
- No
- Not sure

What does this mean?
**Fire-safety responsibility can sit with an employer, owner, landlord, occupier or another person with control of the premises. Responsibility can also be shared.**

Behaviour:
- Yes / Partly / Not sure → show Q10 and Q11.
- No → skip scored Fire questions and do not count Fire as a finding.
- Not sure is not itself a finding; the follow-up answers determine findings.
- Jurisdiction-specific legal wording remains behind versioned recommendations.

### Q10 — Fire risk assessment
Key: `F02_FIRE_RA`

Question:
**Do you have a current fire risk assessment for the premises you are responsible for?**

What does this mean?
**A fire risk assessment should identify fire hazards, people at risk, the precautions already in place and any further action required. It should be reviewed when relevant circumstances change.**

What good looks like:
**The assessment reflects the premises, people and activities, is recorded where required and any actions are being followed up.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q11 — Fire arrangements and maintenance
New combined key: `F06_FIRE_ARRANGEMENTS`

Question:
**Are your fire emergency arrangements understood and are the relevant fire precautions checked and maintained?**

What does this mean?
**People should know what to do if there is a fire. Relevant alarms, emergency lighting, escape routes, firefighting equipment and other precautions should also be checked and maintained as appropriate for the premises.**

What good looks like:
**Emergency arrangements are practical and communicated, required systems are maintained, defects are acted on and relevant records are available.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

## Section D — Premises risks

### Gate L0 — Water-system responsibility
Unscored progressive profile field.

Question:
**Are you responsible, fully or partly, for the building's hot and cold water systems?**

What does this mean?
**We ask this so we only show Legionella checks where they may be relevant. Responsibility may sit with your organisation, a landlord, a managing agent or be shared.**

Behaviour:
- Yes / Partly / Not sure → show Q12.
- No → skip Legionella.

### Q12 — Legionella
New combined key: `L04_LEGIONELLA_MANAGEMENT`

Question:
**Do you have a suitable Legionella risk assessment and the required controls in place?**

What does this mean?
**The assessment should consider whether Legionella could grow or spread in water systems you control and identify any management, monitoring, maintenance or other precautions that are needed.**

What good looks like:
**Responsibilities are clear, the risk has been assessed, required controls are carried out and the arrangements are reviewed when circumstances change.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Gate A0 — Asbestos applicability
Unscored progressive profile field.

Question:
**Are you responsible for maintenance or repair of a non-domestic building built before 2000, or where its age is uncertain?**

What does this mean?
**We ask because older buildings may contain asbestos and responsibility for maintenance or repair can bring responsibilities for managing that risk.**

Behaviour:
- Yes / Partly / Not sure → show Q13.
- No → skip Asbestos.

### Q13 — Asbestos management
New combined key: `AS05_ASBESTOS_MANAGEMENT`

Question:
**Do you have suitable asbestos information and management arrangements for the areas you are responsible for?**

What does this mean?
**You should know whether asbestos is present or presumed to be present, where it is located and what condition it is in. That information should be kept current and made available before work that could disturb it.**

What good looks like:
**There is a current record/register, responsibilities and actions are clear, known or presumed materials are monitored and relevant information reaches anyone who could disturb them.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

Implementation note:
- The v1.2 recommendation for a Priority/Review finding should additionally mention that intrusive/refurbishment work may require more specific asbestos information before work starts. Do not add another universal question unless competent review identifies a genuine need.

## Section E — Additional workplace risks

### Selector S0 — Relevant additional risks
Unscored multi-select.

Question:
**Which of these are relevant to your work?**

Tiles:
- Hazardous substances
- Manual handling
- Display screen equipment
- Lone working
- Work at height
- Machinery / equipment
- Contractors
- Driving for work
- Young workers
- None of these

Help:
**Select anything that forms a meaningful part of your work. We use this to avoid asking you about risks that do not apply.**

Behaviour:
- Save selections as `risk_flags`.
- If None → skip Q14.
- If one or more selected → show Q14.
- Selecting None clears other risk flags.
- Existing detailed specialist questions remain in historical content versions but are not active in v1.2.

### Q14 — Selected risk controls
New key: `S01_SELECTED_RISK_CONTROLS`

Dynamic question:
**Have the additional risks you've selected been assessed and properly controlled?**

Under the question, show compact chips naming the user's selected risks.

What does this mean?
**Different activities need different controls. The important point is that the relevant risks have been considered, suitable precautions are in place and those precautions are reviewed when things change.**

What good looks like:
**The selected risks have proportionate assessments and controls, people understand what is expected of them and significant actions are followed through.**

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

Report behaviour:
- The recommendation should echo the selected risk names.
- It must not imply that every selected risk has the same legal requirement.
- It should direct the user to review the relevant risk-specific controls.

## Section F — Final checks

### Q15 — Employers' Liability insurance
New key: `E01_EMPLOYERS_LIABILITY`

Question:
**Do you have Employers' Liability insurance where it is required?**

What does this mean?
**Most employers are required to insure against liability for injury or disease suffered by employees because of their work, although exemptions can apply in some circumstances.**

What good looks like:
**You have confirmed whether the requirement applies to your organisation and, where it does, suitable cover is in force.**

Applicability:
- employee_band != none

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Priority action
- Not sure → Review recommended

### Q16 — Health and safety law information
New key: `E02_LAW_INFORMATION`

Question:
**Have workers been given the required health and safety law information?**

What does this mean?
**Employers can provide the required information by displaying the approved Health and Safety Law poster in a suitable position or by giving workers the equivalent leaflet/information.**

What good looks like:
**Workers can easily access the required health and safety law information.**

Applicability:
- employee_band != none
- jurisdiction-specific review required before launch

Status:
- Yes → No obvious gap identified
- Partly → Review recommended
- No → Review recommended
- Not sure → Review recommended

---

# 11. Answer semantics

## Yes
Means the user believes the arrangement is in place.
Customer status: **No obvious gap identified**.

## Partly
Means something exists but may be incomplete, out of date, inconsistent or not fully implemented.
Customer status: **Review recommended**.

## No
Status is defined per question:
- higher-impact foundational checks can produce **Priority action**;
- lower-impact administrative/clarity checks can produce **Review recommended**.

## Not sure
Default customer status: **Review recommended**.

The copy should never shame a user for selecting Not sure. The recommendation should begin from verification:
**Confirm the current position and who is responsible.**

# 12. Result roll-up

No scoring.

Headline counts:
- Priority actions
- Reviews recommended
- Areas looking good

Pillars:
- Health & Safety
- Fire Safety — only if applicable
- Legionella — only if applicable
- Asbestos — only if applicable

Pillar status is the highest finding severity within that applicable pillar.

If a pillar is not applicable, omit it from the headline grid rather than displaying a misleading positive result.

# 13. Results reveal screen

After the last applicable question:

Eyebrow:
**Healthcheck complete**

Headline:
**Here's where things stand**

Three large summary cards:
- [n] Priority actions
- [n] Worth reviewing
- [n] Areas looking good

Below the cards:

### Your first priority

Display the highest-ranked Priority finding. If there is no Priority finding, display the first Review finding. If neither exists, display a positive completion message.

Example:
**Make sure actions from your fire risk assessment are being followed through.**

Supporting copy:
**Your answer suggests this is one of the first areas worth addressing.**

Then:
### Get your complete action plan

Supporting:
**Enter your details to view your full recommendations and download your report.**

Contact fields:
- First name
- Last name
- Company
- Work email
- Telephone — optional unless audit requested

Audit request and marketing consent remain separate.

Primary CTA:
**View my full action plan**

The user sees real value before being asked for contact details.

# 14. Contact-capture design

Use a calm single card beneath the result reveal.

Do not:
- hide the headline result behind a form;
- use aggressive sales copy;
- pre-tick marketing;
- combine audit request with marketing consent.

Audit option wording:
**Would you like Acorn Safety Services to contact you about any of the areas highlighted or arrange a free compliance audit?**
- Yes please
- Not at the moment

If Yes:
- telephone required
- postcode required

Marketing:
**I'd also like to receive occasional health and safety guidance and updates from Acorn Safety Services.**

# 15. Web report

Retain the current executive-report concept but simplify it around v1.2.

Recommended structure:

## A. Header / cover
- Acorn branding
- company
- date
- concise descriptor

## B. Healthcheck at a glance
- three counts
- only applicable pillar cards
- short narrative summary

## C. Your priority action plan
For every Priority action:
- What we found
- What to do next
- Why it matters
- What good looks like

Surface the top 3 visually first. Do not suppress additional Priority findings; continue the full list below.

## D. Other things worth reviewing
Shorter cards:
- heading
- practical next step
- optional "What good looks like" where useful

## E. What you're already doing well
Concise two-column list of positive findings.

## F. Support
One CTA:
**Request a free Health & Safety Compliance Audit**

Retain the disclaimer.

# 16. PDF

Target a concise 4–6 page report for a typical journey, but never truncate findings solely to hit a page count.

Suggested pagination:
1. Cover
2. At a glance
3–4. Priority actions / reviews as required
5. Positive findings + support

Dynamic pagination is acceptable.

Keep:
- direct clickable CTA;
- logo;
- phone;
- website;
- privacy/disclaimer;
- immutable snapshot data.

# 17. Customer email

Subject remains:
**Your Acorn Health & Safety Healthcheck**

Email body:
1. short confirmation;
2. three headline counts;
3. top Priority/Review item;
4. secure report button;
5. brief disclaimer.

Avoid reproducing the entire report in the email.

# 18. WordPress admin record

Assessment detail should show:
- assessment version: 1.2
- jurisdiction
- employee band
- applicability/gate answers
- selected additional risks
- all scored answers
- finding status
- recommendation used
- report token / report actions
- email delivery state
- audit requested
- marketing consent
- completion timestamps

Historical 1.0/1.1 records remain unchanged.

# 19. Visual design system

Use the Acorn Safety Services brand palette as the base.

Core tokens:
- Acorn Safety primary accent blue: `#084e87`
- Ink: `#17221f`
- Muted text: `#60756d`
- Soft surface: `#f7fbf9`
- Border: `#dce6e2`

Design principles:
- Acorn Safety blue `#084e87` is the primary branded accent throughout the Healthcheck;
- do not use Acorn Analytical green as the main Healthcheck brand colour;
- impact comes from scale, space, hierarchy and motion — not loud colour;
- avoid traffic-light semantics;
- statuses must remain understandable without colour;
- no new decorative colour should carry legal/compliance meaning.

### Layout
- landing max width: 1040px
- question content max width: 820px
- question card padding desktop: 40–48px
- mobile padding: 20–24px
- border radius: 16–20px for major surfaces
- button/card radius: 12–14px

### Typography
- hero: `clamp(2.5rem, 5vw, 4.5rem)`
- question: `clamp(1.75rem, 3vw, 2.6rem)`
- section label: small uppercase/letter-spaced
- body: 17–18px desktop, 16px mobile
- line length: keep help copy around 60–72 characters where practical

### Answer cards
- visually large
- full-card clickable
- clear selected state
- visible focus state
- icon optional, text always present
- no tiny radio circles as the primary interaction

### Explainer
Collapsed:
**ⓘ What does this mean?**

Expanded:
- pale neutral/blue-tinted panel
- plain-English paragraph
- divider
- **What good looks like** line

### Motion
- 160–240ms standard transitions
- subtle slide/fade for question change and explainer
- no celebratory/confetti motion
- reduced-motion support

# 20. Mobile-first requirements

At 320px width:
- no horizontal scrolling;
- answer cards remain at least 48px high;
- all text readable without zoom;
- question and first answer options appear quickly without excessive scrolling;
- help accordion remains usable;
- contact form uses appropriate input types/autocomplete;
- CTA remains easy to reach;
- results counts stack cleanly.

# 21. Accessibility

Must retain or improve current accessibility coverage.

Requirements:
- semantic headings;
- fieldset/legend or equivalent accessible grouping;
- keyboard-operable answer cards;
- visible focus;
- `aria-expanded` and `aria-controls` for explainers;
- live region for saved/error state without excessive announcements;
- correct error association;
- no meaning by colour alone;
- WCAG AA contrast;
- axe test remains clean on landing, question, help-open, results and report states.

# 22. Technical implementation approach

The current v1.0.3 backend is strong and should be preserved.

However, one meaningful backend change is required:

### Current limitation
`ApplicabilityContext::fromProfile()` requires every current profile field before questions can be displayed. This is why the journey begins with a large profile form.

### v1.2 approach
Refactor profile/context handling so it supports **progressive context**.

Initial required context:
- jurisdiction
- employee_band

Progressive context collected later:
- fire responsibility / premises responsibility
- water-system responsibility
- maintenance/repair + building-age applicability
- risk_flags

The service should:
- accept a partial profile at the start;
- validate only fields supplied at each progressive step;
- preserve allowed-value validation;
- recompute applicability after a progressive context field changes;
- delete answers that become non-applicable, exactly as the current profile-update logic already protects against hidden stale answers.

Do not fake missing data with hidden defaults such as sector="other".

### Sector
Sector is no longer needed to drive the v1.2 question set and should become optional/legacy for v1.2 rather than forcing an unnecessary customer question.

### Historical behaviour
Legacy content versions must continue to resume/render safely if their assessments already contain the full legacy profile.

# 23. v1.2 content version

Create a new published content version:
`1.2`

Do not overwrite 1.1.

Implementation should clone or seed a distinct version containing only the v1.2 active scored questions.

Historical:
- 1.0 stays retired/historical
- 1.1 stays historical
- completed reports remain immutable
- any in-progress 1.1 assessment should remain bound to 1.1 and resume using its original question set

New assessments after publish use 1.2.

# 24. Question-key strategy

Reuse existing keys where the concept remains substantially the same:
- M01_COMPETENT_PERSON
- M02_POLICY
- R01_GENERAL_RA
- R02_ACTION_REVIEW
- M04_CONSULTATION
- A01_FIRST_AID
- F02_FIRE_RA

Use new keys where multiple old questions are deliberately combined:
- P01_TRAINING_INDUCTION
- A04_INCIDENT_REPORTING
- W03_WORKPLACE_EQUIPMENT
- F06_FIRE_ARRANGEMENTS
- L04_LEGIONELLA_MANAGEMENT
- AS05_ASBESTOS_MANAGEMENT
- S01_SELECTED_RISK_CONTROLS
- E01_EMPLOYERS_LIABILITY
- E02_LAW_INFORMATION

This prevents historical meaning from being silently changed under the same key.

# 25. Deterministic recommendation content

Every Partly / No / Not sure answer must have a versioned recommendation row for every relevant jurisdiction.

Recommendation fields remain:
- finding status
- heading
- identified text
- next step
- why it matters
- what good looks like
- source metadata
- service tags

For Not sure:
identified text should acknowledge uncertainty without treating it as a failure.

Preferred pattern:
**You indicated that you are not sure whether this arrangement is currently in place.**

Next step:
**Confirm the current position and who is responsible, then review the following...**

# 26. Report priority ordering

Do not rank by AI.

Use deterministic `sort_rank`.

Suggested broad priority ordering:
1. Fire / immediate foundational premises risks
2. Main risk assessment / competent assistance
3. First aid / training / selected higher-risk controls
4. Legionella / asbestos where applicable
5. administrative/review items

Final ordering requires competent-person review.

# 27. TDD implementation plan

## Phase 1 — Content/version tests
Add failing tests first for:
- new 1.2 version creation
- 1.1 remains untouched
- new assessments bind to 1.2
- existing completed/in-progress legacy assessments retain original version
- required recommendation coverage
- source/reviewer metadata validation

## Phase 2 — Progressive context tests
Add failing tests for:
- starting with only jurisdiction + employee band
- updating a progressive context field
- recalculating applicable questions
- removing answers that become hidden
- not requiring sector
- legacy full-profile validation still works for legacy assessment state where needed

## Phase 3 — Question-flow tests
Add browser tests for:
- no large profile questionnaire
- exactly two initial tailoring fields
- one question per screen
- answer auto-save/advance
- Back works
- explainer expands/collapses
- Fire conditional branch
- Legionella conditional branch
- Asbestos conditional branch
- risk selector branch
- None clears risk selections
- typical journey completes without irrelevant specialist questions

## Phase 4 — Design tests
Browser assertions for:
- answer-card structure
- five-stage progress
- no percentage score/progress text
- mobile 320/375/390 widths
- keyboard/focus
- reduced-motion-safe behaviour
- accessibility scan with explainer open and result screen visible

## Phase 5 — Report/email/admin
Tests for:
- only applicable pillar cards appear
- full findings retained
- top priority surfaced in pre-report
- email contains counts + top issue + secure URL
- PDF/web share canonical snapshot
- admin shows v1.2 progressive context and selected risks

## Phase 6 — Release verification
Run the existing full CI plus:
- PHP 8.1 compatibility
- syntax
- unit
- WordPress integration
- Playwright desktop
- Playwright mobile
- axe
- dependency audit
- production ZIP verification
- clean WordPress install from generated ZIP

No ZIP is to be supplied until this full run is green.

# 28. Representative acceptance journeys

## Journey A — small office, mostly good
- England
- 10–49 employees
- Fire responsibility yes
- Water responsibility no
- Asbestos gate no
- DSE + contractors selected
- mostly Yes, one Partly

Expected:
- short journey
- Fire visible
- Legionella/Asbestos omitted
- one Review
- no misleading positive status for omitted pillars

## Journey B — property/FM with premises risks
- England
- 50–249
- Fire yes
- Water yes
- pre-2000 maintenance responsibility yes
- multiple risks
- mixture of No / Partly / Not sure

Expected:
- Fire, Legionella, Asbestos all visible
- priority + review findings
- "Not sure" produces review, not pass/fail language

## Journey C — very small / home-led business
- under 5 employees
- no relevant premises responsibility
- no selected additional risks

Expected:
- written-policy wording uses under-5 variant
- specialist premises modules skipped
- no empty/irrelevant report sections

## Journey D — uncertain user
- multiple Not sure responses

Expected:
- no shaming language
- practical "confirm the current position" recommendations
- report still useful

# 29. Launch gates

Engineering green does not equal legal/content approval.

Before live launch:
- [ ] Health & Safety wording signed off by named Acorn competent person
- [ ] Fire wording reviewed for England, Wales, Scotland and Northern Ireland
- [ ] Legionella wording reviewed for GB and Northern Ireland
- [ ] Asbestos wording reviewed for GB and Northern Ireland
- [ ] Employers' Liability / law-poster wording checked for intended UK coverage
- [ ] Privacy/retention confirmed
- [ ] Email delivery checked in production
- [ ] Report logo configured
- [ ] Four representative real journeys completed and timed
- [ ] Desktop + mobile UX review completed
- [ ] Web report, PDF, email and admin record compared for consistency

# 30. Definition of done

v1.2 is ready to replace v1.1 for new assessments when:

1. a new user no longer sees the large initial profile questionnaire;
2. the Healthcheck feels like a guided product, not a form;
3. typical completion is roughly 3–4 minutes;
4. questions are short and understandable;
5. every question has a useful expandable explainer;
6. specialist topics appear only where relevant;
7. the result reveal gives value before contact capture;
8. web/PDF/email/admin are consistent;
9. historical reports remain unchanged;
10. all automated tests and clean-ZIP verification are green;
11. named competent reviewers have signed off the customer-facing legal/technical wording.

import {execFileSync} from 'node:child_process';
import {test, expect} from '@playwright/test';
import {answerCurrentQuestion, completeProfile, healthcheck, progressToResults} from './helpers';

const wpEnv = (...args: string[]): string =>
  execFileSync('npx', ['wp-env', 'run', 'cli', 'wp', ...args], {encoding: 'utf8', stdio: ['ignore', 'pipe', 'inherit']}).trim();

test('visitor completes simplified guided Healthcheck and reaches report', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await expect(app.getByLabel('Work email')).not.toBeVisible();
  await expect(app.getByRole('heading', {level: 1})).toContainText('Find the gaps.');

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await expect(app.getByRole('heading', {name: 'A couple of details so we can tailor the Healthcheck'})).toBeVisible();

  await completeProfile(page, '1–4');
  await expect(app.getByRole('heading', {level: 2})).toHaveText('Do you have someone competent helping you manage health and safety?');
  await expect(app.locator('.acorn-hc__stage-rail span')).toHaveCount(5);

  const help = app.getByRole('button', {name: /What does this mean/});
  await help.click();
  await expect(app.getByText('What good looks like', {exact: true})).toBeVisible();

  await progressToResults(page, 'Yes');

  await expect(app.getByRole('heading', {name: "Here's where things stand"})).toBeVisible();
  await expect(app.locator('.acorn-hc__headline-counts > div')).toHaveCount(3);
  await expect(app.getByText('Get your complete action plan')).toBeVisible();

  await app.getByLabel('First name').fill('Ada');
  await app.getByLabel('Last name').fill('Lovelace');
  await app.getByLabel('Company').fill('Analytical Ltd');
  await app.getByLabel('Work email').fill('ada@example.test');
  await app.getByRole('button', {name: 'View my full action plan'}).click();

  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'Your Healthcheck is complete. Now turn the findings into action.'})).toBeVisible();
  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: "What you're already doing well"})).toBeVisible();
});


test('landing page presents the complete Acorn Safety Healthcheck proposition', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await expect(app.locator('.acorn-hc__landing')).toBeVisible();
  await expect(app.getByRole('img', {name: 'Acorn Safety Services'})).toBeVisible();
  await expect(app.getByRole('heading', {level: 1, name: /Find the gaps\./})).toBeVisible();

  await expect(app.getByRole('heading', {name: 'More than a checklist'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'A clearer picture in a few minutes'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'The Healthcheck looks across the areas businesses often need to manage together'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'Problems are easier to deal with when you can see them clearly.'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'See exactly where to focus'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'Knowing the gaps is the first step. Fixing them is what matters.'})).toBeVisible();
  await expect(app.getByRole('heading', {name: 'Ready to see where things stand?'})).toBeVisible();

  const previewCounts = app.locator('.acorn-hc__landing-preview-counts > div');
  await expect(previewCounts).toHaveCount(3);
  await expect(previewCounts.nth(0)).toContainText('2');
  await expect(previewCounts.nth(0)).toContainText('Priority actions');
  await expect(previewCounts.nth(1)).toContainText('4');
  await expect(previewCounts.nth(1)).toContainText('Worth reviewing');
  await expect(previewCounts.nth(2)).toContainText('10');
  await expect(previewCounts.nth(2)).toContainText('Areas looking good');

  await expect(app.getByRole('link', {name: 'Request a free Compliance Audit'})).toHaveAttribute(
    'href',
    'https://acornhealthandsafety.co.uk/health-and-safety-compliance-audit/',
  );

  await expect(app.getByRole('button', {name: /Start my.*Healthcheck/})).toHaveCount(4);
});

test('saved landing content and final-section switch reach the rendered page', async ({page}) => {
  wpEnv(
    'eval',
    "update_option('acorn_hc_landing_content', ['hero_title_line_1' => 'Admin edited heading', 'show_final_cta' => 0]);",
  );

  try {
    await page.goto('/health-and-safety-healthcheck/');
    const app = healthcheck(page);

    await expect(app.getByRole('heading', {level: 1})).toContainText('Admin edited heading');
    await expect(app.getByRole('heading', {name: 'Ready to see where things stand?'})).not.toBeVisible();
    await expect(
      app.getByText(
        'This is an indicative self-assessment based on the information you provide. It is not a formal audit, legal advice or confirmation of compliance.',
        {exact: true},
      ),
    ).not.toBeVisible();
  } finally {
    wpEnv('option', 'delete', 'acorn_hc_landing_content');
  }
});

test('landing page is polished and overflow-free on a phone viewport', async ({page}) => {
  await page.setViewportSize({width: 390, height: 844});
  await page.goto('/health-and-safety-healthcheck/');

  const app = healthcheck(page);
  const landing = app.locator('.acorn-hc__landing');
  await expect(landing).toBeVisible();

  const metrics = await page.evaluate(() => ({
    viewport: window.innerWidth,
    documentWidth: document.documentElement.scrollWidth,
  }));
  expect(metrics.documentWidth).toBeLessThanOrEqual(metrics.viewport);

  const heading = app.getByRole('heading', {level: 1});
  const headingSize = parseFloat(await heading.evaluate(el => getComputedStyle(el).fontSize));
  expect(headingSize).toBeLessThanOrEqual(48);

  const action = app.locator('.acorn-hc__landing-actions').first();
  const primary = action.locator('.acorn-hc__primary').first();
  const actionBox = await action.boundingBox();
  const primaryBox = await primary.boundingBox();
  expect(primaryBox?.width ?? 0).toBeLessThanOrEqual((actionBox?.width ?? 0) + 1);

  const heroCard = app.locator('.acorn-hc__landing-hero-card');
  const cardBox = await heroCard.boundingBox();
  expect(cardBox?.width ?? 0).toBeLessThanOrEqual(360);
});

test('guided assessment actions fit comfortably on a phone viewport', async ({page}) => {
  await page.setViewportSize({width: 390, height: 844});
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();

  const actionButton = app.getByRole('button', {name: 'Start the questions'});
  const appBox = await app.boundingBox();
  const buttonBox = await actionButton.boundingBox();

  expect(buttonBox?.width ?? 0).toBeLessThanOrEqual((appBox?.width ?? 0) + 1);

  const metrics = await page.evaluate(() => ({
    viewport: window.innerWidth,
    documentWidth: document.documentElement.scrollWidth,
  }));
  expect(metrics.documentWidth).toBeLessThanOrEqual(metrics.viewport);
});

test('plugin assets are not loaded on unrelated pages', async ({page}) => {
  await page.goto('/');
  const sources = await page.locator('script,link').evaluateAll(nodes =>
    nodes.map(node => (node as HTMLScriptElement).src || (node as HTMLLinkElement).href),
  );
  expect(sources.join(' ')).not.toContain('healthcheck.js');
});

test('initial tailoring asks only jurisdiction and employee count', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);
  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();

  await expect(app.getByRole('group', {name: 'Where is your main workplace?'})).toBeVisible();
  await expect(app.getByRole('group', {name: 'How many people do you employ?'})).toBeVisible();
  await expect(app.getByText('Organisation type / sector')).not.toBeVisible();
  await expect(app.getByText('Responsibility for hot/cold water systems')).not.toBeVisible();
});

test('Acorn Safety blue is the primary assessment accent', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  const button = app.getByRole('button', {name: 'Start the questions'});
  await expect(button).toHaveCSS('background-color', 'rgb(8, 78, 135)');
});

test('resume landing gives Start again a subdued secondary treatment', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await expect(app.getByRole('heading', {name: 'A couple of details so we can tailor the Healthcheck'})).toBeVisible();
  await page.reload();

  const restart = app.getByRole('button', {name: 'Start again'});
  await expect(restart).toHaveClass(/acorn-hc__secondary/);
});

test('desktop question screens use the available assessment width', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page, '10–49');

  const wrap = app.locator('.acorn-hc__question-wrap');
  const wrapBox = await wrap.boundingBox();
  expect(wrapBox?.width ?? 0).toBeGreaterThan(880);

  const heading = app.getByRole('heading', {level: 2});
  const headingBox = await heading.boundingBox();
  expect(headingBox?.width ?? 0).toBeGreaterThan(700);
});



test('single-choice routing questions auto-advance without a Continue button', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page, '10–49');

  for (let i = 0; i < 9; i++) {
    await answerCurrentQuestion(page, 'Yes');
  }

  await expect(app.getByRole('heading', {name: 'Are you responsible, fully or partly, for fire safety at any workplace or premises?'})).toBeVisible();
  await expect(app.getByRole('button', {name: 'Continue'})).not.toBeVisible();

  await app.getByLabel('No', {exact: true}).check();

  await expect(app.getByRole('heading', {name: "Are you responsible, fully or partly, for the building's hot and cold water systems?"})).toBeVisible();
  await expect(app.getByRole('button', {name: 'Continue'})).not.toBeVisible();
});

test('secure report opens with an authority-led action hero', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);
  await progressToResults(page, 'No');

  await app.getByLabel('First name').fill('Authority');
  await app.getByLabel('Last name').fill('Tester');
  await app.getByLabel('Company').fill('Authority Test Ltd');
  await app.getByLabel('Work email').fill('authority@example.test');
  await app.getByRole('button', {name: 'View my full action plan'}).click();

  const report = page.locator('.acorn-hc__report');
  await expect(report.locator('.acorn-hc__report-hero--authority')).toBeVisible();
  await expect(report.getByRole('heading', {name: 'Your Healthcheck is complete. Now turn the findings into action.'})).toBeVisible();
  await expect(report.getByText('Specialist guidance across Health & Safety, Fire Safety, Legionella and Asbestos.')).toBeVisible();
});

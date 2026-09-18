import {test, expect, Page} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import {answerCurrentQuestion, completeProfile, healthcheck, questionAnswer} from './helpers';

async function expectAccessible(page: Page, stage: string, scope?: string): Promise<void> {
  const response = await page.request.get(page.url());
  expect(response.status(), `${stage} page status`).toBe(200);

  let axe = new AxeBuilder({page}).withTags(['wcag2a', 'wcag2aa']);
  if (scope) axe = axe.include(scope);

  const results = await axe.analyze();
  expect(results.violations, stage).toEqual([]);
}

test('all public stages meet WCAG A/AA and support keyboard operation', async ({page}) => {
  const response = await page.goto('/health-and-safety-healthcheck/');
  expect(response?.status()).toBe(200);
  const app = healthcheck(page);
  await expect(app).toBeVisible();

  // The wizard is embedded in the site's theme, so accessibility checks here
  // deliberately scope to the plugin UI rather than attributing theme markup
  // defects to the Healthcheck.
  await expectAccessible(page, 'landing', '#acorn-healthcheck');

  await app.getByRole('button', {name: 'Start my Healthcheck'}).focus();
  await page.keyboard.press('Enter');
  await expect(app.getByRole('heading', {name: 'Your organisation'})).toBeFocused();
  await expectAccessible(page, 'profile', '#acorn-healthcheck');

  await completeProfile(page);
  await expect(app.getByText(/Question 1 of/)).toBeVisible();
  await expectAccessible(page, 'question', '#acorn-healthcheck');

  const firstQuestion = await app.getByRole('heading', {level: 2}).textContent();
  await questionAnswer(page, 'Yes').focus();
  await page.keyboard.press('Space');
  await expect
    .poll(async () => (await app.getByRole('heading', {level: 2}).textContent().catch(() => '')) ?? '')
    .not.toBe(firstQuestion ?? '');

  for (let i = 0; i < 30; i++) {
    if (await app.getByRole('heading', {name: 'Your Healthcheck is complete'}).isVisible().catch(() => false)) break;
    await answerCurrentQuestion(page, 'Yes');
  }

  await expect(app.getByRole('heading', {name: 'Your Healthcheck is complete'})).toBeVisible();
  await expectAccessible(page, 'headline and contact', '#acorn-healthcheck');

  await app.getByLabel('First name').fill('Ada');
  await app.getByLabel('Last name').fill('Lovelace');
  await app.getByLabel('Company').fill('Accessible Ltd');
  await app.getByLabel('Work email').fill('accessibility@example.test');
  await app.getByRole('button', {name: 'View my full report'}).click();

  const report = page.locator('.acorn-hc__report');
  await expect(report.getByRole('heading', {name: 'Your Health & Safety Healthcheck'})).toBeVisible();
  await expectAccessible(page, 'report', '.acorn-hc__report');
});

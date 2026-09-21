import {test, expect, Page} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import {completeProfile, healthcheck, progressToResults, questionAnswer} from './helpers';

async function expectAccessible(page: Page, stage: string, scope?: string): Promise<void> {
  const response = await page.request.get(page.url());
  expect(response.status(), `${stage} page status`).toBe(200);

  let axe = new AxeBuilder({page}).withTags(['wcag2a', 'wcag2aa']);
  if (scope) axe = axe.include(scope);

  const results = await axe.analyze();
  expect(results.violations, stage).toEqual([]);
}

test('all public v1.2 stages meet WCAG A/AA and support keyboard operation', async ({page}) => {
  const response = await page.goto('/health-and-safety-healthcheck/');
  expect(response?.status()).toBe(200);
  const app = healthcheck(page);

  await expectAccessible(page, 'landing', '#acorn-healthcheck');

  await app.getByRole('button', {name: 'Start my Healthcheck'}).focus();
  await page.keyboard.press('Enter');
  await expect(app.getByRole('heading', {name: 'A couple of details so we can tailor the Healthcheck'})).toBeFocused();
  await expectAccessible(page, 'tailoring', '#acorn-healthcheck');

  await completeProfile(page);
  await expectAccessible(page, 'question', '#acorn-healthcheck');

  const help = app.getByRole('button', {name: /What does this mean/});
  await help.click();
  await expect(help).toHaveAttribute('aria-expanded', 'true');
  await expectAccessible(page, 'expanded help', '#acorn-healthcheck');

  const firstQuestion = await app.getByRole('heading', {level: 2}).textContent();
  await questionAnswer(page, 'Yes').focus();
  await page.keyboard.press('Space');
  await expect
    .poll(async () => (await app.getByRole('heading', {level: 2}).textContent().catch(() => '')) ?? '')
    .not.toBe(firstQuestion ?? '');

  await progressToResults(page, 'Yes');
  await expectAccessible(page, 'headline and contact', '#acorn-healthcheck');

  await app.getByLabel('First name').fill('Ada');
  await app.getByLabel('Last name').fill('Lovelace');
  await app.getByLabel('Company').fill('Accessible Ltd');
  await app.getByLabel('Work email').fill('accessibility@example.test');
  await app.getByRole('button', {name: 'View my full action plan'}).click();

  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'Your Healthcheck is complete. Now turn the findings into action.'})).toBeVisible();
  await expectAccessible(page, 'report', '.acorn-hc__report');
});

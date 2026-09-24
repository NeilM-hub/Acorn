import {test, expect} from '@playwright/test';
import {completeProfile, healthcheck, progressToResults} from './helpers';

test.use({viewport: {width: 390, height: 844}});

test('mobile visitor can complete the simplified Healthcheck and reach the report', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);
  await progressToResults(page, 'Yes');

  await expect(app.getByRole('heading', {name: "Here's where things stand"})).toBeVisible();
  await app.getByLabel('First name').fill('Mobile');
  await app.getByLabel('Last name').fill('Tester');
  await app.getByLabel('Company').fill('Mobile Test Ltd');
  await app.getByLabel('Work email').fill('mobile@example.test');
  await app.getByRole('button', {name: 'View my full action plan'}).click();

  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'Your Healthcheck is complete. Now turn the findings into action.'})).toBeVisible();
  await expect(page.locator('body')).toHaveCSS('overflow-x', /^(visible|auto|clip)$/);
});

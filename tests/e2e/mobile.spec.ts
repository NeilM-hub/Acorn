import {test, expect} from '@playwright/test';
import {answerCurrentQuestion, completeProfile, healthcheck} from './helpers';

test.use({viewport: {width: 390, height: 844}});

test('mobile visitor can complete the Healthcheck and reach the report', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();
  await completeProfile(page);

  for (let i = 0; i < 30; i++) {
    if (await app.getByRole('heading', {name: 'Your Healthcheck is complete'}).isVisible().catch(() => false)) break;
    await answerCurrentQuestion(page, 'Yes');
  }

  await expect(app.getByRole('heading', {name: 'Your Healthcheck is complete'})).toBeVisible();
  await app.getByLabel('First name').fill('Mobile');
  await app.getByLabel('Last name').fill('Tester');
  await app.getByLabel('Company').fill('Mobile Test Ltd');
  await app.getByLabel('Work email').fill('mobile@example.test');
  await app.getByRole('button', {name: 'View my full report'}).click();

  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'Health & Safety Healthcheck Report'})).toBeVisible();
  await expect(page.locator('body')).toHaveCSS('overflow-x', /^(visible|auto|clip)$/);
});

import {test, expect} from '@playwright/test';
import {completeProfile, healthcheck, progressToResults} from './helpers';

test('completed report offers a customer resend control', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);
  await progressToResults(page, 'Yes');

  await app.getByLabel('First name').fill('Resend');
  await app.getByLabel('Last name').fill('Tester');
  await app.getByLabel('Company').fill('Resend Test Ltd');
  await app.getByLabel('Work email').fill('resend@example.test');
  await app.getByRole('button', {name: 'View my full action plan'}).click();

  const report = page.locator('.acorn-hc__report');
  const button = report.getByRole('button', {name: 'Resend email'});
  await expect(button).toBeVisible();

  await page.route('**/wp-json/acorn-healthcheck/v1/reports/*/resend', async route => {
    await route.fulfill({status: 200, contentType: 'application/json', body: JSON.stringify({message: 'Your report email has been resent.'})});
  });

  await button.click();
  await expect(report.getByRole('status')).toContainText('Your report email has been resent.');
});

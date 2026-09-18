import {test, expect, Page} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import {completeProfile} from './helpers';

async function expectAccessible(page: Page, stage: string): Promise<void> {
  const response = await page.request.get(page.url());
  expect(response.status(), `${stage} page status`).toBe(200);
  const results = await new AxeBuilder({page}).withTags(['wcag2a', 'wcag2aa']).analyze();
  expect(results.violations, stage).toEqual([]);
}

test('all public stages meet WCAG A/AA and support keyboard operation', async ({page}) => {
  const response = await page.goto('/health-and-safety-healthcheck/');
  expect(response?.status()).toBe(200);
  await expect(page.locator('#acorn-healthcheck')).toBeVisible();
  await expectAccessible(page, 'landing');

  await page.getByRole('button', {name: 'Start my Healthcheck'}).focus();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('heading', {name: 'Your organisation'})).toBeFocused();
  await expectAccessible(page, 'profile');

  await completeProfile(page);
  await expect(page.getByText(/Question 1 of/)).toBeVisible();
  await expectAccessible(page, 'question');
  await page.getByLabel('Yes').focus();
  await page.keyboard.press('Space');
  await expect(page.getByText(/Question 2 of/)).toBeVisible();

  for (let i = 0; i < 30; i++) {
    if (await page.getByRole('heading', {name: 'Your Healthcheck is complete'}).isVisible().catch(() => false)) break;
    await page.getByLabel('Yes').check();
  }
  await expectAccessible(page, 'headline and contact');

  await page.getByLabel('First name').fill('Ada');
  await page.getByLabel('Last name').fill('Lovelace');
  await page.getByLabel('Company').fill('Accessible Ltd');
  await page.getByLabel('Work email').fill('accessibility@example.test');
  await page.getByRole('button', {name: 'View my full report'}).click();
  await expect(page.getByRole('heading', {name: 'Health & Safety Healthcheck Report'})).toBeVisible();
  await expectAccessible(page, 'report');
});

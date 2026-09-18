import {test, expect} from '@playwright/test';
import {answerCurrentQuestion, completeProfile, healthcheck} from './helpers';

test('visitor completes profile, relevant questions, headline gate and contact capture', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await expect(app.getByLabel('Work email')).not.toBeVisible();
  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();
  await completeProfile(page);
  await expect(app.getByRole('heading', {level: 2})).toHaveText('Do you have competent health and safety support in place?');

  for (let i = 0; i < 30; i++) {
    if (await app.getByRole('heading', {name: 'Your Healthcheck is complete'}).isVisible().catch(() => false)) break;
    await answerCurrentQuestion(page, 'Yes');
  }

  await expect(app.getByRole('heading', {name: 'Your Healthcheck is complete'})).toBeVisible();
  await expect(app.getByText(/Priority actions/)).toBeVisible();

  await app.getByLabel('First name').fill('Ada');
  await app.getByLabel('Last name').fill('Lovelace');
  await app.getByLabel('Company').fill('Analytical Ltd');
  await app.getByLabel('Work email').fill('ada@example.test');
  await app.getByRole('button', {name: 'View my full report'}).click();

  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'Your Health & Safety Healthcheck'})).toBeVisible();
  await expect(page.locator('.acorn-hc__report').getByRole('heading', {name: 'What you're already doing well'})).toBeVisible();
});

test('plugin assets are not loaded on unrelated pages', async ({page}) => {
  await page.goto('/');
  const sources = await page.locator('script,link').evaluateAll(nodes =>
    nodes.map(node => (node as HTMLScriptElement).src || (node as HTMLLinkElement).href),
  );
  expect(sources.join(' ')).not.toContain('healthcheck.js');
});

test('profile uses cleaner selectable cards without nested fieldset boxes', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();

  const jurisdiction = app.getByRole('group', {name: 'Jurisdiction'});
  await expect(jurisdiction).toBeVisible();
  expect(await jurisdiction.evaluate(el => getComputedStyle(el).borderTopWidth)).toBe('0px');

  const england = app.getByLabel('England');
  const card = england.locator('..');
  expect(await card.evaluate(el => getComputedStyle(el).borderRadius)).toBe('14px');

  await england.check();
  expect(await card.evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(234, 245, 241)');
});

test('resume landing gives Start again a subdued secondary treatment', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();
  await expect(app.getByRole('heading', {name: 'Your organisation'})).toBeVisible();
  await page.reload();

  const restart = app.getByRole('button', {name: 'Start again'});
  await expect(restart).toHaveClass(/acorn-hc__secondary/);
  expect(await restart.evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(243, 245, 244)');
});


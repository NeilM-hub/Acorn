import {expect, type Locator, type Page} from '@playwright/test';

export const healthcheck = (page: Page): Locator => page.locator('#acorn-healthcheck');

export const questionAnswer = (page: Page, label: 'Yes' | 'Partly' | 'No' | 'Not sure'): Locator =>
  healthcheck(page)
    .getByRole('group', {name: 'Choose an answer'})
    .getByLabel(label, {exact: true});

export async function waitForQuestion(page: Page): Promise<void> {
  await expect(healthcheck(page).getByRole('group', {name: 'Choose an answer'})).toBeVisible();
}

export async function answerCurrentQuestion(
  page: Page,
  label: 'Yes' | 'Partly' | 'No' | 'Not sure',
): Promise<void> {
  const app = healthcheck(page);
  const heading = app.getByRole('heading', {level: 2});
  await expect(heading).toBeVisible();
  const previousHeading = await heading.textContent();

  await questionAnswer(page, label).check();

  await expect
    .poll(async () => (await app.getByRole('heading', {level: 2}).textContent().catch(() => '')) ?? '', {timeout: 5000})
    .not.toBe(previousHeading ?? '');
}

export async function completeProfile(page: Page, employeeLabel = '1–4'): Promise<void> {
  const app = healthcheck(page);

  await app.getByLabel('England').check();
  await app.getByLabel(employeeLabel, {exact: true}).check();
  await app.getByRole('button', {name: 'Start the questions'}).click();

  await waitForQuestion(page);
}

export async function completeTailoring(page: Page, employeeLabel = '1–4'): Promise<void> {
  await completeProfile(page, employeeLabel);
}

export async function progressToResults(
  page: Page,
  answer: 'Yes' | 'Partly' | 'No' | 'Not sure' = 'Yes',
): Promise<void> {
  const app = healthcheck(page);

  for (let i = 0; i < 40; i++) {
    if (await app.getByRole('heading', {name: "Here's where things stand"}).isVisible().catch(() => false)) {
      return;
    }

    const gate = app.locator('form[data-form="gate"]');
    if (await gate.isVisible().catch(() => false)) {
      await gate.getByLabel('No', {exact: true}).check();
      await gate.getByRole('button', {name: 'Continue'}).click();
      continue;
    }

    const risks = app.locator('form[data-form="risks"]');
    if (await risks.isVisible().catch(() => false)) {
      await risks.getByLabel('None of these', {exact: true}).check();
      await risks.getByRole('button', {name: 'Continue'}).click();
      continue;
    }

    if (await app.getByRole('group', {name: 'Choose an answer'}).isVisible().catch(() => false)) {
      await answerCurrentQuestion(page, answer);
      continue;
    }

    await page.waitForTimeout(100);
  }

  throw new Error('Healthcheck did not reach results within the expected number of steps.');
}

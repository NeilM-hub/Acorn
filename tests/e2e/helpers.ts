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
    .poll(async () => (await app.getByRole('heading', {level: 2}).textContent().catch(() => '')) ?? '')
    .not.toBe(previousHeading ?? '');
}

export async function completeProfile(page: Page): Promise<void> {
  const app = healthcheck(page);

  await app.getByLabel('England').check();
  await app.getByLabel('1–4').check();
  await app.getByLabel('Organisation type / sector').selectOption('office_professional');
  await app.getByLabel('Office', {exact: true}).check();
  await app.getByRole('group', {name: 'Responsibility for premises'}).getByLabel('Yes', {exact: true}).check();
  await app.getByRole('group', {name: 'Shared premises'}).getByLabel('Yes', {exact: true}).check();
  await app.getByLabel('Display screen equipment').check();
  await app.getByRole('group', {name: 'Responsibility for hot/cold water systems'}).getByLabel('No', {exact: true}).check();
  await app.getByRole('group', {name: 'Responsibility for maintenance or repair'}).getByLabel('No', {exact: true}).check();
  await app.getByLabel('Not relevant', {exact: true}).check();
  await app.getByRole('group', {name: 'Is intrusive work planned?'}).getByLabel('No', {exact: true}).check();
  await app.getByRole('button', {name: 'Continue to questions'}).click();

  await waitForQuestion(page);
}

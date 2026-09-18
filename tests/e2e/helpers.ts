import type {Page} from '@playwright/test';

export async function completeProfile(page: Page): Promise<void> {
  await page.getByLabel('England').check();
  await page.getByLabel('1–4').check();
  await page.getByLabel('Organisation type / sector').selectOption('office_professional');
  await page.getByLabel('Office', {exact: true}).check();
  await page.getByRole('group', {name: 'Responsibility for premises'}).getByLabel('Yes').check();
  await page.getByRole('group', {name: 'Shared premises'}).getByLabel('Yes').check();
  await page.getByLabel('Display screen equipment').check();
  await page.getByRole('group', {name: 'Responsibility for hot/cold water systems'}).getByLabel('No').check();
  await page.getByRole('group', {name: 'Responsibility for maintenance or repair'}).getByLabel('No').check();
  await page.getByLabel('Not relevant').check();
  await page.getByRole('group', {name: 'Is intrusive work planned?'}).getByLabel('No').check();
  await page.getByRole('button', {name: 'Continue to questions'}).click();
}

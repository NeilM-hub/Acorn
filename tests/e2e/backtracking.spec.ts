import {test, expect} from '@playwright/test';
import {answerCurrentQuestion, completeProfile, healthcheck, questionAnswer, waitForQuestion} from './helpers';

test('online reload offers resume without storing contact data', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  await healthcheck(page).getByRole('button', {name: 'Start my Healthcheck'}).click();
  await page.reload();
  await expect(healthcheck(page).getByRole('button', {name: 'Continue Healthcheck'})).toBeVisible();

  const saved = await page.evaluate(() => localStorage.getItem('acorn_hc_session_v1'));
  expect(saved).not.toContain('email');
  expect(saved).not.toContain('telephone');
});

test('network failure queues an answer and reconnect flushes it', async ({page, context}) => {
  await page.goto('/health-and-safety-healthcheck/');
  await healthcheck(page).getByRole('button', {name: 'Start my Healthcheck'}).click();
  await completeProfile(page);
  await expect(healthcheck(page).getByText(/Question 1 of/)).toBeVisible();

  await context.setOffline(true);
  await questionAnswer(page, 'Partly').check();
  await expect(healthcheck(page).getByText('Working offline — progress kept on this device')).toBeVisible();
  await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}').pending?.length || 0)).toBe(1);

  await context.setOffline(false);
  await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}').pending?.length || 0)).toBe(0);
  await expect(healthcheck(page).getByText(/Question 2 of/)).toBeVisible();
});

test('Back restores the previous question and selected answer', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();
  await completeProfile(page);

  await answerCurrentQuestion(page, 'Partly');
  const secondQuestion = await app.getByRole('heading', {level: 2}).textContent();

  await answerCurrentQuestion(page, 'Yes');
  await app.getByRole('button', {name: 'Back'}).click();

  await expect(app.getByRole('heading', {level: 2})).toHaveText(secondQuestion || '');
  await expect(questionAnswer(page, 'Yes')).toBeChecked();
});

test('editing profile removes a conditional module and its hidden answer', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: 'Start my Healthcheck'}).click();
  await completeProfile(page);

  await app.getByRole('button', {name: 'Edit business profile'}).click();
  await expect(app.getByRole('heading', {name: 'Your organisation'})).toBeVisible();
  await app.getByLabel('Work at height').check();
  await app.getByLabel('Driving for work').check();
  await app.getByRole('button', {name: 'Continue to questions'}).click();
  await waitForQuestion(page);

  for (let i = 0; i < 30; i++) {
    const heading = await app.getByRole('heading', {level: 2}).textContent();
    if (heading?.includes('work at height')) break;
    await answerCurrentQuestion(page, 'Yes');
  }

  await expect(app.getByRole('heading', {level: 2})).toContainText('work at height');
  await answerCurrentQuestion(page, 'No');

  await app.getByRole('button', {name: 'Edit business profile'}).click();
  await expect(app.getByRole('heading', {name: 'Your organisation'})).toBeVisible();
  await app.getByLabel('Work at height').uncheck();
  await app.getByRole('button', {name: 'Continue to questions'}).click();
  await waitForQuestion(page);

  const state = await page.evaluate(async () => {
    const session = JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}');
    const response = await fetch(`${(window as any).acornHealthcheck.rest}/assessments/${session.token}`);
    return response.json();
  });

  expect(state.questions.map((question: {key: string}) => question.key)).not.toContain('WAH01_WORK_AT_HEIGHT');
  expect(state.answers).not.toHaveProperty('WAH01_WORK_AT_HEIGHT');
});

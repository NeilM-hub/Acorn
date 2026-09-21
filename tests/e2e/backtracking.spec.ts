import {test, expect} from '@playwright/test';
import {answerCurrentQuestion, completeProfile, healthcheck, questionAnswer} from './helpers';

test('online reload offers resume without storing contact data', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  await healthcheck(page).getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await expect(healthcheck(page).getByRole('heading', {name: 'A couple of details so we can tailor the Healthcheck'})).toBeVisible();
  await page.reload();
  await expect(healthcheck(page).getByRole('button', {name: 'Continue Healthcheck'})).toBeVisible();

  const saved = await page.evaluate(() => localStorage.getItem('acorn_hc_session_v1'));
  expect(saved).not.toContain('email');
  expect(saved).not.toContain('telephone');
});

test('network failure queues an answer and reconnect flushes it', async ({page, context}) => {
  await page.goto('/health-and-safety-healthcheck/');
  await healthcheck(page).getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);

  await context.setOffline(true);
  await questionAnswer(page, 'Partly').check();
  await expect(healthcheck(page).getByText('Working offline — progress kept on this device')).toBeVisible();
  await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}').pending?.length || 0)).toBe(1);

  await context.setOffline(false);
  await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}').pending?.length || 0)).toBe(0);
});

test('Back restores the previous question and selected answer', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);

  await answerCurrentQuestion(page, 'Partly');
  const secondQuestion = await app.getByRole('heading', {level: 2}).textContent();

  await answerCurrentQuestion(page, 'Yes');
  await app.getByRole('button', {name: 'Back'}).click();

  await expect(app.getByRole('heading', {level: 2})).toHaveText(secondQuestion || '');
  await expect(questionAnswer(page, 'Yes')).toBeChecked();
});

test('changing progressive context removes a hidden conditional answer', async ({page}) => {
  await page.goto('/health-and-safety-healthcheck/');
  const app = healthcheck(page);

  await app.getByRole('button', {name: /Start my.*Healthcheck/}).first().click();
  await completeProfile(page);

  const session = await page.evaluate(() => JSON.parse(localStorage.getItem('acorn_hc_session_v1') || '{}'));
  const rest = await page.evaluate(() => (window as any).acornHealthcheck.rest);

  await page.evaluate(async ({rest, token}) => {
    await fetch(`${rest}/assessments/${token}/profile`, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({fire_safety_responsibility: 'yes'}),
    });
    await fetch(`${rest}/assessments/${token}/answers/F02_FIRE_RA`, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({answer: 'no'}),
    });
    await fetch(`${rest}/assessments/${token}/profile`, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({fire_safety_responsibility: 'no'}),
    });
  }, {rest, token: session.token});

  const state = await page.evaluate(async ({rest, token}) => {
    const response = await fetch(`${rest}/assessments/${token}`);
    return response.json();
  }, {rest, token: session.token});

  expect(state.questions.map((question: {key: string}) => question.key)).not.toContain('F02_FIRE_RA');
  expect(state.answers).not.toHaveProperty('F02_FIRE_RA');
  await expect(app).toBeVisible();
});

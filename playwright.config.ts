import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  baseURL: 'http://localhost:8888',
  globalSetup: './tests/e2e/global-setup.ts',
  use: { baseURL: 'http://localhost:8888' },
});

import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e/live',
    fullyParallel: false,
    forbidOnly: true,
    retries: 0,
    workers: 1,
    reporter: [['list']],
    outputDir: 'storage/framework/testing/playwright-live',
    use: {
        ...devices['Desktop Chrome'],
        colorScheme: 'dark',
        locale: 'tr-TR',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
});

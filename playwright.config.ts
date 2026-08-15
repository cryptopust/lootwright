import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [['list']],
    outputDir: 'storage/framework/testing/playwright',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        colorScheme: 'dark',
        locale: 'tr-TR',
        permissions: ['clipboard-write'],
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
    expect: {
        toHaveScreenshot: {
            animations: 'disabled',
            maxDiffPixelRatio: 0.01,
        },
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: [
        {
            command:
                'php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
            cwd: './public',
            url: 'http://127.0.0.1:8000',
            env: {
                ...process.env,
                CACHE_STORE: 'array',
                QUEUE_CONNECTION: 'sync',
                SESSION_DRIVER: 'array',
            },
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
        {
            command: 'npm run dev -- --host 127.0.0.1',
            url: 'http://127.0.0.1:5173/@vite/client',
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
    ],
});

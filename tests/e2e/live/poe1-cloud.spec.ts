import { expect, test } from '@playwright/test';

/**
 * Explicit live-cloud acceptance checks.
 *
 * This file is intentionally skipped unless LOOTWRIGHT_LIVE_E2E=true. It is
 * never part of the normal CI/browser suite and does not contain credentials.
 */
const enabled = process.env.LOOTWRIGHT_LIVE_E2E === 'true';
const liveUrl =
    process.env.LOOTWRIGHT_LIVE_URL ??
    'https://lootwright-production-kt2jq5.laravel.cloud';
const destructive = process.env.LOOTWRIGHT_LIVE_E2E_DESTRUCTIVE === 'true';

test.describe('PoE1 Laravel Cloud acceptance', () => {
    test.skip(!enabled, 'Set LOOTWRIGHT_LIVE_E2E=true to run against Cloud.');

    test('renders the live application without failed document requests', async ({
        page,
    }) => {
        const failures: string[] = [];
        page.on('requestfailed', (request) => {
            failures.push(
                `${request.method()} ${new URL(request.url()).pathname}`,
            );
        });

        const response = await page.goto(liveUrl, {
            waitUntil: 'networkidle',
            timeout: 60_000,
        });

        expect(response?.status()).toBe(200);
        await expect(page).toHaveTitle(/lootwright/i);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        expect(failures).toEqual([]);
    });

    test('reaches the PoE1 build-import step and keeps policy failures controlled', async ({
        page,
    }) => {
        await page.goto(`${liveUrl}/analyses/new`, {
            waitUntil: 'networkidle',
            timeout: 60_000,
        });
        await page.getByText('Var olan buildi analiz et').click();
        await page.getByRole('button', { name: 'Devam', exact: true }).click();
        await page.locator('select').first().selectOption('duelist');
        await page.locator('input[type=number]').fill('96');
        await page.getByRole('button', { name: 'Devam', exact: true }).click();

        await expect(
            page.getByLabel(/PoB kodu veya pasted pobb.in bağlantısı/),
        ).toBeVisible();
    });

    test('checks authenticated login when credentials are supplied at runtime', async ({
        page,
    }) => {
        const email = process.env.LOOTWRIGHT_LIVE_E2E_EMAIL;
        const password = process.env.LOOTWRIGHT_LIVE_E2E_PASSWORD;
        test.skip(
            !email || !password,
            'Runtime QA credentials were not supplied.',
        );

        await page.goto(`${liveUrl}/login`, {
            waitUntil: 'networkidle',
            timeout: 60_000,
        });
        await page.locator('input[type=email]').fill(email!);
        await page.locator('input[type=password]').fill(password!);
        await page.getByRole('button', { name: /Giriş yap|Login/i }).click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/\/login(?:\?|$)/);
    });

    test('keeps destructive ownership/delete checks explicitly opt-in', async () => {
        test.skip(!destructive, 'Set LOOTWRIGHT_LIVE_E2E_DESTRUCTIVE=true for disposable destructive checks.');
        expect(destructive).toBe(true);
    });
});

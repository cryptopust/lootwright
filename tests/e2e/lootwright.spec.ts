import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

async function expectNoHorizontalOverflow(page: Page): Promise<void> {
    const dimensions = await page.evaluate(() => ({
        clientWidth: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
    }));

    expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.clientWidth);
}

test('completes the fake analysis review flow with local validation', async ({
    page,
}) => {
    await page.goto('/');
    // Wait for the destination wizard to hydrate before exercising its local
    // validation handler. A merely visible button can still be pre-hydration.
    await page.locator('a[href="/analyses/new"]').last().click();
    await page.locator('textarea').first().waitFor();

    await page.getByRole('button', { name: 'Devam', exact: true }).click();
    await expect(page.getByRole('alert')).toContainText('en az 12 karakterlik');

    await page.getByRole('textbox').fill('eNrtFixtureBuildInput');
    await page.getByRole('button', { name: 'Devam' }).click();
    await page
        .getByRole('textbox', { name: 'Ne elde etmek istiyorsun?' })
        .fill('Haritalamada daha dayanıklı olmak istiyorum.');
    await page.getByRole('button', { name: 'Devam' }).click();
    await page
        .getByRole('checkbox', {
            name: /İşleme açıklamasını okudum/,
        })
        .check();
    await page.getByRole('button', { name: 'Devam' }).click();
    await expect(
        page.getByRole('group', { name: 'Gönderim öncesi doğrulama' }),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'Fixture incelemesini hazırla' })
        .click();

    await expect(page.getByRole('status')).toContainText(
        'Fixture import incelemesi hazır',
    );
    await page.getByRole('link', { name: 'Import incelemesine geç' }).click();
    await expect(page).toHaveURL('/analyses/demo/import');
    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Parser ne gördü?',
    );
});

test('exposes evidence and keeps manual Trade actions within policy', async ({
    page,
}) => {
    await page.goto('/analyses/demo/findings');
    const firstWhy = page.getByRole('button', { name: 'Neden?' }).first();
    await firstWhy.click();
    await expect(firstWhy).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('.evidence-body').first()).toContainText(
        'poe1.defence.elemental_resistance.minimum',
    );

    await page.goto('/analyses/demo/trade');
    const tradeLink = page.getByRole('link', {
        name: 'Resmî PoE1 Trade ana sayfasını aç',
    });
    await expect(tradeLink).toHaveAttribute(
        'href',
        'https://www.pathofexile.com/trade',
    );
    await expect(page.locator('body')).not.toContainText('/api/trade/');
    await page.getByRole('button', { name: 'Geniş fallback' }).click();
    await expect(page.locator('.recipe-sheet')).toContainText('min 70');
    await page
        .getByRole('button', { name: 'Düz metin tarifi kopyala' })
        .click();
    await expect(page.locator('.copy-status')).toContainText(
        'URL kopyalanmadı',
    );
});

test('switches the localized shell without changing result identity', async ({
    page,
}) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'EN', exact: true }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(
        'Build decisions',
    );
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByLabel('Path of Exile 1').first()).toBeVisible();
});

test('supports keyboard entry and a 320 pixel viewport', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto('/');
    await page.keyboard.press('Tab');

    await expect(page.getByRole('link', { name: 'İçeriğe geç' })).toBeFocused();
    await expectNoHorizontalOverflow(page);
});

for (const visual of [
    {
        name: 'landing-mobile',
        path: '/',
        viewport: { width: 390, height: 844 },
        snapshot: 'landing-390.png',
    },
    {
        name: 'wizard-tablet',
        path: '/analyses/new',
        viewport: { width: 768, height: 1024 },
        snapshot: 'wizard-768.png',
    },
    {
        name: 'trade-desktop',
        path: '/analyses/demo/trade',
        viewport: { width: 1440, height: 1000 },
        snapshot: 'trade-1440.png',
    },
] as const) {
    test(`matches the ${visual.name} responsive fixture`, async ({ page }) => {
        await page.setViewportSize(visual.viewport);
        await page.goto(visual.path);
        await expectNoHorizontalOverflow(page);
        await expect(page).toHaveScreenshot(visual.snapshot, {
            fullPage: true,
        });
    });
}

import fs from 'node:fs';
import zlib from 'node:zlib';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

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
const email = process.env.LOOTWRIGHT_LIVE_E2E_EMAIL;
const password = process.env.LOOTWRIGHT_LIVE_E2E_PASSWORD;
const qa2Email = process.env.LOOTWRIGHT_LIVE_E2E_QA2_EMAIL;
const qa2Password = process.env.LOOTWRIGHT_LIVE_E2E_QA2_PASSWORD;
const fixture = fs.readFileSync(
    'resources/acceptance/poe1-supported.xml',
    'utf8',
);

async function login(page: Page) {
    await page.goto(`${liveUrl}/login`, {
        waitUntil: 'networkidle',
        timeout: 60_000,
    });
    await page.locator('input[type=email]').fill(email!);
    await page.locator('input[type=password]').fill(password!);
    await page.getByRole('button', { name: /Giriş yap|Login/i }).click();
    await page.waitForLoadState('networkidle');
    await expect(page).not.toHaveURL(/\/login(?:\?|$)/);
}

async function submitBuild(page: Page, xml: string): Promise<any> {
    await page.goto(`${liveUrl}/analyses/new`, {
        waitUntil: 'networkidle',
        timeout: 60_000,
    });
    await page.getByText('Var olan buildi analiz et').click();
    await page.getByRole('button', { name: 'Devam', exact: true }).click();
    await page.locator('select').first().selectOption('duelist');
    await page.locator('input[type=number]').fill('96');
    await page.getByRole('button', { name: 'Devam', exact: true }).click();
    await page
        .getByLabel(/PoB kodu veya pasted pobb.in/)
        .fill(zlib.deflateSync(Buffer.from(xml)).toString('base64url'));
    await page.getByRole('button', { name: 'Devam', exact: true }).click();

    for (let i = 0; i < 4; i += 1) {
        await page.getByRole('button', { name: 'Devam', exact: true }).click();
    }

    await page.locator('input[type=checkbox]').last().check();
    await page.getByRole('button', { name: 'Devam', exact: true }).click();
    const responsePromise = page.waitForResponse((response: any) =>
        response.url().includes('/api/analyses/wizard'),
    );
    await page.getByRole('button', { name: /Analizi|Gönder|Başlat/i }).click();
    const submission = await (await responsePromise).json();

    for (let i = 0; i < 120; i += 1) {
        await page.waitForTimeout(1000);
        const payload = await (
            await page.request.get(
                `${liveUrl}/api/analyses/${submission.analysis_id}`,
            )
        ).json();

        if (['completed', 'failed'].includes(payload?.analysis?.state)) {
            return payload.analysis;
        }
    }

    throw new Error('Live analysis did not reach a terminal state.');
}

function variant(kind: string): string {
    if (kind === 'low-resistance') {
        return fixture.replace(
            'FireResist" value="75',
            'FireResist" value="50',
        );
    }

    if (kind === 'attribute') {
        return fixture.replace(
            '</Build>',
            '<PlayerStat stat="Strength" value="20"/><PlayerStat stat="StrengthRequirement" value="100"/></Build>',
        );
    }

    if (kind === 'ci') {
        return fixture.replace('<Spec nodes=""/>', '<Spec nodes="11455"/>');
    }

    if (kind === 'rt') {
        return fixture.replace('<Spec nodes=""/>', '<Spec nodes="31961"/>');
    }

    return fixture;
}

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
        test.skip(
            !destructive,
            'Set LOOTWRIGHT_LIVE_E2E_DESTRUCTIVE=true for disposable destructive checks.',
        );
        expect(destructive).toBe(true);
    });

    test('runs canonical CI and suppresses generic life advice', async ({
        page,
    }) => {
        test.skip(
            !email || !password,
            'Runtime QA credentials were not supplied.',
        );
        await login(page);
        const analysis = await submitBuild(page, variant('ci'));
        expect(analysis.output.build_summary.keystones).toContain(
            'Chaos Inoculation',
        );
        const codes = (analysis.output.findings ?? []).map(
            (item: any) => item.code,
        );
        expect(codes).not.toContain('defence.life.below_content_profile');
        expect(
            (analysis.output.recommendations ?? [])
                .map((item: any) => item.code)
                .join(' '),
        ).not.toMatch(/life/i);
    });

    test('runs canonical Resolute Technique and suppresses crit advice', async ({
        page,
    }) => {
        test.skip(
            !email || !password,
            'Runtime QA credentials were not supplied.',
        );
        await login(page);
        const analysis = await submitBuild(page, variant('rt'));
        expect(analysis.output.build_summary.keystones).toContain(
            'Resolute Technique',
        );
        const text = JSON.stringify({
            findings: analysis.output.findings,
            recommendations: analysis.output.recommendations,
        });
        expect(text).not.toMatch(
            /critical strike chance|critical strike multiplier|crit-dependent/i,
        );
    });

    test('proves low-resistance actionable finding, planner, recipe and save/reload', async ({
        page,
    }) => {
        test.skip(
            !email || !password,
            'Runtime QA credentials were not supplied.',
        );
        await login(page);
        const analysis = await submitBuild(page, variant('low-resistance'));
        expect(
            analysis.output.findings.map((item: any) => item.code),
        ).toContain('defence.fire_resistance.below_reported_max');
        expect(analysis.output.recommendations.length).toBeGreaterThan(0);
        expect(analysis.output.manual_trade_recipes.length).toBeGreaterThan(0);
        const token = await page
            .locator('meta[name=csrf-token]')
            .getAttribute('content');
        const saved = await page.request.post(
            `${liveUrl}/api/saved/analyses/${analysis.id}`,
            { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token! } },
        );
        expect(saved.ok()).toBeTruthy();
        const reloaded = await (
            await page.request.get(`${liveUrl}/api/analyses/${analysis.id}`)
        ).json();
        expect(reloaded.analysis.id).toBe(analysis.id);
        expect(reloaded.analysis.ruleset.id).toBe(analysis.ruleset.id);
        expect(
            reloaded.analysis.output.manual_trade_recipes.length,
        ).toBeGreaterThan(0);
        test.skip(
            !qa2Email || !qa2Password,
            'Runtime QA User 2 credentials were not supplied.',
        );
        const other = await page.context().browser()!.newContext();
        const otherPage = await other.newPage();
        await otherPage.goto(`${liveUrl}/login`);
        await otherPage.locator('input[type=email]').fill(qa2Email!);
        await otherPage.locator('input[type=password]').fill(qa2Password!);
        await otherPage.locator('button').last().click();
        await otherPage.waitForLoadState('networkidle');
        expect(
            (
                await otherPage.request.get(
                    `${liveUrl}/api/analyses/${analysis.id}`,
                )
            ).status(),
        ).toBe(404);
        expect(
            (
                await otherPage.request.get(
                    `${liveUrl}/api/analyses/${analysis.id}/export`,
                )
            ).status(),
        ).toBe(404);
        await other.close();
    });

    test('proves attribute deficiency finding and actionable planner/recipe', async ({
        page,
    }) => {
        test.skip(
            !email || !password,
            'Runtime QA credentials were not supplied.',
        );
        await login(page);
        const analysis = await submitBuild(page, variant('attribute'));
        expect(
            analysis.output.findings.map((item: any) => item.code),
        ).toContain('attributes.requirement.missing');
        expect(analysis.output.recommendations.length).toBeGreaterThan(0);
        expect(analysis.output.manual_trade_recipes.length).toBeGreaterThan(0);
    });

    test('proves owner-confirmed deletion and persistence for disposable analysis', async ({
        page,
    }) => {
        test.skip(
            !destructive || !email || !password,
            'Set LOOTWRIGHT_LIVE_E2E_DESTRUCTIVE=true with QA credentials.',
        );
        await login(page);
        const analysis = await submitBuild(page, fixture);
        await page.goto(`${liveUrl}/analyses/${analysis.id}`, {
            waitUntil: 'networkidle',
        });
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Analizi sil' }).click();
        await page.waitForURL(/\/user\/confirm-password/);
        await page.locator('input[type=password]').fill(password!);
        const confirmation = page.waitForResponse((response) =>
            response.url().includes('/user/confirm-password'),
        );
        await page.getByRole('button', { name: 'Devam et' }).click();
        const confirmationResponse = await confirmation;
        expect(confirmationResponse.status()).toBeLessThan(400);
        await page.waitForTimeout(1_000);
        await page.goto(`${liveUrl}/analyses/${analysis.id}`, {
            waitUntil: 'networkidle',
        });
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Analizi sil' }).click();
        await page.waitForTimeout(2_000);
        expect(
            (
                await page.request.get(`${liveUrl}/api/analyses/${analysis.id}`)
            ).status(),
        ).toBe(404);
        expect(
            (
                await page.request.get(
                    `${liveUrl}/api/analyses/${analysis.id}/export`,
                )
            ).status(),
        ).toBe(404);
        await page.reload();
        expect(page.url()).toMatch(/\/analyses/);
    });
});

import fs from 'node:fs';
import zlib from 'node:zlib';
import { chromium } from 'playwright';

const base = 'https://lootwright-production-kt2jq5.laravel.cloud';
const qa = JSON.parse(
    fs.readFileSync(`${process.env.TEMP}\\lootwright-qa-runtime.json`, 'utf8'),
);
const xml = fs.readFileSync('resources/acceptance/poe1-supported.xml');
const pob = zlib.deflateSync(xml).toString('base64url');
const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
await page.locator('input[type=email]').fill(qa.email);
await page.locator('input[type=password]').fill(qa.password);
await page.getByRole('button', { name: /Giri|Login/i }).click();
await page.waitForURL(/\/dashboard/);
await page.goto(`${base}/analyses/new`, { waitUntil: 'networkidle' });
await page.getByText('Var olan buildi analiz et').click();
await page.getByRole('button', { name: 'Devam', exact: true }).click();
await page.locator('select').first().selectOption('duelist');
await page.locator('input[type=number]').fill('96');
await page.getByRole('button', { name: 'Devam', exact: true }).click();
await page.getByLabel(/PoB kodu veya pasted pobb.in/).fill(pob);
await page.getByRole('button', { name: 'Devam', exact: true }).click();

for (let index = 0; index < 4; index += 1) {
    await page.getByRole('button', { name: 'Devam', exact: true }).click();
}

await page.locator('input[type=checkbox]').last().check();
await page.getByRole('button', { name: 'Devam', exact: true }).click();
const pending = page.waitForResponse((response) =>
    response.url().includes('/api/analyses/wizard'),
);
await page.getByRole('button', { name: /Analizi|G.nd(er|er)|Ba.lat/i }).click();
const response = await pending;
const submission = await response.json();
console.log(
    JSON.stringify({
        event: 'IMPORT_ACCEPTED',
        http: response.status(),
        analysis_id: submission.analysis_id,
        state: submission.status,
    }),
);

const seen = new Set();
let body;

for (let index = 0; index < 90; index += 1) {
    await page.waitForTimeout(1000);
    const polled = await page.request.get(
        `${base}/api/analyses/${submission.analysis_id}`,
    );
    body = await polled.json();
    const state = body?.analysis?.state ?? body?.state;

    if (!seen.has(state)) {
        seen.add(state);
        console.log(JSON.stringify({ event: 'STATE', state }));
    }

    if (['completed', 'failed'].includes(state)) {
        break;
    }
}

const analysis = body.analysis;
const output = analysis.output ?? {};
console.log(
    JSON.stringify({
        event: 'SUMMARY',
        analysis_id: analysis.id,
        state: analysis.state,
        failure_code: analysis.failure_code,
        ruleset: analysis.ruleset,
        output_keys: Object.keys(output),
        findings: output.findings?.length ?? null,
        recommendations: output.recommendations?.length ?? null,
        recipes: output.recipes?.length ?? output.trade_recipes?.length ?? null,
        output,
    }),
);
await browser.close();

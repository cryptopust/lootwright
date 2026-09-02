import { chromium } from 'playwright';
import fs from 'node:fs';
import zlib from 'node:zlib';
const base='https://lootwright-production-kt2jq5.laravel.cloud';
const qa=JSON.parse(fs.readFileSync(process.env.TEMP+'\\lootwright-qa-runtime.json','utf8'));
const xml=fs.readFileSync('resources/acceptance/poe1-supported.xml');
const pob=zlib.deflateSync(xml).toString('base64url');
const browser=await chromium.launch({headless:true}); const page=await browser.newPage();
await page.goto(base+'/login',{waitUntil:'networkidle'}); await page.locator('input[type=email]').fill(qa.email); await page.locator('input[type=password]').fill(qa.password); await page.getByRole('button',{name:/Giri|Login/i}).click(); await page.waitForLoadState('networkidle');
await page.goto(base+'/analyses/new',{waitUntil:'networkidle'});
await page.getByText('Var olan buildi analiz et').click(); await page.getByRole('button',{name:'Devam',exact:true}).click();
await page.locator('select').first().selectOption('duelist'); await page.locator('input[type=number]').fill('96'); await page.getByRole('button',{name:'Devam',exact:true}).click();
const pobField=page.getByLabel(/PoB kodu veya pasted pobb.in/); await pobField.fill(pob); await page.getByRole('button',{name:'Devam',exact:true}).click();
for(let i=0;i<4;i++){ await page.getByRole('button',{name:'Devam',exact:true}).click(); }
await page.getByLabel(/İşleme ve süreli saklama/).check(); await page.getByRole('button',{name:'Devam',exact:true}).click();
const submitResponse=page.waitForResponse(r=>r.url().includes('/api/analyses/wizard')); await page.getByRole('button',{name:/Analizi|Gönder|Başlat/i}).click(); const sr=await submitResponse; const body=await sr.json(); console.log('submit',sr.status(),body);
const id=body.analysis_id; let result=null; for(let i=0;i<20;i++){ await page.waitForTimeout(1500); const rr=await page.request.get(base+'/api/analyses/'+id); result={status:rr.status(),body:await rr.json()}; console.log('poll',i,result.status,result.body?.analysis?.state??result.body?.state); if((result.body?.analysis?.state??result.body?.state)==='completed')break; }
console.log('result',JSON.stringify(result).slice(0,3000));
await browser.close();

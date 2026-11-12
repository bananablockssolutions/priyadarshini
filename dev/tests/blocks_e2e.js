// Browser test: shared state between blocks, optimistic write, rollback, no-JS fallback.
// Usage: PD_TESTS_WIPE=yes node blocks_e2e.js <pageId> <a1> <a2>   (site at http://127.0.0.1:8088)
const { chromium } = require('playwright');
const [pageId, A1, A2] = process.argv.slice(2);
const BASE = 'http://127.0.0.1:8088';
let fails = 0; const t = (ok, m) => { console.log((ok ? 'PASS ' : 'FAIL ') + m); if (!ok) fails = 1; };
const url = `${BASE}/?page_id=${pageId}`;
const txt = async (p, sel) => (await p.locator(sel).first().innerText()).trim();

async function login(ctx, user, pass) {
  const p = await ctx.newPage();
  await p.goto(`${BASE}/wp-login.php`); await p.fill('#user_login', user); await p.fill('#user_pass', pass); await p.click('#wp-submit');
  await p.waitForLoadState('networkidle'); await p.close();
}
const cardCount = (p, id) => p.locator(`[data-pd-entity-id="artisan-${id}"] .pd-artisan-card__followers`).innerText().then(s => s.trim());

(async () => {
  const browser = await chromium.launch();

  // ---- visitor
  { const ctx = await browser.newContext(); const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
    const html = await (await p.goto(url)).text();
    t(/"_nonce":""|\\"_nonce\\":\\"\\"/.test(html) || !/_nonce":"[a-z0-9]{6,}/.test(html), 'visitor page carries no REST nonce');
    t(await p.locator('[data-pd-entity="artisan"]').count() === 2, 'two artisan cards in the browser');
    t(await txt(p, '#mirror-a button') === 'Follow', 'visitor sees Follow');
    t((await p.locator('#mirror-a button').getAttribute('aria-pressed')) === 'false', 'visitor button aria-pressed=false');
    await p.click('#mirror-a button'); await p.waitForLoadState('domcontentloaded');
    t(/wp-login\.php/.test(p.url()), 'visitor click goes to sign-in (no silent state change): ' + p.url().slice(0, 60));
    t(errs.length === 0, 'no page errors for visitor ' + errs.join('|')); await ctx.close(); }

  // ---- signed-in buyer, JS on
  const ctx = await browser.newContext(); await login(ctx, 'buyer', 'pw-buyer-1');
  const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message)); await p.goto(url); await p.waitForFunction(() => document.querySelector('#mirror-a button'));
  t(await cardCount(p, A1) === '0 followers' && await cardCount(p, A2) === '4 followers', 'start: 0 and 4 followers on the cards');
  t(await txt(p, '#mirror-a button') === 'Follow' && await txt(p, '#mirror-b button') === 'Follow', 'both buttons for artisan 1 start as Follow');

  // slow the server so the optimistic state is observable
  let release; const gate = new Promise(r => release = r);
  await p.route('**/follow*', async route => { if (route.request().method() === 'POST') { await gate; } await route.continue(); });
  await p.click('#mirror-a button');
  await p.waitForFunction(() => document.querySelector('#mirror-a button').innerText.trim() === 'Following');
  t(await txt(p, '#mirror-b button') === 'Following', 'optimistic: the OTHER button for the same artisan updates at once (derived, not copied)');
  t(await cardCount(p, A1) === '1 follower', 'optimistic: the card count updates at once (1 follower)');
  t((await p.locator('#mirror-b button').getAttribute('aria-pressed')) === 'true', 'optimistic: aria-pressed true on the other button');
  t(await txt(p, '#other button') === 'Follow' && await cardCount(p, A2) === '4 followers', 'a different artisan is untouched');
  release(); await p.waitForLoadState('networkidle'); await p.waitForTimeout(300);
  t(await cardCount(p, A1) === '1 follower' && await txt(p, '#mirror-b button') === 'Following', 'after server reply: still 1 follower, Following');
  await p.unroute('**/follow*');
  await p.reload(); await p.waitForFunction(() => document.querySelector('#mirror-a button'));
  t(await txt(p, '#mirror-a button') === 'Following' && await cardCount(p, A1) === '1 follower', 'reload: server state matches (Following, 1 follower)');

  // failure rolls back everywhere
  await p.route('**/follow*', route => route.request().method() === 'POST' ? route.fulfill({ status: 500, body: '{}' }) : route.continue());
  await p.click('#other button'); await p.waitForTimeout(600);
  t(await txt(p, '#other button') === 'Follow' && await cardCount(p, A2) === '4 followers', 'failed request: button and count roll back');
  t((await p.locator('#other button').getAttribute('aria-pressed')) === 'false', 'failed request: aria-pressed back to false');
  await p.unroute('**/follow*');

  // rapid double click ends in the server's truth
  await p.click('#mirror-b button', { clickCount: 2, delay: 20 }); await p.waitForLoadState('networkidle'); await p.waitForTimeout(500);
  const shown = await txt(p, '#mirror-a button'); const cnt = await cardCount(p, A1);
  await p.reload(); await p.waitForFunction(() => document.querySelector('#mirror-a button'));
  t(shown === await txt(p, '#mirror-a button') && cnt === await cardCount(p, A1), `double click: page agrees with the server after reload (${shown}, ${cnt})`);
  t(errs.length === 0, 'no page errors for buyer ' + errs.join('|'));
  await ctx.close();

  // ---- no JavaScript: the form post path
  const nj = await browser.newContext({ javaScriptEnabled: false }); await login(nj, 'buyer2', 'pw-buyer2-1');
  const q = await nj.newPage(); await q.goto(url);
  const label = (await q.locator('#other button').innerText()).trim();
  t(label === 'Follow', 'no-JS: server renders "Follow" text (not blank)');
  await Promise.all([q.waitForNavigation(), q.click('#other button')]);
  t(/page_id=/.test(q.url()), 'no-JS: form post redirects back to the page');
  // the starting 4 was a hand-set cache; a real follow recounts from the follows table, which is the source of truth
  t((await q.locator('#other button').innerText()).trim() === 'Following' && /^1 follower$/.test(await cardCount(q, A2)), 'no-JS: now Following; count recomputed from the follows table (1 follower)');
  await nj.close(); await browser.close(); process.exit(fails);
})().catch(e => { console.error('ERROR', e); process.exit(2); });

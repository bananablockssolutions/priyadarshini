/* Illustrated menu: card to cover, Back flies home, Escape, phone, reduced motion, no JavaScript. Needs dev/seed-demo.php. Run: PD_URL=http://127.0.0.1:8088 node dev/tests/menu_e2e.js */
const { chromium } = require('playwright');
const B=process.env.PD_URL||'http://127.0.0.1:8088', O=process.env.PD_SHOTS||'/tmp/';
(async()=>{
 const br=await chromium.launch();
 const res=[]; const ok=(n,c)=>res.push((c?'PASS ':'FAIL ')+n);
 // desktop with motion
 let ctx=await br.newContext({viewport:{width:1280,height:800}}); let p=await ctx.newPage();
 const errs=[]; p.on('pageerror',e=>errs.push(e.message)); p.on('console',m=>m.type()==='error'&&errs.push(m.text()));
 await p.goto(B+'/?pd-nosplash'); await p.waitForTimeout(800);
 await p.click('#pd-shell-menu-btn'); await p.waitForTimeout(300);
 ok('menu opens', await p.isVisible('#pd-menu'));
 ok('aria-expanded true', (await p.getAttribute('#pd-shell-menu-btn','aria-expanded'))==='true');
 await p.screenshot({path:O+'menu-d.png'});
 const card=p.locator('#pd-menu a[href*="madhubani"]');
 await card.hover(); await p.waitForTimeout(250);
 await card.click();
 for (const t of [120,260,420]) { await p.waitForTimeout(t===120?120:140); await p.screenshot({path:O+`mid-${t}.png`}); }
 await p.waitForTimeout(900);
 ok('url is craft page', p.url().includes('/product-category/madhubani-painting/'));
 ok('menu closed', !(await p.isVisible('#pd-menu')));
 ok('cover h1', (await p.textContent('#pd-main h1')).includes('Madhubani'));
 ok('focus on h1', await p.evaluate(()=>document.activeElement?.tagName==='H1'));
 ok('works listed', (await p.locator('#pd-main .pd-work-card').count())>0);
 ok('makers listed', (await p.locator('#pd-main .pd-craft-makers .pd-artisan-card').count())>0);
 await p.screenshot({path:O+'craft-d.png',fullPage:true});
 await p.goBack(); await p.waitForTimeout(1000);
 ok('back: home', new URL(p.url()).pathname==='/');
 ok('back: menu reopened', await p.isVisible('#pd-menu'));
 ok('back: focus on card', await p.evaluate(()=>document.activeElement?.closest?.('.pd-menucard')?.href.includes('madhubani')));
 await p.screenshot({path:O+'back-d.png'});
 await p.keyboard.press('Escape'); await p.waitForTimeout(200);
 ok('escape closes', !(await p.isVisible('#pd-menu')));
 // Artisans section from menu
 await p.click('#pd-shell-menu-btn'); await p.click('#pd-menu a[href*="/artisans/"]'); await p.waitForTimeout(1200);
 ok('artisans cover', (await p.textContent('#pd-main h1')).includes('Artisans'));
 ok('no js errors', errs.length===0); if(errs.length) console.log(errs);
 await ctx.close();
 // mobile
 ctx=await br.newContext({viewport:{width:390,height:844},deviceScaleFactor:2}); p=await ctx.newPage();
 await p.goto(B+'/?pd-nosplash'); await p.waitForTimeout(600);
 await p.click('#pd-shell-menu-btn'); await p.waitForTimeout(300); await p.screenshot({path:O+'menu-m.png'});
 await p.click('#pd-menu a[href*="sikki"]'); await p.waitForTimeout(1300); await p.screenshot({path:O+'craft-m.png'});
 ok('mobile craft page', p.url().includes('sikki'));
 await ctx.close();
 // reduced motion
 ctx=await br.newContext({viewport:{width:1280,height:800},reducedMotion:'reduce'}); p=await ctx.newPage();
 await p.goto(B+'/?pd-nosplash'); await p.click('#pd-shell-menu-btn'); await p.click('#pd-menu a[href*="sujni"]');
 ok('reduced: arrived outline', !!(await p.waitForSelector('#pd-main .pd-cover .pd-arrived',{timeout:3000}).catch(()=>null)));
 await ctx.close();
 // no JS
 ctx=await br.newContext({viewport:{width:1280,height:800},javaScriptEnabled:false}); p=await ctx.newPage();
 await p.goto(B+'/?pd-nosplash'); await p.click('#pd-shell-menu-btn');
 ok('nojs: menu via :target', await p.isVisible('#pd-menu'));
 await p.click('#pd-menu a[href*="madhubani"]'); 
 ok('nojs: craft page', (await p.textContent('h1')).includes('Madhubani'));
 await ctx.close(); await br.close();
 console.log(res.join('\n'));
})();

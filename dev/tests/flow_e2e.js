/* Phone flows from the approved prototype (sketches 3 and 4): a work card grows into its work page and back, picks carry
   over, Buy flies a dot into the Bag, the Bag icon grows into the cart heading, Remove, and leaving the cart. Also the
   same steps without JavaScript and with reduced motion. Needs dev/seed-demo.php. Run: PD_URL=http://127.0.0.1:8088 node dev/tests/flow_e2e.js
   PD_VIDEO=/some/dir records the main run. */
const { chromium } = require( 'playwright' );
const B = process.env.PD_URL || 'http://127.0.0.1:8088';
let pass = 0, fail = 0;
const ok = ( c, m ) => { if ( c ) { pass++; console.log( 'PASS ' + m ); } else { fail++; console.log( 'FAIL ' + m ); } };
const wait = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

( async () => {
	const browser = await chromium.launch();
	const errors = [];
	const ctx = await browser.newContext( { viewport: { width: 390, height: 844 }, ...( process.env.PD_VIDEO ? { recordVideo: { dir: process.env.PD_VIDEO, size: { width: 390, height: 844 } } } : {} ) } );
	const p = await ctx.newPage();
	p.on( 'pageerror', ( e ) => errors.push( e.message ) );
	p.on( 'console', ( m ) => m.type() === 'error' && ! /favicon|404/.test( m.text() ) && errors.push( m.text() ) );
	await p.goto( B + '/product-category/kurti/', { waitUntil: 'networkidle' } );
	await p.evaluate( () => { document.querySelector( '.pd-splash' )?.remove(); window.__alive = 1; } );

	// card
	const card = p.locator( '.pd-work-card' ).first();
	ok( await card.locator( '.pd-pic' ).isVisible(), 'card: square picture' );
	ok( ( await card.locator( '.pd-pic__badge' ).count() ) === 1, 'card: badge' );
	ok( ! ( await card.locator( '.pd-maker-line' ).isVisible() ), 'card: no maker name' );
	ok( ( await card.locator( '.pd-dot' ).count() ) === 3 && ( await card.locator( '.pd-sz' ).count() ) === 4, 'card: colour dots and sizes' );
	const grid = await p.evaluate( () => getComputedStyle( document.querySelector( '.pd-shop .wc-block-product-template' ) ).gridTemplateColumns.split( ' ' ).length );
	ok( grid === 2, 'grid: two columns' );

	// pick, then open: the card's parts fly into the work page
	await card.locator( '.pd-dot' ).nth( 1 ).click();
	const picked = await card.locator( '.pd-dot input:checked' ).getAttribute( 'value' );
	ok( ( await card.locator( '.pd-work-card__link' ).getAttribute( 'href' ) ).includes( 'attribute_pa_colour=' + picked ), 'pick: travels with the link' );
	const title = ( await card.locator( '.pd-work-card__title' ).textContent() ).trim();
	await card.locator( '.pd-pic' ).click();
	await wait( 300 );
	ok( await p.evaluate( () => document.querySelectorAll( '.pd-fly' ).length > 0 ), 'open: parts are in flight' );
	await p.waitForSelector( '.pd-wpage__name', { state: 'visible' } );
	await wait( 900 );
	ok( await p.evaluate( () => window.__alive === 1 ), 'open: same document (no reload)' );
	ok( ( await p.locator( '.pd-wpage__name' ).textContent() ).trim() === title, 'open: card name became the h1' );
	ok( ( await p.locator( '.pd-wpage .pd-dot input:checked' ).getAttribute( 'value' ) ) === picked, 'open: pick carried over' );
	ok( await p.evaluate( () => document.activeElement?.classList.contains( 'pd-wpage__name' ) ), 'open: focus on the heading' );
	ok( await p.evaluate( () => ! document.querySelector( '.pd-fly' ) ), 'open: no copies left' );
	ok( ( await p.locator( '.pd-wpage__back' ).textContent() ).includes( 'Kurti' ), 'page: back link names the type' );

	// Buy on the page
	const before = parseInt( await p.locator( '.pd-bagc' ).textContent(), 10 ) || 0;
	await p.locator( '.pd-wpage .pd-pick__btn' ).click();
	await wait( 250 );
	ok( await p.evaluate( () => !! document.querySelector( '.pd-dotfly' ) ), 'buy: dot flies to the Bag' );
	await p.waitForFunction( ( n ) => parseInt( document.querySelector( '.pd-bagc' ).textContent, 10 ) === n + 1, before, { timeout: 5000 } ).catch( () => {} );
	ok( parseInt( await p.locator( '.pd-bagc' ).textContent(), 10 ) === before + 1, 'buy: count goes up' );
	await wait( 500 );
	ok( await p.locator( '.pd-toast.is-on' ).isVisible(), 'buy: "Added to bag"' );

	// Back link plays Back: the page shrinks into its card
	await p.locator( '.pd-wpage__back' ).click();
	await wait( 250 );
	ok( await p.evaluate( () => document.querySelectorAll( '.pd-fly' ).length > 0 ), 'back: parts are in flight' );
	await p.waitForURL( /product-category\/kurti/ );
	await wait( 900 );
	ok( await p.evaluate( () => ! document.querySelector( '.pd-fly' ) ), 'back: no copies left' );
	ok( await p.evaluate( () => document.activeElement?.closest( '.pd-work-card' ) !== null ), 'back: focus returns to the card' );

	// Bag to cart
	await p.locator( '.pd-work-card' ).nth( 1 ).locator( '.pd-pick__btn' ).click();
	await wait( 1300 );
	const count = parseInt( await p.locator( '.pd-bagc' ).textContent(), 10 );
	await p.locator( '.pd-baglink' ).click();
	await wait( 250 );
	ok( await p.evaluate( () => !! document.querySelector( '.pd-baglayer' ) ), 'cart: Bag icon is in flight' );
	await p.waitForSelector( '.pd-cart', { state: 'visible' } );
	await wait( 1000 );
	ok( await p.evaluate( () => window.__alive === 1 ), 'cart: same document (no reload)' );
	ok( await p.evaluate( () => document.documentElement.dataset.pdOnCart === '1' && getComputedStyle( document.querySelector( '.pd-baglink' ) ).visibility === 'hidden' ), 'cart: header shows only the menu' );
	ok( ( await p.locator( '.pd-crow' ).count() ) === count, 'cart: one row per piece' );
	ok( ( await p.locator( '.pd-cart__title' ).getAttribute( 'aria-label' ) ) === 'Cart', 'cart: heading reads Cart' );
	const meta = await p.locator( '.pd-crow__meta' ).first().textContent();
	ok( /S|M|L/.test( meta ), 'cart: size shown' );

	// Remove
	await p.locator( '.pd-crow__rm' ).first().click();
	await wait( 900 );
	ok( ( await p.locator( '.pd-crow:visible' ).count() ) === count - 1, 'remove: row goes' );
	ok( parseInt( await p.locator( '.pd-bagc' ).textContent(), 10 ) === count - 1, 'remove: count goes down' );

	// Leave: the icon goes back up
	await p.locator( '.pd-tabs a' ).first().click();
	await wait( 250 );
	ok( await p.evaluate( () => !! document.querySelector( '.pd-baglayer' ) ), 'leave: icon flies back up' );
	await wait( 1200 );
	ok( await p.evaluate( () => ! document.documentElement.dataset.pdOnCart && getComputedStyle( document.querySelector( '.pd-baglink' ) ).visibility === 'visible' && ! document.querySelector( '.pd-baglayer' ) ), 'leave: Bag back in the header' );
	ok( errors.length === 0, 'no js errors' + ( errors.length ? ': ' + errors.join( ' | ' ) : '' ) );
	await ctx.close();

	// reduced motion: same pages, no copies
	const r = await browser.newContext( { viewport: { width: 390, height: 844 }, reducedMotion: 'reduce' } );
	const q = await r.newPage();
	await q.goto( B + '/product-category/saree/', { waitUntil: 'networkidle' } );
	await q.evaluate( () => document.querySelector( '.pd-splash' )?.remove() );
	await q.locator( '.pd-work-card .pd-pic' ).first().click();
	await q.waitForSelector( '.pd-wpage__name', { state: 'visible' } );
	ok( await q.evaluate( () => ! document.querySelector( '.pd-fly' ) ), 'reduced: no flight' );
	await r.close();

	// without JavaScript: Buy posts the picked colour and size, the cart lists it, Remove works
	const n = await browser.newContext( { viewport: { width: 390, height: 844 }, javaScriptEnabled: false } );
	const s = await n.newPage();
	await s.goto( B + '/product-category/kurti/' );
	await s.locator( '.pd-work-card' ).first().locator( '.pd-sz' ).nth( 2 ).click();
	await s.locator( '.pd-work-card' ).first().locator( '.pd-pick__btn' ).click();
	await s.waitForLoadState();
	await s.goto( B + '/cart/' );
	ok( ( await s.locator( '.pd-crow' ).count() ) === 1 && /L/.test( await s.locator( '.pd-crow__meta' ).textContent() ), 'nojs: Buy adds the picked size' );
	await s.locator( '.pd-crow__rm' ).click();
	await s.waitForLoadState();
	ok( ( await s.locator( '.pd-crow' ).count() ) === 0, 'nojs: Remove' );
	await n.close();

	await browser.close();
	console.log( `${ pass } passed, ${ fail } failed` );
	process.exit( fail ? 1 : 0 );
} )();

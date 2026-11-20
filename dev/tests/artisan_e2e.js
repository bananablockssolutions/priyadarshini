/* Artists flow from the approved prototype (owner sketch 5): the Artists tab lists ranked cards; a card grows into its
   artisan page (portrait, ribbon, name, line, medals and artforms fly), Follow and Commission sit below the name, the
   maker's works follow like a category page; a work opened there says "‹ Meera" and plays Back; "‹ Artists" shrinks the
   page into its card. Needs dev/seed-demo.php. Run: PD_URL=http://127.0.0.1:8088 node dev/tests/artisan_e2e.js
   PD_VIDEO=/some/dir records the run. */
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
	await p.goto( B + '/', { waitUntil: 'networkidle' } );
	await p.evaluate( () => { document.querySelector( '.pd-splash' )?.remove(); window.__alive = 1; } );

	// Artists tab
	await p.locator( '.pd-tabs a', { hasText: 'Artists' } ).click();
	await p.waitForSelector( '.pd-acard', { state: 'visible' } );
	await wait( 500 );
	ok( await p.evaluate( () => window.__alive === 1 ), 'tab: same document' );
	const cards = p.locator( '.pd-acard' );
	ok( ( await cards.count() ) >= 3, 'list: the artisans' );
	ok( ( await cards.first().locator( '.pd-rib' ).textContent() ).includes( '#1' ), 'list: #1 ribbon on the first card' );
	ok( ( await cards.nth( 2 ).locator( '.pd-rib' ).textContent() ).includes( '#3' ), 'list: ranked in order' );
	const card = cards.first();
	ok( await card.locator( '.pd-apic' ).isVisible(), 'card: square portrait' );
	ok( ( await card.locator( '.pd-amark--award' ).count() ) === 2, 'card: two medals' );
	ok( ( await card.locator( '.pd-amark--form' ).count() ) >= 2, 'card: artform icons' );
	ok( await card.locator( '.pd-aline' ).isVisible(), 'card: short line' );
	const box = await card.boundingBox();
	ok( box && box.width > 340, 'card: full width' );
	if ( process.env.PD_SHOTS ) await p.screenshot( { path: process.env.PD_SHOTS + '/1-artists.png' } );

	// open: the card grows into the page
	const name = ( await card.locator( '.pd-aname' ).evaluate( ( e ) => e.firstChild.textContent ) ).trim();
	await card.locator( '.pd-apic' ).click();
	await wait( 300 );
	ok( await p.evaluate( () => document.querySelectorAll( '.pd-fly' ).length >= 4 ), 'open: portrait, ribbon, name and marks in flight' );
	if ( process.env.PD_SHOTS ) await p.screenshot( { path: process.env.PD_SHOTS + '/2-flight.png' } );
	await p.waitForSelector( '.pd-apage__name', { state: 'visible' } );
	await wait( 1300 );
	ok( await p.evaluate( () => window.__alive === 1 ), 'open: same document (no reload)' );
	ok( ( await p.locator( '.pd-apage__name' ).textContent() ).includes( name ), 'page: the card name is the h1' );
	ok( await p.evaluate( () => document.activeElement?.classList.contains( 'pd-apage__name' ) ), 'page: focus on the heading' );
	ok( await p.evaluate( () => ! document.querySelector( '.pd-fly' ) ), 'page: no copies left' );
	const pw = await p.locator( '.pd-apage .pd-apic' ).boundingBox();
	ok( pw && pw.width > 200, 'page: big square portrait' );
	ok( await p.locator( '.pd-apage .pd-rib' ).isVisible(), 'page: ribbon' );
	const nb = await p.locator( '.pd-apage__name' ).boundingBox();
	const ab = await p.locator( '.pd-apage__act' ).boundingBox();
	ok( ab && nb && ab.y > nb.y + nb.height - 2, 'page: Follow and Commission below the name' );
	ok( await p.locator( '.pd-apage__act .pd-follow button' ).isVisible() && await p.locator( '.pd-apage__comm' ).isVisible(), 'page: Follow and Commission a work' );
	ok( await p.locator( '.pd-apage .pd-amark__lbl' ).first().isVisible(), 'page: award names shown' );
	ok( await p.locator( '.pd-apage__text' ).isVisible() && await p.locator( '.pd-apage__quote' ).isVisible(), 'page: bio and quote' );
	ok( ( await p.locator( '.pd-mob .pd-toolbar__count' ).textContent() ).includes( 'by ' + name.split( ' ' )[ 0 ] ), 'page: "N pieces by Meera"' );
	ok( await p.locator( '.pd-mob .pd-rail' ).isVisible(), 'page: Filters rail' );
	const cols = await p.evaluate( () => getComputedStyle( document.querySelector( '.pd-mob .pd-shop .wc-block-product-template' ) ).gridTemplateColumns.split( ' ' ).length );
	ok( cols === 2, 'page: works in two columns' );
	ok( await p.evaluate( () => [ ...document.querySelectorAll( '.pd-mob .pd-shop li' ) ].every( ( li ) => getComputedStyle( li ).opacity === '1' ) ), 'page: works revealed' );
	if ( process.env.PD_SHOTS ) await p.screenshot( { path: process.env.PD_SHOTS + '/3-page.png', fullPage: true } );

	// a work from the artisan page, and back to it
	const work = p.locator( '.pd-mob .pd-work-card' ).first();
	await work.scrollIntoViewIfNeeded();
	await work.locator( '.pd-pic' ).click();
	await p.waitForSelector( '.pd-wpage__name', { state: 'visible' } );
	await wait( 1000 );
	ok( ( await p.locator( '.pd-wpage__back' ).textContent() ).includes( name.split( ' ' )[ 0 ] ), 'work: back link says the maker' );
	await p.locator( '.pd-wpage__back' ).click();
	await p.waitForSelector( '.pd-apage__name', { state: 'visible' } );
	await wait( 1000 );
	ok( await p.evaluate( () => window.__alive === 1 && ! document.querySelector( '.pd-fly' ) ), 'work back: artisan page again, same document' );
	ok( p.url().includes( '/a/' ), 'work back: address is the artisan page' );

	// Follow toggles in place
	const fb = p.locator( '.pd-apage__act .pd-follow button' );
	const was = await fb.getAttribute( 'aria-pressed' );
	ok( was === 'false' || was === 'true', 'follow: has a pressed state' );

	// ‹ Artists plays Back: the page shrinks into its card
	await p.evaluate( () => window.scrollTo( 0, 0 ) );
	await p.locator( '.pd-apage__back' ).click();
	await wait( 250 );
	ok( await p.evaluate( () => document.querySelectorAll( '.pd-fly' ).length > 0 ), 'back: parts are in flight' );
	await p.waitForSelector( '.pd-acard', { state: 'visible' } );
	await wait( 900 );
	ok( await p.evaluate( () => window.__alive === 1 && ! document.querySelector( '.pd-fly' ) ), 'back: list again, no copies left' );
	ok( p.url().endsWith( '/artisans/' ), 'back: address is the list' );
	ok( await p.evaluate( () => [ ...document.querySelectorAll( '.pd-acard [data-pd-fly]' ) ].every( ( e ) => getComputedStyle( e ).visibility === 'visible' ) ), 'back: card parts visible' );

	// without JavaScript the list and page still work
	const nojs = await browser.newContext( { viewport: { width: 390, height: 844 }, javaScriptEnabled: false } );
	const q = await nojs.newPage();
	await q.goto( B + '/artisans/' );
	ok( ( await q.locator( '.pd-acard' ).count() ) >= 3, 'no JS: list renders' );
	await q.locator( '.pd-acard' ).first().click();
	await q.waitForLoadState();
	ok( await q.locator( '.pd-apage__name' ).isVisible() && ( await q.locator( '.pd-mob .pd-work-card' ).count() ) > 0, 'no JS: page and works render' );
	await nojs.close();

	ok( errors.length === 0, 'no script errors' + ( errors.length ? ': ' + errors.join( ' | ' ) : '' ) );
	await ctx.close();
	await browser.close();
	console.log( `${ pass } passed, ${ fail } failed` );
	process.exit( fail ? 1 : 0 );
} )();

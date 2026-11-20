/**
 * Browser checks for pd-theme 0.2 + pd-blocks 0.2: persistent shell, client navigation, focus, Back, bag, splash, menu, search.
 * Needs a site seeded with dev/seed-demo.php. Run: PD_URL=http://127.0.0.1:8088 node dev/tests/theme_e2e.js
 * Uses a fresh browser context per group, so the cart starts empty.
 */
const { chromium } = require( 'playwright' );
const BASE = process.env.PD_URL || 'http://127.0.0.1:8088';
let pass = 0, fail = 0;
const ok = ( cond, name, extra = '' ) => { cond ? pass++ : fail++; console.log( `${ cond ? 'PASS' : 'FAIL' }  ${ name }${ extra && ! cond ? '  -> ' + extra : '' }` ); };
const settle = ( p ) => p.waitForFunction( () => ! document.documentElement.classList.contains( 'pd-is-loading' ) ).then( () => p.waitForTimeout( 250 ) );

( async () => {
	const browser = await chromium.launch();
	const errors = [];
	const newPage = async ( opts = {} ) => {
		const ctx = await browser.newContext( { viewport: { width: 1280, height: 900 }, ...opts } );
		const p = await ctx.newPage();
		p.on( 'pageerror', ( e ) => errors.push( e.message ) );
		p.on( 'console', ( m ) => m.type() === 'error' && errors.push( m.text() ) );
		return p;
	};

	// 1. Splash: once per session, ends on its own, never under reduced motion.
	{
		const p = await newPage();
		await p.goto( BASE + '/' );
		ok( await p.locator( '.pd-splash' ).isVisible(), 'splash shows on the first page of a session' );
		ok( await p.locator( '#pd-main' ).getAttribute( 'inert' ) !== null, 'page behind the splash is inert while it runs' );
		await p.waitForTimeout( 2300 );
		ok( ! ( await p.locator( '.pd-splash' ).isVisible() ), 'splash is gone within 2.3 s' );
		ok( await p.locator( '#pd-main' ).getAttribute( 'inert' ) === null, 'page is interactive after the splash' );
		await p.reload();
		ok( ! ( await p.locator( '.pd-splash' ).isVisible() ), 'splash does not show again in the same session' );
		const rm = await newPage( { reducedMotion: 'reduce' } );
		await rm.goto( BASE + '/' );
		ok( ! ( await rm.locator( '.pd-splash' ).isVisible() ), 'no splash under reduced motion' );
		await rm.context().close();
		await p.context().close();
	}

	// 2. Client navigation with a persistent shell.
	{
		const p = await newPage();
		await p.goto( BASE + '/?pd-nosplash' );
		await p.waitForTimeout( 300 );
		await p.evaluate( () => { window.__marker = 1; window.__shell = document.querySelector( '.pd-shell' ); } );
		await p.click( '.pd-shell__nav a:has-text("Artisans")' );
		await p.waitForURL( '**/artisans/' );
		await settle( p );
		ok( await p.evaluate( () => window.__marker === 1 ), 'Artisans opened without a full page load' );
		ok( await p.evaluate( () => window.__shell === document.querySelector( '.pd-shell' ) ), 'shell is the same DOM node after navigation' );
		ok( await p.evaluate( () => document.activeElement?.tagName === 'H1' ), 'focus moves to the page heading' );
		ok( ( await p.locator( '.pd-shell__nav a:has-text("Artisans")' ).getAttribute( 'aria-current' ) ) === 'page', 'nav marks Artisans as current' );
		ok( ( await p.locator( '.pd-shell__nav a:has-text("Works")' ).getAttribute( 'aria-current' ) ) === null, 'nav does not mark Works' );
		const live = await p.locator( '[role=status][aria-live=polite]' ).textContent();
		ok( /Artisans/.test( live ), 'page title announced politely', live );
		ok( /Artisans ·/.test( await p.locator( '.pd-ctx__text' ).textContent() ), 'context row updated for the directory' );

		await p.evaluate( () => window.scrollTo( 0, 200 ) );
		await p.click( '.pd-artisan-card__link:has-text("Meera Devi")' );
		await p.waitForURL( '**/a/demo-meera-devi/' );
		await settle( p );
		ok( await p.evaluate( () => window.__marker === 1 ), 'artisan page opened in place' );
		ok( /Meera Devi/.test( await p.locator( '.pd-ctx__text' ).textContent() ), 'context row shows the maker' );
		ok( await p.locator( '.pd-ctx .pd-seal[data-accent="gulabi"]' ).count() === 1, 'context row carries the maker Seal' );
		ok( await p.locator( '#pd-main .pd-work-card' ).count() === 6, 'works shelf lists only this maker\'s 6 works', String( await p.locator( '#pd-main .pd-work-card' ).count() ) );
		ok( await p.evaluate( () => window.scrollY ) === 0, 'new page starts at the top' );

		await p.click( '.pd-work-card__link:has-text("Fish and Lotus Pond")' );
		await p.waitForURL( '**/product/demo-fish-and-lotus-pond/' );
		await settle( p );
		ok( await p.evaluate( () => window.__marker === 1 ), 'work page opened in place' );
		ok( ( await p.locator( '#pd-main h1:visible' ).textContent() ).includes( 'Fish and Lotus Pond' ), 'work page heading' );
		ok( await p.locator( '#pd-main [data-pd-vt="work-' + ( await p.locator( '.pd-buy' ).getAttribute( 'data-wp-context' ).then( ( c ) => JSON.parse( c ).workId ) ) + '"][data-pd-vt-dest]' ).count() === 1, 'gallery plate is the transition destination' );

		await p.goBack();
		await p.waitForURL( '**/a/demo-meera-devi/' );
		await p.waitForTimeout( 500 );
		ok( await p.evaluate( () => window.__marker === 1 ), 'Back restores in place' );
		ok( /Meera Devi/.test( await p.locator( '.pd-ctx__text' ).textContent() ), 'Back restores the context row' );
		const focusId = await p.evaluate( () => document.activeElement?.dataset.pdFocusId || '' );
		ok( /^work-\d+-link-shelf$/.test( focusId ), 'Back returns focus to the card that was used', focusId );
		await p.goBack();
		await p.waitForURL( '**/artisans/' );
		await p.waitForTimeout( 500 );
		ok( Math.abs( ( await p.evaluate( () => window.scrollY ) ) - 200 ) < 40 || ( await p.evaluate( () => document.body.scrollHeight - innerHeight ) ) < 200, 'Back restores the scroll position' );
		await p.context().close();
	}

	// 3. Bag: optimistic count, server truth, announcement, shared mirrors.
	{
		const p = await newPage();
		await p.goto( BASE + '/product/demo-sikki-elephant/?pd-nosplash' );
		await p.waitForTimeout( 300 );
		ok( ( await p.locator( '.pd-bagc' ).textContent() ).trim() === '0', 'bag starts empty' );
		await p.evaluate( () => { window.__marker = 1; } );
		const t0 = Date.now();
		await p.click( '.pd-buy__form button:not([data-pd-buy-now])' );
		await p.waitForFunction( () => document.querySelector( '.pd-bagc' ).textContent.trim() === '1' );
		ok( Date.now() - t0 < 300, 'count moves at once (optimistic)', String( Date.now() - t0 ) + ' ms' );
		await p.waitForFunction( () => document.querySelector( '.pd-buy__form button:not([data-pd-buy-now])' ).getAttribute( 'aria-busy' ) === 'false' );
		ok( await p.evaluate( () => window.__marker === 1 ), 'Add to bag does not reload the page' );
		const cart = await p.evaluate( async () => ( await ( await fetch( '/wp-json/wc/store/v1/cart', { credentials: 'same-origin' } ) ).json() ).items_count );
		ok( cart === 1, 'WooCommerce cart holds the work', String( cart ) );
		ok( ( await p.locator( '.pd-baglink' ).getAttribute( 'aria-label' ) ) === 'Bag, 1 work', 'bag link label mirrors the count' );
		await p.waitForTimeout( 150 );
		ok( /added to your bag/.test( await p.locator( '[role=status][aria-live=polite]' ).textContent() ), 'addition announced politely' );
		await p.click( '.pd-shell__nav a:has-text("Works")' );
		await p.waitForURL( '**/shop/' );
		await settle( p );
		ok( ( await p.locator( '.pd-bagc' ).textContent() ).trim() === '1', 'count survives client navigation (reseeded from server)' );
		await p.reload();
		ok( ( await p.locator( '.pd-bagc' ).textContent() ).trim() === '1', 'count is server truth after reload' );
		await p.click( '.pd-baglink' );
		await p.waitForURL( '**/cart/' );
		ok( await p.evaluate( () => window.__marker === undefined ), 'bag page loads normally (WooCommerce cart scripts)' );
		await p.waitForFunction( () => document.querySelector( '#pd-main' )?.innerText.includes( 'Sikki Elephant' ), null, { timeout: 15000 } ).catch( () => {} );
		ok( ( await p.locator( '#pd-main' ).innerText() ).includes( 'Sikki Elephant' ), 'bag page shows the work' );
		// Failure path: a bad id rolls the count back and alerts. (Its 400 response is expected, so errors are counted before it.)
		await p.goto( BASE + '/product/demo-sikki-elephant/?pd-nosplash' );
		await p.waitForTimeout( 300 );
		const before = errors.length;
		await p.evaluate( async () => { const { store } = await import( '@wordpress/interactivity' ); await store( 'pd/bag' ).actions.add( 999999, 1, 'Nothing' ); } );
		await p.waitForTimeout( 200 );
		ok( ( await p.locator( '.pd-bagc' ).textContent() ).trim() === '1', 'failed add rolls the count back' );
		ok( /Could not add/.test( await p.locator( '[role=alert]' ).textContent() ), 'failed add raises an alert' );
		errors.splice( before ); // drop the expected 400 from the deliberate failure
		await p.context().close();
	}

	// 4. Without JavaScript the same forms work.
	{
		const p = await newPage( { javaScriptEnabled: false } );
		await p.goto( BASE + '/product/demo-village-story-panel/' );
		await p.click( '.pd-buy__form button:not([data-pd-buy-now])' );
		await p.waitForLoadState( 'load' );
		ok( ( await p.locator( '.pd-bagc' ).textContent() ).trim() === '1', 'no-JS Add to bag posts to WooCommerce and the count shows 1' );
		ok( await p.locator( '.pd-shell__nav a' ).count() === 2, 'no-JS navigation is plain links' );
		await p.context().close();
	}

	// 5. Phone: menu, Escape, search in the context row, hide on scroll.
	{
		const p = await newPage( { viewport: { width: 390, height: 844 } } );
		await p.goto( BASE + '/shop/?pd-nosplash' );
		await p.waitForTimeout( 300 );
		ok( ! ( await p.locator( '.pd-shell__nav' ).isVisible() ), 'nav is folded on a phone' );
		const barW = await p.evaluate( () => document.querySelector( '.pd-shell__bar' ).scrollWidth <= innerWidth );
		ok( barW, 'shell bar fits a 390px screen' );
		await p.click( '#pd-shell-menu-btn' );
		ok( await p.locator( '#pd-menu' ).isVisible(), 'Menu opens the illustrated menu' );
		ok( ( await p.getAttribute( '#pd-shell-menu-btn', 'aria-label' ) ) === 'Close menu', 'menu icon is labelled Close menu' );
		await p.waitForTimeout( 900 );
		await p.keyboard.press( 'Escape' );
		await p.waitForTimeout( 800 );
		ok( ! ( await p.locator( '#pd-menu' ).isVisible() ), 'Escape closes the menu' );
		ok( await p.evaluate( () => document.activeElement?.id === 'pd-shell-menu-btn' ), 'focus returns to the Menu button' );
		await p.setViewportSize( { width: 1280, height: 800 } ); // search lives in the header on wider screens; on a phone it is the Explore tab
		await p.waitForTimeout( 200 );
		const h0 = await p.evaluate( () => document.querySelector( '.pd-shell' ).offsetHeight );
		await p.click( '#pd-shell-search-btn' );
		await p.waitForTimeout( 100 );
		ok( await p.evaluate( () => document.activeElement?.id === 'pd-shell-search-input' ), 'Search opens in the context row with focus in the field' );
		ok( h0 === await p.evaluate( () => document.querySelector( '.pd-shell' ).offsetHeight ), 'shell keeps its height when search opens', String( h0 ) );
		await p.keyboard.press( 'Escape' );
		ok( await p.evaluate( () => document.activeElement?.id === 'pd-shell-search-btn' ), 'Escape closes search and returns focus' );
		await p.click( '#pd-shell-search-btn' );
		await p.fill( '#pd-shell-search-input', 'Sikki' );
		await p.evaluate( () => { window.__marker = 1; } );
		await p.keyboard.press( 'Enter' );
		await p.waitForURL( '**/?s=Sikki' );
		await settle( p );
		ok( await p.evaluate( () => window.__marker === 1 ), 'search results open in place' );
		ok( ( await p.content() ).includes( 'Sikki Elephant' ), 'search finds the work' );
		ok( ! ( await p.locator( '.pd-shell__search' ).isVisible() ), 'search closes after navigation (reset rule)' );
		await p.setViewportSize( { width: 390, height: 844 } );
		await p.goto( BASE + '/shop/?pd-nosplash' );
		await p.waitForTimeout( 300 );
		await p.mouse.wheel( 0, 600 );
		await p.waitForTimeout( 400 );
		ok( await p.evaluate( () => document.querySelector( '.pd-shell' ).classList.contains( 'is-hidden' ) ), 'shell hides when scrolling down on a phone' );
		await p.mouse.wheel( 0, -50 );
		await p.waitForTimeout( 400 );
		ok( ! ( await p.evaluate( () => document.querySelector( '.pd-shell' ).classList.contains( 'is-hidden' ) ) ), 'shell shows again when scrolling up' );
		await p.context().close();
	}

	// 6. Reduced motion: same navigation, destination outlined instead of moving.
	{
		const p = await newPage( { reducedMotion: 'reduce' } );
		await p.goto( BASE + '/shop/' );
		await p.waitForTimeout( 300 );
		await p.evaluate( () => { window.__marker = 1; } );
		await p.click( '.pd-work-card__link >> nth=0' );
		await p.waitForFunction( () => document.querySelector( '.pd-arrived' ) );
		ok( await p.evaluate( () => window.__marker === 1 ), 'reduced motion: same in-place navigation' );
		ok( await p.evaluate( () => !! document.querySelector( '#pd-main .pd-gallery .pd-arrived' ) ), 'reduced motion: destination plate outlined' );
		await p.context().close();
	}

	// 7. Nothing private in Interactivity state.
	{
		const p = await newPage();
		await p.goto( BASE + '/a/demo-meera-devi/?pd-nosplash' );
		const data = await p.evaluate( () => document.getElementById( 'wp-script-module-data-@wordpress/interactivity' )?.textContent || '' );
		ok( data.length > 0 && ! /address|phone|email|payout|verifier|user_id|userId/i.test( data ), 'public state holds no private fields' );
		await p.context().close();
	}

	ok( errors.length === 0, 'no JavaScript errors on any page', errors.slice( 0, 3 ).join( ' | ' ) );
	console.log( `\n${ pass } passed, ${ fail } failed` );
	await browser.close();
	process.exit( fail ? 1 : 0 );
} )();

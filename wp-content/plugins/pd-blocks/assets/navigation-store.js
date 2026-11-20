/* Store `pd/navigation`. Owner of: route url, progressVisible, the polite and assertive live regions,
   scroll and focus snapshots (BLOCKS.md 9.1 and 10). Only the routed region "pd-main" (and the shell's
   small "pd-context" region) is replaced; the shell, progress line and live regions stay mounted. */
import { store, getContext } from '@wordpress/interactivity';

const SNAP = 'pd.snap:';
const KEEP = 10; // route snapshots kept (Back restores from these first)
const FRESH_MS = 30000; // a prefetched page older than this is fetched again
const cache = new Map(); // path -> { at, promise<string|null> }
let navToken = 0;

const motionOK = () => ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const pathOf = ( url ) => {
	const u = new URL( url, window.location.href );
	return u.pathname + u.search;
};
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
const main = () => document.getElementById( 'pd-main' );

function sameSection( href, url ) {
	if ( ! href ) {
		return false;
	}
	const a = new URL( href, window.location.href ).pathname;
	const b = new URL( url, window.location.href ).pathname;
	const slash = ( p ) => ( p.endsWith( '/' ) ? p : p + '/' );
	return a === '/' ? b === '/' : slash( b ).startsWith( slash( a ) );
}

/** Fetch a page's server HTML once; null when it cannot be routed (the caller then loads it normally). */
function fetchPage( path ) {
	const hit = cache.get( path );
	const live = ( store( 'pd/navigation' ).state.narrowRoutable || [] ).some( ( p ) => path.startsWith( p ) ); // the cart changes; always ask again
	if ( hit && ! live && Date.now() - hit.at < FRESH_MS ) {
		return hit.promise;
	}
	const promise = ( async () => {
		try {
			const res = await fetch( path, { credentials: 'same-origin', headers: { 'X-PD-Route': '1' } } );
			const type = res.headers.get( 'content-type' ) || '';
			if ( ! res.ok || ! type.startsWith( 'text/html' ) || pathOf( res.url ) !== path ) {
				return null;
			}
			const html = await res.text();
			// Only pages built on the routed main, and not marked as needing a full load, are swapped in place.
			return /data-wp-router-region="pd-main"/.test( html ) && /data-pd-routable="1"/.test( html ) ? html : null;
		} catch ( e ) {
			return null;
		}
	} )();
	cache.set( path, { at: Date.now(), promise } );
	while ( cache.size > KEEP ) {
		cache.delete( cache.keys().next().value );
	}
	return promise;
}

function snapshot( source = null, extra = {} ) {
	try {
		const el = source?.closest?.( '[data-pd-focus-id]' ) ? source : document.activeElement;
		const focus = el?.closest?.( '[data-pd-focus-id]' )?.dataset.pdFocusId || '';
		const key = SNAP + pathOf( window.location.href );
		sessionStorage.setItem( key, JSON.stringify( { y: window.scrollY, focus, ...extra } ) );
		const list = JSON.parse( sessionStorage.getItem( 'pd.snaps' ) || '[]' ).filter( ( k ) => k !== key );
		list.push( key );
		while ( list.length > KEEP ) {
			sessionStorage.removeItem( list.shift() );
		}
		sessionStorage.setItem( 'pd.snaps', JSON.stringify( list ) );
	} catch ( e ) {}
}

function readSnapshot( path ) {
	try {
		return JSON.parse( sessionStorage.getItem( SNAP + path ) || 'null' );
	} catch ( e ) {
		return null;
	}
}

/** Wait until the routed region shows the page we asked for (the router renders asynchronously). */
async function settled( path ) {
	for ( let i = 0; i < 40; i++ ) {
		if ( main()?.dataset.pdPath === path ) {
			return true;
		}
		await sleep( 15 );
	}
	return false;
}

function focusHeading() {
	const region = main();
	const h = [ ...( region?.querySelectorAll( 'h1' ) || [] ) ].find( ( e ) => e.getClientRects().length ) || region;
	if ( ! h ) {
		return;
	}
	if ( ! h.hasAttribute( 'tabindex' ) ) {
		h.setAttribute( 'tabindex', '-1' );
	}
	h.focus( { preventScroll: true } );
}

/** The visual that carries a shared-element transition: the plate or seal inside the clicked card. */
function sharedSource( link ) {
	const entity = link?.closest?.( '[data-pd-entity-id]' );
	if ( ! entity ) {
		return null;
	}
	const id = entity.dataset.pdEntityId;
	const el = entity.matches( '[data-pd-vt]' ) ? entity : entity.querySelector( `[data-pd-vt="${ CSS.escape( id ) }"]` );
	return el ? { id, el } : null;
}

function destinationFor( id ) {
	const region = main();
	if ( ! region || ! id ) {
		return null;
	}
	const sel = `[data-pd-vt="${ CSS.escape( id ) }"]`;
	return region.querySelector( `${ sel }[data-pd-vt-dest]` ) || region.querySelector( sel );
}

function outline( el ) {
	if ( ! el ) {
		return;
	}
	el.classList.add( 'pd-arrived' );
	setTimeout( () => el.classList.remove( 'pd-arrived' ), 1000 );
}

/* ---------- shared elements on phones (approved prototype), the wpmovies.dev way ----------
   One engine for the three flows: a menu row grows into its type page, a work card grows into its work page, and the
   Bag icon grows into the cart heading. The router swaps #pd-main underneath while what you look at never disappears:
   "holds" (fixed copies of the parts you see) stay on screen through the swap, then copies of the new page's own parts
   start exactly where the old parts were and grow into place (FLIP), and hand over to the real nodes. Parts are marked
   data-pd-fly="<role>" inside a scope: a menu row [data-pd-row], a type page cover [data-pd-cover], or a work
   [data-pd-shared="work-12"] (the card, or the work page with data-pd-hero). Back plays the same flights in reverse. */
const NARROW = '(max-width: 759px)';
const flightsOK = () => motionOK() && window.matchMedia( NARROW ).matches;
const rowFlight = flightsOK;
const BY_WIDTH = new Set( [ 'ill', 'img', 'rib' ] ); // pictures scale by width, words by font size
const SCOPES = '[data-pd-shared],[data-pd-cover],[data-pd-row]';
const EASE = 'cubic-bezier(.2,.8,.2,1)';
const shell = () => store( 'pd/shell' );
const root = document.documentElement;
let lastFrom = ''; // the page this one was opened from, so a "‹ Saree" link can play Back instead

function partsOf( scope ) {
	const out = {};
	if ( ! scope ) {
		return out;
	}
	scope.querySelectorAll( '[data-pd-fly]' ).forEach( ( e ) => {
		if ( e.closest( SCOPES ) === scope && ! out[ e.dataset.pdFly ] ) {
			out[ e.dataset.pdFly ] = e;
		}
	} );
	return out;
}
const rectsOf = ( parts ) =>
	Object.fromEntries(
		Object.entries( parts )
			.map( ( [ k, e ] ) => [ k, { r: e.getBoundingClientRect(), fs: parseFloat( getComputedStyle( e ).fontSize ) || 16 } ] )
			.filter( ( [ , v ] ) => v.r.width > 0 && v.r.height > 0 )
	);

/**
 * A fixed copy of a part, drawn exactly where the part is now. The copy sits inside bare copies of its ancestors up to
 * its scope (class names only, no layout of their own), so rules like ".pd-work-card .pd-dots" still style it.
 */
function copyOf( e ) {
	const r = e.getBoundingClientRect();
	const cs = getComputedStyle( e );
	const c = e.cloneNode( true );
	c.removeAttribute( 'data-pd-fly' );
	c.removeAttribute( 'id' );
	c.querySelectorAll( '[id]' ).forEach( ( n ) => n.removeAttribute( 'id' ) );
	c.querySelectorAll( 'input' ).forEach( ( n ) => n.removeAttribute( 'name' ) ); // never join the real radio groups
	Object.assign( c.style, { width: `${ r.width }px`, height: `${ r.height }px`, margin: '0', font: cs.font, color: cs.color, letterSpacing: cs.letterSpacing, textAlign: cs.textAlign, visibility: 'visible', opacity: '1', transform: 'none' } );
	// nested parts (the Devanagari name) keep their look outside their usual ancestors
	const src = [ ...e.querySelectorAll( '*' ) ];
	[ ...c.querySelectorAll( '*' ) ].forEach( ( n, i ) => {
		const k = src[ i ] && getComputedStyle( src[ i ] );
		if ( k && n instanceof HTMLElement ) {
			Object.assign( n.style, { fontSize: k.fontSize, fontFamily: k.fontFamily, color: k.color, marginLeft: k.marginLeft } );
		}
	} );
	const scope = e.closest( SCOPES ) || e.parentElement;
	let wrap = c;
	for ( let a = e.parentElement; a && a !== document.body; a = a.parentElement ) {
		const shell = document.createElement( a.tagName === 'FORM' || a.tagName === 'A' || a.tagName === 'FIELDSET' ? 'div' : a.tagName );
		if ( a.className && typeof a.className === 'string' ) {
			shell.className = a.className;
		}
		shell.style.cssText = 'display:block;position:static;margin:0;padding:0;border:0;width:auto;height:auto;min-height:0;box-shadow:none;background:none;transform:none;overflow:visible;opacity:1;visibility:visible';
		shell.appendChild( wrap );
		wrap = shell;
		if ( a === scope ) {
			break;
		}
	}
	wrap.classList.add( 'pd-fly' );
	wrap.setAttribute( 'aria-hidden', 'true' );
	wrap.inert = true;
	const accent = e.closest( '[data-accent]' )?.dataset.accent;
	if ( accent ) {
		wrap.dataset.accent = accent;
	}
	Object.assign( wrap.style, { position: 'fixed', left: '0', top: '0', width: `${ r.width }px`, height: `${ r.height }px`, transform: `translate(${ r.left }px,${ r.top }px)`, margin: '0', padding: '0' } );
	document.body.appendChild( wrap );
	return { c: wrap, r };
}

/** Copies of what you see now, kept on screen while the page underneath is swapped. */
function hold( parts ) {
	const copies = Object.values( parts ).filter( ( e ) => e.getBoundingClientRect().width > 0 ).map( ( e ) => copyOf( e ).c );
	return {
		release( ms = 120 ) {
			copies.forEach( ( c ) => {
				if ( ! ms ) {
					c.remove();
					return;
				}
				c.animate( [ { opacity: 1 }, { opacity: 0 } ], { duration: ms, fill: 'forwards' } ).finished.catch( () => {} ).then( () => c.remove() );
			} );
		},
	};
}

/** Keep the arriving parts hidden from the moment the router paints them until their copies have landed. */
function hideArriving( selector ) {
	const st = document.createElement( 'style' );
	st.textContent = `${ selector } [data-pd-fly]{visibility:hidden!important}`;
	document.head.appendChild( st );
	return () => st.remove();
}

/** Grow copies of `to` from where `from` was (rects), then hand over to the real parts. */
async function fly( from, to, duration = 620, onStart = null ) {
	const jobs = [];
	for ( const [ k, e ] of Object.entries( to ) ) {
		const f = from[ k ];
		if ( ! f || ! e.getBoundingClientRect().width ) {
			continue;
		}
		const fs = parseFloat( getComputedStyle( e ).fontSize ) || 16;
		const { c, r } = copyOf( e );
		e.style.visibility = 'hidden';
		const s = BY_WIDTH.has( k ) ? f.r.width / r.width : f.fs / fs;
		const a = c.animate(
			[ { transform: `translate(${ f.r.left }px,${ f.r.top }px) scale(${ s })` }, { transform: `translate(${ r.left }px,${ r.top }px) scale(1)` } ],
			{ duration, easing: EASE, fill: 'forwards' }
		);
		jobs.push( a.finished.catch( () => {} ).then( () => ( { c, e } ) ) );
	}
	onStart?.();
	const done = await Promise.all( jobs );
	done.forEach( ( { c, e } ) => {
		e.style.visibility = '';
		c.remove();
	} );
}

/** What arrives after the grown parts: the frame, the rest of the words, the toolbar, the rail, then the pieces. */
function reveal( withPieces = true ) {
	const sel = '#pd-main [data-pd-reveal]' + ( withPieces ? ', #pd-main .pd-shop .wc-block-product-template > li' : '' );
	const list = [ ...document.querySelectorAll( sel ) ].filter( ( el ) => ! el.hidden );
	list.forEach( ( el, i ) =>
		el.animate( [ { opacity: 0, transform: 'translateY(14px)' }, { opacity: 1, transform: 'none' } ], { duration: 380, delay: Math.min( i, 12 ) * 70, easing: EASE, fill: 'backwards' } )
	);
	delete root.dataset.pdArrive;
}

/** Mark the page for the shell: on the cart page the header shows only the menu (sketch 4). */
function markPage() {
	if ( main()?.querySelector( '[data-pd-cart]' ) ) {
		root.dataset.pdOnCart = '1';
	} else {
		delete root.dataset.pdOnCart;
	}
}

/** A copy of the Bag icon in a layer above everything, at rect r. */
function bagLayer( svg, r ) {
	const layer = document.createElement( 'div' );
	layer.className = 'pd-baglayer';
	layer.setAttribute( 'aria-hidden', 'true' );
	Object.assign( layer.style, { left: `${ r.left }px`, top: `${ r.top }px`, width: `${ r.width }px`, height: `${ r.height }px` } );
	const c = svg.cloneNode( true );
	layer.appendChild( c );
	document.body.appendChild( layer );
	return { layer, svg: c };
}

/** Where the cart heading's icon will be (pd/cart CSS: 76px, centred, 6px below the top of the routed main). */
function slotGuess() {
	const m = main();
	if ( ! m ) {
		return null;
	}
	const r = m.getBoundingClientRect();
	const pad = parseFloat( getComputedStyle( m ).paddingTop ) || 0;
	const top = Math.max( r.top, document.querySelector( '.pd-shell__bar' )?.getBoundingClientRect().bottom || 0 ) + pad + 6;
	return { left: r.left + r.width / 2 - 38, top, width: 76, height: 76 };
}

/**
 * Bag icon flies down a curve and grows into the cart heading (sketch 4). It leaves at once, towards where the heading
 * will be, while the cart is fetched; returns { land(), cancel() } so the caller can settle it once the page is in.
 */
function bagToCart( first ) {
	const svg = document.querySelector( '.pd-baglink svg' );
	const last = slotGuess();
	if ( ! svg || ! first || ! last ) {
		return null;
	}
	const { layer, svg: c } = bagLayer( svg, last );
	document.querySelector( '.pd-baglink' )?.classList.add( 'is-away' );
	const k = first.width / last.width;
	const dx = first.left - last.left;
	const dy = first.top - last.top;
	const flight = c
		.animate(
			[
				{ transform: `translate(${ dx }px,${ dy }px) scale(${ k })` },
				{ offset: 0.45, transform: `translate(${ dx * 0.55 }px,${ dy * 0.35 }px) scale(${ k * 1.6 }) rotate(-8deg)` },
				{ offset: 0.85, transform: 'translate(0px,4px) scale(1.04) rotate(2deg)' },
				{ transform: 'none' },
			],
			{ duration: 720, easing: 'cubic-bezier(.35,0,.25,1)', fill: 'forwards' }
		)
		.finished.catch( () => {} );
	return {
		async land() {
			root.dataset.pdOnCart = '1';
			const slot = main()?.querySelector( '[data-pd-bag-slot]' );
			if ( slot ) {
				const r = slot.getBoundingClientRect();
				Object.assign( layer.style, { left: `${ r.left }px`, top: `${ r.top }px`, width: `${ r.width }px`, height: `${ r.height }px` } );
			}
			main()
				?.querySelectorAll( '.pd-cart__title span' )
				.forEach( ( el, i ) => el.animate( [ { opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' } ], { duration: 300, delay: i * 70, easing: EASE, fill: 'backwards' } ) );
			await flight;
			layer.remove();
			document.querySelector( '.pd-baglink' )?.classList.remove( 'is-away' );
		},
		cancel() {
			layer.remove();
			document.querySelector( '.pd-baglink' )?.classList.remove( 'is-away' );
		},
	};
}

/** Leaving the cart: the big icon slides back up into the header, then the count fades back in. */
function cartToBag() {
	const slot = main()?.querySelector( '[data-pd-bag-slot]' );
	const bag = document.querySelector( '.pd-baglink' );
	const svg = slot?.querySelector( 'svg' );
	if ( ! slot || ! bag || ! svg || ! flightsOK() ) {
		delete root.dataset.pdOnCart;
		return Promise.resolve();
	}
	const first = slot.getBoundingClientRect();
	const { layer, svg: c } = bagLayer( svg, first );
	slot.style.visibility = 'hidden';
	bag.classList.add( 'is-landing' );
	delete root.dataset.pdOnCart;
	const last = bag.querySelector( 'svg' ).getBoundingClientRect();
	Object.assign( layer.style, { left: `${ last.left }px`, top: `${ last.top }px`, width: `${ last.width }px`, height: `${ last.height }px` } );
	const k = first.width / last.width;
	const dx = first.left - last.left;
	const dy = first.top - last.top;
	return c
		.animate(
			[ { transform: `translate(${ dx }px,${ dy }px) scale(${ k })` }, { offset: 0.55, transform: `translate(${ dx * 0.4 }px,${ dy * 0.6 }px) scale(${ k * 0.7 }) rotate(6deg)` }, { transform: 'none' } ],
			{ duration: 560, easing: 'cubic-bezier(.35,0,.25,1)' }
		)
		.finished.catch( () => {} )
		.then( () => {
			layer.remove();
			bag.classList.remove( 'is-landing' );
		} );
}

const { state, actions } = store( 'pd/navigation', {
	state: {
		get isCurrent() {
			return sameSection( getContext().href, state.url );
		},
		get ariaCurrent() {
			return sameSection( getContext().href, state.url ) ? 'page' : null;
		},
		get tabCurrent() {
			return ( getContext().match || [] ).some( ( h ) => sameSection( h, state.url ) ) ? 'page' : null;
		},
	},
	actions: {
		announce( text, urgent = false ) {
			const key = urgent ? 'assertive' : 'polite';
			state[ key ] = '';
			setTimeout( () => ( state[ key ] = text ), 60 );
		},
		prefetch( href ) {
			fetchPage( pathOf( href ) );
		},
		/** Client navigation with the persistent shell. Falls back to a normal page load whenever anything is unusual. */
		*go( href, { source = null } = {} ) {
			const target = new URL( href, window.location.href );
			const path = target.pathname + target.search;
			const from = pathOf( window.location.href );
			// "‹ Saree" on a work page opened from Saree plays Back, so the work shrinks into its card again.
			if ( source?.closest?.( '[data-pd-back]' ) && lastFrom && new URL( lastFrom, window.location.href ).pathname === target.pathname ) {
				history.back();
				return;
			}
			const mine = ++navToken;
			const row = source?.closest?.( '[data-pd-from-menu]' );
			const card = row ? null : source?.closest?.( '#pd-main [data-pd-shared]:not([data-pd-hero])' );
			const bagLink = source?.closest?.( '.pd-baglink' );
			const fromMenu = !! row;
			const flights = flightsOK();
			const flight = fromMenu && flights;
			const cardFlight = !! card && flights && Object.keys( partsOf( card ) ).length > 0;
			const onCart = !! main()?.querySelector( '[data-pd-cart]' );
			const bagFlight = !! bagLink && flights && ! onCart;
			snapshot( source, { ...( fromMenu ? { menu: row.dataset.pdRow || '1' } : {} ), from: lastFrom } ); // remember where this page came from, for its back link after a Back
			let leaving = null;
			let first = null;
			let held = null;
			let fade = null;
			let bagTrip = null;
			if ( flight ) {
				first = rectsOf( partsOf( row ) );
				leaving = shell().actions.leaveMenu( row ); // the rest of the menu steps back while the page loads
			} else if ( cardFlight ) {
				// the card's parts stay where they are; everything else steps back while the work page loads
				first = rectsOf( partsOf( card ) );
				held = hold( partsOf( card ) );
				fade = main().animate( [ { opacity: 1 }, { opacity: 0 } ], { duration: 200, fill: 'forwards' } );
			} else if ( bagFlight ) {
				fade = main().animate( [ { opacity: 1 }, { opacity: 0 } ], { duration: 180, fill: 'forwards' } );
				bagTrip = bagToCart( bagLink.querySelector( 'svg' )?.getBoundingClientRect() || null );
			}
			const leavingCart = onCart && target.pathname !== window.location.pathname ? cartToBag() : null;
			if ( 'scrollRestoration' in history ) {
				history.scrollRestoration = 'manual';
			}
			const t1 = setTimeout( () => mine === navToken && ( state.progressVisible = true ), 100 );
			const t2 = setTimeout( () => mine === navToken && document.documentElement.classList.add( 'pd-is-loading' ), 300 );
			const done = () => {
				clearTimeout( t1 );
				clearTimeout( t2 );
				if ( mine === navToken ) {
					state.progressVisible = false;
					document.documentElement.classList.remove( 'pd-is-loading' );
				}
			};
			const undo = () => {
				held?.release( 0 );
				bagTrip?.cancel();
				fade?.cancel();
				delete root.dataset.pdArrive;
			};
			const html = yield fetchPage( path );
			if ( mine !== navToken ) {
				undo();
				return;
			}
			if ( ! html ) {
				done();
				window.location.assign( target.href );
				return;
			}
			let router;
			try {
				router = yield import( '@wordpress/interactivity-router' );
			} catch ( e ) {
				done();
				window.location.assign( target.href );
				return;
			}
			cache.delete( path ); // a page is shown once from the prefetch cache; Back uses the router's own copy
			const navigate = () => router.actions.navigate( target.href, { html, force: true, loadingAnimation: false, screenReaderAnnouncement: false } );
			const arrived = () => {
				done();
				lastFrom = from;
				state.url = window.location.href;
				markPage();
			};
			const finish = () => {
				focusHeading();
				actions.announce( main()?.dataset.pdTitle || document.title );
				document.dispatchEvent( new CustomEvent( 'pd:route', { detail: { kind: 'push', url: state.url } } ) );
			};
			if ( flight ) {
				root.dataset.pdArrive = 'type';
				try {
					yield Promise.all( [ leaving, sleep( 200 ) ] );
					yield navigate();
					yield settled( path );
				} catch ( e ) {
					delete root.dataset.pdArrive;
					done();
					window.location.assign( target.href );
					return;
				}
				arrived();
				window.scrollTo( 0, 0 );
				const to = partsOf( main()?.querySelector( '[data-pd-cover]' ) );
				shell().actions.menuLeft();
				Object.values( partsOf( row ) ).forEach( ( e ) => ( e.style.visibility = '' ) );
				if ( Object.keys( to ).length ) {
					const landing = fly( first, to, 620 );
					setTimeout( reveal, 380 ); // the intro and the pieces start while the heading settles
					yield landing;
				} else {
					reveal();
				}
				finish();
				return;
			}
			if ( cardFlight || bagFlight ) {
				const id = card?.dataset.pdShared;
				const artisan = !! id?.startsWith( 'artisan-' );
				// a work opened from an artisan page says "‹ Meera" on its back link, and that link plays Back
				const backLabel = cardFlight && ! artisan ? main()?.querySelector( '[data-pd-back-label]' )?.dataset.pdBackLabel : '';
				root.dataset.pdArrive = cardFlight ? ( artisan ? 'artisan' : 'work' ) : 'cart';
				const unhide = cardFlight ? hideArriving( `#pd-main [data-pd-hero][data-pd-shared="${ CSS.escape( id ) }"]` ) : () => {};
				try {
					yield Promise.all( [ leavingCart, sleep( cardFlight ? 180 : 0 ) ] );
					yield navigate();
					yield settled( path );
				} catch ( e ) {
					unhide();
					undo();
					done();
					window.location.assign( target.href );
					return;
				}
				arrived();
				window.scrollTo( 0, 0 );
				fade?.cancel();
				if ( backLabel ) {
					const link = main()?.querySelector( '[data-pd-hero] [data-pd-back]' );
					if ( link ) {
						link.href = new URL( from, window.location.href ).href;
						link.lastChild.textContent = backLabel;
					}
				}
				if ( cardFlight ) {
					const to = partsOf( main()?.querySelector( `[data-pd-hero][data-pd-shared="${ CSS.escape( id ) }"]` ) );
					const landing = fly( first, to, 620, () => {
						held.release( 140 );
						unhide();
					} );
					setTimeout( () => reveal( artisan ), 420 ); // an artisan page brings its works in too
					yield landing;
				} else {
					yield bagTrip?.land();
					reveal( false );
				}
				finish();
				return;
			}
			// Leaving the cart for any other page: the icon is already on its way up; swap without a view transition.
			const motion = motionOK() && typeof document.startViewTransition === 'function' && ! leavingCart;
			const shared = sharedSource( source );
			let landed = null;
			const swap = async () => {
				await navigate();
				await settled( path );
				if ( shared ) {
					landed = destinationFor( shared.id );
					if ( motion && landed ) {
						landed.style.viewTransitionName = 'pd-shared';
					}
				}
			};
			try {
				if ( motion ) {
					if ( shared ) {
						shared.el.style.viewTransitionName = 'pd-shared';
						document.documentElement.dataset.pdVt = 'shared';
					}
					const vt = document.startViewTransition( swap );
					yield vt.updateCallbackDone;
					vt.finished.finally( () => {
						if ( landed ) {
							landed.style.viewTransitionName = '';
						}
						if ( shared?.el ) {
							shared.el.style.viewTransitionName = '';
						}
						delete document.documentElement.dataset.pdVt;
					} );
				} else {
					yield swap();
					if ( ! leavingCart ) {
						outline( landed ); // reduced motion: the destination is outlined instead of moving
					}
				}
			} catch ( e ) {
				done();
				window.location.assign( target.href );
				return;
			}
			if ( mine !== navToken ) {
				done();
				return;
			}
			arrived();
			if ( target.hash ) {
				document.getElementById( decodeURIComponent( target.hash.slice( 1 ) ) )?.scrollIntoView();
			} else {
				window.scrollTo( 0, 0 );
			}
			finish();
		},
	},
	callbacks: {
		/** Runs once on the persistent shell: link and form interception, intent prefetch, Back and Forward. */
		boot() {
			const routable = ( a, event ) => {
				if ( ! a || ! a.href || a.closest( '[data-pd-reload]' ) || a.hasAttribute( 'download' ) ) {
					return false;
				}
				if ( event && ( event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) ) {
					return false;
				}
				if ( a.target && a.target !== '_self' ) {
					return false;
				}
				const u = new URL( a.href, window.location.href );
				if ( u.origin !== window.location.origin || /\.(pdf|jpe?g|png|webp|gif|svg|zip|xml|txt)$/i.test( u.pathname ) ) {
					return false;
				}
				if ( u.pathname === window.location.pathname && u.search === window.location.search && u.hash ) {
					return false; // an anchor on this page
				}
				if ( u.searchParams.has( 'add-to-cart' ) || u.searchParams.has( 'remove_item' ) || u.searchParams.has( 'preview' ) ) {
					return false;
				}
				const phoneOnly = ( state.narrowRoutable || [] ).some( ( p ) => u.pathname.startsWith( p ) );
				if ( phoneOnly ? ! window.matchMedia( NARROW ).matches : state.excluded.some( ( p ) => u.pathname.startsWith( p ) ) ) {
					return false;
				}
				return !! document.querySelector( '[data-wp-router-region="pd-main"]' );
			};

			const onClick = ( event ) => {
				const a = event.target.closest?.( 'a[href]' );
				if ( ! routable( a, event ) ) {
					return;
				}
				event.preventDefault();
				actions.go( a.href, { source: a } );
			};
			const onSubmit = ( event ) => {
				const form = event.target;
				if ( event.defaultPrevented || ! form.matches( 'form[data-pd-route]' ) || ( form.method || 'get' ).toLowerCase() !== 'get' ) {
					return;
				}
				const url = new URL( form.action || window.location.href, window.location.href );
				url.search = new URLSearchParams( new FormData( form ) ).toString();
				event.preventDefault();
				actions.go( url.href );
			};
			let hoverTimer = 0;
			const onIntent = ( event ) => {
				const a = event.target.closest?.( 'a[href]' );
				if ( ! routable( a ) ) {
					return;
				}
				clearTimeout( hoverTimer );
				if ( event.type === 'pointerover' ) {
					hoverTimer = setTimeout( () => actions.prefetch( a.href ), 80 ); // a short pause means intent
				} else {
					actions.prefetch( a.href );
				}
			};
			const onOut = () => clearTimeout( hoverTimer );
			const onPop = async () => {
				// The router renders the earlier page from its own copy; we restore scroll and the triggering control,
				// and play the flight that opened this page in reverse. Everything is measured now, synchronously,
				// before the router replaces the page: a work page shrinks back into its card, a type page's cover
				// flies back into its menu row, and the cart's big icon slides back up into the header.
				const path = pathOf( window.location.href );
				const snap = readSnapshot( path );
				const region = main();
				const flights = flightsOK();
				const cover = rectsOf( partsOf( region?.querySelector( '[data-pd-cover]' ) ) );
				const hero = flights ? region?.querySelector( '[data-pd-hero][data-pd-shared]' ) : null;
				const heroId = hero?.dataset.pdShared || '';
				const heroFrom = hero ? rectsOf( partsOf( hero ) ) : null;
				const held = hero ? hold( partsOf( hero ) ) : null;
				const unhide = hero ? hideArriving( `#pd-main [data-pd-shared="${ CSS.escape( heroId ) }"]:not([data-pd-hero])` ) : () => {};
				// the page steps back (its parts stay on screen as holds) until the earlier page is in and scrolled
				const popFade = hero ? region.animate( [ { opacity: 1 }, { opacity: 0 } ], { duration: 160, fill: 'forwards' } ) : null;
				const leavingCart = region?.querySelector( '[data-pd-cart]' ) ? cartToBag() : null;
				lastFrom = snap?.from || '';
				let back = null;
				if ( snap?.menu ) {
					root.dataset.pdArrive = 'menu'; // the shell keeps the menu open through this route change
					const row = await shell().actions.returnToMenu( String( snap.menu ) );
					const to = partsOf( row );
					if ( flights && Object.keys( to ).length && Object.keys( cover ).length ) {
						back = fly( cover, to, 520 );
					}
				}
				await settled( path );
				state.url = window.location.href;
				markPage();
				window.scrollTo( 0, snap?.y || 0 );
				popFade?.cancel();
				if ( hero ) {
					const card = main()?.querySelector( `[data-pd-shared="${ CSS.escape( heroId ) }"]:not([data-pd-hero])` );
					const to = partsOf( card );
					if ( card && Object.keys( to ).length ) {
						// the rest of the type page comes back while the work shrinks into its card
						[ ...main().children ].forEach( ( el ) => el.animate( [ { opacity: 0 }, { opacity: 1 } ], { duration: 320, delay: 260, easing: 'ease-out', fill: 'backwards' } ) );
						back = fly( heroFrom, to, 560, () => {
							held.release( 140 );
							unhide();
						} );
					} else {
						held.release( 0 );
						unhide();
					}
				}
				await back;
				await leavingCart;
				await sleep( 30 ); // let the shell's route watch run while the menu is still marked as kept
				if ( root.dataset.pdArrive === 'menu' ) {
					delete root.dataset.pdArrive;
				}
				const el = snap?.focus && document.querySelector( `[data-pd-focus-id="${ CSS.escape( snap.focus ) }"]` );
				if ( el ) {
					( el.matches( 'a,button,input,select,textarea,[tabindex]' ) ? el : el.querySelector( 'a,button' ) )?.focus( { preventScroll: true } );
				} else {
					focusHeading();
				}
				actions.announce( main()?.dataset.pdTitle || document.title );
				document.dispatchEvent( new CustomEvent( 'pd:route', { detail: { kind: 'pop', url: state.url } } ) );
			};

			markPage();
			document.addEventListener( 'click', onClick );
			document.addEventListener( 'submit', onSubmit );
			document.addEventListener( 'pointerover', onIntent, { passive: true } );
			document.addEventListener( 'pointerout', onOut, { passive: true } );
			document.addEventListener( 'focusin', onIntent );
			document.addEventListener( 'touchstart', onIntent, { passive: true } );
			window.addEventListener( 'popstate', onPop );
		},
	},
} );

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
	if ( hit && Date.now() - hit.at < FRESH_MS ) {
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

function snapshot() {
	try {
		const el = document.activeElement;
		const focus = el?.closest?.( '[data-pd-focus-id]' )?.dataset.pdFocusId || '';
		const key = SNAP + pathOf( window.location.href );
		sessionStorage.setItem( key, JSON.stringify( { y: window.scrollY, focus } ) );
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
	const h = region?.querySelector( 'h1' ) || region;
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

const { state, actions } = store( 'pd/navigation', {
	state: {
		get isCurrent() {
			return sameSection( getContext().href, state.url );
		},
		get ariaCurrent() {
			return sameSection( getContext().href, state.url ) ? 'page' : null;
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
			const mine = ++navToken;
			snapshot();
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
			const html = yield fetchPage( path );
			if ( mine !== navToken ) {
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
			const motion = motionOK() && typeof document.startViewTransition === 'function';
			const shared = sharedSource( source );
			let arrived = null;
			const swap = async () => {
				await router.actions.navigate( target.href, { html, force: true, loadingAnimation: false, screenReaderAnnouncement: false } );
				await settled( path );
				if ( shared ) {
					arrived = destinationFor( shared.id );
					if ( motion && arrived ) {
						arrived.style.viewTransitionName = 'pd-shared';
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
						if ( arrived ) {
							arrived.style.viewTransitionName = '';
						}
						if ( shared?.el ) {
							shared.el.style.viewTransitionName = '';
						}
						delete document.documentElement.dataset.pdVt;
					} );
				} else {
					yield swap();
					outline( arrived ); // reduced motion: the destination is outlined instead of moving
				}
			} catch ( e ) {
				done();
				window.location.assign( target.href );
				return;
			}
			done();
			if ( mine !== navToken ) {
				return;
			}
			state.url = window.location.href;
			if ( target.hash ) {
				document.getElementById( decodeURIComponent( target.hash.slice( 1 ) ) )?.scrollIntoView();
			} else {
				window.scrollTo( 0, 0 );
			}
			focusHeading();
			actions.announce( main()?.dataset.pdTitle || document.title );
			document.dispatchEvent( new CustomEvent( 'pd:route', { detail: { kind: 'push', url: state.url } } ) );
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
				if ( state.excluded.some( ( p ) => u.pathname.startsWith( p ) ) ) {
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
				// The router renders the earlier page from its own copy; we restore scroll and the triggering control.
				const path = pathOf( window.location.href );
				await settled( path );
				state.url = window.location.href;
				const snap = readSnapshot( path );
				window.scrollTo( 0, snap?.y || 0 );
				const el = snap?.focus && document.querySelector( `[data-pd-focus-id="${ CSS.escape( snap.focus ) }"]` );
				if ( el ) {
					( el.matches( 'a,button,input,select,textarea,[tabindex]' ) ? el : el.querySelector( 'a,button' ) )?.focus( { preventScroll: true } );
				} else {
					focusHeading();
				}
				actions.announce( main()?.dataset.pdTitle || document.title );
				document.dispatchEvent( new CustomEvent( 'pd:route', { detail: { kind: 'pop', url: state.url } } ) );
			};

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

/* Store `pd/shell`. Owner of: menuOpen, searchOpen, hidden (BLOCKS.md 9.1). UI only: every key resets on navigation.
   The shell itself is persistent: it is never replaced by the router, so these are the same DOM nodes on every page. */
import { store, getElement, withScope } from '@wordpress/interactivity';

const NARROW = '(max-width: 759px)';
const motionOK = () => ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const ref = ( sel ) => document.querySelector( sel );


/* ---------- the menu's dropping border (mobile, approved prototype) ----------
   The icon's third line leaves the icon, falls in 3D down the screen while the panel unrolls behind it, and settles
   as the panel's bottom border. Closing plays it backwards. Measured live, so it fits any screen. */
const NARROW_Q = '(max-width: 759px)';
const animated = () => motionOK() && window.matchMedia( NARROW_Q ).matches;
const el = {
	burger: () => ref( '#pd-shell-menu-btn' ),
	panel: () => ref( '#pd-menu' ),
	line: () => ref( '.pd-dropline' ),
};
const finished = ( a ) => a.finished.catch( () => {} );
let geoCache = null;

function geo() {
	const l3 = el.burger()?.children[ 2 ];
	const panel = el.panel();
	if ( ! l3 || ! panel ) {
		return null;
	}
	const a = l3.getBoundingClientRect();
	const p = panel.getBoundingClientRect();
	const end = { x: p.left, y: p.top + p.height - 1.25, w: p.width };
	return { p, end, dx: a.left - end.x, dy: a.top - end.y, k: a.width / end.w };
}

function frames( g ) {
	const off = [ 0, 0.18, 0.72, 0.86, 1 ];
	const ys = [ g.dy, g.dy * 0.82, 7, -4, 0 ];
	const xs = [ g.dx, g.dx * 0.7, 0, 0, 0 ];
	const sc = [ g.k, g.k * 1.8, 1, 1, 1 ];
	const rz = [ 0, -14, 1.5, -0.6, 0 ];
	const rx = [ 0, 140, 360, 360, 360 ];
	const ez = [ 'cubic-bezier(.4,0,.9,.6)', 'cubic-bezier(.5,0,1,1)', 'ease-out', 'ease-in-out', 'linear' ];
	const H = g.p.height;
	return {
		line: off.map( ( o, i ) => ( { offset: o, easing: ez[ i ], transform: `translate(${ xs[ i ] }px,${ ys[ i ] }px) rotateX(${ rx[ i ] }deg) rotate(${ rz[ i ] }deg) scaleX(${ sc[ i ] })` } ) ),
		clip: off.map( ( o, i ) => {
			const vis = Math.max( 0, Math.min( H + 6, H + ys[ i ] ) );
			return { offset: o, easing: ez[ i ], clipPath: `inset(0 0 ${ Math.max( 0, H - vis ) }px 0)` };
		} ),
	};
}
const reversed = ( list ) => list.slice().reverse().map( ( k ) => ( { ...k, offset: 1 - k.offset } ) );

function placeLine( g ) {
	const line = el.line();
	if ( ! line || ! g ) {
		return;
	}
	Object.assign( line.style, { left: g.end.x + 'px', top: g.end.y + 'px', width: g.end.w + 'px', transform: '' } );
	line.classList.add( 'is-on' );
}

/** Show the panel in its open state at once (Back to the menu), border in place. */
function showOpen() {
	el.panel()?.classList.add( 'is-open' );
	el.burger()?.classList.add( 'is-lent' );
	geoCache = geo();
	placeLine( geoCache );
}

/** Clear everything the open menu lent out: the line, the hidden third bar, inline styles. */
function tidy() {
	const line = el.line();
	if ( line ) {
		line.getAnimations().forEach( ( a ) => a.cancel() );
		line.classList.remove( 'is-on' );
	}
	el.burger()?.classList.remove( 'is-lent' );
	const panel = el.panel();
	if ( panel ) {
		panel.classList.remove( 'is-open' );
		panel.getAnimations( { subtree: true } ).forEach( ( a ) => a.cancel() );
		panel.querySelectorAll( '[style]' ).forEach( ( n ) => n.removeAttribute( 'style' ) );
	}
}

let lastY = 0;
let busy = false;
let lastUrl = '';

const { state, actions } = store( 'pd/shell', {
	state: {
		get menuLabel() {
			return state.menuOpen ? 'Close menu' : 'Open menu';
		},
		get shellHidden() {
			return state.hidden && ! state.menuOpen && ! state.searchOpen;
		},
	},
	actions: {
		/** The menu icon is a link to #pd-menu, so without JavaScript the panel opens with :target. */
		toggleMenu( event ) {
			event?.preventDefault();
			if ( busy ) {
				return;
			}
			if ( state.menuOpen ) {
				actions.closeMenuAnimated();
			} else {
				actions.openMenuAnimated();
			}
		},
		*openMenuAnimated() {
			state.searchOpen = false;
			state.menuOpen = true;
			const panel = el.panel();
			const line = el.line();
			showOpen();
			if ( ! animated() || ! panel || ! line || ! geoCache ) {
				panel?.querySelector( '.pd-mrow' )?.focus( { preventScroll: true } );
				return;
			}
			busy = true;
			const f = frames( geoCache );
			const a1 = line.animate( f.line, { duration: 820, fill: 'both' } );
			const a2 = panel.animate( f.clip, { duration: 820, fill: 'both' } );
			[ ...panel.querySelectorAll( '.pd-menu__items > li' ) ].forEach( ( li, i ) =>
				li.animate( [ { transform: 'rotateX(-75deg)', opacity: 0 }, { transform: 'rotateX(0)', opacity: 1 } ], { duration: 420, delay: 160 + i * 90, easing: 'cubic-bezier(.2,.9,.3,1.2)', fill: 'backwards' } )
			);
			yield Promise.all( [ finished( a1 ), finished( a2 ) ] );
			a1.cancel();
			a2.cancel();
			busy = false;
			panel.querySelector( '.pd-mrow' )?.focus( { preventScroll: true } );
		},
		*closeMenuAnimated( returnFocus = true ) {
			const panel = el.panel();
			const line = el.line();
			if ( animated() && panel && line && geoCache ) {
				busy = true;
				el.burger()?.setAttribute( 'aria-expanded', 'false' ); // the cross turns back while the border rises
				const f = frames( geoCache );
				const a1 = line.animate( reversed( f.line ), { duration: 620, fill: 'both', easing: 'ease-in' } );
				const a2 = panel.animate( reversed( f.clip ), { duration: 620, fill: 'both', easing: 'ease-in' } );
				yield Promise.all( [ finished( a1 ), finished( a2 ) ] );
				busy = false;
			}
			state.menuOpen = false;
			tidy();
			if ( returnFocus ) {
				el.burger()?.focus( { preventScroll: true } );
			}
		},
		/**
		 * A row was chosen (called by pd/navigation while the page loads): the other rows step back, the side borders
		 * slide out and vanish, and the border line flies back up into the icon.
		 */
		*leaveMenu( row ) {
			const panel = el.panel();
			const line = el.line();
			if ( ! panel ) {
				return;
			}
			el.burger()?.setAttribute( 'aria-expanded', 'false' );
			const li = row?.closest( 'li' );
			[ ...panel.querySelectorAll( '.pd-menu__items > li' ) ].filter( ( x ) => x !== li ).forEach( ( o, i ) =>
				o.animate( [ { opacity: 1 }, { opacity: 0, transform: 'translateY(6px)' } ], { duration: 220, delay: i * 25, fill: 'forwards' } )
			);
			if ( li ) {
				li.style.borderBottomColor = 'transparent';
			}
			const out = ( side, dx ) => panel.querySelector( side )?.animate( [ { transform: 'none', opacity: 1 }, { transform: `translateX(${ dx }px)`, opacity: 0 } ], { duration: 360, easing: 'cubic-bezier(.5,0,.8,.4)', fill: 'forwards' } );
			const anims = [ out( '.pd-menu__side.is-l', -34 ), out( '.pd-menu__side.is-r', 34 ) ];
			const g = geoCache || geo();
			if ( line && g ) {
				const up = line.animate(
					[ { transform: 'none' }, { offset: 0.6, transform: `translate(${ g.dx * 0.5 }px,${ g.dy * 0.7 }px) rotate(10deg) scaleX(${ g.k * 1.6 })` }, { transform: `translate(${ g.dx }px,${ g.dy }px) scaleX(${ g.k })` } ],
					{ duration: 520, easing: 'cubic-bezier(.5,0,.3,1)', fill: 'both' }
				);
				finished( up ).then( () => {
					line.classList.remove( 'is-on' );
					up.cancel();
					el.burger()?.classList.remove( 'is-lent' );
				} );
				anims.push( up );
			}
			yield Promise.all( anims.filter( Boolean ).map( finished ) );
		},
		/** After the row has become the page: the menu is closed and tidied. */
		menuLeft() {
			state.menuOpen = false;
			tidy();
		},
		/** Back to the menu from a type page: open at once, sides slide back in, other rows return. */
		*returnToMenu( rowId ) {
			state.searchOpen = false;
			state.menuOpen = true;
			showOpen();
			const panel = el.panel();
			if ( ! panel ) {
				return;
			}
			const row = panel.querySelector( `[data-pd-row="${ CSS.escape( rowId ) }"]` );
			if ( ! animated() ) {
				return row;
			}
			const li = row?.closest( 'li' );
			const inn = ( side, dx ) => panel.querySelector( side )?.animate( [ { transform: `translateX(${ dx }px)`, opacity: 0 }, { transform: 'none', opacity: 1 } ], { duration: 340, easing: 'cubic-bezier(.2,.7,.2,1)' } );
			inn( '.pd-menu__side.is-l', -34 );
			inn( '.pd-menu__side.is-r', 34 );
			[ ...panel.querySelectorAll( '.pd-menu__items > li' ) ].filter( ( x ) => x !== li ).forEach( ( o, i ) =>
				o.animate( [ { opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' } ], { duration: 240, delay: 120 + i * 40, fill: 'backwards' } )
			);
			return row;
		},
		closeMenu() {
			state.menuOpen = false;
		},

		/** Search opens in the context row (same 44px), so nothing below moves. The link still works without JavaScript. */
		openSearch( event ) {
			event?.preventDefault();
			state.menuOpen = false;
			state.searchOpen = true;
			requestAnimationFrame( () => ref( '#pd-shell-search-input' )?.focus() );
		},
		closeSearch( returnFocus = true ) {
			state.searchOpen = false;
			if ( returnFocus ) {
				requestAnimationFrame( () => ref( '#pd-shell-search-btn' )?.focus() );
			}
		},
		onKeydown( event ) {
			if ( event.key !== 'Escape' ) {
				return;
			}
			if ( state.searchOpen ) {
				actions.closeSearch();
			} else if ( state.menuOpen && ! busy ) {
				actions.closeMenuAnimated();
			}
		},
		/** A press outside the open menu closes it. */
		onDocumentClick( event ) {
			if ( state.menuOpen && ! busy && ! event.target.closest( '.pd-shell__bar, .pd-menu, .pd-tabs' ) ) {
				actions.closeMenuAnimated( false );
			}
		},
		onNavClick() {
			state.menuOpen = false;
		},
		/** Under 760px, scrolling down past 160px hides the shell; scrolling up 8px shows it again. */
		onScroll() {
			const y = window.scrollY;
			if ( ! window.matchMedia( NARROW ).matches || ref( '.pd-shell' )?.contains( document.activeElement ) ) {
				state.hidden = false;
			} else if ( y > lastY && y > 160 ) {
				state.hidden = true;
			} else if ( lastY - y >= 8 ) {
				state.hidden = false;
			}
			lastY = y;
		},
	},
	callbacks: {
		/** Reset UI keys whenever the route changes (Router rule "reset"); the shell is shown on every new page. */
		resetOnRoute() {
			const nav = store( 'pd/navigation' );
			const url = nav.state.url;
			const keep = document.documentElement.dataset.pdArrive === 'menu';
			if ( lastUrl && url !== lastUrl && ! keep ) {
				if ( state.menuOpen && ! document.documentElement.dataset.pdArrive ) {
					tidy();
				}
				state.menuOpen = false;
				state.searchOpen = false;
				state.hidden = false;
			}
			lastUrl = url;
		},
		/**
		 * Splash (UI-08, UI-09): the motif draws on paper, then moves into the header logo. At most 1.8 s, once per session,
		 * skippable, never shown under reduced motion. A tiny head script decides before first paint; CSS ends it on its own
		 * at 1.9 s even if this module never loads, so it can never block the page.
		 */
		splash() {
			const root = document.documentElement;
			const el = getElement().ref;
			if ( ! root.classList.contains( 'pd-splash-on' ) ) {
				return;
			}
			const inert = ref( '#pd-main' );
			inert?.setAttribute( 'inert', '' );
			let finished = false;
			const end = () => {
				if ( finished ) {
					return;
				}
				finished = true;
				root.classList.remove( 'pd-splash-on' );
				inert?.removeAttribute( 'inert' );
				document.removeEventListener( 'keydown', onKey );
			};
			const onKey = ( e ) => ( e.key === 'Escape' || e.key === 'Enter' || e.key === ' ' ) && end();
			document.addEventListener( 'keydown', onKey );
			el.querySelector( '.pd-splash__skip' )?.addEventListener( 'click', end );
			const fish = el.querySelector( '.pd-splash__motif' );
			const logo = ref( '.pd-brandmark .pd-motif' );
			// Draw 0 to 0.9 s and fill to 1.0 s in CSS; then dock into the logo in 0.6 s. If the page is ready first, dock now.
			const dockAt = document.readyState === 'complete' ? 1000 : 1000;
			setTimeout( () => {
				if ( finished || ! fish || ! logo || ! motionOK() ) {
					return end();
				}
				const f = fish.getBoundingClientRect();
				const l = logo.getBoundingClientRect();
				const s = l.width / f.width;
				fish.style.transition = 'transform .6s cubic-bezier(.6,.05,.2,1)';
				fish.style.transform = `translate(${ l.left + l.width / 2 - ( f.left + f.width / 2 ) }px, ${ l.top + l.height / 2 - ( f.top + f.height / 2 ) }px) scale(${ s })`;
				el.classList.add( 'is-docking' );
				setTimeout( end, 650 );
			}, dockAt );
		},
		listen() {
			const onScroll = withScope( () => actions.onScroll() );
			const onClick = withScope( ( e ) => actions.onDocumentClick( e ) );
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			document.addEventListener( 'click', onClick );
			return () => {
				window.removeEventListener( 'scroll', onScroll );
				document.removeEventListener( 'click', onClick );
			};
		},
	},
} );

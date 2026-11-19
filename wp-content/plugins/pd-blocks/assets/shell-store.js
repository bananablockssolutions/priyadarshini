/* Store `pd/shell`. Owner of: menuOpen, searchOpen, hidden (BLOCKS.md 9.1). UI only: every key resets on navigation.
   The shell itself is persistent: it is never replaced by the router, so these are the same DOM nodes on every page. */
import { store, getElement, withScope } from '@wordpress/interactivity';

const NARROW = '(max-width: 759px)';
const motionOK = () => ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const ref = ( sel ) => document.querySelector( sel );

let lastY = 0;
let lastUrl = '';

const { state, actions } = store( 'pd/shell', {
	state: {
		get menuLabel() {
			return state.menuOpen ? 'Close' : 'Menu';
		},
		get shellHidden() {
			return state.hidden && ! state.menuOpen && ! state.searchOpen;
		},
	},
	actions: {
		/** The Menu control is a link to #pd-menu, so without JavaScript the panel opens with :target. */
		toggleMenu( event ) {
			event?.preventDefault();
			state.menuOpen = ! state.menuOpen;
			if ( state.menuOpen ) {
				state.searchOpen = false;
			}
		},
		closeMenu() {
			state.menuOpen = false;
		},
		closeMenuAndReturn( event ) {
			event?.preventDefault();
			state.menuOpen = false;
			requestAnimationFrame( () => ref( '#pd-shell-menu-btn' )?.focus() );
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
			} else if ( state.menuOpen ) {
				state.menuOpen = false;
				ref( '#pd-shell-menu-btn' )?.focus();
			}
		},
		/** A press outside the open menu closes it. */
		onDocumentClick( event ) {
			if ( state.menuOpen && ! event.target.closest( '.pd-shell__bar, .pd-menu' ) ) {
				state.menuOpen = false;
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
			if ( lastUrl && url !== lastUrl ) {
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

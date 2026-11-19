/* Store `pd/catalogue`. Owner of: view (grid or list, kept for the tab session) and sheetOpen (UI, reset on route).
   The switch moves each card from where it was to where it lands (FLIP), as in the approved prototype. */
import { store, getElement } from '@wordpress/interactivity';

const KEY = 'pd.view';
const motionOK = () => ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const root = document.documentElement;
try {
	root.dataset.pdView = sessionStorage.getItem( KEY ) === 'list' ? 'list' : 'grid';
} catch ( e ) {
	root.dataset.pdView = 'grid';
}

function setView( view ) {
	const cards = [ ...document.querySelectorAll( '.pd-shop .wc-block-product-template > li' ) ];
	const first = cards.map( ( c ) => c.getBoundingClientRect() );
	state.view = view;
	root.dataset.pdView = view;
	try {
		sessionStorage.setItem( KEY, view );
	} catch ( e ) {}
	if ( ! motionOK() ) {
		return;
	}
	cards.forEach( ( c, i ) => {
		const l = c.getBoundingClientRect();
		const f = first[ i ];
		c.animate( [ { transform: `translate(${ f.left - l.left }px,${ f.top - l.top }px)` }, { transform: 'none' } ], { duration: 360, easing: 'cubic-bezier(.2,.8,.2,1)' } );
	} );
}

const { state } = store( 'pd/catalogue', {
	state: {
		view: root.dataset.pdView,
		get isGrid() {
			return state.view !== 'list';
		},
		get isList() {
			return state.view === 'list';
		},
	},
	actions: {
		showGrid() {
			setView( 'grid' );
		},
		showList() {
			setView( 'list' );
		},
		openSheet( event ) {
			event?.preventDefault();
			state.sheetOpen = true;
			const el = getElement().ref;
			requestAnimationFrame( () => el?.closest( '.pd-rail-wrap' )?.querySelector( '.pd-sheet input' )?.focus() );
		},
		closeSheet( event ) {
			if ( event?.type === 'click' ) {
				event.preventDefault();
			}
			state.sheetOpen = false;
		},
		sheetKey( event ) {
			if ( event.key === 'Escape' ) {
				state.sheetOpen = false;
				document.querySelector( '.pd-rail' )?.focus();
			}
		},
	},
} );

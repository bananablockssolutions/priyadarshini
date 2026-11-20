/* Store `pd/bag`. Owner of: bag.count (BLOCKS.md 9.1). A thin mirror of the WooCommerce cart: the Store API is the truth.
   Readers (the shell bag link, the buy box) bind to state.count / state.countLabel and call actions.add; they never write. */
import { store, getContext, getServerState, withScope } from '@wordpress/interactivity';

let token = 0; // the latest write wins; older replies are ignored
let lastServer = null; // the server state object last applied (one per page load or route)

const motionOK = () => ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const rupees = ( n ) => '₹' + Math.round( n ).toLocaleString( 'en-IN' ) + ( Math.abs( n - Math.round( n ) ) > 0.004 ? ( n % 1 ).toFixed( 2 ).slice( 1 ) : '' );

/** A pigment dot arcs from the Buy button into the Bag beside the menu (approved prototype). */
function dotToBag( from ) {
	const bag = document.querySelector( '.pd-baglink' );
	if ( ! from || ! bag || ! motionOK() || ! bag.offsetParent ) {
		return Promise.resolve();
	}
	const a = from.getBoundingClientRect();
	const b = bag.getBoundingClientRect();
	const d = document.createElement( 'span' );
	d.className = 'pd-dotfly';
	d.setAttribute( 'aria-hidden', 'true' );
	d.style.left = `${ a.left + a.width / 2 - 8 }px`;
	d.style.top = `${ a.top + a.height / 2 - 8 }px`;
	document.body.appendChild( d );
	const dx = b.left + b.width / 2 - ( a.left + a.width / 2 );
	const dy = b.top + b.height / 2 - ( a.top + a.height / 2 );
	return d
		.animate(
			[ { transform: 'translate(0,0) scale(1)' }, { offset: 0.45, transform: `translate(${ dx * 0.45 }px,${ dy * 0.45 - 60 }px) scale(1.15)` }, { transform: `translate(${ dx }px,${ dy }px) scale(.4)` } ],
			{ duration: 620, easing: 'cubic-bezier(.4,0,.6,1)' }
		)
		.finished.catch( () => {} )
		.then( () => d.remove() );
}

let toastTimer = 0;
function toast( text ) {
	state.toast = text;
	state.toastOn = true;
	clearTimeout( toastTimer );
	toastTimer = setTimeout( () => ( state.toastOn = false ), 1600 );
}

const say = ( text, urgent = false ) => {
	const nav = store( 'pd/navigation' );
	nav.actions?.announce?.( text, urgent );
};

const { state, actions } = store( 'pd/bag', {
	state: {
		get countLabel() {
			return state.count === 1 ? 'Bag, 1 work' : `Bag, ${ state.count } works`;
		},
		get hasItems() {
			return state.count > 0;
		},
	},
	actions: {
		/** Add a work. Optimistic: the count moves at once, then the server's count replaces it, or it rolls back. */
		*add( productId, quantity = 1, title = '', variation = [] ) {
			const mine = ++token;
			const before = state.count;
			state.count = before + quantity;
			state.pending = true;
			actions.bumpOnce();
			try {
				const res = yield fetch( state.storeApi + 'cart/add-item', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', Nonce: state._nonce },
					body: JSON.stringify( variation.length ? { id: productId, quantity, variation } : { id: productId, quantity } ),
				} );
				const nonce = res.headers.get( 'Nonce' );
				if ( nonce ) {
					state._nonce = nonce;
				}
				const data = yield res.json();
				if ( ! res.ok ) {
					throw new Error( data?.message || 'add failed' );
				}
				if ( mine === token ) {
					state.count = data.items_count; // server truth wins
					say( `${ title || 'The work' } added to your bag. ${ state.count === 1 ? '1 work' : state.count + ' works' } in bag.` );
				}
				document.dispatchEvent( new CustomEvent( 'pd:bag:changed', { detail: { count: data.items_count } } ) );
				return true;
			} catch ( e ) {
				if ( mine === token ) {
					state.count = before; // roll back
					say( `Could not add ${ title || 'the work' } to your bag. ${ e.message && e.message !== 'add failed' ? stripTags( e.message ) : 'Please try again.' }`, true );
				}
				return false;
			} finally {
				if ( mine === token ) {
					state.pending = false;
				}
			}
		},
		/**
		 * Add-to-bag form (works without JavaScript as a normal WooCommerce form post). Context: { workId, title }.
		 * A "Buy now" button (formaction to checkout) is left to the browser. Picked colour and size go with the item.
		 */
		*submitAdd( event ) {
			if ( event.submitter?.hasAttribute( 'formaction' ) ) {
				return;
			}
			event.preventDefault();
			const { workId, title } = getContext();
			const form = event.target;
			const data = new FormData( form );
			const qty = parseInt( data.get( 'quantity' ) || '1', 10 ) || 1;
			const variation = [];
			for ( const [ k, v ] of data.entries() ) {
				if ( k.startsWith( 'attribute_' ) && v ) {
					variation.push( { attribute: k.slice( 10 ), value: String( v ) } );
				}
			}
			const flight = dotToBag( event.submitter || form.querySelector( '[type=submit]' ) );
			const ok = yield actions.add( workId, qty, title, variation );
			yield flight;
			if ( ok ) {
				toast( 'Added to bag' );
			}
		},
		/** Remove a line on the cart page (pd/cart). Without JavaScript the link is WooCommerce's own remove link. */
		*removeLine( event ) {
			const link = event.target.closest( '[data-pd-key]' );
			if ( ! link ) {
				return;
			}
			event.preventDefault();
			const row = link.closest( '.pd-crow' );
			const cart = link.closest( '[data-pd-cart]' );
			const before = state.count;
			state.count = Math.max( 0, before - ( parseInt( row?.dataset.pdQty || '1', 10 ) || 1 ) );
			try {
				const res = yield fetch( state.storeApi + 'cart/remove-item', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', Nonce: state._nonce },
					body: JSON.stringify( { key: link.dataset.pdKey } ),
				} );
				const nonce = res.headers.get( 'Nonce' );
				if ( nonce ) {
					state._nonce = nonce;
				}
				const data = yield res.json();
				if ( ! res.ok ) {
					throw new Error( data?.message || 'remove failed' );
				}
				state.count = data.items_count;
			} catch ( e ) {
				state.count = before;
				say( 'Could not remove that piece. Please try again.', true );
				return;
			}
			if ( row ) {
				if ( motionOK() ) {
					yield row.animate( [ { opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateX(-30px)' } ], { duration: 220, fill: 'forwards' } ).finished.catch( () => {} );
				}
				const next = row.nextElementSibling?.matches( '.pd-crow' ) ? row.nextElementSibling : row.previousElementSibling;
				row.hidden = true;
				row.dataset.pdGone = '1';
				( next?.querySelector( '.pd-crow__rm' ) || cart?.querySelector( 'h1' ) )?.focus?.();
			}
			if ( cart ) {
				const live = [ ...cart.querySelectorAll( '.pd-crow:not([data-pd-gone])' ) ];
				const total = live.reduce( ( t, r ) => t + parseFloat( r.dataset.pdLine || '0' ), 0 );
				const n = state.count;
				const count = cart.querySelector( '[data-pd-count]' );
				if ( count ) {
					count.textContent = n === 1 ? '1 piece' : `${ n } pieces`;
				}
				const tot = cart.querySelector( '[data-pd-total]' );
				if ( tot ) {
					tot.textContent = rupees( total );
				}
				if ( ! live.length ) {
					cart.querySelector( '.pd-cart__empty' )?.removeAttribute( 'hidden' );
					cart.querySelector( '[data-pd-cart-end]' )?.setAttribute( 'hidden', '' );
				}
			}
			say( `Removed. ${ state.count === 1 ? '1 piece' : state.count + ' pieces' } in your bag.` );
			document.dispatchEvent( new CustomEvent( 'pd:bag:changed', { detail: { count: state.count } } ) );
		},
		/** Ask the server again (after WooCommerce's own blocks change the cart). */
		*refresh() {
			const mine = ++token;
			try {
				const res = yield fetch( state.storeApi + 'cart', { credentials: 'same-origin' } );
				const nonce = res.headers.get( 'Nonce' );
				if ( nonce ) {
					state._nonce = nonce;
				}
				const data = yield res.json();
				if ( res.ok && mine === token ) {
					state.count = data.items_count;
				}
			} catch ( e ) {}
		},
		bumpOnce() {
			state.bump = false;
			requestAnimationFrame( () => ( state.bump = true ) );
			setTimeout( () => ( state.bump = false ), 400 );
		},
	},
	callbacks: {
		/** Reseed on every route: the new page's server count replaces ours (Router rule "reseed"). */
		reseed() {
			const server = getServerState();
			// Only a new page's server state reseeds. Reading nothing else here keeps this watch from re-running
			// on our own writes (an earlier version also read state.pending and reset the count after every add).
			if ( server === lastServer ) {
				return;
			}
			lastServer = server;
			if ( typeof server.count === 'number' ) {
				state.count = server.count;
			}
			if ( server._nonce ) {
				state._nonce = server._nonce;
			}
		},
		/** WooCommerce blocks (mini cart, product buttons, Cart block) announce their own changes. */
		listen() {
			const refresh = withScope( () => actions.refresh() );
			document.addEventListener( 'wc-blocks_added_to_cart', refresh );
			document.addEventListener( 'wc-blocks_removed_from_cart', refresh );
			// Back and Forward show the router's earlier copy of a page, so ask the cart again.
			const onRoute = ( e ) => e.detail?.kind === 'pop' && refresh();
			document.addEventListener( 'pd:route', onRoute );
			return () => {
				document.removeEventListener( 'pd:route', onRoute );
				document.removeEventListener( 'wc-blocks_added_to_cart', refresh );
				document.removeEventListener( 'wc-blocks_removed_from_cart', refresh );
			};
		},
	},
} );

function stripTags( html ) {
	const d = document.createElement( 'div' );
	d.innerHTML = html;
	return d.textContent || '';
}

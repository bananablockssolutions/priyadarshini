/* Store `pd/bag`. Owner of: bag.count (BLOCKS.md 9.1). A thin mirror of the WooCommerce cart: the Store API is the truth.
   Readers (the shell bag link, the buy box) bind to state.count / state.countLabel and call actions.add; they never write. */
import { store, getContext, getServerState, withScope } from '@wordpress/interactivity';

let token = 0; // the latest write wins; older replies are ignored
let lastServer = null; // the server state object last applied (one per page load or route)

const say = ( text, urgent = false ) => {
	const nav = store( 'pd/navigation' );
	nav.actions?.announce?.( text, urgent );
};

const { state, actions } = store( 'pd/bag', {
	state: {
		get countLabel() {
			return state.count === 1 ? 'Bag, 1 work' : `Bag, ${ state.count } works`;
		},
	},
	actions: {
		/** Add a work. Optimistic: the count moves at once, then the server's count replaces it, or it rolls back. */
		*add( productId, quantity = 1, title = '' ) {
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
					body: JSON.stringify( { id: productId, quantity } ),
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
		/** Add-to-bag form (works without JavaScript as a normal WooCommerce form post). Context: { workId, title }. */
		*submitAdd( event ) {
			event.preventDefault();
			const { workId, title } = getContext();
			const qty = parseInt( event.target.querySelector( '[name=quantity]' )?.value || '1', 10 ) || 1;
			yield actions.add( workId, qty, title );
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

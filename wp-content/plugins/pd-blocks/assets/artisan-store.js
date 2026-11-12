/* Store `pd/artisan`. Owner of: followed[artisanPostId], followerCount[artisanPostId] (BLOCKS.md 9.1).
   Other blocks read these through data-wp-bind / data-wp-text on the getters below; they never write them. */
import { store, getContext } from '@wordpress/interactivity';

const pending = new Map(); // artisanPostId -> token of the latest optimistic write

const { state } = store( 'pd/artisan', {
	state: {
		get isFollowing() {
			const { artisanPostId } = getContext();
			return !! state.followed[ artisanPostId ];
		},
		get followers() {
			const { artisanPostId } = getContext();
			return state.followerCount[ artisanPostId ] ?? 0;
		},
		get followersLabel() {
			const n = state.followers;
			return n === 1 ? '1 follower' : `${ n } followers`;
		},
		get followLabel() {
			return state.isFollowing ? 'Following' : 'Follow';
		},
	},
	actions: {
		*toggleFollow( event ) {
			const { artisanPostId: id } = getContext();
			if ( ! state.signedIn ) {
				return; // lets the form post go to sign-in; no JS state change
			}
			event.preventDefault();
			const prev = { followed: !! state.followed[ id ], count: state.followerCount[ id ] ?? 0 };
			const want = ! prev.followed;
			const token = Symbol( 'follow' );
			pending.set( id, token );
			// optimistic write, in the owner
			state.followed[ id ] = want;
			state.followerCount[ id ] = Math.max( 0, prev.count + ( want ? 1 : -1 ) );
			try {
				const res = yield fetch( state.restUrl + id + '/follow', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': state._nonce },
					body: JSON.stringify( { follow: want } ),
				} );
				if ( ! res.ok ) {
					throw new Error( 'follow failed' );
				}
				const data = yield res.json();
				if ( pending.get( id ) === token ) {
					// server truth wins
					state.followed[ id ] = data.followed;
					state.followerCount[ id ] = data.followerCount;
				}
			} catch ( e ) {
				if ( pending.get( id ) === token ) {
					state.followed[ id ] = prev.followed; // roll back
					state.followerCount[ id ] = prev.count;
					state.error = 'Could not update. Try again.';
				}
			}
		},
	},
} );

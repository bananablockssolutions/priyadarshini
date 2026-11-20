/* Editor side of the pd blocks. No build step: plain script on the wp globals.
   Leaf blocks preview with their server render; the routed main holds the page's blocks. */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const ServerSideRender = wp.serverSideRender;
	const { PanelBody, ToggleControl, Disabled } = wp.components;

	const preview = ( name ) =>
		function Edit( props ) {
			const blockProps = useBlockProps();
			const controls =
				name === 'pd/work-card'
					? el(
							InspectorControls,
							null,
							el(
								PanelBody,
								{ title: 'Card' },
								el( ToggleControl, {
									label: 'Show the maker line',
									checked: !! props.attributes.showMaker,
									onChange: ( v ) => props.setAttributes( { showMaker: v } ),
								} )
							)
					  )
					: null;
			return el(
				'div',
				blockProps,
				controls,
				el( Disabled, null, el( ServerSideRender, { block: name, attributes: props.attributes, urlQueryArgs: { post_id: props.context?.postId } } ) )
			);
		};

	[ 'pd/app-shell', 'pd/work-card', 'pd/work-gallery', 'pd/buy-box', 'pd/artisan-card', 'pd/follow-button', 'pd/cover', 'pd/craft-makers', 'pd/shop-toolbar', 'pd/filters-rail' ].forEach( ( name ) => {
		if ( wp.blocks.getBlockType( name ) && wp.blocks.getBlockType( name ).edit ) {
			return;
		}
		registerBlockType( name, { edit: preview( name ), save: () => null } );
	} );

	registerBlockType( 'pd/routed-main', {
		edit: function Edit() {
			const blockProps = useBlockProps( { className: 'pd-main' } );
			return el( 'main', useInnerBlocksProps( blockProps ) );
		},
		save: () => el( InnerBlocks.Content ),
	} );
} )( window.wp );

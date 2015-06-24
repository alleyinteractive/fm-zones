jQuery( function( $ ) {
	function fmz_add_post( post, container ) {
		var tpl = _.template( $( '.fmz-post-template', container ).html() );
		post.i = $( '.zone-posts-list', container ).children().length + 1;
		var $el = $( tpl( post ) ).hide();
		$( '.zone-posts-list', container ).append( $el.fadeIn() );
	}

	function fmz_reorder_posts() {
		$( '.zone-post', this ).each( function( index ) {
			$( '.zone-post-position', this ).text( index + 1 );
		} );
	}

	$( '#post-body' ).on( 'change', '.zone-post-latest', function() {
		if ( $( this ).val() ) {
			try {
				post = JSON.parse( $( this ).val() );
				var container = $( this ).closest( '.fm-zone-posts-wrapper' );
				fmz_add_post( post, container );
				$( 'option:selected', this ).remove();
			} catch ( e ) {
				// in case the JSON is invalid
			}
		}
	} );

	$( '.zone-posts-list' ).sortable( {
		stop: fmz_reorder_posts
		, placeholder: 'ui-state-highlight'
		, forcePlaceholderSize: true
	} );
} );
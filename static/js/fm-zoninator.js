;( function( $ ) {

	var FM_Zone = function( element ) {
		var $container = $( element )
		  , obj = this
		  , tpl = _.template( $( '.fmz-post-template', $container ).html() )
		;

		var reorder_posts = function() {
			$( '.zone-post', $container ).each( function( index ) {
				$( '.zone-post-position', this ).text( index + 1 );
			} );
		}

		var add_post = function( post ) {
			post.i = $( '.zone-posts-list', $container ).children().length + 1;
			var $el = $( tpl( post ) ).hide();
			$( '.zone-posts-list', $container ).append( $el.fadeIn() );
		}

		$( '.zone-post-latest', $container ).change( function() {
			if ( $( this ).val() ) {
				try {
					post = JSON.parse( $( this ).val() );
					add_post( post );
					$( 'option:selected', this ).remove();
				} catch ( e ) {
					// in case the JSON is invalid
				}
			}
		} );

		$( '.zone-posts-list', $container ).sortable( {
			stop: reorder_posts
			, placeholder: 'ui-state-highlight'
			, forcePlaceholderSize: true
		} );
	};


	$.fn.fm_zonify = function() {
		return this.each( function() {
			var $element = $( this );

			// Return early if this element already has a plugin instance
			if ( $element.data( 'fm_zonify' ) ) {
				return;
			}

			// Instantiate our object
			var fm_zone = new FM_Zone( this );

			// Store plugin object in this element's data
			$element.data( 'fm_zonify', fm_zone );
		} );
	};


	$( document ).ready( function() {
		$( '.fm-zone-posts-wrapper' ).fm_zonify();
	} );

} )( jQuery );

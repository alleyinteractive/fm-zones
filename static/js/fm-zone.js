;( function( $ ) {

	var FM_Zone = function( element, posts ) {
		var $container = $( element )
		  , obj = this
		  , tpl = _.template( $( '.fmz-post-template', $container ).html() )
		  , $search_field = $( '.zone-post-search', $container )
		  , field_name = $( 'input:hidden.zone-name', $container ).attr( 'name' ) + '[]';
		;

		var item_tpl = _.template(
			'<a>'
				+ '<span class="image"><%= thumb %></span>'
				+ '<span class="details">'
					+ '<span class="title"><%- title %></span>'
					+ '<span class="type"><%- post_type %></span>'
					+ '<span class="date"><%- date %></span>'
					+ '<span class="status"><%- post_status %></span>'
				+ '</span>'
			+ '</a>'
		);

		if ( ! posts ) {
			posts = [];
		}

		var after_sort = function( event, ui ) {
			// Reorder the sending container
			obj.reorder_posts();

			// Reorder the receiving container
			receiving_container = $( ui.item ).closest( '.fm-zone-posts-wrapper' );
			var zone = receiving_container.data( 'fm_zonify' );
			if ( zone ) {
				zone.reorder_posts();
				zone.remove_from_recents( $( ui.item ).data( 'post-id' ) );
			}
		}

		var add_post = function( post ) {
			post.i = $( '.zone-posts-list', $container ).children().length + 1;
			var $el = $( tpl( post ) ).hide();
			$( 'input:hidden', $el ).attr( 'name', field_name );
			$( '.zone-posts-list', $container ).append( $el.fadeIn() );
			obj.remove_from_recents( post.id );
		}

		obj.remove_from_recents = function( id ) {
			$( '.zone-post-latest option[data-post-id="' + id + '"]', $container ).remove();
		}

		obj.reorder_posts = function() {
			$( '.zone-post:visible', $container ).each( function( index ) {
				$( '.zone-post-position', this ).text( index + 1 );
				$( 'input:hidden', this ).attr( 'name', field_name );
			} );
		}

		obj.get_current_ids = function() {
			return _.map( $( '.zone-post', $container ), function( el ) {
				return $( el ).data( 'post-id' );
			} );
		}

		$( '.zone-post-latest', $container ).change( function() {
			if ( $( this ).val() ) {
				try {
					post = JSON.parse( $( this ).val() );
					add_post( post );
				} catch ( e ) {
					// in case the JSON is invalid
				}
			}
		} );

		$( '.zone-posts-list', $container ).sortable( {
			stop: after_sort
			, connectWith: '.fm-zone-posts-connected .zone-posts-list'
			, placeholder: 'ui-state-highlight'
			, forcePlaceholderSize: true
		} );

		$container.on( 'click', '.delete', function( e ) {
			e.preventDefault();
			$( this ).closest( '.zone-post' ).fadeOut( 'normal', function() {
				$( this ).remove();
				obj.reorder_posts();
			} );
		} );

		$search_field
			.bind( 'loading.start', function( e ) {
				$( this ).addClass( 'loading' );
			} )
			.bind( 'loading.end', function( e ) {
				$( this ).removeClass( 'loading' );
			} )
			.autocomplete( {
				minLength: 3
				, appendTo: $container
				, delay: 500
				, source: function( request, response ) {
					// Append more request vars
					request.action = $search_field.data( 'action' );
					request._nonce = $search_field.data( 'nonce' );
					request.fm_context = $search_field.data( 'context' );
					request.fm_subcontext = $search_field.data( 'subcontext' );
					request.fm_args = $search_field.data( 'args' );
					request.exclude = obj.get_current_ids();


					var acajax = $.post( ajaxurl, request, function( data, status, xhr ) {
						if ( xhr === acajax ) {
							response( data.data );
						}
						$search_field.trigger( 'loading.end' );
					}, 'json' );
				}
				, select: function( e, ui ) {
					add_post( ui.item );
				}
				, search: function( e, ui ) {
					$search_field.trigger( 'loading.start' );
				}
			} );

		/* Manipulate the results */
		var autocomplete = $search_field.data( 'autocomplete' ) || $search_field.data( 'ui-autocomplete' );
		autocomplete._renderItem = function( ul, item ) {
			return $( '<li></li>' )
				.data( 'item.autocomplete', item )
				.append( item_tpl( item ) )
				.appendTo( ul )
				;
		}

		// Lastly, populate with existing data
		_.each( posts, add_post );
	};


	$.fn.fm_zonify = function( posts ) {
		return this.each( function() {
			var $element = $( this );

			// Return early if this element already has a plugin instance
			if ( $element.data( 'fm_zonify' ) ) {
				return;
			}

			// Instantiate our object
			var fm_zone = new FM_Zone( this, posts );

			// Store plugin object in this element's data
			$element
				.data( 'fm_zonify', fm_zone )
				.addClass( 'zonified' )
			;
		} );
	};


	$( document ).ready( function() {
		var zonifier = function() {
			var posts = [];
			if ( $( this ).data( 'current' ) ) {
				try {
					posts = $( this ).data( 'current' );
				} catch ( e ) {
					// in case the JSON is invalid
				}
			}

			$( this ).fm_zonify( posts );
		}

		$( '.fm-zone-posts-wrapper:visible' ).each( zonifier );

		$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', function() {
			$( '.fm-zone-posts-wrapper:visible:not(.zonified)' ).each( zonifier );
		} );
	} );

} )( jQuery );

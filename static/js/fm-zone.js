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
				+ '<span class="image"><img src="<%- thumb %>" /></span>'
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
			if ( zone && zone !== obj ) {
				zone.reorder_posts();
				zone.remove_from_recents( $( ui.item ).data( 'post-id' ) );
			}
		}

		var add_post = function( post ) {
			var limit = $container.data( 'limit' ) - 0,
				$zone_posts = $( '.zone-posts-list > .zone-post', $container );

			if ( limit > 0 && $zone_posts.length >= limit ) {
				obj.error_message( fm_zone_l10n.too_many_items );
				return;
			}

			var $placeholders = $( '.zone-placeholder', $container );
			post.i = $zone_posts.length + 1;
			var $el = $( tpl( post ) ).hide();

			$container.trigger( 'fm-zone-pre-add-post', [ $el, post ] );
			$( 'input:hidden', $el ).attr( 'name', field_name );
			$el.fadeIn();
			if ( $placeholders.length ) {
				$placeholders.first().replaceWith( $el );
			} else {
				$( '.zone-posts-list', $container ).append( $el );
			}
			obj.remove_from_recents( post.id );
			$container.trigger( 'fm-zone-after-add-post', [ $el, post ] );
		}

		var maybe_populate_placeholders = function() {
			var placeholders = $container.data( 'placeholders' );
			if ( ! placeholders ) {
				return;
			}

			var count = $( '.zone-posts-list', $container ).children().length;

			// If we have enough posts, no need to add placeholders
			if ( count >= placeholders ) {
				return;
			}

			// debugger;

			for ( var i = 0; i < placeholders - count; i++ ) {
				$( '.zone-posts-list', $container ).append( $( '<div class="zone-placeholder" />' ).text( fm_zone_l10n.placeholder_content ) );
			}
		}

		obj.error_message = function( msg ) {
			var $div = $( '.fmz-notice', $container );
			if ( ! $div.length ) {
				$div = $( '<div class="fmz-notice" />' ).hide();
				$( '.zone-posts-list', $container ).before( $div );
			}
			$div.text( msg ).fadeIn();
			setTimeout( function() { $div.fadeOut(); }, 10000 );
		}

		obj.remove_from_recents = function( id ) {
			$( '.zone-post-latest option[data-post-id="' + id + '"]', $container ).remove();
		}

		obj.reorder_posts = function() {
			$container.trigger( 'fm-zone-reorder-start' );
			$( '.zone-post:visible', $container ).each( function( index ) {
				$( '.zone-post-position', this ).text( index + 1 );
				$( 'input:hidden', this ).attr( 'name', field_name );
			} );
			$container.trigger( 'fm-zone-reorder-stop' );
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
			, items: '.zone-post'
		} );

		$container.on( 'click', '.delete', function( e ) {
			e.preventDefault();
			$( this ).closest( '.zone-post' ).fadeOut( 'normal', function() {
				$( this ).remove();
				obj.reorder_posts();
				maybe_populate_placeholders();
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

					if ( 1 === $search_field.data('use-datasource' ) ) {
						request.fm_search_nonce = $search_field.data( 'nonce' );
						request.fm_autocomplete_search = request.term;
					} else {
						request._nonce = $search_field.data( 'nonce' );
					}

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

		// Remove hidden elements
		$( '.fmz-remove-if-js', $container ).remove();

		// If we have a limit, but not all the posts, add in a placeholder
		maybe_populate_placeholders();
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

	/**
	 * A wrapper for DOM ready handlers in the global wp object and jQuery,
	 * with a shim fallback that mimics the behavior of wp.domReady.
	 * Ensures that metaboxes have loaded before initializing functionality.
	 * @param {function} callback - The callback function to execute when the DOM is ready.
	 */
	$.fn.fm_zone_load_module = function( callback ) {
		if ( 'object' === typeof wp && 'function' === typeof wp.domReady ) {
			wp.domReady( callback );
		} else if ( jQuery ) {
			jQuery( document ).ready( callback );
		} else {
			// Shim wp.domReady.
			if (
				document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
				document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
			) {
				callback();
				return;
			}

			// DOMContentLoaded has not fired yet, delay callback until then.
			document.addEventListener( 'DOMContentLoaded', callback );
		}
	}

	$( this ).fm_zone_load_module( function() {
		var zonifier = function() {
			if ( $( this ).closest( '.fmjs-proto' ).length ) {
				return;
			}

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

<?php

/**
 * Zoninator Act-Alike Fieldmanager Field
 */
class Fieldmanager_Zone_Field extends Fieldmanager_Field {

	private $field_id;

	public $query_args = array();

	public $autocomplete_attributes = array();

	public $accept_from_other_zones = false;

	public $ajax_args = array();

	public $post_limit = 0;

	public $placeholders = 0;

	public static $assets_enqueued = false;

	public function __construct( $label = '', $options = array() ) {
		$this->template = FMZ_PATH . '/templates/field.php';
		$this->multiple = true;
		$this->sanitize = 'absint';

		parent::__construct( $label, $options );

		// Hook after the field has been fully constructed, which is on `init`.
		add_action( 'wp_loaded', array( $this, 'hook_ajax_action' ) );

		// Only enqueue assets once per request
		if ( ! self::$assets_enqueued ) {
			self::$assets_enqueued = true;
			if ( did_action( 'admin_enqueue_scripts' ) ) {
				$this->assets();
			} else {
				add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
			}
		}
	}

	public function assets() {
		wp_enqueue_style( 'fm-zone-jquery-ui', FMZ_URL . '/static/jquery-ui/smoothness/jquery-ui.theme.css', false, FMZ_VERSION, 'all' );
		wp_enqueue_style( 'fm-zone-styles', FMZ_URL . '/static/css/fm-zone.css', false, FMZ_VERSION, 'all' );
		wp_enqueue_script( 'fm-zone-script', FMZ_URL . '/static/js/fm-zone.js', array( 'jquery', 'underscore', 'jquery-ui-sortable', 'jquery-ui-autocomplete' ), FMZ_VERSION, true );
		wp_localize_script( 'fm-zone-script', 'fm_zone_l10n', array(
			'too_many_items' => __( "You've reached the post limit on this field. To add more posts, you must remove one or more.", 'fm-zones' ),
			'placeholder_content' => apply_filters( 'fm-zones-placeholder-content', __( 'Select a post to fill this position', 'fm-zones' ) ),
		) );
	}

	/**
	 * Hook into the ajax action for this field.
	 *
	 * This is done after the field has been defined so we have access to the
	 * field's ancestors.
	 */
	public function hook_ajax_action() {
		add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'ajax_request' ) );
	}

	public function form_element( $value = null ) {
		list( $context, $subcontext ) = fm_get_context();
		$this->autocomplete_attributes['data-context'] = $context;
		$this->autocomplete_attributes['data-subcontext'] = $subcontext;
		$this->autocomplete_attributes['data-action'] = $this->get_ajax_action( $this->name );
		$this->autocomplete_attributes['data-nonce'] = wp_create_nonce( 'fm_search_nonce' );
		$this->autocomplete_attributes['data-args'] = json_encode( $this->ajax_args );

		return parent::form_element( $value );
	}

	/**
	 * Generates an HTML attribute string based on the value of
	 * $this->autocomplete_attributes.
	 *
	 * @see Fieldmanager_Field::$autocomplete_attributes
	 * @return string HTML attributes ready to insert into an HTML tag.
	 */
	public function get_element_autocomplete_attributes() {
		$attr_str = array();
		foreach ( $this->autocomplete_attributes as $attr => $val ) {
			if ( true === $val ) {
				$attr_str[] = sanitize_key( $attr );
			} else {
				$attr_str[] = sprintf( '%s="%s"', sanitize_key( $attr ), esc_attr( $val ) );
			}
		}
		return implode( ' ', $attr_str );
	}

	/**
	 * Get the most recent posts.
	 *
	 * @todo limit to certain time period (last 3 months?) by default?
	 *
	 * @param  array  $except Post IDs to exclude, because they're already
	 *                        in the zone.
	 * @return array {@see Fieldmanager_Zone_Field::format_posts()}
	 */
	public function get_recent_posts( $except = array() ) {
		return $this->get_posts( array(
			'post__not_in' => $except,
			'orderby' => 'date',
			'order' => 'desc',
		) );
	}

	/**
	 * Get an array of posts matching default and given criteria.
	 *
	 * @todo limit to last year(?) by default for performance, make that an
	 *       option.
	 *
	 * @param  array  $args WP_Query args
	 * @return array {@see Fieldmanager_Zone_Field::format_posts()}
	 */
	public function get_posts( $args = array() ) {
		$args = array_merge(
			array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => 10,
				'suppress_filters' => false,
			),
			$this->query_args,
			$args
		);

		return $this->format_posts( get_posts( $args ) );
	}

	public function format_posts( $posts, $format = 'array' ) {
		$return = array();

		foreach ( $posts as $post ) {
			/**
			 * @todo filter this so that the output can be customized
			 */
			$return[] = array(
				'id'     => $post->ID,
				'post_status' => $post->post_status,
				'post_type'   => $post->post_type,
				'title'  => $post->post_title,
				'date'   => $post->post_date,
				'thumb'  => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, array( 50, 50 ) ) : '',
				'link'   => get_permalink( $post->ID ),
			);
		}

		if ( 'json' == $format ) {
			return wp_json_encode( $return );
		} else {
			return $return;
		}
	}

	public function get_current_posts_json( $ids ) {
		if ( empty( $ids ) ) {
			return '[]';
		} else {
			return $this->format_posts(
				get_posts( array(
					'post__in' => $ids,
					'post_status' => 'any',
					'post_type' => get_post_types(),
					'orderby' => 'post__in',
					'order' => 'asc',
					'posts_per_page' => count( $ids ),
					'suppress_filters' => false,
				) ),
				'json'
			);
		}
	}

	public function get_ajax_action() {
		return $this->get_field_id() . '_search_ajax';
	}

	public function get_field_id() {
		if ( ! $this->field_id ) {
			$el = $this;
			$id_slugs = array();
			while ( $el ) {
				array_unshift( $id_slugs, $el->name );
				$el = $el->parent;
			}
			$this->field_id = 'fm-' . implode( '-', $id_slugs );
		}
		return $this->field_id;
	}

	public function ajax_request() {
		check_ajax_referer( 'fm_search_nonce', '_nonce' );

		if ( empty( $_POST['term'] ) ) {
			wp_send_json_error( __( 'Search term is empty!', 'fm-zones' ) );
		}

		$args = array(
			's' => sanitize_text_field( wp_unslash( $_POST['term'] ) ),
			'orderby' => 'relevance',
		);
		if ( ! empty( $_POST['exclude'] ) ) {
			$args['post__not_in'] = array_map( 'intval', (array) $_POST['exclude'] );
		}
		$posts = $this->get_posts( $args );

		wp_send_json_success( $posts );
	}

	public function maybe_connect() {
		if ( $this->accept_from_other_zones ) {
			echo ' fm-zone-posts-connected';
		}
	}

	/**
	 * Presave function, which handles sanitization and validation
	 *
	 * @param int|array $values This will either be a post ID or array of
	 *                          post IDs.
	 * @return int|array Sanitized values.
	 */
	public function presave( $values, $current_values = array() ) {
		if ( is_array( $values ) ) {
			// Fieldmanager_Field doesn't like it when $values is an array,
			// so we need to replicate what it does here.
			foreach ( $values as $value ) {
				foreach ( $this->validate as $func ) {
					if ( ! call_user_func( $func, $value ) ) {
						$this->_failed_validation( sprintf(
							__( 'Input "%1$s" is not valid for field "%2$s" ', 'fm-zones' ),
							(string) $value,
							$this->label
						) );
					}
				}
			}

			return array_map( $this->sanitize, $values );
		} else {
			return parent::presave( $values, $current_values );
		}
	}

	/**
	 * Alter values before they go through the sanitize & save routine.
	 *
	 * Here, we're enforcing $post_limit.
	 *
	 * @param  array $values Field values being saved. This will either be
	 *                       an array of ints if this is a singular field,
	 *                       or an array of array of ints if $limit != 1.
	 * @param  array $current_values Field's previous values.
	 * @return array Altered values.
	 */
	public function presave_alter_values( $values, $current_values = array() ) {
		if ( $this->post_limit > 0 ) {
			if ( ! empty( $values[0] ) && is_array( $values[0] ) ) {
				// If this is an array of arrays, limit each individually
				$values = array_filter( $values );
				foreach ( $values as $i => $value ) {
					if ( ! is_array( $value ) ) {
						unset( $values[ $i ] );
					} else {
						$values[ $i ] = array_slice( $value, 0, $this->post_limit );
					}
				}
			} elseif ( is_array( $values ) ) {
				// this is an array of ints, so we can enforce the limit on it
				$values = array_slice( $values, 0, $this->post_limit );
			}
		}

		return parent::presave_alter_values( $values, $current_values );
	}
}

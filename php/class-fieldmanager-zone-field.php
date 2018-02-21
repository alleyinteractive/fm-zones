<?php

/**
 * Zoninator Act-Alike Fieldmanager Field
 */
class Fieldmanager_Zone_Field extends Fieldmanager_Field {
	/**
	 * Unused
	 *
	 * @deprecated
	 * @var null
	 */
	private $field_id;

	/**
	 * Legacy WP_Query args array
	 *
	 * Offloaded to datasource for back-compat
	 *
	 * @var array
	 */
	public $query_args = array();

	/**
	 * Default post arguments.
	 *
	 * @var array
	 */
	protected $default_args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => 10,
		'suppress_filters' => false,
	);

	/**
	 * Accept posts from another zone?
	 *
	 * @var bool
	 */
	public $accept_from_other_zones = false;

	/**
	 * Additional Ajax arguments
	 *
	 * @var array
	 */
	public $ajax_args = array();

	/**
	 * How many posts should does this zone accept?
	 *
	 * @var int
	 */
	public $post_limit = 0;

	/**
	 * How many placeholder slots to show
	 *
	 * @var int
	 */
	public $placeholders = 0;

	/**
	 * Have assets been enqueued?
	 *
	 * @var bool
	 */
	public static $assets_enqueued = false;

	/**
	 * Set up field
	 *
	 * @param string $label
	 * @param array  $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->template = FMZ_PATH . '/templates/field.php';
		$this->multiple = true;
		$this->sanitize = 'absint';

		parent::__construct( $label, $options );

		// Gracefully handle implementations preceding datasource support.
		if ( empty( $this->datasource ) ) {
			$this->datasource = new Fieldmanager_Datasource_Zone_Field( array(
				'query_args' => $this->query_args,
			) );

			$this->query_args = array();
		} else {
			$datasource_provides_posts = $this->datasource instanceof Fieldmanager_Datasource_Post;
			$datasource_provides_posts = (bool) apply_filters( 'fm_zone_datasource_provides_posts', $datasource_provides_posts );

			if ( ! $datasource_provides_posts ) {
				/* translators: 1: Filter tag. */
				$message = esc_html( sprintf( __( 'You must specify a datasource that returns WP_Post objects. Use the \'%s\' filter to indicate that a custom datasource returns post objects.', 'fm-zones' ), 'fm_zone_datasource_provides_posts' ) );
				if ( Fieldmanager_Field::$debug ) {
					throw new FM_Developer_Exception( $message );
				} else {
					wp_die( $message, esc_html__( 'Unsupported Datasource', 'fieldmanager' ) );
				}
			}
		}

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

	/**
	 * Provide back-compat for reading deprecated property
	 *
	 * @param string $property Deprecated property.
	 * @return mixed
	 */
	public function __get( $property ) {
		if ( 'autocomplete_attributes' === $property ) {
			return $this->attributes;
		}
	}

	/**
	 * Provide back-compat for updating deprecated property
	 *
	 * @param string $property Deprecated property.
	 * @param mixed  $value New property value.
	 */
	public function __set( $property, $value ) {
		if ( 'autocomplete_attributes' === $property ) {
			$this->attributes = $value;
		}
	}

	/**
	 * Enqueue field's static assets
	 */
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
	 * Deprecated Ajax hook
	 *
	 * @deprecated
	 */
	public function hook_ajax_action() {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12' );
	}

	public function form_element( $value = null ) {
		list( $context, $subcontext ) = fm_get_context();
		$this->attributes['data-context'] = $context;
		$this->attributes['data-subcontext'] = $subcontext;
		$this->attributes['data-nonce'] = wp_create_nonce( 'fm_search_nonce' );
		$this->attributes['data-action'] = $this->datasource->get_ajax_action( $this->name );
		$this->attributes['data-use-datasource'] = 1;
		$this->attributes['data-args'] = wp_json_encode( $this->ajax_args );

		return parent::form_element( $value );
	}

	/**
	 * Generates an HTML attribute string based on the value of
	 * $this->attributes.
	 *
	 * @see Fieldmanager_Field::$attributes
	 * @deprecated
	 * @return string HTML attributes ready to insert into an HTML tag.
	 */
	public function get_element_autocomplete_attributes() {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12', __CLASS__ . '::get_element_attributes' );
		return $this->get_element_attributes();
	}

	/**
	 * Get the most recent posts.
	 *
	 * @todo limit to certain time period (last 3 months?) by default?
	 *
	 * @param  array  $except Post IDs to exclude, because they're already
	 *                        in the zone.
	 * @return array {@see Fieldmanager_Datasource_Zone_Field::format_posts()}
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
	 * @return array {@see Fieldmanager_Datasource_Zone_Field::format_posts()}
	 */
	public function get_posts( $args = array() ) {
		$args = array_merge(
			$this->default_args,
			$this->datasource->query_args,
			$args
		);

		$this->query_args = $args;
		add_filter( 'fm_zones_get_posts_query_args', array( $this, 'set_query_args' ), 9 );

		$posts = $this->datasource->get_items();

		$this->query_args = null;
		remove_filter( 'fm_zones_get_posts_query_args', array( $this, 'set_query_args' ), 9 );

		return $this->datasource->format_posts( $posts );
	}

	/**
	 * Force datasource query arguments
	 *
	 * @param array $query_args WP_Query arguments.
	 * @return array
	 */
	public function set_query_args( $query_args ) {
		return $this->query_args;
	}

	/**
	 * Format post data for display
	 *
	 * @deprecated
	 * @param array  $posts Array of post objects to format.
	 * @param string $format Return format, either `json` or `array`.
	 * @return mixed
	 */
	public function format_posts( $posts, $format = 'array' ) {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12', __CLASS__ . '->datasource->format_posts' );
		return $this->datasource->format_posts( $posts, $format );
	}

	/**
	 * Format an array of post IDs
	 *
	 * @param array $ids Array of post IDs to format
	 * @return string
	 */
	public function get_current_posts_json( $ids ) {
		if ( empty( $ids ) ) {
			return wp_json_encode( array() );
		}

		$posts = get_posts( array(
			'post__in' => $ids,
			'post_status' => 'any',
			'post_type' => 'any',
			'orderby' => 'post__in',
			'order' => 'asc',
			'posts_per_page' => count( $ids ),
			'suppress_filters' => false,
		) );

		return $this->datasource->format_posts( $posts, 'json' );
	}

	/**
	 * Get Ajax action
	 *
	 * @deprecated
	 * @return string
	 */
	public function get_ajax_action() {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12' );
		return '';
	}

	/**
	 * Get field's HTML ID
	 *
	 * @deprecated
	 * @return string
	 */
	public function get_field_id() {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12', __CLASS__ . '::get_element_id' );
		return $this->get_element_id();
	}

	/**
	 * Handle Ajax request
	 *
	 * @deprecated
	 */
	public function ajax_request() {
		_deprecated_function( __METHOD__, 'fm-zones-0.1.12' );
	}

	/**
	 * Add class to indicate this zone accepts posts from another
	 */
	public function maybe_connect() {
		if ( $this->accept_from_other_zones ) {
			echo ' fm-zone-posts-connected';
		}
	}

	/**
	 * Offload preloading values to datasource
	 *
	 * @param array $values Values to alter.
	 * @return array
	 */
	public function preload_alter_values( $values ) {
		return $this->datasource->preload_alter_values( $this, $values );
	}

	/**
	 * Presave function, which handles sanitization and validation
	 *
	 * @param int|array $values This will either be a post ID or array of
	 *                          post IDs.
	 * @return int|array Sanitized values.
	 */
	public function presave( $values, $current_values = array() ) {
		if ( ! is_array( $values ) ) {
			return $this->datasource->presave( $this, $values, $current_values );
		}

		// Fieldmanager_Field doesn't like it when $values is an array,
		// so we need to replicate what its validation here.
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

		foreach ( $values as $key => $value ) {
			$values[ $key ] = $this->datasource->presave( $this, $value, empty( $current_values[ $key ] ) ? array() : $current_values[ $key ] );
		}

		return $values;
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

		return $this->datasource->presave_alter_values( $this, $values, $current_values );
	}
}

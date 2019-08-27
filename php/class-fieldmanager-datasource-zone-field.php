<?php
/**
 * Shim to use Fieldmanager Post Datasources for FM Zones
 *
 * @package FM Zones
 */

/**
 * Class Fieldmanager_Datasource_Zone_Field
 */
class Fieldmanager_Datasource_Zone_Field extends Fieldmanager_Datasource_Post {
	/**
	 * Unmodified query args
	 *
	 * Used to restore original query args after filtering
	 * for specific datasource request.
	 *
	 * @var array|null
	 */
	protected $original_query_args = null;

	/**
	 * Set up the datasource
	 *
	 * @param array $options Datasource options.
	 */
	public function __construct( array $options = array() ) {
		parent::__construct( $options );

		add_filter( 'fm_zones_excluded_posts', array( $this, 'set_excluded_posts' ) );
	}

	/**
	 * Return matching posts, formatted as FM Zones expects
	 *
	 * @param string $fragment Search term.
	 * @return array
	 */
	public function get_items( $fragment = null ) {
		if ( is_callable( $this->query_callback ) ) {
			return $this->do_get_items( $fragment );
		}

		/**
		 * Filter query arguments, for back-compat
		 *
		 * Original query arguments are retained and restored
		 * to make the filter behave as if it's datasource-specific.
		 *
		 * @param array $args An array of WP_Query arguments.
		 */
		$this->original_query_args = $this->query_args;
		$this->query_args = apply_filters( 'fm_zones_get_posts_query_args', $this->query_args );

		// Backcompat sorting.
		if ( ! isset( $this->query_args['orderby'] ) || empty( $this->query_args['orderby'] ) ) {
			$this->query_args['orderby'] = 'relevance';
		}

		$items = $this->do_get_items( $fragment );

		$this->query_args = $this->original_query_args;
		$this->original_query_args = null;

		return $items;
	}

	/**
	 * Get results and ensure format matches what field expects
	 *
	 * @param string $fragment Search term.
	 * @return array
	 */
	protected function do_get_items( $fragment ) {
		$items = parent::get_items( $fragment );
		return $this->prepare_datasource_items( $items );
	}

	/**
	 * Return matched posts, formatted as FM Zones JS expects
	 *
	 * @param string $fragment Search term.
	 * @return array
	 */
	public function get_items_for_ajax( $fragment = null ) {
		$items = $this->get_items( $fragment );
		$items = $this->format_posts( $items );

		$data = array(
			'success' => true,
			'data'    => $items,
		);

		return $data;
	}

	/**
	 * Reformat post objects for JS
	 *
	 * @param array  $posts Array of post objects to format.
	 * @param string $format Return format, either `json` or `array`.
	 * @return array|false|string
	 */
	public function format_posts( $posts, $format = 'array' ) {
		$return = array();

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				if ( Fieldmanager_Field::$debug ) {
					throw new FM_Developer_Exception( esc_html__( 'Datasource must return array of WP_Post objects.', 'fm-zones' ) );
				}

				continue;
			}

			$return[] = array(
				'id'          => $post->ID,
				'post_status' => $post->post_status,
				'post_type'   => $post->post_type,
				'title'       => $post->post_title,
				'date'        => $post->post_date,
				'thumb'       => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, array( 50, 50 ) ) : '',
				'link'        => get_permalink( $post->ID ),
			);
		}

		if ( 'json' === $format ) {
			return wp_json_encode( $return, JSON_HEX_AMP );
		} else {
			return $return;
		}
	}

	/**
	 * Convert datasource items array to array of WP_Post objects
	 *
	 * @param array $posts Array keyed by post ID, value irrelevant.
	 * @return array
	 */
	public function prepare_datasource_items( $posts ) {
		$posts = array_keys( $posts );
		$posts = array_map( 'absint', $posts );
		$posts = array_filter( $posts );

		$excluded_posts = apply_filters( 'fm_zones_excluded_posts', array() );
		$posts = array_diff( $posts, $excluded_posts );

		$posts = array_map( 'get_post', $posts );

		return $posts;
	}

	/**
	 * Exclude already-chosen posts
	 *
	 * @param array $excluded Post IDs already in use.
	 * @return array
	 */
	public function set_excluded_posts( $excluded ) {
		if ( isset( $_REQUEST['exclude'] ) && is_array( $_REQUEST['exclude'] ) ) { // WPCS: input var ok. CSRF ok.
			$to_exclude = array_map( 'absint', $_REQUEST['exclude'] ); // WPCS: input var ok. CSRF ok.
			$to_exclude = array_filter( $to_exclude );

			$excluded = array_merge( $excluded, $to_exclude );
		}

		return $excluded;
	}

	/**
	 * Get an action to register by hashing (non cryptographically for speed)
	 * the options that make this datasource unique.
	 *
	 * @return string
	 */
	public function get_ajax_action() {
		if ( ! empty( $this->ajax_action ) ) {
			return $this->ajax_action;
		}

		$unique_key = wp_json_encode( $this->query_args );
		$unique_key .= (string) $this->query_callback;
		return 'fm_datasource_zone_field' . crc32( $unique_key );
	}
}

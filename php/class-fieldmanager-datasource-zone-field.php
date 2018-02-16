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
	 * Return matching posts, formatted as FM Zones expects
	 *
	 * @param string $fragment Search term.
	 * @return array
	 */
	public function get_items( $fragment = null ) {
		$items = parent::get_items( $fragment );
		return $this->prepare_datasource_items( $items, $this->query_args );
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

		$data = [
			'success' => true,
			'data'    => $items,
		];

		return $data;
	}

	/**
	 * Reformat post objects for JS
	 *
	 * @param array  $posts Array of post objects to format.
	 * @param string $format Return format, either `json` or `array`.
	 * @return mixed
	 */
	public function format_posts( array $posts, string $format = 'array' ) {
		$return = [];

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				if ( Fieldmanager_Field::$debug ) {
					throw new FM_Developer_Exception( esc_html__( 'Datasource must return array of WP_Post objects.', 'fm-zones' ) );
				}

				continue;
			}

			$return[] = [
				'id'          => $post->ID,
				'post_status' => $post->post_status,
				'post_type'   => $post->post_type,
				'title'       => $post->post_title,
				'date'        => $post->post_date,
				'thumb'       => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, [ 50, 50 ] ) : '',
				'link'        => get_permalink( $post->ID ),
			];
		}

		if ( 'json' === $format ) {
			return wp_json_encode( $return );
		} else {
			return $return;
		}
	}

	/**
	 * Convert datasource items array to array of WP_Post objects
	 *
	 * @param array $posts Array keyed by post ID, value irrelevant.
	 * @param array $query_args WP_Query arguments.
	 * @return array
	 */
	public function prepare_datasource_items( array $posts, array $query_args ) : array {
		// Back-compat excluded posts handling.
		if ( isset( $query_args['post__not_in'] ) ) {
			foreach ( $query_args['post__not_in'] as $excluded ) {
				unset( $posts[ $excluded ] );
			}
		}

		$posts = array_keys( $posts );
		$posts = array_map( 'absint', $posts );
		$posts = array_filter( $posts );
		$posts = array_map( 'get_post', $posts );

		return $posts;
	}
}

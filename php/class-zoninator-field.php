<?php

if ( class_exists( 'Fieldmanager_Field' ) ) {

	/**
	 * Zoninator Act-Alike Fieldmanager Field
	 */
	class Zoninator_Field extends Fieldmanager_Field {

		public $query_args = array();

		public function __construct( $label = '', $options = array() ) {
			$this->template = FMZ_PATH . '/templates/field.php';

			parent::__construct( $label, $options );
		}

		/**
		 * Get the most recent posts.
		 *
		 * @todo limit to certain time period (last 3 months?) by default?
		 *
		 * @param  array  $except Post IDs to exclude, because they're already
		 *                        in the zone.
		 * @return array {@see Zoninator_Field::format_posts()}
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
		 * @return array {@see Zoninator_Field::format_posts()}
		 */
		public function get_posts( $args = array() ) {
			$args = array_merge(
				array(
					'post_type' => 'post',
					'post_status' => 'publish',
					'posts_per_page' => 10,
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
					'status' => $post->post_status,
					'type'   => $post->post_type,
					'title'  => $post->post_title,
					'date'   => $post->post_date,
					'thumb'  => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail( $post->ID, array( 50, 50 ) ) : '',
					'link'   => get_permalink( $post->ID ),
				);
			}

			if ( 'json' == $format ) {
				return json_encode( $return );
			} else {
				return $return;
			}
		}

		public function the_current_posts_json( $ids ) {
			if ( empty( $ids ) ) {
				echo '[]';
			} else {
				echo $this->format_posts(
					get_posts( array(
						'post__in' => $ids,
						'post_status' => 'any',
						'post_type' => 'any',
					) ),
					'json'
				);
			}
		}
	}

} // if Fieldmanager_Field exxists
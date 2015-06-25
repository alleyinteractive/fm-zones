<?php

if ( class_exists( 'Fieldmanager_Field' ) ) {

	/**
	 * Zoninator Act-Alike Fieldmanager Field
	 */
	class Zoninator_Field extends Fieldmanager_Field {

		public $query_args = array();

		public $autocomplete_attributes = array();

		public $accept_from_other_zones = false;

		public function __construct( $label = '', $options = array() ) {
			$this->template = FMZ_PATH . '/templates/field.php';
			$this->multiple = true;

			parent::__construct( $label, $options );

			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'ajax_request' ) );
			// die( 'wp_ajax_' . $this->get_ajax_action() );
		}

		public function assets() {
			wp_enqueue_style( 'fm-zoninator-jquery-ui', FMZ_URL . '/static/jquery-ui/smoothness/jquery-ui.theme.css', false, FMZ_VERSION, 'all' );
			wp_enqueue_style( 'fm-zoninator-styles', FMZ_URL . '/static/css/fm-zoninator.css', false, FMZ_VERSION, 'all' );
			wp_enqueue_script( 'fm-zoninator-script', FMZ_URL . '/static/js/fm-zoninator.js', array( 'jquery', 'underscore', 'jquery-ui-sortable', 'jquery-ui-autocomplete' ), FMZ_VERSION, true );
		}

		public function form_element( $value = null ) {
			list( $context, $subcontext ) = fm_get_context();
			$this->autocomplete_attributes['data-context'] = $context;
			$this->autocomplete_attributes['data-subcontext'] = $subcontext;
			$this->autocomplete_attributes['data-action'] = $this->get_ajax_action( $this->name );
			$this->autocomplete_attributes['data-nonce'] = wp_create_nonce( 'fm_search_nonce' );

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
				if ( $val === true ){
					$attr_str[] = sanitize_key( $attr );
				} else{
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
					'post_status' => $post->post_status,
					'post_type'   => $post->post_type,
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

		public function get_current_posts_json( $ids ) {
			if ( empty( $ids ) ) {
				return '[]';
			} else {
				return $this->format_posts(
					get_posts( array(
						'post__in' => $ids,
						'post_status' => 'any',
						'post_type' => 'any',
						'orderby' => 'post__in',
						'order' => 'asc',
					) ),
					'json'
				);
			}
		}

		public function get_ajax_action() {
			return $this->get_element_id() . '_search_ajax';
		}

		public function ajax_request() {
			check_ajax_referer( 'fm_search_nonce', '_nonce' );

			if ( empty( $_POST['term'] ) ) {
				wp_send_json_error( __( 'Search term is empty!', 'fm-zoninator' ) );
			}

			$args = array(
				's' => sanitize_text_field( $_POST['term'] ),
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
	}

} // if Fieldmanager_Field exxists
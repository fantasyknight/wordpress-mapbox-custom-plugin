<?php

declare(strict_types=1);

namespace AgentFire\Plugin\Test;

use Twig\Environment;
use Twig\Loader;

use Twig\TwigFunction;

use AgentFire\Plugin\Test\Traits\Singleton;

use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

defined( 'ABSPATH' ) or die();

/**
 * Class Rest
 * @package AgentFire\Plugin\Test
 */
class Rest {
	use Singleton;

	/**
	 * @var string Endpoint namespace
	 */
	const NAMESPACE = 'agentfire/v1/';
	protected Environment $twig;

	/**
	 * @var string Route base
	 */
	const REST_BASE = 'test';

	public function __construct() {
		// set twig template environment
		$this->twig = new Environment(
			new Loader\FilesystemLoader( AGENTFIRE_TEST_PATH . 'template/test' )
		);
		$this->twig->addFunction( new TwigFunction( 'asset', function ($asset) {
			return sprintf( plugins_url( 'mapbox/assets/%s' ), ltrim( $asset, '/' ) );
		} ) );
		
		add_action( 'init', [Rest::class, 'registerRoutes'] );
		add_action( 'init', [Rest::class, 'registerMakerPostType'] );
		add_shortcode( 'agentfire_test', [ $this, 'renderMapBox' ] );
	}

	/**
	 * Register endpoints
	 */
	public static function registerRoutes() {
		register_rest_route(
			self::NAMESPACE , self::REST_BASE . '/markers',
			[ 
				'show_in_index' => false,
				'methods' 		=> [ 'GET', 'POST' ],
				'callback' 		=> [ Rest::class, 'markers' ],
				'args' 			=> [],
			]
		);
	}

	/**
	 * Register marker post type
	 */
	public static function registerMakerPostType() {
		// register marker post type
		register_post_type(
			'marker',
			[ 
				'labels' 				=> [ 
					'name' 			=> 'Markers',
					'singular_name' => 'Marker',
				],
				'public' 				=> true,
				'exclude_from_search' 	=> false,
				'has_archive' 			=> true,
				'supports' 				=> [ 'title', 'editor' ],
				'rewrite' 				=> 'slug'
			]
		);

		// register marker tag taxonomy
		register_taxonomy(
			'marker_tag',
			'marker',
			[ 
				'labels' 	=> [ 
					'name' 			=> 'Tags',
					'singular_name' => 'Tag',
				],
				'rewrite' 	=> 'slug'
			]
		);

		// add initial tags
		$intial_terms = [ 'Shop', 'Real Estate', 'Hospital', 'School', 'Restaurant', 'Office', 'Hotel', 'Motel', 'Bathroom', 'Sport Center' ];
		foreach ( $intial_terms as $term ) {
			wp_insert_term(
				$term,
				'marker_tag'
			);
		}
	}

	/**
	 * Marker api for getting and setting marker data
	 * 
	 */
	public static function markers( WP_REST_Request $request ) {
		global $wpdb;

		// add a new marker
		if ( 'POST' === $request->get_method() ) {
			$tag = $request->get_param( 'new_tag' ) !== '' ? $request->get_param( 'new_tag' ) : $request->get_param( 'tag' );

			// add a new tag
			wp_insert_term(
				$request->get_param( 'new_tag' ),
				'marker_tag'
			);

			// add a new marker
			$post_id = wp_insert_post(
				[ 
					'post_type' 	=> 'marker',
					'post_title' 	=> $request->get_param( 'name' ),
					'post_author' 	=> $request->get_param( 'user_id' ),
					'post_status' 	=> 'publish',
					'meta_input' 	=> [ 
						'lng' 		=> $request->get_param( 'lng' ),
						'lat' 		=> $request->get_param( 'lat' )
					]
				]
			);

			wp_set_post_terms( $post_id, $tag, 'marker_tag' );

			return new WP_REST_Response( [ 'result' => 'success' ] );
		}

		// get makers data
		if ( 'GET' === $request->get_method() ) {
			$tag_filter = [];

			// tag filter query
			if ( $request->get_param( 'tag' ) ) {
				$tag_filter = [ 
					'relation' => 'OR',
					[ 
						'taxonomy' 	=> 'marker_tag',
						'field' 	=> 'slug',
						'terms' 	=> $request->get_param( 'tag' ),
					]
				];
			}

			// filter markers by tag
			$search = $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '';
			$markers = new WP_Query(
				[ 
					'post_type' => 'marker',
					's'			=> $request->get_param( 'search' ),
					'tax_query' => [ 
						$request->get_param( 'tag' ) ? $tag_filter : ''
					]
				]
			);

			// filter markers by name
			$my_markers = [];
			$other_markers = [];
			foreach ( $markers->posts as $marker ) {
				$meta = get_post_meta( $marker->ID );
				$tag = wp_get_post_terms( $marker->ID, 'marker_tag', [ 'fields' => 'names' ] );
				$item = [ 
					'name' 	=> $marker->post_title,
					'slug' 	=> $marker->post_name,
					'tag' 	=> $tag ? $tag : [],
					'lng' 	=> $meta['lng'][0],
					'lat' 	=> $meta['lat'][0]
				];

				if ( $marker->post_author == $request->get_param( 'user_id' ) ) {
					array_push( $my_markers, $item );
				} else {
					array_push( $other_markers, $item );
				}
			}

			// get tags
			$tags = get_terms(
				[ 
					'taxonomy' 		=> 'marker_tag',
					'hide_empty' 	=> false,
					'orderby' 		=> 'count',
					'order' 		=> 'desc'
				]
			);

			return new WP_REST_Response( [ 'my_markers' => $my_markers, 'other_markers' => $other_markers, 'tags' => $tags, 'user' => wp_get_current_user() ] );
		}

		return new WP_REST_Response( [] );
	}

	/**
	 * [agentfire_test] shortcode
	 */
	public function renderMapBox() {
		$map_box_key = '';
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			$map_box_key = get_field( 'test_mapbox_token', 'acf-group_test-settings' );
		}

		return $this->twig->render(
			'main.twig',
			[ 
				'userID' 	=> get_current_user_id(),
				'apiURL' 	=> get_site_url() . '/wp-json/' . self::NAMESPACE . self::REST_BASE . '/markers',
				'mapBoxKey' => $map_box_key
			]
		);
	}

	/**
	 * Add a twig function to get plugins_url
	 */
	public function getFunctions() {
		return [ 
			new TwigFunction( 'plugins_url', [ $this, 'getPluginsUrl' ] ),
		];
	}

	/**
	 * Add a function to plugin url
	 */
	public function getPluginsUrl( string $path ) {
		// Replace 'my-plugin' with the name of your plugin directory.
		return plugins_url( $path, 'my-plugin' );
	}
}
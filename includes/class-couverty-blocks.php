<?php
defined( 'ABSPATH' ) || exit;

/**
 * Blocks handler for Couverty
 */
class Couverty_Blocks {

	/**
	 * Constructor - register filters and hooks
	 */
	public function __construct() {
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ], 10, 2 );
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register custom block category
	 *
	 * @param array  $categories Block categories.
	 * @param object $post_type_object Post type object.
	 * @return array
	 */
	public function register_block_category( $categories, $post_type_object ) {
		// Check if 'couverty' category already exists
		foreach ( $categories as $category ) {
			if ( 'couverty' === $category['slug'] ) {
				return $categories;
			}
		}

		// Add Couverty category
		return array_merge(
			[
				[
					'slug'  => 'couverty',
					'title' => esc_html__( 'Couverty', 'couverty' ),
					'icon'  => 'fork-knife',
				],
			],
			$categories
		);
	}

	/**
	 * Register all blocks
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Register Menu block
		register_block_type(
			COUVERTY_PLUGIN_DIR . 'blocks/menu',
			[
				'render_callback' => [ $this, 'render_menu_block' ],
			]
		);

		// Register Boissons block
		register_block_type(
			COUVERTY_PLUGIN_DIR . 'blocks/boissons',
			[
				'render_callback' => [ $this, 'render_boissons_block' ],
			]
		);

		// Register Menu du jour block
		register_block_type(
			COUVERTY_PLUGIN_DIR . 'blocks/menu-du-jour',
			[
				'render_callback' => [ $this, 'render_menu_du_jour_block' ],
			]
		);

		// Register Reservation block
		register_block_type(
			COUVERTY_PLUGIN_DIR . 'blocks/reservation',
			[
				'render_callback' => [ $this, 'render_reservation_block' ],
			]
		);
	}

	/**
	 * Render menu block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_menu_block( $attributes ) {
		$shortcodes = new Couverty_Shortcodes();
		$valid_layouts = [ 'list', 'grid' ];
		$layout = isset( $attributes['layout'] ) && in_array( $attributes['layout'], $valid_layouts, true )
			? $attributes['layout'] : 'list';

		$atts = [
			'layout'         => $layout,
			'show_prices'    => ( $attributes['showPrices'] ?? true ) ? 'true' : 'false',
			'show_images'    => ( $attributes['showImages'] ?? true ) ? 'true' : 'false',
			'show_allergens' => ( $attributes['showAllergens'] ?? true ) ? 'true' : 'false',
		];
		return wp_kses_post( $shortcodes->render_menu( $atts ) );
	}

	/**
	 * Render boissons block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_boissons_block( $attributes ) {
		$shortcodes = new Couverty_Shortcodes();
		$valid_layouts = [ 'list', 'grid' ];
		$layout = isset( $attributes['layout'] ) && in_array( $attributes['layout'], $valid_layouts, true )
			? $attributes['layout'] : 'list';

		$atts = [
			'layout'        => $layout,
			'show_prices'   => ( $attributes['showPrices'] ?? true ) ? 'true' : 'false',
			'show_details'  => ( $attributes['showDetails'] ?? true ) ? 'true' : 'false',
		];
		return wp_kses_post( $shortcodes->render_boissons( $atts ) );
	}

	/**
	 * Render menu du jour block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_menu_du_jour_block( $attributes ) {
		$shortcodes = new Couverty_Shortcodes();
		$atts = [
			'show_price' => ( $attributes['showPrice'] ?? true ) ? 'true' : 'false',
		];
		return wp_kses_post( $shortcodes->render_menu_du_jour( $atts ) );
	}

	/**
	 * Render reservation block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_reservation_block( $attributes ) {
		$shortcodes    = new Couverty_Shortcodes();
		$valid_appearances = [ 'card', 'glass', 'minimal', 'dark' ];
		$valid_radii       = [ 'none', 'sm', 'md', 'lg' ];

		$appearance = isset( $attributes['appearance'] ) && in_array( $attributes['appearance'], $valid_appearances, true )
			? $attributes['appearance'] : 'card';
		$radius = isset( $attributes['radius'] ) && in_array( $attributes['radius'], $valid_radii, true )
			? $attributes['radius'] : 'lg';

		$atts = [
			'height'     => max( 300, min( 1200, (int) ( $attributes['height'] ?? 600 ) ) ),
			'appearance' => $appearance,
			'radius'     => $radius,
		];
		// Note: no wp_kses_post() here — reservation template contains
		// a <script> tag for iframe creation that would be stripped.
		// Input values (appearance, radius, height) are validated above.
		return $shortcodes->render_reservation( $atts );
	}
}

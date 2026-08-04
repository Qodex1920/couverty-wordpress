<?php
defined( 'ABSPATH' ) || exit;

/**
 * Blocks handler for Couverty
 *
 * Blocks are server-rendered through the shortcode handler, so the markup stays
 * identical whether the user picked a block, a shortcode or a page builder.
 *
 * The rendered markup is not run through wp_kses_post(): the templates escape
 * every value they output, and kses would strip the reservation widget's script
 * tag. Attribute values coming from the editor are whitelisted here instead.
 */
class Couverty_Blocks {

	/**
	 * Shared shortcode handler used to render every block.
	 *
	 * @var Couverty_Shortcodes
	 */
	private $shortcodes;

	/**
	 * Constructor - register filters and hooks
	 *
	 * @param Couverty_Shortcodes $shortcodes Shared shortcode handler.
	 */
	public function __construct( Couverty_Shortcodes $shortcodes ) {
		$this->shortcodes = $shortcodes;

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
		$blocks = [
			'menu'         => [ $this, 'render_menu_block' ],
			'boissons'     => [ $this, 'render_boissons_block' ],
			'menu-du-jour' => [ $this, 'render_menu_du_jour_block' ],
			'reservation'  => [ $this, 'render_reservation_block' ],
		];

		foreach ( $blocks as $dir => $callback ) {
			$block_type = register_block_type(
				COUVERTY_PLUGIN_DIR . 'blocks/' . $dir,
				[ 'render_callback' => $callback ]
			);

			if ( ! $block_type instanceof WP_Block_Type || empty( $block_type->editor_script_handles ) ) {
				continue;
			}

			foreach ( $block_type->editor_script_handles as $handle ) {
				wp_set_script_translations( $handle, 'couverty', COUVERTY_PLUGIN_DIR . 'languages' );
			}
		}
	}

	/**
	 * Pick a value from a whitelist, falling back to a default.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $key        Attribute name.
	 * @param array  $allowed    Allowed values.
	 * @param string $default    Fallback value.
	 * @return string
	 */
	private function pick( $attributes, $key, $allowed, $default ) {
		return isset( $attributes[ $key ] ) && in_array( $attributes[ $key ], $allowed, true )
			? $attributes[ $key ]
			: $default;
	}

	/**
	 * Normalize a boolean attribute into the string form shortcodes expect.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $key        Attribute name.
	 * @return string 'true' or 'false'
	 */
	private function flag( $attributes, $key ) {
		return ( $attributes[ $key ] ?? true ) ? 'true' : 'false';
	}

	/**
	 * Render menu block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_menu_block( $attributes ) {
		return $this->shortcodes->render_menu( [
			'layout'         => $this->pick( $attributes, 'layout', [ 'list', 'grid' ], 'list' ),
			'show_prices'    => $this->flag( $attributes, 'showPrices' ),
			'show_images'    => $this->flag( $attributes, 'showImages' ),
			'show_allergens' => $this->flag( $attributes, 'showAllergens' ),
		] );
	}

	/**
	 * Render boissons block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_boissons_block( $attributes ) {
		return $this->shortcodes->render_boissons( [
			'layout'       => $this->pick( $attributes, 'layout', [ 'list', 'grid' ], 'list' ),
			'show_prices'  => $this->flag( $attributes, 'showPrices' ),
			'show_details' => $this->flag( $attributes, 'showDetails' ),
		] );
	}

	/**
	 * Render menu du jour block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_menu_du_jour_block( $attributes ) {
		return $this->shortcodes->render_menu_du_jour( [
			'show_price' => $this->flag( $attributes, 'showPrice' ),
		] );
	}

	/**
	 * Render reservation block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_reservation_block( $attributes ) {
		return $this->shortcodes->render_reservation( [
			'height'     => max( 300, min( 1200, (int) ( $attributes['height'] ?? 600 ) ) ),
			'appearance' => $this->pick( $attributes, 'appearance', [ 'card', 'glass', 'minimal', 'dark' ], 'card' ),
			'radius'     => $this->pick( $attributes, 'radius', [ 'none', 'sm', 'md', 'lg' ], 'lg' ),
		] );
	}
}

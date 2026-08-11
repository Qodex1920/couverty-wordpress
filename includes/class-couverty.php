<?php
/**
 * Main Couverty plugin class
 */

defined( 'ABSPATH' ) || exit;

class Couverty {
	/**
	 * Single instance of the class
	 *
	 * @var Couverty
	 */
	private static $instance = null;

	/**
	 * API client instance
	 *
	 * @var Couverty_API
	 */
	private $api = null;

	/**
	 * Get singleton instance
	 *
	 * @return Couverty
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		// Translations must be loaded on `init`, not earlier: since WordPress 6.7
		// loading them on `plugins_loaded` triggers a _load_textdomain_just_in_time notice.
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_styles' ) );
		add_action( 'wp_head', array( $this, 'output_reservation_schema' ) );

		// Blocks delegate their rendering to the shortcode handler, so they share
		// the same instance rather than re-registering shortcodes on every render.
		$shortcodes = new Couverty_Shortcodes();

		new Couverty_Blocks( $shortcodes );
		new Couverty_REST();
		new Couverty_Sync();
		new Couverty_Pages();
	}

	/**
	 * Declare the online booking in JSON-LD.
	 *
	 * The widget lives in a `noindex` iframe and the floating button is built in
	 * JS, so nothing in the served HTML tells a crawler that the site takes
	 * bookings, nor that Couverty powers them. `@id` points at the site's own
	 * entity so the markup merges with the theme's schema instead of competing
	 * with it. Disable with `add_filter( 'couverty_output_schema', '__return_false' )`.
	 */
	public function output_reservation_schema() {
		$settings = self::get_settings();

		if ( empty( $settings['slug'] ) || empty( $settings['base_url'] ) ) {
			return;
		}

		if ( ! apply_filters( 'couverty_output_schema', true ) ) {
			return;
		}

		$home        = untrailingslashit( home_url() );
		$reserve_url = untrailingslashit( $settings['base_url'] ) . '/' . rawurlencode( $settings['slug'] ) . '/reserver';

		$schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Restaurant',
			'@id'                 => $home . '/#restaurant',
			'name'                => get_bloginfo( 'name' ),
			'url'                 => $home,
			'acceptsReservations' => $reserve_url,
			'potentialAction'     => array(
				'@type'    => 'ReserveAction',
				'target'   => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => $reserve_url,
				),
				'provider' => array(
					'@type' => 'Organization',
					'name'  => 'Couverty',
					'url'   => 'https://couverty.ch',
				),
			),
		);

		// wp_json_encode() escapes slashes, so a `</script>` in the site name
		// cannot break out of the tag.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
	}

	/**
	 * Load the plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'couverty',
			false,
			dirname( plugin_basename( COUVERTY_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Enqueue public styles and scripts.
	 */
	public function enqueue_public_assets() {
		$this->enqueue_public_styles();
		$this->register_on_demand_scripts();
		$this->enqueue_floating_widget();
		$this->enqueue_lightbox_fix();
	}

	/**
	 * Register (but do not enqueue) the scripts templates pull in on demand.
	 *
	 * A page with no dish images and no booking widget therefore ships no JS.
	 */
	private function register_on_demand_scripts() {
		wp_register_script(
			'couverty-lightbox',
			COUVERTY_PLUGIN_URL . 'assets/js/couverty-lightbox.js',
			array(),
			COUVERTY_VERSION,
			array( 'in_footer' => true )
		);

		wp_localize_script( 'couverty-lightbox', 'couvertyLightbox', array(
			'closeLabel' => __( 'Fermer', 'couverty' ),
		) );

		wp_register_script(
			'couverty-reservation',
			COUVERTY_PLUGIN_URL . 'assets/js/couverty-reservation.js',
			array(),
			COUVERTY_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Enqueue public styles.
	 *
	 * Loaded on every page by default: page builders such as Bricks and Elementor
	 * store their layout outside `post_content`, so Couverty content cannot be
	 * detected reliably. Sites that only use shortcodes or blocks can narrow this
	 * down with the `couverty_enqueue_public_styles` filter.
	 */
	private function enqueue_public_styles() {
		/**
		 * Filter whether the public stylesheet should be loaded on the current request.
		 *
		 * @param bool $load Defaults to true.
		 */
		if ( ! apply_filters( 'couverty_enqueue_public_styles', true ) ) {
			return;
		}

		$this->enqueue_stylesheet();
	}

	/**
	 * Load the public stylesheet inside the block editor.
	 *
	 * The blocks preview their real markup through ServerSideRender, so without
	 * this the owner edits an unstyled stack of text and cannot judge the page
	 * before publishing it. The `couverty_enqueue_public_styles` filter is not
	 * applied here on purpose: a site that narrows the stylesheet down to a few
	 * front-end templates still needs a faithful preview while editing.
	 */
	public function enqueue_editor_styles() {
		// `enqueue_block_assets` also fires on the front end, where
		// enqueue_public_styles() already ran with the filter applied.
		if ( ! is_admin() ) {
			return;
		}

		$this->enqueue_stylesheet();
	}

	/**
	 * Enqueue the public stylesheet. Shared by the front end and the editor.
	 */
	private function enqueue_stylesheet() {
		wp_enqueue_style(
			'couverty-public',
			COUVERTY_PLUGIN_URL . 'assets/css/couverty-public.css',
			array(),
			COUVERTY_VERSION
		);
	}

	/**
	 * Enqueue the floating booking button script.
	 */
	private function enqueue_floating_widget() {
		$settings = self::get_settings();

		if ( empty( $settings['floating_enabled'] ) ) {
			return;
		}

		if ( empty( $settings['slug'] ) || empty( $settings['base_url'] ) ) {
			return;
		}

		$args = array( 'slug' => $settings['slug'] );

		if ( ! empty( $settings['floating_text'] ) ) {
			$args['text'] = $settings['floating_text'];
		}

		// add_query_arg() URL-encodes the values itself.
		$src = add_query_arg( $args, rtrim( $settings['base_url'], '/' ) . '/widget-floating.js' );

		wp_enqueue_script(
			'couverty-floating',
			$src,
			array(),
			null, // Version is carried by the remote file itself.
			array(
				'strategy'  => 'async',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Enqueue the PhotoSwipe dimension fix.
	 *
	 * Bricks sets empty data-pswp-width/height for external image URLs, which makes
	 * PhotoSwipe fall back to viewport dimensions. The script is a no-op on pages
	 * without PhotoSwipe links, and can be disabled entirely with the
	 * `couverty_enable_lightbox_fix` filter.
	 */
	private function enqueue_lightbox_fix() {
		/**
		 * Filter whether the PhotoSwipe dimension fix should be loaded.
		 *
		 * @param bool $enable Defaults to true.
		 */
		if ( ! apply_filters( 'couverty_enable_lightbox_fix', true ) ) {
			return;
		}

		wp_enqueue_script(
			'couverty-lightbox-fix',
			COUVERTY_PLUGIN_URL . 'assets/js/couverty-lightbox-fix.js',
			array(),
			COUVERTY_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Get API client instance
	 *
	 * @return Couverty_API
	 */
	public function get_api() {
		if ( is_null( $this->api ) ) {
			$this->api = new Couverty_API( self::get_settings() );
		}
		return $this->api;
	}

	/**
	 * Drop the memoized API client.
	 *
	 * Call this after saving settings so the next get_api() picks up the new
	 * key instead of the one captured earlier in the request.
	 */
	public function reset_api() {
		$this->api = null;
	}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'api_key'          => '',
			'base_url'         => 'https://couverty.ch',
			'slug'             => '',
			'cache_duration'   => 600,
			'floating_enabled' => false,
			'floating_text'    => 'Réserver',
		);

		$settings = get_option( 'couverty_settings', array() );
		$settings = wp_parse_args( $settings, $defaults );

		// Allow override via wp-config.php constant
		if ( defined( 'COUVERTY_BASE_URL' ) ) {
			$settings['base_url'] = COUVERTY_BASE_URL;
		}

		return $settings;
	}
}

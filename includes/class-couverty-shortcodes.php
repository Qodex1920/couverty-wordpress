<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes handler for Couverty
 */
class Couverty_Shortcodes {

	/**
	 * Constructor - register shortcodes
	 */
	public function __construct() {
		add_shortcode( 'couverty_menu', [ $this, 'render_menu' ] );
		add_shortcode( 'couverty_boissons', [ $this, 'render_boissons' ] );
		add_shortcode( 'couverty_menu_du_jour', [ $this, 'render_menu_du_jour' ] );
		add_shortcode( 'couverty_reservation', [ $this, 'render_reservation' ] );
	}

	/**
	 * Render menu shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML
	 */
	public function render_menu( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'layout' => 'list',
				'show_prices' => true,
				'show_images' => true,
				'show_allergens' => true,
			],
			$atts,
			'couverty_menu'
		);

		// Check if API is configured
		if ( ! $this->is_api_configured() ) {
			return $this->get_config_error_message();
		}

		// Get API instance
		$api = Couverty::get_instance()->get_api();
		if ( ! $api ) {
			return $this->get_api_error_message();
		}

		// Fetch menu data
		$response = $api->get_menu( 'plats' );
		if ( ! $response || ! isset( $response['plats']['categories'] ) ) {
			return $this->get_no_data_error_message( 'Menu' );
		}

		// Render template
		$data = [
			'categories' => $response['plats']['categories'],
		];

		return $this->render_template( 'menu', $data, $atts );
	}

	/**
	 * Render boissons shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML
	 */
	public function render_boissons( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'layout' => 'list',
				'show_prices' => true,
				'show_details' => true,
			],
			$atts,
			'couverty_boissons'
		);

		// Check if API is configured
		if ( ! $this->is_api_configured() ) {
			return $this->get_config_error_message();
		}

		// Get API instance
		$api = Couverty::get_instance()->get_api();
		if ( ! $api ) {
			return $this->get_api_error_message();
		}

		// Fetch boissons data
		$response = $api->get_menu( 'boissons' );
		if ( ! $response || ! isset( $response['boissons']['categories'] ) ) {
			return $this->get_no_data_error_message( 'Boissons' );
		}

		// Render template
		$data = [
			'categories' => $response['boissons']['categories'],
		];

		return $this->render_template( 'boissons', $data, $atts );
	}

	/**
	 * Render menu du jour shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML
	 */
	public function render_menu_du_jour( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'show_price' => 'true',
			],
			$atts,
			'couverty_menu_du_jour'
		);

		// Check if API is configured
		if ( ! $this->is_api_configured() ) {
			return $this->get_config_error_message();
		}

		// Get API instance
		$api = Couverty::get_instance()->get_api();
		if ( ! $api ) {
			return $this->get_api_error_message();
		}

		// Fetch menu data (all to get menuSemaine)
		$response = $api->get_menu( 'all' );
		if ( ! $response || ! isset( $response['menuSemaine'] ) ) {
			return $this->get_no_data_error_message( 'Menu du jour' );
		}

		$menu_semaine = $response['menuSemaine'];
		if ( ! isset( $menu_semaine['menus'] ) || empty( $menu_semaine['menus'] ) ) {
			return $this->get_no_data_error_message( 'Menu du jour' );
		}

		// Render template
		$data = $menu_semaine;

		return $this->render_template( 'menu-du-jour', $data, $atts );
	}

	/**
	 * Render reservation shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML
	 */
	public function render_reservation( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'height' => 600,
				'appearance' => 'card',
				'radius' => 'lg',
			],
			$atts,
			'couverty_reservation'
		);

		// Check if API is configured
		if ( ! $this->is_api_configured() ) {
			return $this->get_config_error_message();
		}

		// Render template (no API call needed for reservation)
		return $this->render_template( 'reservation', [], $atts );
	}

	/**
	 * Check if API is configured
	 *
	 * @return bool
	 */
	private function is_api_configured() {
		$settings = Couverty::get_settings();
		return isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) &&
			isset( $settings['slug'] ) && ! empty( $settings['slug'] );
	}

	/**
	 * Get configuration error message
	 *
	 * @return string HTML
	 */
	private function get_config_error_message() {
		$message = esc_html__( 'Couverty plugin is not configured. Please add your API key and slug in the plugin settings.', 'couverty' );

		if ( is_admin() ) {
			$message = esc_html__( 'Couverty plugin is not configured. Please add your API key and slug in the plugin settings.', 'couverty' );
		}

		return '<div class="couverty-error">' . $message . '</div>';
	}

	/**
	 * Get API error message
	 *
	 * @return string HTML
	 */
	private function get_api_error_message() {
		$message = esc_html__( 'Unable to initialize Couverty API. Please check your settings.', 'couverty' );
		return '<div class="couverty-error">' . $message . '</div>';
	}

	/**
	 * Get no data error message
	 *
	 * @param string $type Data type (e.g. "Menu", "Boissons")
	 * @return string HTML
	 */
	private function get_no_data_error_message( $type = 'Data' ) {
		$message = sprintf(
			/* translators: %s: Data type */
			esc_html__( 'No %s data available. Please check your Couverty settings.', 'couverty' ),
			esc_html( $type )
		);
		return '<div class="couverty-error">' . $message . '</div>';
	}

	/**
	 * Render template with output buffering
	 *
	 * @param string $template Template file name (without .php)
	 * @param array  $data Template data
	 * @param array  $atts Shortcode attributes
	 * @return string HTML output
	 */
	private function render_template( $template, $data = [], $atts = [] ) {
		$template_file = COUVERTY_PLUGIN_DIR . 'templates/' . $template . '.php';

		if ( ! file_exists( $template_file ) ) {
			return '<div class="couverty-error">' . esc_html__( 'Template not found.', 'couverty' ) . '</div>';
		}

		ob_start();
		include $template_file;
		return ob_get_clean();
	}
}

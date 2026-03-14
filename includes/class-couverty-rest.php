<?php
/**
 * Couverty REST API endpoints
 *
 * Exposes Couverty data via WordPress REST API for use in page builders
 * (Bricks, Elementor, etc.) and custom themes.
 *
 * Endpoints:
 *   GET /wp-json/couverty/v1/menu        → Plats par catégorie
 *   GET /wp-json/couverty/v1/boissons    → Boissons par catégorie
 *   GET /wp-json/couverty/v1/menu-du-jour → Menu du jour / de la semaine
 *   GET /wp-json/couverty/v1/restaurant  → Informations du restaurant
 */

defined( 'ABSPATH' ) || exit;

class Couverty_REST {

	/**
	 * REST namespace
	 */
	const NAMESPACE = 'couverty/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/menu', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_menu' ),
			'permission_callback' => array( $this, 'check_configured' ),
		) );

		register_rest_route( self::NAMESPACE, '/boissons', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_boissons' ),
			'permission_callback' => array( $this, 'check_configured' ),
		) );

		register_rest_route( self::NAMESPACE, '/menu-du-jour', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_menu_du_jour' ),
			'permission_callback' => array( $this, 'check_configured' ),
		) );

		register_rest_route( self::NAMESPACE, '/restaurant', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_restaurant' ),
			'permission_callback' => array( $this, 'check_configured' ),
		) );
	}

	/**
	 * Check if plugin is configured before allowing REST access
	 *
	 * @return bool|WP_Error
	 */
	public function check_configured() {
		$settings = Couverty::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new WP_Error(
				'couverty_not_configured',
				__( 'Couverty plugin is not configured.', 'couverty' ),
				array( 'status' => 503 )
			);
		}

		return true;
	}

	/**
	 * GET /wp-json/couverty/v1/menu
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_menu() {
		$data = couverty_get_menu();

		if ( is_null( $data ) ) {
			return $this->api_error();
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /wp-json/couverty/v1/boissons
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_boissons() {
		$data = couverty_get_boissons();

		if ( is_null( $data ) ) {
			return $this->api_error();
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /wp-json/couverty/v1/menu-du-jour
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_menu_du_jour() {
		$data = couverty_get_menu_du_jour();

		if ( is_null( $data ) ) {
			return $this->api_error();
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /wp-json/couverty/v1/restaurant
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_restaurant() {
		$data = couverty_get_restaurant();

		if ( is_null( $data ) ) {
			return $this->api_error();
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Return a standard API error
	 *
	 * @return WP_Error
	 */
	private function api_error() {
		$settings = Couverty::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return new WP_Error(
				'couverty_not_configured',
				__( 'Couverty plugin is not configured. Add your API key in Settings > Couverty.', 'couverty' ),
				array( 'status' => 503 )
			);
		}

		return new WP_Error(
			'couverty_api_error',
			__( 'Unable to fetch data from Couverty API.', 'couverty' ),
			array( 'status' => 502 )
		);
	}
}

<?php
/**
 * Couverty API client
 */

defined( 'ABSPATH' ) || exit;

class Couverty_API {
	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Base URL
	 *
	 * @var string
	 */
	private $base_url = 'https://couverty.ch';

	/**
	 * Cache duration in seconds
	 *
	 * @var int
	 */
	private $cache_duration = 600;

	/**
	 * Constructor
	 *
	 * @param array $settings Settings array
	 */
	public function __construct( $settings = array() ) {
		$this->api_key        = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$this->base_url       = isset( $settings['base_url'] ) ? $settings['base_url'] : 'https://couverty.ch';
		$this->cache_duration = isset( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 600;
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $params   Query parameters
	 *
	 * @return array|null
	 */
	private function request( $endpoint, $params = array() ) {
		$url = rtrim( $this->base_url, '/' ) . $endpoint;

		// Add query parameters
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$args = array(
			'timeout' => 15,
			'headers' => array(
				'X-API-Key'   => $this->api_key,
				'Accept'      => 'application/json',
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Unwrap API response envelope: { success: true, data: {...} }
		if ( is_array( $data ) && isset( $data['data'] ) ) {
			return $data['data'];
		}

		return $data;
	}

	/**
	 * Get menu
	 *
	 * @param string $type Menu type (all, boissons, plats, etc.)
	 *
	 * @return array|null
	 */
	public function get_menu( $type = 'all' ) {
		$cache_key = "couverty_menu_{$type}";
		return $this->get_cached( $cache_key, function() use ( $type ) {
			return $this->request( '/api/public/v1/menu', array( 'type' => $type ) );
		}, $this->cache_duration );
	}

	/**
	 * Get restaurant info
	 *
	 * @return array|null
	 */
	public function get_restaurant_info() {
		$cache_key = 'couverty_restaurant';
		return $this->get_cached( $cache_key, function() {
			return $this->request( '/api/public/v1/restaurant' );
		}, $this->cache_duration );
	}

	/**
	 * Test connection to API
	 *
	 * @return array
	 */
	public function test_connection() {
		// Don't use cache for connection test
		$data = $this->request( '/api/public/v1/restaurant' );

		if ( is_null( $data ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to connect to Couverty API', 'couverty' ),
			);
		}

		if ( isset( $data['error'] ) ) {
			return array(
				'success' => false,
				'error'   => $data['error'],
			);
		}

		return array(
			'success' => true,
			'data'    => $data,
		);
	}

	/**
	 * Clear all plugin caches
	 */
	public function clear_cache() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'%\_transient\_couverty\_%',
				'%\_transient\_timeout\_couverty\_%'
			)
		);
	}

	/**
	 * Get cached value or call callback
	 *
	 * @param string   $key      Cache key
	 * @param callable $callback Callback to get value
	 * @param int      $ttl      Time to live in seconds
	 *
	 * @return mixed
	 */
	private function get_cached( $key, $callback, $ttl = 600 ) {
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = call_user_func( $callback );

		if ( ! is_null( $value ) ) {
			set_transient( $key, $value, $ttl );
		}

		return $value;
	}
}

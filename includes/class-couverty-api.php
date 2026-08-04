<?php
/**
 * Couverty API client
 */

defined( 'ABSPATH' ) || exit;

class Couverty_API {
	/**
	 * How long a failed request is remembered before we retry the API.
	 *
	 * Without this, a Couverty outage means every page view fires a fresh
	 * 15-second blocking HTTP request.
	 */
	const FAILURE_TTL = 60;

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
	 * Last error encountered, for admin diagnostics.
	 *
	 * @var WP_Error|null
	 */
	private $last_error = null;

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
	 * @return array|WP_Error Decoded payload, or WP_Error describing what went wrong.
	 */
	private function request( $endpoint, $params = array() ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error(
				'couverty_no_api_key',
				__( 'Aucune clé API renseignée.', 'couverty' )
			);
		}

		$url = rtrim( $this->base_url, '/' ) . $endpoint;

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get( $url, array(
			'timeout'    => 15,
			'user-agent' => 'Couverty-WP/' . COUVERTY_VERSION . '; ' . home_url( '/' ),
			'headers'    => array(
				'X-API-Key' => $this->api_key,
				'Accept'    => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'couverty_transport_error',
				sprintf(
					/* translators: %s: underlying transport error message */
					__( 'Impossible de joindre Couverty : %s', 'couverty' ),
					$response->get_error_message()
				)
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status ) {
			return $this->http_error( $status, $data );
		}

		// Unwrap API response envelope: { success: true, data: {...} }.
		if ( is_array( $data ) && isset( $data['data'] ) ) {
			return $data['data'];
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'couverty_invalid_response',
				__( 'Réponse inattendue de l\'API Couverty.', 'couverty' )
			);
		}

		return $data;
	}

	/**
	 * Turn an HTTP status into an actionable error message.
	 *
	 * @param int        $status HTTP status code.
	 * @param array|null $data   Decoded response body, if any.
	 *
	 * @return WP_Error
	 */
	private function http_error( $status, $data ) {
		$remote_message = is_array( $data ) && ! empty( $data['error'] ) ? (string) $data['error'] : '';

		switch ( $status ) {
			case 401:
				$code    = 'couverty_unauthorized';
				$message = __( 'Clé API invalide, expirée ou révoquée. Générez-en une nouvelle depuis votre tableau de bord Couverty.', 'couverty' );
				break;

			case 403:
				$code    = 'couverty_forbidden';
				$message = __( 'Cette clé API n\'a pas les permissions nécessaires (menu:read et restaurant:read).', 'couverty' );
				break;

			case 429:
				$code    = 'couverty_rate_limited';
				$message = __( 'Quota de requêtes dépassé. Augmentez la durée du cache ou attendez la prochaine heure.', 'couverty' );
				break;

			case 404:
				$code    = 'couverty_not_found';
				$message = __( 'Ressource introuvable côté Couverty. Vérifiez que votre établissement est bien actif.', 'couverty' );
				break;

			default:
				$code    = 'couverty_server_error';
				$message = sprintf(
					/* translators: %d: HTTP status code */
					__( 'L\'API Couverty a répondu avec une erreur (HTTP %d).', 'couverty' ),
					$status
				);
		}

		if ( $remote_message ) {
			$message .= ' — ' . $remote_message;
		}

		return new WP_Error( $code, $message, array( 'status' => $status ) );
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
		} );
	}

	/**
	 * Get restaurant info
	 *
	 * @return array|null
	 */
	public function get_restaurant_info() {
		return $this->get_cached( 'couverty_restaurant', function() {
			return $this->request( '/api/public/v1/restaurant' );
		} );
	}

	/**
	 * Get upcoming events for a tenant
	 *
	 * @param string $slug Tenant slug.
	 * @return array|null Array of events or null on error. Response shape: { events: [...] }.
	 */
	public function get_events( $slug ) {
		if ( empty( $slug ) ) {
			return null;
		}
		$cache_key = 'couverty_events_' . md5( $slug );
		return $this->get_cached( $cache_key, function() use ( $slug ) {
			return $this->request( '/api/public/' . rawurlencode( $slug ) . '/events' );
		} );
	}

	/**
	 * Build the public URL for an event detail page.
	 *
	 * @param string $slug     Tenant slug.
	 * @param string $event_id Event ID.
	 * @return string
	 */
	public function build_event_url( $slug, $event_id ) {
		return rtrim( $this->base_url, '/' ) . '/' . rawurlencode( $slug ) . '/evenements/' . rawurlencode( $event_id );
	}

	/**
	 * The last error encountered by this client, if any.
	 *
	 * @return WP_Error|null
	 */
	public function get_last_error() {
		return $this->last_error;
	}

	/**
	 * Human-readable message for the last error, or an empty string.
	 *
	 * @return string
	 */
	public function get_last_error_message() {
		return is_wp_error( $this->last_error ) ? $this->last_error->get_error_message() : '';
	}

	/**
	 * Test connection to API
	 *
	 * @return array { success: bool, error?: string, code?: string, data?: array }
	 */
	public function test_connection() {
		// Bypass both the positive and the negative cache for an explicit test.
		$this->forget_failure( 'couverty_restaurant' );
		$data = $this->request( '/api/public/v1/restaurant' );

		if ( is_wp_error( $data ) ) {
			$this->last_error = $data;
			return array(
				'success' => false,
				'code'    => $data->get_error_code(),
				'error'   => $data->get_error_message(),
			);
		}

		$this->last_error = null;

		return array(
			'success' => true,
			'data'    => $data,
		);
	}

	/**
	 * Every cache key this plugin writes, so we can clear them without
	 * touching the options table directly (works with Redis/Memcached).
	 *
	 * @return string[]
	 */
	private function cache_keys() {
		$keys = array(
			'couverty_menu_all',
			'couverty_menu_plats',
			'couverty_menu_boissons',
			'couverty_restaurant',
		);

		$settings = Couverty::get_settings();
		if ( ! empty( $settings['slug'] ) ) {
			$keys[] = 'couverty_events_' . md5( $settings['slug'] );
		}

		return $keys;
	}

	/**
	 * Clear all plugin caches
	 */
	public function clear_cache() {
		foreach ( $this->cache_keys() as $key ) {
			delete_transient( $key );
			$this->forget_failure( $key );
		}

		// Sweep leftovers from older versions or removed slugs. Skipped when an
		// external object cache is active, since transients aren't in the DB then.
		if ( ! wp_using_ext_object_cache() ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_couverty_' ) . '%',
					$wpdb->esc_like( '_transient_timeout_couverty_' ) . '%'
				)
			);
		}
	}

	/**
	 * Cache key holding the "this call recently failed" marker.
	 *
	 * @param string $key Base cache key.
	 * @return string
	 */
	private function failure_key( $key ) {
		return $key . '_err';
	}

	/**
	 * Drop the failure marker for a key.
	 *
	 * @param string $key Base cache key.
	 */
	private function forget_failure( $key ) {
		delete_transient( $this->failure_key( $key ) );
	}

	/**
	 * Get cached value or call callback
	 *
	 * @param string   $key      Cache key
	 * @param callable $callback Callback returning array|WP_Error
	 *
	 * @return array|null
	 */
	private function get_cached( $key, $callback ) {
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Back off instead of replaying a 15s timeout on every page view.
		$recent_failure = get_transient( $this->failure_key( $key ) );
		if ( false !== $recent_failure ) {
			$this->last_error = new WP_Error( $recent_failure['code'], $recent_failure['message'] );
			return null;
		}

		$value = call_user_func( $callback );

		if ( is_wp_error( $value ) ) {
			$this->last_error = $value;
			set_transient(
				$this->failure_key( $key ),
				array(
					'code'    => $value->get_error_code(),
					'message' => $value->get_error_message(),
				),
				self::FAILURE_TTL
			);
			return null;
		}

		$this->last_error = null;
		set_transient( $key, $value, $this->cache_duration );

		return $value;
	}
}

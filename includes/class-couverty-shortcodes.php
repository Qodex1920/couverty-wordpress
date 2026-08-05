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
				'layout'         => 'list',
				'show_prices'    => true,
				'show_images'    => true,
				'show_allergens' => true,
			],
			$atts,
			'couverty_menu'
		);

		if ( ! $this->is_api_configured() ) {
			return $this->config_error();
		}

		$api      = Couverty::get_instance()->get_api();
		$response = $api->get_menu( 'plats' );

		if ( ! $response || ! isset( $response['plats']['categories'] ) ) {
			return $this->data_error( $api, __( 'la carte des plats', 'couverty' ) );
		}

		return $this->render_template( 'menu', [ 'categories' => $response['plats']['categories'] ], $atts );
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
				'layout'       => 'list',
				'show_prices'  => true,
				'show_details' => true,
			],
			$atts,
			'couverty_boissons'
		);

		if ( ! $this->is_api_configured() ) {
			return $this->config_error();
		}

		$api      = Couverty::get_instance()->get_api();
		$response = $api->get_menu( 'boissons' );

		if ( ! $response || ! isset( $response['boissons']['categories'] ) ) {
			return $this->data_error( $api, __( 'la carte des boissons', 'couverty' ) );
		}

		return $this->render_template( 'boissons', [ 'categories' => $response['boissons']['categories'] ], $atts );
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

		if ( ! $this->is_api_configured() ) {
			return $this->config_error();
		}

		$api      = Couverty::get_instance()->get_api();
		$response = $api->get_menu( 'all' );

		if ( ! $response || empty( $response['menuSemaine']['menus'] ) ) {
			return $this->data_error( $api, __( 'le menu du jour', 'couverty' ) );
		}

		return $this->render_template( 'menu-du-jour', $response['menuSemaine'], $atts );
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
				'height'     => 600,
				'appearance' => 'card',
				'radius'     => 'lg',
			],
			$atts,
			'couverty_reservation'
		);

		if ( ! $this->is_api_configured() ) {
			return $this->config_error();
		}

		// No API call needed: the widget is an iframe.
		return $this->render_template( 'reservation', [], $atts );
	}

	/**
	 * Check if API is configured
	 *
	 * @return bool
	 */
	private function is_api_configured() {
		$settings = Couverty::get_settings();
		return ! empty( $settings['api_key'] ) && ! empty( $settings['slug'] );
	}

	/**
	 * Whether the current user can be shown technical details.
	 *
	 * @return bool
	 */
	private function can_see_details() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Message shown when the plugin has not been connected yet.
	 *
	 * @return string HTML
	 */
	private function config_error() {
		if ( ! $this->can_see_details() ) {
			// Nothing actionable for a visitor — render nothing at all.
			return '';
		}

		return $this->notice(
			__( 'Couverty n\'est pas encore connecté.', 'couverty' ),
			__( 'Ajoutez votre clé API dans Réglages → Couverty, puis cliquez sur « Connecter ».', 'couverty' )
		);
	}

	/**
	 * Message shown when the API did not return usable data.
	 *
	 * @param Couverty_API $api   API client, holding the last error.
	 * @param string       $label What was being fetched, e.g. "la carte des plats".
	 * @return string HTML
	 */
	private function data_error( $api, $label ) {
		if ( ! $this->can_see_details() ) {
			return '';
		}

		$detail = $api->get_last_error_message();

		if ( ! $detail ) {
			$detail = sprintf(
				/* translators: %s: what was being fetched, e.g. "la carte des plats" */
				__( 'Aucune donnée disponible pour %s. Vérifiez que le contenu est publié dans Couverty.', 'couverty' ),
				$label
			);
		}

		return $this->notice(
			sprintf(
				/* translators: %s: what was being fetched, e.g. "la carte des plats" */
				__( 'Couverty n\'a pas pu charger %s.', 'couverty' ),
				$label
			),
			$detail
		);
	}

	/**
	 * Build an admin-only inline notice.
	 *
	 * @param string $title  Short headline.
	 * @param string $detail Actionable detail.
	 * @return string HTML
	 */
	private function notice( $title, $detail ) {
		return sprintf(
			'<div class="couverty-error"><strong>%s</strong><br>%s<br><em>%s</em></div>',
			esc_html( $title ),
			esc_html( $detail ),
			esc_html__( 'Ce message n\'est visible que par les administrateurs du site.', 'couverty' )
		);
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
			return '';
		}

		ob_start();
		include $template_file;
		return ob_get_clean();
	}
}

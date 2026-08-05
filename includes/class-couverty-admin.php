<?php
/**
 * Couverty admin settings page
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Admin {

	/**
	 * Tabs available on the settings screen.
	 *
	 * @return array Slug => label.
	 */
	private function get_tabs() {
		return array(
			'settings'    => __( 'Réglages', 'couverty' ),
			'integration' => __( 'Intégration', 'couverty' ),
		);
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 5 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'wp_ajax_couverty_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_couverty_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_couverty_sync_data', array( $this, 'ajax_sync_data' ) );
		add_action( 'wp_ajax_couverty_create_pages', array( $this, 'ajax_create_pages' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Couverty',
			'Couverty',
			'manage_options',
			'couverty',
			array( $this, 'render_settings_page' ),
			'dashicons-food',
			80
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'couverty_settings',
			'couverty_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'couverty_connection',
			__( 'Connexion', 'couverty' ),
			array( $this, 'render_connection_section' ),
			'couverty_settings'
		);

		// label_for makes WordPress wrap the field title in a real <label for>.
		add_settings_field(
			'couverty_api_key',
			__( 'Clé API', 'couverty' ),
			array( $this, 'render_api_key_field' ),
			'couverty_settings',
			'couverty_connection',
			array( 'label_for' => 'couverty_api_key' )
		);

		add_settings_field(
			'couverty_slug',
			__( 'Identifiant du restaurant', 'couverty' ),
			array( $this, 'render_slug_field' ),
			'couverty_settings',
			'couverty_connection',
			array( 'label_for' => 'couverty_slug' )
		);

		add_settings_section(
			'couverty_cache',
			__( 'Cache', 'couverty' ),
			array( $this, 'render_cache_section' ),
			'couverty_settings'
		);

		add_settings_field(
			'couverty_cache_duration',
			__( 'Durée du cache', 'couverty' ),
			array( $this, 'render_cache_duration_field' ),
			'couverty_settings',
			'couverty_cache',
			array( 'label_for' => 'couverty_cache_duration' )
		);

		add_settings_section(
			'couverty_floating',
			__( 'Bouton flottant de réservation', 'couverty' ),
			array( $this, 'render_floating_section' ),
			'couverty_settings'
		);

		add_settings_field(
			'couverty_floating_enabled',
			__( 'Activer le bouton', 'couverty' ),
			array( $this, 'render_floating_enabled_field' ),
			'couverty_settings',
			'couverty_floating'
		);

		add_settings_field(
			'couverty_floating_text',
			__( 'Texte du bouton', 'couverty' ),
			array( $this, 'render_floating_text_field' ),
			'couverty_settings',
			'couverty_floating',
			array( 'label_for' => 'couverty_floating_text' )
		);
	}

	/**
	 * Sanitize settings
	 *
	 * Starts from the stored values so a partial form submission never wipes
	 * settings that were not on screen.
	 *
	 * @param array $settings Submitted settings.
	 *
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$stored    = get_option( 'couverty_settings', array() );
		$sanitized = is_array( $stored ) ? $stored : array();

		// An empty API key field means "keep the current key", so the stored key
		// never has to be rendered back into the page.
		if ( isset( $settings['api_key'] ) && '' !== trim( $settings['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $settings['api_key'] );
		}

		if ( isset( $settings['slug'] ) ) {
			$sanitized['slug'] = sanitize_text_field( $settings['slug'] );
		}

		if ( isset( $settings['cache_duration'] ) ) {
			$sanitized['cache_duration'] = (int) $settings['cache_duration'];
		}

		if ( isset( $settings['floating_text'] ) ) {
			$sanitized['floating_text'] = sanitize_text_field( $settings['floating_text'] );
		}

		// Unchecked checkboxes are simply absent from the payload.
		$sanitized['floating_enabled'] = ! empty( $settings['floating_enabled'] );

		return $sanitized;
	}

	/**
	 * Enqueue admin styles and scripts on Couverty settings page
	 *
	 * @param string $page_hook Current page hook
	 */
	public function enqueue_admin_styles( $page_hook ) {
		if ( 'toplevel_page_couverty' !== $page_hook ) {
			return;
		}

		wp_enqueue_style(
			'couverty-admin',
			COUVERTY_PLUGIN_URL . 'assets/css/couverty-admin.css',
			array(),
			COUVERTY_VERSION
		);

		wp_enqueue_script(
			'couverty-admin',
			COUVERTY_PLUGIN_URL . 'assets/js/couverty-admin.js',
			array( 'jquery' ),
			COUVERTY_VERSION,
			true
		);

		wp_localize_script( 'couverty-admin', 'couverty', array(
			'nonce'    => wp_create_nonce( 'couverty_nonce' ),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'i18n'     => array(
				'testing'      => __( 'Test en cours…', 'couverty' ),
				'syncing'      => __( 'Synchronisation…', 'couverty' ),
				'clearing'     => __( 'Vidage…', 'couverty' ),
				'creating'     => __( 'Création…', 'couverty' ),
				'connected'    => __( 'Connexion réussie', 'couverty' ),
				/* translators: %s: restaurant name */
				'connectedTo'  => __( 'Connecté à %s', 'couverty' ),
				'networkError' => __( 'Impossible de contacter votre site WordPress. Réessayez.', 'couverty' ),
				'syncTimeout'  => __( 'La synchronisation a dépassé le délai d\'attente. Elle continue peut-être en arrière-plan — rechargez la page dans une minute.', 'couverty' ),
				'copied'       => __( 'copié', 'couverty' ),
				'copy'         => __( 'copier', 'couverty' ),
				'plats'        => __( 'plats', 'couverty' ),
				'boissons'     => __( 'boissons', 'couverty' ),
				'menus'        => __( 'menus du jour', 'couverty' ),
				'evenements'   => __( 'événements', 'couverty' ),
			),
		) );
	}

	/**
	 * Single source of truth for Couverty meta field definitions.
	 * Used by the dynamic data docs and the sync section.
	 *
	 * @return array Post type => meta key => [ label, type ].
	 */
	private function get_meta_fields() {
		return array(
			'couverty_plat' => array(
				'couverty_prix'        => array( 'label' => __( 'Prix', 'couverty' ), 'type' => 'string', 'hint' => 'CHF 18.- / CHF 28.-' ),
				'couverty_image_url'   => array( 'label' => __( 'Image URL', 'couverty' ), 'type' => 'string' ),
				'couverty_vegetarien'  => array( 'label' => __( 'Végétarien', 'couverty' ), 'type' => 'boolean' ),
				'couverty_vegan'       => array( 'label' => __( 'Végan', 'couverty' ), 'type' => 'boolean' ),
				'couverty_sans_gluten' => array( 'label' => __( 'Sans gluten', 'couverty' ), 'type' => 'boolean' ),
				'couverty_allergenes'  => array( 'label' => __( 'Allergènes', 'couverty' ), 'type' => 'string' ),
			),
			'couverty_boisson' => array(
				'couverty_prix'   => array( 'label' => __( 'Prix', 'couverty' ), 'type' => 'string', 'hint' => 'CHF 5.- / CHF 8.-' ),
				'couverty_volume' => array( 'label' => __( 'Volume', 'couverty' ), 'type' => 'string' ),
				'couverty_region' => array( 'label' => __( 'Région', 'couverty' ), 'type' => 'string' ),
				'couverty_annee'  => array( 'label' => __( 'Année', 'couverty' ), 'type' => 'integer' ),
			),
			'couverty_menu_jour' => array(
				'couverty_prix'       => array( 'label' => __( 'Prix', 'couverty' ), 'type' => 'string', 'hint' => 'CHF 18.-' ),
				'couverty_jour_label' => array( 'label' => __( 'Jour', 'couverty' ), 'type' => 'string' ),
				'couverty_entree'     => array( 'label' => __( 'Entrée', 'couverty' ), 'type' => 'string' ),
				'couverty_plat'       => array( 'label' => __( 'Plat', 'couverty' ), 'type' => 'string' ),
				'couverty_dessert'    => array( 'label' => __( 'Dessert', 'couverty' ), 'type' => 'string' ),
			),
			'couverty_evenement' => array(
				'couverty_date_debut' => array( 'label' => __( 'Date de début', 'couverty' ), 'type' => 'string', 'hint' => 'ISO 8601' ),
				'couverty_date_fin'   => array( 'label' => __( 'Date de fin', 'couverty' ), 'type' => 'string', 'hint' => __( 'ISO 8601, optionnel', 'couverty' ) ),
				'couverty_image_url'  => array( 'label' => __( 'Image URL', 'couverty' ), 'type' => 'string' ),
				'couverty_url'        => array( 'label' => __( 'Lien vers l\'événement', 'couverty' ), 'type' => 'string', 'hint' => __( 'Page détail sur couverty.ch', 'couverty' ) ),
			),
		);
	}

	/**
	 * CPT labels and taxonomy mappings.
	 *
	 * @return array Post type => [ label, taxonomy ].
	 */
	private function get_post_type_info() {
		return array(
			'couverty_plat'      => array( 'label' => __( 'Plats', 'couverty' ), 'taxonomy' => 'couverty_cat_plat' ),
			'couverty_boisson'   => array( 'label' => __( 'Boissons', 'couverty' ), 'taxonomy' => 'couverty_cat_boisson' ),
			'couverty_menu_jour' => array( 'label' => __( 'Menu du jour', 'couverty' ), 'taxonomy' => null ),
			'couverty_evenement' => array( 'label' => __( 'Événements', 'couverty' ), 'taxonomy' => null ),
		);
	}

	/**
	 * Get published post counts for all Couverty CPTs.
	 *
	 * @return array [ plats => int, boissons => int, menus => int, evenements => int ]
	 */
	private function get_post_counts() {
		$counts = array();

		foreach ( array(
			'plats'      => 'couverty_plat',
			'boissons'   => 'couverty_boisson',
			'menus'      => 'couverty_menu_jour',
			'evenements' => 'couverty_evenement',
		) as $key => $post_type ) {
			$count           = wp_count_posts( $post_type );
			$counts[ $key ] = isset( $count->publish ) ? (int) $count->publish : 0;
		}

		return $counts;
	}

	/**
	 * Print a click-to-copy code chip.
	 *
	 * @param string $value Value to copy.
	 */
	private function copy_chip( $value ) {
		printf(
			'<button type="button" class="couverty-copy" data-copy="%1$s" aria-label="%2$s"><span class="couverty-copy__value">%3$s</span> <span class="couverty-copy__hint" aria-hidden="true">%4$s</span></button>',
			esc_attr( $value ),
			/* translators: %s: the value that will be copied */
			esc_attr( sprintf( __( 'Copier %s', 'couverty' ), $value ) ),
			esc_html( $value ),
			esc_html__( 'copier', 'couverty' )
		);
	}

	// ─── AJAX ───────────────────────────────────────────────────────

	/**
	 * Guard shared by every AJAX handler.
	 */
	private function verify_ajax_request() {
		check_ajax_referer( 'couverty_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permissions insuffisantes.', 'couverty' ) );
		}
	}

	/**
	 * AJAX test connection
	 */
	public function ajax_test_connection() {
		$this->verify_ajax_request();

		// Use the API key from the form when provided, so the key can be tested
		// before it is saved.
		$test_api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		if ( $test_api_key ) {
			$api = new Couverty_API( array_merge( Couverty::get_settings(), array( 'api_key' => $test_api_key ) ) );
		} else {
			$api = Couverty::get_instance()->get_api();
		}

		$result = $api->test_connection();

		if ( ! $result['success'] ) {
			wp_send_json_error( $result['error'] );
		}

		$data     = $result['data'];
		$settings = Couverty::get_settings();

		// Persist the key that was just proven to work, plus the slug it maps to.
		if ( $test_api_key ) {
			$settings['api_key'] = $test_api_key;
		}
		if ( isset( $data['slug'] ) ) {
			$settings['slug'] = $data['slug'];
		}
		update_option( 'couverty_settings', $settings );

		// The memoized client may still hold the previous (or empty) key.
		Couverty::get_instance()->reset_api();

		// A successful connection is the right moment for a first full sync.
		$sync        = new Couverty_Sync();
		$sync_result = $sync->sync( true );
		$counts      = $this->get_post_counts();

		wp_send_json_success( array(
			'restaurant_name' => isset( $data['name'] ) ? $data['name'] : '',
			'slug'            => isset( $data['slug'] ) ? $data['slug'] : '',
			'synced'          => $sync_result['success'],
			'sync_error'      => isset( $sync_result['error'] ) ? $sync_result['error'] : '',
			'counts'          => $counts,
		) );
	}

	/**
	 * AJAX sync data
	 */
	public function ajax_sync_data() {
		$this->verify_ajax_request();

		$sync   = new Couverty_Sync();
		$result = $sync->sync( true );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				isset( $result['error'] )
					? $result['error']
					: __( 'Synchronisation échouée. Vérifiez votre clé API.', 'couverty' )
			);
		}

		wp_send_json_success( array(
			'message' => __( 'Données synchronisées.', 'couverty' ),
			'counts'  => $this->get_post_counts(),
		) );
	}

	/**
	 * AJAX create the example pages
	 */
	public function ajax_create_pages() {
		$this->verify_ajax_request();

		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( __( 'Vous n\'avez pas le droit de créer des pages.', 'couverty' ) );
		}

		$result = Couverty_Pages::create();

		if ( 0 === $result['created'] ) {
			wp_send_json_error( __( 'Les pages existent déjà — retrouvez-les dans Pages.', 'couverty' ) );
		}

		/* translators: %d: number of pages created */
		$message = _n(
			'%d page créée en brouillon. Relisez-la, puis publiez-la.',
			'%d pages créées en brouillon. Relisez-les, puis publiez-les.',
			$result['created'],
			'couverty'
		);

		wp_send_json_success( array(
			'message' => sprintf( $message, $result['created'] ),
		) );
	}

	/**
	 * AJAX clear cache
	 */
	public function ajax_clear_cache() {
		$this->verify_ajax_request();

		Couverty::get_instance()->get_api()->clear_cache();

		wp_send_json_success( __( 'Cache vidé.', 'couverty' ) );
	}

	// ─── Screen ─────────────────────────────────────────────────────

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		$tabs = $this->get_tabs();

		// Read-only tab switch; no state change, so no nonce is needed.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'settings';
		}

		$settings     = Couverty::get_settings();
		$is_connected = ! empty( $settings['api_key'] ) && ! empty( $settings['slug'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Couverty', 'couverty' ); ?></h1>

			<?php
			// add_menu_page() screens don't print these on their own, so "Réglages
			// enregistrés" would never appear without this call.
			settings_errors();
			?>

			<?php $this->render_status_card(); ?>

			<div
				id="couverty-test-result"
				class="couverty-test-result"
				role="status"
				aria-live="polite"
				style="display: none;"
			></div>

			<nav class="nav-tab-wrapper wp-clearfix">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a
						href="<?php echo esc_url( admin_url( 'admin.php?page=couverty&tab=' . $slug ) ); ?>"
						class="nav-tab <?php echo $slug === $active ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'settings' === $active ) : ?>
				<div class="couverty-admin-section">
					<form action="options.php" method="POST">
						<?php
						settings_fields( 'couverty_settings' );
						do_settings_sections( 'couverty_settings' );

						// Until the site is linked, "Connecter" is the primary action;
						// saving is secondary and must not look like the way forward.
						submit_button(
							__( 'Enregistrer les réglages', 'couverty' ),
							$is_connected ? 'primary' : 'secondary'
						);
						?>
					</form>
				</div>
				<?php if ( $is_connected ) : ?>
					<?php $this->render_pages_section(); ?>
					<?php $this->render_sync_section(); ?>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( ! $is_connected ) : ?>
					<div class="notice notice-warning inline">
						<p>
							<strong><?php esc_html_e( 'Votre établissement n\'est pas encore connecté.', 'couverty' ); ?></strong>
							<?php esc_html_e( 'Les shortcodes, champs et endpoints ci-dessous ne renverront rien tant que ce n\'est pas fait.', 'couverty' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=couverty&tab=settings' ) ); ?>">
								<?php esc_html_e( 'Connecter maintenant', 'couverty' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
				<?php $this->render_shortcodes_section(); ?>
				<?php $this->render_dynamic_data_section(); ?>
				<?php $this->render_rest_api_section(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Connection status card, shown above the tabs.
	 */
	private function render_status_card() {
		$settings     = Couverty::get_settings();
		$is_connected = ! empty( $settings['api_key'] ) && ! empty( $settings['slug'] );
		$restaurant   = get_option( 'couverty_restaurant_data', array() );
		$last_sync    = get_option( 'couverty_last_sync', '' );
		$sync_status  = get_option( 'couverty_sync_status', array() );
		$counts       = $this->get_post_counts();

		$sync_failed = isset( $sync_status['success'] ) && false === $sync_status['success'];

		// A key without a slug means the user saved the form but never connected:
		// the most common dead end, so it gets its own explicit state.
		$key_only = ! empty( $settings['api_key'] ) && empty( $settings['slug'] );

		if ( $key_only ) {
			$modifier = 'warning';
			$dot      = 'pending';
			$title    = __( 'Clé enregistrée, établissement pas encore identifié', 'couverty' );
			$hint     = __( 'Cliquez sur « Connecter » ci-dessous pour lier votre établissement et récupérer vos données.', 'couverty' );
		} elseif ( ! $is_connected ) {
			$modifier = 'warning';
			$dot      = 'pending';
			$title    = __( 'Pas encore connecté', 'couverty' );
			$hint     = __( 'Collez votre clé API ci-dessous, puis cliquez sur « Connecter ».', 'couverty' );
		} elseif ( $sync_failed ) {
			$modifier = 'error';
			$dot      = 'disconnected';
			$title    = __( 'Synchronisation interrompue', 'couverty' );
			$hint     = '';
		} else {
			$modifier = 'connected';
			$dot      = 'connected';
			$hint     = '';
			$title    = ! empty( $restaurant['name'] )
				/* translators: %s: restaurant name */
				? sprintf( __( 'Connecté à %s', 'couverty' ), $restaurant['name'] )
				: __( 'Connecté', 'couverty' );
		}
		?>
		<div class="couverty-status couverty-status--<?php echo esc_attr( $modifier ); ?>">
			<div class="couverty-status__main">
				<p class="couverty-status__title">
					<span class="couverty-status-indicator <?php echo esc_attr( $dot ); ?>"></span>
					<?php echo esc_html( $title ); ?>
				</p>
				<p class="couverty-status__meta">
					<?php if ( $is_connected ) : ?>
						<?php if ( $sync_failed ) : ?>
							<?php // What the restaurant owner actually needs to know first. ?>
							<strong><?php esc_html_e( 'Votre site continue d\'afficher les données déjà synchronisées.', 'couverty' ); ?></strong>
							<br>
						<?php endif; ?>
						<span id="couverty-counts">
							<?php
							printf(
								/* translators: 1: plats count, 2: boissons count, 3: menus count, 4: events count */
								esc_html__( '%1$d plats · %2$d boissons · %3$d menus du jour · %4$d événements', 'couverty' ),
								(int) $counts['plats'],
								(int) $counts['boissons'],
								(int) $counts['menus'],
								(int) $counts['evenements']
							);
							?>
						</span>
						<br>
						<?php if ( $last_sync ) : ?>
							<?php
							// Stored in GMT since 1.8.0, so it compares directly with time().
							printf(
								/* translators: %s: human readable time difference, e.g. "5 mins" */
								esc_html__( 'Dernière synchronisation il y a %s.', 'couverty' ),
								esc_html( human_time_diff( strtotime( $last_sync . ' UTC' ), time() ) )
							);
							?>
						<?php else : ?>
							<?php esc_html_e( 'Jamais synchronisé.', 'couverty' ); ?>
						<?php endif; ?>
					<?php else : ?>
						<?php echo esc_html( $hint ); ?>
					<?php endif; ?>
				</p>
			</div>

			<?php if ( $is_connected ) : ?>
				<div class="couverty-status__actions">
					<?php if ( $sync_failed ) : ?>
						<?php // Retrying the same sync would just fail again — send the user to the key. ?>
						<a
							href="<?php echo esc_url( admin_url( 'admin.php?page=couverty&tab=settings#couverty_api_key' ) ); ?>"
							class="button button-primary"
						>
							<?php esc_html_e( 'Mettre à jour la clé API', 'couverty' ); ?>
						</a>
						<button type="button" id="couverty-sync-data" class="button">
							<?php esc_html_e( 'Réessayer', 'couverty' ); ?>
						</button>
					<?php else : ?>
						<button type="button" id="couverty-sync-data" class="button button-primary">
							<?php esc_html_e( 'Actualiser depuis Couverty', 'couverty' ); ?>
						</button>
						<button type="button" id="couverty-clear-cache" class="button">
							<?php esc_html_e( 'Vider le cache', 'couverty' ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $sync_failed && ! empty( $sync_status['error'] ) ) : ?>
			<div class="notice notice-error inline">
				<p>
					<strong><?php esc_html_e( 'Cause :', 'couverty' ); ?></strong>
					<?php echo esc_html( $sync_status['error'] ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'WP-Cron est désactivé sur ce site : la synchronisation automatique dépend donc de la tâche planifiée configurée chez votre hébergeur. Sinon, utilisez le bouton « Synchroniser ».', 'couverty' ); ?>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the example pages section.
	 *
	 * Answers the "what now?" moment right after a successful connection.
	 */
	private function render_pages_section() {
		$pages   = Couverty_Pages::get_status();
		$missing = count( array_filter( $pages, function( $p ) {
			return ! $p['exists'];
		} ) );
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Pages prêtes à l\'emploi', 'couverty' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Créez en un clic les pages qui affichent vos contenus Couverty. Elles sont créées en brouillon : relisez-les et publiez-les quand vous êtes prêt.', 'couverty' ); ?>
			</p>

			<div class="couverty-table-scroll">
				<table class="widefat striped couverty-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Page', 'couverty' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Contenu', 'couverty' ); ?></th>
							<th scope="col"><?php esc_html_e( 'État', 'couverty' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pages as $page ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $page['title'] ); ?></strong></td>
								<td><?php echo esc_html( $page['description'] ); ?></td>
								<td>
									<?php if ( ! $page['exists'] ) : ?>
										<span class="couverty-type"><?php esc_html_e( 'Pas encore créée', 'couverty' ); ?></span>
									<?php else : ?>
										<?php if ( 'publish' === $page['status'] ) : ?>
											<span class="couverty-status-indicator connected"></span>
											<?php esc_html_e( 'Publiée', 'couverty' ); ?>
										<?php else : ?>
											<span class="couverty-status-indicator pending"></span>
											<?php esc_html_e( 'Brouillon', 'couverty' ); ?>
										<?php endif; ?>
										<?php if ( $page['edit_url'] ) : ?>
											— <a href="<?php echo esc_url( $page['edit_url'] ); ?>"><?php esc_html_e( 'Modifier', 'couverty' ); ?></a>
										<?php endif; ?>
										<?php if ( ! empty( $page['needs_image'] ) ) : ?>
											<span class="couverty-hint">
												<?php esc_html_e( 'Ajoutez une image mise en avant : elle devient le bandeau d\'ouverture.', 'couverty' ); ?>
											</span>
										<?php endif; ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<p class="couverty-field-actions">
				<?php if ( $missing > 0 ) : ?>
					<button type="button" id="couverty-create-pages" class="button button-primary">
						<?php
						printf(
							/* translators: %d: number of pages that will be created */
							esc_html( _n( 'Créer %d page manquante', 'Créer %d pages manquantes', $missing, 'couverty' ) ),
							(int) $missing
						);
						?>
					</button>
				<?php else : ?>
					<span class="description"><?php esc_html_e( 'Toutes les pages existent. Vous pouvez aussi insérer ces mises en page dans n\'importe quelle page depuis l\'éditeur : cherchez « Couverty » dans les compositions.', 'couverty' ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render data sync section
	 */
	private function render_sync_section() {
		$all_fields     = $this->get_meta_fields();
		$post_type_info = $this->get_post_type_info();
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Synchronisation des données', 'couverty' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Vos données Couverty sont copiées dans WordPress sous forme de types de contenu personnalisés, toutes les 30 minutes. Elles restent donc disponibles pour vos constructeurs de pages même si l\'API est momentanément injoignable.', 'couverty' ); ?>
			</p>

			<div class="couverty-table-scroll">
				<table class="widefat striped couverty-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Type de contenu', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Champs', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Taxonomie', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_fields as $post_type => $fields ) : ?>
						<?php $info = isset( $post_type_info[ $post_type ] ) ? $post_type_info[ $post_type ] : array( 'taxonomy' => null ); ?>
						<tr>
							<td><code><?php echo esc_html( $post_type ); ?></code></td>
							<td>
								<?php foreach ( array_keys( $fields ) as $key ) : ?>
									<code><?php echo esc_html( $key ); ?></code>
								<?php endforeach; ?>
							</td>
							<td>
								<?php if ( ! empty( $info['taxonomy'] ) ) : ?>
									<code><?php echo esc_html( $info['taxonomy'] ); ?></code>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render shortcodes and blocks reference section
	 */
	private function render_shortcodes_section() {
		$shortcodes = array(
			array(
				'name'       => __( 'Menu (carte des plats)', 'couverty' ),
				'shortcode'  => '[couverty_menu]',
				'attributes' => 'layout="list|grid" show_prices="true|false" show_images="true|false" show_allergens="true|false"',
			),
			array(
				'name'       => __( 'Boissons', 'couverty' ),
				'shortcode'  => '[couverty_boissons]',
				'attributes' => 'layout="list|grid" show_prices="true|false" show_details="true|false"',
			),
			array(
				'name'       => __( 'Menu du jour', 'couverty' ),
				'shortcode'  => '[couverty_menu_du_jour]',
				'attributes' => 'show_price="true|false"',
			),
			array(
				'name'       => __( 'Réservation (widget)', 'couverty' ),
				'shortcode'  => '[couverty_reservation]',
				'attributes' => 'height="300-1200" appearance="card|glass|minimal|dark" radius="none|sm|md|lg"',
			),
		);
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Shortcodes & blocs', 'couverty' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Ces shortcodes fonctionnent dans n\'importe quel éditeur, constructeur de pages ou template de thème. Les mêmes contenus sont disponibles en blocs dans la catégorie « Couverty » de l\'éditeur WordPress.', 'couverty' ); ?>
			</p>

			<div class="couverty-table-scroll">
				<table class="widefat striped couverty-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Contenu', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Shortcode', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Options', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $shortcodes as $sc ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $sc['name'] ); ?></strong></td>
							<td><?php $this->copy_chip( $sc['shortcode'] ); ?></td>
							<td><code><?php echo esc_html( $sc['attributes'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dynamic data reference section
	 */
	private function render_dynamic_data_section() {
		$all_fields     = $this->get_meta_fields();
		$post_type_info = $this->get_post_type_info();
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Données dynamiques (constructeurs de pages)', 'couverty' ); ?></h2>

			<div class="couverty-callout couverty-callout--info">
				<strong><?php esc_html_e( 'En trois étapes :', 'couverty' ); ?></strong>
				<ol>
					<li><?php esc_html_e( 'Créez une boucle de requête (Query Loop) et choisissez un type de contenu Couverty', 'couverty' ); ?></li>
					<li><?php esc_html_e( 'Dans la boucle, utilisez « Champ personnalisé » ou « Données dynamiques »', 'couverty' ); ?></li>
					<li><?php esc_html_e( 'Collez le nom du champ depuis les tableaux ci-dessous', 'couverty' ); ?></li>
				</ol>
			</div>

			<?php foreach ( $all_fields as $post_type => $fields ) : ?>
				<?php $info = isset( $post_type_info[ $post_type ] ) ? $post_type_info[ $post_type ] : array( 'label' => $post_type ); ?>
				<h3><?php echo esc_html( $info['label'] ); ?> — <code><?php echo esc_html( $post_type ); ?></code></h3>

				<div class="couverty-table-scroll">
					<table class="widefat striped couverty-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Champ', 'couverty' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Nom du champ', 'couverty' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'couverty' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $fields as $key => $field ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $field['label'] ); ?></strong>
									<?php if ( ! empty( $field['hint'] ) ) : ?>
										<span class="couverty-hint"><?php echo esc_html( $field['hint'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php $this->copy_chip( $key ); ?></td>
								<td class="couverty-type"><?php echo esc_html( $field['type'] ); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td><strong><?php esc_html_e( 'Nom', 'couverty' ); ?></strong></td>
							<td><em><?php esc_html_e( 'Titre de l\'article (champ WordPress standard)', 'couverty' ); ?></em></td>
							<td class="couverty-type">string</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Description', 'couverty' ); ?></strong></td>
							<td><em><?php esc_html_e( 'Contenu de l\'article (champ WordPress standard)', 'couverty' ); ?></em></td>
							<td class="couverty-type">string</td>
						</tr>
					</tbody>
				</table>
				</div>
			<?php endforeach; ?>

			<div class="couverty-callout couverty-callout--warning">
				<strong><?php esc_html_e( 'Taxonomies (pour filtrer) :', 'couverty' ); ?></strong>
				<ul>
					<li><code>couverty_cat_plat</code> — <?php esc_html_e( 'Catégories de plats', 'couverty' ); ?></li>
					<li><code>couverty_cat_boisson</code> — <?php esc_html_e( 'Catégories de boissons', 'couverty' ); ?></li>
				</ul>
			</div>

			<h3><?php esc_html_e( 'Syntaxe par constructeur', 'couverty' ); ?></h3>
			<div class="couverty-table-scroll">
				<table class="widefat striped couverty-table">
				<tbody>
					<tr>
						<td><strong>Elementor</strong></td>
						<td><?php esc_html_e( 'Dynamic Tags → Custom Field → nom du champ', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td><strong>Bricks</strong></td>
						<td>
							<?php
							printf(
								/* translators: 1: generic syntax, 2: concrete example */
								esc_html__( 'Syntaxe %1$s — par exemple %2$s', 'couverty' ),
								'<code>{cf_nom_du_champ}</code>',
								'<code>{cf_couverty_prix}</code>'
							);
							?>
						</td>
					</tr>
					<tr>
						<td><strong>Divi</strong></td>
						<td><?php esc_html_e( 'Dynamic Content → Post Fields → Custom Fields → nom du champ', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td><strong>Beaver Builder</strong></td>
						<td><?php esc_html_e( 'Field Connections → Post Custom Field → nom du champ', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td><strong>WordPress</strong></td>
						<td><?php esc_html_e( 'Blocs Couverty, ou bloc Boucle de requête filtré sur un type de contenu Couverty', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td><strong>PHP</strong></td>
						<td><?php $this->copy_chip( "get_post_meta( \$post_id, 'couverty_prix', true )" ); ?></td>
					</tr>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render REST API reference section
	 */
	private function render_rest_api_section() {
		$rest_url  = rest_url( 'couverty/v1/' );
		$endpoints = array(
			'menu'         => __( 'Plats par catégorie', 'couverty' ),
			'boissons'     => __( 'Boissons par catégorie', 'couverty' ),
			'menu-du-jour' => __( 'Menu du jour / de la semaine', 'couverty' ),
			'restaurant'   => __( 'Informations du restaurant', 'couverty' ),
		);
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'API REST & fonctions PHP', 'couverty' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Pour les intégrations sur mesure : ces endpoints servent les données Couverty depuis votre propre site, en passant par le cache du plugin.', 'couverty' ); ?>
			</p>

			<div class="couverty-table-scroll">
				<table class="widefat striped couverty-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Endpoint', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Données', 'couverty' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Fonction PHP', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $endpoints as $path => $description ) : ?>
						<tr>
							<td><?php $this->copy_chip( $rest_url . $path ); ?></td>
							<td><?php echo esc_html( $description ); ?></td>
							<td><?php $this->copy_chip( 'couverty_get_' . str_replace( '-', '_', $path ) . '()' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>

			<div class="couverty-callout">
				<p><strong><?php esc_html_e( 'Exemple PHP', 'couverty' ); ?></strong></p>
				<pre><code>$menu = couverty_get_menu();

foreach ( $menu['categories'] as $categorie ) {
    echo esc_html( $categorie['nom'] );

    foreach ( $categorie['plats'] as $plat ) {
        echo esc_html( $plat['nom'] );
    }
}</code></pre>
			</div>
		</div>
		<?php
	}

	// ─── Fields ─────────────────────────────────────────────────────

	/**
	 * Render connection section
	 */
	public function render_connection_section() {
		echo '<p>' . esc_html__( 'La clé API relie ce site à votre établissement Couverty.', 'couverty' ) . '</p>';
	}

	/**
	 * Render API key field
	 */
	public function render_api_key_field() {
		$settings = Couverty::get_settings();
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$has_key  = '' !== $api_key;
		$is_linked = $has_key && ! empty( $settings['slug'] );
		?>
		<input
			type="password"
			id="couverty_api_key"
			name="couverty_settings[api_key]"
			value=""
			class="regular-text"
			autocomplete="off"
			spellcheck="false"
			placeholder="<?php echo esc_attr( $has_key ? __( 'Laissez vide pour conserver la clé actuelle', 'couverty' ) : 'qr_...' ); ?>"
		/>

		<p class="description">
			<?php if ( $has_key ) : ?>
				<?php
				printf(
					/* translators: %s: masked API key preview */
					esc_html__( 'Clé enregistrée : %s', 'couverty' ),
					'<span class="couverty-key-preview">' . esc_html( substr( $api_key, 0, 7 ) . '••••••••' ) . '</span>'
				);
				?>
				<br>
			<?php endif; ?>
			<?php
			printf(
				/* translators: %s: link to the Couverty dashboard integrations page */
				esc_html__( 'Créez-la dans %s.', 'couverty' ),
				'<a href="' . esc_url( rtrim( $settings['base_url'], '/' ) . '/settings?tab=integrations' ) . '" target="_blank" rel="noopener">'
					. esc_html__( 'votre tableau de bord Couverty → Intégrations', 'couverty' )
					. '</a>'
			);
			?>
		</p>

		<p class="couverty-field-actions">
			<?php
			// Connecting is what actually links the site (it stores the key, resolves
			// the slug and runs the first sync), so it carries the primary style until
			// the site is linked. Saving settings is the secondary action then.
			$class = $is_linked ? 'button' : 'button button-primary';
			$label = $is_linked ? __( 'Tester la connexion', 'couverty' ) : __( 'Connecter', 'couverty' );
			?>
			<button type="button" id="couverty-test-connection" class="<?php echo esc_attr( $class ); ?>">
				<?php echo esc_html( $label ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Render slug field
	 */
	public function render_slug_field() {
		$settings = Couverty::get_settings();
		$slug     = isset( $settings['slug'] ) ? $settings['slug'] : '';
		?>
		<input
			type="text"
			id="couverty_slug"
			name="couverty_settings[slug]"
			value="<?php echo esc_attr( $slug ); ?>"
			class="regular-text"
			readonly
		/>
		<p class="description"><?php esc_html_e( 'Rempli automatiquement à la connexion.', 'couverty' ); ?></p>
		<?php
	}

	/**
	 * Render cache section
	 */
	public function render_cache_section() {
		echo '<p>' . esc_html__( 'Durée pendant laquelle les données sont réutilisées sans rappeler l\'API Couverty.', 'couverty' ) . '</p>';
	}

	/**
	 * Render cache duration field
	 */
	public function render_cache_duration_field() {
		$settings        = Couverty::get_settings();
		$cache_duration  = isset( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 600;
		$cache_durations = array(
			300  => __( '5 minutes', 'couverty' ),
			600  => __( '10 minutes', 'couverty' ),
			1800 => __( '30 minutes', 'couverty' ),
			3600 => __( '1 heure', 'couverty' ),
		);
		?>
		<select id="couverty_cache_duration" name="couverty_settings[cache_duration]">
			<?php foreach ( $cache_durations as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $cache_duration, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render floating section
	 */
	public function render_floating_section() {
		echo '<p>' . esc_html__( 'Affiche un bouton de réservation en bas à droite, sur toutes les pages du site.', 'couverty' ) . '</p>';
	}

	/**
	 * Render floating enabled field
	 */
	public function render_floating_enabled_field() {
		$settings = Couverty::get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="couverty_settings[floating_enabled]"
				value="1"
				<?php checked( ! empty( $settings['floating_enabled'] ) ); ?>
			/>
			<?php esc_html_e( 'Afficher sur toutes les pages', 'couverty' ); ?>
		</label>
		<?php
	}

	/**
	 * Render floating text field
	 */
	public function render_floating_text_field() {
		$settings      = Couverty::get_settings();
		$floating_text = isset( $settings['floating_text'] ) ? $settings['floating_text'] : '';
		?>
		<input
			type="text"
			id="couverty_floating_text"
			name="couverty_settings[floating_text]"
			value="<?php echo esc_attr( $floating_text ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Réserver', 'couverty' ); ?>"
		/>
		<?php
	}
}

// Initialize admin class
if ( is_admin() ) {
	new Couverty_Admin();
}

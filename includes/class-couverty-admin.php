<?php
/**
 * Couverty admin settings page
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Admin {
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

		// Connection section
		add_settings_section(
			'couverty_connection',
			__( 'Connection', 'couverty' ),
			array( $this, 'render_connection_section' ),
			'couverty_settings'
		);

		add_settings_field(
			'couverty_api_key',
			__( 'API Key', 'couverty' ),
			array( $this, 'render_api_key_field' ),
			'couverty_settings',
			'couverty_connection'
		);

		// Cache section
		add_settings_section(
			'couverty_cache',
			__( 'Cache', 'couverty' ),
			array( $this, 'render_cache_section' ),
			'couverty_settings'
		);

		add_settings_field(
			'couverty_cache_duration',
			__( 'Cache Duration', 'couverty' ),
			array( $this, 'render_cache_duration_field' ),
			'couverty_settings',
			'couverty_cache'
		);

		// Floating button section
		add_settings_section(
			'couverty_floating',
			__( 'Floating Button', 'couverty' ),
			array( $this, 'render_floating_section' ),
			'couverty_settings'
		);

		add_settings_field(
			'couverty_floating_enabled',
			__( 'Enable Floating Button', 'couverty' ),
			array( $this, 'render_floating_enabled_field' ),
			'couverty_settings',
			'couverty_floating'
		);

		add_settings_field(
			'couverty_floating_text',
			__( 'Button Text', 'couverty' ),
			array( $this, 'render_floating_text_field' ),
			'couverty_settings',
			'couverty_floating'
		);

		add_settings_field(
			'couverty_slug',
			__( 'Restaurant Slug', 'couverty' ),
			array( $this, 'render_slug_field' ),
			'couverty_settings',
			'couverty_floating'
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $settings Settings array
	 *
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = array();

		if ( isset( $settings['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $settings['api_key'] );
		}

		if ( isset( $settings['slug'] ) ) {
			$sanitized['slug'] = sanitize_text_field( $settings['slug'] );
		}

		if ( isset( $settings['cache_duration'] ) ) {
			$sanitized['cache_duration'] = (int) $settings['cache_duration'];
		}

		if ( isset( $settings['floating_enabled'] ) ) {
			$sanitized['floating_enabled'] = (bool) $settings['floating_enabled'];
		}

		if ( isset( $settings['floating_text'] ) ) {
			$sanitized['floating_text'] = sanitize_text_field( $settings['floating_text'] );
		}

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
			'nonce'     => wp_create_nonce( 'couverty_nonce' ),
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
		) );
	}

	/**
	 * Single source of truth for Couverty meta field definitions.
	 * Used by metabox, dynamic data docs, and sync section.
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
				'couverty_dessert'    => array( 'label' => __( 'Dessert', 'couverty' ), 'type' => 'string' ),
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
			'couverty_plat'     => array( 'label' => __( 'Plats', 'couverty' ), 'taxonomy' => 'couverty_cat_plat' ),
			'couverty_boisson'  => array( 'label' => __( 'Boissons', 'couverty' ), 'taxonomy' => 'couverty_cat_boisson' ),
			'couverty_menu_jour' => array( 'label' => __( 'Menu du jour', 'couverty' ), 'taxonomy' => null ),
		);
	}

	/**
	 * Get published post counts for all Couverty CPTs.
	 *
	 * @return array [ plats => int, boissons => int, menus => int ]
	 */
	private function get_post_counts() {
		$plat_count    = wp_count_posts( 'couverty_plat' );
		$boisson_count = wp_count_posts( 'couverty_boisson' );
		$menu_count    = wp_count_posts( 'couverty_menu_jour' );

		return array(
			'plats'    => isset( $plat_count->publish ) ? (int) $plat_count->publish : 0,
			'boissons' => isset( $boisson_count->publish ) ? (int) $boisson_count->publish : 0,
			'menus'    => isset( $menu_count->publish ) ? (int) $menu_count->publish : 0,
		);
	}

	/**
	 * AJAX test connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'couverty_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'couverty' ) );
		}

		$api      = Couverty::get_instance()->get_api();
		$result   = $api->test_connection();

		if ( ! $result['success'] ) {
			wp_send_json_error( $result['error'] );
		}

		$data = $result['data'];

		// Auto-save slug if present.
		if ( isset( $data['slug'] ) ) {
			$settings         = Couverty::get_settings();
			$settings['slug'] = $data['slug'];
			update_option( 'couverty_settings', $settings );
		}

		// Auto-sync data on successful connection (force = skip smart polling check).
		$sync        = new Couverty_Sync();
		$sync_result = $sync->sync( true );
		$counts      = $this->get_post_counts();

		wp_send_json_success( array(
			'restaurant_name' => isset( $data['name'] ) ? esc_html( $data['name'] ) : '',
			'slug'            => isset( $data['slug'] ) ? esc_attr( $data['slug'] ) : '',
			'synced'          => $sync_result['success'],
			'sync_error'      => isset( $sync_result['error'] ) ? $sync_result['error'] : '',
			'plats'           => $counts['plats'],
			'boissons'        => $counts['boissons'],
		) );
	}

	/**
	 * AJAX sync data
	 */
	public function ajax_sync_data() {
		check_ajax_referer( 'couverty_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'couverty' ) );
		}

		$sync   = new Couverty_Sync();
		$result = $sync->sync( true );

		if ( ! $result['success'] ) {
			wp_send_json_error( isset( $result['error'] ) ? $result['error'] : __( 'Sync failed. Check your API key.', 'couverty' ) );
		}

		$counts = $this->get_post_counts();

		wp_send_json_success( array(
			'message'  => __( 'Data synced successfully!', 'couverty' ),
			'plats'    => $counts['plats'],
			'boissons' => $counts['boissons'],
			'menus'    => $counts['menus'],
		) );
	}

	/**
	 * AJAX clear cache
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'couverty_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'couverty' ) );
		}

		$api = Couverty::get_instance()->get_api();
		$api->clear_cache();

		wp_send_json_success( __( 'Cache cleared successfully', 'couverty' ) );
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		$settings = Couverty::get_settings();
		$is_connected = ! empty( $settings['api_key'] ) && ! empty( $settings['slug'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Couverty Settings', 'couverty' ); ?></h1>

			<form action="options.php" method="POST">
				<?php
				settings_fields( 'couverty_settings' );
				do_settings_sections( 'couverty_settings' );
				submit_button();
				?>
			</form>

			<div id="couverty-test-result" style="display: none; margin-top: 20px;"></div>

			<?php $this->render_sync_section(); ?>
			<?php $this->render_shortcodes_section( $is_connected ); ?>
			<?php $this->render_dynamic_data_section(); ?>
			<?php $this->render_rest_api_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render shortcodes and blocks reference section
	 *
	 * @param bool $is_connected Whether the plugin is connected
	 */
	private function render_shortcodes_section( $is_connected ) {
		$shortcodes = array(
			array(
				'name'        => __( 'Menu (carte des plats)', 'couverty' ),
				'shortcode'   => '[couverty_menu]',
				'block'       => 'couverty/menu',
				'attributes'  => 'layout="list|grid" show_prices="true|false" show_images="true|false" show_allergens="true|false"',
			),
			array(
				'name'        => __( 'Boissons', 'couverty' ),
				'shortcode'   => '[couverty_boissons]',
				'block'       => 'couverty/boissons',
				'attributes'  => 'layout="list|grid" show_prices="true|false" show_details="true|false"',
			),
			array(
				'name'        => __( 'Menu du jour', 'couverty' ),
				'shortcode'   => '[couverty_menu_du_jour]',
				'block'       => 'couverty/menu-du-jour',
				'attributes'  => 'show_price="true|false"',
			),
			array(
				'name'        => __( 'Réservation (widget)', 'couverty' ),
				'shortcode'   => '[couverty_reservation]',
				'block'       => 'couverty/reservation',
				'attributes'  => 'height="600" appearance="card|glass|minimal|dark" radius="none|sm|md|lg"',
			),
		);
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Shortcodes & Blocks', 'couverty' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'Use these shortcodes in any editor, page builder, or theme template. Gutenberg blocks are also available in the block inserter under the "Couverty" category.', 'couverty' ); ?>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Content', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'Options', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $shortcodes as $sc ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $sc['name'] ); ?></strong></td>
							<td><code style="cursor: pointer; user-select: all;"><?php echo esc_html( $sc['shortcode'] ); ?></code></td>
							<td><code style="font-size: 12px; color: #666;"><?php echo esc_html( $sc['attributes'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! $is_connected ) : ?>
				<p class="description" style="margin-top: 10px; color: #d63638;">
					<?php esc_html_e( 'Connect your API key above to start using shortcodes and blocks.', 'couverty' ); ?>
				</p>
			<?php endif; ?>
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
			<h2><?php esc_html_e( 'Dynamic Data', 'couverty' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'Couverty stores your restaurant data as standard WordPress custom fields. This means they work automatically with any page builder or theme.', 'couverty' ); ?>
			</p>

			<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 16px; margin-bottom: 20px;">
				<strong><?php esc_html_e( 'How it works:', 'couverty' ); ?></strong>
				<ol style="margin: 8px 0 0; padding-left: 20px;">
					<li><?php esc_html_e( 'In your page builder, create a Query Loop (or Posts element) and select a Couverty post type as source', 'couverty' ); ?></li>
					<li><?php esc_html_e( 'Inside the loop, use the "Custom Field" or "Dynamic Data" option of your builder', 'couverty' ); ?></li>
					<li><?php esc_html_e( 'Enter the field name from the table below to display the corresponding data', 'couverty' ); ?></li>
				</ol>
			</div>

			<?php foreach ( $all_fields as $post_type => $fields ) :
				$info = isset( $post_type_info[ $post_type ] ) ? $post_type_info[ $post_type ] : array( 'label' => $post_type );
			?>
				<h3 style="margin-top: 20px; margin-bottom: 8px;">
					<?php echo esc_html( $info['label'] ); ?>
					<span style="font-weight: normal; color: #666; font-size: 13px;">
						— <?php echo esc_html( $post_type ); ?>
					</span>
				</h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Field', 'couverty' ); ?></th>
							<th><?php esc_html_e( 'Field Name', 'couverty' ); ?></th>
							<th><?php esc_html_e( 'Type', 'couverty' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $fields as $key => $field ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $field['label'] ); ?></strong>
									<?php if ( ! empty( $field['hint'] ) ) : ?>
										<br><span style="color: #999; font-size: 12px;"><?php echo esc_html( $field['hint'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><code style="cursor: pointer; user-select: all;"><?php echo esc_html( $key ); ?></code></td>
								<td><span style="color: #666;"><?php echo esc_html( $field['type'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td><strong><?php esc_html_e( 'Name', 'couverty' ); ?></strong></td>
							<td><em><?php esc_html_e( 'Post title (standard WordPress field)', 'couverty' ); ?></em></td>
							<td><span style="color: #666;">string</span></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Description', 'couverty' ); ?></strong></td>
							<td><em><?php esc_html_e( 'Post content (standard WordPress field)', 'couverty' ); ?></em></td>
							<td><span style="color: #666;">string</span></td>
						</tr>
					</tbody>
				</table>
			<?php endforeach; ?>

			<div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 12px 16px; margin-top: 20px;">
				<strong><?php esc_html_e( 'Taxonomies (for filtering):', 'couverty' ); ?></strong>
				<ul style="margin: 8px 0 0; padding-left: 20px;">
					<li><code style="user-select: all;">couverty_cat_plat</code> — <?php esc_html_e( 'Dish categories', 'couverty' ); ?></li>
					<li><code style="user-select: all;">couverty_cat_boisson</code> — <?php esc_html_e( 'Drink categories', 'couverty' ); ?></li>
				</ul>
			</div>

			<div style="background: #f6f7f7; border-left: 4px solid #8c8f94; padding: 12px 16px; margin-top: 15px;">
				<strong><?php esc_html_e( 'Builder-specific syntax:', 'couverty' ); ?></strong>
				<table style="margin-top: 8px; border-collapse: collapse; width: 100%;">
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>Elementor</strong></td>
						<td style="padding: 4px 0;"><?php esc_html_e( 'Dynamic Tags → Custom Field → enter the field name', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>Bricks</strong></td>
						<td style="padding: 4px 0;">
							<?php
							printf(
								/* translators: %1$s: example syntax, %2$s: field name */
								esc_html__( 'Use %1$s syntax (e.g. %2$s)', 'couverty' ),
								'<code>{cf_field_name}</code>',
								'<code>{cf_couverty_prix}</code>'
							);
							?>
						</td>
					</tr>
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>Divi</strong></td>
						<td style="padding: 4px 0;"><?php esc_html_e( 'Dynamic Content → Post Fields → Custom Fields → enter the field name', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>Beaver Builder</strong></td>
						<td style="padding: 4px 0;"><?php esc_html_e( 'Field Connections → Post Custom Field → enter the field name', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>Gutenberg</strong></td>
						<td style="padding: 4px 0;"><?php esc_html_e( 'Use the Couverty blocks or Query Loop block with post type filter', 'couverty' ); ?></td>
					</tr>
					<tr>
						<td style="padding: 4px 12px 4px 0; white-space: nowrap;"><strong>PHP</strong></td>
						<td style="padding: 4px 0;"><code style="user-select: all;">get_post_meta( $post_id, 'couverty_prix', true )</code></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render REST API reference section
	 */
	private function render_rest_api_section() {
		$rest_url = rest_url( 'couverty/v1/' );
		$endpoints = array(
			array(
				'path'        => 'menu',
				'description' => __( 'Plats par catégorie', 'couverty' ),
			),
			array(
				'path'        => 'boissons',
				'description' => __( 'Boissons par catégorie', 'couverty' ),
			),
			array(
				'path'        => 'menu-du-jour',
				'description' => __( 'Menu du jour / de la semaine', 'couverty' ),
			),
			array(
				'path'        => 'restaurant',
				'description' => __( 'Informations du restaurant', 'couverty' ),
			),
		);
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'REST API (for page builders)', 'couverty' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'Use these endpoints in Bricks, Elementor, or any page builder that supports dynamic data via REST API or PHP functions.', 'couverty' ); ?>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Endpoint', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'Data', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'PHP Function', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $endpoints as $ep ) : ?>
						<tr>
							<td><code style="cursor: pointer; user-select: all;"><?php echo esc_url( $rest_url . $ep['path'] ); ?></code></td>
							<td><?php echo esc_html( $ep['description'] ); ?></td>
							<td><code style="cursor: pointer; user-select: all;">couverty_get_<?php echo esc_html( str_replace( '-', '_', $ep['path'] ) ); ?>()</code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description" style="margin-top: 10px;">
				<?php
				printf(
					/* translators: %s: example PHP code */
					esc_html__( 'PHP example: %s', 'couverty' ),
					'<code style="user-select: all;">$menu = couverty_get_menu(); foreach ( $menu[\'categories\'] as $cat ) { echo $cat[\'nom\']; }</code>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render data sync section
	 */
	private function render_sync_section() {
		$last_sync    = get_option( 'couverty_last_sync', '' );
		$sync_status  = get_option( 'couverty_sync_status', array() );
		$counts       = $this->get_post_counts();
		$is_stale     = false;

		if ( $last_sync ) {
			$last_sync_ts = strtotime( $last_sync );
			$is_stale     = $last_sync_ts && ( time() - $last_sync_ts ) > 3600;
		}

		$status_success = isset( $sync_status['success'] ) ? $sync_status['success'] : null;
		$status_error   = isset( $sync_status['error'] ) ? $sync_status['error'] : '';
		?>
		<div class="couverty-admin-section">
			<h2><?php esc_html_e( 'Data Sync', 'couverty' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'Couverty data is synced into WordPress as custom post types. This makes your menu available as dynamic content in any page builder (Gutenberg, Bricks, Elementor, etc.).', 'couverty' ); ?>
			</p>

			<?php if ( false === $status_success && $status_error ) : ?>
				<div class="notice notice-error inline" style="margin: 0 0 15px;">
					<p>
						<strong><?php esc_html_e( 'Last sync failed:', 'couverty' ); ?></strong>
						<?php echo esc_html( $status_error ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $is_stale ) : ?>
				<div class="notice notice-warning inline" style="margin: 0 0 15px;">
					<p>
						<?php esc_html_e( 'Data may be outdated — last sync was over 1 hour ago. WP-Cron requires site traffic to run. Click "Sync Now" to update.', 'couverty' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Status', 'couverty' ); ?></th>
					<td>
						<?php if ( $last_sync ) : ?>
							<?php if ( true === $status_success ) : ?>
								<span style="color: #00a32a;">&#9679;</span>
							<?php elseif ( false === $status_success ) : ?>
								<span style="color: #d63638;">&#9679;</span>
							<?php endif; ?>
							<?php
							printf(
								/* translators: %s: last sync timestamp */
								esc_html__( 'Last sync: %s', 'couverty' ),
								esc_html( $last_sync )
							);
							?>
						<?php else : ?>
							<span style="color: #dba617;">&#9679;</span>
							<?php esc_html_e( 'Never synced', 'couverty' ); ?>
						<?php endif; ?>
						<br>
						<span class="description">
							<?php
							printf(
								/* translators: %1$d: plats count, %2$d: boissons count, %3$d: menus count */
								esc_html__( '%1$d plats, %2$d boissons, %3$d menus du jour', 'couverty' ),
								$counts['plats'],
								$counts['boissons'],
								$counts['menus']
							);
							?>
						</span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Actions', 'couverty' ); ?></th>
					<td>
						<button type="button" id="couverty-sync-data" class="button button-primary">
							<?php esc_html_e( 'Sync Now', 'couverty' ); ?>
						</button>
						<span class="description" style="margin-left: 10px;">
							<?php esc_html_e( 'Auto-syncs every 30 minutes via WP-Cron.', 'couverty' ); ?>
						</span>
					</td>
				</tr>
			</table>

			<?php
			$all_fields     = $this->get_meta_fields();
			$post_type_info = $this->get_post_type_info();
			?>
			<h3 style="margin-top: 15px; margin-bottom: 8px;"><?php esc_html_e( 'Available post types', 'couverty' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post Type', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'Fields', 'couverty' ); ?></th>
						<th><?php esc_html_e( 'Taxonomy', 'couverty' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_fields as $post_type => $fields ) :
						$info = isset( $post_type_info[ $post_type ] ) ? $post_type_info[ $post_type ] : array( 'taxonomy' => null );
						$field_codes = array();
						foreach ( array_keys( $fields ) as $key ) {
							$field_codes[] = '<code>' . esc_html( $key ) . '</code>';
						}
					?>
						<tr>
							<td><strong><?php echo esc_html( $post_type ); ?></strong></td>
							<td><?php echo implode( ' ', $field_codes ); // phpcs:ignore -- each item is escaped above. ?></td>
							<td><?php echo ! empty( $info['taxonomy'] ) ? '<code>' . esc_html( $info['taxonomy'] ) . '</code>' : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'Select these post types in your page builder\'s Query Loop / Posts element to display Couverty data with full layout control.', 'couverty' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render connection section
	 */
	public function render_connection_section() {
		echo '<p>' . esc_html__( 'Configure your Couverty API connection', 'couverty' ) . '</p>';
	}

	/**
	 * Render API key field
	 */
	public function render_api_key_field() {
		$settings = Couverty::get_settings();
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		?>
		<input
			type="password"
			name="couverty_settings[api_key]"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
			required
		/>
		<p class="description"><?php esc_html_e( 'Your Couverty API key', 'couverty' ); ?></p>
		<button type="button" id="couverty-test-connection" class="button">
			<?php esc_html_e( 'Test Connection', 'couverty' ); ?>
		</button>
		<?php
	}

	/**
	 * Render cache section
	 */
	public function render_cache_section() {
		echo '<p>' . esc_html__( 'Configure caching for API responses', 'couverty' ) . '</p>';
	}

	/**
	 * Render cache duration field
	 */
	public function render_cache_duration_field() {
		$settings        = Couverty::get_settings();
		$cache_duration  = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 600;
		$cache_durations = array(
			300   => __( '5 minutes', 'couverty' ),
			600   => __( '10 minutes', 'couverty' ),
			1800  => __( '30 minutes', 'couverty' ),
			3600  => __( '1 hour', 'couverty' ),
		);
		?>
		<select name="couverty_settings[cache_duration]">
			<?php foreach ( $cache_durations as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $cache_duration, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<button type="button" id="couverty-clear-cache" class="button" style="margin-left: 10px;">
			<?php esc_html_e( 'Clear Cache Now', 'couverty' ); ?>
		</button>
		<?php
	}

	/**
	 * Render floating section
	 */
	public function render_floating_section() {
		echo '<p>' . esc_html__( 'Configure floating reservation button', 'couverty' ) . '</p>';
	}

	/**
	 * Render floating enabled field
	 */
	public function render_floating_enabled_field() {
		$settings          = Couverty::get_settings();
		$floating_enabled  = isset( $settings['floating_enabled'] ) ? $settings['floating_enabled'] : false;
		?>
		<label>
			<input
				type="checkbox"
				name="couverty_settings[floating_enabled]"
				value="1"
				<?php checked( $floating_enabled, 1 ); ?>
			/>
			<?php esc_html_e( 'Show floating button on all pages', 'couverty' ); ?>
		</label>
		<?php
	}

	/**
	 * Render floating text field
	 */
	public function render_floating_text_field() {
		$settings       = Couverty::get_settings();
		$floating_text  = isset( $settings['floating_text'] ) ? $settings['floating_text'] : 'Réserver';
		?>
		<input
			type="text"
			name="couverty_settings[floating_text]"
			value="<?php echo esc_attr( $floating_text ); ?>"
			class="regular-text"
			placeholder="Réserver"
		/>
		<p class="description"><?php esc_html_e( 'Text displayed on the floating button', 'couverty' ); ?></p>
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
			name="couverty_settings[slug]"
			value="<?php echo esc_attr( $slug ); ?>"
			class="regular-text"
			readonly
		/>
		<p class="description"><?php esc_html_e( 'Auto-filled when connection is successful', 'couverty' ); ?></p>
		<?php
	}
}

// Initialize admin class
if ( is_admin() ) {
	new Couverty_Admin();
}

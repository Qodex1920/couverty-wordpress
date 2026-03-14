<?php
/**
 * Couverty data sync — Custom Post Types
 *
 * Syncs menu, drinks, and daily menu data from the Couverty API
 * into WordPress CPTs so the data is available natively in ALL
 * page builders and editors (Gutenberg, Bricks, Elementor, etc.).
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Sync {

	const CRON_HOOK     = 'couverty_sync_event';
	const CRON_INTERVAL = 'couverty_30min';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( self::CRON_HOOK, array( $this, 'sync' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
	}

	// ─── Activation / Deactivation ──────────────────────────────────

	/**
	 * Plugin activation — register CPTs + schedule cron
	 */
	public static function activate() {
		$instance = new self();
		$instance->register_content_types();
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, self::CRON_INTERVAL, self::CRON_HOOK );
		}
	}

	/**
	 * Plugin deactivation — unschedule cron
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		flush_rewrite_rules();
	}

	/**
	 * Register 30-minute cron interval
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules[ self::CRON_INTERVAL ] = array(
			'interval' => 1800,
			'display'  => __( 'Every 30 minutes', 'couverty' ),
		);
		return $schedules;
	}

	// ─── Content types registration ─────────────────────────────────

	/**
	 * Register all CPTs, taxonomies, and meta fields
	 */
	public function register_content_types() {
		// CPTs.
		$this->register_cpt( 'couverty_plat', __( 'Plats', 'couverty' ), __( 'Plat', 'couverty' ), 'dashicons-food' );
		$this->register_cpt( 'couverty_boisson', __( 'Boissons', 'couverty' ), __( 'Boisson', 'couverty' ), 'dashicons-coffee' );
		$this->register_cpt( 'couverty_menu_jour', __( 'Menus du jour', 'couverty' ), __( 'Menu du jour', 'couverty' ), 'dashicons-calendar-alt' );

		// Taxonomies.
		$this->register_tax( 'couverty_cat_plat', 'couverty_plat', __( 'Categories plats', 'couverty' ) );
		$this->register_tax( 'couverty_cat_boisson', 'couverty_boisson', __( 'Categories boissons', 'couverty' ) );

		// Meta fields.
		$this->register_meta();
	}

	/**
	 * Register a single CPT
	 *
	 * @param string $slug  Post type slug.
	 * @param string $plural Plural label.
	 * @param string $singular Singular label.
	 * @param string $icon  Dashicon class.
	 */
	private function register_cpt( $slug, $plural, $singular, $icon ) {
		register_post_type( $slug, array(
			'labels'              => array(
				'name'          => $plural,
				'singular_name' => $singular,
			),
			'public'              => true,
			'show_ui'             => false,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
		) );
	}

	/**
	 * Register a taxonomy
	 *
	 * @param string $slug      Taxonomy slug.
	 * @param string $post_type Associated post type.
	 * @param string $label     Display label.
	 */
	private function register_tax( $slug, $post_type, $label ) {
		register_taxonomy( $slug, $post_type, array(
			'labels'            => array(
				'name'          => $label,
				'singular_name' => $label,
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => false,
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'rewrite'           => false,
		) );
	}

	/**
	 * Register all meta fields with REST API visibility
	 */
	private function register_meta() {
		$fields = array(
			'couverty_plat' => array(
				'couverty_prix'        => array( 'type' => 'string',  'default' => '' ),
				'couverty_image_url'   => array( 'type' => 'string',  'default' => '' ),
				'couverty_vegetarien'  => array( 'type' => 'boolean', 'default' => false ),
				'couverty_vegan'       => array( 'type' => 'boolean', 'default' => false ),
				'couverty_sans_gluten' => array( 'type' => 'boolean', 'default' => false ),
				'couverty_allergenes'  => array( 'type' => 'string',  'default' => '' ),
				'couverty_external_id' => array( 'type' => 'string',  'default' => '' ),
				'couverty_ordre'       => array( 'type' => 'integer', 'default' => 0 ),
			),
			'couverty_boisson' => array(
				'couverty_prix'        => array( 'type' => 'string',  'default' => '' ),
				'couverty_volume'      => array( 'type' => 'string',  'default' => '' ),
				'couverty_region'      => array( 'type' => 'string',  'default' => '' ),
				'couverty_annee'       => array( 'type' => 'integer', 'default' => 0 ),
				'couverty_external_id' => array( 'type' => 'string',  'default' => '' ),
				'couverty_ordre'       => array( 'type' => 'integer', 'default' => 0 ),
			),
			'couverty_menu_jour' => array(
				'couverty_jour'        => array( 'type' => 'integer', 'default' => 0 ),
				'couverty_jour_label'  => array( 'type' => 'string',  'default' => '' ),
				'couverty_entree'      => array( 'type' => 'string',  'default' => '' ),
				'couverty_dessert'     => array( 'type' => 'string',  'default' => '' ),
				'couverty_prix'        => array( 'type' => 'string',  'default' => '' ),
				'couverty_external_id' => array( 'type' => 'string',  'default' => '' ),
			),
		);

		foreach ( $fields as $post_type => $metas ) {
			foreach ( $metas as $key => $config ) {
				register_post_meta( $post_type, $key, array(
					'type'         => $config['type'],
					'single'       => true,
					'default'      => $config['default'],
					'show_in_rest' => true,
				) );
			}
		}
	}

	// ─── Sync ───────────────────────────────────────────────────────

	/**
	 * Sync all data from the Couverty API
	 *
	 * @return array { success: bool, error?: string, counts?: array }
	 */
	public function sync( $force = false ) {
		$settings = Couverty::get_settings();

		if ( empty( $settings['api_key'] ) ) {
			$this->save_sync_status( false, __( 'API key not configured', 'couverty' ) );
			return array( 'success' => false, 'error' => __( 'API key not configured', 'couverty' ) );
		}

		$api = Couverty::get_instance()->get_api();

		// Smart polling: check if menu data has changed before doing a full sync.
		if ( ! $force ) {
			$restaurant = $api->get_restaurant_info();
			if ( is_array( $restaurant ) ) {
				update_option( 'couverty_restaurant_data', $restaurant, false );
			}

			$remote_updated = isset( $restaurant['menuUpdatedAt'] ) ? $restaurant['menuUpdatedAt'] : null;
			$local_updated  = get_option( 'couverty_menu_updated_at', '' );

			if ( $remote_updated && $remote_updated === $local_updated ) {
				// Data hasn't changed — skip full sync.
				$this->save_sync_status( true, __( 'No changes detected — sync skipped', 'couverty' ) );
				update_option( 'couverty_last_sync', current_time( 'mysql' ), false );
				return array( 'success' => true, 'skipped' => true );
			}
		}

		$api->clear_cache();

		// Single API call for all menu data.
		$menu = $api->get_menu( 'all' );

		if ( ! is_array( $menu ) ) {
			$this->save_sync_status( false, __( 'API returned no data — existing data preserved', 'couverty' ) );
			return array( 'success' => false, 'error' => __( 'API returned no data', 'couverty' ) );
		}

		$counts = array( 'plats' => 0, 'boissons' => 0, 'menus' => 0 );

		if ( isset( $menu['plats'] ) ) {
			$counts['plats'] = $this->sync_plats( $menu['plats'] );
		}
		if ( isset( $menu['boissons'] ) ) {
			$counts['boissons'] = $this->sync_boissons( $menu['boissons'] );
		}
		if ( isset( $menu['menuSemaine'] ) ) {
			$counts['menus'] = $this->sync_menu_du_jour( $menu['menuSemaine'] );
		}

		// Restaurant info → wp_options (fetch fresh if forced, reuse if already fetched).
		if ( $force ) {
			$restaurant = $api->get_restaurant_info();
			if ( is_array( $restaurant ) ) {
				update_option( 'couverty_restaurant_data', $restaurant, false );
			}
		}

		// Store remote menuUpdatedAt so next cron can compare.
		$restaurant = get_option( 'couverty_restaurant_data', array() );
		if ( isset( $restaurant['menuUpdatedAt'] ) ) {
			update_option( 'couverty_menu_updated_at', $restaurant['menuUpdatedAt'], false );
		}

		update_option( 'couverty_last_sync', current_time( 'mysql' ), false );
		$this->save_sync_status( true, '', $counts );

		return array( 'success' => true, 'counts' => $counts );
	}

	/**
	 * Save sync status for admin display
	 *
	 * @param bool   $success Whether sync succeeded.
	 * @param string $error   Error message if failed.
	 * @param array  $counts  Synced item counts.
	 */
	private function save_sync_status( $success, $error = '', $counts = array() ) {
		update_option( 'couverty_sync_status', array(
			'success' => $success,
			'error'   => $error,
			'counts'  => $counts,
			'time'    => current_time( 'mysql' ),
		), false );
	}

	/**
	 * Sync plats into couverty_plat CPT
	 *
	 * @param array $data Plats data with 'categories' key.
	 * @return int Number of synced items.
	 */
	private function sync_plats( $data ) {
		if ( ! isset( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
			return 0;
		}

		$synced_ids = array();

		foreach ( $data['categories'] as $category ) {
			$term_id  = $this->get_or_create_term( $category, 'couverty_cat_plat' );
			$cat_plats = isset( $category['plats'] ) ? $category['plats'] : array();

			foreach ( $cat_plats as $plat ) {
				$prix = $this->resolve_prix( $plat );

				$post_id = $this->upsert(
					'couverty_plat',
					isset( $plat['id'] ) ? $plat['id'] : '',
					array(
						'post_title'   => isset( $plat['nom'] ) ? $plat['nom'] : '',
						'post_content' => isset( $plat['description'] ) ? $plat['description'] : '',
						'post_status'  => 'publish',
						'menu_order'   => isset( $plat['ordre'] ) ? (int) $plat['ordre'] : 0,
					),
					array(
						'couverty_prix'       => $prix,
						'couverty_image_url'  => isset( $plat['imageUrl'] ) ? $plat['imageUrl'] : '',
						'couverty_vegetarien'     => ! empty( $plat['vegetarien'] ) ? '1' : '0',
						'couverty_vegan'          => ! empty( $plat['vegan'] ) ? '1' : '0',
						'couverty_sans_gluten'    => ! empty( $plat['sansGluten'] ) ? '1' : '0',
						'couverty_allergenes'     => isset( $plat['allergenes'] ) && is_array( $plat['allergenes'] )
							? implode( ', ', $plat['allergenes'] ) : '',
						'couverty_ordre'          => isset( $plat['ordre'] ) ? (int) $plat['ordre'] : 0,
					)
				);

				if ( $post_id && $term_id ) {
					wp_set_object_terms( $post_id, array( (int) $term_id ), 'couverty_cat_plat' );
				}

				if ( $post_id ) {
					$synced_ids[] = $post_id;
				}
			}
		}

		// Only cleanup if we actually synced data — avoid wiping everything on empty API response.
		if ( ! empty( $synced_ids ) ) {
			$this->cleanup( 'couverty_plat', $synced_ids );
		}

		return count( $synced_ids );
	}

	/**
	 * Sync boissons into couverty_boisson CPT
	 *
	 * @param array $data Boissons data with 'categories' key.
	 * @return int Number of synced items.
	 */
	private function sync_boissons( $data ) {
		if ( ! isset( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
			return 0;
		}

		$synced_ids = array();

		foreach ( $data['categories'] as $category ) {
			$term_id      = $this->get_or_create_term( $category, 'couverty_cat_boisson' );
			$cat_boissons = isset( $category['boissons'] ) ? $category['boissons'] : array();

			foreach ( $cat_boissons as $boisson ) {
				$prix = $this->resolve_prix( $boisson );

				$post_id = $this->upsert(
					'couverty_boisson',
					isset( $boisson['id'] ) ? $boisson['id'] : '',
					array(
						'post_title'   => isset( $boisson['nom'] ) ? $boisson['nom'] : '',
						'post_content' => isset( $boisson['description'] ) ? $boisson['description'] : '',
						'post_status'  => 'publish',
						'menu_order'   => isset( $boisson['ordre'] ) ? (int) $boisson['ordre'] : 0,
					),
					array(
						'couverty_prix'   => $prix,
						'couverty_volume' => isset( $boisson['volume'] ) ? $boisson['volume'] : '',
						'couverty_region'         => isset( $boisson['region'] ) ? $boisson['region'] : '',
						'couverty_annee'          => isset( $boisson['annee'] ) ? (int) $boisson['annee'] : 0,
						'couverty_ordre'          => isset( $boisson['ordre'] ) ? (int) $boisson['ordre'] : 0,
					)
				);

				if ( $post_id && $term_id ) {
					wp_set_object_terms( $post_id, array( (int) $term_id ), 'couverty_cat_boisson' );
				}

				if ( $post_id ) {
					$synced_ids[] = $post_id;
				}
			}
		}

		if ( ! empty( $synced_ids ) ) {
			$this->cleanup( 'couverty_boisson', $synced_ids );
		}

		return count( $synced_ids );
	}

	/**
	 * Sync menu du jour into couverty_menu_jour CPT
	 *
	 * @param array $data Menu semaine data with 'menus' key.
	 * @return int Number of synced items.
	 */
	private function sync_menu_du_jour( $data ) {
		if ( ! isset( $data['menus'] ) || ! is_array( $data['menus'] ) ) {
			return 0;
		}

		$synced_ids = array();
		$day_names  = $this->get_day_names();

		foreach ( $data['menus'] as $menu ) {
			$jour       = isset( $menu['jour'] ) ? (int) $menu['jour'] : 0;
			$jour_label = isset( $day_names[ $jour ] ) ? $day_names[ $jour ] : (string) $jour;
			$prix       = $this->resolve_prix( $menu );

			$post_id = $this->upsert(
				'couverty_menu_jour',
				isset( $menu['id'] ) ? $menu['id'] : '',
				array(
					'post_title'   => $jour_label,
					'post_content' => isset( $menu['plat'] ) ? $menu['plat'] : '',
					'post_status'  => 'publish',
					'menu_order'   => $jour,
				),
				array(
					'couverty_jour'       => $jour,
					'couverty_jour_label' => $jour_label,
					'couverty_entree'     => isset( $menu['entree'] ) ? $menu['entree'] : '',
					'couverty_dessert'    => isset( $menu['dessert'] ) ? $menu['dessert'] : '',
					'couverty_prix'       => $prix,
				)
			);

			if ( $post_id ) {
				$synced_ids[] = $post_id;
			}
		}

		if ( ! empty( $synced_ids ) ) {
			$this->cleanup( 'couverty_menu_jour', $synced_ids );
		}

		return count( $synced_ids );
	}

	// ─── Helpers ────────────────────────────────────────────────────

	/**
	 * Resolve formatted price string from API item data.
	 * Uses prixAffichage if provided, otherwise builds from prix/prix2/prix3.
	 *
	 * @param array $item API item with prix, prix2, prix3, prixAffichage keys.
	 * @return string e.g. "CHF 18.-" or "CHF 18.- / CHF 28.-"
	 */
	private function resolve_prix( $item ) {
		if ( ! empty( $item['prixAffichage'] ) ) {
			return $item['prixAffichage'];
		}

		$prix  = isset( $item['prix'] ) ? (float) $item['prix'] : 0;
		if ( $prix <= 0 ) {
			return '';
		}

		$parts = array( $this->format_chf( $prix ) );

		$prix2 = isset( $item['prix2'] ) ? (float) $item['prix2'] : 0;
		if ( $prix2 > 0 ) {
			$parts[] = $this->format_chf( $prix2 );
		}

		$prix3 = isset( $item['prix3'] ) ? (float) $item['prix3'] : 0;
		if ( $prix3 > 0 ) {
			$parts[] = $this->format_chf( $prix3 );
		}

		return implode( ' / ', $parts );
	}

	/**
	 * Format amount in Swiss CHF notation.
	 *
	 * @param float $amount Price amount.
	 * @return string e.g. "CHF 18.-" or "CHF 18.50"
	 */
	private function format_chf( $amount ) {
		if ( floor( $amount ) == $amount ) {
			return 'CHF ' . number_format( $amount, 0, '.', '' ) . '.-';
		}
		return 'CHF ' . number_format( $amount, 2, '.', '' );
	}

	/**
	 * Insert or update a post by external ID
	 *
	 * @param string $post_type   Post type.
	 * @param string $external_id Couverty ID.
	 * @param array  $args        wp_insert_post args.
	 * @param array  $meta        Meta key/value pairs.
	 * @return int Post ID or 0 on failure.
	 */
	private function upsert( $post_type, $external_id, $args, $meta = array() ) {
		$args['post_type'] = $post_type;

		// Find existing post by external ID.
		$existing = get_posts( array(
			'post_type'      => $post_type,
			'meta_key'       => 'couverty_external_id',
			'meta_value'     => $external_id,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) {
			$args['ID'] = $existing[0];
			wp_update_post( $args );
			$post_id = $existing[0];
		} else {
			$post_id = wp_insert_post( $args );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		// Always store the external ID.
		$meta['couverty_external_id'] = $external_id;

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}

	/**
	 * Delete posts that were not part of the latest sync
	 *
	 * @param string $post_type Post type.
	 * @param array  $keep_ids  Post IDs to keep.
	 */
	private function cleanup( $post_type, $keep_ids ) {
		$all = get_posts( array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );

		foreach ( $all as $post_id ) {
			if ( ! in_array( $post_id, $keep_ids, true ) ) {
				wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Get or create a taxonomy term from a category array
	 *
	 * @param array  $category Category data with 'nom' and optional 'description'.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int Term ID or 0.
	 */
	private function get_or_create_term( $category, $taxonomy ) {
		$name = isset( $category['nom'] ) ? $category['nom'] : '';

		if ( empty( $name ) ) {
			return 0;
		}

		$term = term_exists( $name, $taxonomy );

		if ( ! $term ) {
			$term = wp_insert_term( $name, $taxonomy, array(
				'description' => isset( $category['description'] ) ? $category['description'] : '',
			) );
		}

		if ( is_wp_error( $term ) ) {
			return 0;
		}

		return is_array( $term ) ? (int) $term['term_id'] : (int) $term;
	}

	/**
	 * Day number to French name mapping
	 *
	 * @return array
	 */
	private function get_day_names() {
		return array(
			0 => __( 'Menu de la semaine', 'couverty' ),
			1 => __( 'Lundi', 'couverty' ),
			2 => __( 'Mardi', 'couverty' ),
			3 => __( 'Mercredi', 'couverty' ),
			4 => __( 'Jeudi', 'couverty' ),
			5 => __( 'Vendredi', 'couverty' ),
			6 => __( 'Samedi', 'couverty' ),
			7 => __( 'Dimanche', 'couverty' ),
		);
	}

	/**
	 * Delete all synced CPT data (for uninstall)
	 */
	public static function delete_all_data() {
		$types = array( 'couverty_plat', 'couverty_boisson', 'couverty_menu_jour' );

		foreach ( $types as $type ) {
			$posts = get_posts( array(
				'post_type'      => $type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			) );
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// Delete taxonomy terms.
		$taxonomies = array( 'couverty_cat_plat', 'couverty_cat_boisson' );
		foreach ( $taxonomies as $tax ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids' ) );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term_id ) {
					wp_delete_term( $term_id, $tax );
				}
			}
		}

		delete_option( 'couverty_restaurant_data' );
		delete_option( 'couverty_last_sync' );
	}
}

<?php
/**
 * Couverty starter content — example pages and block patterns
 *
 * Gives a restaurant a working set of pages in one click instead of asking them
 * to build each one from an empty editor. Pages are created as drafts: the site
 * owner reviews and publishes them, so nothing appears publicly by surprise.
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Pages {

	/**
	 * Option holding the pages we created, as slug => post ID.
	 */
	const OPTION = 'couverty_example_pages';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * The pages we offer to create, in the order they are presented.
	 *
	 * @return array Slug => [ title, block, description ].
	 */
	public static function definitions() {
		return array(
			'carte' => array(
				'title'       => __( 'Notre carte', 'couverty' ),
				'heading'     => __( 'Notre carte', 'couverty' ),
				'block'       => 'couverty/menu',
				'attributes'  => array( 'layout' => 'list' ),
				'description' => __( 'La carte des plats, par catégorie.', 'couverty' ),
			),
			'menu-du-jour' => array(
				'title'       => __( 'Menu du jour', 'couverty' ),
				'heading'     => __( 'Menu du jour', 'couverty' ),
				'block'       => 'couverty/menu-du-jour',
				'attributes'  => array(),
				'description' => __( 'Le menu du jour ou de la semaine.', 'couverty' ),
			),
			'boissons' => array(
				'title'       => __( 'Nos boissons', 'couverty' ),
				'heading'     => __( 'Nos boissons', 'couverty' ),
				'block'       => 'couverty/boissons',
				'attributes'  => array( 'layout' => 'list' ),
				'description' => __( 'La carte des boissons.', 'couverty' ),
			),
			'reservation' => array(
				'title'       => __( 'Réserver une table', 'couverty' ),
				'heading'     => __( 'Réserver une table', 'couverty' ),
				'block'       => 'couverty/reservation',
				'attributes'  => array(),
				'description' => __( 'Le formulaire de réservation en ligne.', 'couverty' ),
			),
		);
	}

	/**
	 * Build the block markup for one page.
	 *
	 * @param array $page Page definition.
	 * @return string Serialized block content.
	 */
	private static function build_content( $page ) {
		$attributes = empty( $page['attributes'] )
			? ''
			: ' ' . wp_json_encode( $page['attributes'] );

		return sprintf(
			"<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">%s</h1>\n<!-- /wp:heading -->\n\n<!-- wp:%s%s /-->",
			esc_html( $page['heading'] ),
			$page['block'],
			$attributes
		);
	}

	/**
	 * Current state of each example page.
	 *
	 * @return array Slug => [ title, description, exists, id, edit_url, view_url, status ].
	 */
	public static function get_status() {
		$created = get_option( self::OPTION, array() );
		$status  = array();

		foreach ( self::definitions() as $slug => $page ) {
			$post_id = isset( $created[ $slug ] ) ? (int) $created[ $slug ] : 0;
			$post    = $post_id ? get_post( $post_id ) : null;

			// The owner may have deleted or trashed the page since; treat it as gone
			// so it can be created again.
			$exists = $post instanceof WP_Post && 'trash' !== $post->post_status;

			$status[ $slug ] = array(
				'title'       => $page['title'],
				'description' => $page['description'],
				'exists'      => $exists,
				'id'          => $exists ? $post_id : 0,
				'status'      => $exists ? $post->post_status : '',
				'edit_url'    => $exists ? get_edit_post_link( $post_id, 'raw' ) : '',
				'view_url'    => $exists ? get_permalink( $post_id ) : '',
			);
		}

		return $status;
	}

	/**
	 * Create the example pages that do not exist yet.
	 *
	 * Existing pages are never overwritten — the owner may have customised them.
	 *
	 * @return array { created: int, skipped: int, pages: array }
	 */
	public static function create() {
		$created = get_option( self::OPTION, array() );
		$status  = self::get_status();
		$count   = 0;
		$skipped = 0;

		foreach ( self::definitions() as $slug => $page ) {
			if ( $status[ $slug ]['exists'] ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => self::build_content( $page ),
				'post_status'  => 'draft',
				'post_type'    => 'page',
			), true );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$created[ $slug ] = $post_id;
			$count++;
		}

		update_option( self::OPTION, $created, false );

		return array(
			'created' => $count,
			'skipped' => $skipped,
			'pages'   => self::get_status(),
		);
	}

	/**
	 * Register block patterns so the same layouts can be inserted manually,
	 * from any page, without going through the example pages.
	 */
	public function register_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( 'couverty', array(
				'label' => __( 'Couverty', 'couverty' ),
			) );
		}

		foreach ( self::definitions() as $slug => $page ) {
			register_block_pattern( 'couverty/' . $slug, array(
				'title'       => $page['title'],
				'description' => $page['description'],
				'categories'  => array( 'couverty' ),
				'content'     => self::build_content( $page ),
			) );
		}
	}
}

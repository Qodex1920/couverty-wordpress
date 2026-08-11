<?php
/**
 * Couverty starter content — example pages and block patterns
 *
 * THESIS — Une carte de restaurant dont la tenue vient du rythme et de la
 * hiérarchie, jamais d'une couche décorative posée sur le thème du client.
 * Refuse la page-plaquette : bandeau dupliqué, grille de cartes, pavés de texte
 * promotionnel entre le visiteur et les plats.
 *
 * OWN-WORLD — Aucune police imposée, et l'encre vient du thème : la page
 * emprunte l'une et l'autre au site. Ne restent en propre que l'accent des noms
 * de plats et la couleur des filets, surchargeables depuis :root (voir
 * readme.txt). Le contenu ne contient que ce qui est du contenu — le titre et
 * l'image d'ouverture appartiennent au thème, qui les rend déjà. Reconnaissable,
 * même vidée, par sa respiration : une phrase d'accroche à la mesure de lecture,
 * la carte, puis une unique zone d'action.
 *
 * STORY — Le visiteur voit une vraie table, parcourt les plats sans friction,
 * et trouve le bouton de réservation là où l'envie naît : après la lecture.
 *
 * FIRST VIEWPORT — Ce que rend le thème : son titre, et l'image mise en avant
 * quand le propriétaire en a défini une. Puis, dans le contenu, une seule
 * phrase avant la carte. L'action primaire n'est jamais dans le premier écran
 * d'une carte.
 *
 * FORM — Extension du monde Couverty déjà en production (couverty-public.css),
 * pas de nouvelle identité : le rendu final appartient au thème du client. Les
 * règles de densité restent réservées aux pages créées ici, pour qu'un
 * shortcode posé dans une mise en page existante ne bouge pas d'un pixel.
 *
 * Les pages sont créées en brouillon : le propriétaire relit avant publication.
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Pages {

	/**
	 * Option holding the pages we created, as slug => post ID.
	 */
	const OPTION = 'couverty_example_pages';

	/**
	 * Placeholder swapped for the real booking page permalink once every page
	 * exists, so the closing button actually leads somewhere.
	 *
	 * Shaped like a URL on purpose: esc_url() prefixes "http://" to any string
	 * without a scheme, which would leave "http://http://…" after replacement.
	 */
	const BOOKING_URL_TOKEN = 'https://couverty.invalid/booking-page';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * The pages we offer to create, in the order they are presented.
	 *
	 * `hero` marks the pages that deserve a featured image: the theme renders
	 * it as the opening band, so the plugin only has to say so in the admin.
	 *
	 * `slug` is translated, the array key is not: the key identifies the page in
	 * the option for the life of the site, so it has to stay stable, while the
	 * permalink should read in the language the site is written in.
	 *
	 * The intros say only what the plugin can guarantee. Earlier wording put
	 * claims in the owner's mouth — daily buying, regional winemakers, weekday
	 * lunch service — that were often simply untrue of the restaurant about to
	 * publish them.
	 *
	 * @return array Key => definition.
	 */
	public static function definitions() {
		return array(
			'carte' => array(
				'title'       => __( 'Notre carte', 'couverty' ),
				'slug'        => _x( 'carte', 'permalien de la page', 'couverty' ),
				'description' => __( 'La carte des plats, par catégorie.', 'couverty' ),
				'intro'       => __( 'Voici notre carte, mise à jour au fil des saisons. Pour toute question sur un plat ou sur un allergène, demandez-nous.', 'couverty' ),
				'block'       => 'couverty/menu',
				'attributes'  => array( 'layout' => 'list' ),
				'hero'        => true,
				'cta'         => true,
			),
			'menu-du-jour' => array(
				'title'       => __( 'Menu du jour', 'couverty' ),
				'slug'        => _x( 'menu-du-jour', 'permalien de la page', 'couverty' ),
				'description' => __( 'Le menu du jour ou de la semaine.', 'couverty' ),
				'intro'       => __( 'Notre menu du moment. Cette page suit ce que nous servons aujourd\'hui, sans que nous ayons à la retoucher.', 'couverty' ),
				'block'       => 'couverty/menu-du-jour',
				'attributes'  => array(),
				'hero'        => false,
				'cta'         => true,
			),
			'boissons' => array(
				'title'       => __( 'Nos boissons', 'couverty' ),
				'slug'        => _x( 'boissons', 'permalien de la page', 'couverty' ),
				'description' => __( 'La carte des boissons.', 'couverty' ),
				'intro'       => __( 'Notre carte des boissons, tenue à jour en même temps que celle des plats.', 'couverty' ),
				'block'       => 'couverty/boissons',
				'attributes'  => array( 'layout' => 'list' ),
				'hero'        => false,
				'cta'         => true,
			),
			'reservation' => array(
				'title'       => __( 'Réserver une table', 'couverty' ),
				'slug'        => _x( 'reservation', 'permalien de la page', 'couverty' ),
				'description' => __( 'Le formulaire de réservation en ligne.', 'couverty' ),
				'intro'       => __( 'Choisissez une date et le nombre de convives. Vous recevez un e-mail dès que votre demande est enregistrée.', 'couverty' ),
				'block'       => 'couverty/reservation',
				'attributes'  => array(),
				'hero'        => true,
				'cta'         => false,
			),
		);
	}

	// ─── Block composition ──────────────────────────────────────────

	/**
	 * Serialize one block, with optional inner markup.
	 *
	 * @param string $name       Block name.
	 * @param array  $attributes Block attributes.
	 * @param string $inner      Inner markup, empty for a self-closing block.
	 * @return string
	 */
	private static function block( $name, $attributes = array(), $inner = '' ) {
		$json = empty( $attributes ) ? '' : ' ' . wp_json_encode( $attributes );

		if ( '' === $inner ) {
			return sprintf( '<!-- wp:%s%s /-->', $name, $json );
		}

		return sprintf( "<!-- wp:%s%s -->\n%s\n<!-- /wp:%s -->", $name, $json, $inner, $name );
	}

	/**
	 * The standing invitation closing a menu, where the appetite is.
	 *
	 * @return string
	 */
	private static function call_to_action() {
		// Espace fine insécable avant le point d'interrogation : sans elle, le
		// signe part seul à la ligne quand la question tombe en fin de mesure.
		$heading = self::block(
			'heading',
			array( 'level' => 2 ),
			sprintf( '<h2 class="wp-block-heading">%s</h2>', esc_html__( 'Envie de passer à table ?', 'couverty' ) )
		);

		$text = self::block(
			'paragraph',
			array(),
			sprintf( '<p>%s</p>', esc_html__( 'Réservez en quelques secondes, sans créer de compte.', 'couverty' ) )
		);

		$button = self::block(
			'button',
			array(),
			sprintf(
				'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
				esc_url( self::BOOKING_URL_TOKEN ),
				esc_html__( 'Réserver une table', 'couverty' )
			)
		);

		$buttons = self::block(
			'buttons',
			array(),
			'<div class="wp-block-buttons">' . "\n" . $button . "\n" . '</div>'
		);

		$inner = '<div class="wp-block-group couverty-page__cta">' . "\n"
			. $heading . "\n\n" . $text . "\n\n" . $buttons . "\n"
			. '</div>';

		return self::block(
			'group',
			array( 'className' => 'couverty-page__cta' ),
			$inner
		);
	}

	/**
	 * Build the block markup for one page.
	 *
	 * No heading and no cover: a block theme's page template already renders
	 * the title and the featured image, and repeating them here would show the
	 * visitor the same thing twice.
	 *
	 * @param array $page Page definition.
	 * @return string Serialized block content.
	 */
	private static function build_content( $page ) {
		$parts = array();

		if ( ! empty( $page['intro'] ) ) {
			$parts[] = self::block(
				'paragraph',
				array( 'className' => 'couverty-page__intro' ),
				sprintf( '<p class="couverty-page__intro">%s</p>', esc_html( $page['intro'] ) )
			);
		}

		// The Couverty block is wrapped rather than given a className: the group is
		// what the page rules hang off, and it stays editable in the editor.
		//
		// No layout attribute on purpose. A constrained group caps itself at the
		// global content size, which a classic theme does not apply to the intro
		// next to it — the card then sat narrower than the paragraph above it,
		// with two different left edges. In flow, the group takes the measure the
		// theme gives the content, whichever kind of theme it is.
		$parts[] = self::block(
			'group',
			array( 'className' => 'couverty-page__carte' ),
			'<div class="wp-block-group couverty-page__carte">' . "\n"
				. self::block( $page['block'], $page['attributes'] ) . "\n"
				. '</div>'
		);

		if ( ! empty( $page['cta'] ) ) {
			$parts[] = self::call_to_action();
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Slug of the booking page, used when no page exists to link to yet.
	 *
	 * @return string
	 */
	private static function booking_slug() {
		$definitions = self::definitions();

		return $definitions['reservation']['slug'];
	}

	// ─── Page creation ──────────────────────────────────────────────

	/**
	 * Current state of each example page.
	 *
	 * @return array Slug => [ title, description, exists, id, status, edit_url, view_url, needs_image ].
	 */
	public static function get_status() {
		$created = get_option( self::OPTION, array() );
		$status  = array();

		foreach ( self::definitions() as $key => $page ) {
			$post_id = isset( $created[ $key ] ) ? (int) $created[ $key ] : 0;
			$post    = $post_id ? get_post( $post_id ) : null;

			// The owner may have deleted or trashed the page since; treat it as gone
			// so it can be created again.
			$exists = $post instanceof WP_Post && 'trash' !== $post->post_status;

			$status[ $key ] = array(
				'title'       => $page['title'],
				'description' => $page['description'],
				'exists'      => $exists,
				'id'          => $exists ? $post_id : 0,
				'status'      => $exists ? $post->post_status : '',
				'edit_url'    => $exists ? get_edit_post_link( $post_id, 'raw' ) : '',
				'view_url'    => $exists ? get_permalink( $post_id ) : '',
				// The theme renders the featured image as the opening band.
				'needs_image' => $exists && ! empty( $page['hero'] ) && ! has_post_thumbnail( $post_id ),
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

		foreach ( self::definitions() as $key => $page ) {
			if ( $status[ $key ]['exists'] ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_title'   => $page['title'],
				'post_name'    => $page['slug'],
				'post_content' => self::build_content( $page ),
				'post_status'  => 'draft',
				'post_type'    => 'page',
			), true );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$created[ $key ] = $post_id;
			$count++;
		}

		update_option( self::OPTION, $created, false );

		self::resolve_booking_links( $created );

		return array(
			'created' => $count,
			'skipped' => $skipped,
			'pages'   => self::get_status(),
		);
	}

	/**
	 * Point the closing buttons at the booking page that now exists.
	 *
	 * The placeholder survives in the pattern version, where there is no page
	 * to link to yet; there it degrades to a plain /reservation path.
	 *
	 * @param array $created Slug => post ID.
	 */
	private static function resolve_booking_links( $created ) {
		$booking_id = isset( $created['reservation'] ) ? (int) $created['reservation'] : 0;

		// get_page_link() sur un brouillon renvoie « ?page_id=12 » : les boutons
		// resteraient figés sur cette forme une fois les pages publiées, avec une
		// redirection canonique à chaque clic. Le troisième argument demande le
		// permalien définitif, celui que la page portera après publication.
		$url = $booking_id
			? get_page_link( $booking_id, false, true )
			: home_url( '/' . self::booking_slug() );

		if ( ! $url ) {
			return;
		}

		foreach ( $created as $post_id ) {
			$post = get_post( (int) $post_id );

			if ( ! $post instanceof WP_Post || false === strpos( $post->post_content, self::BOOKING_URL_TOKEN ) ) {
				continue;
			}

			wp_update_post( array(
				'ID'           => $post->ID,
				'post_content' => str_replace( self::BOOKING_URL_TOKEN, esc_url_raw( $url ), $post->post_content ),
			) );
		}
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

		// Le slug de la page de réservation, sans interroger la base : c'est celui
		// que create() lui donne, donc le lien tombe juste dès qu'elle existe.
		$fallback = home_url( '/' . self::booking_slug() );

		foreach ( self::definitions() as $key => $page ) {
			register_block_pattern( 'couverty/' . $key, array(
				'title'       => $page['title'],
				'description' => $page['description'],
				'categories'  => array( 'couverty' ),
				'content'     => str_replace(
					self::BOOKING_URL_TOKEN,
					esc_url( $fallback ),
					self::build_content( $page )
				),
			) );
		}
	}
}

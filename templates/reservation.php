<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reservation iframe template
 *
 * The iframe itself is built by assets/js/couverty-reservation.js, which also
 * validates the origin of the resize messages sent by the embedded widget.
 *
 * @var array $atts Shortcode attributes (height, appearance, radius)
 */

$height     = isset( $atts['height'] ) ? (int) $atts['height'] : 600;
$appearance = isset( $atts['appearance'] ) ? sanitize_text_field( $atts['appearance'] ) : 'card';
$radius     = isset( $atts['radius'] ) ? sanitize_text_field( $atts['radius'] ) : 'lg';

$settings = Couverty::get_settings();
$base_url = rtrim( isset( $settings['base_url'] ) ? $settings['base_url'] : 'https://couverty.ch', '/' );
$slug     = isset( $settings['slug'] ) ? $settings['slug'] : '';

// The Couverty logo is served in the site's HTML, outside the noindex iframe: a
// link inside the iframe counts for couverty.ch, not for this site.
// couverty-reservation.js lays it over the widget's corner, where the widget
// would draw its own (`credit=host` leaves that spot empty). Same look, but a
// visible, brand-anchored link. Hide it with
// `add_filter( 'couverty_reservation_credit', '__return_false' )`: the widget
// then shows its own logo again.
$show_credit = apply_filters( 'couverty_reservation_credit', true );

$iframe_args = array(
	'appearance' => $appearance,
	'radius'     => $radius,
);
if ( $show_credit ) {
	$iframe_args['credit'] = 'host';
}
$iframe_url = add_query_arg( $iframe_args, $base_url . '/embed/' . rawurlencode( $slug ) );

// Served in clear text for crawlers that do not run JS; couverty-reservation.js
// replaces it with the iframe as soon as the widget mounts.
$reserve_url = $base_url . '/' . rawurlencode( $slug ) . '/reserver';

$logo_url = add_query_arg(
	array(
		'utm_source'   => 'widget',
		'utm_medium'   => 'wordpress',
		'utm_campaign' => 'powered_by',
		'utm_content'  => $slug,
	),
	'https://couverty.ch/'
);

// Origin the widget is allowed to post resize messages from.
$parsed = wp_parse_url( $base_url );
$origin = isset( $parsed['scheme'], $parsed['host'] )
	? $parsed['scheme'] . '://' . $parsed['host'] . ( isset( $parsed['port'] ) ? ':' . $parsed['port'] : '' )
	: $base_url;

wp_enqueue_script( 'couverty-reservation' );
?>

<div
	class="couverty-reservation"
	data-couverty-embed="<?php echo esc_url( $iframe_url ); ?>"
	data-couverty-origin="<?php echo esc_attr( $origin ); ?>"
	data-couverty-height="<?php echo esc_attr( (string) $height ); ?>"
	data-couverty-title="<?php esc_attr_e( 'Réservation en ligne', 'couverty' ); ?>"
><a href="<?php echo esc_url( $reserve_url ); ?>"><?php
	printf(
		/* translators: %s: site name. */
		esc_html__( 'Réserver une table chez %s', 'couverty' ),
		esc_html( get_bloginfo( 'name' ) )
	);
?></a>
<?php if ( $show_credit ) : ?>
<a class="couverty-logo" href="<?php echo esc_url( $logo_url ); ?>" target="_blank" rel="noopener" style="display:inline-block;line-height:0"><img src="<?php echo esc_url( $base_url . '/logo/logo-couverty.png' ); ?>" alt="Couverty" width="76" height="18" style="display:block;width:76px;height:auto;max-width:none;margin:0;padding:0;border:0;box-shadow:none"></a>
<?php endif; ?>
</div>

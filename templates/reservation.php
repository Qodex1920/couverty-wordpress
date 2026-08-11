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

$iframe_url = add_query_arg(
	array(
		'appearance' => $appearance,
		'radius'     => $radius,
	),
	$base_url . '/embed/' . rawurlencode( $slug )
);

// Served in clear text for crawlers that do not run JS; couverty-reservation.js
// replaces it with the iframe as soon as the widget mounts.
$reserve_url = $base_url . '/' . rawurlencode( $slug ) . '/reserver';

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
		esc_html__( 'Réserver une table — %s', 'couverty' ),
		esc_html( get_bloginfo( 'name' ) )
	);
?></a></div>

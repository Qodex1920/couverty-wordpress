<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reservation widget template
 *
 * Same markup as the integration code from Couverty's settings: widget.js
 * (handle `couverty-widget`, see Couverty::register_widget_script()) mounts it.
 *
 * @var array $atts Shortcode attributes, checked by render_reservation() (height, appearance, radius)
 */

// Room kept for the widget before it loads, so the page does not jump; the
// widget then grows to fit its content.
$height = (int) $atts['height'];

$settings = Couverty::get_settings();
$base_url = rtrim( $settings['base_url'], '/' );
$slug     = $settings['slug'];

// The Couverty logo is served in the site's HTML, outside the noindex iframe: a
// link inside the iframe counts for couverty.ch, not for this site.
// widget.js lays it over the widget's corner, where the widget would draw its
// own (`credit=host` leaves that spot empty). Same look, but a visible,
// brand-anchored link. Hide it with
// `add_filter( 'couverty_reservation_credit', '__return_false' )`: the widget
// then shows its own logo again.
$show_credit = apply_filters( 'couverty_reservation_credit', true );

// Served in clear text for crawlers that do not run JS; widget.js replaces it
// with the iframe as soon as the widget mounts.
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

wp_enqueue_script( 'couverty-widget' );
?>

<div
	class="couverty-reservation"
	data-couverty-widget="<?php echo esc_attr( $slug ); ?>"
	<?php if ( $atts['appearance'] ) : ?>data-couverty-appearance="<?php echo esc_attr( $atts['appearance'] ); ?>"<?php endif; ?>
	<?php if ( $atts['radius'] ) : ?>data-couverty-radius="<?php echo esc_attr( $atts['radius'] ); ?>"<?php endif; ?>
	style="position:relative;min-height:<?php echo esc_attr( $height ); ?>px"
><a href="<?php echo esc_url( $reserve_url ); ?>"><?php
	printf(
		/* translators: %s: site name. */
		esc_html__( 'Réserver une table chez %s', 'couverty' ),
		esc_html( get_bloginfo( 'name' ) )
	);
?></a>
<?php if ( $show_credit ) : ?>
<a class="couverty-logo" data-couverty-logo href="<?php echo esc_url( $logo_url ); ?>" target="_blank" rel="noopener" style="display:inline-block;line-height:0"><img src="<?php echo esc_url( $base_url . '/logo/logo-couverty.png' ); ?>" alt="Couverty" width="76" height="18" style="display:block;width:76px;height:auto;max-width:none;margin:0;padding:0;border:0;box-shadow:none"></a>
<?php endif; ?>
</div>

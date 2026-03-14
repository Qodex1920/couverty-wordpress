<?php
defined( 'ABSPATH' ) || exit;
$shortcodes = new Couverty_Shortcodes();
$atts       = [
	'layout'       => $attributes['layout'] ?? 'list',
	'show_prices'  => ( $attributes['showPrices'] ?? true ) ? 'true' : 'false',
	'show_details' => ( $attributes['showDetails'] ?? true ) ? 'true' : 'false',
];
echo $shortcodes->render_boissons( $atts );

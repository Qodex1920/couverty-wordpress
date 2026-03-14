<?php
defined( 'ABSPATH' ) || exit;
$shortcodes = new Couverty_Shortcodes();
$atts       = [
	'layout'         => $attributes['layout'] ?? 'list',
	'show_prices'    => ( $attributes['showPrices'] ?? true ) ? 'true' : 'false',
	'show_images'    => ( $attributes['showImages'] ?? true ) ? 'true' : 'false',
	'show_allergens' => ( $attributes['showAllergens'] ?? true ) ? 'true' : 'false',
];
echo $shortcodes->render_menu( $atts );

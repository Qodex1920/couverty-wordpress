<?php
defined( 'ABSPATH' ) || exit;
$shortcodes = new Couverty_Shortcodes();
$atts       = [
	'show_price' => ( $attributes['showPrice'] ?? true ) ? 'true' : 'false',
];
echo $shortcodes->render_menu_du_jour( $atts );

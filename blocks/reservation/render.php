<?php
defined( 'ABSPATH' ) || exit;
$shortcodes = new Couverty_Shortcodes();
$atts       = [
	'height'     => (int) ( $attributes['height'] ?? 600 ),
	'appearance' => $attributes['appearance'] ?? 'card',
	'radius'     => $attributes['radius'] ?? 'lg',
];
echo $shortcodes->render_reservation( $atts );

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reservation iframe template
 *
 * @var array $atts Shortcode attributes (height, appearance, radius)
 */

$height = isset( $atts['height'] ) ? (int) $atts['height'] : 600;
$appearance = isset( $atts['appearance'] ) ? sanitize_text_field( $atts['appearance'] ) : 'card';
$radius = isset( $atts['radius'] ) ? sanitize_text_field( $atts['radius'] ) : 'lg';

// Get base URL and slug from settings
$settings = Couverty::get_settings();
$base_url = isset( $settings['base_url'] ) ? $settings['base_url'] : 'https://couverty.ch';
$slug = isset( $settings['slug'] ) ? $settings['slug'] : '';

// Generate unique widget ID
$unique_id = wp_unique_id( 'cv-' );

// Build iframe URL
$iframe_url = add_query_arg(
	[
		'appearance' => $appearance,
		'radius' => $radius,
	],
	$base_url . '/embed/' . $slug
);
?>

<div class="couverty-reservation">
	<div id="couverty-reservation-widget-<?php echo esc_attr( $unique_id ); ?>"></div>
	<script>
	(function() {
		var iframe = document.createElement('iframe');
		iframe.src = '<?php echo esc_url( $iframe_url ); ?>';
		iframe.style.width = '100%';
		iframe.style.minHeight = '<?php echo esc_attr( (string) $height ); ?>px';
		iframe.style.border = 'none';
		iframe.style.overflow = 'hidden';
		iframe.setAttribute('scrolling', 'no');
		iframe.setAttribute('frameborder', '0');
		iframe.setAttribute('title', 'Réservation');

		window.addEventListener('message', function(event) {
			if (event.data && event.data.type === 'widget:resize') {
				iframe.style.height = event.data.height + 'px';
			}
		});

		var c = document.getElementById('couverty-reservation-widget-<?php echo esc_attr( $unique_id ); ?>');
		if (c) c.appendChild(iframe);
	})();
	</script>
</div>

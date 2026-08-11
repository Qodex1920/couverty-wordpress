<?php
defined( 'ABSPATH' ) || exit;

/**
 * Boissons template
 *
 * @var array $data Contains 'categories' key with boissons data
 * @var array $atts Shortcode attributes (layout, show_prices, show_details)
 */

$layout = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : 'list';
$show_prices = isset( $atts['show_prices'] ) ? filter_var( $atts['show_prices'], FILTER_VALIDATE_BOOLEAN ) : true;
$show_details = isset( $atts['show_details'] ) ? filter_var( $atts['show_details'], FILTER_VALIDATE_BOOLEAN ) : true;

$categories = isset( $data['categories'] ) ? $data['categories'] : [];

// Même règle que pour la carte des plats : sans titre de section, les catégories
// prennent le niveau qui suit directement le h1 de la page.
$category_level = ! empty( $data['boissonsTitre'] ) ? 3 : 2;
?>

<div class="couverty-boissons couverty-boissons--<?php echo esc_attr( $layout ); ?>">
	<?php if ( ! empty( $data['boissonsTitre'] ) || ! empty( $data['boissonsDescription'] ) ) : ?>
		<div class="couverty-boissons__header">
			<?php if ( ! empty( $data['boissonsTitre'] ) ) : ?>
				<h2 class="couverty-boissons__title"><?php echo esc_html( $data['boissonsTitre'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $data['boissonsDescription'] ) ) : ?>
				<p class="couverty-boissons__description"><?php echo esc_html( $data['boissonsDescription'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $categories as $category ) : ?>
		<div class="couverty-category">
			<?php if ( isset( $category['nom'] ) ) : ?>
				<?php
				printf(
					'<h%1$d class="couverty-category__title">%2$s</h%1$d>',
					(int) $category_level,
					esc_html( $category['nom'] )
				);
				?>
			<?php endif; ?>

			<div class="couverty-category__items">
				<?php foreach ( $category['boissons'] ?? [] as $boisson ) : ?>
					<div class="couverty-boisson">
						<div class="couverty-boisson__header">
							<span class="couverty-boisson__name"><?php echo esc_html( $boisson['nom'] ?? '' ); ?></span>
							<?php if ( $show_prices && isset( $boisson['prix'] ) ) : ?>
								<span class="couverty-boisson__price">
									<?php if ( ! empty( $boisson['prixAffichage'] ) ) : ?>
										<?php echo esc_html( $boisson['prixAffichage'] ); ?>
									<?php else : ?>
										<?php echo esc_html( number_format( (float) $boisson['prix'], 2, '.', '' ) ); ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( $show_details ) : ?>
							<div class="couverty-boisson__details">
								<?php
								$details = [];
								foreach ( [ 'volume', 'region', 'annee' ] as $key ) {
									if ( ! empty( $boisson[ $key ] ) ) {
										$details[] = (string) $boisson[ $key ];
									}
								}
								echo esc_html( implode( ' · ', $details ) );
								?>
							</div>
						<?php endif; ?>

						<?php if ( isset( $boisson['description'] ) && $boisson['description'] ) : ?>
							<p class="couverty-boisson__description"><?php echo esc_html( $boisson['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php if ( ! empty( $data['boissonsNotes'] ) ) : ?>
		<div class="couverty-boissons__notes">
			<?php echo nl2br( esc_html( $data['boissonsNotes'] ) ); ?>
		</div>
	<?php endif; ?>
</div>

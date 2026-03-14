<?php
defined( 'ABSPATH' ) || exit;

/**
 * Menu template (plats)
 *
 * @var array $data Contains 'categories' key with plats data
 * @var array $atts Shortcode attributes (layout, show_prices, show_images, show_allergens)
 */

$layout = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : 'list';
$show_prices = isset( $atts['show_prices'] ) ? filter_var( $atts['show_prices'], FILTER_VALIDATE_BOOLEAN ) : true;
$show_images = isset( $atts['show_images'] ) ? filter_var( $atts['show_images'], FILTER_VALIDATE_BOOLEAN ) : true;
$show_allergens = isset( $atts['show_allergens'] ) ? filter_var( $atts['show_allergens'], FILTER_VALIDATE_BOOLEAN ) : true;

$categories = isset( $data['categories'] ) ? $data['categories'] : [];
$has_images = false;

// Check if any plat has images
if ( $show_images ) {
	foreach ( $categories as $category ) {
		foreach ( $category['plats'] ?? [] as $plat ) {
			if ( isset( $plat['imageUrl'] ) && $plat['imageUrl'] ) {
				$has_images = true;
				break 2;
			}
		}
	}
}
?>

<div class="couverty-menu couverty-menu--<?php echo esc_attr( $layout ); ?>">
	<?php if ( ! empty( $data['menuTitre'] ) || ! empty( $data['menuDescription'] ) ) : ?>
		<div class="couverty-menu__header">
			<?php if ( ! empty( $data['menuTitre'] ) ) : ?>
				<h2 class="couverty-menu__title"><?php echo esc_html( $data['menuTitre'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $data['menuDescription'] ) ) : ?>
				<p class="couverty-menu__description"><?php echo esc_html( $data['menuDescription'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $categories as $category ) : ?>
		<div class="couverty-category">
			<?php if ( isset( $category['nom'] ) ) : ?>
				<h3 class="couverty-category__title"><?php echo esc_html( $category['nom'] ); ?></h3>
			<?php endif; ?>

			<?php if ( isset( $category['description'] ) && $category['description'] ) : ?>
				<p class="couverty-category__description"><?php echo esc_html( $category['description'] ); ?></p>
			<?php endif; ?>

			<div class="couverty-category__items">
				<?php foreach ( $category['plats'] ?? [] as $plat ) : ?>
					<div class="couverty-plat">
						<?php if ( 'grid' === $layout && $show_images && isset( $plat['imageUrl'] ) && $plat['imageUrl'] ) : ?>
							<img
								class="couverty-plat__image"
								src="<?php echo esc_url( $plat['imageUrl'] ); ?>"
								alt="<?php echo esc_attr( $plat['nom'] ?? '' ); ?>"
								loading="lazy"
							>
						<?php endif; ?>

						<div class="couverty-plat__header">
							<div class="couverty-plat__name-wrapper">
								<span class="couverty-plat__name"><?php echo esc_html( $plat['nom'] ?? '' ); ?></span>
								<?php if ( 'list' === $layout && $show_images && isset( $plat['imageUrl'] ) && $plat['imageUrl'] ) : ?>
									<button class="couverty-plat__image-btn" data-src="<?php echo esc_attr( $plat['imageUrl'] ); ?>" data-alt="<?php echo esc_attr( $plat['nom'] ?? '' ); ?>" aria-label="<?php esc_attr_e( 'View image', 'couverty' ); ?>">
										<svg class="couverty-plat__image-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
											<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
											<circle cx="12" cy="12" r="3"></circle>
										</svg>
									</button>
								<?php endif; ?>
							</div>
							<?php if ( $show_prices && isset( $plat['prix'] ) ) : ?>
								<span class="couverty-plat__price">
									<?php if ( ! empty( $plat['prixAffichage'] ) ) : ?>
										<?php echo esc_html( $plat['prixAffichage'] ); ?>
									<?php else : ?>
										<?php echo esc_html( number_format( (float) $plat['prix'], 2, '.', '' ) ); ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( isset( $plat['description'] ) && $plat['description'] ) : ?>
							<p class="couverty-plat__description"><?php echo esc_html( $plat['description'] ); ?></p>
						<?php endif; ?>

						<div class="couverty-plat__tags">
							<?php if ( isset( $plat['vegetarien'] ) && $plat['vegetarien'] ) : ?>
								<span class="couverty-tag couverty-tag--vege">Végétarien</span>
							<?php endif; ?>
							<?php if ( isset( $plat['vegan'] ) && $plat['vegan'] ) : ?>
								<span class="couverty-tag couverty-tag--vegan">Vegan</span>
							<?php endif; ?>
							<?php if ( isset( $plat['sansGluten'] ) && $plat['sansGluten'] ) : ?>
								<span class="couverty-tag couverty-tag--sg">Sans gluten</span>
							<?php endif; ?>
						</div>

						<?php if ( $show_allergens && isset( $plat['allergenes'] ) && is_array( $plat['allergenes'] ) && ! empty( $plat['allergenes'] ) ) : ?>
							<p class="couverty-plat__allergens">Allergènes : <?php echo esc_html( implode( ', ', $plat['allergenes'] ) ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php if ( ! empty( $data['menuNotes'] ) ) : ?>
		<div class="couverty-menu__notes">
			<?php echo nl2br( esc_html( $data['menuNotes'] ) ); ?>
		</div>
	<?php endif; ?>
</div>

<?php if ( $has_images ) : ?>
	<div id="couverty-lightbox" class="couverty-lightbox" style="display:none;">
		<button class="couverty-lightbox__close" aria-label="Close">&times;</button>
		<img class="couverty-lightbox__img" src="" alt="">
	</div>
	<script>
		(function() {
			var lightbox = document.getElementById('couverty-lightbox');
			var closeBtn = lightbox.querySelector('.couverty-lightbox__close');

			document.querySelectorAll('.couverty-plat__image-btn').forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					lightbox.querySelector('img').src = this.dataset.src;
					lightbox.querySelector('img').alt = this.dataset.alt || '';
					lightbox.style.display = 'flex';
				});
			});

			closeBtn.addEventListener('click', function() {
				lightbox.style.display = 'none';
			});

			lightbox.addEventListener('click', function(e) {
				if (e.target === this) {
					this.style.display = 'none';
				}
			});
		})();
	</script>
<?php endif; ?>

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Menu du jour template
 *
 * @var array $data Contains 'config' and 'menus' keys with weekly menu data
 * @var array $atts Shortcode attributes
 */

$config = isset( $data['config'] ) ? $data['config'] : [];
$menus  = isset( $data['menus'] ) ? $data['menus'] : [];

$day_labels = [
	0 => __( 'Semaine', 'couverty' ),
	1 => __( 'Lundi', 'couverty' ),
	2 => __( 'Mardi', 'couverty' ),
	3 => __( 'Mercredi', 'couverty' ),
	4 => __( 'Jeudi', 'couverty' ),
	5 => __( 'Vendredi', 'couverty' ),
	6 => __( 'Samedi', 'couverty' ),
	7 => __( 'Dimanche', 'couverty' ),
];

// Current day (1=Monday to 7=Sunday) in the site's timezone, not the server's.
$current_day = (int) wp_date( 'N' );

$menu_unique_semaine = ! empty( $config['menuUniqueSemaine'] );
$show_prices         = ! empty( $config['afficherPrix'] );

// La page porte déjà un h1, et le titre de la carte est un h2 : le menu du jour
// s'aligne dessus au lieu de démarrer en h3, et les jours suivent en h4. Sans
// titre de section, les jours remontent d'un cran pour ne pas sauter de niveau.
$has_heading = ! empty( $config['titre'] );
$day_level   = $has_heading ? 3 : 2;

/**
 * Render a single day card.
 *
 * @param array  $menu        Menu data (entree, plat, dessert, prix).
 * @param string $day_label   Heading for the card.
 * @param bool   $is_today    Whether to highlight the card.
 * @param bool   $show_prices Whether the price should be displayed.
 * @param int    $day_level   Heading level for the day.
 */
$render_day = static function ( $menu, $day_label, $is_today, $show_prices, $day_level ) {
	$courses = [
		'entree'  => __( 'Entrée', 'couverty' ),
		'plat'    => __( 'Plat', 'couverty' ),
		'dessert' => __( 'Dessert', 'couverty' ),
	];
	?>
	<div class="couverty-menu-jour<?php echo $is_today ? ' couverty-menu-jour--today' : ''; ?>">
		<?php
		printf(
			'<h%1$d class="couverty-menu-jour__day">%2$s</h%1$d>',
			(int) $day_level,
			esc_html( $day_label )
		);
		?>
		<div class="couverty-menu-jour__courses">
			<?php foreach ( $courses as $key => $label ) : ?>
				<?php
				$value = isset( $menu[ $key ] ) ? $menu[ $key ] : '';
				// The main course is always shown, even when empty, to keep the layout stable.
				if ( '' === $value && 'plat' !== $key ) {
					continue;
				}
				?>
				<div class="couverty-menu-jour__course">
					<span class="couverty-menu-jour__label"><?php echo esc_html( $label ); ?></span>
					<span class="couverty-menu-jour__content"><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( $show_prices && ! empty( $menu['prix'] ) ) : ?>
			<div class="couverty-menu-jour__price">
				<?php
				echo esc_html(
					! empty( $menu['prixAffichage'] )
						? $menu['prixAffichage']
						: 'CHF ' . number_format( (float) $menu['prix'], 2, '.', '' )
				);
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php
};
?>

<div class="couverty-menu-du-jour">
	<?php if ( $has_heading ) : ?>
		<h2 class="couverty-menu-du-jour__title"><?php echo esc_html( $config['titre'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $menu_unique_semaine ) : ?>
		<?php
		foreach ( $menus as $menu ) {
			if ( isset( $menu['jour'] ) && 0 === (int) $menu['jour'] ) {
				$render_day( $menu, $day_labels[0], false, $show_prices, $day_level );
				break;
			}
		}
		?>
	<?php else : ?>
		<?php
		foreach ( $menus as $menu ) {
			$jour = isset( $menu['jour'] ) ? (int) $menu['jour'] : 0;
			$render_day(
				$menu,
				isset( $day_labels[ $jour ] ) ? $day_labels[ $jour ] : '',
				$jour === $current_day,
				$show_prices,
				$day_level
			);
		}
		?>
	<?php endif; ?>
</div>

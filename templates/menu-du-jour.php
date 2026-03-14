<?php
defined( 'ABSPATH' ) || exit;

/**
 * Menu du jour template
 *
 * @var array $data Contains 'config' and 'menus' keys with weekly menu data
 * @var array $atts Shortcode attributes
 */

$config = isset( $data['config'] ) ? $data['config'] : [];
$menus = isset( $data['menus'] ) ? $data['menus'] : [];

// Day labels
$day_labels = [
	0 => 'Semaine',
	1 => 'Lundi',
	2 => 'Mardi',
	3 => 'Mercredi',
	4 => 'Jeudi',
	5 => 'Vendredi',
	6 => 'Samedi',
	7 => 'Dimanche',
];

// Get current day (1=Monday to 7=Sunday)
$current_day = (int) date( 'N' );

// Check if single menu for the week
$menu_unique_semaine = isset( $config['menuUniqueSemaine'] ) && $config['menuUniqueSemaine'];
$show_prices = isset( $config['afficherPrix'] ) && $config['afficherPrix'];
?>

<div class="couverty-menu-du-jour">
	<?php if ( isset( $config['titre'] ) && $config['titre'] ) : ?>
		<h3 class="couverty-menu-du-jour__title"><?php echo esc_html( $config['titre'] ); ?></h3>
	<?php endif; ?>

	<?php if ( $menu_unique_semaine ) : ?>
		<?php
		$single_menu = null;
		foreach ( $menus as $menu ) {
			if ( isset( $menu['jour'] ) && 0 === (int) $menu['jour'] ) {
				$single_menu = $menu;
				break;
			}
		}
		?>
		<?php if ( $single_menu ) : ?>
			<div class="couverty-menu-jour">
				<h4 class="couverty-menu-jour__day"><?php echo esc_html( $day_labels[0] ); ?></h4>
				<div class="couverty-menu-jour__courses">
					<?php if ( isset( $single_menu['entree'] ) && $single_menu['entree'] ) : ?>
						<div class="couverty-menu-jour__course">
							<span class="couverty-menu-jour__label">Entrée</span>
							<span class="couverty-menu-jour__content"><?php echo esc_html( $single_menu['entree'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="couverty-menu-jour__course">
						<span class="couverty-menu-jour__label">Plat</span>
						<span class="couverty-menu-jour__content"><?php echo esc_html( $single_menu['plat'] ?? '' ); ?></span>
					</div>
					<?php if ( isset( $single_menu['dessert'] ) && $single_menu['dessert'] ) : ?>
						<div class="couverty-menu-jour__course">
							<span class="couverty-menu-jour__label">Dessert</span>
							<span class="couverty-menu-jour__content"><?php echo esc_html( $single_menu['dessert'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $show_prices && isset( $single_menu['prix'] ) && $single_menu['prix'] ) : ?>
					<div class="couverty-menu-jour__price">CHF <?php echo esc_html( number_format( (float) $single_menu['prix'], 2, '.', '' ) ); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<?php foreach ( $menus as $menu ) : ?>
			<?php
			$jour = isset( $menu['jour'] ) ? (int) $menu['jour'] : 0;
			$is_today = ( $jour === $current_day );
			$class = $is_today ? 'couverty-menu-jour couverty-menu-jour--today' : 'couverty-menu-jour';
			?>
			<div class="<?php echo esc_attr( $class ); ?>">
				<h4 class="couverty-menu-jour__day"><?php echo esc_html( $day_labels[ $jour ] ?? '' ); ?></h4>
				<div class="couverty-menu-jour__courses">
					<?php if ( isset( $menu['entree'] ) && $menu['entree'] ) : ?>
						<div class="couverty-menu-jour__course">
							<span class="couverty-menu-jour__label">Entrée</span>
							<span class="couverty-menu-jour__content"><?php echo esc_html( $menu['entree'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="couverty-menu-jour__course">
						<span class="couverty-menu-jour__label">Plat</span>
						<span class="couverty-menu-jour__content"><?php echo esc_html( $menu['plat'] ?? '' ); ?></span>
					</div>
					<?php if ( isset( $menu['dessert'] ) && $menu['dessert'] ) : ?>
						<div class="couverty-menu-jour__course">
							<span class="couverty-menu-jour__label">Dessert</span>
							<span class="couverty-menu-jour__content"><?php echo esc_html( $menu['dessert'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $show_prices && isset( $menu['prix'] ) && $menu['prix'] ) : ?>
					<div class="couverty-menu-jour__price">CHF <?php echo esc_html( number_format( (float) $menu['prix'], 2, '.', '' ) ); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

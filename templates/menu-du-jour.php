<?php
defined( 'ABSPATH' ) || exit;

/**
 * Menu du jour template
 *
 * One card per day. A day can hold several menus (up to 10), each one a full
 * entrée / plat / dessert with its own price; inside a menu, every course can
 * offer several alternatives the guest picks from. Both levels of choice are
 * written with an « ou ».
 *
 * @var array $data Contains 'config' and 'menus' keys with weekly menu data
 * @var array $atts Shortcode attributes: show_price, part, day
 */

$config = isset( $data['config'] ) ? $data['config'] : [];
$menus  = isset( $data['menus'] ) ? $data['menus'] : [];

$show_price = isset( $atts['show_price'] ) ? filter_var( $atts['show_price'], FILTER_VALIDATE_BOOLEAN ) : true;
// Both values are whitelisted by the shortcode handler.
$part       = isset( $atts['part'] ) ? $atts['part'] : 'all';
$only_today = isset( $atts['day'] ) && 'today' === $atts['day'];

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

$course_labels = [
	'entrees'  => __( 'Entrée', 'couverty' ),
	'plats'    => __( 'Plat', 'couverty' ),
	'desserts' => __( 'Dessert', 'couverty' ),
];

$service_labels = [
	'MIDI' => __( 'Midi', 'couverty' ),
	'SOIR' => __( 'Soir', 'couverty' ),
];

$or_label  = __( 'ou', 'couverty' );
$or_inline = '<span class="couverty-menu-jour__or-inline">' . esc_html( $or_label ) . '</span>';

// Current day (1=Monday to 7=Sunday) in the site's timezone, not the server's.
$current_day = (int) wp_date( 'N' );

$menu_unique_semaine = ! empty( $config['menuUniqueSemaine'] );
$service_soir_actif  = ! empty( $config['serviceSoirActif'] );

// La page porte déjà un h1, et le titre de la carte est un h2 : le menu du jour
// s'aligne dessus au lieu de démarrer en h3, et les jours suivent en h4. Sans
// titre de section, les jours remontent d'un cran pour ne pas sauter de niveau.
$has_heading = ! empty( $config['titre'] );
$day_level   = $has_heading ? 3 : 2;

/**
 * Alternatives of one course, as a list of non-empty strings.
 *
 * The API sends one array per course (entrees, plats, desserts). A response
 * that only carries the joined string (entree, plat, dessert) is read as a
 * single alternative.
 *
 * @param array  $menu Menu data.
 * @param string $key  'entrees', 'plats' or 'desserts'.
 * @return string[]
 */
$alternatives = static function ( $menu, $key ) {
	$singular = [
		'entrees'  => 'entree',
		'plats'    => 'plat',
		'desserts' => 'dessert',
	];

	if ( isset( $menu[ $key ] ) && is_array( $menu[ $key ] ) ) {
		$values = $menu[ $key ];
	} elseif ( isset( $menu[ $singular[ $key ] ] ) ) {
		$values = [ $menu[ $singular[ $key ] ] ];
	} else {
		$values = [];
	}

	$items = [];
	foreach ( $values as $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			$items[] = $value;
		}
	}

	return $items;
};

/**
 * Service of a menu, MIDI unless the API says SOIR.
 *
 * @param array $menu Menu data.
 * @return string 'MIDI' or 'SOIR'
 */
$service_of = static function ( $menu ) {
	return isset( $menu['service'] ) && 'SOIR' === $menu['service'] ? 'SOIR' : 'MIDI';
};

// Group the menus by day. Jour 0 is the single weekly menu: the two modes never
// mix, so the other one's rows are ignored rather than shown under a wrong label.
$days = [];
foreach ( $menus as $menu ) {
	$jour = isset( $menu['jour'] ) ? (int) $menu['jour'] : 0;

	if ( ! isset( $day_labels[ $jour ] ) ) {
		continue;
	}
	if ( $menu_unique_semaine ? 0 !== $jour : 0 === $jour ) {
		continue;
	}
	if ( $only_today && ! $menu_unique_semaine && $jour !== $current_day ) {
		continue;
	}
	// When a single course is requested, a menu without it has nothing to show.
	if ( 'all' !== $part && empty( $alternatives( $menu, $part ) ) ) {
		continue;
	}

	$days[ $jour ][] = $menu;
}

if ( empty( $days ) ) {
	return;
}

ksort( $days );

// Inside a day: MIDI before SOIR, then the order set in Couverty.
foreach ( $days as &$day_menus ) {
	usort(
		$day_menus,
		static function ( $a, $b ) use ( $service_of ) {
			$rank_a = [ 'SOIR' === $service_of( $a ) ? 1 : 0, isset( $a['ordre'] ) ? (int) $a['ordre'] : 0 ];
			$rank_b = [ 'SOIR' === $service_of( $b ) ? 1 : 0, isset( $b['ordre'] ) ? (int) $b['ordre'] : 0 ];
			return $rank_a <=> $rank_b;
		}
	);
}
unset( $day_menus );
?>

<div class="couverty-menu-du-jour">
	<?php if ( $has_heading ) : ?>
		<h2 class="couverty-menu-du-jour__title"><?php echo esc_html( $config['titre'] ); ?></h2>
	<?php endif; ?>

	<?php foreach ( $days as $jour => $day_menus ) : ?>
		<?php
		$is_today = ! $menu_unique_semaine && $jour === $current_day;

		$services     = [];
		$price_labels = [];
		foreach ( $day_menus as $menu ) {
			$services[ $service_of( $menu ) ] = true;

			$prix           = isset( $menu['prix'] ) ? (float) $menu['prix'] : 0;
			$price_labels[] = $prix > 0 ? 'CHF ' . number_format( $prix, 2, '.', '' ) : '';
		}

		// Midi / Soir only when the day actually serves both.
		$show_service = $service_soir_actif && count( $services ) > 1;

		// A price shared by every menu of the day is written once, at the foot of
		// the card; otherwise each menu carries its own.
		$shared_price = ( $show_price && 1 === count( array_unique( $price_labels ) ) ) ? $price_labels[0] : '';
		?>
		<div class="couverty-menu-jour<?php echo $is_today ? ' couverty-menu-jour--today' : ''; ?>">
			<?php
			printf(
				'<h%1$d class="couverty-menu-jour__day">%2$s</h%1$d>',
				(int) $day_level,
				esc_html( $day_labels[ $jour ] )
			);
			?>

			<?php foreach ( $day_menus as $index => $menu ) : ?>
				<?php
				$service = $service_of( $menu );

				// Without the Midi / Soir distinction, every menu of the day is an
				// alternative; with it, the guest chooses within a service, and the
				// service label opens each group instead of an « ou ».
				$opens_group = 0 === $index || ( $show_service && $service !== $service_of( $day_menus[ $index - 1 ] ) );
				?>
				<?php if ( ! $opens_group ) : ?>
					<div class="couverty-menu-jour__or"><?php echo esc_html( $or_label ); ?></div>
				<?php endif; ?>

				<div class="couverty-menu-jour__menu">
					<?php if ( $show_service && $opens_group ) : ?>
						<div class="couverty-menu-jour__service"><?php echo esc_html( $service_labels[ $service ] ); ?></div>
					<?php endif; ?>

					<div class="couverty-menu-jour__courses">
						<?php foreach ( $course_labels as $key => $label ) : ?>
							<?php
							if ( 'all' !== $part && $key !== $part ) {
								continue;
							}
							$items = $alternatives( $menu, $key );
							if ( empty( $items ) ) {
								continue;
							}

							if ( 'plats' === $key ) {
								// Main courses stack, one alternative per line.
								$content = '';
								foreach ( $items as $i => $item ) {
									$content .= '<span class="couverty-menu-jour__alt">' . ( $i > 0 ? $or_inline . ' ' : '' ) . esc_html( $item ) . '</span>';
								}
							} else {
								// Entrées and desserts read as one line.
								$content = implode( ' ' . $or_inline . ' ', array_map( 'esc_html', $items ) );
							}
							?>
							<div class="couverty-menu-jour__course couverty-menu-jour__course--<?php echo esc_attr( $key ); ?>">
								<?php if ( 'all' === $part ) : ?>
									<span class="couverty-menu-jour__label"><?php echo esc_html( $label ); ?></span>
								<?php endif; ?>
								<?php // Every piece of $content went through esc_html() above. ?>
								<span class="couverty-menu-jour__content"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( $show_price && '' === $shared_price && '' !== $price_labels[ $index ] ) : ?>
						<div class="couverty-menu-jour__price"><?php echo esc_html( $price_labels[ $index ] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if ( '' !== $shared_price ) : ?>
				<div class="couverty-menu-jour__price"><?php echo esc_html( $shared_price ); ?></div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

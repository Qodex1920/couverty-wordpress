<?php
/**
 * Données structurées schema.org (JSON-LD) pour les blocs et shortcodes.
 *
 * Les fiches synchronisées (CPT) ne sont pas des pages : le seul endroit où
 * Google lit le menu est la page WordPress qui porte le bloc. On y décrit donc
 * le restaurant et son menu, comme le fait couverty.ch sur ses pages publiques.
 *
 * @package Couverty
 */

defined( 'ABSPATH' ) || exit;

class Couverty_Schema {

	/**
	 * Carte des plats ou des boissons : une MenuSection par catégorie.
	 *
	 * @param array  $categories Catégories avec leurs 'plats' ou 'boissons'.
	 * @param string $items_key  'plats' ou 'boissons'.
	 * @param string $menu_name  Nom du menu (Carte, Boissons).
	 * @return string Balise script, ou chaîne vide sans contenu.
	 */
	public static function carte( $categories, $items_key, $menu_name ) {
		$sections = array();

		foreach ( (array) $categories as $category ) {
			$items = array();
			foreach ( (array) ( $category[ $items_key ] ?? array() ) as $item ) {
				$menu_item = self::menu_item( $item['nom'] ?? '', $item['description'] ?? null, $item['prix'] ?? null );
				if ( ! $menu_item ) {
					continue;
				}
				$diets = array_filter(
					array(
						! empty( $item['vegetarien'] ) ? 'https://schema.org/VegetarianDiet' : null,
						! empty( $item['vegan'] ) ? 'https://schema.org/VeganDiet' : null,
						! empty( $item['sansGluten'] ) ? 'https://schema.org/GlutenFreeDiet' : null,
					)
				);
				if ( $diets ) {
					$menu_item['suitableForDiet'] = array_values( $diets );
				}
				if ( ! empty( $item['imageUrl'] ) ) {
					$menu_item['image'] = $item['imageUrl'];
				}
				$items[] = $menu_item;
			}

			if ( ! $items ) {
				continue;
			}
			$section = array(
				'@type'       => 'MenuSection',
				'name'        => $category['nom'] ?? '',
				'hasMenuItem' => $items,
			);
			if ( ! empty( $category['description'] ) ) {
				$section['description'] = $category['description'];
			}
			$sections[] = $section;
		}

		return self::script( $menu_name, $sections );
	}

	/**
	 * Menu du jour : une MenuSection par jour, un MenuItem par menu.
	 *
	 * @param array $data 'config' et 'menus' de l'API (menus actifs).
	 * @return string
	 */
	public static function menu_du_jour( $data ) {
		$config = $data['config'] ?? array();
		$menus  = $data['menus'] ?? array();
		$labels = array(
			0 => __( 'Cette semaine', 'couverty' ),
			1 => __( 'Lundi', 'couverty' ),
			2 => __( 'Mardi', 'couverty' ),
			3 => __( 'Mercredi', 'couverty' ),
			4 => __( 'Jeudi', 'couverty' ),
			5 => __( 'Vendredi', 'couverty' ),
			6 => __( 'Samedi', 'couverty' ),
			7 => __( 'Dimanche', 'couverty' ),
		);
		$or     = ' ' . __( 'ou', 'couverty' ) . ' ';
		$unique = ! empty( $config['menuUniqueSemaine'] );

		$by_day = array();
		foreach ( (array) $menus as $menu ) {
			$jour = (int) ( $menu['jour'] ?? 0 );
			if ( $unique ? 0 !== $jour : 0 === $jour ) {
				continue;
			}
			$plats = self::alternatives( $menu, 'plats', 'plat' );
			if ( ! $plats ) {
				continue;
			}
			$parts = array_filter(
				array(
					implode( $or, self::alternatives( $menu, 'entrees', 'entree' ) ),
					implode( $or, self::alternatives( $menu, 'desserts', 'dessert' ) ),
				)
			);
			$item = self::menu_item( implode( $or, $plats ), $parts ? implode( ' · ', $parts ) : null, $menu['prix'] ?? null );
			if ( $item ) {
				$by_day[ $jour ][] = $item;
			}
		}
		ksort( $by_day );

		$sections = array();
		foreach ( $by_day as $jour => $items ) {
			$sections[] = array(
				'@type'       => 'MenuSection',
				'name'        => $labels[ $jour ] ?? (string) $jour,
				'hasMenuItem' => $items,
			);
		}

		return self::script( $config['titre'] ?? __( 'Menu du jour', 'couverty' ), $sections );
	}

	/**
	 * Alternatives d'un service : tableau de l'API, ou l'ancienne chaîne.
	 *
	 * @param array  $menu     Menu du jour.
	 * @param string $plural   Clé tableau (plats).
	 * @param string $singular Clé chaîne (plat).
	 * @return string[]
	 */
	private static function alternatives( $menu, $plural, $singular ) {
		if ( isset( $menu[ $plural ] ) && is_array( $menu[ $plural ] ) ) {
			$values = $menu[ $plural ];
		} else {
			$values = isset( $menu[ $singular ] ) ? array( $menu[ $singular ] ) : array();
		}
		return array_values( array_filter( array_map( 'trim', array_map( 'strval', $values ) ) ) );
	}

	/**
	 * Un MenuItem : nom obligatoire, description et prix quand ils existent.
	 *
	 * @param string      $name        Nom.
	 * @param string|null $description Description.
	 * @param mixed       $prix        Prix en CHF.
	 * @return array|null
	 */
	private static function menu_item( $name, $description, $prix ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return null;
		}
		$item = array(
			'@type' => 'MenuItem',
			'name'  => $name,
		);
		if ( $description ) {
			$item['description'] = $description;
		}
		if ( is_numeric( $prix ) && (float) $prix > 0 ) {
			$item['offers'] = array(
				'@type'         => 'Offer',
				'price'         => number_format( (float) $prix, 2, '.', '' ),
				'priceCurrency' => 'CHF',
			);
		}
		return $item;
	}

	/**
	 * Le graphe : le restaurant (nom, adresse, téléphone connus du plugin) et son menu.
	 *
	 * @param string $menu_name Nom du menu.
	 * @param array  $sections  MenuSection.
	 * @return string
	 */
	private static function script( $menu_name, $sections ) {
		if ( ! $sections ) {
			return '';
		}

		$restaurant = get_option( 'couverty_restaurant_data', array() );
		$name       = $restaurant['name'] ?? get_bloginfo( 'name' );

		$node = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Restaurant',
			'name'     => $name,
			'url'      => home_url( '/' ),
			'hasMenu'  => array(
				'@type'          => 'Menu',
				'name'           => $menu_name . ' - ' . $name,
				'hasMenuSection' => $sections,
			),
		);

		$address = $restaurant['contact']['address'] ?? array();
		if ( ! empty( $address['rue'] ) || ! empty( $address['npaVille'] ) ) {
			$node['address'] = array_filter(
				array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $address['rue'] ?? null,
					'addressLocality' => $address['npaVille'] ?? null,
					'addressCountry'  => 'CH',
				)
			);
		}
		if ( ! empty( $restaurant['contact']['telephone'] ) ) {
			$node['telephone'] = $restaurant['contact']['telephone'];
		}

		// JSON_HEX_TAG : aucun « </script> » possible dans le contenu, même saisi par le restaurateur.
		return '<script type="application/ld+json">'
			. wp_json_encode( $node, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. '</script>';
	}
}

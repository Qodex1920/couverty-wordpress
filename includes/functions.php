<?php
/**
 * Couverty public helper functions
 *
 * Use these functions in theme templates, page builders (Bricks, Elementor),
 * or any custom PHP code to retrieve Couverty data.
 *
 * All functions use the WordPress Transients API for caching.
 *
 * Example usage in a Bricks Code element:
 *
 *   $menu = couverty_get_menu();
 *   if ( $menu ) {
 *       foreach ( $menu['categories'] as $category ) {
 *           echo '<h2>' . esc_html( $category['nom'] ) . '</h2>';
 *           foreach ( $category['plats'] as $plat ) {
 *               echo '<p>' . esc_html( $plat['nom'] ) . ' — CHF ' . esc_html( $plat['prix'] ) . '</p>';
 *           }
 *       }
 *   }
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get menu (plats) data from Couverty API
 *
 * Returns an array with:
 *   - categories: array of categories, each containing:
 *       - nom: string
 *       - description: string|null
 *       - nombrePrix: int (1-3)
 *       - prixLabels: string[]|null
 *       - plats: array of plats, each containing:
 *           - nom: string
 *           - prix: float
 *           - prix2: float|null
 *           - prix3: float|null
 *           - description: string|null
 *           - imageUrl: string|null
 *           - vegetarien: bool
 *           - vegan: bool
 *           - sansGluten: bool
 *           - allergenes: string[]
 *
 * @return array|null Menu data or null on failure
 */
function couverty_get_menu() {
	$api = Couverty::get_instance()->get_api();

	if ( ! $api ) {
		return null;
	}

	$response = $api->get_menu( 'plats' );

	if ( ! $response || ! isset( $response['plats'] ) ) {
		return null;
	}

	return $response['plats'];
}

/**
 * Get boissons (drinks) data from Couverty API
 *
 * Returns an array with:
 *   - categories: array of categories, each containing:
 *       - nom: string
 *       - nombrePrix: int (1-3)
 *       - prixLabels: string[]|null
 *       - boissons: array of drinks, each containing:
 *           - nom: string
 *           - prix: float
 *           - prix2: float|null
 *           - prix3: float|null
 *           - description: string|null
 *           - volume: string|null
 *           - region: string|null
 *           - annee: int|null
 *
 * @return array|null Boissons data or null on failure
 */
function couverty_get_boissons() {
	$api = Couverty::get_instance()->get_api();

	if ( ! $api ) {
		return null;
	}

	$response = $api->get_menu( 'boissons' );

	if ( ! $response || ! isset( $response['boissons'] ) ) {
		return null;
	}

	return $response['boissons'];
}

/**
 * Get menu du jour (daily/weekly menu) data from Couverty API
 *
 * Returns an array with:
 *   - config: array containing:
 *       - menuUniqueSemaine: bool
 *       - afficherPrix: bool
 *       - titre: string|null
 *   - menus: array of menus, each containing:
 *       - jour: int (0=week, 1=Monday, ..., 7=Sunday)
 *       - entree: string|null
 *       - plat: string
 *       - dessert: string|null
 *       - prix: float|null
 *
 * @return array|null Menu du jour data or null on failure
 */
function couverty_get_menu_du_jour() {
	$api = Couverty::get_instance()->get_api();

	if ( ! $api ) {
		return null;
	}

	$response = $api->get_menu( 'all' );

	if ( ! $response || ! isset( $response['menuSemaine'] ) ) {
		return null;
	}

	return $response['menuSemaine'];
}

/**
 * Get restaurant information from Couverty API
 *
 * Returns an array with:
 *   - name: string
 *   - slug: string
 *   - contact: array (email, telephone, address)
 *   - hours: array (midi, soir)
 *   - social: array (website, facebook, instagram, ...)
 *   - branding: array (primaryColor, secondaryColor, logoUrl, coverImageUrl)
 *
 * @return array|null Restaurant data or null on failure
 */
function couverty_get_restaurant() {
	$api = Couverty::get_instance()->get_api();

	if ( ! $api ) {
		return null;
	}

	return $api->get_restaurant_info();
}

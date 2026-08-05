=== Couverty ===
Contributors: couverty
Tags: restaurant, menu, réservation, booking, food
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intégrez le menu et les réservations de votre restaurant depuis Couverty directement dans WordPress.

== Description ==

Le plugin officiel Couverty affiche le menu de votre restaurant, la carte des boissons, le menu du jour et le widget de réservation sur votre site WordPress.

**Fonctionnalités :**

* 4 blocs WordPress et 4 shortcodes équivalents
* Synchronisation automatique des données dans des types de contenu WordPress, utilisables par tous les constructeurs de pages
* Cache local : le site continue d'afficher vos données même si l'API est momentanément injoignable
* Bouton de réservation flottant configurable
* API REST et fonctions PHP pour les intégrations sur mesure
* Mises à jour automatiques

**Blocs et shortcodes disponibles :**

* **Menu** — `[couverty_menu]` — carte des plats par catégorie
* **Boissons** — `[couverty_boissons]` — carte des boissons
* **Menu du jour** — `[couverty_menu_du_jour]` — menu du jour ou de la semaine
* **Réservation** — `[couverty_reservation]` — widget de réservation en ligne

== Installation ==

1. Téléchargez le plugin depuis votre tableau de bord Couverty
2. Dans WordPress, allez dans Extensions → Ajouter → Téléverser une extension
3. Sélectionnez le fichier .zip et cliquez sur « Installer maintenant »
4. Activez le plugin
5. Ouvrez le menu **Couverty** dans la barre latérale
6. Collez votre clé API et testez la connexion

**Obtenir une clé API :**
Connectez-vous à votre compte Couverty, puis allez dans Réglages → Intégrations → API publique pour créer une clé.

== Frequently Asked Questions ==

= Ai-je besoin d'un compte Couverty ? =
Oui, le plugin nécessite un compte Couverty actif avec un abonnement incluant l'accès API.

= Le plugin ralentit-il mon site ? =
Non. Les données sont mises en cache localement (transients WordPress, 10 minutes par défaut) et copiées dans des types de contenu WordPress toutes les 30 minutes. Les pages sont servies depuis votre base de données, pas depuis l'API.

= Que se passe-t-il si l'API Couverty est indisponible ? =
Vos contenus déjà synchronisés restent affichés. Le plugin attend une minute avant de retenter un appel, pour ne jamais ralentir vos pages.

= Puis-je personnaliser l'apparence ? =
Oui. Les classes CSS sont préfixées (`couverty-*`) et les couleurs, espacements et arrondis passent par des variables CSS surchargeables depuis votre thème.

= Les shortcodes fonctionnent-ils avec Elementor / Divi / Bricks ? =
Oui. En plus des shortcodes, les données sont exposées en types de contenu personnalisés (`couverty_plat`, `couverty_boisson`, `couverty_menu_jour`, `couverty_evenement`) avec leurs champs personnalisés, utilisables dans n'importe quelle boucle de requête.

= Comment désactiver le chargement du CSS sur tout le site ? =
Ajoutez `add_filter( 'couverty_enqueue_public_styles', '__return_false' );` puis rechargez-le uniquement où vous en avez besoin.

== Changelog ==

= 1.8.2 =
* Protection contre l'accès direct ajoutée aux fichiers d'assets des blocs
* Échappement consolidé sur les détails des boissons (volume, région, année)

= 1.8.1 =
* Plugin traduit en allemand et en italien — interface d'administration, blocs de l'éditeur et messages d'erreur
* Allemand en orthographe suisse (« ss » et non « ß ») ; variantes de_CH, de_CH_informal et de_DE fournies
* L'interface suit désormais la langue du site WordPress, sans réglage supplémentaire

= 1.8.0 =
* Cache d'échec : une API injoignable ne déclenche plus un appel bloquant à chaque page vue
* Messages d'erreur explicites (clé invalide, permission manquante, quota dépassé) au lieu d'un échec générique
* La synchronisation après mise à jour s'exécute en arrière-plan et non plus pendant le chargement d'une page
* « Vider le cache » fonctionne désormais avec un object cache externe (Redis, Memcached)
* Scripts sortis du HTML et chargés uniquement quand ils servent ; nouveaux filtres `couverty_enqueue_public_styles` et `couverty_enable_lightbox_fix`
* Correction du bloc Réservation, qui ne s'affichait pas dans l'éditeur
* Le réglage « Texte du bouton » est enfin appliqué au bouton flottant
* Page de réglages réorganisée en onglets, avec état de connexion et copie en un clic
* Connexion en un seul bouton : plus d'impasse entre « Enregistrer » et « Tester »
* L'état de connexion se met à jour immédiatement, et « Réglages enregistrés » s'affiche enfin
* En cas d'échec de synchronisation, la page dit ce qui continue de fonctionner et mène droit à la clé à corriger
* Tableaux de référence lisibles sur mobile et tablette, champs correctement étiquetés pour les lecteurs d'écran
* La clé API n'est plus réaffichée en clair dans la page de réglages
* Le menu du jour utilise le fuseau horaire du site pour déterminer le jour courant
* Plugin traduisible : chaînes uniformisées et modèle de traduction fourni

= 1.7.2 =
* Prix pré-formatés côté Couverty (`CHF 18.- / CHF 28.-`)

= 1.7.0 =
* Synchronisation des événements
* Interface d'administration en français

= 1.6.0 =
* Mises à jour automatiques depuis GitHub Releases

= 1.5.0 =
* Types de contenu personnalisés pour les constructeurs de pages
* API REST `couverty/v1`

= 1.0.0 =
* Version initiale

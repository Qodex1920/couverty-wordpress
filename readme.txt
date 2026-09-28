=== Couverty ===
Contributors: couverty
Tags: restaurant, menu, réservation, booking, food
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.11.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intégrez le menu et les réservations de votre restaurant depuis Couverty directement dans WordPress.

== Description ==

Le plugin officiel Couverty affiche le menu de votre restaurant, la carte des boissons, le menu du jour et le widget de réservation sur votre site WordPress.

**Fonctionnalités :**

* Pages Carte, Menu du jour, Boissons et Réservation créées en un clic
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
Oui. Les classes CSS sont préfixées (`couverty-*`) et les couleurs, espacements et arrondis passent par des variables CSS. Ajoutez ce bloc dans Apparence → Personnaliser → CSS additionnel :

`:root {
	--couverty-accent: #8b1a2b;       /* nom des plats et des boissons */
	--couverty-text-muted: #7d6e5b;   /* descriptions, allergènes, notes */
	--couverty-border: #d4c5b4;       /* filets de séparation */
	--couverty-radius: 8px;
}`

Le texte principal suit déjà la couleur de votre thème. Sur un thème à fond sombre, pensez à éclaircir `--couverty-accent`, `--couverty-text-muted` et `--couverty-border`, qui gardent des valeurs pensées pour un fond clair.

= Les shortcodes fonctionnent-ils avec Elementor / Divi / Bricks ? =
Oui. En plus des shortcodes, les données sont exposées en types de contenu personnalisés (`couverty_plat`, `couverty_boisson`, `couverty_menu_jour`, `couverty_evenement`) avec leurs champs personnalisés, utilisables dans n'importe quelle boucle de requête.

= Comment désactiver le chargement du CSS sur tout le site ? =
Ajoutez `add_filter( 'couverty_enqueue_public_styles', '__return_false' );` puis rechargez-le uniquement où vous en avez besoin.

== Changelog ==

= 1.11.2 =
* Constructeurs de pages : les fiches Menu du jour exposent Entrée (HTML), Plat (HTML) et Dessert (HTML), où le « ou » entre alternatives est déjà en italique. À utiliser dans un élément texte qui accepte le HTML, à la place des champs texte brut

= 1.11.1 =
* Référencement : les blocs Carte, Boissons et Menu du jour publient les données structurées schema.org (Restaurant, Menu, MenuItem avec prix et régimes), comme les pages publiques Couverty
* Les fiches synchronisées (plats, boissons, menus du jour, événements) n'ont plus d'URL propre et sortent des sitemaps WordPress, Yoast et Rank Math : elles restent disponibles pour les constructeurs de pages

= 1.11.0 =
* Menu du jour : les alternatives d'un même service (deux entrées, deux plats au choix) s'affichent reliées par « ou », comme dans Couverty
* Plusieurs menus le même jour sont regroupés dans une seule carte, séparés par un filet « ou », avec le service Midi ou Soir quand les deux existent
* Nouvelles options du shortcode et du bloc : `part` pour n'afficher que les entrées, les plats ou les desserts, `day="today"` pour ne montrer que le jour courant
* Le prix du menu du jour s'affiche enfin ; il est écrit une fois par jour quand tous les menus sont au même prix

= 1.10.0 =
* L'éditeur affiche enfin la carte telle qu'elle sortira : plus besoin de publier pour voir le résultat
* Les blocs Couverty acceptent la pleine largeur et la classe CSS saisie dans l'éditeur, qui étaient jusqu'ici sans effet
* La carte reprend la couleur de texte de votre thème — elle restait noire, donc illisible sur un site à fond sombre
* Les couleurs, arrondis et espacements se surchargent maintenant depuis `:root`, comme la documentation l'annonçait
* Le bouton photo d'un plat et la fermeture de la visionneuse sont assez grands pour le doigt ; la visionneuse garde le clavier chez elle et bloque le défilement de la page
* Titres de sections hiérarchisés correctement pour les lecteurs d'écran, quel que soit le contenu envoyé par Couverty
* Le bouton de fin de page pointe vers l'adresse définitive de la page de réservation, sans redirection
* La carte s'aligne sur la largeur du texte, y compris avec un thème classique
* Textes d'exemple réécrits : ils ne mettent plus dans votre bouche des promesses que vous n'avez pas faites
* L'adresse des pages créées suit la langue du site
* Le centrage du bouton final ne l'emporte plus sur l'alignement choisi dans l'éditeur

= 1.9.2 =
* Correction : le bouton « Réserver une table » en bas des pages créées ne menait nulle part
* Menu du jour mis en page comme le reste de la carte, sans encadrés
* Sur mobile, le prix ne concurrence plus le nom du plat
* L'accroche est alignée avec le titre, et le filet avant l'invitation retrouve sa couleur
* Les réglages signalent les pages auxquelles il manque une image mise en avant

= 1.9.1 =
* Les pages créées laissent le thème afficher le titre et l'image d'ouverture — plus de titre en double
* Carte mise en page comme une carte imprimée : plats aérés, filets de séparation, ligne de conduite jusqu'au prix
* Le bouton de fin de page pointe vers votre vraie page de réservation
* Meilleure lisibilité des descriptions et des allergènes : le gris secondaire passe le seuil de contraste WCAG AA

= 1.9.0 =
* Nouveau : créez en un clic les pages Carte, Menu du jour, Boissons et Réservation, prêtes à publier
* Les pages sont créées en brouillon — vous les relisez avant qu'elles n'apparaissent sur votre site
* Les mêmes mises en page sont disponibles comme compositions dans l'éditeur : cherchez « Couverty »
* Une page supprimée peut être recréée ; une page existante n'est jamais écrasée

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

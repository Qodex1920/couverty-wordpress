<?php
/**
 * Main Couverty plugin class
 */

defined( 'ABSPATH' ) || exit;

class Couverty {
	/**
	 * Single instance of the class
	 *
	 * @var Couverty
	 */
	private static $instance = null;

	/**
	 * API client instance
	 *
	 * @var Couverty_API
	 */
	private $api = null;

	/**
	 * Get singleton instance
	 *
	 * @return Couverty
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		load_plugin_textdomain( 'couverty', false, dirname( plugin_basename( COUVERTY_PLUGIN_FILE ) ) . '/languages' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_styles' ) );
		add_action( 'wp_footer', array( $this, 'inject_floating_widget' ) );
		add_action( 'wp_footer', array( $this, 'inject_lightbox_fix' ) );
		new Couverty_Blocks();
		new Couverty_Shortcodes();
		new Couverty_REST();
		new Couverty_Sync();
	}

	/**
	 * Enqueue public styles
	 */
	public function enqueue_public_styles() {
		// Always load — CSS is needed for shortcodes, blocks, AND page builders using CPTs.
		wp_enqueue_style(
			'couverty-public',
			COUVERTY_PLUGIN_URL . 'assets/css/couverty-public.css',
			array(),
			COUVERTY_VERSION
		);
	}

	/**
	 * Check if page contains Couverty shortcode or block
	 *
	 * @return bool
	 */
	private function has_couverty_content() {
		global $post;

		if ( ! $post ) {
			return false;
		}

		return (
			has_shortcode( $post->post_content, 'couverty_menu' ) ||
			has_shortcode( $post->post_content, 'couverty_boissons' ) ||
			has_shortcode( $post->post_content, 'couverty_menu_du_jour' ) ||
			has_shortcode( $post->post_content, 'couverty_reservation' ) ||
			strpos( $post->post_content, '<!-- wp:couverty' ) !== false
		);
	}

	/**
	 * Inject floating widget script in footer
	 */
	public function inject_floating_widget() {
		$settings = self::get_settings();

		if ( ! isset( $settings['floating_enabled'] ) || ! $settings['floating_enabled'] ) {
			return;
		}

		if ( empty( $settings['slug'] ) || empty( $settings['base_url'] ) ) {
			return;
		}

		$base_url = esc_url( $settings['base_url'] );
		$slug      = esc_attr( $settings['slug'] );

		?>
		<script async src="<?php echo esc_url( "{$base_url}/widget-floating.js?slug={$slug}" ); ?>"></script>
		<?php
	}

	/**
	 * Fix PhotoSwipe lightbox dimensions for external images (Bricks, etc.)
	 *
	 * Bricks sets empty data-pswp-width/height for external image URLs,
	 * causing PhotoSwipe to use viewport dimensions instead.
	 */
	public function inject_lightbox_fix() {
		?>
		<script>
		(function() {
			if (!document.querySelector('a[data-pswp-src]')) return;

			var cache = {};
			var fixed = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

			function needsFix(link) {
				if (fixed && fixed.has(link)) return false;
				var w = link.getAttribute('data-pswp-width');
				var h = link.getAttribute('data-pswp-height');
				return !w || !h || w === '' || h === '' || w === '0' || h === '0';
			}

			function applyDims(link, w, h) {
				link.setAttribute('data-pswp-width', w);
				link.setAttribute('data-pswp-height', h);
				if (fixed) fixed.add(link);
			}

			function fixLink(link, cb) {
				var src = link.getAttribute('data-pswp-src');
				if (!src) return cb && cb();

				if (cache[src]) {
					applyDims(link, cache[src].w, cache[src].h);
					return cb && cb();
				}

				var img = new Image();
				img.onload = function() {
					cache[src] = { w: this.naturalWidth, h: this.naturalHeight };
					applyDims(link, this.naturalWidth, this.naturalHeight);
					if (cb) cb();
				};
				img.onerror = function() {
					if (cb) cb();
				};
				img.src = src;
			}

			function fixAll() {
				document.querySelectorAll('a[data-pswp-src]').forEach(function(link) {
					if (needsFix(link)) fixLink(link);
				});
			}

			/* Initial pass */
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', fixAll);
			} else {
				fixAll();
			}

			/* Watch for dynamically added content, auto-disconnect after 30s */
			if (typeof MutationObserver !== 'undefined') {
				var timer;
				var obs = new MutationObserver(function() {
					clearTimeout(timer);
					timer = setTimeout(fixAll, 200);
				});
				obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
				setTimeout(function() { obs.disconnect(); }, 30000);
			}

			/* Click safety net: block only if dimensions missing, load then re-click */
			document.addEventListener('click', function(e) {
				var link = e.target.closest('a[data-pswp-src]');
				if (!link || !needsFix(link)) return;

				e.preventDefault();
				e.stopPropagation();

				fixLink(link, function() {
					if (fixed) fixed.add(link);
					link.click();
				});
			}, true);
		})();
		</script>
		<?php
	}

	/**
	 * Get API client instance
	 *
	 * @return Couverty_API
	 */
	public function get_api() {
		if ( is_null( $this->api ) ) {
			$settings   = self::get_settings();
			$this->api  = new Couverty_API( $settings );
		}
		return $this->api;
	}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'api_key'          => '',
			'base_url'         => 'https://couverty.ch',
			'slug'             => '',
			'cache_duration'   => 600,
			'floating_enabled' => false,
			'floating_text'    => 'Réserver',
		);

		$settings = get_option( 'couverty_settings', array() );
		$settings = wp_parse_args( $settings, $defaults );

		// Allow override via wp-config.php constant
		if ( defined( 'COUVERTY_BASE_URL' ) ) {
			$settings['base_url'] = COUVERTY_BASE_URL;
		}

		return $settings;
	}
}

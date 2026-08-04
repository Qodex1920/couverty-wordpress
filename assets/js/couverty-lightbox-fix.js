/**
 * Couverty — PhotoSwipe dimension fix
 *
 * Bricks sets empty data-pswp-width/height for external image URLs (such as the
 * Cloudinary URLs Couverty serves), which makes PhotoSwipe fall back to viewport
 * dimensions. This preloads each image to fill in the real dimensions.
 *
 * No-op on pages without PhotoSwipe links.
 */
(function () {
	'use strict';

	if (!document.querySelector('a[data-pswp-src]')) {
		return;
	}

	var cache = {};
	var fixed = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

	function needsFix(link) {
		if (fixed && fixed.has(link)) {
			return false;
		}
		var w = link.getAttribute('data-pswp-width');
		var h = link.getAttribute('data-pswp-height');
		return !w || !h || w === '0' || h === '0';
	}

	function applyDims(link, w, h) {
		link.setAttribute('data-pswp-width', w);
		link.setAttribute('data-pswp-height', h);
		if (fixed) {
			fixed.add(link);
		}
	}

	function fixLink(link, cb) {
		var src = link.getAttribute('data-pswp-src');
		if (!src) {
			return cb && cb();
		}

		if (cache[src]) {
			applyDims(link, cache[src].w, cache[src].h);
			return cb && cb();
		}

		var img = new Image();
		img.onload = function () {
			cache[src] = { w: this.naturalWidth, h: this.naturalHeight };
			applyDims(link, this.naturalWidth, this.naturalHeight);
			if (cb) {
				cb();
			}
		};
		img.onerror = function () {
			if (cb) {
				cb();
			}
		};
		img.src = src;
	}

	function fixAll() {
		document.querySelectorAll('a[data-pswp-src]').forEach(function (link) {
			if (needsFix(link)) {
				fixLink(link);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', fixAll);
	} else {
		fixAll();
	}

	/* Watch for dynamically added content, auto-disconnect after 30s. */
	if (typeof MutationObserver !== 'undefined') {
		var timer;
		var obs = new MutationObserver(function () {
			clearTimeout(timer);
			timer = setTimeout(fixAll, 200);
		});
		obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
		setTimeout(function () {
			obs.disconnect();
		}, 30000);
	}

	/* Click safety net: block only if dimensions are missing, load then re-click. */
	document.addEventListener(
		'click',
		function (e) {
			var link = e.target.closest ? e.target.closest('a[data-pswp-src]') : null;
			if (!link || !needsFix(link)) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			fixLink(link, function () {
				if (fixed) {
					fixed.add(link);
				}
				link.click();
			});
		},
		true
	);
})();

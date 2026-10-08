/**
 * Couverty — reservation widget
 *
 * Builds the booking iframe for every [data-couverty-embed] container, keeps its
 * height in sync with the embedded form, and lays the Couverty logo served in the
 * page's HTML over the spot the widget leaves for it (bottom-right corner).
 */
(function () {
	'use strict';

	var frames = [];

	// `credit` comes from the widget: where to put the logo, relative to the
	// iframe's top-right corner, or null when no form is shown. Before the first
	// message the logo waits in the corner (widget padding plus its border).
	function placeLogo(logo, credit) {
		if (!logo) {
			return;
		}
		logo.style.position = 'absolute';
		logo.style.zIndex = '1';
		logo.style.display = credit === null ? 'none' : 'inline-block';
		logo.style.top = credit ? credit.top + 'px' : 'auto';
		logo.style.bottom = credit ? 'auto' : '17px';
		logo.style.right = (credit ? credit.right : 17) + 'px';
	}

	function mount(container) {
		if (container.dataset.couvertyMounted === '1') {
			return;
		}
		container.dataset.couvertyMounted = '1';

		var src = container.dataset.couvertyEmbed;
		if (!src) {
			return;
		}

		var logo = container.querySelector('.couverty-logo');
		var fallback = container.querySelector('a:not(.couverty-logo)');

		var iframe = document.createElement('iframe');
		iframe.src = src;
		iframe.title = container.dataset.couvertyTitle || 'Réservation';
		iframe.style.display = 'block';
		iframe.style.width = '100%';
		iframe.style.minHeight = (parseInt(container.dataset.couvertyHeight, 10) || 600) + 'px';
		iframe.style.border = 'none';
		iframe.style.overflow = 'hidden';
		iframe.setAttribute('scrolling', 'no');
		iframe.loading = 'lazy';

		// Drops the crawler fallback link, keeps the logo. The container is the
		// logo's positioning box even when the public stylesheet is disabled.
		if (fallback) {
			fallback.remove();
		}
		container.style.position = 'relative';
		container.insertBefore(iframe, logo);

		frames.push({ iframe: iframe, origin: container.dataset.couvertyOrigin, logo: logo });
		placeLogo(logo);
	}

	window.addEventListener('message', function (event) {
		if (!event.data || event.data.type !== 'widget:resize') {
			return;
		}

		for (var i = 0; i < frames.length; i++) {
			var frame = frames[i];
			// Only accept messages from the very window we embedded, and only
			// when they come from the expected origin.
			if (frame.iframe.contentWindow !== event.source || frame.origin !== event.origin) {
				continue;
			}

			var height = parseInt(event.data.height, 10);
			if (height > 0) {
				frame.iframe.style.height = height + 'px';
			}
			if ('credit' in event.data) {
				placeLogo(frame.logo, event.data.credit);
			}
			return;
		}
	});

	function mountAll() {
		document.querySelectorAll('[data-couverty-embed]').forEach(mount);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', mountAll);
	} else {
		mountAll();
	}
})();

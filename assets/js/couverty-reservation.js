/**
 * Couverty — reservation widget
 *
 * Builds the booking iframe for every [data-couverty-embed] container and keeps
 * its height in sync with the embedded form.
 */
(function () {
	'use strict';

	var frames = [];

	function mount(container) {
		if (container.dataset.couvertyMounted === '1') {
			return;
		}
		container.dataset.couvertyMounted = '1';

		var src = container.dataset.couvertyEmbed;
		if (!src) {
			return;
		}

		var iframe = document.createElement('iframe');
		iframe.src = src;
		iframe.title = container.dataset.couvertyTitle || 'Réservation';
		iframe.style.width = '100%';
		iframe.style.minHeight = (parseInt(container.dataset.couvertyHeight, 10) || 600) + 'px';
		iframe.style.border = 'none';
		iframe.style.overflow = 'hidden';
		iframe.setAttribute('scrolling', 'no');
		iframe.loading = 'lazy';

		container.appendChild(iframe);
		frames.push({ iframe: iframe, origin: container.dataset.couvertyOrigin });
	}

	window.addEventListener('message', function (event) {
		if (!event.data || event.data.type !== 'widget:resize') {
			return;
		}

		var height = parseInt(event.data.height, 10);
		if (!height || height < 0) {
			return;
		}

		for (var i = 0; i < frames.length; i++) {
			// Only accept a resize from the very window we embedded, and only
			// when it comes from the expected origin.
			if (frames[i].iframe.contentWindow === event.source && frames[i].origin === event.origin) {
				frames[i].iframe.style.height = height + 'px';
				return;
			}
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

/**
 * Couverty — dish image lightbox
 *
 * Uses a single delegated listener and one shared overlay, so several menus can
 * live on the same page without duplicating markup or element IDs.
 */
(function () {
	'use strict';

	var overlay = null;
	var image = null;
	var lastFocused = null;

	function build() {
		if (overlay) {
			return;
		}

		overlay = document.createElement('div');
		overlay.className = 'couverty-lightbox';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'couverty-lightbox__close';
		close.setAttribute('aria-label', couvertyLightbox.closeLabel);
		close.textContent = '×';

		image = document.createElement('img');
		image.className = 'couverty-lightbox__img';
		image.alt = '';

		overlay.appendChild(close);
		overlay.appendChild(image);
		document.body.appendChild(overlay);

		close.addEventListener('click', hide);
		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) {
				hide();
			}
		});
	}

	function show(src, alt) {
		build();
		image.src = src;
		image.alt = alt || '';
		overlay.style.display = 'flex';
		document.addEventListener('keydown', onKeydown);
	}

	function hide() {
		if (!overlay) {
			return;
		}
		overlay.style.display = 'none';
		image.src = '';
		document.removeEventListener('keydown', onKeydown);
		if (lastFocused) {
			lastFocused.focus();
			lastFocused = null;
		}
	}

	function onKeydown(e) {
		if (e.key === 'Escape' || e.key === 'Esc') {
			hide();
		}
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.couverty-plat__image-btn') : null;
		if (!btn || !btn.dataset.src) {
			return;
		}

		e.preventDefault();
		lastFocused = btn;
		show(btn.dataset.src, btn.dataset.alt);
	});
})();

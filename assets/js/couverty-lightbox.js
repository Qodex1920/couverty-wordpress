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
	var closeButton = null;
	var lastFocused = null;
	var previousBodyOverflow = '';

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
		closeButton = close;

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

		// Le nom du plat nomme la vue ; sans lui, l'alt de l'image suffit.
		if (alt) {
			overlay.setAttribute('aria-label', alt);
		} else {
			overlay.removeAttribute('aria-label');
		}

		overlay.style.display = 'flex';

		// La page ne doit pas défiler derrière la vue.
		previousBodyOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';

		document.addEventListener('keydown', onKeydown);

		// Sans ça, le focus reste sur la puce cachée derrière l'overlay : au
		// clavier on tabulerait dans une page qu'on ne voit plus.
		closeButton.focus();
	}

	function hide() {
		if (!overlay) {
			return;
		}
		overlay.style.display = 'none';
		image.src = '';
		document.body.style.overflow = previousBodyOverflow;
		document.removeEventListener('keydown', onKeydown);
		if (lastFocused) {
			lastFocused.focus();
			lastFocused = null;
		}
	}

	function onKeydown(e) {
		if (e.key === 'Escape' || e.key === 'Esc') {
			hide();
			return;
		}

		// La vue n'a qu'une commande : garder Tab dessus revient à confiner le
		// focus, sans avoir à parcourir un arbre d'éléments focusables.
		if (e.key === 'Tab') {
			e.preventDefault();
			closeButton.focus();
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

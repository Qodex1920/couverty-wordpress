(function ($) {
	'use strict';

	var i18n = couverty.i18n;

	$(function () {
		bindAction('#couverty-test-connection', i18n.testing, testConnection);
		bindAction('#couverty-sync-data', i18n.syncing, syncData);
		bindAction('#couverty-clear-cache', i18n.clearing, clearCache);
		bindAction('#couverty-create-pages', i18n.creating, createPages);
		bindCopyButtons();
	});

	/**
	 * Wire a button to an async action, handling its busy state.
	 *
	 * Uses aria-busy rather than disabled so the focused button stays in the
	 * accessibility tree and keyboard focus is never dropped to <body>.
	 *
	 * @param {string}   selector  Button selector.
	 * @param {string}   busyLabel Label shown while the request runs.
	 * @param {Function} action    Receives a `done` callback to restore the button.
	 */
	function bindAction(selector, busyLabel, action) {
		$(document).on('click', selector, function (e) {
			e.preventDefault();

			var $btn = $(this);
			if ($btn.attr('aria-busy') === 'true') {
				return;
			}

			var label = $btn.text();
			$btn.attr('aria-busy', 'true').text(busyLabel);

			action(function () {
				$btn.removeAttr('aria-busy').text(label);
			});
		});
	}

	function showResult(message, isSuccess) {
		var $result = $('#couverty-test-result');

		$result
			.removeClass('success error')
			.addClass(isSuccess ? 'success' : 'error')
			.text(message)
			.show();

		// The result sits above the tabs; the buttons that fill it do not.
		if ($result.length && $result[0].scrollIntoView) {
			$result[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
		}
	}

	/**
	 * Summarise synced counts, e.g. "12 plats · 30 boissons · 5 menus du jour".
	 *
	 * @param {Object} counts Counts keyed by content type.
	 * @return {string} Formatted summary.
	 */
	function formatCounts(counts) {
		if (!counts) {
			return '';
		}

		return [
			counts.plats + ' ' + i18n.plats,
			counts.boissons + ' ' + i18n.boissons,
			counts.menus + ' ' + i18n.menus,
			counts.evenements + ' ' + i18n.evenements,
		].join(' · ');
	}

	/**
	 * Bring the status card in line with what just happened, so the page never
	 * shows "not connected yet" directly above a success message.
	 *
	 * @param {Object} data Payload from the test-connection endpoint.
	 */
	function refreshStatusCard(data) {
		var $card = $('.couverty-status');
		if (!$card.length) {
			return;
		}

		$card.removeClass('couverty-status--warning couverty-status--error').addClass('couverty-status--connected');
		$card.find('.couverty-status-indicator').removeClass('pending disconnected').addClass('connected');

		if (data.restaurant_name) {
			$card
				.find('.couverty-status__title')
				.html($('<span class="couverty-status-indicator connected"></span>'))
				.append(document.createTextNode(i18n.connectedTo.replace('%s', data.restaurant_name)));
		}

		if (data.counts) {
			$card.find('.couverty-status__meta').text(formatCounts(data.counts));
		}

		$card.find('.notice-error').remove();
	}

	function post(action, data, done, onSuccess, timeoutMessage) {
		$.ajax({
			url: couverty.ajax_url,
			type: 'POST',
			timeout: 60000,
			data: $.extend({ action: action, nonce: couverty.nonce }, data || {}),
			success: function (response) {
				if (response.success) {
					onSuccess(response.data);
				} else {
					showResult(response.data || i18n.networkError, false);
				}
			},
			error: function (jqXHR, textStatus) {
				showResult(
					textStatus === 'timeout' && timeoutMessage ? timeoutMessage : i18n.networkError,
					false
				);
			},
			complete: done,
		});
	}

	function testConnection(done) {
		post(
			'couverty_test_connection',
			{ api_key: $('#couverty_api_key').val() },
			done,
			function (data) {
				var message = i18n.connected;

				if (data.restaurant_name) {
					message += ' — ' + data.restaurant_name;
				}
				if (data.slug) {
					$('#couverty_slug').val(data.slug);
				}
				if (data.synced) {
					message += ' (' + formatCounts(data.counts) + ')';
				} else if (data.sync_error) {
					message += ' — ' + data.sync_error;
				}

				refreshStatusCard(data);
				showResult(message, true);
			}
		);
	}

	function syncData(done) {
		post(
			'couverty_sync_data',
			{},
			done,
			function (data) {
				$('#couverty-counts').text(formatCounts(data.counts));
				showResult(data.message + ' ' + formatCounts(data.counts), true);
			},
			i18n.syncTimeout
		);
	}

	function clearCache(done) {
		post('couverty_clear_cache', {}, done, function (data) {
			showResult(data, true);
		});
	}

	function createPages(done) {
		post('couverty_create_pages', {}, done, function (data) {
			showResult(data.message, true);
			// The table of pages is rendered server-side; reload so it reflects
			// what was just created rather than duplicating the markup here.
			setTimeout(function () {
				window.location.reload();
			}, 1200);
		});
	}

	/**
	 * Click-to-copy for shortcodes, field names and endpoints.
	 */
	function bindCopyButtons() {
		$(document).on('click', '.couverty-copy', function () {
			var $btn = $(this);
			var value = $btn.data('copy');
			var $hint = $btn.find('.couverty-copy__hint');

			var confirm = function () {
				$btn.addClass('is-copied');
				$hint.text(i18n.copied);
				setTimeout(function () {
					$btn.removeClass('is-copied');
					$hint.text(i18n.copy);
				}, 1500);
			};

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(value).then(confirm);
				return;
			}

			// http:// staging sites don't get the async clipboard API.
			var $tmp = $('<textarea>').val(value).css({ position: 'fixed', opacity: 0 }).appendTo('body');
			$tmp[0].select();
			try {
				document.execCommand('copy');
				confirm();
			} finally {
				$tmp.remove();
			}
		});
	}
})(jQuery);

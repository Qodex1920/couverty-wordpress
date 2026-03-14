(function($) {
	'use strict';

	$(document).ready(function() {
		// Test connection button
		$('#couverty-test-connection').on('click', function(e) {
			e.preventDefault();
			testConnection();
		});

		// Clear cache button
		$('#couverty-clear-cache').on('click', function(e) {
			e.preventDefault();
			clearCache();
		});

		// Sync data button
		$('#couverty-sync-data').on('click', function(e) {
			e.preventDefault();
			syncData();
		});

		function showResult(message, isSuccess) {
			var $result = $('#couverty-test-result');
			var $div = $('<div/>')
				.addClass('couverty-test-result')
				.addClass(isSuccess ? 'success' : 'error')
				.text(message);
			$result.empty().append($div).show();
		}

		function testConnection() {
			var btn = $('#couverty-test-connection');
			var originalText = btn.text();
			btn.prop('disabled', true).text('Test en cours...');

			$.ajax({
				url: couverty.ajax_url,
				type: 'POST',
				timeout: 60000,
				data: {
					action: 'couverty_test_connection',
					nonce: couverty.nonce,
					api_key: $('input[name="couverty_settings[api_key]"]').val(),
				},
				success: function(response) {
					if (response.success) {
						var msg = 'Connexion réussie !';
						if (response.data.restaurant_name) {
							msg += ' Restaurant : ' + response.data.restaurant_name;
						}
						if (response.data.slug) {
							msg += ' (Slug : ' + response.data.slug + ')';
							$('input[name="couverty_settings[slug]"]').val(response.data.slug);
						}
						if (response.data.synced) {
							msg += ' — Données synchronisées : ' + response.data.plats + ' plats, '
								+ response.data.boissons + ' boissons';
						}
						showResult(msg, true);
					} else {
						showResult(response.data || 'Connexion échouée', false);
					}
				},
				error: function() {
					showResult('Erreur de communication avec le serveur', false);
				},
				complete: function() {
					btn.prop('disabled', false).text(originalText);
				},
			});
		}

		function clearCache() {
			var btn = $('#couverty-clear-cache');
			var originalText = btn.text();
			btn.prop('disabled', true).text('Vidage en cours...');

			$.ajax({
				url: couverty.ajax_url,
				type: 'POST',
				data: {
					action: 'couverty_clear_cache',
					nonce: couverty.nonce,
				},
				success: function(response) {
					showResult(
						response.success ? response.data : (response.data || 'Échec'),
						response.success
					);
				},
				error: function() {
					showResult('Erreur de communication avec le serveur', false);
				},
				complete: function() {
					btn.prop('disabled', false).text(originalText);
				},
			});
		}

		function syncData() {
			var btn = $('#couverty-sync-data');
			var originalText = btn.text();
			btn.prop('disabled', true).text('Synchronisation en cours...');

			$.ajax({
				url: couverty.ajax_url,
				type: 'POST',
				timeout: 60000,
				data: {
					action: 'couverty_sync_data',
					nonce: couverty.nonce,
				},
				success: function(response) {
					if (response.success) {
						var msg = response.data.message;
						msg += ' (' + response.data.plats + ' plats, '
							+ response.data.boissons + ' boissons, '
							+ response.data.menus + ' menus)';
						showResult(msg, true);
					} else {
						showResult(response.data || 'Synchronisation échouée', false);
					}
				},
				error: function() {
					showResult('La synchronisation a échoué (délai d\'attente dépassé ?)', false);
				},
				complete: function() {
					btn.prop('disabled', false).text(originalText);
				},
			});
		}
	});
})(jQuery);

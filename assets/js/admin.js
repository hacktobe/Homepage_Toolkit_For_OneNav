(function ($) {
	'use strict';

	function setMessage($element, message, type) {
		$element.removeClass('is-success is-error').addClass(type === 'success' ? 'is-success' : 'is-error').text(message);
	}

	function requestExport($wrap, callback) {
		var $textarea = $wrap.find('.htfo-export-data');
		var $status = $wrap.find('.htfo-export-status');
		$status.text(htfoBackup.loading);
		$textarea.prop('disabled', true);

		$.post(htfoBackup.ajaxUrl, {
			action: 'htfo_export_settings',
			nonce: $wrap.data('nonce')
		}).done(function (response) {
			if (!response.success || !response.data) {
				$status.text(htfoBackup.loadFailed);
				if (typeof callback === 'function') {
					callback(null);
				}
				return;
			}
			var json = JSON.stringify(response.data, null, 2);
			$textarea.val(json);
			$status.text('');
			if (typeof callback === 'function') {
				callback(json);
			}
		}).fail(function () {
			$status.text(htfoBackup.networkFailed);
			if (typeof callback === 'function') {
				callback(null);
			}
		}).always(function () {
			$textarea.prop('disabled', false);
		});
	}

	$(function () {
		var $wrap = $('.htfo-backup');
		if (!$wrap.length) {
			return;
		}

		requestExport($wrap);

		$wrap.on('click', '.htfo-refresh', function () {
			requestExport($wrap);
		});

		$wrap.on('click', '.htfo-copy', function () {
			var $button = $(this);
			$button.prop('disabled', true).text(htfoBackup.fetching);
			requestExport($wrap, function (json) {
				if (!json) {
					$button.text(htfoBackup.copy).prop('disabled', false);
					return;
				}
				var finish = function () {
					$button.text(htfoBackup.copied);
					window.setTimeout(function () {
						$button.text(htfoBackup.copy).prop('disabled', false);
					}, 1600);
				};

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(json).then(finish).catch(function () {
						$wrap.find('.htfo-export-data').trigger('select');
						document.execCommand('copy');
						finish();
					});
				} else {
					$wrap.find('.htfo-export-data').trigger('select');
					document.execCommand('copy');
					finish();
				}
			});
		});

		$wrap.on('click', '.htfo-download', function () {
			var $button = $(this);
			$button.prop('disabled', true).text(htfoBackup.fetching);
			requestExport($wrap, function (json) {
				if (!json) {
					$button.text(htfoBackup.download).prop('disabled', false);
					return;
				}
				var url = URL.createObjectURL(new Blob([json], { type: 'application/json' }));
				var link = document.createElement('a');
				link.href = url;
				link.download = 'ywdjdh-homepage-toolkit-for-onenav-' + new Date().toISOString().slice(0, 10) + '.json';
				document.body.appendChild(link);
				link.click();
				link.remove();
				URL.revokeObjectURL(url);
				$button.text(htfoBackup.download).prop('disabled', false);
			});
		});

		$wrap.on('click', '.htfo-import', function () {
			var $button = $(this);
			var $message = $wrap.find('.htfo-backup-message');
			var json = $wrap.find('.htfo-import-data').val().trim();

			if (!json) {
				setMessage($message, htfoBackup.emptyImport, 'error');
				return;
			}

			try {
				JSON.parse(json);
			} catch (error) {
				setMessage($message, htfoBackup.invalidJson, 'error');
				return;
			}

			$button.prop('disabled', true).text(htfoBackup.importing);
			$message.removeClass('is-success is-error').hide().text('');

			$.post(htfoBackup.ajaxUrl, {
				action: 'htfo_import_settings',
				nonce: $wrap.data('nonce'),
				data: json
			}).done(function (response) {
				if (response.success) {
					setMessage($message, htfoBackup.imported, 'success');
					window.setTimeout(function () { window.location.reload(); }, 1200);
					return;
				}
				setMessage($message, response.data || htfoBackup.importFailed, 'error');
				$button.prop('disabled', false).text(htfoBackup.import);
			}).fail(function (xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : htfoBackup.networkFailed;
				setMessage($message, message, 'error');
				$button.prop('disabled', false).text(htfoBackup.import);
			});
		});
	});
}(jQuery));

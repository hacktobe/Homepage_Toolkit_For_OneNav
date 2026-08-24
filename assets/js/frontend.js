(function ($) {
	'use strict';

	var config = window.htfoDock || {};
	var storageKey = config.storageKey || 'htfo_dock_custom_links';
	var legacyStorageKey = config.legacyStorageKey || '';
	var deleteIndex = -1;
	var previousFocus = null;

	function readStoredLinks() {
		var raw = null;
		try {
			raw = window.localStorage.getItem(storageKey);
			if (raw === null && legacyStorageKey) {
				raw = window.localStorage.getItem(legacyStorageKey);
			}
			var parsed = raw ? JSON.parse(raw) : [];
			return Array.isArray(parsed) ? parsed : [];
		} catch (error) {
			return [];
		}
	}

	function storeLinks(links) {
		try {
			window.localStorage.setItem(storageKey, JSON.stringify(links));
			return true;
		} catch (error) {
			return false;
		}
	}

	function normalizeHttpUrl(raw) {
		try {
			var url = new URL(String(raw || '').trim());
			return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
		} catch (error) {
			return '';
		}
	}

	function hostname(raw) {
		try {
			return new URL(raw).hostname || '';
		} catch (error) {
			return '';
		}
	}

	function firstCharacter(name, url) {
		var value = String(name || '').trim() || hostname(url);
		var character = value ? value.charAt(0) : '?';
		return /[a-z]/i.test(character) ? character.toUpperCase() : character;
	}

	function hashString(value) {
		var hash = 0;
		var string = String(value || 'onenav');
		for (var index = 0; index < string.length; index += 1) {
			hash = ((hash << 5) - hash) + string.charCodeAt(index);
			hash |= 0;
		}
		return Math.abs(hash);
	}

	function hslToHex(hue, saturation, lightness) {
		var s = saturation / 100;
		var l = lightness / 100;
		var chroma = (1 - Math.abs((2 * l) - 1)) * s;
		var x = chroma * (1 - Math.abs(((hue / 60) % 2) - 1));
		var m = l - (chroma / 2);
		var rgb = [0, 0, 0];

		if (hue < 60) { rgb = [chroma, x, 0]; }
		else if (hue < 120) { rgb = [x, chroma, 0]; }
		else if (hue < 180) { rgb = [0, chroma, x]; }
		else if (hue < 240) { rgb = [0, x, chroma]; }
		else if (hue < 300) { rgb = [x, 0, chroma]; }
		else { rgb = [chroma, 0, x]; }

		return '#' + rgb.map(function (component) {
			return Math.round((component + m) * 255).toString(16).padStart(2, '0');
		}).join('');
	}

	function textAvatar(name, url) {
		var hue = hashString(hostname(url) || name) % 360;
		var c1 = hslToHex(hue, 75, 52);
		var c2 = hslToHex((hue + 28) % 360, 80, 45);
		var character = firstCharacter(name, url)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">' +
			'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">' +
			'<stop offset="0%" stop-color="' + c1 + '"/><stop offset="100%" stop-color="' + c2 + '"/>' +
			'</linearGradient></defs><rect width="50" height="50" rx="12" fill="url(#g)"/>' +
			'<text x="25" y="27" text-anchor="middle" dominant-baseline="middle" ' +
			'font-family="system-ui,sans-serif" font-size="22" font-weight="700" fill="#fff">' + character + '</text></svg>';
		return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
	}

	function addTooltip($icon) {
		var title = String($icon.attr('data-title') || '').trim();
		if (title && !$icon.find('.htfo-tooltip').length) {
			$('<div>').addClass('htfo-tooltip').text(title).appendTo($icon);
		}
		$icon.addClass('htfo-hovered');
	}

	function removeTooltip($icon) {
		$icon.find('.htfo-tooltip').remove();
		$icon.removeClass('htfo-hovered');
	}

	function bindTooltips() {
		$('.htfo-dock-icon').off('.htfoTooltip')
			.on('mouseenter.htfoTooltip focusin.htfoTooltip', function () { addTooltip($(this)); })
			.on('mouseleave.htfoTooltip focusout.htfoTooltip', function () { removeTooltip($(this)); });
	}

	function renderCustomLinks() {
		$('.htfo-custom-icon').remove();
		readStoredLinks().forEach(function (link, index) {
			var name = String(link && link.name ? link.name : '').trim().slice(0, 100);
			var url = normalizeHttpUrl(link && link.url ? link.url : '');
			if (!name || !url) {
				return;
			}

			var iconUrl = normalizeHttpUrl(link && link.icon ? link.icon : '');
			var fallback = textAvatar(name, url);
			var $item = $('<li>').addClass('htfo-dock-icon htfo-custom-icon').attr({
				'data-index': index,
				'data-title': name
			});
			var $link = $('<a>').attr({ href: url, target: '_blank', rel: 'noopener noreferrer' });
			var $image = $('<img>').attr({ src: iconUrl || fallback, alt: name, loading: 'lazy' });

			if (iconUrl) {
				$image.one('error', function () { $(this).attr('src', fallback); });
			}

			$link.append($image);
			$item.append($link).insertBefore('.htfo-dock-divider');
		});
		bindTooltips();
	}

	function openOverlay($overlay, focusSelector) {
		previousFocus = document.activeElement;
		$overlay.prop('hidden', false).hide().fadeIn(150, function () {
			$overlay.find(focusSelector).trigger('focus');
		});
	}

	function closeOverlay($overlay) {
		$overlay.fadeOut(150, function () {
			$overlay.prop('hidden', true);
			if (previousFocus) {
				$(previousFocus).trigger('focus');
			}
		});
	}

	$(function () {
		var platform = (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '';
		if (/Win/i.test(String(platform))) {
			$('body').addClass('htfo-windows');
		}

		var $addOverlay = $('#htfo-add-overlay');
		var $deleteOverlay = $('#htfo-delete-overlay');
		renderCustomLinks();

		$('#htfo-collapse-button').on('click', function () {
			$('.htfo-dock-container').fadeOut(200, function () { $('#htfo-expand-dock').fadeIn(150); });
		});

		$('#htfo-expand-dock').on('click', function () {
			$(this).fadeOut(150, function () { $('.htfo-dock-container').fadeIn(200); });
		});

		$('#htfo-add-button').on('click', function () {
			$('#htfo-link-form').get(0).reset();
			$('.htfo-form-error').text('');
			openOverlay($addOverlay, '#htfo-add-url');
		});

		$('#htfo-modal-close').on('click', function () { closeOverlay($addOverlay); });
		$('#htfo-cancel-delete').on('click', function () {
			deleteIndex = -1;
			closeOverlay($deleteOverlay);
		});

		$('.htfo-modal-overlay').on('click', function (event) {
			if (event.target === this) {
				closeOverlay($(this));
			}
		});

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape') {
				$('.htfo-modal-overlay:visible').each(function () { closeOverlay($(this)); });
			}
		});

		$('#htfo-link-form').on('submit', function (event) {
			event.preventDefault();
			var url = normalizeHttpUrl($('#htfo-add-url').val());
			var name = String($('#htfo-add-name').val() || '').trim().slice(0, 100);
			var rawIcon = String($('#htfo-add-icon').val() || '').trim();
			var icon = rawIcon ? normalizeHttpUrl(rawIcon) : '';

			if (!url || !name || (rawIcon && !icon)) {
				$('.htfo-form-error').text(config.invalidUrl || 'Enter a valid HTTP or HTTPS URL.');
				return;
			}

			var links = readStoredLinks();
			links.push({ url: url, name: name, icon: icon });
			if (storeLinks(links)) {
				renderCustomLinks();
				closeOverlay($addOverlay);
			}
		});

		function requestDelete(index) {
			deleteIndex = Number(index);
			if (Number.isInteger(deleteIndex) && deleteIndex >= 0) {
				openOverlay($deleteOverlay, '#htfo-confirm-delete');
			}
		}

		$(document).on('contextmenu', '.htfo-custom-icon', function (event) {
			event.preventDefault();
			requestDelete($(this).attr('data-index'));
		});

		$(document).on('keydown', '.htfo-custom-icon a', function (event) {
			if (event.shiftKey && (event.key === 'Delete' || event.key === 'Backspace')) {
				event.preventDefault();
				requestDelete($(this).closest('.htfo-custom-icon').attr('data-index'));
			}
		});

		$('#htfo-confirm-delete').on('click', function () {
			var links = readStoredLinks();
			if (deleteIndex >= 0 && deleteIndex < links.length) {
				links.splice(deleteIndex, 1);
				storeLinks(links);
				renderCustomLinks();
			}
			deleteIndex = -1;
			closeOverlay($deleteOverlay);
		});
	});
}(jQuery));

=== YWDJDH Homepage Toolkit for OneNav ===
Contributors: luckydog007
Tags: onenav, homepage, dropdown menu, navigation, dock
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add configurable homepage dropdown menus and an optional desktop dock to the OneNav theme.

== Description ==

YWDJDH Homepage Toolkit for OneNav extends the OneNav theme with focused homepage navigation tools:

* Configurable dropdown menus in the desktop homepage header.
* Theme icons or administrator-uploaded custom icons.
* An optional desktop dock with up to ten administrator-configured links.
* Browser-local custom dock links for individual visitors.
* JSON export and import for the plugin's own settings.
* Dark mode and reduced-motion support.

The plugin requires the OneNav theme or a OneNav child theme. OneNav is a third-party theme and is not bundled with this plugin. This plugin is an independent extension and is not endorsed by or affiliated with the OneNav theme author.

No tracking, telemetry, advertising, or automatic third-party requests are included.

== Installation ==

1. Install and activate the OneNav theme or a OneNav child theme.
2. Upload the `ywdjdh-homepage-toolkit-for-onenav` folder to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New > Upload Plugin.
3. Activate YWDJDH Homepage Toolkit for OneNav.
4. Open the OneNav Theme Settings page, then configure Homepage Toolkit.

Do not activate this plugin at the same time as a private predecessor that renders the same dropdown or dock features. On first activation, compatible predecessor settings are copied when the new settings are still empty.

== Frequently Asked Questions ==

= Does this plugin work with other themes? =

No. It integrates with hooks and the settings framework provided by OneNav. If OneNav is not active, the plugin remains inactive and shows an administrator notice.

= Where are visitor-created dock links stored? =

They are stored in the visitor's browser using localStorage. They are not sent to WordPress or to the plugin author.

= Does the plugin contact an external icon service? =

No. When a visitor does not provide an icon URL, the plugin creates a text icon locally in the browser. If a site administrator or visitor explicitly enters a remote image URL, the visitor's browser will request that image from the specified host under that host's privacy policy.

= What happens when I uninstall the plugin? =

The plugin removes its own OneNav theme settings. Browser-local custom dock links remain in each visitor's browser because WordPress cannot delete another person's browser storage during server-side uninstall.

== Privacy ==

The plugin does not collect or transmit personal data and does not include telemetry.

Visitor-created dock links (URL, label, and optional icon URL) are saved only in that visitor's browser localStorage. If a remote icon URL is explicitly supplied, the browser requests the image directly from that third-party host. Site owners should describe any remote resources they configure in their own privacy policy.

== Support ==

For project information and support resources, visit the [YWDJDH Homepage Toolkit for OneNav plugin page](https://ywdjdh.com/homepage-toolkit-for-onenav).

== Changelog ==

= 1.0.0 =

* Initial public release.
* Added configurable homepage dropdown menus.
* Added an optional desktop dock with browser-local custom links.
* Added protected JSON settings import and export.
* Added local text-icon fallback with no automatic third-party favicon request.

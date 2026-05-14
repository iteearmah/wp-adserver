=== AdServer ===
Contributors: iteearmah
Tags: ads, adserver, advertisement, ad-management, geo-targeting, ad-rotation, tracking, impressions, clicks, ad-zones, scheduling, device-targeting
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.5.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage, rotate, track, and serve ads with geo-targeting, device targeting, zones, scheduling, and detailed analytics.

== Description ==

AdServer is a powerful and lightweight advertisement management system for WordPress. It allows you to create, manage, and track advertisements with ease.

**Key Features:**

* **Custom Ad Management** — Create ads as custom post types with full control.
* **Ad Rotation** — Weighted rotation system to serve ads based on priority.
* **Impression & Click Tracking** — Detailed tracking with 7-day statistics and analytics dashboard.
* **Geo-Targeting** — Include or exclude ads based on the visitor's country.
* **Device Targeting** — Target ads by device type (Mobile, Tablet, Desktop).
* **Ad Zones** — Group ads into zones for targeted placement.
* **Scheduling** — Set start and end dates for your ad campaigns.
* **Capping** — Set impression and click limits for each ad.
* **Duplicate Ads** — Quickly clone existing advertisements with one click.
* **Ad Status Toggle** — Easily enable or disable ads without deleting them.
* **Dashboard Widget** — Quick overview of total impressions, clicks, and CTR.
* **Export/Import** — Backup or migrate your advertisements and settings via JSON.
* **User Access Control** — Granular control over who can manage ads.
* **Analytics Reports** — Professional reporting dashboard with charts and CSV export.
* **AJAX Serving** — Non-blocking, asynchronous ad delivery via WordPress AJAX.
* **Shortcode Support** — Place ads using shortcodes or script tags.

**Dependencies:**

This plugin requires the [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) (formerly ACF) plugin to be installed and active. An admin notice will be displayed if the dependency is missing.

== Installation ==

1. Upload the `wp-adserver` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Install and activate the [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) plugin.
4. Go to **AdServer** in your admin menu to start creating ads.

== Frequently Asked Questions ==

= How do I display an ad? =

Use the shortcode `[wp_adserver zone="your-zone-slug"]` in any post or page. To find the zone slug, go to **AdServer > Ad Zones**.

= How do I track statistics? =

Statistics for each ad are displayed in the "Ad Statistics" meta box when editing an ad, as well as in the main Ads list view.

= How do I use Ad Zones? =

1. Go to **AdServer > Ad Zones** and create a new zone (e.g., "Sidebar").
2. Note the **Slug** of the zone you created.
3. Edit an advertisement and select the zone from the **Ad Zones** box on the right.
4. Use the shortcode `[wp_adserver zone="sidebar"]` (replace "sidebar" with your slug) to display ads from that zone.
5. You can also use `<div id="wp-ad-sidebar"></div><script src="https://your-site.com/?wp_ad_serve=1&zone=sidebar&uid=wp-ad-sidebar" async></script>` for remote placement.

= Does this plugin require any external services? =

No. Geo-targeting relies on server-provided HTTP headers (e.g., `HTTP_X_COUNTRY_CODE`) typically set by your hosting provider or a CDN like Cloudflare. No external API calls are made.

= What happens if Secure Custom Fields is not installed? =

An admin notice will be displayed prompting you to install the required plugin. Core ad management features will not function without it.

== Screenshots ==

1. Ad management list view with status indicators and quick stats.
2. Ad editor with scheduling, capping, geo-targeting, and device targeting options.
3. Ad Zones management for organizing ads by placement.
4. Analytics reporting dashboard with charts and CSV export.
5. Dashboard widget showing quick ad performance stats.

== Changelog ==

= 1.5.0 =
* Fixed automated plugin scan issues: added sanitize_callback to register_setting() calls.
* Updated "Tested up to" to WordPress 6.9.
* Changed Text Domain to "adserver" to match plugin slug.
* Created languages directory for Domain Path compliance.

= 1.4.0 =
* Renamed plugin display name from "WP AdServer" to "AdServer" for WordPress.org directory compliance.

= 1.3.0 =
* Security audit: added input sanitization, output escaping, and URL validation across all files.
* Hardened click redirect with wp_http_validate_url() and wp_safe_redirect() fallback.
* Secured import functionality with uploaded file checks and post data sanitization.
* Replaced json_encode with wp_json_encode and date() with gmdate() for best practices.
* Added Text Domain and Domain Path headers for i18n compliance.
* Added proper cleanup of tracking table and transients on uninstall.
* Updated git configuration files (.gitignore, .gitattributes, .distignore, .editorconfig).

= 1.2.0 =
* Fixed ad serving reliability for non-public post types in AJAX contexts using direct database queries.
* Improved cache invalidation with versioned transients and hooks for all ad status changes.
* Enhanced administrator debug output with visitor context, filtering reasons, and ad status details.
* Standardized zone slug handling to lowercase across shortcodes and AJAX handlers.
* Added .gitattributes with export-ignore directives for clean distribution archives.

= 1.1.0 =
* Added professional analytics reporting dashboard with interactive charts.
* Added CSV export functionality for reporting data.
* Migrated tracking to a custom database table for high-traffic performance.
* Added duplicate ad, ad status toggle, and dashboard stats widget.
* Added nonce verification and capability checks for settings.
* Improved input sanitization and output escaping.
* Added translation wrappers for core strings.
* Refined admin notices and simplified the readme changelog.
* Updated Access Configuration with user selection and SCF dependency enforcement.

= 1.0.0 =
* Initial release.
* Modular architecture for better maintainability.
* Custom post type `wp_ad` for structured ad management.
* Weighted rotation system using `[wp_adserver]` shortcode.
* Advanced geo-targeting capabilities.
* Ad scheduling and performance capping (impressions/clicks).
* Zone-based ad delivery.
* Detailed impression and click tracking with admin dashboard statistics.

== Upgrade Notice ==

= 1.5.0 =
Fixes all WordPress.org automated plugin scan issues. Recommended for all users.

= 1.4.0 =
Plugin renamed to "AdServer" for WordPress.org compliance. No functional changes.

= 1.3.0 =
Security hardening release with input sanitization, output escaping, and WordPress best practices. Recommended for all users.

= 1.2.0 =
Fixes ad serving reliability in AJAX contexts and improves cache invalidation. Recommended for all users.

= 1.1.0 =
Major feature update with analytics, export/import, and security improvements.

= 1.0.0 =
Initial release.

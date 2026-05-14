=== WP AdServer ===
Contributors: Junie
Tags: ads, adserver, advertisements, tracking, geo-targeting, rotation
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A specialized WordPress plugin to manage, rotate, track, and serve advertisements with advanced features like Geo-targeting and Zone-based delivery.

== Description ==

WP AdServer is a powerful and lightweight advertisement management system for WordPress. It allows you to create, manage, and track advertisements with ease.

Key features include:
* **Custom Ad Management:** Create ads as custom post types.
* **Ad Rotation:** Weighted rotation system to serve ads based on priority.
* **Tracking:** Detailed tracking of impressions and clicks with 7-day statistics.
* **Geo-Targeting:** Include or exclude ads based on the visitor's country.
* **Device Targeting:** Target ads based on the visitor's device (Mobile, Tablet, Desktop).
* **Ad Zones:** Group ads into zones for targeted placement.
* **Scheduling:** Set start and end dates for your ad campaigns.
* **Capping:** Set impression and click limits for each ad.
* **Duplicate Ads:** Quickly clone existing advertisements with one click.
* **Ad Status Toggle:** Easily enable or disable ads without deleting them.
* **Dashboard Widget:** Quick overview of total impressions, clicks, and CTR on your WordPress dashboard.
* **Export/Import:** Backup or migrate your advertisements and settings via JSON files.
* **User Access Control:** Granular control over who can manage ads.
* **Analytics Reports:** Professional reporting dashboard with charts and CSV export.
* **AJAX Serving:** Uses native WordPress AJAX for non-blocking, asynchronous ad delivery.
* **Shortcode Support:** Easily place ads using shortcodes or script tags.

**Note:** This plugin requires the [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) plugin to be installed and active.

== Installation ==

1. Upload the `wp-adserver` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure "Secure Custom Fields" is also installed and active.
4. Go to 'WP AdServer' in your admin menu to start creating ads.

== Frequently Asked Questions ==

= How do I display an ad? =
Use the shortcode `[wp_adserver zone="your-zone-slug"]` in any post or page. To find the zone slug, go to **WP AdServer > Ad Zones**.

= How do I track statistics? =
Statistics for each ad are displayed in the "Ad Statistics" meta box when editing an ad, as well as in the main Ads list view.

= How do I use Ad Zones? =
1. Go to **WP AdServer > Ad Zones** and create a new zone (e.g., "Sidebar").
2. Note the **Slug** of the zone you created.
3. Edit an advertisement and select the zone from the **Ad Zones** box on the right.
4. Use the shortcode `[wp_adserver zone="sidebar"]` (replace "sidebar" with your slug) to display ads from that zone.
5. You can also use `<div id="wp-ad-sidebar"></div><script src="https://your-site.com/?wp_ad_serve=1&zone=sidebar&uid=wp-ad-sidebar" async></script>` for remote placement.

== Changelog ==

= 1.2.0 =
* Fixed ad serving reliability for non-public post types in AJAX contexts using direct database queries.
* Improved cache invalidation with versioned transients and hooks for all ad status changes.
* Enhanced administrator debug output with visitor context, filtering reasons, and ad status details.
* Standardized zone slug handling to lowercase across shortcodes and AJAX handlers.
* Added .gitattributes with export-ignore directives for clean distribution archives.

= 1.1.0 =
* Security: Added nonce verification and capability checks for settings.
* Best Practice: Improved input sanitization and output escaping.
* I18n: Added translation wrappers for core strings.
* UI/UX: Refined admin notices and simplified the readme changelog.
* Updated Access Configuration with user selection and SCF dependency enforcement.
* Added "Duplicate Ad" functionality to the Ads list view.
* Added "Active" toggle for advertisements.
* Added "WP AdServer Quick Stats" Dashboard Widget.
* Added Export/Import tools for advertisements and settings.
* Improved Ads list table with an "Active" status indicator.

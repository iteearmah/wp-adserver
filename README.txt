=== WP AdServer ===
Contributors: Junie
Tags: ads, adserver, advertisements, tracking, geo-targeting, rotation
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.1.0
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
* **Ad Zones:** Group ads into zones for targeted placement.
* **Scheduling:** Set start and end dates for your ad campaigns.
* **Capping:** Set impression and click limits for each ad.
* **User Access Control:** Granular control over who can manage ads.
* **Shortcode Support:** Easily place ads using shortcodes or script tags.

**Note:** This plugin requires the [Secure Custom Fields](https://wordpress.org/plugins/smart-custom-fields/) plugin to be installed and active.

== Installation ==

1. Upload the `wp-adserver` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure "Secure Custom Fields" is also installed and active.
4. Go to 'WP AdServer' in your admin menu to start creating ads.

== Frequently Asked Questions ==

= How do I display an ad? =
Use the shortcode `[wp_adserver zone="your-zone-slug"]` in any post or page.

= How do I track statistics? =
Statistics for each ad are displayed in the "Ad Statistics" meta box when editing an ad, as well as in the main Ads list view.

== Changelog ==

= 1.1.0 =
* Improved Access Configuration with user selection.
* Added dependency enforcement for SCF.
* UI/UX enhancements.

= 1.0.0 =
* Initial release.
* Modular architecture.
* Scheduling, Capping, and Geo-targeting.

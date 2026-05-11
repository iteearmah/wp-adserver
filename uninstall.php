<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following:
 * - This file should be as self-contained as possible.
 * - Only direct queries to the database should be used.
 * - This file should not include any other files from the plugin.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up the database
 */
global $wpdb;

// 1. Delete all ads (custom post type)
$ads = get_posts( array(
	'post_type'   => 'wp_ad',
	'numberposts' => -1,
	'post_status' => 'any',
) );

foreach ( $ads as $ad ) {
	wp_delete_post( $ad->ID, true );
}

// 2. Delete all options registered by the plugin
$options = array(
	'wp_adserver_role_caps',
	'wp_adserver_allowed_users',
	'options_wp_adserver_allowed_users_list', // SCF Option
	'_options_wp_adserver_allowed_users_list', // SCF Option Hidden
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// 3. Delete any extra metadata if necessary (though wp_delete_post handles most)
// If we had custom tables, we would drop them here.

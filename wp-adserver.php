<?php

/*
Plugin Name: WP AdServer
Plugin URI: https://github.com/iteearmah/wp-adserver
Description: A specialized plugin to manage, rotate, track, and serve advertisements.
Version: 1.1.0
Author: Samuel Attoh Armah
Author URI: https://github.com/iteearmah
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the modular system
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-adserver-loader.php';

/**
 * Check if Secure Custom Fields is active
 */
function wp_adserver_check_dependencies() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		add_action( 'admin_notices', 'wp_adserver_scf_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Display admin notice if SCF is missing
 */
function wp_adserver_scf_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( $screen->parent_base === 'edit.php?post_type=wp_ad' || $screen->id === 'plugins' ) {
		?>
		<div class="notice notice-error">
			<p><?php _e( '<strong>WP AdServer</strong> requires the <strong>Secure Custom Fields</strong> (formerly ACF) plugin to be installed and active for full functionality.', 'wp-adserver' ); ?></p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=secure+custom+fields' ) ); ?>" class="button button-primary"><?php _e( 'Install Secure Custom Fields', 'wp-adserver' ); ?></a>
			</p>
		</div>
		<?php
	}
}

/**
 * Initialize the plugin
 */
function wp_adserver_init() {
	wp_adserver_check_dependencies();
	new WP_AdServer_Loader();
}

wp_adserver_init();
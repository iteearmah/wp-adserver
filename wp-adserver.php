<?php

/*
Plugin Name: AdServer
Plugin URI: https://github.com/iteearmah/wp-adserver
Description: A specialized plugin to manage, rotate, track, and serve advertisements.
Version: 1.6.0
Author: Samuel Attoh Armah
Author URI: https://github.com/iteearmah
License: GPL2
Text Domain: adserver
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_ADSERVER_VERSION', '1.6.0' );

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
	if ( ! $screen || ( $screen->parent_base !== 'edit.php?post_type=wp_ad' && $screen->id !== 'plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo wp_kses_post( __( '<strong>AdServer</strong> requires the <strong>Secure Custom Fields</strong> (formerly ACF) plugin to be installed and active for full functionality.', 'adserver' ) ); ?></p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=secure+custom+fields' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Install Secure Custom Fields', 'adserver' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin
 */
function wp_adserver_init() {
	wp_adserver_check_dependencies();
	new WP_AdServer_Loader();
}

/**
 * Activate the plugin
 */
function wp_adserver_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-adserver-tracking.php';
	WP_AdServer_Tracking::create_tables();
}
register_activation_hook( __FILE__, 'wp_adserver_activate' );

wp_adserver_init();

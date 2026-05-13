<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Loader {

	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-post-types.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-fields.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-tracking.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-renderer.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-access.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wp-adserver-reports.php';
	}

	private function init_components() {
		WP_AdServer_Post_Types::init();
		WP_AdServer_Fields::init();
		WP_AdServer_Tracking::init();
		WP_AdServer_Renderer::init();
		WP_AdServer_Admin::init();
		WP_AdServer_Access::init();
		WP_AdServer_Reports::init();
	}
}

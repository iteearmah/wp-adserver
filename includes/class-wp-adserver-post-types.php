<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
	}

	public static function register_post_type() {
		$labels = array(
			'name'               => 'Ads',
			'singular_name'      => 'Ad',
			'menu_name'          => 'AdServer',
			'name_admin_bar'     => 'Ad',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Ad',
			'new_item'           => 'New Ad',
			'edit_item'          => 'Edit Ad',
			'view_item'          => 'View Ad',
			'all_items'          => 'All Ads',
			'search_items'       => 'Search Ads',
			'parent_item_colon'  => 'Parent Ads:',
			'not_found'          => 'No ads found.',
			'not_found_in_trash' => 'No ads found in Trash.',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'wp-ad' ),
			'capability_type'    => 'ad',
			'map_meta_cap'       => true,
			'capabilities'       => array(
				'edit_post'              => 'edit_ad',
				'read_post'              => 'read_ad',
				'delete_post'            => 'delete_ad',
				'edit_posts'             => 'edit_ads',
				'edit_others_posts'      => 'edit_others_ads',
				'publish_posts'          => 'publish_ads',
				'read_private_posts'     => 'read_private_ads',
				'edit_private_posts'     => 'edit_private_ads',
				'edit_published_posts'   => 'edit_published_ads',
				'delete_posts'           => 'delete_ads',
				'delete_others_posts'    => 'delete_others_ads',
				'delete_private_posts'   => 'delete_private_ads',
				'delete_published_posts' => 'delete_published_ads',
				'create_posts'           => 'edit_ads',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'wp_ad', $args );
	}

	public static function register_taxonomies() {
		register_taxonomy( 'ad_zone', 'wp_ad', array(
			'label'             => 'Ad Zones',
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
		) );
	}
}

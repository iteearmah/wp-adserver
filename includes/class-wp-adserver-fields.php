<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Fields {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_fields' ) );
	}

	public static function register_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group( array(
			'key'      => 'group_wp_ad_details',
			'title'    => 'Ad Details',
			'fields'   => array(
				array(
					'key'           => 'field_wp_ad_type',
					'label'         => 'Ad Type',
					'name'          => 'wp_ad_type',
					'type'          => 'select',
					'choices'       => array(
						'image' => 'Image',
						'html'  => 'HTML/Code',
					),
					'default_value' => 'image',
				),
				array(
					'key'               => 'field_wp_ad_image',
					'label'             => 'Image',
					'name'              => 'wp_ad_image',
					'type'              => 'image',
					'return_format'     => 'url',
					'preview_size'      => 'medium',
					'library'           => 'all',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_wp_ad_type',
								'operator' => '==',
								'value'    => 'image',
							),
						),
					),
				),
				array(
					'key'               => 'field_wp_ad_destination_url',
					'label'             => 'Destination URL',
					'name'              => 'wp_ad_destination_url',
					'type'              => 'url',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_wp_ad_type',
								'operator' => '==',
								'value'    => 'image',
							),
						),
					),
				),
				array(
					'key'               => 'field_wp_ad_html_code',
					'label'             => 'HTML Code',
					'name'              => 'wp_ad_html_code',
					'type'              => 'textarea',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_wp_ad_type',
								'operator' => '==',
								'value'    => 'html',
							),
						),
					),
				),
				array(
					'key'           => 'field_wp_ad_weight',
					'label'         => 'Weight (1-10)',
					'name'          => 'wp_ad_weight',
					'type'          => 'number',
					'default_value' => 1,
					'min'           => 1,
					'max'           => 10,
				),
				array(
					'key'   => 'field_wp_ad_scheduling_heading',
					'label' => 'Scheduling & Limits',
					'type'  => 'accordion',
				),
				array(
					'key'   => 'field_wp_ad_start_date',
					'label' => 'Start Date',
					'name'  => 'wp_ad_start_date',
					'type'  => 'date_time_picker',
					'display_format' => 'Y-m-d H:i:s',
					'return_format'  => 'Y-m-d H:i:s',
				),
				array(
					'key'   => 'field_wp_ad_end_date',
					'label' => 'End Date',
					'name'  => 'wp_ad_end_date',
					'type'  => 'date_time_picker',
					'display_format' => 'Y-m-d H:i:s',
					'return_format'  => 'Y-m-d H:i:s',
				),
				array(
					'key'   => 'field_wp_ad_limit_impressions',
					'label' => 'Impression Limit',
					'name'  => 'wp_ad_limit_impressions',
					'type'  => 'number',
					'instructions' => 'Set to 0 or leave empty for no limit.',
				),
				array(
					'key'   => 'field_wp_ad_limit_clicks',
					'label' => 'Click Limit',
					'name'  => 'wp_ad_limit_clicks',
					'type'  => 'number',
					'instructions' => 'Set to 0 or leave empty for no limit.',
				),
				array(
					'key'   => 'field_wp_ad_geo_heading',
					'label' => 'Geo-Targeting',
					'type'  => 'accordion',
				),
				array(
					'key'           => 'field_wp_ad_geo_enabled',
					'label'         => 'Enable Geo-Targeting',
					'name'          => 'wp_ad_geo_enabled',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 0,
				),
				array(
					'key'               => 'field_wp_ad_geo_mode',
					'label'             => 'Geo Mode',
					'name'              => 'wp_ad_geo_mode',
					'type'              => 'select',
					'choices'           => array(
						'include' => 'Include selected countries',
						'exclude' => 'Exclude selected countries',
					),
					'default_value'     => 'include',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_wp_ad_geo_enabled',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
				),
				array(
					'key'               => 'field_wp_ad_geo_countries',
					'label'             => 'Countries (ISO codes, comma separated)',
					'name'              => 'wp_ad_geo_countries',
					'type'              => 'text',
					'placeholder'       => 'US, GB, CA',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_wp_ad_geo_enabled',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'wp_ad',
					),
				),
			),
		) );
		acf_add_local_field_group( array(
			'key'      => 'group_wp_ad_access_settings',
			'title'    => 'Access Settings',
			'fields'   => array(
				array(
					'key'           => 'field_wp_adserver_allowed_users_list',
					'label'         => 'Allowed Users',
					'name'          => 'wp_adserver_allowed_users_list',
					'type'          => 'user',
					'instructions'  => 'Select specific users who are allowed to manage advertisements.',
					'required'      => 0,
					'return_format' => 'id',
					'multiple'      => 1,
					'allow_null'    => 1,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'wp-adserver-access',
					),
				),
			),
		) );
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Renderer {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_shortcode( 'wp_adserver', array( __CLASS__, 'render_shortcode' ) );
		add_shortcode( 'wp_ad_script', array( __CLASS__, 'render_script_shortcode' ) );
	}

	public static function enqueue_scripts() {
		wp_enqueue_style( 'wp-adserver', plugins_url( 'assets/css/style.css', __FILE__ ) );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'zone' => '',
		), $atts );

		return self::render_ad( $atts['zone'] );
	}

	public static function render_script_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'zone' => '',
		), $atts );

		$url = add_query_arg( array(
			'wp_ad_serve' => 1,
			'zone'        => $atts['zone'],
		), home_url( '/' ) );

		return '<script src="' . esc_url( $url ) . '"></script>';
	}

	public static function render_ad( $zone_slug = '' ) {
		$args = array(
			'post_type'      => 'wp_ad',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		if ( ! empty( $zone_slug ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'ad_zone',
					'field'    => 'slug',
					'terms'    => $zone_slug,
				),
			);
		}

		$ads = get_posts( $args );
		if ( empty( $ads ) ) {
			return '';
		}

		$eligible_ads = array();
		$visitor_country = WP_AdServer_Tracking::get_visitor_country();
		$now = current_time( 'Y-m-d H:i:s' );

		foreach ( $ads as $ad ) {
			$ad_id = $ad->ID;

			// Check Scheduling
			$start_date = get_field( 'wp_ad_start_date', $ad_id );
			$end_date   = get_field( 'wp_ad_end_date', $ad_id );

			if ( $start_date && $now < $start_date ) continue;
			if ( $end_date && $now > $end_date ) continue;

			// Check Limits
			$limit_impressions = (int) get_field( 'wp_ad_limit_impressions', $ad_id );
			$limit_clicks      = (int) get_field( 'wp_ad_limit_clicks', $ad_id );

			if ( $limit_impressions > 0 ) {
				$current_imprs = (int) get_post_meta( $ad_id, '_wp_ad_impressions', true );
				if ( $current_imprs >= $limit_impressions ) continue;
			}
			if ( $limit_clicks > 0 ) {
				$current_clicks = (int) get_post_meta( $ad_id, '_wp_ad_clicks', true );
				if ( $current_clicks >= $limit_clicks ) continue;
			}

			// Check Geo
			$geo_enabled = get_field( 'wp_ad_geo_enabled', $ad_id );
			if ( $geo_enabled ) {
				$mode      = get_field( 'wp_ad_geo_mode', $ad_id );
				$countries = get_field( 'wp_ad_geo_countries', $ad_id );
				$country_list = is_array( $countries ) ? array_map( 'strtoupper', $countries ) : array_map( 'trim', explode( ',', strtoupper( $countries ) ) );

				if ( $mode === 'include' ) {
					if ( ! in_array( $visitor_country, $country_list ) ) continue;
				} else {
					if ( in_array( $visitor_country, $country_list ) ) continue;
				}
			}

			$weight = (int) get_field( 'wp_ad_weight', $ad_id ) ?: 1;
			for ( $i = 0; $i < $weight; $i++ ) {
				$eligible_ads[] = $ad_id;
			}
		}

		if ( empty( $eligible_ads ) ) {
			return '';
		}

		$selected_ad_id = $eligible_ads[ array_rand( $eligible_ads ) ];
		WP_AdServer_Tracking::track_event( $selected_ad_id, 'impression' );

		$type = get_field( 'wp_ad_type', $selected_ad_id );
		$output = '';

		if ( $type === 'image' ) {
			$image_url = get_field( 'wp_ad_image', $selected_ad_id );
			$click_url = add_query_arg( 'wp_ad_click', $selected_ad_id, home_url( '/' ) );
			$output = sprintf(
				'<div class="wp-adserver-ad"><a href="%s" target="_blank"><img src="%s" style="max-width:100%%; height:auto;"></a></div>',
				esc_url( $click_url ),
				esc_url( $image_url )
			);
		} else {
			$html_code = get_field( 'wp_ad_html_code', $selected_ad_id );
			$output = '<div class="wp-adserver-ad">' . wp_kses_post( $html_code ) . '</div>';
		}

		return $output;
	}
}

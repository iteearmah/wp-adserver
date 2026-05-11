<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Tracking {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle_click_tracking' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_script_request' ) );
	}

	public static function handle_click_tracking() {
		if ( isset( $_GET['wp_ad_click'] ) ) {
			$ad_id = intval( $_GET['wp_ad_click'] );
			self::track_event( $ad_id, 'click' );

			$dest_url = function_exists( 'get_field' ) ? get_field( 'wp_ad_destination_url', $ad_id ) : get_post_meta( $ad_id, 'wp_ad_destination_url', true );
			if ( $dest_url ) {
				wp_redirect( $dest_url );
				exit;
			}
			wp_redirect( home_url() );
			exit;
		}
	}

	public static function track_event( $ad_id, $type ) {
		$total_key = ( $type === 'impression' ) ? '_wp_ad_impressions' : '_wp_ad_clicks';
		$current_total = get_post_meta( $ad_id, $total_key, true ) ?: 0;
		update_post_meta( $ad_id, $total_key, $current_total + 1 );

		$today = date( 'Y-m-d' );
		$stats_key = '_wp_ad_stats_' . $today;
		$stats = get_post_meta( $ad_id, $stats_key, true ) ?: array();

		if ( ! isset( $stats[ $type ] ) ) {
			$stats[ $type ] = 0;
		}
		$stats[ $type ]++;

		if ( $type === 'impression' ) {
			$country = self::get_visitor_country();
			if ( ! isset( $stats['countries'] ) ) {
				$stats['countries'] = array();
			}
			if ( ! isset( $stats['countries'][ $country ] ) ) {
				$stats['countries'][ $country ] = 0;
			}
			$stats['countries'][ $country ]++;
		}

		update_post_meta( $ad_id, $stats_key, $stats );
	}

	public static function get_visitor_country() {
		$headers = array(
			'HTTP_CF_IPCOUNTRY',
			'HTTP_X_COUNTRY_CODE',
			'HTTP_X_REAL_COUNTRY',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				return strtoupper( $_SERVER[ $header ] );
			}
		}

		return 'Unknown';
	}

	public static function handle_script_request() {
		if ( isset( $_GET['wp_ad_serve'] ) ) {
			header( 'Content-Type: application/javascript' );
			$zone = isset( $_GET['zone'] ) ? sanitize_text_field( $_GET['zone'] ) : '';
			$ad_html = WP_AdServer_Renderer::render_ad( $zone );
			echo "document.write(" . json_encode( $ad_html ) . ");";
			exit;
		}
	}
}

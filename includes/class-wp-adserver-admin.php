<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_stats_meta_box' ) );
		add_filter( 'manage_wp_ad_posts_columns', array( __CLASS__, 'add_custom_columns' ) );
		add_action( 'manage_wp_ad_posts_custom_column', array( __CLASS__, 'render_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-wp_ad_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );
	}

	public static function add_stats_meta_box() {
		add_meta_box(
			'wp_ad_stats',
			'Ad Statistics (Last 7 Days)',
			array( __CLASS__, 'render_stats_meta_box' ),
			'wp_ad',
			'normal',
			'high'
		);
	}

	public static function render_stats_meta_box( $post ) {
		echo '<table class="widefat fixed striped">';
		echo '<thead><tr><th>' . esc_html__( 'Date', 'wp-adserver' ) . '</th><th>' . esc_html__( 'Impressions', 'wp-adserver' ) . '</th><th>' . esc_html__( 'Clicks', 'wp-adserver' ) . '</th><th>' . esc_html__( 'CTR', 'wp-adserver' ) . '</th><th>' . esc_html__( 'Top Countries', 'wp-adserver' ) . '</th></tr></thead>';
		echo '<tbody>';

		for ( $i = 0; $i < 7; $i++ ) {
			$date       = gmdate( 'Y-m-d', strtotime( "-$i days" ) );
			$stats      = get_post_meta( $post->ID, '_wp_ad_stats_' . $date, true ) ?: array();
			$imprs      = isset( $stats['impression'] ) ? intval( $stats['impression'] ) : 0;
			$clicks     = isset( $stats['click'] ) ? intval( $stats['click'] ) : 0;
			$ctr        = $imprs > 0 ? round( ( $clicks / $imprs ) * 100, 2 ) : 0;
			$countries  = isset( $stats['countries'] ) ? (array) $stats['countries'] : array();
			arsort( $countries );
			$top_countries = array_slice( array_keys( $countries ), 0, 3 );
			$geo_display   = implode( ', ', array_map( 'esc_html', $top_countries ) ) ?: '-';

			echo '<tr>';
			echo '<td>' . esc_html( $date ) . ( $i === 0 ? ' (' . esc_html__( 'Today', 'wp-adserver' ) . ')' : '' ) . '</td>';
			echo '<td>' . esc_html( $imprs ) . '</td>';
			echo '<td>' . esc_html( $clicks ) . '</td>';
			echo '<td>' . esc_html( $ctr ) . '%</td>';
			echo '<td>' . esc_html( $geo_display ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	public static function add_custom_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key === 'date' ) {
				$new_columns['impressions'] = esc_html__( 'Impressions', 'wp-adserver' );
				$new_columns['clicks']      = esc_html__( 'Clicks', 'wp-adserver' );
				$new_columns['ctr']         = esc_html__( 'CTR', 'wp-adserver' );
				$new_columns['weight']      = esc_html__( 'Weight', 'wp-adserver' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	public static function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'impressions':
				echo esc_html( get_post_meta( $post_id, '_wp_ad_impressions', true ) ?: 0 );
				break;
			case 'clicks':
				echo esc_html( get_post_meta( $post_id, '_wp_ad_clicks', true ) ?: 0 );
				break;
			case 'ctr':
				$impressions = get_post_meta( $post_id, '_wp_ad_impressions', true ) ?: 0;
				$clicks = get_post_meta( $post_id, '_wp_ad_clicks', true ) ?: 0;
				echo $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0;
				echo '%';
				break;
			case 'weight':
				$weight = function_exists( 'get_field' ) ? get_field( 'wp_ad_weight', $post_id ) : get_post_meta( $post_id, 'wp_ad_weight', true );
				echo esc_html( $weight ?: 1 );
				break;
		}
	}

	public static function make_columns_sortable( $columns ) {
		$columns['impressions'] = 'impressions';
		$columns['clicks'] = 'clicks';
		$columns['weight'] = 'weight';
		return $columns;
	}
}

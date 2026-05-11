<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_stats_meta_box' ), 10, 2 );
		add_filter( 'manage_wp_ad_posts_columns', array( __CLASS__, 'add_custom_columns' ) );
		add_action( 'manage_wp_ad_posts_custom_column', array( __CLASS__, 'render_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-wp_ad_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );

		// Ad Zone Taxonomy columns
		add_filter( 'manage_edit-ad_zone_columns', array( __CLASS__, 'add_zone_columns' ) );
		add_action( 'manage_ad_zone_custom_column', array( __CLASS__, 'render_zone_columns' ), 10, 3 );

		// Custom upload folder for ads
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'handle_upload_prefilter' ) );

		// Redirect to ads list after publishing/saving
		add_filter( 'redirect_post_location', array( __CLASS__, 'redirect_after_save' ), 10, 2 );
	}

	public static function redirect_after_save( $location, $post_id ) {
		if ( get_post_type( $post_id ) === 'wp_ad' && isset( $_POST['publish'] ) ) {
			// Only redirect to list if it was a new post being published
			if ( isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'auto-draft' ) {
				$location = admin_url( 'edit.php?post_type=wp_ad' );
			}
		}
		return $location;
	}

	public static function handle_upload_prefilter( $file ) {
		if ( isset( $_REQUEST['post_id'] ) ) {
			$post_id = intval( $_REQUEST['post_id'] );
			if ( get_post_type( $post_id ) === 'wp_ad' ) {
				add_filter( 'upload_dir', array( __CLASS__, 'custom_upload_dir' ) );
			}
		} elseif ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'upload-attachment' ) {
			// This might be an AJAX upload from the media library, we check the context if possible
			// SCF uses the standard media library. We can check if it's coming from our post type.
			$referer = wp_get_referer();
			if ( $referer && strpos( $referer, 'post_type=wp_ad' ) !== false ) {
				add_filter( 'upload_dir', array( __CLASS__, 'custom_upload_dir' ) );
			}
		}
		return $file;
	}

	public static function custom_upload_dir( $uploads ) {
		$subdir = '/wp-adserver';
		$uploads['subdir'] = $subdir . $uploads['subdir'];
		$uploads['path']   = $uploads['basedir'] . $uploads['subdir'];
		$uploads['url']    = $uploads['baseurl'] . $uploads['subdir'];

		// Remove the filter after use to avoid affecting other uploads in the same request if any
		remove_filter( 'upload_dir', array( __CLASS__, 'custom_upload_dir' ) );

		return $uploads;
	}

	public static function add_stats_meta_box( $post_type, $post ) {
		// Only show stats on existing ads, not when creating a new one
		if ( 'wp_ad' !== $post_type || ! $post instanceof WP_Post || 'auto-draft' === $post->post_status ) {
			return;
		}

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

	public static function add_zone_columns( $columns ) {
		$columns['zone_shortcode'] = esc_html__( 'Shortcode', 'wp-adserver' );
		$columns['zone_script']    = esc_html__( 'Script Tag', 'wp-adserver' );
		return $columns;
	}

	public static function render_zone_columns( $content, $column_name, $term_id ) {
		$term = get_term( $term_id, 'ad_zone' );
		if ( ! $term || is_wp_error( $term ) ) {
			return $content;
		}

		$slug = $term->slug;

		switch ( $column_name ) {
			case 'zone_shortcode':
				return '<code>[wp_adserver zone="' . esc_attr( $slug ) . '"]</code>';
			case 'zone_script':
				$url = add_query_arg( array(
					'wp_ad_serve' => 1,
					'zone'        => $slug,
				), home_url( '/' ) );
				return '<code>&lt;script src="' . esc_url( $url ) . '"&gt;&lt;/script&gt;</code>';
		}

		return $content;
	}
}

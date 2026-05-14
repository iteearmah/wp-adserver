<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Renderer {

	private static $meta_cache = array();

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_shortcode( 'wp_adserver', array( __CLASS__, 'render_shortcode' ) );
		add_shortcode( 'wp_ad_script', array( __CLASS__, 'render_script_shortcode' ) );

		// AJAX handlers
		add_action( 'wp_ajax_nopriv_wp_adserver_get_ad', array( __CLASS__, 'ajax_get_ad' ) );
		add_action( 'wp_ajax_wp_adserver_get_ad', array( __CLASS__, 'ajax_get_ad' ) );

		// Script execution optimization
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_async_attribute' ), 10, 2 );

		// Clear cache on ad updates
		add_action( 'save_post_wp_ad', array( __CLASS__, 'clear_ad_list_cache' ) );
		add_action( 'deleted_post', array( __CLASS__, 'clear_ad_list_cache' ) );
		add_action( 'trashed_post', array( __CLASS__, 'clear_ad_list_cache' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'clear_ad_list_cache' ) );
		add_action( 'set_object_terms', array( __CLASS__, 'clear_ad_list_cache_on_term_change' ), 10, 4 );
		add_action( 'transition_post_status', array( __CLASS__, 'clear_cache_on_status_transition' ), 10, 3 );
	}

	/**
	 * Clear cache on post status transition.
	 */
	public static function clear_cache_on_status_transition( $new_status, $old_status, $post ) {
		if ( 'wp_ad' === $post->post_type && $new_status !== $old_status ) {
			self::clear_ad_list_cache( $post->ID );
		}
	}

	/**
	 * Clear ad list cache when an ad is saved or deleted.
	 */
	public static function clear_ad_list_cache( $post_id ) {
		if ( get_post_type( $post_id ) !== 'wp_ad' ) {
			return;
		}

		// Use versioning to invalidate all ad list transients at once
		$new_version = time();
		update_option( 'wp_adserver_cache_version', $new_version );
	}

	/**
	 * Clear ad list cache when terms are changed.
	 */
	public static function clear_ad_list_cache_on_term_change( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( 'ad_zone' === $taxonomy ) {
			self::clear_ad_list_cache( $object_id );
		}
	}

	public static function add_async_attribute( $tag, $handle ) {
		if ( 'wp-adserver-js' !== $handle ) {
			return $tag;
		}
		return str_replace( ' src', ' async defer src', $tag );
	}

	public static function enqueue_scripts() {
  wp_register_style( 'adserver', plugins_url( '../assets/css/style.css', __FILE__ ), array(), WP_ADSERVER_VERSION );
		wp_register_script( 'wp-adserver-js', plugins_url( '../assets/js/wp-adserver.js', __FILE__ ), array(), WP_ADSERVER_VERSION, true );
		wp_localize_script( 'wp-adserver-js', 'wpAdServer', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'zone' => '',
		), $atts );

		// Only enqueue frontend assets if the shortcode is used
		wp_enqueue_style( 'adserver' );
		wp_enqueue_script( 'wp-adserver-js' );

		$zone_slug = ! empty( $atts['zone'] ) ? strtolower( sanitize_title( $atts['zone'] ) ) : 'default';
		$uid       = 'wp-ad-' . $zone_slug;

		return sprintf(
			'<div id="%s" class="wp-adserver-placeholder" data-zone="%s"></div>',
			esc_attr( $uid ),
			esc_attr( $zone_slug )
		);
	}

	public static function ajax_get_ad() {
		$zone = isset( $_GET['zone'] ) ? strtolower( sanitize_title( wp_unslash( $_GET['zone'] ) ) ) : '';
		$debug_info = '';
		$html = self::render_ad( $zone, $debug_info );

		if ( ! $html && current_user_can( 'manage_options' ) ) {
			$html = '<div style="border:1px dashed #ccc; padding:10px; color:#666; font-size:12px;">';
			$html .= 'AdServer: No eligible ads found for zone "' . esc_html( $zone ) . '".';
			if ( $debug_info ) {
				$html .= '<br>Reason: ' . esc_html( $debug_info );
			}
			$html .= '</div>';
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function render_script_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'zone' => '',
		), $atts );

		$zone_slug = ! empty( $atts['zone'] ) ? strtolower( sanitize_title( $atts['zone'] ) ) : 'default';
		$unique_id = 'wp-ad-' . $zone_slug;
		$url = add_query_arg( array(
			'wp_ad_serve' => 1,
			'zone'        => $zone_slug,
			'uid'         => $unique_id,
		), home_url( '/' ) );

		return sprintf(
			'<div id="%s" class="wp-adserver-script-container"></div><script src="%s" async></script>',
			esc_attr( $unique_id ),
			esc_url( $url )
		);
	}

	private static function get_cached_field( $field_name, $post_id ) {
		$cache_key = $post_id . '_' . $field_name;
		if ( ! isset( self::$meta_cache[ $cache_key ] ) ) {
			self::$meta_cache[ $cache_key ] = get_field( $field_name, $post_id );
		}
		return self::$meta_cache[ $cache_key ];
	}

	public static function render_ad( $zone_slug = '', &$debug_info = '' ) {
		$zone_slug = strtolower( $zone_slug );

		global $wpdb;

		if ( ! empty( $zone_slug ) ) {
			$ads = $wpdb->get_col( $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				WHERE p.post_type = 'wp_ad'
				AND p.post_status = 'publish'
				AND tt.taxonomy = 'ad_zone'
				AND t.slug = %s",
				$zone_slug
			) );
		} else {
			$ads = $wpdb->get_col(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'wp_ad'
				AND post_status = 'publish'"
			);
		}

		$ads = array_map( 'intval', $ads );

		if ( empty( $ads ) ) {
			$debug_info = 'No ads assigned to this zone or no published ads found.';

			// Check if any ads exist in this zone but are drafts/scheduled
			if ( ! empty( $zone_slug ) ) {
				$all_ads_in_zone = $wpdb->get_col( $wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
					WHERE p.post_type = 'wp_ad'
					AND p.post_status != 'auto-draft'
					AND tt.taxonomy = 'ad_zone'
					AND t.slug = %s",
					$zone_slug
				) );
			} else {
				$all_ads_in_zone = $wpdb->get_col(
					"SELECT ID FROM {$wpdb->posts}
					WHERE post_type = 'wp_ad'
					AND post_status != 'auto-draft'"
				);
			}

			if ( ! empty( $all_ads_in_zone ) ) {
				$statuses = array();
				$unpublished_found = false;
				foreach ( $all_ads_in_zone as $id ) {
					$status = get_post_status( $id );
					$statuses[] = $status;
					if ( $status !== 'publish' ) {
						$unpublished_found = true;
					}
				}
				$status_counts = array_count_values( $statuses );
				$status_string = array();
				foreach ( $status_counts as $status => $count ) {
					$status_string[] = "$count $status";
				}
				$debug_info .= ' (Found ads in this zone with statuses: ' . implode( ', ', $status_string ) . '.';
				if ( $unpublished_found ) {
					$debug_info .= ' Please publish them to make them eligible.)';
				} else {
					$debug_info .= ' This may indicate a transient cache issue or visibility problem.)';
				}
			}

			return '';
		}

		$eligible_ads = array();
		$visitor_country = WP_AdServer_Tracking::get_visitor_country();
		$visitor_device  = WP_AdServer_Tracking::get_visitor_device();
		$now = current_time( 'Y-m-d H:i:s' );

		$reasons = array();
		if ( current_user_can( 'manage_options' ) ) {
			$reasons[] = "Visitor context: Country: {$visitor_country}, Device: {$visitor_device}, Time: {$now}";
		}

		foreach ( $ads as $ad_id ) {
			$ad_title = get_the_title( $ad_id );

			// Check Active
			$is_active = self::get_cached_field( 'wp_ad_active', $ad_id );
			if ( $is_active === false || $is_active === 0 || $is_active === '0' ) {
 			$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) is inactive (Value: " . wp_json_encode( $is_active ) . ").";
				continue;
			}

			// Check Scheduling
			$start_date = self::get_cached_field( 'wp_ad_start_date', $ad_id );
			$end_date   = self::get_cached_field( 'wp_ad_end_date', $ad_id );

			if ( $start_date && $now < $start_date ) {
				$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) scheduled to start at {$start_date}. (Current: {$now})";
				continue;
			}
			if ( $end_date && $now > $end_date ) {
				$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) expired at {$end_date}. (Current: {$now})";
				continue;
			}

			// Check Limits
			$limit_impressions = (int) self::get_cached_field( 'wp_ad_limit_impressions', $ad_id );
			$limit_clicks      = (int) self::get_cached_field( 'wp_ad_limit_clicks', $ad_id );

			if ( $limit_impressions > 0 ) {
				$current_imprs = WP_AdServer_Tracking::get_total_stats( $ad_id, 'impression' );
				if ( (int) $current_imprs >= $limit_impressions ) {
					$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) reached impression limit ({$limit_impressions}). (Current: {$current_imprs})";
					continue;
				}
			}
			if ( $limit_clicks > 0 ) {
				$current_clicks = WP_AdServer_Tracking::get_total_stats( $ad_id, 'click' );
				if ( (int) $current_clicks >= $limit_clicks ) {
					$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) reached click limit ({$limit_clicks}). (Current: {$current_clicks})";
					continue;
				}
			}

			// Check Geo
			$geo_enabled = self::get_cached_field( 'wp_ad_geo_enabled', $ad_id );
			if ( $geo_enabled ) {
				$mode      = self::get_cached_field( 'wp_ad_geo_mode', $ad_id );
				$countries = self::get_cached_field( 'wp_ad_geo_countries', $ad_id );
				$country_list = is_array( $countries ) ? array_map( 'strtoupper', $countries ) : array_map( 'trim', explode( ',', strtoupper( $countries ) ) );

				if ( $mode === 'include' ) {
					if ( ! in_array( $visitor_country, $country_list ) ) {
						$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) restricted to countries: " . implode(', ', $country_list) . ". (Visitor country: {$visitor_country})";
						continue;
					}
				} else {
					if ( in_array( $visitor_country, $country_list ) ) {
						$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) excluded from countries: " . implode(', ', $country_list) . ". (Visitor country: {$visitor_country})";
						continue;
					}
				}
			}

			// Check Device
			$device_enabled = self::get_cached_field( 'wp_ad_device_enabled', $ad_id );
			if ( $device_enabled ) {
				$target_devices = self::get_cached_field( 'wp_ad_device_types', $ad_id );
				$target_devices = is_array( $target_devices ) ? $target_devices : array();

				if ( ! in_array( $visitor_device, $target_devices ) ) {
					$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) restricted to devices: " . implode( ', ', $target_devices ) . ". (Visitor device: {$visitor_device})";
					continue;
				}
			}

			// Check content
			$type = self::get_cached_field( 'wp_ad_type', $ad_id );
			if ( $type === 'image' ) {
				$image_url = self::get_cached_field( 'wp_ad_image', $ad_id );
				if ( ! $image_url ) {
					$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) has no image set.";
					continue;
				}
			} else {
				$html_code = self::get_cached_field( 'wp_ad_html_code', $ad_id );
				if ( empty( $html_code ) ) {
					$reasons[] = "Ad '{$ad_title}' (ID: {$ad_id}) has no HTML code set.";
					continue;
				}
			}

			$weight = (int) self::get_cached_field( 'wp_ad_weight', $ad_id ) ?: 1;
			for ( $i = 0; $i < $weight; $i++ ) {
				$eligible_ads[] = $ad_id;
			}
		}

		if ( empty( $eligible_ads ) ) {
			if ( ! empty( $reasons ) ) {
				$debug_info = implode( ' | ', array_unique( $reasons ) );
			} else {
				$debug_info = 'Unknown filtering reason.';
			}
			return '';
		}

		$selected_ad_id = $eligible_ads[ array_rand( $eligible_ads ) ];
		WP_AdServer_Tracking::track_event( $selected_ad_id, 'impression' );

		$type = self::get_cached_field( 'wp_ad_type', $selected_ad_id );
		$output = '';

		if ( $type === 'image' ) {
			$image_url = self::get_cached_field( 'wp_ad_image', $selected_ad_id );
			$click_url = add_query_arg( 'wp_ad_click', $selected_ad_id, home_url( '/' ) );

			$output = sprintf(
				'<div class="wp-adserver-ad"><a href="%s" target="_blank"><img src="%s" style="max-width:100%%; height:auto;"></a></div>',
				esc_url( $click_url ),
				esc_url( $image_url )
			);
		} else {
			$html_code = self::get_cached_field( 'wp_ad_html_code', $selected_ad_id );
			$output = '<div class="wp-adserver-ad">' . $html_code . '</div>';
		}

		return $output;
	}
}

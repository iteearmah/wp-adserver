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

		// Row actions
		add_filter( 'post_row_actions', array( __CLASS__, 'add_duplicate_link' ), 10, 2 );
		add_action( 'admin_action_wp_adserver_duplicate_ad', array( __CLASS__, 'handle_duplicate_ad' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_duplicate_notice' ) );

		// Ad Zone Taxonomy columns
		add_filter( 'manage_edit-ad_zone_columns', array( __CLASS__, 'add_zone_columns' ) );
		add_action( 'manage_ad_zone_custom_column', array( __CLASS__, 'render_zone_columns' ), 10, 3 );

		// Custom upload folder for ads
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'handle_upload_prefilter' ) );

		// Redirect to ads list after publishing/saving
		add_filter( 'redirect_post_location', array( __CLASS__, 'redirect_after_save' ), 10, 2 );

		// Dashboard Widget
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );

		// Enqueue Admin Assets
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );

		// Admin Menu for Tools
		add_action( 'admin_menu', array( __CLASS__, 'add_tools_menu' ), 20 );

		// Handle Export
		add_action( 'admin_init', array( __CLASS__, 'handle_export' ) );
		// Handle Import
		add_action( 'admin_init', array( __CLASS__, 'handle_import' ) );

		// AJAX Toggle Ad Status
		add_action( 'wp_ajax_wp_adserver_toggle_status', array( __CLASS__, 'ajax_toggle_status' ) );
	}

	/**
	 * Enqueue admin assets.
	 */
	public static function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( $screen && $screen->post_type === 'wp_ad' ) {
			wp_enqueue_style( 'wp-adserver-admin', plugins_url( '../assets/css/admin.css', __FILE__ ), array(), '1.1.0' );
			wp_enqueue_script( 'wp-adserver-admin', plugins_url( '../assets/js/admin.js', __FILE__ ), array( 'jquery' ), '1.1.0', true );
		}
	}

	/**
	 * Add Tools submenu.
	 */
	public static function add_tools_menu() {
		add_submenu_page(
			'edit.php?post_type=wp_ad',
			esc_html__( 'Tools', 'wp-adserver' ),
			esc_html__( 'Tools', 'wp-adserver' ),
			'manage_options',
			'wp-adserver-tools',
			array( __CLASS__, 'render_tools_page' )
		);
	}

	/**
	 * Render Tools page.
	 */
	public static function render_tools_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP AdServer Tools', 'wp-adserver' ); ?></h1>
			<hr>

			<div class="card">
				<h2><?php esc_html_e( 'Export Ads', 'wp-adserver' ); ?></h2>
				<p><?php esc_html_e( 'Export all advertisements and their settings to a JSON file.', 'wp-adserver' ); ?></p>
				<form method="post" action="">
					<?php wp_nonce_field( 'wp_adserver_export', 'wp_adserver_export_nonce' ); ?>
					<input type="hidden" name="wp_adserver_action" value="export_ads">
					<?php submit_button( esc_html__( 'Export Ads to JSON', 'wp-adserver' ), 'primary', 'submit_export' ); ?>
				</form>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Import Ads', 'wp-adserver' ); ?></h2>
				<p><?php esc_html_e( 'Import advertisements from a previously exported JSON file.', 'wp-adserver' ); ?></p>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wp_adserver_import', 'wp_adserver_import_nonce' ); ?>
					<input type="hidden" name="wp_adserver_action" value="import_ads">
					<input type="file" name="import_file" accept=".json" required>
					<?php submit_button( esc_html__( 'Import Ads from JSON', 'wp-adserver' ), 'secondary', 'submit_import' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle Export.
	 */
	public static function handle_export() {
		if ( ! isset( $_POST['wp_adserver_action'] ) || $_POST['wp_adserver_action'] !== 'export_ads' ) {
			return;
		}

		if ( ! check_admin_referer( 'wp_adserver_export', 'wp_adserver_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$args = array(
			'post_type'      => 'wp_ad',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
			'posts_per_page' => -1,
		);

		$ads = get_posts( $args );
		$export_data = array();

		foreach ( $ads as $ad ) {
			$meta = get_post_custom( $ad->ID );
			// Filter out tracking stats to start fresh on import? Actually keep them for exact backup.
			// But maybe skip some internal WP meta.
			$ad_data = array(
				'post_title'   => $ad->post_title,
				'post_content' => $ad->post_content,
				'post_status'  => $ad->post_status,
				'meta'         => $meta,
				'zones'        => wp_get_object_terms( $ad->ID, 'ad_zone', array( 'fields' => 'slugs' ) ),
			);
			$export_data[] = $ad_data;
		}

		$json_data = json_encode( $export_data, JSON_PRETTY_PRINT );
		$filename = 'wp-adserver-export-' . date( 'Y-m-d-H-i-s' ) . '.json';

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		echo $json_data;
		exit;
	}

	/**
	 * Handle Import.
	 */
	public static function handle_import() {
		if ( ! isset( $_POST['wp_adserver_action'] ) || $_POST['wp_adserver_action'] !== 'import_ads' ) {
			return;
		}

		if ( ! check_admin_referer( 'wp_adserver_import', 'wp_adserver_import_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			return;
		}

		$json_data = file_get_contents( $_FILES['import_file']['tmp_name'] );
		$ads_data = json_decode( $json_data, true );

		if ( ! is_array( $ads_data ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p>' . esc_html__( 'Invalid import file format.', 'wp-adserver' ) . '</p></div>';
			} );
			return;
		}

		$count = 0;
		foreach ( $ads_data as $ad_data ) {
			$new_post = array(
				'post_title'   => $ad_data['post_title'],
				'post_content' => $ad_data['post_content'],
				'post_status'  => $ad_data['post_status'],
				'post_type'    => 'wp_ad',
			);

			$new_id = wp_insert_post( $new_post );

			if ( ! is_wp_error( $new_id ) ) {
				// Restore meta
				if ( ! empty( $ad_data['meta'] ) ) {
					foreach ( $ad_data['meta'] as $key => $values ) {
						foreach ( $values as $value ) {
							// If it is serialized, WordPress update_post_meta handles it if we pass it correctly.
							// But get_post_custom returns strings.
							$value = maybe_unserialize( $value );
							update_post_meta( $new_id, $key, $value );
						}
					}
				}

				// Restore zones
				if ( ! empty( $ad_data['zones'] ) ) {
					wp_set_object_terms( $new_id, $ad_data['zones'], 'ad_zone' );
				}
				$count++;
			}
		}

		// Clear cache
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp_ad_list_%'" );

		add_action( 'admin_notices', function() use ( $count ) {
			echo '<div class="updated"><p>' . sprintf( esc_html__( 'Successfully imported %d advertisements.', 'wp-adserver' ), $count ) . '</p></div>';
		} );
	}

	/**
	 * Add Dashboard Widget for quick stats.
	 */
	public static function add_dashboard_widget() {
		if ( current_user_can( 'edit_ads' ) ) {
			wp_add_dashboard_widget(
				'wp_adserver_stats_widget',
				esc_html__( 'WP AdServer Quick Stats', 'wp-adserver' ),
				array( __CLASS__, 'render_dashboard_widget' )
			);
		}
	}

	/**
	 * Render Dashboard Widget content.
	 */
	public static function render_dashboard_widget() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wp_adserver_tracking';

		// Total ads count
		$total_ads = wp_count_posts( 'wp_ad' )->publish;

		// Total impressions and clicks from the custom tracking table
		$stats = $wpdb->get_row( "SELECT 
			COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as total_impressions,
			COUNT(CASE WHEN event_type = 'click' THEN 1 END) as total_clicks
			FROM $table_name" );

		$total_impressions = isset( $stats->total_impressions ) ? intval( $stats->total_impressions ) : 0;
		$total_clicks      = isset( $stats->total_clicks ) ? intval( $stats->total_clicks ) : 0;

		$ctr = ( $total_impressions > 0 ) ? round( ( $total_clicks / $total_impressions ) * 100, 2 ) : 0;

		echo '<div class="wp-adserver-dashboard-widget">';
		echo '<ul>';
		echo '<li><strong>' . esc_html__( 'Published Ads:', 'wp-adserver' ) . '</strong> ' . esc_html( $total_ads ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Total Impressions:', 'wp-adserver' ) . '</strong> ' . esc_html( number_format( $total_impressions ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Total Clicks:', 'wp-adserver' ) . '</strong> ' . esc_html( number_format( $total_clicks ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Overall CTR:', 'wp-adserver' ) . '</strong> ' . esc_html( $ctr ) . '%</li>';
		echo '</ul>';
		echo '<p>';
		echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=wp_ad' ) ) . '" class="button button-primary">' . esc_html__( 'Manage Ads', 'wp-adserver' ) . '</a> ';
		echo '<a href="' . esc_url( admin_url( 'post-new.php?post_type=wp_ad' ) ) . '" class="button">' . esc_html__( 'Add New Ad', 'wp-adserver' ) . '</a>';
		echo '</p>';
		echo '</div>';
	}

	public static function redirect_after_save( $location, $post_id ) {
		if ( get_post_type( $post_id ) === 'wp_ad' ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp_ad_list_%'" );

			if ( isset( $_POST['publish'] ) ) {
				// Only redirect to list if it was a new post being published
				if ( isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'auto-draft' ) {
					$location = admin_url( 'edit.php?post_type=wp_ad' );
				}
			}
		}
		return $location;
	}
	
	/**
	 * Add "Duplicate" link to post row actions.
	 */
	public static function add_duplicate_link( $actions, $post ) {
		if ( $post->post_type !== 'wp_ad' || ! current_user_can( 'edit_ads' ) ) {
			return $actions;
		}

		$url = add_query_arg(
			array(
				'action' => 'wp_adserver_duplicate_ad',
				'post'   => $post->ID,
				'nonce'  => wp_create_nonce( 'wp_adserver_duplicate_' . $post->ID ),
			),
			admin_url( 'admin.php' )
		);

		$actions['duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate this ad', 'wp-adserver' ),
			esc_html__( 'Duplicate', 'wp-adserver' )
		);

		return $actions;
	}

	/**
	 * Handle the duplication of an ad.
	 */
	public static function handle_duplicate_ad() {
		$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
		$nonce   = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'wp_adserver_duplicate_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wp-adserver' ) );
		}

		if ( ! current_user_can( 'edit_ads' ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate ads.', 'wp-adserver' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'wp_ad' ) {
			wp_die( esc_html__( 'Post not found.', 'wp-adserver' ) );
		}

		$current_user = wp_get_current_user();

		$new_post_args = array(
			'post_author' => $current_user->ID,
			'post_content' => $post->post_content,
			'post_title' => sprintf( esc_html__( '%s (Copy)', 'wp-adserver' ), $post->post_title ),
			'post_status' => 'draft',
			'post_type' => $post->post_type,
			'post_parent' => $post->post_parent,
			'menu_order' => $post->menu_order,
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html__( 'Failed to create duplicate post.', 'wp-adserver' ) );
		}

		// Duplicate all taxonomies
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_post_id, $terms, $taxonomy );
		}

		// Duplicate all post meta
		$post_meta = get_post_custom( $post_id );
		foreach ( $post_meta as $key => $values ) {
			// Skip internal WP meta and tracking stats
			if ( strpos( $key, '_wp_ad_stats_' ) === 0 || in_array( $key, array( '_wp_ad_impressions', '_wp_ad_clicks' ) ) ) {
				continue;
			}
			
			foreach ( $values as $value ) {
				add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
			}
		}

		wp_safe_redirect( add_query_arg( 'duplicated', 1, admin_url( 'edit.php?post_type=wp_ad' ) ) );
		exit;
	}

	/**
	 * Show admin notice after duplication.
	 */
	public static function show_duplicate_notice() {
		if ( isset( $_GET['duplicated'] ) && $_GET['duplicated'] == 1 ) {
			echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Advertisement successfully duplicated as a draft.', 'wp-adserver' ) . '</p></div>';
		}
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

		$all_stats = WP_AdServer_Tracking::get_ad_stats( $post->ID, 7 );

		foreach ( $all_stats as $date => $stats ) {
			$imprs      = isset( $stats['impression'] ) ? intval( $stats['impression'] ) : 0;
			$clicks     = isset( $stats['click'] ) ? intval( $stats['click'] ) : 0;
			$ctr        = $imprs > 0 ? round( ( $clicks / $imprs ) * 100, 2 ) : 0;
			$countries  = isset( $stats['countries'] ) ? (array) $stats['countries'] : array();
			$top_countries = array_keys( $countries );
			$geo_display   = implode( ', ', array_map( 'esc_html', $top_countries ) ) ?: '-';

			echo '<tr>';
			echo '<td>' . esc_html( $date ) . ( $date === gmdate( 'Y-m-d' ) ? ' (' . esc_html__( 'Today', 'wp-adserver' ) . ')' : '' ) . '</td>';
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
			if ( $key === 'title' ) {
				$new_columns[ $key ] = $value;
				$new_columns['active'] = esc_html__( 'Status', 'wp-adserver' );
				continue;
			}
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
			case 'active':
				$active = get_field( 'wp_ad_active', $post_id );
				$nonce  = wp_create_nonce( 'wp_ad_status_' . $post_id );
				echo '<div class="wp-ad-status-toggle" data-post-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '" style="cursor: pointer; display: inline-block;">';
				if ( $active === false || $active === 0 ) {
					echo '<span class="dashicons dashicons-hidden" style="color:#d63638;" title="' . esc_attr__( 'Inactive', 'wp-adserver' ) . '"></span>';
				} else {
					echo '<span class="dashicons dashicons-visibility" style="color:#00a32a;" title="' . esc_attr__( 'Active', 'wp-adserver' ) . '"></span>';
				}
				echo '</div>';
				break;
			case 'impressions':
				echo esc_html( WP_AdServer_Tracking::get_total_stats( $post_id, 'impression' ) );
				break;
			case 'clicks':
				echo esc_html( WP_AdServer_Tracking::get_total_stats( $post_id, 'click' ) );
				break;
			case 'ctr':
				$impressions = WP_AdServer_Tracking::get_total_stats( $post_id, 'impression' );
				$clicks = WP_AdServer_Tracking::get_total_stats( $post_id, 'click' );
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
				$uid = 'wp-ad-' . $slug;
				$url = add_query_arg( array(
					'wp_ad_serve' => 1,
					'zone'        => $slug,
					'uid'         => $uid,
				), home_url( '/' ) );
				return '<code>&lt;div id="' . esc_attr( $uid ) . '"&gt;&lt;/div&gt;&lt;script src="' . esc_url( $url ) . '" async&gt;&lt;/script&gt;</code>';
		}

		return $content;
	}

	/**
	 * AJAX Toggle Status.
	 */
	public static function ajax_toggle_status() {
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'wp_ad_status_' . $post_id ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$current_status = get_field( 'wp_ad_active', $post_id );
		$new_status     = ( $current_status === false || $current_status === 0 ) ? 1 : 0;

		update_field( 'wp_ad_active', $new_status, $post_id );

		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp_ad_list_%'" );

		ob_start();
		if ( $new_status === 0 ) {
			echo '<span class="dashicons dashicons-hidden" style="color:#d63638;" title="' . esc_attr__( 'Inactive', 'wp-adserver' ) . '"></span>';
		} else {
			echo '<span class="dashicons dashicons-visibility" style="color:#00a32a;" title="' . esc_attr__( 'Active', 'wp-adserver' ) . '"></span>';
		}
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'   => $html,
			'status' => $new_status,
		) );
	}
}

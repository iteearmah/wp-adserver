<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Reports {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_reports_menu' ), 15 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_report_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_export' ) );
	}

	public static function add_reports_menu() {
		add_submenu_page(
			'edit.php?post_type=wp_ad',
			esc_html__( 'Reports', 'wp-adserver' ),
			esc_html__( 'Reports', 'wp-adserver' ),
			'manage_options',
			'wp-adserver-reports',
			array( __CLASS__, 'render_reports_page' )
		);
	}

	public static function enqueue_report_assets( $hook ) {
		if ( strpos( $hook, 'wp-adserver-reports' ) === false ) {
			return;
		}

		// Load Chart.js from CDN for better visualization
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );
		
		wp_enqueue_style( 'wp-adserver-admin', plugins_url( '../assets/css/admin.css', __FILE__ ), array(), '1.1.0' );
	}

	public static function handle_export() {
		if ( ! isset( $_GET['wp_ad_export'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'wp_ad_export_report' );

		$days = isset( $_GET['days'] ) ? intval( $_GET['days'] ) : 30;
		$ad_id = isset( $_GET['ad_id'] ) ? intval( $_GET['ad_id'] ) : 0;

		$stats = WP_AdServer_Tracking::get_aggregated_stats( array(
			'days'  => $days,
			'ad_id' => $ad_id,
			'groupby' => 'date'
		) );

		header( 'Content-Type: text/csv; charset=utf-8' );
  header( 'Content-Disposition: attachment; filename=wp-adserver-report-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( __( 'Date', 'wp-adserver' ), __( 'Impressions', 'wp-adserver' ), __( 'Clicks', 'wp-adserver' ), __( 'CTR (%)', 'wp-adserver' ) ) );

		foreach ( $stats as $row ) {
			$ctr = $row->impressions > 0 ? ( $row->clicks / $row->impressions ) * 100 : 0;
			fputcsv( $output, array(
				$row->label,
				$row->impressions,
				$row->clicks,
				number_format( $ctr, 2 )
			) );
		}

		fclose( $output );
		exit;
	}

	public static function render_reports_page() {
		$days = isset( $_GET['days'] ) ? intval( $_GET['days'] ) : 30;
		$ad_id = isset( $_GET['ad_id'] ) ? intval( $_GET['ad_id'] ) : 0;

		$stats = WP_AdServer_Tracking::get_aggregated_stats( array(
			'days'  => $days,
			'ad_id' => $ad_id,
			'groupby' => 'date'
		) );

		$device_stats = WP_AdServer_Tracking::get_aggregated_stats( array(
			'days'  => $days,
			'ad_id' => $ad_id,
			'groupby' => 'device'
		) );

		$country_stats = WP_AdServer_Tracking::get_aggregated_stats( array(
			'days'  => $days,
			'ad_id' => $ad_id,
			'groupby' => 'country'
		) );

		$total_impressions = 0;
		$total_clicks = 0;
		$chart_labels = array();
		$chart_impressions = array();
		$chart_clicks = array();

		foreach ( $stats as $row ) {
			$total_impressions += $row->impressions;
			$total_clicks += $row->clicks;
			$chart_labels[] = $row->label;
			$chart_impressions[] = $row->impressions;
			$chart_clicks[] = $row->clicks;
		}

		$avg_ctr = $total_impressions > 0 ? ( $total_clicks / $total_impressions ) * 100 : 0;

		?>
		<div class="wrap wp-adserver-reports">
			<h1><?php esc_html_e( 'AdServer Reports', 'wp-adserver' ); ?></h1>

			<div class="report-filters card">
				<form method="get" action="">
					<input type="hidden" name="post_type" value="wp_ad">
					<input type="hidden" name="page" value="wp-adserver-reports">
					
					<div class="filter-group">
						<label for="days"><?php esc_html_e( 'Period:', 'wp-adserver' ); ?></label>
						<select name="days" id="days">
							<option value="7" <?php selected( $days, 7 ); ?>><?php esc_html_e( 'Last 7 Days', 'wp-adserver' ); ?></option>
							<option value="30" <?php selected( $days, 30 ); ?>><?php esc_html_e( 'Last 30 Days', 'wp-adserver' ); ?></option>
							<option value="90" <?php selected( $days, 90 ); ?>><?php esc_html_e( 'Last 90 Days', 'wp-adserver' ); ?></option>
						</select>
					</div>

					<div class="filter-group">
						<label for="ad_id"><?php esc_html_e( 'Filter by Ad:', 'wp-adserver' ); ?></label>
						<select name="ad_id" id="ad_id">
							<option value="0"><?php esc_html_e( 'All Ads', 'wp-adserver' ); ?></option>
							<?php
							$ads = get_posts( array( 'post_type' => 'wp_ad', 'posts_per_page' => -1 ) );
							foreach ( $ads as $ad ) {
								echo '<option value="' . esc_attr( $ad->ID ) . '" ' . selected( $ad_id, $ad->ID, false ) . '>' . esc_html( $ad->post_title ) . '</option>';
							}
							?>
						</select>
					</div>

					<?php submit_button( esc_html__( 'Filter', 'wp-adserver' ), 'secondary', 'submit', false ); ?>
				</form>

				<div class="report-actions">
					<a href="<?php echo esc_url( add_query_arg( array( 'wp_ad_export' => 1, '_wpnonce' => wp_create_nonce( 'wp_ad_export_report' ) ) ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -3px; font-size: 18px;"></span>
						<?php esc_html_e( 'Export to CSV', 'wp-adserver' ); ?>
					</a>
				</div>
			</div>

			<div class="stats-overview">
				<div class="stat-card stat-impressions">
					<div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
					<div class="stat-content">
						<h3><?php esc_html_e( 'Total Impressions', 'wp-adserver' ); ?></h3>
						<div class="stat-value"><?php echo esc_html( number_format( $total_impressions ) ); ?></div>
					</div>
				</div>
				<div class="stat-card stat-clicks">
					<div class="stat-icon"><span class="dashicons dashicons-external"></span></div>
					<div class="stat-content">
						<h3><?php esc_html_e( 'Total Clicks', 'wp-adserver' ); ?></h3>
						<div class="stat-value"><?php echo esc_html( number_format( $total_clicks ) ); ?></div>
					</div>
				</div>
				<div class="stat-card stat-ctr">
					<div class="stat-icon"><span class="dashicons dashicons-chart-area"></span></div>
					<div class="stat-content">
						<h3><?php esc_html_e( 'Average CTR', 'wp-adserver' ); ?></h3>
						<div class="stat-value"><?php echo esc_html( number_format( $avg_ctr, 2 ) ); ?>%</div>
					</div>
				</div>
			</div>

			<div class="card chart-container">
				<h2><?php esc_html_e( 'Performance Over Time', 'wp-adserver' ); ?></h2>
				<canvas id="performanceChart" width="400" height="150"></canvas>
			</div>

			<div class="reports-grid">
				<div class="card">
					<h2><?php esc_html_e( 'Devices', 'wp-adserver' ); ?></h2>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Device', 'wp-adserver' ); ?></th>
								<th><?php esc_html_e( 'Impressions', 'wp-adserver' ); ?></th>
								<th><?php esc_html_e( 'Clicks', 'wp-adserver' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $device_stats as $row ) : ?>
								<tr>
									<td><?php echo esc_html( ucfirst( $row->label ) ); ?></td>
  							<td><?php echo esc_html( number_format( $row->impressions ) ); ?></td>
  							<td><?php echo esc_html( number_format( $row->clicks ) ); ?></td>
  						</tr>
  					<?php endforeach; ?>
  					</tbody>
  				</table>
  			</div>

  				<div class="card">
  					<h2><?php esc_html_e( 'Top Countries', 'wp-adserver' ); ?></h2>
  					<table class="wp-list-table widefat fixed striped">
  						<thead>
  							<tr>
  								<th><?php esc_html_e( 'Country', 'wp-adserver' ); ?></th>
  								<th><?php esc_html_e( 'Impressions', 'wp-adserver' ); ?></th>
  								<th><?php esc_html_e( 'Clicks', 'wp-adserver' ); ?></th>
  							</tr>
  						</thead>
  						<tbody>
  							<?php foreach ( array_slice( $country_stats, 0, 10 ) as $row ) : ?>
  								<tr>
  									<td><?php echo esc_html( $row->label ?: __( 'Unknown', 'wp-adserver' ) ); ?></td>
  									<td><?php echo esc_html( number_format( $row->impressions ) ); ?></td>
  									<td><?php echo esc_html( number_format( $row->clicks ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var ctx = document.getElementById('performanceChart').getContext('2d');
			new Chart(ctx, {
				type: 'line',
				data: {
					labels: <?php echo wp_json_encode( $chart_labels ); ?>,
					datasets: [{
						label: '<?php esc_html_e( 'Impressions', 'wp-adserver' ); ?>',
						data: <?php echo wp_json_encode( $chart_impressions ); ?>,
						borderColor: '#2271b1',
						backgroundColor: function(context) {
							const chart = context.chart;
							const {ctx, chartArea} = chart;
							if (!chartArea) return null;
							const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
							gradient.addColorStop(0, 'rgba(34, 113, 177, 0)');
							gradient.addColorStop(1, 'rgba(34, 113, 177, 0.2)');
							return gradient;
						},
						fill: true,
						tension: 0.4,
						pointRadius: 4,
						pointHoverRadius: 6
					}, {
						label: '<?php esc_html_e( 'Clicks', 'wp-adserver' ); ?>',
						data: <?php echo wp_json_encode( $chart_clicks ); ?>,
						borderColor: '#d63638',
						backgroundColor: function(context) {
							const chart = context.chart;
							const {ctx, chartArea} = chart;
							if (!chartArea) return null;
							const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
							gradient.addColorStop(0, 'rgba(214, 54, 56, 0)');
							gradient.addColorStop(1, 'rgba(214, 54, 56, 0.2)');
							return gradient;
						},
						fill: true,
						tension: 0.4,
						pointRadius: 4,
						pointHoverRadius: 6
					}]
				},
				options: {
					responsive: true,
					plugins: {
						legend: {
							position: 'top',
						},
						tooltip: {
							mode: 'index',
							intersect: false,
						}
					},
					interaction: {
						mode: 'nearest',
						axis: 'x',
						intersect: false
					},
					scales: {
						y: {
							beginAtZero: true,
							grid: {
								drawBorder: false,
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						x: {
							grid: {
								display: false
							}
						}
					}
				}
			});
		});
		</script>
		<?php
	}
}

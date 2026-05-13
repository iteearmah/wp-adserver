<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AdServer_Access {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_filter( 'user_has_cap', array( __CLASS__, 'check_user_whitelist' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'init', array( __CLASS__, 'add_admin_caps' ) );
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'wp-adserver-access' ) === false ) {
			return;
		}

		if ( function_exists( 'acf_enqueue_scripts' ) ) {
			acf_enqueue_scripts();
		}

		wp_enqueue_style( 'wp-adserver-admin-css', plugins_url( '../assets/css/admin.css', __FILE__ ), array(), '1.1.0' );
		wp_enqueue_script( 'wp-adserver-admin-js', plugins_url( '../assets/js/admin.js', __FILE__ ), array( 'jquery' ), '1.1.0', true );
	}

	public static function check_user_whitelist( $allcaps, $caps, $args ) {
		// Admins always have access, regardless of whitelist.
		// We explicitly grant them our custom capabilities here to ensure visibility
		// even if the database-level role capabilities are not yet synchronized.
		if ( ! empty( $allcaps['manage_options'] ) || ! empty( $allcaps['administrator'] ) ) {
			foreach ( self::get_capabilities() as $cap => $label ) {
				$allcaps[ $cap ] = true;
			}
			return $allcaps;
		}

		if ( ! is_user_logged_in() ) {
			return $allcaps;
		}

		// Only intercept our custom capabilities
		$ad_caps = array_keys( self::get_capabilities() );
		$intercept = false;
		foreach ( $caps as $cap ) {
			if ( in_array( $cap, $ad_caps ) ) {
				$intercept = true;
				break;
			}
		}

		if ( ! $intercept ) {
			return $allcaps;
		}

		$allowed_user_ids = function_exists( 'get_field' ) ? get_field( 'wp_adserver_allowed_users_list', 'option' ) : array();
		
		// Fallback to old username-based whitelist if the new one is empty
		if ( empty( $allowed_user_ids ) ) {
			$allowed_users_raw = get_option( 'wp_adserver_allowed_users', '' );
			if ( empty( $allowed_users_raw ) ) {
				return $allcaps;
			}
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user || ! $current_user->exists() ) {
			return $allcaps;
		}

		$is_allowed = false;
		if ( ! empty( $allowed_user_ids ) && is_array( $allowed_user_ids ) ) {
			if ( in_array( $current_user->ID, $allowed_user_ids ) ) {
				$is_allowed = true;
			}
		} else {
			// Check legacy whitelist
			$allowed_users = array_map( 'trim', explode( ',', $allowed_users_raw ) );
			if ( in_array( $current_user->user_login, $allowed_users ) ) {
				$is_allowed = true;
			}
		}

		if ( ! $is_allowed ) {
			foreach ( $ad_caps as $cap ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}

	public static function get_capabilities() {
		return array(
			'edit_ad'               => 'Edit Ad',
			'read_ad'               => 'Read Ad',
			'delete_ad'             => 'Delete Ad',
			'edit_ads'              => 'Edit Ads',
			'edit_others_ads'       => 'Edit Others Ads',
			'publish_ads'           => 'Publish Ads',
			'read_private_ads'      => 'Read Private Ads',
			'edit_private_ads'      => 'Edit Private Ads',
			'edit_published_ads'    => 'Edit Published Ads',
			'delete_ads'            => 'Delete Ads',
			'delete_others_ads'     => 'Delete Others Ads',
			'delete_private_ads'    => 'Delete Private Ads',
			'delete_published_ads'  => 'Delete Published Ads',
		);
	}

	public static function register_settings() {
		register_setting( 'wp_adserver_access_group', 'wp_adserver_role_caps' );
		register_setting( 'wp_adserver_access_group', 'wp_adserver_allowed_users' );

		// Register the SCF/ACF options page slug so it's recognized
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_sub_page( array(
				'page_title'  => 'Access Configuration',
				'menu_title'  => 'Access Settings',
				'parent_slug' => 'edit.php?post_type=wp_ad',
				'menu_slug'   => 'wp-adserver-access',
				'capability'  => 'manage_options',
				'redirect'    => false,
			) );
		}
	}

	public static function add_settings_page() {
		// If ACF is not active, we still need the submenu page
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			add_submenu_page(
				'edit.php?post_type=wp_ad',
				'Access Configuration',
				'Access Settings',
				'manage_options',
				'wp-adserver-access',
				array( __CLASS__, 'render_settings_page' )
			);
		}
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-adserver' ) );
		}

		// Ensure SCF is fully ready if available
		if ( function_exists( 'acf_render_field_wrap' ) ) {
			acf_enqueue_scripts();
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The Secure Custom Fields plugin must be active to manage access settings.', 'wp-adserver' ) . '</p></div>';
			return;
		}

		if ( isset( $_POST['wp_adserver_save_access'] ) && check_admin_referer( 'wp_adserver_access_nonce' ) ) {
			self::save_role_caps();
			self::save_allowed_users();
			
			// Save SCF fields if available
			if ( function_exists( 'acf_maybe_get_field' ) ) {
				// We need to manually update the field since we are not using acf_form()
				$field_key = 'field_wp_adserver_allowed_users_list';
				if ( isset( $_POST['acf'][$field_key] ) ) {
					$acf_value = wp_unslash( $_POST['acf'][$field_key] );
					update_field( $field_key, $acf_value, 'option' );
				}
			}

			echo '<div class="updated notice is-dismissible"><p>Settings saved successfully.</p></div>';
		}

		$roles = wp_roles()->roles;
		$caps  = self::get_capabilities();
		$allowed_users = get_option( 'wp_adserver_allowed_users', '' );

		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'user_access';
		?>
		<div class="wrap wp-adserver-settings">
			<h1 class="wp-heading-inline">WP AdServer Access Configuration</h1>
			<hr class="wp-header-end">

			<nav class="nav-tab-wrapper wp-adserver-tabs">
				<a href="?post_type=wp_ad&page=wp-adserver-access&tab=user_access" class="nav-tab <?php echo $active_tab === 'user_access' ? 'nav-tab-active' : ''; ?>">User Access</a>
				<a href="?post_type=wp_ad&page=wp-adserver-access&tab=role_permissions" class="nav-tab <?php echo $active_tab === 'role_permissions' ? 'nav-tab-active' : ''; ?>">Role Permissions</a>
			</nav>

			<div class="wp-adserver-tab-content">
				<form method="post">
					<?php wp_nonce_field( 'wp_adserver_access_nonce' ); ?>
					
					<?php if ( $active_tab === 'user_access' ) : ?>
						<div class="card">
							<h2>User Access Whitelist</h2>
							<p class="description">Restrict access to the WP AdServer management section to specific users. Administrators always have access.</p>
							<table class="form-table">
								<?php if ( function_exists( 'acf_render_field_wrap' ) ) : ?>
									<tr>
										<th scope="row">Allowed Users</th>
										<td>
											<?php
											$field = acf_get_field( 'field_wp_adserver_allowed_users_list' );
											if ( $field ) {
												$field['name']  = 'acf[field_wp_adserver_allowed_users_list]';
												$field['value'] = get_field( 'wp_adserver_allowed_users_list', 'option' );
												acf_render_field_wrap( $field );
											} else {
												// Fallback if field not found for some reason
												acf_render_field_wrap( array(
													'key'           => 'field_wp_adserver_allowed_users_list',
													'label'         => 'Allowed Users',
													'name'          => 'acf[field_wp_adserver_allowed_users_list]',
													'type'          => 'user',
													'return_format' => 'id',
													'multiple'      => 1,
													'allow_null'    => 1,
													'value'         => get_field( 'wp_adserver_allowed_users_list', 'option' ),
												) );
											}
											?>
										</td>
									</tr>
								<?php else : ?>
									<tr>
										<th scope="row"><label for="wp_adserver_allowed_users">Allowed Usernames (Legacy)</label></th>
										<td>
											<textarea name="wp_adserver_allowed_users" id="wp_adserver_allowed_users" rows="5" class="large-text" placeholder="user1, user2, user3"><?php echo esc_textarea( get_option( 'wp_adserver_allowed_users', '' ) ); ?></textarea>
											<p class="description">Enter usernames separated by commas. <strong>Note:</strong> Secure Custom Fields plugin is recommended for a better user selection experience.</p>
										</td>
									</tr>
								<?php endif; ?>
							</table>
						</div>
					<?php else : ?>
						<div class="card">
							<h2>Role Permissions Matrix</h2>
							<p class="description">Configure which capabilities each role should have for managing advertisements.</p>
							<div class="matrix-container" style="overflow-x: auto;">
								<table class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th class="role-column">Role</th>
											<?php foreach ( $caps as $cap => $label ) : ?>
												<th class="cap-column"><?php echo esc_html( $label ); ?></th>
											<?php endforeach; ?>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $roles as $role_key => $role_data ) : ?>
											<tr>
												<td class="role-name"><strong><?php echo esc_html( $role_data['name'] ); ?></strong></td>
												<?php foreach ( $caps as $cap => $label ) : ?>
													<td class="cap-check">
														<?php
														$is_admin = ( $role_key === 'administrator' );
														$checked  = isset( $role_data['capabilities'][ $cap ] ) && $role_data['capabilities'][ $cap ];
														if ( $is_admin ) {
															$checked = true; // Admins always have it
														}
														?>
														<input type="checkbox" name="role_caps[<?php echo esc_attr( $role_key ); ?>][<?php echo esc_attr( $cap ); ?>]" value="1" <?php checked( $checked ); ?> <?php disabled( $is_admin ); ?>>
													</td>
												<?php endforeach; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endif; ?>

					<p class="submit">
						<input type="submit" name="wp_adserver_save_access" class="button button-primary button-hero" value="Save Changes">
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	private static function save_role_caps() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$submitted_caps = isset( $_POST['role_caps'] ) ? (array) $_POST['role_caps'] : array();
		$roles = wp_roles();
		$available_caps = self::get_capabilities();

		foreach ( $roles->roles as $role_key => $role_data ) {
			if ( $role_key === 'administrator' ) continue;

			$role = get_role( $role_key );
			if ( ! $role ) continue;

			foreach ( $available_caps as $cap => $label ) {
				if ( isset( $submitted_caps[ $role_key ][ $cap ] ) ) {
					$role->add_cap( $cap );
				} else {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	private static function save_allowed_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$allowed_users = isset( $_POST['wp_adserver_allowed_users'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_adserver_allowed_users'] ) ) : '';
		update_option( 'wp_adserver_allowed_users', $allowed_users );
	}

	public static function add_admin_caps() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::get_capabilities() as $cap => $label ) {
				$admin->add_cap( $cap );
			}
		}
	}
}

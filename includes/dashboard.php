<?php

/**
 * Register the theme's top-level options page
 */
function gcm_register_options_pages() {
	// Developmental On Demand Main page
	add_menu_page(
		null,
		__( 'Developmental On Demand', 'gcm' ),
		'manage_options',
		'acf-gcm-settings',
		'gcm_display_general_settings_page',
		'dashicons-gcm-icon',
		80
	);
	
	// Submenu page just to change the first link in the submenu to say General Settings
	add_submenu_page(
		'acf-gcm-settings',
		null,
		__( 'General Settings', 'gcm' ),
		'manage_options',
		'acf-gcm-settings',
		'gcm_display_general_settings_page'
	);

	// Provider Sync Status Dashboard
	add_submenu_page(
		'acf-gcm-settings',
		__( 'Provider Sync', 'gcm' ),
		__( 'Provider Sync', 'gcm' ),
		'manage_options',
		'gcm-provider-sync',
		'gcm_display_provider_sync_page'
	);

	/*
	// Add other sub options pages
	// Developmental On Demand -> Icons
	add_submenu_page(
		'acf-gcm-settings', // $parent, // 'acf-gcm-root',
		'Icons',
		'Icons',
		'manage_options',
		'gcm-icons-settings',
		'gcm_display_icons_settings_page'
	);
	*/
	
	// Register the ACF options page at the same time despite also being hooked in acf/init
	gcm_register_acf_options_pages();
}
add_action( 'admin_menu', 'gcm_register_options_pages' );

/**
 * Register the theme options page using ACF
 * @return void
 */
function gcm_register_acf_options_pages() {
	if ( ! function_exists('acf_add_options_sub_page') ) return;
	
	// ACF options page to let us add acf fields to that page
	// Developmental On Demand -> General Settings
	// get_field( 'xxx', 'gcm_settings' );
	acf_add_options_sub_page(array(
		'parent_slug' => 'acf-gcm-root', // $parent, // 'acf-gcm-root',
		'menu_slug'   => 'acf-gcm-settings',
		'page_title'  => __( 'General Settings', 'gcm' ) . ' (gcm_settings)',
		'menu_title'  => null,
		'capability'  => 'manage_options',
		'post_id'     => 'gcm_settings',
		'autoload'      => false,
		'redirect' 		=> true,
	));
}
add_action( 'acf/init', 'gcm_register_acf_options_pages' );


/**
 * Displays the general settings page. This should be replaced with an ACF page, unless ACF is disabled
 *
 * @return void
 */
function gcm_display_general_settings_page() {
	// If ACF is running, do not show the default options page.
	if ( function_exists('acf_add_options_page') ) {
		return;
	}
	
	// ACF is NOT active at this point - display a message to explain.
	?>
	<div class="wrap">
		<h1>General Settings</h1>
		<p>Activate the plugin "Advanced Custom Field Pro" to enable theme settings.</p>
	</div>
	<?php
}

/**
 * Display Provider Sync Status Dashboard
 *
 * @return void
 */
function gcm_display_provider_sync_page() {
	// Handle manual sync request
	$sync_result = null;
	if ( isset( $_POST['gcm_trigger_sync'] ) && check_admin_referer( 'gcm_provider_sync', 'gcm_sync_nonce' ) ) {
		if ( function_exists( 'gcm_sync_providers_from_google_sheets' ) ) {
			$sync_result = gcm_sync_providers_from_google_sheets();
		} else {
			$sync_result = array(
				'success' => false,
				'message' => 'Sync function not available. Make sure the theme is active.'
			);
		}
	}

	// Get current configuration
	$sync_enabled = function_exists( 'get_field' ) ? get_field( 'google_sheets_enabled', 'option' ) : false;
	$last_sync = function_exists( 'get_field' ) ? get_field( 'google_sheets_last_sync', 'option' ) : null;
	$spreadsheet_id = function_exists( 'get_field' ) ? get_field( 'google_spreadsheet_id', 'option' ) : null;
	$staff_count = function_exists( 'get_field' ) ? count( get_field( 'staff_list', 'option' ) ?: array() ) : 0;

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( $sync_result ): ?>
			<div class="notice notice-<?php echo $sync_result['success'] ? 'success' : 'error'; ?> is-dismissible">
				<p><strong><?php echo esc_html( $sync_result['message'] ); ?></strong></p>
			</div>
		<?php endif; ?>

		<div class="card">
			<h2>📊 Sync Status</h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<th style="width: 200px;">Sync Enabled</th>
						<td>
							<?php if ( $sync_enabled ): ?>
								<span style="color: green;">✓ Enabled</span>
							<?php else: ?>
								<span style="color: red;">✗ Disabled</span>
								<p style="margin: 5px 0 0 0;">
									<a href="<?php echo admin_url( 'admin.php?page=acf-options-website-settings' ); ?>">
										→ Enable in Website Settings
									</a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Last Sync</th>
						<td>
							<?php if ( $last_sync ): ?>
								<?php
								$timestamp = strtotime( $last_sync );
								$time_ago = human_time_diff( $timestamp, current_time( 'timestamp' ) );
								?>
								<strong><?php echo date( 'M j, Y g:i A', $timestamp ); ?></strong>
								<br><em>(<?php echo $time_ago; ?> ago)</em>
							<?php else: ?>
								<em>Never synced</em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Google Spreadsheet</th>
						<td>
							<?php if ( $spreadsheet_id ): ?>
								<code><?php echo esc_html( $spreadsheet_id ); ?></code>
								<br>
								<a href="https://docs.google.com/spreadsheets/d/<?php echo esc_attr( $spreadsheet_id ); ?>/edit" target="_blank">
									→ Open in Google Sheets
								</a>
							<?php else: ?>
								<em>Not configured</em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Providers in WordPress</th>
						<td>
							<strong><?php echo $staff_count; ?></strong> providers
							<?php if ( $staff_count > 0 ): ?>
								<br>
								<a href="<?php echo admin_url( 'admin.php?page=acf-options-staff' ); ?>">
									→ View/Edit Staff
								</a>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Next Automatic Sync</th>
						<td>
							<?php
							$next_run = wp_next_scheduled( 'gcm_daily_provider_sync' );
							if ( $next_run && $sync_enabled ):
								$time_until = human_time_diff( current_time( 'timestamp' ), $next_run );
							?>
								<strong><?php echo date( 'M j, Y g:i A', $next_run ); ?></strong>
								<br><em>(in <?php echo $time_until; ?>)</em>
							<?php else: ?>
								<em>No automatic sync scheduled</em>
								<?php if ( ! $sync_enabled ): ?>
									<br><small>Enable sync in settings to schedule automatic updates</small>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php if ( $sync_enabled ): ?>
		<div class="card" style="margin-top: 20px;">
			<h2>🔄 Manual Sync</h2>
			<p>Click the button below to immediately sync provider data from Google Sheets to WordPress.</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'gcm_provider_sync', 'gcm_sync_nonce' ); ?>
				<p>
					<button type="submit" name="gcm_trigger_sync" class="button button-primary button-large">
						Sync Now from Google Sheets
					</button>
				</p>
			</form>

			<p><small><strong>What gets synced:</strong> Provider names, NPI, DEA, CAQH ID, state licenses with expiration dates</small></p>
			<p><small><strong>What stays in WordPress:</strong> Bio, photo, education, specialties, languages, years of experience</small></p>
		</div>
		<?php else: ?>
		<div class="card" style="margin-top: 20px; border-left: 4px solid #dc3232;">
			<h2>⚙️ Configuration Required</h2>
			<p><strong>Google Sheets sync is not enabled.</strong></p>
			<p>To enable automatic provider roster syncing:</p>
			<ol>
				<li>Go to <a href="<?php echo admin_url( 'admin.php?page=acf-options-website-settings' ); ?>">Website Settings</a></li>
				<li>Scroll to "Google Sheets Provider Sync" section</li>
				<li>Check "Enable Google Sheets Sync"</li>
				<li>Enter your service account JSON path and spreadsheet ID</li>
				<li>Click "Update"</li>
			</ol>
			<p>
				<a href="<?php echo admin_url( 'admin.php?page=acf-options-website-settings' ); ?>" class="button button-primary">
					Configure Settings
				</a>
			</p>
		</div>
		<?php endif; ?>

		<div class="card" style="margin-top: 20px;">
			<h2>📖 Documentation</h2>
			<ul>
				<li><strong>Theme Documentation:</strong> <code>GOOGLE_SHEETS_SYNC.md</code></li>
				<li><strong>WP-CLI Command:</strong> <code>wp dod sync-providers</code></li>
				<li><strong>Sheet Format:</strong> Email, Full Name, NPI, CAQH ID, DEA, DEA Expiry, License1_State, License1_Number, License1_Expiration, etc.</li>
			</ul>
		</div>
	</div>

	<style>
		.card h2 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}
		.widefat th {
			font-weight: 600;
		}
	</style>
	<?php
}

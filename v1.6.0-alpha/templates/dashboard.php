<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'TW Backup 365', 'tw-backup-365' ); ?> - <?php echo esc_html( $version ); ?></h1>
	<hr class="wp-header-end">

	<div class="tw-dashboard-top-row">
		<div class="card tw-card-half" style="border-left: 4px solid #2271b1;">
			<h2><?php esc_html_e( 'System Health Status', 'tw-backup-365' ); ?></h2>
			<div class="tw-health-grid">
				<div class="tw-health-item">
					<strong><?php esc_html_e( 'Disk Free Space:', 'tw-backup-365' ); ?></strong><br>
					<span class="tw-stat-value" style="<?php echo ( $disk_free < 500 * 1024 * 1024 ) ? 'color: #d63638;' : 'color: #00a32a;'; ?>">
						<?php echo size_format( $disk_free ); ?>
					</span>
					<p class="description"><?php printf( esc_html__( 'Total: %s', 'tw-backup-365' ), size_format( $disk_total ) ); ?></p>
				</div>
				<div class="tw-health-item">
					<strong><?php esc_html_e( 'Server Load:', 'tw-backup-365' ); ?></strong><br>
					<span class="tw-stat-value" style="<?php echo ( $cpu_load !== false && $cpu_load > 5.0 ) ? 'color: #d63638;' : 'color: #00a32a;'; ?>">
						<?php echo ( $cpu_load === false ) ? __( 'N/A', 'tw-backup-365' ) : esc_html( $cpu_load ); ?>
					</span>
					<p class="description"><?php esc_html_e( 'Safe limit: < 5.0', 'tw-backup-365' ); ?></p>
				</div>
				<div class="tw-health-item">
					<strong><?php esc_html_e( 'Memory Limit:', 'tw-backup-365' ); ?></strong><br>
					<span class="tw-stat-value" style="color: #2271b1;">
						<?php echo esc_html( $memory_limit ); ?>
					</span>
					<p class="description"><?php esc_html_e( 'PHP Config', 'tw-backup-365' ); ?></p>
				</div>
			</div>
		</div>
		
		<div class="card tw-card-half">
			<h2><?php esc_html_e( 'One-Click Secure Backup', 'tw-backup-365' ); ?></h2>
			<p><?php esc_html_e( 'Backups are split into 20MB parts. Pre-flight checks ensure system stability.', 'tw-backup-365' ); ?></p>
			
			<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px;">
				<p><strong><?php esc_html_e( 'Security Note:', 'tw-backup-365' ); ?></strong> 
				<?php esc_html_e( 'If using Nginx, disable directory listing. Randomized filenames are active.', 'tw-backup-365' ); ?></p>
			</div>

			<p class="description">
				<?php printf( esc_html__( 'Storage Path: %s', 'tw-backup-365' ), '<code id="storage-path" style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom;">' . esc_html( $backup_path ) . '</code>' ); ?>
				<button type="button" class="button button-small copy-btn" data-clipboard-target="#storage-path">
					<span class="dashicons dashicons-clipboard" style="margin-top:2px;"></span> <?php esc_html_e( 'Copy Path', 'tw-backup-365' ); ?>
				</button>
			</p>
			
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="margin-top: 15px;">
				<?php wp_nonce_field( 'tw_backup_action', 'tw_backup_nonce' ); ?>
				<input type="hidden" name="action" value="tw_backup_trigger">
				
				<?php if ( $is_cooldown ) : 
					$remaining = $is_cooldown - time();
					if ( $remaining < 0 ) $remaining = 0;
				?>
					<input type="submit" 
						   id="tw-cooldown-btn" 
						   class="button button-secondary" 
						   disabled 
						   value="<?php echo esc_attr( sprintf( __( 'Please wait %s', 'tw-backup-365' ), gmdate( 'i:s', $remaining ) ) ); ?>"
						   data-remaining="<?php echo esc_attr( $remaining ); ?>"
						   data-ready-text="<?php esc_attr_e( 'Start Secure Backup', 'tw-backup-365' ); ?>"
						   data-wait-text="<?php esc_attr_e( 'Please wait', 'tw-backup-365' ); ?>"
					>
				<?php else : ?>
					<?php submit_button( __( 'Start Secure Backup', 'tw-backup-365' ), 'primary' ); ?>
				<?php endif; ?>
			</form>
		</div>
	</div>

	<div class="card" style="margin-top:20px; padding: 0; max-width: none;">
		<div style="padding: 15px; border-bottom: 1px solid #eee;">
			<h3 style="margin:0;"><?php esc_html_e( 'Backup History', 'tw-backup-365' ); ?></h3>
		</div>
		
		<?php if ( empty( $backups ) ) : ?>
			<div style="padding: 20px;">
				<p><?php esc_html_e( 'No backups found.', 'tw-backup-365' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 200px;"><?php esc_html_e( 'Date', 'tw-backup-365' ); ?></th>
						<th><?php esc_html_e( 'Backup ID (Hash)', 'tw-backup-365' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Total Size', 'tw-backup-365' ); ?></th>
						<th style="width: 150px; text-align: right;"><?php esc_html_e( 'Actions', 'tw-backup-365' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $backups as $hash => $group ) : ?>
						<tr>
							<td><strong><?php echo esc_html( wp_date( 'Y-m-d H:i:s', strtotime( $group['date'] ) ) ); ?></strong></td>
							<td>
								<code><?php echo esc_html( $hash ); ?></code><br>
								<span class="description"><?php printf( esc_html__( '%d parts found', 'tw-backup-365' ), count( $group['files'] ) ); ?></span>
							</td>
							<td><?php echo size_format( $group['total_size'] ); ?></td>
							<td style="text-align: right;">
								<button type="button" class="button toggle-details" data-target="details-<?php echo esc_attr( $hash ); ?>">
									<?php esc_html_e( 'View Files', 'tw-backup-365' ); ?>
								</button>
							</td>
						</tr>
						<tr id="details-<?php echo esc_attr( $hash ); ?>" style="display:none;" class="backup-details-row">
							<td colspan="4" style="background-color: #f9f9f9; padding: 10px 20px;">
								<p class="description" style="margin-top:0;"><strong><?php esc_html_e( 'File List (Download via FTP):', 'tw-backup-365' ); ?></strong></p>
								<ul style="list-style: disc; margin-left: 20px; margin-bottom: 0;">
									<?php foreach ( $group['files'] as $file ) : ?>
										<li><?php echo esc_html( $file['name'] ); ?> <span style="color:#888;">(<?php echo size_format( $file['size'] ); ?>)</span></li>
									<?php endforeach; ?>
								</ul>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		
		<div style="padding: 15px; background: #fff; border-top: 1px solid #eee;">
			<p class="description">
				<strong><?php esc_html_e( 'How to Restore:', 'tw-backup-365' ); ?></strong>
				<?php esc_html_e( 'Download all parts (.001, .002...) via FTP. Extract the first file (.001) to auto-merge.', 'tw-backup-365' ); ?>
			</p>
		</div>
	</div>
	
	<?php 
	// 狀態通知
	if ( isset( $_GET['status'] ) ) {
		$status = $_GET['status'];
		$class = 'error';
		$msg = __( 'Backup failed.', 'tw-backup-365' );
		if ( $status === 'success' ) { $class = 'success'; $msg = __( 'Backup completed successfully!', 'tw-backup-365' ); }
		elseif ( $status === 'ratelimit' ) { $msg = __( 'Please wait before next backup.', 'tw-backup-365' ); }
		elseif ( $status === 'nospace' ) { $msg = __( 'Error: Insufficient disk space for backup.', 'tw-backup-365' ); }
		elseif ( $status === 'highload' ) { $msg = __( 'Error: Server load is too high. Try again later.', 'tw-backup-365' ); }
		echo '<div class="notice notice-' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
	?>
</div>
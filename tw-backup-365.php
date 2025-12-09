<?php
/**
 * Plugin Name:       tw_backup_365
 * Plugin URI:        https://example.com/tw-backup-365
 * Description:       Secure Full Site Backup with Smart Grouping & Performance Hotfix.
 * Version:           1.4.1 (Performance Hotfix)
 * Author:            Your Name
 * Text Domain:       tw-backup-365
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tw_Backup_365 {

	private $chunk_size = 20 * 1024 * 1024; // 20MB
	private $cooldown_time = 600; // 10 Minutes
	private $backup_dir;
	private $backup_url;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->backup_dir = $upload_dir['basedir'] . '/tw-backup-secure';
		$this->backup_url = $upload_dir['baseurl'] . '/tw-backup-secure';

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_tw_backup_trigger', array( $this, 'handle_backup_process' ) );
		
		add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'tw-backup-365', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Backup 365', 'tw-backup-365' ),
			__( 'Backup 365', 'tw-backup-365' ),
			'manage_options',
			'tw-backup-365',
			array( $this, 'render_admin_page' ),
			'dashicons-shield-alt'
		);
	}

	public function render_admin_page() {
		$this->check_secure_dir();
		$grouped_backups = $this->get_grouped_backups();
		$is_cooldown = get_transient( 'tw_backup_cooldown' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'TW Backup 365 - v1.4.1', 'tw-backup-365' ); ?></h1>
			<hr class="wp-header-end">
			
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'One-Click Secure Backup', 'tw-backup-365' ); ?></h2>
				<p><?php esc_html_e( 'Backups are split into 20MB parts with randomized filenames for maximum security.', 'tw-backup-365' ); ?></p>
				
				<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px;">
					<p><strong><?php esc_html_e( 'Security Note:', 'tw-backup-365' ); ?></strong> 
					<?php esc_html_e( 'If using Nginx, disable directory listing. Randomized filenames are active.', 'tw-backup-365' ); ?></p>
				</div>

				<p class="description">
					<?php printf( esc_html__( 'Storage Path: %s', 'tw-backup-365' ), '<code id="storage-path">' . esc_html( $this->backup_dir ) . '</code>' ); ?>
					<button type="button" class="button button-small copy-btn" data-clipboard-target="#storage-path">
						<span class="dashicons dashicons-clipboard" style="margin-top:2px;"></span> <?php esc_html_e( 'Copy Path', 'tw-backup-365' ); ?>
					</button>
				</p>
				
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<?php wp_nonce_field( 'tw_backup_action', 'tw_backup_nonce' ); ?>
					<input type="hidden" name="action" value="tw_backup_trigger">
					
					<?php if ( $is_cooldown ) : ?>
						<button type="button" class="button button-secondary" disabled>
							<?php printf( esc_html__( 'Wait %d min', 'tw-backup-365' ), ceil( ( $is_cooldown - time() ) / 60 ) ); ?>
						</button>
					<?php else : ?>
						<?php submit_button( __( 'Start Secure Backup', 'tw-backup-365' ), 'primary' ); ?>
					<?php endif; ?>
				</form>
			</div>

			<div class="card" style="margin-top:20px; max-width: 800px; padding: 0;">
				<div style="padding: 15px; border-bottom: 1px solid #eee;">
					<h3 style="margin:0;"><?php esc_html_e( 'Backup History', 'tw-backup-365' ); ?></h3>
				</div>
				
				<?php if ( empty( $grouped_backups ) ) : ?>
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
							<?php foreach ( $grouped_backups as $hash => $group ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( date( 'Y-m-d H:i:s', strtotime( $group['date'] ) ) ); ?></strong>
									</td>
									<td>
										<code><?php echo esc_html( $hash ); ?></code>
										<br>
										<span class="description"><?php printf( esc_html__( '%d parts found', 'tw-backup-365' ), count( $group['files'] ) ); ?></span>
									</td>
									<td>
										<?php echo size_format( $group['total_size'] ); ?>
									</td>
									<td style="text-align: right;">
										<button type="button" class="button toggle-details" data-target="details-<?php echo esc_attr( $hash ); ?>">
											<?php esc_html_e( 'View Files', 'tw-backup-365' ); ?>
										</button>
									</td>
								</tr>
								<tr id="details-<?php echo esc_attr( $hash ); ?>" style="display:none;" class="backup-details-row">
									<td colspan="4" style="background-color: #f9f9f9; padding: 10px 20px;">
										<p class="description" style="margin-top:0;">
											<strong><?php esc_html_e( 'File List (Download via FTP):', 'tw-backup-365' ); ?></strong>
										</p>
										<ul style="list-style: disc; margin-left: 20px; margin-bottom: 0;">
											<?php foreach ( $group['files'] as $file ) : ?>
												<li>
													<?php echo esc_html( $file['name'] ); ?> 
													<span style="color:#888;">(<?php echo size_format( $file['size'] ); ?>)</span>
												</li>
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
			
			<?php $this->render_status_notice(); ?>
		</div>
		<?php
	}

	private function get_grouped_backups() {
		$files = glob( $this->backup_dir . '/*' );
		$groups = [];

		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) continue;
			$basename = basename( $file );
			if ( in_array( $basename, ['.htaccess', 'web.config', 'index.php', 'index.html'] ) ) continue;

			// Name_YYYYMMDDHHMMSS_Hash.zip.part
			if ( preg_match( '/^(.+)_(\d{14})_([a-zA-Z0-9]{12})\.zip(\.\d+)?$/', $basename, $matches ) ) {
				$date = $matches[2];
				$hash = $matches[3];
				if ( ! isset( $groups[ $hash ] ) ) {
					$groups[ $hash ] = [ 'date' => $date, 'total_size' => 0, 'files' => [] ];
				}
				$size = filesize( $file );
				$groups[ $hash ]['total_size'] += $size;
				$groups[ $hash ]['files'][] = [ 'name' => $basename, 'size' => $size ];
			}
		}

		uasort( $groups, function( $a, $b ) {
			return strcmp( $b['date'], $a['date'] );
		} );

		return $groups;
	}

	public function admin_footer_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'tw-backup-365' ) :
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.toggle-details').on('click', function() {
				var target = '#' + $(this).data('target');
				$(target).toggle();
			});
			$('.copy-btn').on('click', function() {
				var $temp = $("<input>");
				$("body").append($temp);
				$temp.val($('#storage-path').text()).select();
				document.execCommand("copy");
				$temp.remove();
				var originalText = $(this).html();
				var $btn = $(this);
				$btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(function() { $btn.html(originalText); }, 2000);
			});
		});
		</script>
		<style>
			.backup-details-row td { box-shadow: inset 0 3px 5px rgba(0,0,0,0.05); }
			.wp-list-table th { font-weight: 600; }
		</style>
		<?php
		endif;
	}

	private function render_status_notice() {
		if ( isset( $_GET['status'] ) ) {
			$class = ( $_GET['status'] === 'success' ) ? 'success' : 'error';
			$msg = ( $_GET['status'] === 'success' ) ? __( 'Backup completed successfully!', 'tw-backup-365' ) : __( 'Backup failed.', 'tw-backup-365' );
			if ( $_GET['status'] === 'ratelimit' ) $msg = __( 'Please wait before next backup.', 'tw-backup-365' );
			echo '<div class="notice notice-' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	private function check_secure_dir() {
		if ( ! file_exists( $this->backup_dir ) ) wp_mkdir_p( $this->backup_dir );
		if ( ! file_exists( $this->backup_dir . '/.htaccess' ) ) file_put_contents( $this->backup_dir . '/.htaccess', "Order Deny,Allow\nDeny from all" );
		if ( ! file_exists( $this->backup_dir . '/web.config' ) ) file_put_contents( $this->backup_dir . '/web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' );
		if ( ! file_exists( $this->backup_dir . '/index.php' ) ) file_put_contents( $this->backup_dir . '/index.php', '<?php // Silence is golden.' );
	}

	public function handle_backup_process() {
		if ( ! isset( $_POST['tw_backup_nonce'] ) || ! wp_verify_nonce( $_POST['tw_backup_nonce'], 'tw_backup_action' ) || ! current_user_can( 'manage_options' ) ) wp_die( 'Security check failed.' );
		if ( get_transient( 'tw_backup_cooldown' ) ) $this->redirect_status( 'ratelimit' );

		set_time_limit( 0 );
		ini_set( 'memory_limit', '512M' );
		set_transient( 'tw_backup_cooldown', time() + $this->cooldown_time, $this->cooldown_time );

		try { $random_hash = bin2hex( random_bytes( 6 ) ); } catch ( Exception $e ) { $random_hash = wp_generate_password( 12, false ); }
		$site_name = sanitize_file_name( get_bloginfo( 'name' ) );
		if ( function_exists( 'mb_substr' ) ) { $site_name = mb_substr( $site_name, 0, 15, 'UTF-8' ); } else { $site_name = substr( $site_name, 0, 15 ); }
		$date_str = date( 'YmdHis' );
		
		$temp_zip_name = $site_name . '_' . $date_str . '_' . $random_hash . '.zip';
		$temp_zip_path = $this->backup_dir . '/' . $temp_zip_name;
		$sql_file = 'db_backup.sql';
		$sql_path = sys_get_temp_dir() . '/' . $sql_file;
		
		if ( $this->dump_database( $sql_path ) && $this->zip_website_optimized( $temp_zip_path, $sql_path, $sql_file ) ) {
			@unlink( $sql_path );
			if ( $this->split_file( $temp_zip_path ) ) {
				$this->redirect_status( 'success' );
			}
		}
		$this->redirect_status( 'error' );
	}

	private function redirect_status( $status ) {
		wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=' . $status ) );
		exit;
	}

	private function dump_database( $output_file ) {
		global $wpdb;
		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
		$handle = fopen( $output_file, 'w' );
		if ( ! $handle ) return false;
		fwrite( $handle, "-- WordPress Database Backup\n\n" );
		foreach ( $tables as $table ) {
			$table_name = $table[0];
			$create_table = $wpdb->get_row( "SHOW CREATE TABLE `$table_name`", ARRAY_N );
			fwrite( $handle, "\n" . $create_table[1] . ";\n\n" );
			$rows = $wpdb->get_results( "SELECT * FROM `$table_name`", ARRAY_A );
			if ( $rows ) {
				foreach ( $rows as $row ) {
					$row = array_map( array( $wpdb, '_real_escape' ), $row );
					fwrite( $handle, "INSERT INTO `$table_name` VALUES ('" . implode( "', '", $row ) . "');\n" );
				}
			}
		}
		fclose( $handle );
		return true;
	}

	/**
	 * Hotfix v1.4.1: 效能優化版
	 * 移除循環開關邏輯，改用單次 close，提升 ZipArchive::addFile 效率
	 */
	private function zip_website_optimized( $zip_destination, $sql_path, $sql_filename_in_zip ) {
		if ( ! class_exists( 'ZipArchive' ) ) return false;
		
		$zip = new ZipArchive();
		// 確保建立新檔並覆蓋舊檔
		if ( $zip->open( $zip_destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) return false;
		
		// 加入資料庫
		$zip->addFile( $sql_path, $sql_filename_in_zip );
		
		$root_path = realpath( ABSPATH );
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root_path, RecursiveDirectoryIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
		
		foreach ( $files as $name => $file ) {
			if ( strpos( $file->getRealPath(), 'tw-backup-secure' ) !== false ) continue;
			if ( $file->getExtension() === 'zip' || $file->getExtension() === 'gz' ) continue;
			
			if ( ! $file->isDir() ) {
				// 這裡不再進行 close/open 循環，因為 addFile 只會加入參照，記憶體消耗極低
				$zip->addFile( $file->getRealPath(), substr( $file->getRealPath(), strlen( $root_path ) + 1 ) );
			}
		}
		
		// 最後一次性寫入磁碟，效率最高
		$zip->close();
		return true;
	}

	private function split_file( $source_file ) {
		if ( ! file_exists( $source_file ) ) return false;
		$handle = fopen( $source_file, 'rb' );
		$part_num = 1;
		while ( ! feof( $handle ) ) {
			$part_handle = fopen( $source_file . '.' . sprintf( '%03d', $part_num ), 'wb' );
			$written = 0;
			while ( $written < $this->chunk_size && ! feof( $handle ) ) {
				$data = fread( $handle, 1024 * 1024 );
				fwrite( $part_handle, $data );
				$written += strlen( $data );
			}
			fclose( $part_handle );
			$part_num++;
		}
		fclose( $handle );
		unlink( $source_file );
		return true;
	}
}

new Tw_Backup_365();
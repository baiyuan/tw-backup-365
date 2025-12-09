<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Tw_Backup_Admin_Page {

	private $engine;
	private $cooldown_time = 600;

	public function __construct() {
		$this->engine = new Tw_Backup_Engine();
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_tw_backup_trigger', array( $this, 'handle_post_request' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'Backup 365', 'tw-backup-365' ),
			__( 'Backup 365', 'tw-backup-365' ),
			'manage_options',
			'tw-backup-365',
			array( $this, 'render_view' ),
			'dashicons-shield-alt'
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_tw-backup-365' !== $hook ) return;

		// 載入獨立 CSS (給 Grok)
		wp_enqueue_style( 'tw-backup-admin-css', TW_BACKUP_URL . 'assets/css/admin.css', array(), TW_BACKUP_VERSION );

		// 載入獨立 JS (給 Chat GPT)
		wp_enqueue_script( 'tw-backup-admin-js', TW_BACKUP_URL . 'assets/js/admin.js', array( 'jquery' ), TW_BACKUP_VERSION, true );
	}

	public function handle_post_request() {
		if ( ! isset( $_POST['tw_backup_nonce'] ) || ! wp_verify_nonce( $_POST['tw_backup_nonce'], 'tw_backup_action' ) || ! current_user_can( 'manage_options' ) ) wp_die( 'Security check failed.' );
		
		if ( get_transient( 'tw_backup_cooldown' ) ) {
			wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=ratelimit' ) );
			exit;
		}

		// 預檢
		set_time_limit( 0 );
		ini_set( 'memory_limit', '512M' );
		
		if ( $this->get_server_load() > 5.0 ) {
			wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=highload' ) );
			exit;
		}
		if ( disk_free_space( $this->engine->get_backup_dir() ) < ( $this->get_directory_size( ABSPATH ) * 1.2 ) ) {
			wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=nospace' ) );
			exit;
		}

		set_transient( 'tw_backup_cooldown', time() + $this->cooldown_time, $this->cooldown_time );

		if ( $this->engine->execute_backup() ) {
			wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=success' ) );
		} else {
			wp_redirect( admin_url( 'admin.php?page=tw-backup-365&status=error' ) );
		}
		exit;
	}

	public function render_view() {
		// 1. 準備數據 (Data Preparation)
		$this->engine->check_secure_dir();
		
		$data = [
			'disk_free'    => disk_free_space( $this->engine->get_backup_dir() ),
			'disk_total'   => disk_total_space( $this->engine->get_backup_dir() ),
			'cpu_load'     => $this->get_server_load(),
			'memory_limit' => ini_get( 'memory_limit' ),
			'backup_path'  => $this->engine->get_backup_dir(),
			'backups'      => $this->get_grouped_backups(),
			'is_cooldown'  => get_transient( 'tw_backup_cooldown' ),
			'version'      => TW_BACKUP_VERSION
		];

		// 2. 載入模板
		$this->load_template( 'dashboard', $data );
	}

	private function load_template( $name, $args = [] ) {
		if ( ! empty( $args ) ) extract( $args );
		include TW_BACKUP_PATH . 'templates/' . $name . '.php';
	}

	// Helper functions (保留原有的 helper 邏輯)
	private function get_grouped_backups() {
		$files = glob( $this->engine->get_backup_dir() . '/*' );
		$groups = [];
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) continue;
			$basename = basename( $file );
			if ( in_array( $basename, ['.htaccess', 'web.config', 'index.php', 'index.html'] ) ) continue;
			if ( preg_match( '/^(.+)_(\d{14})_([a-zA-Z0-9]{12})\.zip(\.\d+)?$/', $basename, $matches ) ) {
				$date = $matches[2];
				$hash = $matches[3];
				if ( ! isset( $groups[ $hash ] ) ) $groups[ $hash ] = [ 'date' => $date, 'total_size' => 0, 'files' => [] ];
				$groups[ $hash ]['total_size'] += filesize( $file );
				$groups[ $hash ]['files'][] = [ 'name' => $basename, 'size' => filesize( $file ) ];
			}
		}
		uasort( $groups, function( $a, $b ) { return strcmp( $b['date'], $a['date'] ); } );
		return $groups;
	}

	private function get_server_load() {
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();
			return is_array( $load ) ? $load[0] : false;
		}
		return false;
	}

	private function get_directory_size( $path ) {
		$size = 0;
		$ignore = array( '.', '..', 'tw-backup-secure' );
		$files = scandir( $path );
		foreach ( $files as $t ) {
			if ( in_array( $t, $ignore ) ) continue;
			if ( is_dir( rtrim( $path, '/' ) . '/' . $t ) ) {
				$size += $this->get_directory_size( rtrim( $path, '/' ) . '/' . $t );
			} else {
				$size += filesize( rtrim( $path, '/' ) . '/' . $t );
			}
		}
		return $size;
	}
}
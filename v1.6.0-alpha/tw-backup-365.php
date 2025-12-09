<?php
/**
 * Plugin Name:       tw_backup_365
 * Plugin URI:        https://example.com/tw-backup-365
 * Description:       Secure Full Site Backup with MVC Architecture (v1.6.0-alpha).
 * Version:           1.6.0-alpha
 * Author:            Your Name
 * Text Domain:       tw-backup-365
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定義常數，方便全域使用
define( 'TW_BACKUP_VERSION', '1.6.0-alpha' );
define( 'TW_BACKUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'TW_BACKUP_URL', plugin_dir_url( __FILE__ ) );

// 載入核心類別
require_once TW_BACKUP_PATH . 'includes/class-backup-engine.php';
require_once TW_BACKUP_PATH . 'includes/class-admin-page.php';

// 初始化外掛
function run_tw_backup_365() {
	// 載入語系
	load_plugin_textdomain( 'tw-backup-365', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// 啟動 Admin 頁面控制器
	$admin_page = new Tw_Backup_Admin_Page();
	$admin_page->init();
}
add_action( 'plugins_loaded', 'run_tw_backup_365' );
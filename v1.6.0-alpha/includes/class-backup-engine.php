<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Tw_Backup_Engine {

	private $chunk_size = 20 * 1024 * 1024; // 20MB
	private $backup_dir;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->backup_dir = $upload_dir['basedir'] . '/tw-backup-secure';
	}

	public function get_backup_dir() {
		return $this->backup_dir;
	}

	public function check_secure_dir() {
		if ( ! file_exists( $this->backup_dir ) ) wp_mkdir_p( $this->backup_dir );
		if ( ! file_exists( $this->backup_dir . '/.htaccess' ) ) file_put_contents( $this->backup_dir . '/.htaccess', "Order Deny,Allow\nDeny from all" );
		if ( ! file_exists( $this->backup_dir . '/web.config' ) ) file_put_contents( $this->backup_dir . '/web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' );
		if ( ! file_exists( $this->backup_dir . '/index.php' ) ) file_put_contents( $this->backup_dir . '/index.php', '<?php // Silence is golden.' );
	}

	public function execute_backup() {
		$this->check_secure_dir();
		
		// 產生檔名
		try { $random_hash = bin2hex( random_bytes( 6 ) ); } catch ( Exception $e ) { $random_hash = wp_generate_password( 12, false ); }
		$site_name = sanitize_file_name( get_bloginfo( 'name' ) );
		if ( function_exists( 'mb_substr' ) ) { $site_name = mb_substr( $site_name, 0, 15, 'UTF-8' ); } else { $site_name = substr( $site_name, 0, 15 ); }
		$date_str = date( 'YmdHis' );
		
		$temp_zip_name = $site_name . '_' . $date_str . '_' . $random_hash . '.zip';
		$temp_zip_path = $this->backup_dir . '/' . $temp_zip_name;
		$sql_file = 'db_backup.sql';
		$sql_path = sys_get_temp_dir() . '/' . $sql_file;

		// 執行流程
		if ( $this->dump_database( $sql_path ) && $this->zip_website_optimized( $temp_zip_path, $sql_path, $sql_file ) ) {
			@unlink( $sql_path );
			if ( $this->split_file( $temp_zip_path ) ) {
				return true;
			}
		}
		return false;
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

	private function zip_website_optimized( $zip_destination, $sql_path, $sql_filename_in_zip ) {
		if ( ! class_exists( 'ZipArchive' ) ) return false;
		$zip = new ZipArchive();
		if ( $zip->open( $zip_destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) return false;
		$zip->addFile( $sql_path, $sql_filename_in_zip );
		$root_path = realpath( ABSPATH );
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root_path, RecursiveDirectoryIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
		foreach ( $files as $name => $file ) {
			if ( strpos( $file->getRealPath(), 'tw-backup-secure' ) !== false ) continue;
			if ( $file->getExtension() === 'zip' || $file->getExtension() === 'gz' ) continue;
			if ( ! $file->isDir() ) {
				$zip->addFile( $file->getRealPath(), substr( $file->getRealPath(), strlen( $root_path ) + 1 ) );
			}
		}
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
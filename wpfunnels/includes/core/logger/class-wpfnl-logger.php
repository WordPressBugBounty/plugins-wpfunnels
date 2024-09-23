<?php
/**
 * Logger 
 * 
 * @package
 */
use WPFunnels\Traits\SingletonTrait;

class Wpfnl_Logger {

	use SingletonTrait;



	/**
	 * Log location, URL path.
	 *
	 * @since 2.5.9
	 * @var   string
	 */
	protected $log_location_url = '';

	/**
	 * Log location, server path.
	 * 
	 * @since 2.5.9
	 * @var   string
	 */
	protected $log_location_file = '';

	/**
	 * Log directory location, server path.
	 *
	 * @since 2.5.9
	 * @var   string
	 */
	protected $log_location_dir = '';

	/**
	 * The location of the log folder's index file.
	 *
	 * @since 2.5.9
	 * @var   string
	 */
	protected $log_index_file = '';

	/**
	 * The logging directory name.
	 *
	 * @since 2.5.9
	 * @var   string
	 */
	protected $log_file_dir = 'wpfunnels/wpfunnels-logs';


	/**
	 * Constructor
	 * 
	 * @return void
	 * @since  2.5.9
	 */
	public function __construct() {

		$uploads_dir             = wp_upload_dir();
		$log_file_name           = 'wpfunnels';
		$this->log_location_url  = "{$uploads_dir['baseurl']}/{$this->log_file_dir}/{$log_file_name}";
		$this->log_location_dir  = "{$uploads_dir['basedir']}/{$this->log_file_dir}";
		$this->log_location_file = "{$this->log_location_dir}/{$log_file_name}";
		$this->log_index_file    = "{$this->log_location_dir}/index.php";

		define('WPFNL_LOG_FILE_DIR', $this->log_location_dir );
		define('WPFNL_LOG_FILE', $this->log_location_file);
	}


	/**
	 * Create the log folder.
	 *
	 * @since 2.5.9
	 */
	public function create_log_folder() {
		wp_mkdir_p( $this->log_location_dir );
	}


	/**
	 * Create log file.
	 *
	 * @param String $log_location_dir
	 * @param String $file_name
	 * 
	 * @since  2.5.9
	 * @return void
	 */
	public static function create_log_file( $log_location_dir, $file_name ) {
		if ( ! is_writable( $log_location_dir ) ) {
			return;
		}

		if ( file_exists( $file_name ) ) {
			return;
		}

		touch( $file_name );
	}


	/**
	 * Retrieve logging file location.
	 *
	 * @return string Logging file location.
	 * 
	 * @since 2.5.9
	 */
	public function get_logging_location() {
		return $this->log_location_file;
	}


	

	/**
	 * Initialize Logging directory
	 *
	 * @since 2.5.9
	 */
	public function initialize_logging() {
		$this->create_log_folder();
	}


	/**
	 * Update log file
	 * 
	 * @param String $log_type
	 * @param Mix $content
	 * @param String $header_text
	 * 
	 * @since 2.5.9
	 */
	public static function modify_log_file( $log_type, $content, $header_text = '' ){
		
		if( $log_type && $content && defined( 'WPFNL_LOG_FILE' ) && defined( 'WPFNL_LOG_FILE_DIR' ) ){
	
			$file_name = WPFNL_LOG_FILE.'-'.trim(strtolower($log_type)).'-log-'.date('Y-m-d').'.log';
			$time = new \DateTimeImmutable('now', wp_timezone());
			$current_time = $time->format("h:i A");
			$header_content = "\nWPFunnels - ".$current_time." - ".$header_text."\n";
			
			if ( !file_exists( $file_name ) ) {
				self::create_log_file( WPFNL_LOG_FILE_DIR, $file_name );
			}

			if ( ! is_writable( $file_name ) ) {
				return;
			}

			file_put_contents ($file_name, $header_content, FILE_APPEND );
			file_put_contents ($file_name, $content, FILE_APPEND );

		}
	}


	/**
	 * Delete existing log files.
	 *
	 * @since 2.5.9
	 *
	 * @return Bool
	 */
	public static function delete_log_file( $file_name ) {
		if ( file_exists( $file_name ) ) {
			unlink( $file_name );
			return true;
		}
		return false;
	}


	/**
	 * Get all log files in the log directory.
	 *
	 * @since  2.5.9
	 * @return array
	 */
	public static function get_log_files() {
		$files  = @scandir( WPFNL_LOG_FILE_DIR ); // @codingStandardsIgnoreLine.
		$result = array();
		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..' ), true ) ) {
					if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
						$result[ sanitize_title( $value ) ] = $value;
					}
				}
			}
		}

		return $result;
	}
}

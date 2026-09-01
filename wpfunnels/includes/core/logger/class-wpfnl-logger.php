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
	 * Bumped whenever the directory hardening below changes, so an install
	 * hardened by an earlier release re-runs it once on upgrade.
	 *
	 * @since 3.12.14
	 */
	const PROTECTION_VERSION = '2';

	/**
	 * Option holding the hardening version already applied.
	 *
	 * @since 3.12.14
	 */
	const PROTECTION_OPTION = '_wpfnl_log_protection_version';



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
		$this->protect_log_folder();
	}


	/**
	 * Harden the log directory.
	 *
	 * Server config files cannot carry this on their own: nginx never reads
	 * .htaccess, and neither does Apache with AllowOverride None, so on those
	 * hosts a log file under uploads stays fetchable no matter what is written
	 * beside it. The unguessable file name suffix is therefore the defence that
	 * holds everywhere, and the rules written here are the second layer for
	 * servers that do honour them.
	 *
	 * The legacy directory used up to 3.0.5 is hardened too - installs that
	 * upgraded through that release still have readable logs sitting there.
	 *
	 * @since 3.12.8
	 */
	protected function protect_log_folder() {
		// Runs on every page load, so the settled state must cost nothing beyond
		// one autoloaded option read.
		if ( get_option( self::PROTECTION_OPTION ) === self::PROTECTION_VERSION ) {
			return;
		}

		$uploads = wp_upload_dir( null, false );
		$legacy  = ! empty( $uploads['basedir'] ) ? $uploads['basedir'] . '/wpfunnels-logs' : '';

		foreach ( array( $this->log_location_dir, $legacy ) as $dir ) {
			if ( ! $dir || ! is_dir( $dir ) || ! is_writable( $dir ) ) {
				continue;
			}

			$this->write_protection_files( $dir, true );
			$this->rename_predictable_logs( $dir );
		}

		update_option( self::PROTECTION_OPTION, self::PROTECTION_VERSION );
	}


	/**
	 * Write the deny rules and placeholders into a log directory.
	 *
	 * @param String $dir     Directory to protect.
	 * @param Bool   $refresh Rewrite files that already exist.
	 *
	 * @since 3.12.14
	 */
	protected function write_protection_files( $dir, $refresh = false ) {
		$dir = trailingslashit( $dir );

		// Order/Deny is Apache 2.2 syntax. On a 2.4 build without
		// mod_access_compat it is an unknown directive, which fails the whole
		// directory with a 500 instead of denying anything - hence the guards.
		$htaccess = $dir . '.htaccess';
		if ( $refresh || ! file_exists( $htaccess ) ) {
			$rules  = "# WPFunnels log files. Deny direct web access.\n";
			$rules .= "<IfModule mod_authz_core.c>\n";
			$rules .= "\tRequire all denied\n";
			$rules .= "</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\n";
			$rules .= "\tOrder deny,allow\n";
			$rules .= "\tDeny from all\n";
			$rules .= "</IfModule>\n";
			$rules .= "Options -Indexes\n";

			@file_put_contents( $htaccess, $rules ); // @codingStandardsIgnoreLine.
		}

		$index = $dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // @codingStandardsIgnoreLine.
		}

		$web_config = $dir . 'web.config';
		if ( $refresh || ! file_exists( $web_config ) ) {
			$config  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			$config .= "<configuration>\n";
			$config .= "\t<system.webServer>\n";
			$config .= "\t\t<security>\n";
			$config .= "\t\t\t<requestFiltering>\n";
			$config .= "\t\t\t\t<fileExtensions>\n";
			$config .= "\t\t\t\t\t<add fileExtension=\".log\" allowed=\"false\" />\n";
			$config .= "\t\t\t\t</fileExtensions>\n";
			$config .= "\t\t\t</requestFiltering>\n";
			$config .= "\t\t</security>\n";
			$config .= "\t</system.webServer>\n";
			$config .= "</configuration>\n";

			@file_put_contents( $web_config, $config ); // @codingStandardsIgnoreLine.
		}
	}


	/**
	 * Rename log files still carrying a guessable name.
	 *
	 * Files written before 3.12.14 are named from a fixed prefix and the date
	 * alone, so hardening new writes is not enough - the existing files stay
	 * fetchable. Renaming keeps the contents and closes the exposure.
	 *
	 * @param String $dir Directory to migrate.
	 *
	 * @since 3.12.14
	 */
	protected function rename_predictable_logs( $dir ) {
		$suffix = self::get_log_file_suffix();
		$files  = glob( trailingslashit( $dir ) . '*.log' );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( false !== strpos( basename( $file ), $suffix ) ) {
				continue;
			}

			$target = substr( $file, 0, -4 ) . '-' . $suffix . '.log';
			if ( ! file_exists( $target ) ) {
				@rename( $file, $target ); // @codingStandardsIgnoreLine.
			}
		}
	}


	/**
	 * Site-specific suffix for log file names.
	 *
	 * Derived from the site salts, so it is stable for a site and unguessable
	 * from outside it. This is what keeps the logs private on servers that
	 * ignore .htaccess.
	 *
	 * @return String
	 * @since 3.12.14
	 */
	public static function get_log_file_suffix() {
		return wp_hash( 'wpfnl-log-file-name' );
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
	
			$file_name = WPFNL_LOG_FILE.'-'.trim(strtolower($log_type)).'-log-'.date('Y-m-d').'-'.self::get_log_file_suffix().'.log';
			$time = new \DateTimeImmutable('now', wp_timezone());
			$current_time = $time->format("h:i A");
			$header_content = "\nWPFunnels - ".$current_time." - ".$header_text."\n";
			
			if ( !file_exists( $file_name ) ) {
				self::create_log_file( WPFNL_LOG_FILE_DIR, $file_name );
			}

			if ( ! is_writable( $file_name ) ) {
				return;
			}

			// Neutralise any PHP open tags in attacker-controlled content so the
			// log file can never be executed if it is ever included by PHP.
			$header_content = self::sanitize_log_content( $header_content );
			$content        = is_scalar( $content ) ? self::sanitize_log_content( (string) $content ) : '';

			file_put_contents ($file_name, $header_content, FILE_APPEND );
			file_put_contents ($file_name, $content, FILE_APPEND );

		}
	}


	/**
	 * Strip PHP open tags from log content to prevent code execution.
	 *
	 * @param String $content
	 *
	 * @return String
	 * @since 3.12.8
	 */
	protected static function sanitize_log_content( $content ) {
		return str_ireplace( array( '<?php', '<?=', '<?' ), '', $content );
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

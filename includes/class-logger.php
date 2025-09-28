<?php
/**
 * Logger handler.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

class Spam_Slayer_5000_Logger {

	/**
	 * Log levels.
	 *
	 * @var array
	 */
	private $levels = array(
		'debug' => 0,
		'info' => 1,
		'warning' => 2,
		'error' => 3,
		'critical' => 4,
	);

	/**
	 * Log directory.
	 *
	 * @var string
	 */
	private $log_dir;

	/**
	 * Log filename.
	 *
	 * @var string
	 */
	private $log_file = 'spam-slayer-5000.log';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->log_dir = $upload_dir['basedir'] . '/spam-slayer-5000/';
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    string    $level      Log level.
	 * @param    array     $context    Additional context.
	 */
	public function log( $message, $level = 'info', $context = array() ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$this->ensure_log_directory();

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$formatted_message = sprintf(
			"[%s] %s: %s",
			$timestamp,
			strtoupper( $level ),
			$message
		);

		if ( ! empty( $context ) ) {
			$formatted_message .= ' | Context: ' . wp_json_encode( $context );
		}

		$formatted_message .= PHP_EOL;

		error_log( $formatted_message, 3, $this->log_dir . $this->log_file );
	}

	/**
	 * Log debug message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    array     $context    Additional context.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( $message, 'debug', $context );
	}

	/**
	 * Log info message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    array     $context    Additional context.
	 */
	public function info( $message, $context = array() ) {
		$this->log( $message, 'info', $context );
	}

	/**
	 * Log warning message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    array     $context    Additional context.
	 */
	public function warning( $message, $context = array() ) {
		$this->log( $message, 'warning', $context );
	}

	/**
	 * Log error message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    array     $context    Additional context.
	 */
	public function error( $message, $context = array() ) {
		$this->log( $message, 'error', $context );
	}

	/**
	 * Log critical message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log.
	 * @param    array     $context    Additional context.
	 */
	public function critical( $message, $context = array() ) {
		$this->log( $message, 'critical', $context );
	}

	/**
	 * Get log contents.
	 *
	 * @since    1.0.0
	 * @param    int       $lines    Number of lines to retrieve.
	 * @return   string              Log contents.
	 */
	public function get_log( $lines = 100 ) {
		$log_path = $this->log_dir . $this->log_file;

		if ( ! file_exists( $log_path ) ) {
			return '';
		}

		$file = new SplFileObject( $log_path );
		$file->seek( PHP_INT_MAX );
		$total_lines = $file->key();

		$start_line = max( 0, $total_lines - $lines );
		$log_lines = array();

		for ( $i = $start_line; $i <= $total_lines; $i++ ) {
			$file->seek( $i );
			$line = $file->current();
			if ( ! empty( trim( $line ) ) ) {
				$log_lines[] = $line;
			}
		}

		return implode( '', $log_lines );
	}

	/**
	 * Clear log file.
	 *
	 * @since    1.0.0
	 * @return   bool    Success status.
	 */
	public function clear_log() {
		$log_path = $this->log_dir . $this->log_file;

		if ( file_exists( $log_path ) ) {
			return unlink( $log_path );
		}

		return true;
	}

	/**
	 * Rotate log file.
	 *
	 * @since    1.0.0
	 */
	public function rotate_log() {
		$log_path = $this->log_dir . $this->log_file;

		if ( ! file_exists( $log_path ) ) {
			return;
		}

		$file_size = filesize( $log_path );
		$max_size = 5 * MB_IN_BYTES; // 5MB

		if ( $file_size > $max_size ) {
			$backup_file = $this->log_dir . 'spam-slayer-5000-' . date( 'Y-m-d-His' ) . '.log';
			rename( $log_path, $backup_file );

			// Keep only last 5 backup files
			$this->cleanup_old_logs();
		}
	}

	/**
	 * Check if logging is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool    True if enabled.
	 */
	private function is_enabled() {
		return (bool) get_option( 'spam_slayer_5000_enable_logging', true );
	}

	/**
	 * Check if should log this level.
	 *
	 * @since    1.0.0
	 * @param    string    $level    Log level.
	 * @return   bool               True if should log.
	 */
	private function should_log( $level ) {
		$min_level = get_option( 'spam_slayer_5000_log_level', 'info' );

		if ( ! isset( $this->levels[ $level ] ) || ! isset( $this->levels[ $min_level ] ) ) {
			return false;
		}

		return $this->levels[ $level ] >= $this->levels[ $min_level ];
	}

	/**
	 * Ensure log directory exists.
	 *
	 * @since    1.0.0
	 */
	private function ensure_log_directory() {
		if ( ! file_exists( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
			file_put_contents( $this->log_dir . '.htaccess', 'deny from all' );
			file_put_contents( $this->log_dir . 'index.php', '<?php // Silence is golden' );
		}
	}

	/**
	 * Cleanup old log files.
	 *
	 * @since    1.0.0
	 */
	private function cleanup_old_logs() {
		$files = glob( $this->log_dir . 'spam-slayer-5000-*.log' );

		if ( count( $files ) > 5 ) {
			usort( $files, function( $a, $b ) {
				return filemtime( $a ) - filemtime( $b );
			} );

			$files_to_delete = array_slice( $files, 0, count( $files ) - 5 );

			foreach ( $files_to_delete as $file ) {
				unlink( $file );
			}
		}
	}
}
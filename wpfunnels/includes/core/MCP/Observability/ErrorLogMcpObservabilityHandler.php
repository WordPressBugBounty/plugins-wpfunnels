<?php
/**
 * ErrorLogMcpObservabilityHandler — Audit logging and observability sink for MCP tool calls.
 *
 * @package WPFunnels\MCP\Observability
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Observability;

defined( 'ABSPATH' ) || exit;

/**
 * Class ErrorLogMcpObservabilityHandler
 */
class ErrorLogMcpObservabilityHandler {

	/**
	 * Log an MCP tool execution event.
	 *
	 * @param string $tool        Name of executed tool.
	 * @param array  $input       Arguments passed to tool.
	 * @param mixed  $result      Tool execution result or error.
	 * @param float  $execution_ms Execution time in milliseconds.
	 * @return void
	 */
	public static function logToolCall( $tool, $input, $result, $execution_ms = 0.0 ) {
		$user_id     = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
		$is_error    = is_wp_error( $result );
		$destructive = ! empty( $input['confirm'] );

		$log_entry = [
			'timestamp'    => current_time( 'mysql' ),
			'tool'         => $tool,
			'user_id'      => $user_id,
			'destructive'  => $destructive,
			'success'      => ! $is_error,
			'execution_ms' => round( $execution_ms, 2 ),
			'error'        => $is_error ? $result->get_error_message() : null,
		];

		/**
		 * Filter or intercept MCP audit log entries.
		 *
		 * @param array $log_entry Structured log payload.
		 */
		do_action( 'wpfunnels/mcp_audit_log', $log_entry );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[WPFunnels MCP] %s | User: %d | Tool: %s | Time: %.2fms | Status: %s',
					$log_entry['timestamp'],
					$user_id,
					$tool,
					$log_entry['execution_ms'],
					$is_error ? 'ERROR (' . $log_entry['error'] . ')' : 'SUCCESS'
				)
			);
		}
	}
}

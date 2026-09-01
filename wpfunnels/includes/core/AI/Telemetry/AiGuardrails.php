<?php
/**
 * AiGuardrails — Rate limiting and spend guardrails for AI Copilot.
 *
 * @package WPFunnels\AI\Telemetry
 * @since 3.13.0
 */

namespace WPFunnels\AI\Telemetry;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Class AiGuardrails
 */
class AiGuardrails {

	/**
	 * Max requests per minute per user.
	 */
	const MAX_REQUESTS_PER_MINUTE = 30;

	/**
	 * Max turn budget per agent loop run.
	 */
	const MAX_TURN_BUDGET = 25;

	/**
	 * Check rate limit for current user.
	 *
	 * @param int $user_id User ID.
	 * @return true|\WP_Error
	 */
	public static function checkRateLimit( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
		}

		$transient_key = 'wpfnl_ai_rate_' . $user_id;
		$count         = (int) get_transient( $transient_key );

		if ( $count >= self::MAX_REQUESTS_PER_MINUTE ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Rate limit exceeded. Please wait a moment before sending more AI requests.', 'wpfnl' ),
				[ 'status' => 429 ]
			);
		}

		set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Record token usage for telemetry tracking.
	 *
	 * @param int    $input_tokens  Input tokens.
	 * @param int    $output_tokens Output tokens.
	 * @param string $provider      Provider slug.
	 * @return void
	 */
	public static function recordUsage( $input_tokens, $output_tokens, $provider = 'openai' ) {
		$total = (int) get_option( 'wpfnl_ai_total_tokens_used', 0 );
		update_option( 'wpfnl_ai_total_tokens_used', $total + $input_tokens + $output_tokens );

		do_action( 'wpfunnels_ai_usage_recorded', [
			'input_tokens'  => $input_tokens,
			'output_tokens' => $output_tokens,
			'provider'      => $provider,
		] );
	}
}

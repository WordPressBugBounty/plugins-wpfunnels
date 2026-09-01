<?php
/**
 * WordPressAIProvider — wraps the WordPress AI Services API.
 *
 * Delegates to whichever AI service the site owner configured via the WordPress
 * AI Services plugin (wordpress.org/plugins/ai-services) or a hosting-level
 * WordPress AI Client. No API key is stored in WPFunnels; the underlying
 * service handles credentials — which also makes this the natural shared
 * connection on sites running several plugins that need AI.
 *
 * Because AI Services does not guarantee tool-use support across all backends,
 * this provider operates in text-generation mode: responses never carry
 * tool_calls, so the copilot can answer from context but cannot execute write
 * operations. For the full copilot (build funnels, configure offers), connect a
 * direct key via Anthropic, OpenAI, or Gemini.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class WordPressAIProvider
 */
class WordPressAIProvider implements ProviderInterface {

	/**
	 * Model id, or 'auto' to let the service decide.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Ignored — the service owns credentials.
	 * @param string $model   Model id or 'auto'.
	 */
	public function __construct( $api_key = '', $model = '' ) {
		unset( $api_key );
		$this->model = '' !== (string) $model ? (string) $model : 'auto';
	}

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'wordpress_ai';
	}

	/**
	 * Check whether a WordPress AI service is available and configured.
	 *
	 * @return true|\WP_Error
	 */
	public function validateKey() {
		if ( ! self::isAvailable() ) {
			return new \WP_Error(
				'wp_ai_unavailable',
				__( 'WordPress AI is not available on this site. Install the AI Services plugin (wordpress.org/plugins/ai-services) or use hosting that provides the WordPress AI Client API.', 'wpfnl' )
			);
		}

		if ( empty( self::getAvailableServices() ) ) {
			return new \WP_Error(
				'wp_ai_no_service',
				__( 'WordPress AI is installed but no AI provider is configured under it. Add one under Settings → AI Services (or your hosting panel).', 'wpfnl' )
			);
		}

		return true;
	}

	/**
	 * Run one text-generation turn via the WordPress AI Services API.
	 *
	 * Tool use is not attempted — tool_calls is always empty.
	 *
	 * @param string $system         System prompt text.
	 * @param array  $messages       Normalized message list.
	 * @param array  $tools          Tool definitions (accepted but not forwarded).
	 * @param bool   $force_tool_use Ignored — this provider never emits tool_calls.
	 * @return array|\WP_Error
	 */
	public function chat( $system, $messages, $tools, $force_tool_use = false ) {
		unset( $tools, $force_tool_use );

		if ( ! self::isAvailable() ) {
			return new \WP_Error( 'wp_ai_unavailable', __( 'WordPress AI is not available.', 'wpfnl' ) );
		}

		try {
			return $this->doChat( $system, $messages );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wp_ai_error', $e->getMessage() );
		}
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Perform the generation call.
	 *
	 * @param string $system   System prompt text.
	 * @param array  $messages Normalized message list.
	 * @return array|\WP_Error
	 * @throws \Throwable When the service throws.
	 */
	private function doChat( $system, $messages ) {
		$fn  = self::servicesFunctionName();
		$api = $fn();

		$service = method_exists( $api, 'get_available_service' )
			? $api->get_available_service( [ 'feature' => 'text-generation' ] )
			: null;

		if ( null === $service || is_wp_error( $service ) ) {
			return new \WP_Error( 'wp_ai_no_service', __( 'No WordPress AI service is available.', 'wpfnl' ) );
		}

		$model_args = [ 'feature' => 'text-generation' ];
		if ( 'auto' !== $this->model && '' !== $this->model ) {
			$model_args['model'] = $this->model;
		}

		$model_obj = method_exists( $service, 'get_model_object' )
			? $service->get_model_object( $model_args )
			: null;

		if ( null === $model_obj || is_wp_error( $model_obj ) ) {
			return new \WP_Error( 'wp_ai_model_error', __( 'Could not load a WordPress AI model.', 'wpfnl' ) );
		}

		// Flatten messages into a prompt string with role prefixes.
		$history_lines = [];
		if ( '' !== (string) $system ) {
			$history_lines[] = 'System: ' . $system;
		}
		foreach ( $messages as $message ) {
			$role    = isset( $message['role'] ) ? $message['role'] : 'user';
			$content = isset( $message['content'] ) ? $message['content'] : [];

			if ( is_string( $content ) ) {
				$decoded = json_decode( $content, true );
				$content = is_array( $decoded ) ? $decoded : [ 'text' => $content ];
			}

			if ( is_array( $content ) ) {
				$text = isset( $content['text'] ) ? $content['text'] : wp_json_encode( $content );
			} else {
				$text = (string) $content;
			}

			$history_lines[] = ( 'assistant' === $role ? 'Assistant: ' : 'User: ' ) . $text;
		}

		$prompt     = implode( "\n\n", $history_lines );
		$candidates = method_exists( $model_obj, 'generate_text' )
			? $model_obj->generate_text( $prompt, [] )
			: null;

		if ( null === $candidates || is_wp_error( $candidates ) ) {
			$message = is_wp_error( $candidates )
				? $candidates->get_error_message()
				: __( 'WordPress AI returned no response.', 'wpfnl' );
			return new \WP_Error( 'wp_ai_empty_response', $message );
		}

		return [
			'text'        => self::extractText( $candidates ),
			'tool_calls'  => [],
			'stop_reason' => 'end_turn',
			'raw'         => null,
			'usage'       => [
				'input_tokens'  => 0,
				'output_tokens' => 0,
			],
		];
	}

	/**
	 * Pull text out of whatever shape the installed AI Services version returns.
	 *
	 * @param mixed $candidates Generation result.
	 * @return string
	 */
	private static function extractText( $candidates ) {
		if ( function_exists( 'ai_candidates_text' ) ) {
			return (string) ai_candidates_text( $candidates );
		}
		if ( is_string( $candidates ) ) {
			return $candidates;
		}

		$text = '';
		if ( is_object( $candidates ) && method_exists( $candidates, 'get_candidates' ) ) {
			$list  = $candidates->get_candidates();
			$first = isset( $list[0] ) ? $list[0] : null;
			if ( $first && method_exists( $first, 'get_content' ) ) {
				foreach ( $first->get_content()->get_parts() as $part ) {
					if ( method_exists( $part, 'get_text' ) ) {
						$text .= $part->get_text();
					}
				}
			}
		}

		return $text;
	}

	// -------------------------------------------------------------------------
	// Static availability checks
	// -------------------------------------------------------------------------

	/**
	 * Whether a WordPress AI Services API is present.
	 *
	 * @return bool
	 */
	public static function isAvailable() {
		return '' !== self::servicesFunctionName();
	}

	/**
	 * Services configured under WordPress AI.
	 *
	 * @return array
	 */
	public static function getAvailableServices() {
		if ( ! self::isAvailable() ) {
			return [];
		}
		try {
			$fn  = self::servicesFunctionName();
			$api = $fn();
			if ( method_exists( $api, 'get_available_services' ) ) {
				$result = $api->get_available_services();
				return is_array( $result ) ? $result : [];
			}
		} catch ( \Throwable $e ) {
			// Swallow — treated as unavailable.
			return [];
		}
		return [];
	}

	/**
	 * Name of the global accessor exposed by the installed AI Services version.
	 *
	 * @return string
	 */
	private static function servicesFunctionName() {
		if ( function_exists( 'ai_services' ) ) {
			return 'ai_services';
		}
		if ( function_exists( 'wp_ai_services' ) ) {
			return 'wp_ai_services';
		}
		return '';
	}
}

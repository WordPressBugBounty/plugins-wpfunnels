<?php
/**
 * AbstractProvider — shared HTTP plumbing for AI provider adapters.
 *
 * Includes a per-provider circuit breaker: after repeated failures the adapter
 * refuses to call out for a cool-off window instead of burning a request (and
 * the user's patience) on every loop step. The breaker never sleeps — a
 * blocking retry inside a synchronous REST request is worse than failing fast.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractProvider
 */
abstract class AbstractProvider implements ProviderInterface {

	/**
	 * Per-step output ceiling. Deliberately below the SDK-default 16K: each
	 * agent-loop step runs inside one synchronous REST request, and shared
	 * hosting PHP timeouts are the binding constraint, not output length.
	 */
	protected const MAX_TOKENS = 8192;

	/**
	 * HTTP timeout for a chat call, in seconds.
	 */
	protected const HTTP_TIMEOUT = 90;

	/**
	 * Consecutive failures that trip the breaker.
	 */
	protected const BREAKER_THRESHOLD = 5;

	/**
	 * How long the breaker stays open, in seconds.
	 */
	protected const BREAKER_COOLOFF = 300;

	/**
	 * API key for the provider ('' for wordpress_ai).
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Model id.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Plaintext API key.
	 * @param string $model   Model id.
	 */
	public function __construct( $api_key, $model ) {
		$this->api_key = (string) $api_key;
		$this->model   = (string) $model;
	}

	/**
	 * Whether the breaker is currently open for this provider.
	 *
	 * @return bool
	 */
	public function isCircuitOpen() {
		$state = $this->breakerState();
		if ( empty( $state['open_until'] ) ) {
			return false;
		}
		if ( time() >= (int) $state['open_until'] ) {
			$this->resetCircuit();
			return false;
		}
		return true;
	}

	/**
	 * Seconds until the breaker closes again.
	 *
	 * @return int
	 */
	public function circuitCooloffRemaining() {
		$state = $this->breakerState();
		return empty( $state['open_until'] ) ? 0 : max( 0, (int) $state['open_until'] - time() );
	}

	/**
	 * POST JSON and decode the response, mapping HTTP failures to WP_Error.
	 *
	 * @param string $url     Endpoint.
	 * @param array  $headers Extra headers.
	 * @param array  $body    Request body.
	 * @return array|\WP_Error Decoded JSON body.
	 */
	protected function postJson( $url, $headers, $body ) {
		if ( $this->isCircuitOpen() ) {
			return new \WP_Error(
				'ai_circuit_open',
				sprintf(
					/* translators: %1$s: provider slug, %2$d: seconds remaining. */
					__( '%1$s failed repeatedly and is paused. Try again in %2$d seconds.', 'wpfnl' ),
					$this->slug(),
					$this->circuitCooloffRemaining()
				)
			);
		}

		$response = wp_remote_post(
			$url,
			[
				'timeout' => self::HTTP_TIMEOUT,
				'headers' => array_merge( [ 'Content-Type' => 'application/json' ], $headers ),
				'body'    => wp_json_encode( $body ),
			]
		);

		return $this->decodeResponse( $response );
	}

	/**
	 * GET and decode, same error mapping.
	 *
	 * @param string $url     Endpoint.
	 * @param array  $headers Extra headers.
	 * @return array|\WP_Error
	 */
	protected function getJson( $url, $headers ) {
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 30,
				'headers' => $headers,
			]
		);
		return $this->decodeResponse( $response );
	}

	/**
	 * Decode a wp_remote_* result, recording breaker state on the way through.
	 *
	 * @param array|\WP_Error $response wp_remote_* result.
	 * @return array|\WP_Error
	 */
	private function decodeResponse( $response ) {
		if ( is_wp_error( $response ) ) {
			$this->recordFailure();
			return new \WP_Error(
				'ai_network_error',
				sprintf(
					/* translators: %1$s: provider slug, %2$s: transport error. */
					__( 'Could not reach %1$s: %2$s', 'wpfnl' ),
					$this->slug(),
					$response->get_error_message()
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			$this->resetCircuit();
			return is_array( $body ) ? $body : [];
		}

		$detail = '';
		if ( is_array( $body ) ) {
			if ( isset( $body['error']['message'] ) ) {
				$detail = $body['error']['message'];
			} elseif ( isset( $body['message'] ) ) {
				$detail = $body['message'];
			} elseif ( isset( $body['error'] ) ) {
				$detail = $body['error'];
			}
			$detail = is_string( $detail ) ? $detail : wp_json_encode( $detail );
		}

		$map = [
			401 => [ 'ai_auth_error', __( 'The API key was rejected. Reconnect with a valid key.', 'wpfnl' ) ],
			403 => [ 'ai_auth_error', __( 'The API key lacks permission for this request.', 'wpfnl' ) ],
			404 => [ 'ai_model_error', __( 'The configured model was not found.', 'wpfnl' ) ],
			429 => [ 'ai_rate_limited', __( 'The provider rate-limited the request. Try again shortly.', 'wpfnl' ) ],
			529 => [ 'ai_overloaded', __( 'The provider is temporarily overloaded. Try again shortly.', 'wpfnl' ) ],
		];

		if ( isset( $map[ $code ] ) ) {
			$err_code    = $map[ $code ][0];
			$err_message = $map[ $code ][1];
		} else {
			$err_code    = 'ai_provider_error';
			/* translators: %d: HTTP status code. */
			$err_message = sprintf( __( 'Provider returned HTTP %d.', 'wpfnl' ), $code );
		}

		// A rejected key or missing model will not fix itself — count those, and
		// server-side failures, toward the breaker. Rate limits are transient and
		// already carry their own retry signal.
		if ( 429 !== $code ) {
			$this->recordFailure();
		}

		$error_data = [ 'status' => $code ];
		if ( 429 === $code ) {
			// The provider tells us exactly how long to wait — surface it so
			// AgentLoop can have the client retry automatically instead of
			// dying with a red error banner over a few-second throttle.
			// Prefer the standard Retry-After header; OpenAI's error message
			// also states it in prose ("Please try again in 6.636s") as a
			// fallback for providers/errors that omit the header.
			$header_retry = wp_remote_retrieve_header( $response, 'retry-after' );
			if ( '' !== $header_retry && is_numeric( $header_retry ) ) {
				$error_data['retry_after'] = (float) $header_retry;
			} elseif ( preg_match( '/try again in\s*([\d.]+)\s*s/i', $detail, $matches ) ) {
				$error_data['retry_after'] = (float) $matches[1];
			}
		}

		return new \WP_Error(
			$err_code,
			trim( $err_message . ( $detail ? ' (' . $detail . ')' : '' ) ),
			$error_data
		);
	}

	/**
	 * Decode a message row's JSON content into an array.
	 *
	 * @param array $message Message row.
	 * @return array
	 */
	protected static function content( $message ) {
		$content = isset( $message['content'] ) ? $message['content'] : [];
		if ( is_string( $content ) ) {
			$content = json_decode( $content, true );
		}
		return is_array( $content ) ? $content : [];
	}

	/**
	 * Provider-native raw payload stored on an assistant message, if it was
	 * produced by THIS provider (raw blocks are not portable across providers).
	 *
	 * @param array $message Message row.
	 * @return mixed|null
	 */
	protected function rawFor( $message ) {
		$meta = isset( $message['meta'] ) ? $message['meta'] : [];
		if ( is_string( $meta ) ) {
			$meta = json_decode( $meta, true );
		}
		if ( is_array( $meta ) && isset( $meta['provider'], $meta['raw'] ) && $meta['provider'] === $this->slug() ) {
			return $meta['raw'];
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// Circuit breaker
	// -------------------------------------------------------------------------

	/**
	 * Option name holding this provider's breaker state.
	 *
	 * @return string
	 */
	private function breakerOption() {
		return '_wpfnl_ai_circuit_' . $this->slug();
	}

	/**
	 * Current breaker state.
	 *
	 * @return array
	 */
	private function breakerState() {
		$state = get_option( $this->breakerOption(), [] );
		return is_array( $state ) ? $state : [];
	}

	/**
	 * Record a failure, tripping the breaker once the threshold is reached.
	 *
	 * @return void
	 */
	private function recordFailure() {
		$state    = $this->breakerState();
		$failures = isset( $state['failures'] ) ? (int) $state['failures'] + 1 : 1;

		$state['failures']     = $failures;
		$state['last_failure'] = time();
		if ( $failures >= self::BREAKER_THRESHOLD ) {
			$state['open_until'] = time() + self::BREAKER_COOLOFF;
		}

		update_option( $this->breakerOption(), $state, false );
	}

	/**
	 * Clear the breaker after a success or once the cool-off has elapsed.
	 *
	 * @return void
	 */
	private function resetCircuit() {
		if ( [] === $this->breakerState() ) {
			return;
		}
		delete_option( $this->breakerOption() );
	}
}

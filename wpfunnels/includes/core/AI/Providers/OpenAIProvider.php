<?php
/**
 * OpenAIProvider — GPT via the OpenAI Chat Completions API.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class OpenAIProvider
 */
class OpenAIProvider extends AbstractProvider {

	/**
	 * API base.
	 */
	private const API_BASE = 'https://api.openai.com/v1';

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'openai';
	}

	/**
	 * Run one model turn.
	 *
	 * @param string $system         System prompt.
	 * @param array  $messages       Normalized message list.
	 * @param array  $tools          Normalized tool definitions.
	 * @param bool   $force_tool_use Force a tool call instead of a text-only reply.
	 * @return array|\WP_Error
	 */
	public function chat( $system, $messages, $tools, $force_tool_use = false ) {
		$body = [
			'model'                 => $this->model,
			'max_completion_tokens' => self::MAX_TOKENS,
			'messages'              => array_merge(
				[
					[
						'role'    => 'system',
						'content' => $system,
					],
				],
				$this->buildMessages( $messages )
			),
		];

		if ( ! empty( $tools ) ) {
			$body['tools']       = array_map(
				static function ( $tool ) {
					return [
						'type'     => 'function',
						'function' => [
							'name'        => self::wireName( $tool['name'] ),
							'description' => $tool['description'],
							'parameters'  => $tool['input_schema'],
						],
					];
				},
				$tools
			);
			// 'required' on the turn's first call — model must act, not narrate
			// ("I'll do X now...") with no tool_calls. See ProviderInterface::chat().
			$body['tool_choice'] = $force_tool_use ? 'required' : 'auto';
		}

		$response = $this->postJson(
			self::API_BASE . '/chat/completions',
			[ 'Authorization' => 'Bearer ' . $this->api_key ],
			$body
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$choice  = isset( $response['choices'][0] ) ? $response['choices'][0] : [];
		$message = isset( $choice['message'] ) ? $choice['message'] : [];

		$tool_calls = [];
		$calls      = isset( $message['tool_calls'] ) ? (array) $message['tool_calls'] : [];
		foreach ( $calls as $call ) {
			$raw_arguments = isset( $call['function']['arguments'] ) ? (string) $call['function']['arguments'] : '{}';
			$arguments     = json_decode( $raw_arguments, true );

			$tool_calls[] = [
				'id'        => isset( $call['id'] ) ? $call['id'] : uniqid( 'call_' ),
				'name'      => self::abilityName( isset( $call['function']['name'] ) ? (string) $call['function']['name'] : '' ),
				'arguments' => is_array( $arguments ) ? $arguments : [],
			];
		}

		$finish   = isset( $choice['finish_reason'] ) ? $choice['finish_reason'] : 'stop';
		$stop_map = [
			'tool_calls' => 'tool_use',
			'length'     => 'max_tokens',
		];

		return [
			'text'        => isset( $message['content'] ) ? (string) $message['content'] : '',
			'tool_calls'  => $tool_calls,
			'stop_reason' => isset( $stop_map[ $finish ] ) ? $stop_map[ $finish ] : 'end_turn',
			'raw'         => $message,
			'usage'       => [
				'input_tokens'  => isset( $response['usage']['prompt_tokens'] ) ? (int) $response['usage']['prompt_tokens'] : 0,
				'output_tokens' => isset( $response['usage']['completion_tokens'] ) ? (int) $response['usage']['completion_tokens'] : 0,
			],
		];
	}

	/**
	 * Cheap live credential check.
	 *
	 * @return true|\WP_Error
	 */
	public function validateKey() {
		$result = $this->getJson(
			self::API_BASE . '/models',
			[ 'Authorization' => 'Bearer ' . $this->api_key ]
		);
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Generate an image via the Images API, base64-encoded so the caller can
	 * write it straight to disk with no second (URL-fetch) network hop.
	 *
	 * @param string $prompt Image description.
	 * @param string $size   One of '1024x1024', '1792x1024', '1024x1792'.
	 * @return array|\WP_Error Response with a 'data' array of {b64_json}.
	 */
	public function generateImage( $prompt, $size = '1024x1024' ) {
		return $this->postJson(
			self::API_BASE . '/images/generations',
			[ 'Authorization' => 'Bearer ' . $this->api_key ],
			[
				'model'           => 'dall-e-3',
				'prompt'          => $prompt,
				'n'               => 1,
				'size'            => $size,
				'response_format' => 'b64_json',
			]
		);
	}

	// -------------------------------------------------------------------------
	// History conversion
	// -------------------------------------------------------------------------

	/**
	 * Convert normalized history into Chat Completions messages.
	 *
	 * @param array $messages Normalized message list.
	 * @return array
	 */
	private function buildMessages( $messages ) {
		$out = [];

		foreach ( $messages as $message ) {
			$role    = isset( $message['role'] ) ? $message['role'] : '';
			$content = self::content( $message );

			if ( 'user' === $role ) {
				$out[] = [
					'role'    => 'user',
					'content' => isset( $content['text'] ) ? (string) $content['text'] : '',
				];
				continue;
			}

			if ( 'assistant' === $role ) {
				$raw = $this->rawFor( $message );
				if ( is_array( $raw ) && ! empty( $raw ) ) {
					$out[] = array_merge( [ 'role' => 'assistant' ], $raw );
					continue;
				}

				$assistant = [
					'role'    => 'assistant',
					'content' => isset( $content['text'] ) ? (string) $content['text'] : '',
				];

				$calls  = isset( $content['tool_calls'] ) ? (array) $content['tool_calls'] : [];
				$mapped = [];
				foreach ( $calls as $call ) {
					$arguments = isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : [];
					$mapped[]  = [
						'id'       => isset( $call['id'] ) ? $call['id'] : uniqid( 'call_' ),
						'type'     => 'function',
						'function' => [
							'name'      => self::wireName( isset( $call['name'] ) ? $call['name'] : '' ),
							'arguments' => wp_json_encode( $arguments ),
						],
					];
				}
				if ( ! empty( $mapped ) ) {
					$assistant['tool_calls'] = $mapped;
				}

				$out[] = $assistant;
				continue;
			}

			if ( 'tool' === $role ) {
				$out[] = [
					'role'         => 'tool',
					'tool_call_id' => isset( $content['tool_call_id'] ) ? (string) $content['tool_call_id'] : '',
					'content'      => isset( $content['content'] ) ? (string) $content['content'] : '',
				];
			}
		}

		return $out;
	}

	/**
	 * OpenAI function names must match ^[a-zA-Z0-9_-]+$ — slashes in ability
	 * names ('wpfunnels/list-funnels') are not allowed.
	 *
	 * @param string $ability_name Ability name.
	 * @return string
	 */
	private static function wireName( $ability_name ) {
		return str_replace( '/', '__', (string) $ability_name );
	}

	/**
	 * Reverse of wireName().
	 *
	 * @param string $wire_name Wire name.
	 * @return string
	 */
	private static function abilityName( $wire_name ) {
		return str_replace( '__', '/', (string) $wire_name );
	}
}

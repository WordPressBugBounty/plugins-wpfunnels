<?php
/**
 * AnthropicProvider — Claude via the Anthropic Messages API.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class AnthropicProvider
 */
class AnthropicProvider extends AbstractProvider {

	/**
	 * Messages endpoint.
	 */
	private const API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Pinned API version.
	 */
	private const API_VERSION = '2023-06-01';

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'anthropic';
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
			'model'      => $this->model,
			'max_tokens' => self::MAX_TOKENS,
			// Stable system prompt first with a cache breakpoint — history grows
			// after it, so repeated loop steps read the prefix from cache.
			'system'     => [
				[
					'type'          => 'text',
					'text'          => $system,
					'cache_control' => [ 'type' => 'ephemeral' ],
				],
			],
			'messages'   => $this->buildMessages( $messages ),
		];

		// Extended thinking only supports tool_choice 'auto' (or none) — the API
		// rejects a forced choice alongside it. On the turn's first call we need
		// the forced call (model must act, not narrate with no tool_calls) more
		// than the extra reasoning, so thinking is skipped for that one request.
		if ( $force_tool_use && ! empty( $tools ) ) {
			$body['tool_choice'] = [ 'type' => 'any' ];
		} else {
			$body['thinking'] = [ 'type' => 'adaptive' ];
		}

		if ( ! empty( $tools ) ) {
			$body['tools'] = array_map(
				static function ( $tool ) {
					return [
						// Tool names must match ^[a-zA-Z0-9_-]{1,64}$ — no slashes.
						'name'         => self::wireName( $tool['name'] ),
						'description'  => $tool['description'],
						'input_schema' => $tool['input_schema'],
					];
				},
				$tools
			);
		}

		$response = $this->postJson(
			self::API_URL,
			[
				'x-api-key'         => $this->api_key,
				'anthropic-version' => self::API_VERSION,
			],
			$body
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$text       = '';
		$tool_calls = [];
		$blocks     = isset( $response['content'] ) ? (array) $response['content'] : [];

		foreach ( $blocks as $block ) {
			$type = isset( $block['type'] ) ? $block['type'] : '';

			if ( 'text' === $type ) {
				$text .= isset( $block['text'] ) ? $block['text'] : '';
			} elseif ( 'tool_use' === $type ) {
				$tool_calls[] = [
					'id'        => isset( $block['id'] ) ? $block['id'] : uniqid( 'toolu_' ),
					'name'      => self::abilityName( isset( $block['name'] ) ? (string) $block['name'] : '' ),
					'arguments' => isset( $block['input'] ) && is_array( $block['input'] ) ? $block['input'] : [],
				];
			}
		}

		$stop_map    = [
			'tool_use'   => 'tool_use',
			'max_tokens' => 'max_tokens',
		];
		$stop_reason = isset( $response['stop_reason'] ) ? $response['stop_reason'] : '';

		return [
			'text'        => $text,
			'tool_calls'  => $tool_calls,
			'stop_reason' => isset( $stop_map[ $stop_reason ] ) ? $stop_map[ $stop_reason ] : 'end_turn',
			// Full native content (incl. thinking blocks) — must be replayed
			// verbatim on the next request of a tool-use loop.
			'raw'         => isset( $response['content'] ) ? $response['content'] : [],
			'usage'       => [
				'input_tokens'  => isset( $response['usage']['input_tokens'] ) ? (int) $response['usage']['input_tokens'] : 0,
				'output_tokens' => isset( $response['usage']['output_tokens'] ) ? (int) $response['usage']['output_tokens'] : 0,
			],
		];
	}

	/**
	 * Cheap live credential check.
	 *
	 * @return true|\WP_Error
	 */
	public function validateKey() {
		$result = $this->postJson(
			self::API_URL,
			[
				'x-api-key'         => $this->api_key,
				'anthropic-version' => self::API_VERSION,
			],
			[
				'model'      => $this->model,
				'max_tokens' => 1,
				'messages'   => [
					[
						'role'    => 'user',
						'content' => 'ping',
					],
				],
			]
		);
		return is_wp_error( $result ) ? $result : true;
	}

	// -------------------------------------------------------------------------
	// History conversion
	// -------------------------------------------------------------------------

	/**
	 * Convert normalized history into Anthropic messages.
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
					$out[] = [
						'role'    => 'assistant',
						'content' => $raw,
					];
					continue;
				}

				// Reconstructed fallback (history produced by another provider).
				$blocks = [];
				if ( isset( $content['text'] ) && '' !== (string) $content['text'] ) {
					$blocks[] = [
						'type' => 'text',
						'text' => (string) $content['text'],
					];
				}
				$calls = isset( $content['tool_calls'] ) ? (array) $content['tool_calls'] : [];
				foreach ( $calls as $call ) {
					$blocks[] = [
						'type'  => 'tool_use',
						'id'    => isset( $call['id'] ) ? $call['id'] : uniqid( 'toolu_' ),
						'name'  => self::wireName( isset( $call['name'] ) ? $call['name'] : '' ),
						'input' => isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : new \stdClass(),
					];
				}
				if ( ! empty( $blocks ) ) {
					$out[] = [
						'role'    => 'assistant',
						'content' => $blocks,
					];
				}
				continue;
			}

			if ( 'tool' === $role ) {
				$result_block = [
					'type'        => 'tool_result',
					'tool_use_id' => isset( $content['tool_call_id'] ) ? (string) $content['tool_call_id'] : '',
					'content'     => isset( $content['content'] ) ? (string) $content['content'] : '',
				];
				if ( ! empty( $content['is_error'] ) ) {
					$result_block['is_error'] = true;
				}

				// Consecutive tool results merge into one user turn.
				$last = count( $out ) - 1;
				if ( $last >= 0
					&& 'user' === $out[ $last ]['role']
					&& is_array( $out[ $last ]['content'] )
					&& isset( $out[ $last ]['content'][0]['type'] )
					&& 'tool_result' === $out[ $last ]['content'][0]['type'] ) {
					$out[ $last ]['content'][] = $result_block;
				} else {
					$out[] = [
						'role'    => 'user',
						'content' => [ $result_block ],
					];
				}
			}
		}

		return $out;
	}

	/**
	 * Anthropic tool names must match ^[a-zA-Z0-9_-]{1,64}$ — slashes in ability
	 * names ('wpfunnels/list-funnels') are not allowed on the wire.
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

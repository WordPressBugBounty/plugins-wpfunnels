<?php
/**
 * GeminiProvider — Gemini via the Google Generative Language API.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class GeminiProvider
 */
class GeminiProvider extends AbstractProvider {

	/**
	 * API base.
	 */
	private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'gemini';
	}

	/**
	 * Run one model turn.
	 *
	 * @param string $system         System prompt.
	 * @param array  $messages       Normalized message list.
	 * @param array  $tools          Normalized tool definitions.
	 * @param bool   $force_tool_use Force a function call instead of a text-only reply.
	 * @return array|\WP_Error
	 */
	public function chat( $system, $messages, $tools, $force_tool_use = false ) {
		$body = [
			'systemInstruction' => [ 'parts' => [ [ 'text' => $system ] ] ],
			'contents'          => $this->buildContents( $messages ),
			'generationConfig'  => [ 'maxOutputTokens' => self::MAX_TOKENS ],
		];

		if ( ! empty( $tools ) ) {
			$body['tools'] = [
				[
					'functionDeclarations' => array_map(
						static function ( $tool ) {
							return [
								'name'        => self::wireName( $tool['name'] ),
								'description' => $tool['description'],
								'parameters'  => SchemaTranslator::forGemini( $tool['input_schema'] ),
							];
						},
						$tools
					),
				],
			];

			// 'ANY' on the turn's first call — model must act, not narrate
			// ("I'll do X now...") with no functionCall part.
			$body['toolConfig'] = [
				'functionCallingConfig' => [ 'mode' => $force_tool_use ? 'ANY' : 'AUTO' ],
			];
		}

		$response = $this->postJson(
			self::API_BASE . '/models/' . rawurlencode( $this->model ) . ':generateContent',
			[ 'x-goog-api-key' => $this->api_key ],
			$body
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$candidate = isset( $response['candidates'][0] ) ? $response['candidates'][0] : [];
		$parts     = isset( $candidate['content']['parts'] ) ? (array) $candidate['content']['parts'] : [];

		$text       = '';
		$tool_calls = [];
		foreach ( $parts as $part ) {
			if ( isset( $part['text'] ) ) {
				$text .= $part['text'];
			}
			if ( isset( $part['functionCall'] ) ) {
				// Gemini has no call ids — mint stable ones for the loop.
				$tool_calls[] = [
					'id'        => uniqid( 'gcall_' ),
					'name'      => self::abilityName( isset( $part['functionCall']['name'] ) ? (string) $part['functionCall']['name'] : '' ),
					'arguments' => isset( $part['functionCall']['args'] ) && is_array( $part['functionCall']['args'] ) ? $part['functionCall']['args'] : [],
				];
			}
		}

		$finish      = isset( $candidate['finishReason'] ) ? (string) $candidate['finishReason'] : 'STOP';
		$stop_reason = 'end_turn';
		if ( ! empty( $tool_calls ) ) {
			$stop_reason = 'tool_use';
		} elseif ( 'MAX_TOKENS' === $finish ) {
			$stop_reason = 'max_tokens';
		}

		return [
			'text'        => $text,
			'tool_calls'  => $tool_calls,
			'stop_reason' => $stop_reason,
			'raw'         => isset( $candidate['content'] ) ? $candidate['content'] : [],
			'usage'       => [
				'input_tokens'  => isset( $response['usageMetadata']['promptTokenCount'] ) ? (int) $response['usageMetadata']['promptTokenCount'] : 0,
				'output_tokens' => isset( $response['usageMetadata']['candidatesTokenCount'] ) ? (int) $response['usageMetadata']['candidatesTokenCount'] : 0,
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
			self::API_BASE . '/models?pageSize=1',
			[ 'x-goog-api-key' => $this->api_key ]
		);
		return is_wp_error( $result ) ? $result : true;
	}

	// -------------------------------------------------------------------------
	// History conversion
	// -------------------------------------------------------------------------

	/**
	 * Convert normalized history into Gemini contents.
	 *
	 * @param array $messages Normalized message list.
	 * @return array
	 */
	private function buildContents( $messages ) {
		$out = [];

		foreach ( $messages as $message ) {
			$role    = isset( $message['role'] ) ? $message['role'] : '';
			$content = self::content( $message );

			if ( 'user' === $role ) {
				$out[] = [
					'role'  => 'user',
					'parts' => [ [ 'text' => isset( $content['text'] ) ? (string) $content['text'] : '' ] ],
				];
				continue;
			}

			if ( 'assistant' === $role ) {
				$raw = $this->rawFor( $message );
				if ( is_array( $raw ) && ! empty( $raw['parts'] ) ) {
					$out[] = [
						'role'  => 'model',
						'parts' => $raw['parts'],
					];
					continue;
				}

				$parts = [];
				if ( isset( $content['text'] ) && '' !== (string) $content['text'] ) {
					$parts[] = [ 'text' => (string) $content['text'] ];
				}
				$calls = isset( $content['tool_calls'] ) ? (array) $content['tool_calls'] : [];
				foreach ( $calls as $call ) {
					$parts[] = [
						'functionCall' => [
							'name' => self::wireName( isset( $call['name'] ) ? $call['name'] : '' ),
							'args' => isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : new \stdClass(),
						],
					];
				}
				if ( ! empty( $parts ) ) {
					$out[] = [
						'role'  => 'model',
						'parts' => $parts,
					];
				}
				continue;
			}

			if ( 'tool' === $role ) {
				$raw_content = isset( $content['content'] ) ? (string) $content['content'] : '';
				$decoded     = json_decode( $raw_content, true );
				$part        = [
					'functionResponse' => [
						'name'     => self::wireName( isset( $content['name'] ) ? (string) $content['name'] : '' ),
						'response' => [ 'result' => null !== $decoded ? $decoded : $raw_content ],
					],
				];

				// Consecutive function responses merge into one user turn.
				$last = count( $out ) - 1;
				if ( $last >= 0 && 'user' === $out[ $last ]['role'] && isset( $out[ $last ]['parts'][0]['functionResponse'] ) ) {
					$out[ $last ]['parts'][] = $part;
				} else {
					$out[] = [
						'role'  => 'user',
						'parts' => [ $part ],
					];
				}
			}
		}

		return $out;
	}

	/**
	 * Gemini function names must match ^[a-zA-Z_][a-zA-Z0-9_.-]* — no slashes.
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

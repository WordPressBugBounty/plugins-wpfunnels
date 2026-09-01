<?php
/**
 * ProviderInterface — contract every AI provider adapter implements.
 *
 * The agent loop speaks one NORMALIZED dialect; adapters translate to and from
 * each provider's wire format.
 *
 * Normalized message (stored in wpfnl_ai_messages.content as JSON):
 *   role 'user'      : { text: string }
 *   role 'assistant' : { text: string, tool_calls: [ {id, name, arguments:object} ] }
 *   role 'tool'      : { tool_call_id: string, name: string, content: string, is_error: bool }
 *
 * Assistant messages may carry provider-native blocks in meta['raw'] (e.g.
 * Anthropic thinking blocks) which adapters replay verbatim when rebuilding
 * history for the SAME provider — required for tool-use loops with thinking.
 *
 * Normalized response from chat():
 *   {
 *     text:        string,
 *     tool_calls:  [ {id, name, arguments:object} ],
 *     stop_reason: 'end_turn'|'tool_use'|'max_tokens',
 *     raw:         mixed,   // provider-native assistant payload for history replay
 *     usage:       { input_tokens:int, output_tokens:int }
 *   }
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ProviderInterface
 */
interface ProviderInterface {

	/**
	 * Run one model turn.
	 *
	 * @param string $system         System prompt.
	 * @param array  $messages       Normalized message list (each: role, content, meta).
	 * @param array  $tools          Normalized tool defs: [ {name, description, input_schema} ].
	 * @param bool   $force_tool_use When true, the model must call a tool instead of
	 *                               replying with narration-only text. Set on the first
	 *                               model call of a turn so an autonomous build request
	 *                               can't end with a promise ("I'll do X now...") and no
	 *                               tool_calls — which AgentLoop would otherwise treat as
	 *                               a finished turn.
	 * @return array|\WP_Error Normalized response.
	 */
	public function chat( $system, $messages, $tools, $force_tool_use = false );

	/**
	 * Cheap live credential check.
	 *
	 * @return true|\WP_Error
	 */
	public function validateKey();

	/**
	 * Provider slug ('anthropic' | 'openai' | 'gemini' | 'wordpress_ai').
	 *
	 * @return string
	 */
	public function slug();
}

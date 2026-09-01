<?php
/**
 * AgentLoop — one frontend-driven iteration of the WPFunnels AI agent.
 *
 * Each step() call performs exactly ONE model request plus its tool batch,
 * then returns; the client immediately requests the next step while status is 'running'.
 * This bounds every HTTP request to a single model round-trip — avoiding PHP timeouts.
 *
 * States returned by step():
 *   running              — tools executed; client should call step() again
 *   pending_confirmation — a destructive tool awaits user approval (confirm())
 *   done                 — final assistant text produced
 *   rate_limited         — provider throttled this request; client should wait
 *                          `retry_after` seconds and call step() again (same
 *                          turn — nothing was persisted for this attempt)
 *   error                — provider or loop failure (message included)
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI;

defined( 'ABSPATH' ) || exit;

use WPFunnels\AI\Settings\AISettings;

/**
 * Class AgentLoop
 */
class AgentLoop {

	/**
	 * Maximum autonomous model iterations per turn.
	 */
	public const MAX_STEPS_PER_TURN = 25;

	/**
	 * Run one loop iteration for a conversation.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $user_id         Current user ID. Defaults to get_current_user_id().
	 * @return array
	 */
	public static function step( $conversation_id, $user_id = 0 ) {
		$user_id      = $user_id ? (int) $user_id : get_current_user_id();
		$conversation = ConversationStore::getOwnedConversation( $conversation_id, $user_id );

		if ( ! $conversation ) {
			return self::errorState( 'Conversation not found or access denied.' );
		}

		if ( 'awaiting_confirmation' === $conversation['status'] ) {
			return [
				'status'  => 'pending_confirmation',
				'pending' => self::pendingForClient( $conversation['pending'] ),
			];
		}

		$messages = ConversationStore::getMessages( $conversation_id );
		if ( empty( $messages ) ) {
			return self::errorState( 'Conversation has no messages yet.' );
		}

		$steps_this_turn = ConversationStore::assistantStepsThisTurn( $messages );

		// Turn budget check.
		if ( $steps_this_turn >= self::MAX_STEPS_PER_TURN ) {
			$note = 'I hit the step limit for this request. Here is where things stand — tell me to continue if you want me to keep going.';
			ConversationStore::appendMessage( $conversation_id, 'assistant', [ 'text' => $note, 'tool_calls' => [] ] );
			ConversationStore::updateConversation( $conversation_id, [ 'status' => 'idle' ] );
			return [
				'status'  => 'done',
				'message' => $note,
			];
		}

		$provider = AIInit::activeProvider();
		if ( is_wp_error( $provider ) ) {
			return self::errorState( $provider->get_error_message(), $provider->get_error_code() );
		}

		$system = SystemPrompt::build( (string) $conversation['context_type'], (int) $conversation['context_id'] );
		$tools  = ToolGateway::toolDefinitions();

		// First model call of the turn must call a tool — otherwise the model can
		// answer with pure narration ("I'll do X now...", no tool_calls), which
		// this loop would then treat as a finished turn (see below) and nothing
		// ever gets built. Once real progress has been made this turn, later
		// steps go back to 'auto' so the model can produce its final text reply.
		$force_tool_use = 0 === $steps_this_turn && ! empty( $tools );

		$response = $provider->chat( $system, $messages, $tools, $force_tool_use );
		if ( is_wp_error( $response ) ) {
			if ( 'ai_rate_limited' === $response->get_error_code() ) {
				// Transient — nothing was persisted for this attempt (no message
				// appended, turn budget untouched), so the client can just wait
				// out the provider's own cooldown and call step() again for the
				// SAME turn, instead of surfacing a dead-end error banner over
				// what is normally a few-second throttle.
				$data        = $response->get_error_data();
				$retry_after = ! empty( $data['retry_after'] ) ? (float) $data['retry_after'] : 5.0;
				return [
					'status'      => 'rate_limited',
					'message'     => $response->get_error_message(),
					'retry_after' => $retry_after,
				];
			}
			return self::errorState( $response->get_error_message(), $response->get_error_code() );
		}

		// Persist the assistant response message.
		ConversationStore::appendMessage(
			$conversation_id,
			'assistant',
			[
				'text'       => $response['text'],
				'tool_calls' => $response['tool_calls'],
			],
			[
				'provider' => $provider->slug(),
				'raw'      => isset( $response['raw'] ) ? $response['raw'] : null,
				'usage'    => isset( $response['usage'] ) ? $response['usage'] : [],
			]
		);

		if ( empty( $response['tool_calls'] ) ) {
			ConversationStore::updateConversation( $conversation_id, [ 'status' => 'idle' ] );
			return [
				'status'  => 'done',
				'message' => $response['text'],
			];
		}

		$executed = [];
		$pending  = [];
		$progress = null;

		foreach ( $response['tool_calls'] as $call ) {
			$call_name = isset( $call['name'] ) ? (string) $call['name'] : '';
			$call_args = isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : [];
			$call_id   = isset( $call['id'] ) ? (string) $call['id'] : uniqid( 'call_' );

			if ( ToolGateway::requiresConfirmation( $call_name ) ) {
				$pending[] = [
					'id'        => $call_id,
					'name'      => $call_name,
					'arguments' => $call_args,
					'summary'   => ToolGateway::describeCall( $call_name, $call_args ),
					'details'   => ToolGateway::describeCallDetails( $call_name, $call_args ),
				];
				continue;
			}

			$result = ToolGateway::executeTool( $call_name, $call_args );

			ConversationStore::appendMessage(
				$conversation_id,
				'tool',
				[
					'tool_call_id' => $call_id,
					'name'         => $call_name,
					'content'      => $result['content'],
					'is_error'     => $result['is_error'],
					'preview'      => ToolGateway::previewFor( $call_name, $result ),
				]
			);

			$executed[] = [
				'tool'     => $call_name,
				'is_error' => $result['is_error'],
			];

			$tool_progress = ToolGateway::progressFor( $call_name );
			if ( $tool_progress ) {
				$progress = $tool_progress;
			}

			self::bindFunnelContext( $conversation_id, $call_name, $result );
		}

		if ( ! empty( $pending ) ) {
			ConversationStore::updateConversation(
				$conversation_id,
				[
					'status'  => 'awaiting_confirmation',
					'pending' => $pending,
				]
			);
			return [
				'status'         => 'pending_confirmation',
				'assistant_text' => $response['text'],
				'executed'       => $executed,
				'pending'        => self::pendingForClient( $pending ),
				'progress'       => $progress,
			];
		}

		ConversationStore::updateConversation( $conversation_id, [ 'status' => 'idle' ] );

		return [
			'status'         => 'running',
			'assistant_text' => $response['text'],
			'executed'       => $executed,
			'progress'       => $progress,
		];
	}

	/**
	 * Resolve a pending confirmation: execute or deny queued tool calls.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $user_id         User ID.
	 * @param bool   $approve         Whether approved.
	 * @param string $deny_reason     Optional reason for denial.
	 * @return array
	 */
	public static function confirm( $conversation_id, $user_id = 0, $approve = true, $deny_reason = '' ) {
		$user_id      = $user_id ? (int) $user_id : get_current_user_id();
		$conversation = ConversationStore::getOwnedConversation( $conversation_id, $user_id );

		if ( ! $conversation ) {
			return self::errorState( 'Conversation not found or access denied.' );
		}

		if ( 'awaiting_confirmation' !== $conversation['status'] || empty( $conversation['pending'] ) ) {
			return self::errorState( 'Nothing is awaiting confirmation.' );
		}

		$executed = [];
		foreach ( (array) $conversation['pending'] as $call ) {
			$call_name = isset( $call['name'] ) ? (string) $call['name'] : '';
			$call_args = isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : [];
			$call_id   = isset( $call['id'] ) ? (string) $call['id'] : uniqid( 'call_' );

			if ( $approve ) {
				$result = ToolGateway::executeTool(
					$call_name,
					array_merge( $call_args, [ 'confirm' => true ] )
				);
				self::bindFunnelContext( $conversation_id, $call_name, $result );
			} else {
				$result = [
					'content'  => wp_json_encode(
						[
							'error'   => 'denied_by_user',
							'message' => 'The user declined this action.' . ( $deny_reason ? ' Reason: ' . $deny_reason : '' ),
						]
					),
					'is_error' => true,
				];
			}

			ConversationStore::appendMessage(
				$conversation_id,
				'tool',
				[
					'tool_call_id' => $call_id,
					'name'         => $call_name,
					'content'      => $result['content'],
					'is_error'     => $result['is_error'],
					'preview'      => $approve ? ToolGateway::previewFor( $call_name, $result ) : null,
				]
			);

			$executed[] = [
				'tool'     => $call_name,
				'is_error' => $result['is_error'],
				'approved' => (bool) $approve,
			];
		}

		ConversationStore::updateConversation(
			$conversation_id,
			[
				'status'  => 'idle',
				'pending' => null,
			]
		);

		return [
			'status'   => 'running',
			'executed' => $executed,
		];
	}

	/**
	 * When create-funnel or step operations succeed, bind conversation to funnel.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $tool_name       Tool name.
	 * @param array  $result          Execution result.
	 * @return void
	 */
	private static function bindFunnelContext( $conversation_id, $tool_name, $result ) {
		if ( ! empty( $result['is_error'] ) ) {
			return;
		}

		if ( ! in_array( $tool_name, [ 'wpfunnels/create-funnel', 'wpfunnels/create-step', 'wpfunnels/reorder-steps', 'wpfunnels/import-funnel-template' ], true ) ) {
			return;
		}

		$decoded = json_decode( (string) ( isset( $result['content'] ) ? $result['content'] : '' ), true );
		if ( ! empty( $decoded['funnel_id'] ) ) {
			ConversationStore::updateConversation(
				$conversation_id,
				[
					'context_type' => 'funnel',
					'context_id'   => (int) $decoded['funnel_id'],
				]
			);
		}
	}

	/**
	 * Normalize pending calls for client responses.
	 *
	 * @param mixed $pending Pending array.
	 * @return array
	 */
	public static function pendingForClient( $pending ) {
		return array_map(
			static function ( $call ) {
				$name = isset( $call['name'] ) ? (string) $call['name'] : '';
				$args = isset( $call['arguments'] ) && is_array( $call['arguments'] ) ? $call['arguments'] : [];

				return [
					'tool'      => $name,
					'summary'   => isset( $call['summary'] ) ? (string) $call['summary'] : ToolGateway::describeCall( $name, $args ),
					'arguments' => $args,
					'details'   => isset( $call['details'] ) && is_array( $call['details'] )
						? $call['details']
						: ToolGateway::describeCallDetails( $name, $args ),
				];
			},
			is_array( $pending ) ? $pending : []
		);
	}

	/**
	 * Build error state payload.
	 *
	 * @param string $message Error message.
	 * @param string $code    Error code.
	 * @return array
	 */
	private static function errorState( $message, $code = 'ai_error' ) {
		return [
			'status'  => 'error',
			'code'    => $code,
			'message' => $message,
		];
	}
}

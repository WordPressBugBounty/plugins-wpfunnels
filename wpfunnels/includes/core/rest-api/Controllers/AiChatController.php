<?php
/**
 * AiChatController — REST API controller for the WPFunnels AI Copilot.
 *
 * Routes:
 *   POST   /wpfunnels/v1/ai/conversations               — Start conversation + optional first message
 *   GET    /wpfunnels/v1/ai/conversations               — List my conversations
 *   GET    /wpfunnels/v1/ai/conversations/{id}          — Conversation detail + history + pending card
 *   PATCH  /wpfunnels/v1/ai/conversations/{id}          — Rename conversation
 *   DELETE /wpfunnels/v1/ai/conversations/{id}          — Delete conversation
 *   POST   /wpfunnels/v1/ai/conversations/{id}/messages — Append user message
 *   POST   /wpfunnels/v1/ai/conversations/{id}/step     — Run ONE agent loop iteration
 *   POST   /wpfunnels/v1/ai/conversations/{id}/confirm  — Approve/deny pending action
 *   GET    /wpfunnels/v1/ai/funnel-preview/{id}         — Canvas/flow preview payload
 *   GET    /wpfunnels/v1/ai/step-preview/{id}           — Step outline preview payload
 *
 * @package WPFunnels\Rest\Controllers
 * @since 3.13.0
 */

namespace WPFunnels\Rest\Controllers;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\AI\AIInit;
use WPFunnels\AI\AgentLoop;
use WPFunnels\AI\ConversationStore;
use WPFunnels\AI\Settings\AISettings;
use WPFunnels\MCP\Tools\PageContentTools;

/**
 * Class AiChatController
 */
class AiChatController extends Wpfnl_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wpfunnels/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ai';

	/**
	 * Check permissions for AI endpoints.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'You must be logged in to access the AI assistant.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! current_user_can( 'wpf_manage_funnels' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'Sorry, you do not have permission to access the AI assistant.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Conversations collection: GET (list), POST (create)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conversations',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_conversations' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_conversation' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Single conversation: GET (detail), PATCH (rename), DELETE (remove)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conversations/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_conversation' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_conversation' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_conversation' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Messages append: POST /ai/conversations/{id}/messages
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conversations/(?P<id>\d+)/messages',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'append_message' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Agent loop step: POST /ai/conversations/{id}/step
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conversations/(?P<id>\d+)/step',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'run_step' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Action confirmation: POST /ai/conversations/{id}/confirm
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conversations/(?P<id>\d+)/confirm',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'confirm_action' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Canvas preview payload: GET /ai/funnel-preview/{id}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/funnel-preview/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_funnel_preview' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Step outline preview payload: GET /ai/step-preview/{id}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/step-preview/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_step_preview' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Connection settings for the copilot's Connect modal:
		// GET|POST /settings/ai
		register_rest_route(
			$this->namespace,
			'/settings/ai',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_ai_settings' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_ai_settings' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);
	}

	/**
	 * Current connection state. Never returns a decrypted key — only a masked
	 * tail, so the settings screen can show "connected" without handing the
	 * credential back to the browser.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_ai_settings( $request ) {
		$state = \WPFunnels\AI\Settings\AISettings::publicState();

		$settings = [
			'enabled'             => (bool) $state['enabled'],
			'provider'            => $state['active_provider'],
			'custom_instructions' => $state['custom_instructions'],
			'mcp_enabled'         => 'no' !== get_option( '_wpfnl_mcp_enabled', 'yes' ),
			'mcp_endpoint'        => \WPFunnels\MCP\MCPInit::endpointUrl(),
			'mcp_supported'       => \WPFunnels\MCP\MCPInit::abilitiesApiAvailable(),
			'providers'           => $state['providers'],
		];

		// Flatten per-provider state into the keys the Connect modal binds to.
		foreach ( $state['providers'] as $slug => $provider ) {
			$settings[ $slug . '_connected' ]  = (bool) $provider['connected'];
			$settings[ $slug . '_masked_key' ] = $provider['masked_key'];
			$settings[ $slug . '_model' ]      = $provider['model'];
			$settings[ $slug . '_key' ]        = '';
		}

		return rest_ensure_response(
			[
				'success'  => true,
				'settings' => $settings,
			]
		);
	}

	/**
	 * Persist connection settings sent by the Connect modal.
	 *
	 * A blank or masked key means "leave the stored credential alone".
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_ai_settings( $request ) {
		$settings_class = '\WPFunnels\AI\Settings\AISettings';
		$params         = (array) $request->get_json_params();
		if ( empty( $params ) ) {
			$params = (array) $request->get_params();
		}

		$errors   = [];
		$provider = isset( $params['provider'] ) ? sanitize_text_field( (string) $params['provider'] ) : '';

		// Single-purpose action flags (Mail Mint parity) — the dedicated AI
		// Assistant settings page sends one of these per call. Mutually
		// exclusive: a request does exactly one thing.
		if ( ! empty( $params['test_connection'] ) && '' !== $provider ) {
			return rest_ensure_response( $this->test_ai_connection( $provider ) );
		}

		if ( ! empty( $params['disconnect'] ) && '' !== $provider ) {
			call_user_func( [ $settings_class, 'disconnect' ], $provider );
		} elseif ( isset( $params['api_key'] ) && '' !== $provider ) {
			$key   = trim( (string) $params['api_key'] );
			$model = isset( $params['model'] ) ? sanitize_text_field( (string) $params['model'] ) : '';
			if ( '' !== $key && false === strpos( $key, '•' ) ) {
				$result = call_user_func( [ $settings_class, 'connect' ], $provider, sanitize_text_field( $key ), $model );
				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				}
			}
		} elseif ( ! empty( $params['set_active'] ) && '' !== $provider ) {
			if ( 'wordpress_ai' === $provider && ! call_user_func( [ $settings_class, 'isConnected' ], $provider ) ) {
				call_user_func( [ $settings_class, 'connect' ], $provider, '' );
			}
			if ( ! call_user_func( [ $settings_class, 'setActiveProvider' ], $provider ) ) {
				$errors[] = sprintf(
					/* translators: %s: provider slug. */
					__( 'Add an API key for %s before making it the active provider.', 'wpfnl' ),
					$provider
				);
			}
		} elseif ( isset( $params['update_model'] ) && '' !== $provider ) {
			call_user_func( [ $settings_class, 'updateModel' ], $provider, sanitize_text_field( (string) $params['update_model'] ) );
		}

		if ( array_key_exists( 'set_enabled', $params ) ) {
			$flag = $params['set_enabled'];
			call_user_func( [ $settings_class, 'setEnabled' ], is_string( $flag ) ? 'yes' === $flag : (bool) rest_sanitize_boolean( $flag ) );
		}

		if ( ! empty( $params['save_instructions'] ) && isset( $params['instructions'] ) ) {
			call_user_func( [ $settings_class, 'saveCustomInstructions' ], sanitize_textarea_field( (string) $params['instructions'] ) );
		}

		// Legacy combined-payload style — every field arrives in one call.
		// Still used by the copilot's in-thread Connect modal, so only run
		// this branch when none of the single-purpose flags above were sent.
		$is_legacy_payload = empty( $params['disconnect'] ) && ! isset( $params['api_key'] )
			&& empty( $params['set_active'] ) && ! isset( $params['update_model'] );

		if ( $is_legacy_payload ) {
			foreach ( [ 'anthropic', 'openai', 'gemini' ] as $legacy_provider ) {
				$key   = isset( $params[ $legacy_provider . '_key' ] ) ? trim( (string) $params[ $legacy_provider . '_key' ] ) : '';
				$model = isset( $params[ $legacy_provider . '_model' ] ) ? sanitize_text_field( (string) $params[ $legacy_provider . '_model' ] ) : '';

				if ( '' !== $key && false === strpos( $key, '•' ) ) {
					$result = call_user_func( [ $settings_class, 'connect' ], $legacy_provider, sanitize_text_field( $key ), $model );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result->get_error_message();
					}
				} elseif ( '' !== $model && call_user_func( [ $settings_class, 'isConnected' ], $legacy_provider ) ) {
					call_user_func( [ $settings_class, 'updateModel' ], $legacy_provider, $model );
				}
			}

			if ( '' !== $provider ) {
				if ( 'wordpress_ai' === $provider && ! call_user_func( [ $settings_class, 'isConnected' ], $provider ) ) {
					call_user_func( [ $settings_class, 'connect' ], $provider, '' );
				}
				if ( ! call_user_func( [ $settings_class, 'setActiveProvider' ], $provider ) ) {
					$errors[] = sprintf(
						/* translators: %s: provider slug. */
						__( 'Add an API key for %s before making it the active provider.', 'wpfnl' ),
						$provider
					);
				}
			}

			if ( isset( $params['custom_instructions'] ) ) {
				call_user_func( [ $settings_class, 'saveCustomInstructions' ], sanitize_textarea_field( (string) $params['custom_instructions'] ) );
			}

			if ( array_key_exists( 'enabled', $params ) ) {
				call_user_func( [ $settings_class, 'setEnabled' ], (bool) rest_sanitize_boolean( $params['enabled'] ) );
			}
		}

		if ( array_key_exists( 'mcp_enabled', $params ) ) {
			update_option( '_wpfnl_mcp_enabled', rest_sanitize_boolean( $params['mcp_enabled'] ) ? 'yes' : 'no' );
		}

		$response = $this->get_ai_settings( $request );
		$data     = $response->get_data();

		$data['success'] = empty( $errors );
		if ( ! empty( $errors ) ) {
			$data['message'] = implode( ' ', $errors );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Live connectivity test against a provider's stored connection.
	 *
	 * Makes one small real request through the same adapter the agent loop
	 * uses, so "it works here" actually means the loop can use it. Never
	 * persists anything.
	 *
	 * @param string $provider Provider slug.
	 * @return array{success: bool, message: string}
	 */
	private function test_ai_connection( $provider ) {
		$settings_class = '\WPFunnels\AI\Settings\AISettings';

		if ( 'wordpress_ai' === $provider && ! call_user_func( [ $settings_class, 'isConnected' ], $provider ) ) {
			call_user_func( [ $settings_class, 'connect' ], $provider, '' );
		}

		if ( ! call_user_func( [ $settings_class, 'isConnected' ], $provider ) ) {
			return [
				'success' => false,
				'message' => __( 'Save an API key before testing the connection.', 'wpfnl' ),
			];
		}

		$adapter = \WPFunnels\AI\AIInit::makeProvider(
			$provider,
			call_user_func( [ $settings_class, 'getApiKey' ], $provider ),
			call_user_func( [ $settings_class, 'getModel' ], $provider )
		);

		if ( ! $adapter ) {
			return [
				'success' => false,
				'message' => __( 'Unknown AI provider.', 'wpfnl' ),
			];
		}

		$result = $adapter->chat(
			'You are a connectivity test. Reply with the single word OK and nothing else.',
			[
				[
					'role'    => 'user',
					'content' => [ 'text' => 'Reply with OK.' ],
				],
			],
			[]
		);

		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'message' => $result->get_error_message(),
			];
		}

		return [
			'success' => true,
			'message' => __( 'Connection is working.', 'wpfnl' ),
		];
	}

	/**
	 * List conversations for the current user.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_conversations( $request ) {
		$user_id      = get_current_user_id();
		$context_type = (string) $request->get_param( 'context_type' );
		$context_id   = (int) $request->get_param( 'context_id' );
		$limit        = max( 1, min( 100, (int) ( $request->get_param( 'limit' ) ?: 50 ) ) );
		$offset       = max( 0, (int) $request->get_param( 'offset' ) );

		$items = ConversationStore::listConversations( $user_id, $context_type, $context_id, $limit, $offset );

		return rest_ensure_response(
			[
				'success'       => true,
				'conversations' => $items,
			]
		);
	}

	/**
	 * Create a conversation and optionally post its first message.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_conversation( $request ) {
		$user_id      = get_current_user_id();
		$message      = trim( (string) $request->get_param( 'message' ) );
		$context_type = sanitize_text_field( (string) ( $request->get_param( 'context_type' ) ?: 'dashboard' ) );
		$context_id   = (int) $request->get_param( 'context_id' );
		$title        = trim( (string) $request->get_param( 'title' ) );

		if ( '' === $title ) {
			$title = '' !== $message ? mb_substr( $message, 0, 40 ) : __( 'New Conversation', 'wpfnl' );
		}

		$provider_slug = AISettings::getActiveProvider();

		$id = ConversationStore::createConversation( $user_id, $provider_slug, $context_type, $context_id, $title );
		if ( ! $id ) {
			return new WP_Error( 'create_failed', __( 'Could not create conversation.', 'wpfnl' ), [ 'status' => 500 ] );
		}

		if ( '' !== $message ) {
			ConversationStore::appendMessage(
				$id,
				'user',
				[
					'text'       => $message,
					'tool_calls' => [],
				]
			);
		}

		return rest_ensure_response(
			[
				'success'         => true,
				'conversation_id' => $id,
				'title'           => $title,
				'context_type'    => $context_type,
				'context_id'      => $context_id,
				'status'          => 'idle',
			]
		);
	}

	/**
	 * Get full detail for a conversation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_conversation( $request ) {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$conversation = ConversationStore::getOwnedConversation( $id, $user_id );
		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found.', 'wpfnl' ), [ 'status' => 404 ] );
		}

		$raw_messages = ConversationStore::getMessages( $id );
		$messages     = [];

		foreach ( $raw_messages as $msg ) {
			$messages[] = [
				'id'         => (int) $msg['id'],
				'role'       => $msg['role'],
				'content'    => ! empty( $msg['content'] ) ? json_decode( $msg['content'], true ) : null,
				'meta'       => ! empty( $msg['meta'] ) ? json_decode( $msg['meta'], true ) : null,
				'created_at' => $msg['created_at'],
			];
		}

		return rest_ensure_response(
			[
				'success'      => true,
				'conversation' => $conversation,
				'messages'     => $messages,
				'pending'      => AgentLoop::pendingForClient( $conversation['pending'] ),
			]
		);
	}

	/**
	 * Rename a conversation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_conversation( $request ) {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );
		$title   = sanitize_text_field( (string) $request->get_param( 'title' ) );

		$conversation = ConversationStore::getOwnedConversation( $id, $user_id );
		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found.', 'wpfnl' ), [ 'status' => 404 ] );
		}

		if ( '' === $title ) {
			return new WP_Error( 'invalid_title', __( 'Title cannot be empty.', 'wpfnl' ), [ 'status' => 400 ] );
		}

		ConversationStore::updateConversation( $id, [ 'title' => $title ] );

		return rest_ensure_response(
			[
				'success' => true,
				'id'      => $id,
				'title'   => $title,
			]
		);
	}

	/**
	 * Delete a conversation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_conversation( $request ) {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$conversation = ConversationStore::getOwnedConversation( $id, $user_id );
		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found.', 'wpfnl' ), [ 'status' => 404 ] );
		}

		ConversationStore::deleteConversation( $id );

		return rest_ensure_response(
			[
				'success' => true,
				'id'      => $id,
			]
		);
	}

	/**
	 * Append a user message to a conversation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function append_message( $request ) {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );
		$text    = trim( (string) $request->get_param( 'content' ) );

		$conversation = ConversationStore::getOwnedConversation( $id, $user_id );
		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found.', 'wpfnl' ), [ 'status' => 404 ] );
		}

		if ( '' === $text ) {
			return new WP_Error( 'empty_message', __( 'Message content cannot be empty.', 'wpfnl' ), [ 'status' => 400 ] );
		}

		$msg_id = ConversationStore::appendMessage(
			$id,
			'user',
			[
				'text'       => $text,
				'tool_calls' => [],
			]
		);

		return rest_ensure_response(
			[
				'success'    => true,
				'message_id' => $msg_id,
			]
		);
	}

	/**
	 * Run one agent loop iteration.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_step( $request ) {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$res = AgentLoop::step( $id, $user_id );
		return rest_ensure_response( $res );
	}

	/**
	 * Approve or deny a pending confirmation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function confirm_action( $request ) {
		$user_id     = get_current_user_id();
		$id          = (int) $request->get_param( 'id' );
		$approve     = (bool) $request->get_param( 'approve' );
		$deny_reason = sanitize_text_field( (string) $request->get_param( 'deny_reason' ) );

		$res = AgentLoop::confirm( $id, $user_id, $approve, $deny_reason );
		return rest_ensure_response( $res );
	}

	/**
	 * Get canvas/flow preview payload for a funnel.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_funnel_preview( $request ) {
		$id     = (int) $request->get_param( 'id' );
		$post   = get_post( $id );

		if ( ! $post || WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'funnel_not_found', __( 'Funnel not found.', 'wpfnl' ), [ 'status' => 404 ] );
		}

		$preview = \WPFunnels\MCP\Helpers\MCPHelper::formatFunnel( $post, true );
		return rest_ensure_response(
			[
				'success' => true,
				'preview' => $preview,
			]
		);
	}

	/**
	 * Get a rendered step preview payload for the copilot's split-view canvas.
	 *
	 * Outline-based (headline / subheadline / CTA / benefits), not a screenshot —
	 * per the plan, screenshot previews are a later evaluation. Reuses
	 * PageContentTools::getStepOutline() so there is exactly one source of truth
	 * for "what an outline looks like" between the `get-step-outline` ability and
	 * this REST route.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_step_preview( $request ) {
		$id = (int) $request->get_param( 'id' );

		$outline = PageContentTools::getStepOutline( [ 'step_id' => $id ] );
		if ( is_wp_error( $outline ) ) {
			return $outline;
		}

		return rest_ensure_response(
			[
				'success' => true,
				'preview' => $outline,
			]
		);
	}
}

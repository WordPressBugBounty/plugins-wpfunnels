<?php
/**
 * ConversationStore — persistence for AI copilot conversations and messages.
 *
 * Every read/write path verifies ownership: conversations are strictly per-user.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConversationStore
 */
class ConversationStore {

	/**
	 * Conversations table suffix.
	 */
	public const CONVERSATIONS_TABLE = 'wpfnl_ai_conversations';

	/**
	 * Messages table suffix.
	 */
	public const MESSAGES_TABLE = 'wpfnl_ai_messages';

	/**
	 * Flag option set once the tables exist.
	 */
	private const READY_OPTION = '_wpfnl_ai_tables_ready';

	/**
	 * Idempotently create the AI tables if missing. Covers the window before the
	 * migration runs and self-heals multisite subsites.
	 *
	 * @return void
	 */
	public static function ensureTables() {
		if ( 'yes' === get_option( self::READY_OPTION ) ) {
			return;
		}

		global $wpdb;

		$conversations = $wpdb->prefix . self::CONVERSATIONS_TABLE;
		$exists        = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $conversations ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $exists !== $conversations ) {
			self::createTables();
		}

		update_option( self::READY_OPTION, 'yes', false );
	}

	/**
	 * Create both tables via dbDelta.
	 *
	 * @return void
	 */
	public static function createTables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->hide_errors();
		$charset       = $wpdb->get_charset_collate();
		$conversations = $wpdb->prefix . self::CONVERSATIONS_TABLE;
		$messages      = $wpdb->prefix . self::MESSAGES_TABLE;

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$conversations} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title varchar(255) NOT NULL DEFAULT '',
				provider varchar(50) NOT NULL DEFAULT '',
				context_type varchar(50) NOT NULL DEFAULT '',
				context_id bigint(20) unsigned DEFAULT NULL,
				status varchar(30) NOT NULL DEFAULT 'idle',
				pending longtext DEFAULT NULL,
				created_at datetime DEFAULT NULL,
				updated_at datetime DEFAULT NULL,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY context (context_type, context_id)
			) {$charset}"
		);

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$messages} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
				role varchar(20) NOT NULL DEFAULT 'user',
				content longtext DEFAULT NULL,
				meta longtext DEFAULT NULL,
				created_at datetime DEFAULT NULL,
				PRIMARY KEY (id),
				KEY conversation_id (conversation_id)
			) {$charset}"
		);
	}

	/**
	 * Drop both tables. Used by uninstall only.
	 *
	 * @return void
	 */
	public static function dropTables() {
		global $wpdb;

		$conversations = $wpdb->prefix . self::CONVERSATIONS_TABLE;
		$messages      = $wpdb->prefix . self::MESSAGES_TABLE;

		$wpdb->query( "DROP TABLE IF EXISTS {$messages}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$conversations}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		delete_option( self::READY_OPTION );
	}

	/**
	 * Start a conversation.
	 *
	 * @param int    $user_id      Owner.
	 * @param string $provider     Provider slug at creation time.
	 * @param string $context_type funnel|step|analytics|dashboard.
	 * @param int    $context_id   Related object id, 0 for none.
	 * @param string $title        Conversation title.
	 * @return int Conversation id.
	 */
	public static function createConversation( $user_id, $provider, $context_type, $context_id, $title ) {
		self::ensureTables();

		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::CONVERSATIONS_TABLE,
			[
				'user_id'      => (int) $user_id,
				'title'        => mb_substr( (string) $title, 0, 250 ),
				'provider'     => (string) $provider,
				'context_type' => (string) $context_type,
				'context_id'   => $context_id ? (int) $context_id : null,
				'status'       => 'idle',
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			]
		);

		$id = (int) $wpdb->insert_id;
		if ( $id > 0 ) {
			do_action( 'wpfunnels_ai_chat_started', $id, (int) $user_id, (string) $context_type, (int) $context_id );
		}

		return $id;
	}

	/**
	 * Fetch a conversation row with `pending` decoded.
	 *
	 * @param int $conversation_id Conversation id.
	 * @return array|null
	 */
	public static function getConversation( $conversation_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::CONVERSATIONS_TABLE;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $conversation_id ), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		$row['pending'] = ! empty( $row['pending'] ) ? json_decode( $row['pending'], true ) : null;
		return $row;
	}

	/**
	 * Fetch a conversation only when it belongs to the given user.
	 *
	 * @param int $conversation_id Conversation id.
	 * @param int $user_id         Expected owner.
	 * @return array|null
	 */
	public static function getOwnedConversation( $conversation_id, $user_id ) {
		$row = self::getConversation( $conversation_id );
		if ( ! $row || (int) $row['user_id'] !== (int) $user_id ) {
			return null;
		}
		return $row;
	}

	/**
	 * List a user's conversations, newest first.
	 *
	 * @param int    $user_id      Owner.
	 * @param string $context_type Optional context filter.
	 * @param int    $context_id   Optional context id filter.
	 * @param int    $limit        Page size.
	 * @param int    $offset       Offset.
	 * @return array
	 */
	public static function listConversations( $user_id, $context_type = '', $context_id = 0, $limit = 50, $offset = 0 ) {
		self::ensureTables();

		global $wpdb;

		$table = $wpdb->prefix . self::CONVERSATIONS_TABLE;
		$where = 'user_id = %d';
		$args  = [ (int) $user_id ];

		if ( '' !== $context_type ) {
			$where .= ' AND context_type = %s';
			$args[] = $context_type;
		}
		if ( (int) $context_id > 0 ) {
			$where .= ' AND context_id = %d';
			$args[] = (int) $context_id;
		}

		$args[] = max( 1, (int) $limit );
		$args[] = max( 0, (int) $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, provider, context_type, context_id, status, created_at, updated_at
				 FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
				$args
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Patch conversation fields. `pending` is JSON-encoded automatically.
	 *
	 * @param int   $conversation_id Conversation id.
	 * @param array $fields          Columns to update.
	 * @return void
	 */
	public static function updateConversation( $conversation_id, $fields ) {
		global $wpdb;

		if ( array_key_exists( 'pending', $fields ) && null !== $fields['pending'] ) {
			$fields['pending'] = wp_json_encode( $fields['pending'] );
		}
		$fields['updated_at'] = current_time( 'mysql' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::CONVERSATIONS_TABLE,
			$fields,
			[ 'id' => (int) $conversation_id ]
		);
	}

	/**
	 * Delete a conversation and its messages (no FK cascade defined).
	 *
	 * @param int $conversation_id Conversation id.
	 * @return void
	 */
	public static function deleteConversation( $conversation_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . self::MESSAGES_TABLE, [ 'conversation_id' => (int) $conversation_id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . self::CONVERSATIONS_TABLE, [ 'id' => (int) $conversation_id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Append a message.
	 *
	 * @param int    $conversation_id Conversation id.
	 * @param string $role            user|assistant|tool.
	 * @param array  $content         Normalized content.
	 * @param array  $meta            Provider, raw payload, usage.
	 * @return int Message id.
	 */
	public static function appendMessage( $conversation_id, $role, $content, $meta = [] ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::MESSAGES_TABLE,
			[
				'conversation_id' => (int) $conversation_id,
				'role'            => (string) $role,
				'content'         => wp_json_encode( $content ),
				'meta'            => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'created_at'      => current_time( 'mysql' ),
			]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * All messages of a conversation in order. Content/meta stay JSON strings;
	 * provider adapters decode lazily.
	 *
	 * @param int $conversation_id Conversation id.
	 * @return array
	 */
	public static function getMessages( $conversation_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::MESSAGES_TABLE;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, role, content, meta, created_at FROM {$table} WHERE conversation_id = %d ORDER BY id ASC",
				(int) $conversation_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Number of assistant messages since the most recent user message — the
	 * loop-iteration counter for the current turn.
	 *
	 * @param array $messages Message rows in order.
	 * @return int
	 */
	public static function assistantStepsThisTurn( $messages ) {
		$steps = 0;

		for ( $i = count( $messages ) - 1; $i >= 0; $i-- ) {
			if ( 'user' === $messages[ $i ]['role'] ) {
				break;
			}
			if ( 'assistant' === $messages[ $i ]['role'] ) {
				$steps++;
			}
		}

		return $steps;
	}
}

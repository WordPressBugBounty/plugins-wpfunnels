<?php
/**
 * DelegatedProvider — adapts a foreign provider object to our interface.
 *
 * Used for the Mail Mint bridge: when the site opts into Mail Mint's AI
 * connection, Mail Mint hands back its own adapter and we forward calls to it.
 * The API key never crosses the plugin boundary — only the call does.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Class DelegatedProvider
 */
class DelegatedProvider implements ProviderInterface {

	/**
	 * Foreign provider adapter. Must expose chat(), validateKey(), slug().
	 *
	 * @var object
	 */
	private $delegate;

	/**
	 * Where the delegate came from, for diagnostics.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Constructor.
	 *
	 * @param object $delegate Foreign provider adapter.
	 * @param string $source   Source label, e.g. 'mail-mint'.
	 */
	public function __construct( $delegate, $source = 'mail-mint' ) {
		$this->delegate = $delegate;
		$this->source   = (string) $source;
	}

	/**
	 * Whether an object can stand in as a provider.
	 *
	 * @param mixed $candidate Object to check.
	 * @return bool
	 */
	public static function isCompatible( $candidate ) {
		return is_object( $candidate )
			&& method_exists( $candidate, 'chat' )
			&& method_exists( $candidate, 'validateKey' )
			&& method_exists( $candidate, 'slug' );
	}

	/**
	 * Source label of the delegate.
	 *
	 * @return string
	 */
	public function source() {
		return $this->source;
	}

	/**
	 * Run one model turn through the delegate.
	 *
	 * @param string $system         System prompt.
	 * @param array  $messages       Normalized message list.
	 * @param array  $tools          Normalized tool definitions.
	 * @param bool   $force_tool_use Force a tool call instead of a text-only reply.
	 * @return array|\WP_Error
	 */
	public function chat( $system, $messages, $tools, $force_tool_use = false ) {
		try {
			return $this->delegate->chat( $system, $messages, $tools, $force_tool_use );
		} catch ( \Throwable $e ) {
			return new \WP_Error(
				'ai_delegate_error',
				sprintf(
					/* translators: %1$s: source plugin, %2$s: error message. */
					__( 'The AI connection borrowed from %1$s failed: %2$s', 'wpfnl' ),
					$this->source,
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Validate the delegate's credentials.
	 *
	 * @return true|\WP_Error
	 */
	public function validateKey() {
		try {
			return $this->delegate->validateKey();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ai_delegate_error', $e->getMessage() );
		}
	}

	/**
	 * Provider slug reported by the delegate.
	 *
	 * @return string
	 */
	public function slug() {
		try {
			return (string) $this->delegate->slug();
		} catch ( \Throwable $e ) {
			return 'delegated';
		}
	}
}

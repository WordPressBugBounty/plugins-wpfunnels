<?php
/**
 * AIInit — entry point and provider factory for the WPFunnels AI module.
 *
 * The AI surface is FREE: any provider works with the site owner's own key.
 * Resolution order for the active provider:
 *
 *   1. WPFunnels' own encrypted connection.
 *   2. Mail Mint's connection, when the admin opted in and Mail Mint exposes it.
 *      The call is delegated — the key itself never crosses the boundary.
 *   3. Nothing connected → WP_Error the UI can act on.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI;

defined( 'ABSPATH' ) || exit;

use WPFunnels\AI\Providers\AnthropicProvider;
use WPFunnels\AI\Providers\DelegatedProvider;
use WPFunnels\AI\Providers\GeminiProvider;
use WPFunnels\AI\Providers\OpenAIProvider;
use WPFunnels\AI\Providers\ProviderInterface;
use WPFunnels\AI\Providers\WordPressAIProvider;
use WPFunnels\AI\Settings\AISettings;

/**
 * Class AIInit
 */
class AIInit {

	/**
	 * Boot the module: migrate legacy keys and make sure tables exist.
	 *
	 * Runs on admin_init rather than plugins_loaded so the one-time work never
	 * touches a front-end request.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'maybeUpgrade' ] );
	}

	/**
	 * One-time upgrade steps, both idempotent and cheap after the first run.
	 *
	 * @return void
	 */
	public static function maybeUpgrade() {
		AISettings::migrateLegacyKeys();
		ConversationStore::ensureTables();
	}

	/**
	 * Build a provider adapter from explicit credentials — used by the
	 * validate-and-save flow before anything is stored.
	 *
	 * For wordpress_ai the API key is ignored.
	 *
	 * @param string $provider Provider slug.
	 * @param string $api_key  Plaintext API key.
	 * @param string $model    Model id.
	 * @return ProviderInterface|null
	 */
	public static function makeProvider( $provider, $api_key, $model = '' ) {
		if ( '' === (string) $model ) {
			$model = isset( AISettings::DEFAULT_MODELS[ $provider ] ) ? AISettings::DEFAULT_MODELS[ $provider ] : '';
		}

		switch ( $provider ) {
			case 'anthropic':
				return new AnthropicProvider( $api_key, $model );
			case 'openai':
				return new OpenAIProvider( $api_key, $model );
			case 'gemini':
				return new GeminiProvider( $api_key, $model );
			case 'wordpress_ai':
				return new WordPressAIProvider( '', $model );
		}

		return null;
	}

	/**
	 * Override provider for testing.
	 *
	 * @var ProviderInterface|null
	 */
	public static $override_provider = null;

	/**
	 * Set active provider override.
	 *
	 * @param ProviderInterface|null $provider Provider instance.
	 * @return void
	 */
	public static function setActiveProvider( $provider ) {
		self::$override_provider = $provider;
	}

	/**
	 * Build the adapter for the currently active connection.
	 *
	 * @return ProviderInterface|\WP_Error
	 */
	public static function activeProvider() {
		if ( null !== self::$override_provider ) {
			return self::$override_provider;
		}

		if ( ! AISettings::isEnabled() ) {
			return new \WP_Error(
				'ai_disabled',
				__( 'The AI Assistant is turned off. Enable it under WPFunnels → Settings → AI.', 'wpfnl' )
			);
		}

		$provider = AISettings::getActiveProvider();

		if ( '' !== $provider ) {
			$adapter = self::makeProvider( $provider, AISettings::getApiKey( $provider ), AISettings::getModel( $provider ) );
			if ( $adapter ) {
				return $adapter;
			}
			return new \WP_Error( 'ai_not_connected', __( 'Unknown AI provider configured.', 'wpfnl' ) );
		}

		$borrowed = self::mailMintProvider();
		if ( $borrowed ) {
			return $borrowed;
		}

		return new \WP_Error(
			'ai_not_connected',
			__( 'No AI provider is connected. Connect one under WPFunnels → Settings → AI.', 'wpfnl' )
		);
	}

	/**
	 * Borrow Mail Mint's AI connection when the admin opted in.
	 *
	 * Prefers the supported filter; falls back to Mail Mint's public factory so
	 * the bridge works on versions that predate the filter.
	 *
	 * @return DelegatedProvider|null
	 */
	public static function mailMintProvider() {
		if ( ! AISettings::usesMailMintConnection() ) {
			return null;
		}

		/**
		 * Filter: a provider adapter shared by Mail Mint.
		 *
		 * @since 3.13.0
		 * @param object|null $provider Adapter exposing chat(), validateKey(), slug().
		 */
		$shared = apply_filters( 'mail_mint/ai/shared_provider', null );

		if ( ! DelegatedProvider::isCompatible( $shared ) && class_exists( '\Mint\MRM\Internal\AI\AIInit' ) ) {
			$shared = \Mint\MRM\Internal\AI\AIInit::activeProvider();
			if ( is_wp_error( $shared ) ) {
				return null;
			}
		}

		return DelegatedProvider::isCompatible( $shared ) ? new DelegatedProvider( $shared, 'mail-mint' ) : null;
	}

	/**
	 * Whether a usable connection exists — own or borrowed.
	 *
	 * @return bool
	 */
	public static function isReady() {
		return ! is_wp_error( self::activeProvider() );
	}
}

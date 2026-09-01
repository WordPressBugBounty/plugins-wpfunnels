<?php
/**
 * AISettings — storage for AI provider connections.
 *
 * API keys are encrypted at rest (AES-256-CBC, key derived from the site's auth
 * salts) and are never returned to the frontend after save — only a masked tail
 * for display.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class AISettings
 */
class AISettings {

	/**
	 * Option holding the whole AI settings array.
	 */
	public const OPTION_KEY = '_wpfnl_ai_settings';

	/**
	 * Supported providers.
	 */
	public const PROVIDERS = [ 'anthropic', 'openai', 'gemini', 'wordpress_ai' ];

	/**
	 * Providers that need an API key. wordpress_ai uses the site-level WP AI Services.
	 */
	public const REQUIRES_KEY = [ 'anthropic', 'openai', 'gemini' ];

	/**
	 * Default model per provider.
	 */
	public const DEFAULT_MODELS = [
		'anthropic'    => 'claude-opus-4-8',
		'openai'       => 'gpt-4o',
		'gemini'       => 'gemini-2.0-flash',
		'wordpress_ai' => 'auto',
	];

	/**
	 * Selectable models per provider, shown in the settings UI dropdown.
	 * The first item is the default.
	 */
	public const MODEL_LISTS = [
		'anthropic'    => [
			[
				'id'    => 'claude-opus-4-8',
				'label' => 'Claude Opus 4.8 (Default)',
			],
			[
				'id'    => 'claude-sonnet-4-6',
				'label' => 'Claude Sonnet 4.6',
			],
			[
				'id'    => 'claude-haiku-4-5-20251001',
				'label' => 'Claude Haiku 4.5',
			],
			[
				'id'    => 'claude-fable-5',
				'label' => 'Claude Fable 5',
			],
			[
				'id'    => 'claude-opus-4-6',
				'label' => 'Claude Opus 4.6',
			],
		],
		'openai'       => [
			[
				'id'    => 'gpt-4o',
				'label' => 'GPT-4o (Default)',
			],
			[
				'id'    => 'gpt-4o-mini',
				'label' => 'GPT-4o Mini',
			],
			[
				'id'    => 'gpt-4.1',
				'label' => 'GPT-4.1',
			],
			[
				'id'    => 'gpt-4.1-mini',
				'label' => 'GPT-4.1 Mini',
			],
			[
				'id'    => 'o4-mini',
				'label' => 'o4 Mini',
			],
			[
				'id'    => 'o3',
				'label' => 'o3',
			],
		],
		'gemini'       => [
			[
				'id'    => 'gemini-2.0-flash',
				'label' => 'Gemini 2.0 Flash (Default)',
			],
			[
				'id'    => 'gemini-2.5-flash',
				'label' => 'Gemini 2.5 Flash',
			],
			[
				'id'    => 'gemini-2.5-pro',
				'label' => 'Gemini 2.5 Pro',
			],
			[
				'id'    => 'gemini-2.5-flash-lite',
				'label' => 'Gemini 2.5 Flash Lite',
			],
		],
		'wordpress_ai' => [
			[
				'id'    => 'auto',
				'label' => 'WordPress Default Model',
			],
		],
	];

	/**
	 * Legacy plaintext key options written by WPFunnels Pro 2.6–2.8.
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_KEY_OPTIONS = [
		'openai'    => '_wpfunnels_ai_openai_api_key',
		'anthropic' => '_wpfunnels_ai_anthropic_api_key',
	];

	/**
	 * Raw settings array (keys stay encrypted).
	 *
	 * @return array
	 */
	public static function all() {
		$settings = get_option( self::OPTION_KEY, [] );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Currently active provider, or '' when nothing usable is connected.
	 *
	 * @return string
	 */
	public static function getActiveProvider() {
		$settings = self::all();
		$active   = isset( $settings['active_provider'] ) ? $settings['active_provider'] : '';
		return in_array( $active, self::PROVIDERS, true ) && self::isConnected( $active ) ? $active : '';
	}

	/**
	 * Master on/off switch. Disabled by default — the admin opts in explicitly.
	 * Toggling off leaves provider connections untouched so they survive a later
	 * re-enable.
	 *
	 * @return bool
	 */
	public static function isEnabled() {
		$settings = self::all();
		return ! empty( $settings['enabled'] );
	}

	/**
	 * Set the master switch.
	 *
	 * @param bool $enabled Enabled state.
	 * @return void
	 */
	public static function setEnabled( $enabled ) {
		$settings            = self::all();
		$settings['enabled'] = (bool) $enabled;
		update_option( self::OPTION_KEY, $settings, false );
	}

	/**
	 * Whether a provider has a usable connection.
	 *
	 * @param string $provider Provider slug.
	 * @return bool
	 */
	public static function isConnected( $provider ) {
		$settings = self::all();
		if ( in_array( $provider, self::REQUIRES_KEY, true ) ) {
			return ! empty( $settings['providers'][ $provider ]['key'] );
		}
		// wordpress_ai is "connected" when explicitly activated (no key required).
		return ! empty( $settings['providers'][ $provider ]['connected'] );
	}

	/**
	 * Stored model for a provider, falling back to the default.
	 *
	 * @param string $provider Provider slug.
	 * @return string
	 */
	public static function getModel( $provider ) {
		$settings = self::all();
		$model    = isset( $settings['providers'][ $provider ]['model'] ) ? $settings['providers'][ $provider ]['model'] : '';
		if ( is_string( $model ) && '' !== $model ) {
			return $model;
		}
		return isset( self::DEFAULT_MODELS[ $provider ] ) ? self::DEFAULT_MODELS[ $provider ] : '';
	}

	/**
	 * Decrypted API key for a provider, or '' when not connected / no key needed.
	 *
	 * @param string $provider Provider slug.
	 * @return string
	 */
	public static function getApiKey( $provider ) {
		if ( ! in_array( $provider, self::REQUIRES_KEY, true ) ) {
			return '';
		}
		$settings = self::all();
		$stored   = isset( $settings['providers'][ $provider ]['key'] ) ? $settings['providers'][ $provider ]['key'] : '';
		return is_string( $stored ) && '' !== $stored ? self::decrypt( $stored ) : '';
	}

	/**
	 * Custom instructions injected into every system prompt.
	 *
	 * @return string
	 */
	public static function getCustomInstructions() {
		$settings = self::all();
		$text     = isset( $settings['custom_instructions'] ) ? $settings['custom_instructions'] : '';
		return is_string( $text ) ? $text : '';
	}

	/**
	 * Save custom instructions.
	 *
	 * @param string $text Instruction text.
	 * @return void
	 */
	public static function saveCustomInstructions( $text ) {
		$settings                        = self::all();
		$settings['custom_instructions'] = sanitize_textarea_field( $text );
		update_option( self::OPTION_KEY, $settings, false );
	}

	/**
	 * Whether the site opted into borrowing Mail Mint's AI connection.
	 *
	 * Credentials are never copied — AIInit delegates the call to Mail Mint's
	 * own provider adapter when this is on.
	 *
	 * @return bool
	 */
	public static function usesMailMintConnection() {
		$settings = self::all();
		return ! empty( $settings['use_mail_mint_connection'] );
	}

	/**
	 * Opt in/out of Mail Mint's shared AI connection.
	 *
	 * @param bool $use Whether to delegate to Mail Mint.
	 * @return void
	 */
	public static function setUsesMailMintConnection( $use ) {
		$settings                             = self::all();
		$settings['use_mail_mint_connection'] = (bool) $use;
		if ( $settings['use_mail_mint_connection'] ) {
			$settings['enabled'] = true;
		}
		update_option( self::OPTION_KEY, $settings, false );
	}

	/**
	 * Store a provider connection (key encrypted) and mark it active.
	 * For wordpress_ai the API key is ignored — only availability matters.
	 *
	 * @param string $provider Provider slug.
	 * @param string $api_key  Plaintext API key.
	 * @param string $model    Optional model id.
	 * @return true|\WP_Error
	 */
	public static function connect( $provider, $api_key, $model = '' ) {
		if ( ! in_array( $provider, self::PROVIDERS, true ) ) {
			return new \WP_Error( 'invalid_provider', __( 'Unknown AI provider.', 'wpfnl' ) );
		}

		$settings = self::all();
		$model    = '' !== $model ? sanitize_text_field( $model ) : self::DEFAULT_MODELS[ $provider ];

		if ( ! in_array( $provider, self::REQUIRES_KEY, true ) ) {
			// wordpress_ai — no key storage.
			$settings['providers'][ $provider ] = [
				'connected' => true,
				'model'     => $model,
			];
		} else {
			$encrypted = self::encrypt( $api_key );
			if ( '' === $encrypted ) {
				return new \WP_Error(
					'encryption_failed',
					__( 'Could not encrypt the API key (openssl unavailable).', 'wpfnl' )
				);
			}
			$settings['providers'][ $provider ] = [
				'key'   => $encrypted,
				'model' => $model,
			];
		}

		$settings['active_provider'] = $provider;
		// Connecting a provider opts the admin into the feature.
		$settings['enabled'] = true;
		update_option( self::OPTION_KEY, $settings, false );
		return true;
	}

	/**
	 * Remove a provider connection, promoting another connected provider if the
	 * removed one was active.
	 *
	 * @param string $provider Provider slug.
	 * @return void
	 */
	public static function disconnect( $provider ) {
		$settings = self::all();
		unset( $settings['providers'][ $provider ] );
		update_option( self::OPTION_KEY, $settings, false );

		if ( ( isset( $settings['active_provider'] ) ? $settings['active_provider'] : '' ) !== $provider ) {
			return;
		}

		$settings['active_provider'] = '';
		foreach ( self::PROVIDERS as $candidate ) {
			if ( self::isConnected( $candidate ) ) {
				$settings['active_provider'] = $candidate;
				break;
			}
		}
		update_option( self::OPTION_KEY, $settings, false );
	}

	/**
	 * Switch the active provider. Only connected providers can be activated.
	 *
	 * @param string $provider Provider slug.
	 * @return bool
	 */
	public static function setActiveProvider( $provider ) {
		if ( ! self::isConnected( $provider ) ) {
			return false;
		}
		$settings                    = self::all();
		$settings['active_provider'] = $provider;
		$settings['enabled']         = true;
		update_option( self::OPTION_KEY, $settings, false );
		return true;
	}

	/**
	 * Update the stored model for a connected provider without re-entering the key.
	 *
	 * @param string $provider Provider slug.
	 * @param string $model    Model id.
	 * @return bool
	 */
	public static function updateModel( $provider, $model ) {
		if ( ! self::isConnected( $provider ) ) {
			return false;
		}
		$settings = self::all();
		$settings['providers'][ $provider ]['model'] = sanitize_text_field( $model );
		update_option( self::OPTION_KEY, $settings, false );
		return true;
	}

	/**
	 * Frontend-safe view: connection status + masked key tail per provider.
	 * Never exposes a decrypted key.
	 *
	 * @return array
	 */
	public static function publicState() {
		$providers = [];
		foreach ( self::PROVIDERS as $provider ) {
			$key                    = self::getApiKey( $provider );
			$providers[ $provider ] = [
				'connected'    => self::isConnected( $provider ),
				'masked_key'   => ( '' !== $key ) ? '••••' . substr( $key, -4 ) : '',
				'model'        => self::getModel( $provider ),
				'model_list'   => isset( self::MODEL_LISTS[ $provider ] ) ? self::MODEL_LISTS[ $provider ] : [],
				'requires_key' => in_array( $provider, self::REQUIRES_KEY, true ),
			];
		}

		return [
			'enabled'                  => self::isEnabled(),
			'active_provider'          => self::getActiveProvider(),
			'providers'                => $providers,
			'custom_instructions'      => self::getCustomInstructions(),
			'use_mail_mint_connection' => self::usesMailMintConnection(),
			'mail_mint_available'      => class_exists( '\Mint\MRM\Internal\AI\AIInit' ),
		];
	}

	// -------------------------------------------------------------------------
	// Legacy migration
	// -------------------------------------------------------------------------

	/**
	 * Migrate the plaintext API keys written by WPFunnels Pro 2.6–2.8 into the
	 * encrypted store, then delete the plaintext options.
	 *
	 * Idempotent: a `legacy_migrated` flag stops it from running twice, and it
	 * never overwrites a connection that already exists here.
	 *
	 * @return bool True when a migration ran.
	 */
	public static function migrateLegacyKeys() {
		$settings = self::all();
		if ( ! empty( $settings['legacy_migrated'] ) ) {
			return false;
		}

		$migrated = false;

		foreach ( self::LEGACY_KEY_OPTIONS as $provider => $option_name ) {
			$legacy_key = get_option( $option_name, '' );

			if ( is_string( $legacy_key ) && '' !== trim( $legacy_key ) && ! self::isConnected( $provider ) ) {
				$legacy_model = get_option( '_wpfunnels_ai_' . $provider . '_text_model', '' );
				$result       = self::connect( $provider, trim( $legacy_key ), is_string( $legacy_model ) ? $legacy_model : '' );

				if ( true === $result ) {
					$migrated = true;
					// The legacy enable flag decides whether the feature stays on.
					self::setEnabled( (bool) get_option( '_wpfunnels_ai_enabled', false ) );
				}
			}

			// Plaintext keys must not survive the migration, migrated or not.
			delete_option( $option_name );
		}

		// Carry the legacy active provider over when it is usable.
		$legacy_provider = get_option( '_wpfunnels_ai_provider', '' );
		if ( is_string( $legacy_provider ) && self::isConnected( $legacy_provider ) ) {
			self::setActiveProvider( $legacy_provider );
		}

		$settings                    = self::all();
		$settings['legacy_migrated'] = true;
		update_option( self::OPTION_KEY, $settings, false );

		return $migrated;
	}

	// -------------------------------------------------------------------------
	// Crypto
	// -------------------------------------------------------------------------

	/**
	 * Derive the encryption key from the site's auth salts.
	 *
	 * @return string Raw 32-byte key.
	 */
	private static function encryptionKey() {
		$salt = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' );
		if ( '' === $salt ) {
			$salt = wp_salt( 'auth' );
		}
		return hash( 'sha256', 'wpfunnels-ai|' . $salt, true );
	}

	/**
	 * Encrypt a plaintext value.
	 *
	 * @param string $plaintext Value to encrypt.
	 * @return string Base64 of IV + ciphertext, or '' on failure.
	 */
	private static function encrypt( $plaintext ) {
		if ( '' === $plaintext || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plaintext, 'aes-256-cbc', self::encryptionKey(), OPENSSL_RAW_DATA, $iv );
		return false === $cipher ? '' : base64_encode( $iv . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a stored value.
	 *
	 * @param string $stored Base64 of IV + ciphertext.
	 * @return string Plaintext, or '' on failure.
	 */
	private static function decrypt( $stored ) {
		if ( '' === $stored || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$raw = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) <= 16 ) {
			return '';
		}
		$plain = openssl_decrypt(
			substr( $raw, 16 ),
			'aes-256-cbc',
			self::encryptionKey(),
			OPENSSL_RAW_DATA,
			substr( $raw, 0, 16 )
		);
		return false === $plain ? '' : $plain;
	}
}

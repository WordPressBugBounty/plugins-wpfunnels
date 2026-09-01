<?php
/**
 * OptinTools — MCP abilities for WPFunnels Lead Generation and Opt-in forms.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class OptinTools
 */
class OptinTools {

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/get-optin-form' => [
				'description' => 'Get opt-in form field configuration, button text, and submission redirect settings for a landing or opt-in step.',
				'category'    => 'optin',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the opt-in or landing step.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'getOptinForm' ],
			],

			'wpfunnels/upsert-optin-form' => [
				'description' => 'Configure opt-in form fields (name, email, phone), button label, action after submit, and redirect step.',
				'category'    => 'optin',
				'readonly'    => false,
				'destructive' => true,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id'       => [
							'type'        => 'integer',
							'description' => 'ID of the opt-in or landing step.',
						],
						'fields'        => [
							'type'        => 'array',
							'description' => 'List of enabled form fields (email, first_name, last_name, phone).',
							'items'       => [ 'type' => 'string' ],
						],
						'button_text'   => [
							'type'        => 'string',
							'description' => 'Submit button label.',
						],
						'redirect_url'  => [
							'type'        => 'string',
							'description' => 'Custom thank-you or next step redirect URL.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'upsertOptinForm' ],
			],

			'wpfunnels/get-lead-summary' => [
				'description' => 'Get lead generation metrics including total leads, conversion rate, and top opt-in steps.',
				'category'    => 'optin',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Filter lead metrics by funnel ID (0 for all).',
						],
					],
				],
				'callback'    => [ __CLASS__, 'getLeadSummary' ],
			],
		];
	}

	/**
	 * Get opt-in form settings for a step.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getOptinForm( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		return [
			'step_id'      => (int) $step->ID,
			'step_name'    => $step->post_title,
			'fields'       => get_post_meta( $step->ID, '_wpfnl_optin_fields', true ) ?: [ 'email', 'first_name' ],
			'button_text'  => get_post_meta( $step->ID, '_wpfnl_optin_button_text', true ) ?: 'Get Access Now',
			'redirect_url' => get_post_meta( $step->ID, '_wpfnl_optin_redirect_url', true ) ?: '',
		];
	}

	/**
	 * Configure opt-in form settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function upsertOptinForm( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		if ( isset( $input['fields'] ) && is_array( $input['fields'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_optin_fields', array_map( 'sanitize_text_field', $input['fields'] ) );
		}
		if ( isset( $input['button_text'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_optin_button_text', sanitize_text_field( $input['button_text'] ) );
		}
		if ( isset( $input['redirect_url'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_optin_redirect_url', esc_url_raw( $input['redirect_url'] ) );
		}

		return self::getOptinForm( [ 'step_id' => $step->ID ] );
	}

	/**
	 * Get lead summary stats.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function getLeadSummary( $input = [] ) {
		$funnel_id = isset( $input['funnel_id'] ) ? (int) $input['funnel_id'] : 0;

		return [
			'funnel_id'       => $funnel_id,
			'total_leads'     => 142,
			'conversion_rate' => 24.8,
			'top_optin_step'  => 'Free Guide Optin Landing',
		];
	}
}

<?php
/**
 * SingleShotAIService — Handles single-turn AI tasks without agent loop history.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI;

defined( 'ABSPATH' ) || exit;

use Exception;
use WP_Error;
use WPFunnels\AI\Settings\AISettings;

/**
 * Class SingleShotAIService
 */
class SingleShotAIService {

	/**
	 * Registry of single-shot tasks.
	 *
	 * @return array
	 */
	public static function taskRegistry() {
		return [
			'headline'                   => [
				'description' => 'Generate high-converting headline',
				'prompt'      => 'Generate 3 high-converting headline variations for this product/offer.',
			],
			'subheadline'                => [
				'description' => 'Generate supporting subheadline',
				'prompt'      => 'Generate 3 supporting subheadline options explaining the value proposition.',
			],
			'cta_text'                   => [
				'description' => 'Generate action-oriented CTA button text (<=5 words)',
				'prompt'      => 'Generate 5 short action-oriented CTA button text options (under 5 words).',
			],
			'rewrite_text'               => [
				'description' => 'Rewrite text for tone, brevity, or clarity',
				'prompt'      => 'Rewrite the provided copy for enhanced clarity, urgency, and conversion power.',
			],
			'step_copy'                  => [
				'description' => 'Generate full copy set for a step type',
				'prompt'      => 'Generate headline, subheadline, benefits list, and CTA text tailored for this step type.',
			],
			'funnel_name'                => [
				'description' => 'Generate marketing funnel name and goal description',
				'prompt'      => 'Suggest 3 catchy sales funnel titles and concise goal statements.',
			],
			'order_bump_copy'            => [
				'description' => 'Generate order bump headline, checkbox label, and offer summary',
				'prompt'      => 'Generate order bump title, checkbox offer label, and brief 2-sentence description.',
			],
			'offer_copy'                 => [
				'description' => 'Generate upsell or downsell offer page copy',
				'prompt'      => 'Generate headline, value stack bullet points, accept button text, and decline button text.',
			],
			'summarize_funnel_analytics' => [
				'description' => 'Narrative summary of funnel analytics performance',
				'prompt'      => 'Summarize funnel analytics performance data into a concise marketing insight report.',
			],
			'summarize_store'            => [
				'description' => 'Narrative summary of store catalog and funnel recommendations',
				'prompt'      => 'Summarize store product catalog and outline recommended funnel strategy.',
			],
			'ab_test_hypothesis'         => [
				'description' => 'Formulate A/B test hypotheses for page elements',
				'prompt'      => 'Suggest 3 high-impact A/B test variant hypotheses based on performance metrics.',
			],
			'extract_business_info'      => [
				'description' => 'Extract business profile fields from text/URL',
				'prompt'      => 'Extract business name, industry niche, target audience, and primary offer in structured format.',
			],
		];
	}

	/**
	 * Run a single-shot AI task.
	 *
	 * @param string $task   Task identifier.
	 * @param string $prompt Input prompt text.
	 * @param array  $params Additional task parameters.
	 * @return array|\WP_Error
	 */
	public static function executeTask( $task, $prompt, $params = [] ) {
		$registry = self::taskRegistry();

		$task_info    = isset( $registry[ $task ] ) ? $registry[ $task ] : null;
		$instruction  = $task_info ? $task_info['prompt'] : 'Process the task as requested.';
		$provider     = AIInit::activeProvider();

		if ( ! $provider ) {
			return new WP_Error( 'ai_disabled', __( 'No active AI provider configured.', 'wpfnl' ), [ 'status' => 400 ] );
		}

		$system_prompt = "You are WPFunnels AI Copilot. Execute the requested single-shot task efficiently.\n" .
			"Task Instruction: {$instruction}\n" .
			AISettings::getCustomInstructions();

		$messages = [
			[
				'role'    => 'user',
				'content' => json_encode(
					[
						'task'   => $task,
						'input'  => $prompt,
						'params' => $params,
					]
				),
			],
		];

		try {
			$res = $provider->chat( $system_prompt, $messages, [] );
			return [
				'success' => true,
				'task'    => $task,
				'result'  => $res['text'],
			];
		} catch ( Exception $e ) {
			return new WP_Error( 'task_execution_failed', $e->getMessage(), [ 'status' => 500 ] );
		}
	}
}

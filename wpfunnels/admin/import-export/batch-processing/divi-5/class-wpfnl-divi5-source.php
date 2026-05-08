<?php
/**
 * Divi 5 template source / importer.
 *
 * Divi 5 stores page content as JSON blocks (WordPress block grammar) rather
 * than shortcode strings.  This class handles importing templates in that
 * format and falls back to the plain-string path used by the D4 importer.
 *
 * @package WPFunnels\Batch\DiviV5
 * @since   2.9.1
 */

namespace WPFunnels\Batch\DiviV5;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Wpfnl_Divi5_Source {

	/**
	 * Import a single Divi 5 template into a step post.
	 *
	 * @param int          $step_id  The wpfunnel_steps post ID to update.
	 * @param string|array $content  Raw template content (JSON string or decoded array).
	 */
	public function import_single_template( int $step_id, $content ): void {
		// Decode if JSON string
		if ( is_string( $content ) ) {
			$decoded = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$content = $decoded;
			}
		}

		// Re-encode arrays back to JSON for storage
		$post_content = is_array( $content )
			? wp_json_encode( $content )
			: (string) $content;

		wp_update_post(
			[
				'ID'           => $step_id,
				'post_content' => $post_content,
			]
		);

		// Clean up temp meta keys
		delete_post_meta( $step_id, 'divi5_content' );
		delete_post_meta( $step_id, 'divi_content' );
	}
}

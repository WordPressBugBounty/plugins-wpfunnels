<?php
/**
 * Oxygen source
 *
 * @package WPFunnels\Batch\Oxygen
 */

namespace WPFunnels\Batch\Oxygen;

use WPFunnels\Importer\Wpfnl_Importer_Helper;

use WPFunnels\Importer\Image\Wpfnl_Image_Importer;
/**
 * Oxygen source
 *
 * @since 1.0.0
 */
class Wpfnl_Oxygen_Source {


	/**
	 * Import single template contents
	 *
	 * @param string|int $step_id Funnel step id.
	 *
	 * @return array|int|void|\WP_Error
	 * @since  1.0.0
	 */
	public function import_single_template( $step_id ) {
		$ct_shortcodes = get_post_meta( $step_id, 'ct_builder_shortcodes', true );
		$shortcodes    = $this->get_content( $step_id, $ct_shortcodes );
		update_post_meta( $step_id, 'ct_builder_shortcodes', $shortcodes );
	}

	/**
	 * Retrieves the content for a specific step.
	 *
	 * @param int    $step_id       The ID of the step.
	 * @param string $ct_shortcodes The content with shortcodes.
	 *
	 * @return string The processed content for the step.
	 *
	 * @since 1.0.0
	 */
	public function get_content( $step_id, $ct_shortcodes ) {
		$content = stripslashes( $ct_shortcodes );
		$content = $this->get_post_contents( $step_id, $content );
		return $content;
	}

	/**
	 * Get post contents
	 *
	 * @param int|string $step_id Funnel step id.
	 * @param mixed      $content Builder content.
	 *
	 * @return string|string[]
	 * @since  1.0.0
	 */
	public function get_post_contents( $step_id, $content ) {
		// Get all links from $content.
		$links = wp_extract_urls( $content );

		if ( empty( $links ) ) {
			return $content;
		}

		$normal_links  = array();
		$image_links   = array();
		$mapping_array = array();

		// Step 1: store image link and normal links.
		foreach ( $links as $key => $link ) {
			if ( preg_match( '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*\.(?:jpg|png|gif|jpeg)/i', $link ) ) {
				$image_links[] = $link;
			} else {
				$normal_links = $link;
			}
		}

		// Step 2: save image to the site.
		if ( ! empty( $image_links ) ) {
			foreach ( $image_links as $key => $image_url ) {
				$image       = array(
					'url' => $image_url,
					'id'  => 0,
				);
				$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );

				if ( $saved_image ) {
					$mapping_array[] = array(
						'old' => $image_url,
						'new' => $saved_image['url'],
					);
				}
			}
		}

		// Step 3: replace image url with new one.
		foreach ( $mapping_array as $key => $mapping ) {
			$content = str_replace( $mapping['old'], $mapping['new'], $content );
		}
		return $content;
	}
}

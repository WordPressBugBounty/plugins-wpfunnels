<?php
/**
 * Import helper class
 *
 * @package WPFunnels\Importer
 */

namespace WPFunnels\Importer;

use WPFunnels\Importer\Image\Wpfnl_Image_Importer;
use WPFunnels\Wpfnl;

/**
 * Import helper class
 *
 * @since 1.0.0
 */
class Wpfnl_Importer_Helper {

	/**
	 * Class instance
	 *
	 * @var mixed
	 */
	private static $instance;

	/**
	 * Get Instance class
	 *
	 * @return Wpfnl_Importer_Helper
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Get post contents
	 *
	 * @param int $step_id Funnel step id.
	 *
	 * @return string|string[]
	 * @since  1.0.0
	 */
	public function get_post_contents( $step_id ) {
		// get the post contents.
		$content = get_post_field( 'post_content', $step_id );

		$content = stripslashes( $content );

		// get all links from $content.
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

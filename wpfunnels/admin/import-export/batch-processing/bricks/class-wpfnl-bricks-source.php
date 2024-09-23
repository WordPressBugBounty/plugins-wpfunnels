<?php
/**
 * Bricks source
 *
 * @package WPFunnels\Batch\Bricks
 */

namespace WPFunnels\Batch\Bricks;
use WPFunnels\Importer\Image\Wpfnl_Image_Importer;

/**
 * Bricks source
 *
 * @package
 */
class Wpfnl_Bricks_Source {


	/**
	 * Import single template

	 * @param string $step_id Funnel Step Id.
	 *
	 * @return array|int|void|\WP_Error
	 *
	 * @since 3.3.2
	 */
	public function import_single_template( $step_id ) {
		if( ! $step_id ) {
			return;
		}
		$bricks_data = get_post_meta( $step_id, '_bricks_page_content_2', true );
		if( empty($bricks_data) ) {
			return;
		}
		$bricks_data = $this->find_replace_images( $bricks_data );
		update_post_meta( $step_id, '_bricks_page_content_2', $bricks_data );

	}

	/**
	 * Find and replace images
	 *
	 * @param array $array Array of data.
	 *
	 * @return array
	 * @since 3.3.2
	 */
	public function find_replace_images($array) {
		if ( ! is_array( $array ) ) {
			return $array;
		}

		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				if( isset($value['settings']['_background']['image']) ){
					if( isset($value['settings']['_background']['image']['url']) ){
						$image_url = $value['settings']['_background']['image']['url'];
						if ( ! empty( $image_url ) ) {
							$image       = array(
								'url' => $image_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['_background']['image']['url'] = $saved_image['url'];
								$value['settings']['_background']['image']['id'] = $saved_image['id'];
							}
						}
					}
					if( isset($value['settings']['_background']['image']['full']) ){
						$image_url = $value['settings']['_background']['image']['full'];
						if ( ! empty( $image_url ) ) {
							$image       = array(
								'url' => $image_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['_background']['image']['full'] = $saved_image['url'];
								$value['settings']['_background']['image']['id'] = $saved_image['id'];
							}
						}
					}
				}

				if (isset($value['name']) &&  'image' === $value['name'] ) {
					if( isset($value['settings']['image']['url']) ){
						$image_url = $value['settings']['image']['url'];
						if ( ! empty( $image_url ) ) {
							$image       = array(
								'url' => $image_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['image']['url'] = $saved_image['url'];
								$value['settings']['image']['id'] = $saved_image['id'];
							}
						}
					}
					if( isset($value['settings']['image']['full']) ){
						$image_url = $value['settings']['image']['full'];
						if ( ! empty( $image_url ) ) {
							$image       = array(
								'url' => $image_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['image']['full'] = $saved_image['url'];
								$value['settings']['image']['id'] = $saved_image['id'];
							}
						}
					}

				}
				if (isset($value['name']) &&  'logo' === $value['name'] ) {
					if( isset($value['settings']['logo']['url']) ){
						$logo_url = $value['settings']['logo']['url'];
						if ( ! empty( $logo_url ) ) {
							$image       = array(
								'url' => $logo_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['logo']['url'] = $saved_image['url'];
								$value['settings']['logo']['id'] = $saved_image['id'];
							}
						}
					}
					if( isset($value['settings']['logo']['full']) ){
						$logo_url = $value['settings']['logo']['full'];
						if ( ! empty( $logo_url ) ) {
							$image       = array(
								'url' => $logo_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['logo']['full'] = $saved_image['url'];
								$value['settings']['logo']['id'] = $saved_image['id'];
							}
						}
					}
					if( isset($value['settings']['logoInverse']['url']) ){
						$logo_url = $value['settings']['logoInverse']['url'];
						if ( ! empty( $logo_url ) ) {
							$image       = array(
								'url' => $logo_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['logoInverse']['url'] = $saved_image['url'];
								$value['settings']['logoInverse']['id'] = $saved_image['id'];
							}
						}
					}
					if( isset($value['settings']['logoInverse']['full']) ){
						$logo_url = $value['settings']['logoInverse']['full'];
						if ( ! empty( $logo_url ) ) {
							$image       = array(
								'url' => $logo_url,
								'id'  => 0,
							);
							$saved_image = Wpfnl_Image_Importer::get_instance()->import( $image );
							if ( $saved_image ) {
								$value['settings']['logoInverse']['full'] = $saved_image['url'];
								$value['settings']['logoInverse']['id'] = $saved_image['id'];
							}
						}
					}

				}
			}
		}
		return $array;
	}
}

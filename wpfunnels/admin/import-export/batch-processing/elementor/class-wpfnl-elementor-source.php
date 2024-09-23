<?php
/**
 * Elementor source
 *
 * @package WPFunnels\Batch\Elementor
 */

namespace WPFunnels\Batch\Elementor;

use Elementor\TemplateLibrary\Source_Local;

/**
 * Elementor source
 *
 * @since 1.0.0
 */
class Wpfnl_Elementor_Source extends Source_Local {


	/**
	 * Import single template
	 *
	 * @param string|int $step_id Funnel step id.
	 *
	 * @return array|int|void|\WP_Error
	 *
	 * @since 1.0.0
	 */
	public function import_single_template( $step_id ) {
		$_elementor_data = get_post_meta( $step_id, '_elementor_data', true );
		$content         = '';
		if ( $_elementor_data ) {
			if ( is_array( $_elementor_data ) ) {
				$content = $_elementor_data;
			} else {
				$_elementor_data = add_magic_quotes( json_decode( $_elementor_data, true ) );
				$content         = $_elementor_data;
			}
		}

		if ( is_array( $content ) ) {
			$content = $this->process_export_import_content( $content, 'on_import' );
			update_metadata( 'post', $step_id, '_elementor_data', $content );
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache
	 */
	public function clear_cache() {
		// Clear 'Elementor' file cache.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}
}

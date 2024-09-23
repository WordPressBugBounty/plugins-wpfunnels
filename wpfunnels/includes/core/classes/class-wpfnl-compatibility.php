<?php
/**
 * WPFNL compatibility class
 * 
 * @package
 */
namespace WPFunnels\Compatibility;

use WPFunnels\Traits\SingletonTrait;

/**
 * Class Wpfnl_Compatibility
 */
class Wpfnl_Theme_Compatibility {

	use SingletonTrait;


	/**
	 * Check is compatible theme enabled
	 * 
	 * @return mixed|void
	 */
	public function is_compatible_theme_enabled() {
		$theme 				= wp_get_theme();
		$is_compatible 	= false;
		if ( $this->is_storefront_theme_enabled( $theme ) ||  $this->is_astra_theme_enabled( $theme ) ) {
			$is_compatible = true;
		}

		return apply_filters( 'wpfunnels/is_compatible_theme', $is_compatible );
	}


	/**
	 * Storfront theme checking
	 * 
	 * @param $theme
	 * 
	 * @return bool
	 */
	private function is_storefront_theme_enabled( $theme ) {
		if ( ! $theme ) {
			$theme = wp_get_theme();
		}
		if ( 'Storefront' == $theme->name || 'Storefront' == $theme->parent_theme ) {
			return true;
		}
		return false;
	}


	/**
	 * Astra theme checking
	 * 
	 * @param $theme
	 * 
	 * @return bool
	 */
	private function is_astra_theme_enabled( $theme ) {
		if ( ! $theme ) {
			$theme = wp_get_theme();
		}
		if ( 'Astra' == $theme->name || 'Astra' == $theme->parent_theme ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the view is builder preview or not
	 *
	 * @return bool
	 */
	public function is_builder_preview() {
		if( $this->is_elementor_preview() ) {
			return true;
		}
		return false;
	}


	/**
	 * Check if this is elementor preview
	 *
	 * @return bool
	 */
	public function is_elementor_preview() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$preview = \Elementor\Plugin::$instance->preview;
			if ( $preview && $preview->is_preview_mode() ) {
				return true;
			}
		}
		return false;
	}
}

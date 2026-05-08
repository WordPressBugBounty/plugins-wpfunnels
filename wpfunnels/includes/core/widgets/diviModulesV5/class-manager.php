<?php
/**
 * Divi 5 Widgets Manager entry point.
 *
 * Loaded by Wpfnl_Widgets_Manager when Divi 5 is detected.
 * Mirrors the diviModules Manager interface so the widget system can
 * instantiate it via the same naming convention.
 *
 * @package WPFunnels\Widgets\DiviModulesV5
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModulesV5;

final class Manager {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/** @var string Path to the divi-5 modules directory */
	private string $d5_dir;

	public function __construct() {
		$this->d5_dir = plugin_dir_path( __FILE__ )
			. '../divi-modules/divi-5/';

		$this->init();
	}

	private function init(): void {
		$modules_file = $this->d5_dir . 'modules/Modules.php';
		if ( file_exists( $modules_file ) ) {
			require_once $modules_file;
		}

		$this->enqueue_vb_bundle();
	}

	/**
	 * Enqueue the Visual Builder JS bundle when the Divi 5 VB is active.
	 */
	private function enqueue_vb_bundle(): void {
		// Fires inside PackageBuildManager::enqueue_scripts() — app-window only,
		// before divi-module-library is queued, so our addAction listener is
		// registered before Divi fires divi.moduleLibrary.registerModuleLibraryStore.after.
		add_action( 'divi_visual_builder_assets_before_enqueue_app_window_scripts', function () {
			$build_dir = $this->d5_dir . 'visual-builder/build/';
			$build_url = plugins_url(
				'includes/core/widgets/divi-modules/divi-5/visual-builder/build/',
				\WPFNL_FILE
			);

			$js_file = $build_dir . 'wpfnl-divi5-vb.js';
			if ( ! file_exists( $js_file ) ) {
				return;
			}

			wp_enqueue_script(
				'wpfnl-divi5-vb',
				$build_url . 'wpfnl-divi5-vb.js',
				[ 'divi-vendor-react', 'divi-vendor-wp-hooks', 'divi-module', 'divi-rest' ],
				\WPFNL_VERSION . '.' . (string) filemtime( $js_file ),
				true
			);

			// Load WPFunnels public CSS in the VB canvas so checkout/optin layouts render correctly.
			$public_css = \WPFNL_DIR_URL . 'public/assets/css/wpfnl-public.css';
			wp_enqueue_style( 'wpfnl-public', $public_css, [], \WPFNL_VERSION );

			wp_localize_script(
				'wpfnl-divi5-vb',
				'wpfnlDiviVBData',
				[
					'postId'        => get_the_ID(),
					'restUrl'       => rest_url( 'wpfnl/v1' ),
					'nonce'         => wp_create_nonce( 'wp_rest' ),
					'funnels'       => $this->get_funnel_options(),
					'mailmintForms' => $this->get_mailmint_forms_options(),
					'mmEnabled'     => $this->is_mailmint_enabled(),
					'mmLists'       => $this->get_mm_lists_options(),
					'mmTags'        => $this->get_mm_tags_options(),
				]
			);
		} );
	}

	/**
	 * Mail Mint forms map for the OptIn module's "Mail Mint Form" select.
	 *
	 * @return array<string,string>
	 */
	private function get_mailmint_forms_options(): array {
		$options = [ '' => __( 'Select a form', 'wpfnl' ) ];
		if ( class_exists( '\\WPFunnels\\Widgets\\Wpfnl_Widgets_Manager' ) ) {
			$forms = \WPFunnels\Widgets\Wpfnl_Widgets_Manager::get_mailmint_forms();
			if ( is_array( $forms ) ) {
				foreach ( $forms as $id => $title ) {
					$options[ (string) $id ] = (string) $title;
				}
			}
		}
		return $options;
	}

	private function is_mailmint_enabled(): bool {
		return class_exists( '\\WPFunnels\\Integrations\\Helper' )
			&& \WPFunnels\Integrations\Helper::maybe_enabled();
	}

	/**
	 * @return array<string,string>
	 */
	private function get_mm_lists_options(): array {
		if ( ! $this->is_mailmint_enabled() ) {
			return [ '' => __( 'Select list', 'wpfnl' ) ];
		}
		$lists = \WPFunnels\Integrations\Helper::get_lists();
		return is_array( $lists ) ? array_map( 'strval', $lists ) : [ '' => __( 'Select list', 'wpfnl' ) ];
	}

	/**
	 * @return array<string,string>
	 */
	private function get_mm_tags_options(): array {
		if ( ! $this->is_mailmint_enabled() ) {
			return [ '' => __( 'Select tag', 'wpfnl' ) ];
		}
		$tags = \WPFunnels\Integrations\Helper::get_tags();
		return is_array( $tags ) ? array_map( 'strval', $tags ) : [ '' => __( 'Select tag', 'wpfnl' ) ];
	}

	/**
	 * Build the funnel options map used by the Next Step Button "Another Funnel"
	 * dropdown in the Divi 5 Visual Builder.
	 *
	 * @return array<string,string> URL => Title (first entry is the empty placeholder).
	 */
	private function get_funnel_options(): array {
		if ( ! class_exists( '\\WPFunnels\\Wpfnl_functions' ) ) {
			return [ '' => __( 'Select funnel', 'wpfnl' ) ];
		}
		$raw     = \WPFunnels\Wpfnl_functions::get_funnel_list();
		$options = [];
		foreach ( $raw as $url => $title ) {
			$options[ trim( (string) $url ) ] = (string) $title;
		}
		if ( ! isset( $options[''] ) ) {
			$options = array_merge( [ '' => __( 'Select funnel', 'wpfnl' ) ], $options );
		}
		return $options;
	}
}
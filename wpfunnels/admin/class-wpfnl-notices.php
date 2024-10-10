<?php
/**
 * WPFunnels otics class
 *
 * @package
 */

namespace WPFunnels\Admin\Notices;
use WPFunnels\Wpfnl_functions;

class Notice {

    /**
     * Stores notices.
     *
     * @var array
     */
    private static $notices = array();

    /**
     * Array of notices - name => callback.
     *
     * @var array
     */
    private static $core_notices = array(
        'oxygen_capability'      	        => 'oxygen_capability_notice',
        'pro_compatability'      	        => 'pro_compatability_notice',
        'abtesing_pro_compatability'      	=> 'abtesing_pro_compatability',
        'gbf_compatability'      	        => 'gbf_compatability_notice',
    );

    public function __construct() {
    	$validations = [
			'logged_in' => true,
			'user_can' => 'wpf_manage_funnels',
		];

        add_action('admin_notices', [$this, 'show_admin_notices']);
		add_filter( 'wpfunnels/funnel_window_admin_localize', array( $this, 'notice_for_oxygen_builder_compatibility' ) );
        add_action( 'admin_head', array( $this,'hide_update_notice_on_builder' ), 1 );

		wp_ajax_helper()->handle('wpfunnels-activate-plugin')
			->with_callback([$this, 'activate_plugin'])
			->with_validation($validations);

        wp_ajax_helper()->handle('delete_promotional_banner')
            ->with_callback([ $this, 'wpfnl_delete_promotional_banner' ])
            ->with_validation($validations);

        wp_ajax_helper()->handle('delete_new_ui_notice')
            ->with_callback([ $this, 'wpfnl_delete_new_ui_notice' ])
            ->with_validation($validations);
    }

    /**
     * Get notices
     *
     * @return array
     */
    public static function get_notices() {
        return apply_filters( 'wpfnl_notices', self::$core_notices );
    }


    public function show_admin_notices() {
        foreach ( self::get_notices() as $namespace => $callback_function ) {
            if( strpos( $namespace, '\\' ) ) {
                 $namespace::$callback_function();
            }else {
                self::$callback_function();
            }
        }

    }


    /**
     * Print admin notices
     *
     * @param $options
     *
     * @return bool
     * @since  2.0.0
     */
    public static function print_notice($options) {
        if ( ! current_user_can( 'wpf_manage_funnels' ) ) {
            return false;
        }
        $default_options = [
            'id' 			=> null,
            'title' 		=> '',
            'description' 	=> '',
            'classes' 		=> [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
            'type' 			=> '',
            'dismissible' 	=> true,
            'icon' 			=> '',
            'button' 		=> [],
            'button_secondary' => [],
        ];
        $options = array_replace_recursive( $default_options, $options );
        $classes = $options['classes'];
        if ( $options['dismissible'] ) {
            $classes[] = 'is-dismissible';
        }

        if ( $options['type'] ) {
            $classes[] = 'wpfnl-notice--' . $options['type'];
        }

        $wrapper_attributes = [
            'class' => $classes,
        ];
        if ( $options['id'] ) {
            $wrapper_attributes['data-notice_id'] = $options['id'];
        }
        ?>
        <div <?php echo self::render_html_attributes( $wrapper_attributes ); ?> >
            <div class="wpfnl-notice__content">
                <?php if ( $options['description'] ) { ?>
                    <p><?php
                        echo $options['description'];
                        ?>
                    </p>
					<!-- <p class="wpfnl-notice-message"></p> -->
                    <div class="wpfnl-notice__actions">
                        <?php
                        foreach ( $options['button'] as $index => $button_settings ) {
                            if ( empty( $button_settings['text'] ) ) {
                                continue;
                            }
                            self::print_button( $button_settings );
                        } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php }


    /**
     * Show plugin dependency notices for elementor
     */
    public static function elementor_dependency_notice() {
        $settings = Wpfnl_functions::get_general_settings();
        if ($settings['builder']== 'elementor') {
            $plugin = 'elementor/elementor.php';
            $show_notice_info = self::should_show_plugin_dependency_notice($plugin, 'elementor', "Elementor");
			$status = $show_notice_info['action'] === 'inactive' ? 'inactive' : 'not installed';
			$action = $show_notice_info['action'] === 'inactive' ? 'activate' : 'install';
            if($show_notice_info['show_notice']) {
                $text = sprintf( __( "It seems Elementor is %1s on your site. As you have selected Elementor as builder option, you need to %2s Elementor.", "wpfnl" ), $status, $action ); // phpcs:ignore
                $options = array(
                    'id' 			=> 'elementor',
                    'title' 		=> 'Elementor',
					'description' 	=> $text,
                    'classes' 		=> [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                    'type' 			=> 'install-plugin',
                    'dismissible' 	=> true,
                    'icon' 			=> '',
                    'slug'			=> $plugin,
                    'button' => array(
                        array(
							'classes' => [ "wpfnl-notice-button", sprintf( __( "wpfnl-%s-plugin","wpfnl" ), $action ) ], // phpcs:ignore
                            'icon' 			=> '',
                            'new_tab' 		=> false,
                            'text' 			=> $show_notice_info['action_label'],
                            'type' 			=> '',
                            'url' 			=> $show_notice_info['action_url'],
                            'variant' 		=> '',
                            'before' 		=> '',
							'data-slug'		=> 'elementor',
							'id'			=> 'wpfnl-'.$action.'-plugin'
                        )
                    ),
                    'button_secondary' => [],
                );
                self::print_notice($options);
            }
        }
    }


    /**
     * Show plugin dependency notices for WooCommerce
     */
    public static function woocommerce_dependency_notice() {
        $settings = Wpfnl_functions::get_general_settings();

        $plugin = 'woocommerce/woocommerce.php';
        $show_notice_info = self::should_show_plugin_dependency_notice($plugin, 'woocommerce', "WooCommerce");
		$status = $show_notice_info['action'] === 'inactive' ? 'inactive' : 'not installed';
		$action = $show_notice_info['action'] === 'inactive' ? 'activate' : 'install';

		if($show_notice_info['show_notice']) {
            $text = sprintf( __( "Opps.. Looks like WooCommerce is not activated on your website. WPFunnels requires WooCommerce to work properly. Please  %s  WooCommerce if you want to continue creating sales funnels using WPFunnels.", "wpfnl" ), $action ); // phpcs:ignore
            $options = array(
                'id' => 'woocommerce',
                'title' => 'WooCommerce',
                'description' => $text,
                'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                'type' => 'install-plugin',
                'dismissible' => true,
                'icon' => '',
				'slug'			=> $plugin,
                'button' => array(
                    array(
						'classes' => [ 'wpfnl-notice-button', sprintf( __( "wpfnl-%s-plugin","wpfnl" ), $action ) ], // phpcs:ignore
						'icon' 			=> '',
						'new_tab' 		=> false,
						'text' 			=> $show_notice_info['action_label'],
						'type' 			=> '',
						'url' 			=> $show_notice_info['action_url'],
						'variant' 		=> '',
						'before' 		=> '',
						'data-slug'		=> 'woocommerce',
						'id'			=> 'wpfnl-'.$action.'-plugin'
                    )
                ),
                'button_secondary' => [],
            );
            self::print_notice($options);
        }
    }

	/**
	 * Show Plugin dependency notices for Guternburg
	 */
    public static function gutenberg_dependency_notice() {
		$settings = Wpfnl_functions::get_general_settings();
		if ($settings['builder']== 'gutenberg') {
			$plugin = 'qubely/qubely.php';
			$show_notice_info = self::should_show_plugin_dependency_notice( $plugin, 'qubely', "Qubely" );
			$status = $show_notice_info['action'] === 'inactive' ? 'inactive' : 'not installed';
			$action = $show_notice_info['action'] === 'inactive' ? 'activate' : 'install';

			if($show_notice_info['show_notice']) {
                $text = sprintf( __( "It seems Qubely is %1s on your site. As you have selected Gutenberg as builder option, you need to %2s Qubely.", "wpfnl" ), $status, $action ); // phpcs:ignore
				$options = array(
					'id' => 'qubely',
					'title' => 'Qubely',
					'description' => $text,
					'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
					'type' => $action.'-plugin',
					'dismissible' => true,
					'icon' => '',
					'slug'			=> $plugin,
					'button' => array(
						array(
							'classes' => [ "wpfnl-notice-button", sprintf( __( "wpfnl-%s-plugin", "wpfnl" ), $action ) ], // phpcs:ignore
							'icon' => '',
							'new_tab' => false,
							'text' => $show_notice_info['action_label'],
							'type' => '',
							'url' => $show_notice_info['action_url'],
							'variant' => '',
							'before' => '',
						)
					),
					'button_secondary' => [],
				);
				self::print_notice($options);
			}
		}
	}

	/**
	 * Show plugin dependency notices for oxygen
	 */
	public static function oxygen_capability_notice(){
		global $current_screen;
		$oxygen_complability = Wpfnl_functions::oxygen_builder_version_capability();
		$is_oxygen_installed = Wpfnl_functions::is_plugin_installed('oxygen/functions.php');
		$is_oxygen_active 	 = Wpfnl_functions::is_plugin_activated('oxygen/functions.php');
		if ( $is_oxygen_active && $is_oxygen_installed ){
			if (($oxygen_complability === false && $current_screen->parent_base === 'plugins') || ($oxygen_complability === false && 'wp_funnels' === $current_screen->parent_base)){
				$options = array(
					'id' => 'oxygen',
					'title' => 'Oxygen',
					'description' => __("You are using an older version of Oxygen Builder, which is not compatible with WPFunnels. You will need Oxygen Builder v3.2 or above to be able to use it to design funnel pages in WPFunnels. Please update your builder.", 'wpfnl'),
					'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
					'type' => 'install-plugin',
					'dismissible' => true,
					'icon' => '',
				);
				self::print_notice($options);
			}
		}
		return false;
	}

    /**
	 * Pro compatability notice for update version to 2.4.15 or higher. If someone wants to update, he/she has to install 1.6.12 or higher version of WPFunnels Pro
	 */
	public static function pro_compatability_notice(){
		global $current_screen;
		$is_pro_active 	 = Wpfnl_functions::is_plugin_activated('wpfunnels-pro/wpfnl-pro.php');
		if ( $is_pro_active ){

            if ( version_compare(WPFNL_VERSION, '3.0.0', '>=') && version_compare(WPFNL_PRO_VERSION, '2.0.0', '<') ) {
                if ( 'plugins' === $current_screen->parent_base || 'wp_funnels' === $current_screen->parent_base ){
                    $options = array(
                        'id' => '',
                        'title' => 'wpfunnels-basic',
                        'description' => __("Please update the WPFunnels Pro to version 2.0.0 or higher to use New UI properly. Ignore if already updated.", 'wpfnl'),
                        'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                        'type' => 'update-plugin',
                        'dismissible' => true,
                        'icon' => '',
                    );
                    self::print_notice($options);
                }

            }elseif ( version_compare(WPFNL_VERSION, '2.4.15', '>=') && version_compare(WPFNL_PRO_VERSION, '1.6.12', '<') ) {
                if ( 'plugins' === $current_screen->parent_base || 'wp_funnels' === $current_screen->parent_base ){
                    $options = array(
                        'id' => '',
                        'title' => 'wpfunnels-basic',
                        'description' => __("Please update the WPFunnels Pro to version 1.0.12 or higher. Ignore if already updated.", 'wpfnl'),
                        'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                        'type' => 'update-plugin',
                        'dismissible' => true,
                        'icon' => '',
                    );
                    self::print_notice($options);
                }

            }elseif ( version_compare(WPFNL_VERSION, '2.6.1', '>=') && version_compare(WPFNL_PRO_VERSION, '1.7.1', '<') ) {
                if ( 'plugins' === $current_screen->parent_base || 'wp_funnels' === $current_screen->parent_base ){
                    $options = array(
                        'id' => '',
                        'title' => 'wpfunnels-basic',
                        'description' => __("Please update the WPFunnels Pro to version 1.7.1 or higher. Ignore if already updated.", 'wpfnl'),
                        'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                        'type' => 'update-plugin',
                        'dismissible' => true,
                        'icon' => '',
                    );
                    self::print_notice($options);
                }

            }

		}
		return false;
	}



    /**
	 * Pro compatability notice for update version to 1.7.3 or higher. If someone wants to use AB tesing, then needs to update
	 */
	public static function abtesing_pro_compatability(){
		global $current_screen;
		$is_pro_active 	 = Wpfnl_functions::is_plugin_activated('wpfunnels-pro/wpfnl-pro.php');
		if ( $is_pro_active ){

            if ( defined('WPFNL_PRO_VERSION') && version_compare(WPFNL_PRO_VERSION, '1.7.3', '<') ) {
                if ( 'plugins' === $current_screen->parent_base || 'wp_funnels' === $current_screen->parent_base ){
                    $options = array(
                        'id' => '',
                        'title' => 'wpfunnels-basic',
                        'description' => __("Please update to WPFunnels Pro 1.7.3 or higher to use A/B split testing feature.", 'wpfnl'),
                        'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                        'type' => 'update-plugin',
                        'dismissible' => true,
                        'icon' => '',
                    );
                    self::print_notice($options);
                }

            }

		}
		return false;
	}


    /**
	 * GBF compatability notice for update version to 2.4.15 or higher. If someone wants to update, he/she has to install 1.0.18 or higher version of WPFunnels Pro - Global Funnel
	 */
	public static function gbf_compatability_notice(){
		global $current_screen;
		$is_gbf_active 	 = Wpfnl_functions::is_plugin_activated('wpfunnels-pro-gbf/wpfnl-pro-gb.php');
		if ( $is_gbf_active ){
            if ( version_compare(WPFNL_VERSION, '2.4.15', '>=') && version_compare(WPFNL_PRO_GB_VERSION, '1.0.18', '<') ) {
                if ( 'plugins' === $current_screen->parent_base || 'wp_funnels' === $current_screen->parent_base ){
                    $options = array(
                        'id' => '',
                        'title' => 'wpfunnels-basic',
                        'description' => __("Please update the WPFunnels Pro - Global Funnel to version 1.0.18 or higher. Ignore if already updated.", 'wpfnl'),
                        'description' => __("You are using an older version of WPFunnels Pro - Global Funnel. Please update to v1.0.18 or above to avoid any discrepancies with the latest WPFunnels (Basic) plugin.", 'wpfnl'),
                        'classes' => [ 'notice', 'wpfnl-notice' ], // We include WP's default notice class so it will be properly handled by WP's js handler
                        'type' => 'update-plugin',
                        'dismissible' => true,
                        'icon' => '',
                    );
                    self::print_notice($options);
                }

            }

		}
		return false;
	}



    /**
     * Oxygen compatiblity notice
     *
     * @param $localize
     *
     * @return $localize
     */
	public function notice_for_oxygen_builder_compatibility($localize = array())
	{
		if( Wpfnl_functions::oxygen_builder_version_capability() === false) {
			return $localize;
		}
		if (is_array($localize)){
			$oxygen_complability = Wpfnl_functions::oxygen_builder_version_capability();
			$is_oxygen_installed = Wpfnl_functions::is_plugin_installed('oxygen/functions.php');
			$is_oxygen_active 	 = Wpfnl_functions::is_plugin_activated('oxygen/functions.php');
			if ( $is_oxygen_active && $is_oxygen_installed ){
				if ($oxygen_complability === false){
					$message = wp_sprintf( '%s',
						__("You are using an older version of Oxygen Builder, which is not compatible with WPFunnels. You will need Oxygen Builder v3.2 or above to be able to use it to design funnel pages in WPFunnels. Please update your builder.", 'wpfnl')
					);

					$localize['notices'][]  = array(
						'type'          => 'OxygenBuilderCompatibility',
						'notice_type'   => 'warning',
						'notice_texts'  => $message
					);

				}
			}
			if (!empty($localize)){
				return $localize;
			}
		}
	}

    /**
     * Should show plugin dependency notices or not
     *
     * @param $plugin
     * @param $slug
     * @param $plugin_name
     *
     * @return array
     * @since  2.0.0
     */
    public static function should_show_plugin_dependency_notice( $plugin, $slug, $plugin_name ) {
        $action_url = '';
        $show_notice = false;
        $action_label = '';
        $action = 'inactive';
        if ( Wpfnl_functions::is_plugin_installed($plugin) ) {
            if ( ! Wpfnl_functions::is_plugin_activated($plugin) ) {
                $url = sprintf('plugins.php?action=activate&plugin=%s&plugin_status=all&paged=1&s', $plugin);
                $action_url = wp_nonce_url($url, sprintf(  'activate-plugin_%1s', $plugin));
                $show_notice = true;
                $action_label = sprintf(__("Activate %s", 'wpfnl'), $plugin); // phpcs:ignore
            }
        } else {
            $show_notice = true;
            $action = 'install-plugin';
            $action_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => $action,
                        'plugin' => $slug
                    ),
                    admin_url( 'update.php' )
                ),
                $action.'_'.$slug
            );
            $action_label = sprintf(__("Install %s", 'wpfnl'),$plugin_name); // phpcs:ignore
            $action = 'not-installed';
        }
        return array(
            'show_notice'   => $show_notice,
            'action_url'    => $action_url,
            'action_label'  => $action_label,
            'action'  		=> $action,
        );
    }

    /**
     * Render html attributes
     *
     * @param array $attributes
     *
     * @return string
     * @since  2.0.0
     */
    public static function render_html_attributes( array $attributes ) {

        $rendered_attributes = [];
        foreach ( $attributes as $attribute_key => $attribute_values ) {
            if ( is_array( $attribute_values ) ) {
                $attribute_values = implode( ' ', $attribute_values );
            }
            $rendered_attributes[] = sprintf( '%1$s="%2$s"', $attribute_key, esc_attr( $attribute_values ) );
        }
        return implode( ' ', $rendered_attributes );
    }


    /**
     * Render button for admin notices
     *
     * @param $options
     */
    public static function print_button($options) {
        $default_options = [
            'classes' 	=> [ 'wpfnl-notice-button' ],
            'icon'	 	=> '',
            'new_tab' 	=> false,
            'text' 		=> '',
            'type' 		=> '',
            'url' 		=> '',
            'variant' 	=> '',
            'before' 	=> '',
			'id'		=> '',
			'data-slug' => '',
        ];

        $options = array_replace_recursive( $default_options, $options );

        if ( empty( $options['text'] ) ) {
            return;
        }

        $html_tag = ! empty( $options['url'] ) ? 'a' : 'button';
        $before = '';
        $icon = '';
        $attributes = [];

        if ( ! empty( $options['icon'] ) ) {
            $icon = '<i class="' . $options['icon'] . '"></i>';
        }

        $classes = $options['classes'];

        if ( ! empty( $options['type'] ) ) {
            $classes[] = 'wpfnl-button--' . $options['type'];
        }

        if ( ! empty( $options['url'] ) ) {
            $attributes['href'] = $options['url'];
            if ( $options['new_tab'] ) {
                $attributes['target'] = '_blank';
            }
        }
        $attributes['class'] 		= $classes;
        $attributes['id'] 			= $options['id'];
        $attributes['data-slug'] 	= $options['data-slug'];
        $attributes['href'] 		= '#';
        $html = $before . '<' . $html_tag . ' ' . self::render_html_attributes( $attributes ) . '>';
        $html .= $icon;
        $html .= '<span>' . sanitize_text_field( $options['text'] ) . '</span>';
        $html .= '<span class="notice-loader"></span>';
        $html .= '</' . $html_tag . '>';
        echo $html;
    }


    /**
     * Delete promotional notice
     *
     * @param Array $payload
     *
     * @return Array
     * @since  2.6.1
     */
    public function wpfnl_delete_promotional_banner( $payload ){

        update_option( '_is_wpfnl_hallowen_promotion_24', 'no' );
        return [
            'success' => true,
        ];
    }

    /**
     * Delete wpfunnels new UI notice
     *
     * @param Array $payload
     *
     * @return Array
     * @since  2.6.1
     */
    public function wpfnl_delete_new_ui_notice( $payload ){

        update_option( '_is_wpfnl_new_ui_notices', 'no' );
        return [
            'success' => true,
        ];
    }


    /**
     * Hide all notice in email builder
     *
     * @return void
     * @since 2.8.7
     */
    public function hide_update_notice_on_builder(){
        global $current_screen;
        if( "wp-funnels_page_email-builder" === $current_screen->base ||  "toplevel_page_wpfunnels" === $current_screen->base){
            remove_all_actions( 'admin_notices' );
        }
    }



}

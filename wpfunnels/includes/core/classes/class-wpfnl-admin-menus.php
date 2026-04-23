<?php
/**
 * Admin menus
 *
 * @package
 */
namespace WPFunnels\Menu;

use WPFunnels\Admin\SetupWizard;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Menus
 *
 * @package Wpfnl
 */
class Wpfnl_Menus
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_plugin_menus']);
        add_filter('admin_head', [$this, 'remove_submenu'], 10, 2);
        add_filter('admin_head', [$this, 'remove_notices_from_funnel_window'], 10, 2);
        add_action('admin_init', [$this, 'disallow_all_step_view']);
        add_action('admin_footer', [$this, 'doc_link_with_new_page']);
        add_filter('wpfnl_dashboard_nav_lists', [$this, 'add_store_checkout_nav'], 15, 1);
        add_action('admin_bar_menu', [$this, 'register_admin_bar_menu'], 100);

        if( isset($_GET['page']) && 'edit_funnel' === $_GET['page'] ) {
			$funnel_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
			$funnel_type = $funnel_id ? get_post_meta($funnel_id, '_wpfnl_funnel_type', true) : '';
			if ( 'store_checkout' === $funnel_type ) {
				add_filter( 'parent_file', function( $parent_file ) {
					return WPFNL_MAIN_PAGE_SLUG;
				});
				add_filter( 'submenu_file', function( $submenu_file ) {
					return WPFNL_STORE_CHECKOUT_SLUG;
				});
			} else {
				add_filter( "admin_body_class", array($this, 'add_folded_menu_class') );
			}
		}
    }


    /**
     * Register plugin menus and submenus
     *
     * @since 1.0.0
     */
    public function register_plugin_menus()
    {
        $role_permission = Wpfnl_functions::get_general_settings();

        add_menu_page(
            'WPFunnels',
            'WPFunnels',
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_MAIN_PAGE_SLUG,
            '',
            WPFNL_DIR_URL . 'admin/assets/images/funnel.svg',
            6
        );


        // Register dashboard page for WPF
		add_submenu_page(
			WPFNL_MAIN_PAGE_SLUG,
			__('Dashboard', 'wpfnl'),
			__('Dashboard', 'wpfnl'),
			'wpf_manage_funnels',
			WPFNL_MAIN_PAGE_SLUG,
			[$this, 'render']
		);

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Funnels', 'wpfnl'),
            __('Funnels', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_FUNNEL_PAGE_SLUG,
            [$this, 'render_funnels_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Store Checkout', 'wpfnl'),
            __('Store Checkout', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_STORE_CHECKOUT_SLUG,
            [$this, 'render_store_checkout_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Automations', 'wpfnl'),
            __('Automations', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_AUTOMATIONS_SLUG,
            [$this, 'render_automations_page']
        );

        $integrations_in_pro = defined( 'WPFNL_PRO_VERSION' ) && version_compare( WPFNL_PRO_VERSION, '2.9.0', '>=' );
        $legacy_addon_active = is_plugin_active( 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php' );

        if ( $integrations_in_pro || ! $legacy_addon_active ) {
            // Pro owns the integrations menu; access is controlled by plan check inside the page renderer.
            add_submenu_page(
                WPFNL_MAIN_PAGE_SLUG,
                __('Integrations', 'wpfnl'),
                __('Integrations', 'wpfnl'),
                Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
                WPFNL_INTEGRATIONS_MAIN_PAGE_SLUG,
                [$this, 'render_integration_page']
            );
        } else {
            /**
             * After setup menu of WPFunnels.
             * Legacy fallback for users still running the old addon.
             *
             * @since 1.0.0
             */
            do_action( 'wpfunnels/after_setup_menu');
        }

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Templates', 'wpfnl'),
            __('Templates', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_TEMPLATE_PAGE_SLUG,
            [$this, 'render_funnel_template_page']
        );

        // add_submenu_page(
        //     WPFNL_MAIN_PAGE_SLUG,
        //     __('Add-ons', 'wpfnl'),
        //     __('Add-ons', 'wpfnl'),
		// 	Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
        //     WPFNL_ADDONS_SLUG,
        //     [$this, 'render_addons_page']
        // );


        // Show Integrations menu if WPFunnels Pro Integrations is deactivated
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Settings', 'wpfnl'),
            __('Settings', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_GLOBAL_SETTINGS_SLUG,
            [$this, 'render_settings_page']
        );

        // add_submenu_page(
        //     WPFNL_MAIN_PAGE_SLUG,
        //     __('Documentation', 'wpfnl'),
        //     '<span id="wpfnl-documentation">'. __('Documentation', 'wpfnl').'</span>',
		// 	Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
        //     'https://getwpfunnels.com/resources/'
        // );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Edit Funnel', 'wpfnl'),
            __('Edit Funnel', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_EDIT_FUNNEL_SLUG,
            [$this, 'render_edit_funnel_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Trash', 'wpfnl'),
            __('Trash', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_TRASH_FUNNEL_SLUG,
            [$this, 'render_trash_funnel_page']
        );

		add_submenu_page(
			WPFNL_MAIN_PAGE_SLUG,
			__('Request a Feature', 'wpfnl'),
			'<span id="wpfnl-request-feature">'. __('Request a Feature', 'wpfnl').'</span>',
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            'https://getwpfunnels.com/ideas/'
		);

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Email Builder', 'wpfnl'),
            __('Email Builder', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            'email-builder',
            [$this, 'render_email_builder_page']
        );

        // if ( !Wpfnl_functions::is_wpfnl_pro_activated() ) {

        //     add_submenu_page(
        //         WPFNL_MAIN_PAGE_SLUG,
        //         __('Free vs Pro', 'wpfnl'),
        //         __('Free vs Pro', 'wpfnl'),
        //         Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
        //         'wpf_feature_comparison',
        //         [$this, 'render_feature_comparison_page']
        //     );


        //     add_submenu_page(
		// 		WPFNL_MAIN_PAGE_SLUG,
		// 		__('Go Pro', 'wpfnl'),
		// 		'<span id="wpfnl-go-pro" class="dashicons dashicons-star-filled" style="font-size: 17px; color:#1fb3fb;"></span> ' . __('Go Pro', 'wpfnl'),
		// 		Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
		// 		'https://getwpfunnels.com/pricing/'
		// 	);
        // }
    }


	/**
	 * Render the admin view of WPFunnels
	 *
	 * @since 3.1.7
	 */
	public function render() {
		include_once WPFNL_ADMIN_DIR . 'views/base.php';
	}



	public function render_email_builder_page() { ?>
        <div id="email-builder"></div>
    <?php }



    /**
     * Render funnel page
     *
     * @since 1.0.0
     */
    public function render_funnels_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('funnels')->get_view();
    }


    /**
     * Render funnel template page
     *
     * @since 1.0.0
     */
    public function render_funnel_template_page()
    {
        require WPFNL_DIR . '/admin/partials/templates.php';
    }


    /**
     * Render feature comparison page
     *
     * @return void
     * @since 3.4.13
     */
    public function render_feature_comparison_page() {
        require WPFNL_DIR . '/admin/partials/feature-comparison.php';
    }


    /**
     * Render edit funnel page.
     *
     * @since 1.0.0
     */
    public function render_edit_funnel_page()
    {
        $funnel_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
        Wpfnl::$instance->module_manager->get_admin_modules('funnel')->init($funnel_id);
        Wpfnl::$instance->module_manager->get_admin_modules('funnel')->get_view();
    }


    /**
     * Render trash funnel page.
     *
     * @since 3.1.8
     */
    public function render_trash_funnel_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('funnels')->get_view();
    }

    /**
     * Render create funnel page
     *
     * @since 1.0.0
     */
    public function render_create_funnel_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('create-funnel')->get_view();
    }


    /**
     * Render settings page
     *
     * @since 1.0.0
     */
    public function render_settings_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('settings')->get_view();
    }

    public function render_automations_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('automations')->get_view();
    }


    /**
     * Render addons page
     *
     * @since 1.0.0
     */
    public function render_addons_page()
    {
        require_once WPFNL_DIR . 'admin/modules/addons/view.php';
    }


    /**
     * Render Store Checkout page
     *
     * Since 3.6.0 multiple store checkouts are allowed, so this page always
     * shows the listing rather than redirecting to a single canvas.
     *
     * @since 3.5.0
     */
    public function render_store_checkout_page()
    {
        require WPFNL_DIR . '/admin/modules/store-checkout/views/store-checkout-listing.php';
    }

    /**
     * Render Integration page
     *
     * @since 1.0.0
     */
    public function render_integration_page()
    {
        // Show Integrations page only if WPFunnels Pro Integrations is deactivated
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( has_action( 'wpfunnels/render_integrations_page' ) ) {
            // Pro plugin has registered its renderer — delegate (plan gate applied inside).
            do_action( 'wpfunnels/render_integrations_page' );
        } elseif ( ! is_plugin_active( 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php' ) ) {
            require_once WPFNL_DIR . 'admin/modules/integration/view.php';
        }
    }

    /**
     * Get Store Checkout funnel if exists
     *
     * @return WP_Post|false
     * @since 3.5.0
     */
    private function get_store_checkout_funnel()
    {
        $args = [
            'post_type'      => WPFNL_FUNNELS_POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => ['publish', 'draft'],
            'meta_query'     => [
                [
                    'key'   => '_wpfnl_funnel_type',
                    'value' => 'store_checkout',
                ],
            ],
        ];

        $funnels = get_posts($args);
        return !empty($funnels) ? $funnels[0] : false;
    }




	/**
	 * Render license page for funnel
	 *
	 * @since 2.0.0
	 */
	public function render_license() {
		require WPFNL_DIR . '/admin/partials/license.php';
	}

    /**
     * Remove submenu from plugin menu
     *
     * @since 1.0.0
     */
    public function remove_submenu()
    {
        remove_submenu_page(WPFNL_MAIN_PAGE_SLUG, 'edit_funnel');
        remove_submenu_page(WPFNL_MAIN_PAGE_SLUG, 'trash_funnels');
    }


    /**
     * Remove all notices from funnel window
     *
     * @since 2.0.0
     */
    public function remove_notices_from_funnel_window() {
    	if (empty($_GET['page'])) {
    		return;
		}
        if (('edit_funnel' == sanitize_text_field( $_GET['page'] ) )) {
            remove_all_actions( 'admin_notices' );
        }
		if ( 'wp_funnels' == sanitize_text_field( $_GET['page'] ) ||  'wpf_templates' == sanitize_text_field( $_GET['page'] ) ||  'trash_funnels' == sanitize_text_field( $_GET['page'] ) || 'wpfnl_settings' == sanitize_text_field( $_GET['page'] ) || 'wpf-license' == sanitize_text_field( $_GET['page'] )  ) {
			add_action('admin_footer', array( $this, 'remove_admin_notices' ));
		}

    }



    /**
     * Force user to visit all steps page
     *
     * @since 1.0.0
     */
    public function disallow_all_step_view()
    {
        global $pagenow;
        if ('edit.php' === $pagenow && isset($_GET['post_type']) && WPFNL_STEPS_POST_TYPE === sanitize_text_field($_GET['post_type'])) {
            $funnel_link = add_query_arg(
                [
                    'page' => WPFNL_MAIN_PAGE_SLUG,
                ],
                admin_url('admin.php')
            );
            wp_safe_redirect(  wp_sanitize_redirect( esc_url_raw( $funnel_link ) ) );
            exit;
        }
    }

    /**
     * Redirect user to pro url
     */
    public function redirect_to_pro()
    {
        $url = 'https://getwpfunnels.com/pricing/';
        wp_safe_redirect(  wp_sanitize_redirect( esc_url_raw( $url ) ) );
        exit();
    }


    /**
     * Open with new page when documenation is clicked
     */
    public function doc_link_with_new_page(){
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#wpfnl-documentation').parent().attr('target','_blank');
                $('#wpfnl-request-feature').parent().attr('target','_blank');
                $('#wpfnl-go-pro').parent().attr('target','_blank');
            });
        </script>
        <?php
    }


    public function add_folded_menu_class($classes) {
		return $classes." folded";
	}


	/**
	 * Remove admin notices
	 */
	public function remove_admin_notices() {
		echo '<style>.update-nag, .updated, .error, .is-dismissible, .notice { display: none; } .wpfnl-import-notice {display: block!important; margin: 15px 15px 15px 0px;} .wpfunnels-notice {display: block; margin-left: 0px; margin-right: 25px;}</style>';
	}


	/**
	 * Add Store Checkout to dashboard navigation
	 *
	 * @param array $list Navigation list
	 * @return array Modified navigation list
	 * @since 3.5.0
	 */
	public function add_store_checkout_nav( $list ) {
		if ( ! is_array( $list ) ) {
			return $list;
		}

		// Insert Store Checkout before settings
		$new_list = array();

		foreach ( $list as $key => $item ) {
			// Insert Store Checkout before settings
			if ( $key === 'settings' ) {
				$new_list['store_checkout'] = array(
					'title' => __('Store Checkout', 'wpfnl'),
					'icon'  => 'storewide-checkout-icon',
					'page'  => 'store_checkout',
				);
			}

			$new_list[$key] = $item;
		}

		return $new_list;
	}


	/**
	 * Register WPFunnels nodes in the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @since 3.10.9
	 */
	public function register_admin_bar_menu( $wp_admin_bar ) {
		$role_permission = Wpfnl_functions::get_general_settings();
		$capability      = Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission );

		if ( ! current_user_can( $capability ) ) {
			return;
		}

		$wp_admin_bar->add_node([
			'id'    => 'wpfunnels',
			'title' => __('WPFunnels', 'wpfnl'),
			'href'  => admin_url('admin.php?page=' . WPFNL_MAIN_PAGE_SLUG),
		]);

		$subnodes = [
			[
				'id'    => 'wpfunnels-dashboard',
				'title' => __('Dashboard', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . WPFNL_MAIN_PAGE_SLUG),
			],
			[
				'id'    => 'wpfunnels-funnels',
				'title' => __('Funnels', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . WPFNL_FUNNEL_PAGE_SLUG),
			],
			[
				'id'    => 'wpfunnels-store-checkout',
				'title' => __('Store Checkout', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . WPFNL_STORE_CHECKOUT_SLUG),
			],
			[
				'id'    => 'wpfunnels-automations',
				'title' => __('Automations', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . WPFNL_AUTOMATIONS_SLUG),
			],
		];

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$integrations_in_pro = defined('WPFNL_PRO_VERSION') && version_compare(WPFNL_PRO_VERSION, '2.9.0', '>=');
		$legacy_addon_active = is_plugin_active('wpfunnels-pro-integrations/wpfunnels-pro-integrations.php');

		if ( $integrations_in_pro || ! $legacy_addon_active ) {
			$subnodes[] = [
				'id'    => 'wpfunnels-integrations',
				'title' => __('Integrations', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . WPFNL_INTEGRATIONS_MAIN_PAGE_SLUG),
			];
		}

		$subnodes[] = [
			'id'    => 'wpfunnels-templates',
			'title' => __('Templates', 'wpfnl'),
			'href'  => admin_url('admin.php?page=' . WPFNL_TEMPLATE_PAGE_SLUG),
		];

		$subnodes[] = [
			'id'    => 'wpfunnels-settings',
			'title' => __('Settings', 'wpfnl'),
			'href'  => admin_url('admin.php?page=' . WPFNL_GLOBAL_SETTINGS_SLUG),
		];

		foreach ( $subnodes as $node ) {
			$wp_admin_bar->add_node( array_merge( $node, ['parent' => 'wpfunnels'] ) );
		}
	}
}

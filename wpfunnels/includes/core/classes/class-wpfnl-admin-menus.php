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
        add_action('in_admin_header', [$this, 'remove_notices_from_react_admin']);
        add_action('admin_init', [$this, 'disallow_all_step_view']);
        add_action('admin_init', [$this, 'maybe_redirect_to_react_admin']);
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
					return self::react_admin_slug( WPFNL_STORE_CHECKOUT_SLUG );
				});
			} else {
				add_filter( "admin_body_class", array($this, 'add_folded_menu_class') );
			}
		}
    }


    /**
     * Screens that live as routes of the React single-page admin.
     *
     * Keyed by the WordPress page slug each screen had before the move; the
     * value is the hash route that replaced it. Three things read this: the
     * submenu items, so a click only changes the fragment; the hidden
     * registrations of the old pages; and maybe_redirect_to_react_admin(),
     * which sends those old pages to the same routes.
     *
     * @return array Legacy page slug => hash route.
     * @since  3.13.7
     */
    public static function react_routes()
    {
        $routes = [
            WPFNL_FUNNEL_PAGE_SLUG    => '#/funnels',
            WPFNL_TRASH_FUNNEL_SLUG   => '#/funnels/trash',
            WPFNL_STORE_CHECKOUT_SLUG => '#/store-checkout',
            WPFNL_AUTOMATIONS_SLUG    => '#/automation',
            WPFNL_TEMPLATE_PAGE_SLUG  => '#/funnel-templates',
            WPFNL_AI_FUNNEL_PAGE_SLUG => '#/ai-assistant',
            WPFNL_GLOBAL_SETTINGS_SLUG => '#/settings',
        ];

        /*
         * Store Checkout takes over the WooCommerce checkout, so without
         * WooCommerce the screen has nothing to list and its route has nothing
         * to show. That case keeps its server-rendered view, which offers to
         * install WooCommerce — see render_store_checkout_page().
         */
        if ( ! Wpfnl_functions::is_wc_active() ) {
            unset( $routes[ WPFNL_STORE_CHECKOUT_SLUG ] );
        }

        return $routes;
    }


    /**
     * Menu slug that opens a screen without leaving the React admin.
     *
     * The app is one WordPress page with the route in the fragment, so a link
     * of the form `wpfunnels#/funnels` moves between screens without a page
     * load. A screen with no route — Store Checkout on a site without
     * WooCommerce — keeps its own page slug.
     *
     * @param string $page Legacy page slug of the screen.
     * @return string Slug to register the menu item under.
     * @since  3.13.7
     */
    public static function react_admin_slug( $page )
    {
        $routes = self::react_routes();

        return isset( $routes[ $page ] ) ? WPFNL_MAIN_PAGE_SLUG . $routes[ $page ] : $page;
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


        // Every screen below is a route of the React single-page admin, which
        // WordPress serves as one page: `?page=wpfunnels`, with the route in
        // the fragment. The submenu items carry that fragment in their slugs,
        // so moving between them only changes the hash and the browser never
        // reloads the admin — the same way the app's own top navigation
        // behaves. The pages these screens used to be are registered after
        // them, hidden from the menu, so every old link still resolves.
		add_submenu_page(
			WPFNL_MAIN_PAGE_SLUG,
			__('Dashboard', 'wpfnl'),
			__('Dashboard', 'wpfnl'),
			'wpf_manage_funnels',
			WPFNL_MAIN_PAGE_SLUG,
			[$this, 'render']
		);

        // Alias the React admin was first built under. Kept registered so links
        // and bookmarks from that period still resolve, but hidden from the menu
        // by remove_submenu() — the dashboard entry above is the real one.
        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Dashboard', 'wpfnl'),
            __('Dashboard', 'wpfnl'),
            'wpf_manage_funnels',
            WPFNL_REACT_ADMIN_SLUG,
            [$this, 'render']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Funnels', 'wpfnl'),
            __('Funnels', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            self::react_admin_slug( WPFNL_FUNNEL_PAGE_SLUG ),
            [$this, 'render_funnels_page']
        );

        // Without WooCommerce this one has no route to open: react_admin_slug()
        // hands back the page slug and the server-rendered view stays, which is
        // what offers to install WooCommerce.
        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Store Checkout', 'wpfnl'),
            __('Store Checkout', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            self::react_admin_slug( WPFNL_STORE_CHECKOUT_SLUG ),
            [$this, 'render_store_checkout_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Automations', 'wpfnl'),
            __('Automations', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            self::react_admin_slug( WPFNL_AUTOMATIONS_SLUG ),
            [$this, 'render_automations_page']
        );

        // Asked below and by render_integration_page(); admin_menu runs before
        // WordPress loads the plugin API on its own.
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

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
            self::react_admin_slug( WPFNL_TEMPLATE_PAGE_SLUG ),
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

        // Settings is a route of the React admin (`#/settings`); the page it
        // used to be is registered below, hidden, and redirected to that route.
        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Settings', 'wpfnl'),
            __('Settings', 'wpfnl'),
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            self::react_admin_slug( WPFNL_GLOBAL_SETTINGS_SLUG ),
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('AI Assistant', 'wpfnl'),
            __('AI Assistant', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            self::react_admin_slug( WPFNL_AI_FUNNEL_PAGE_SLUG ),
            [$this, 'render_ai_funnel_page']
        );

        /*
         * The pages those screens used to be, each still answering on its own
         * slug so bookmarks, the admin bar and anything built from the slugs
         * keep working — admin_init sends them on to the matching route. They
         * are dropped from the menu by remove_submenu(); the items above are
         * what people click, and those never leave the page.
         */
        $legacy_renderers = [
            WPFNL_FUNNEL_PAGE_SLUG    => 'render_funnels_page',
            WPFNL_TRASH_FUNNEL_SLUG   => 'render_trash_funnel_page',
            WPFNL_STORE_CHECKOUT_SLUG => 'render_store_checkout_page',
            WPFNL_AUTOMATIONS_SLUG    => 'render_automations_page',
            WPFNL_TEMPLATE_PAGE_SLUG  => 'render_funnel_template_page',
            WPFNL_AI_FUNNEL_PAGE_SLUG => 'render_ai_funnel_page',
            WPFNL_GLOBAL_SETTINGS_SLUG => 'render_settings_page',
        ];

        foreach ( array_keys( self::react_routes() ) as $legacy_slug ) {
            add_submenu_page(
                WPFNL_MAIN_PAGE_SLUG,
                __('WPFunnels', 'wpfnl'),
                __('WPFunnels', 'wpfnl'),
                Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
                $legacy_slug,
                [$this, $legacy_renderers[ $legacy_slug ]]
            );
        }

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
		include_once WPFNL_ADMIN_DIR . 'views/react-admin.php';
	}


	/**
	 * Send the screens that moved into the React admin to their routes.
	 *
	 * Each screen in react_routes() was its own WordPress page with a
	 * server-rendered view. They are routes of the single-page admin now (see
	 * src/admin/pages), so the pages are kept registered — bookmarks and every
	 * link built from the slugs still resolve — and each one hands over to the
	 * route that replaced it.
	 *
	 * The menu items no longer come through here: they carry their route in the
	 * fragment of their own slug (see react_admin_slug), so clicking one moves
	 * between screens without a page load at all. This is the path for links
	 * made before the move, which still name the old page.
	 *
	 * @return void
	 * @since  3.13.5
	 */
	public function maybe_redirect_to_react_admin() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$routes = self::react_routes();

		if ( ! isset( $routes[ $page ] ) ) {
			return;
		}

		/**
		 * Filters whether a legacy screen redirects to its React admin route.
		 *
		 * @param bool   $redirect Whether to redirect. Default true.
		 * @param string $page     Page slug being visited.
		 *
		 * @since 3.13.5
		 */
		if ( ! apply_filters( 'wpfunnels/redirect_to_react_admin', true, $page ) ) {
			return;
		}

		$url = admin_url( 'admin.php?page=' . WPFNL_MAIN_PAGE_SLUG ) . $routes[ $page ];

		// A search carried over from the legacy funnel list keeps working: the
		// React list reads it out of the hash route's query string.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $search ) {
			$url .= '?s=' . rawurlencode( $search );
		}

		/*
		 * The AI Assistant's own parameters travel the same way. `conversation_id`
		 * is the deep link into one thread; `wpfnl_prompt` (with an optional
		 * `wpfnl_context`) is the hand-off from a prompt typed elsewhere in the
		 * admin, which the page sends on arrival. Every link to the old page
		 * carries them in the query string, so they have to come across into the
		 * hash route's own query string, where the router can read them.
		 */
		if ( WPFNL_AI_FUNNEL_PAGE_SLUG === $page ) {
			$carried = [];

			$conversation_id = isset( $_GET['conversation_id'] ) ? absint( wp_unslash( $_GET['conversation_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $conversation_id ) {
				$carried['conversation_id'] = $conversation_id;
			}

			$prompt = isset( $_GET['wpfnl_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_GET['wpfnl_prompt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $prompt ) {
				$carried['wpfnl_prompt'] = $prompt;

				$context = isset( $_GET['wpfnl_context'] ) ? sanitize_key( wp_unslash( $_GET['wpfnl_context'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( $context ) {
					$carried['wpfnl_context'] = $context;
				}
			}

			if ( $carried ) {
				$url .= '?' . http_build_query( $carried );
			}
		}

		if ( wp_safe_redirect( $url ) ) {
			exit;
		}
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
        $this->render();
    }


    /**
     * Render AI funnel page.
     *
     * @since 3.10.0
     */
    public function render_ai_funnel_page()
    {
        $this->render();
    }


    /**
     * Render funnel template page
     *
     * @since 1.0.0
     */
    public function render_funnel_template_page()
    {
        $this->render();
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
        $this->render();
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
     * shows the listing rather than redirecting to a single canvas. Since
     * 3.13.6 that listing is the React route `#/store-checkout`.
     *
     * @since 3.5.0
     */
    public function render_store_checkout_page()
    {
        // Store Checkout takes over the WooCommerce checkout, so the screen is
        // meaningless without WooCommerce. Offer to install/activate it instead
        // of rendering an empty listing.
        if ( ! Wpfnl_functions::is_wc_active() ) {
            require WPFNL_DIR . '/admin/modules/store-checkout/views/woocommerce-required.php';
            return;
        }

        // The listing is a route of the React admin now (`#/store-checkout`),
        // which maybe_redirect_to_react_admin() sends this page to. Rendering
        // the app here keeps the page working if that redirect is filtered off.
        $this->render();
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
        // The React admin's legacy alias; it renders the same dashboard as the
        // entry above it, so only one of the two belongs in the menu.
        remove_submenu_page(WPFNL_MAIN_PAGE_SLUG, WPFNL_REACT_ADMIN_SLUG);

        /*
         * The pages the React screens used to be. They stay registered — the
         * callbacks still answer, so old links resolve — but the menu items
         * people click are the ones registered alongside them with the route
         * in the fragment, which never reload the page.
         */
        foreach ( array_keys( self::react_routes() ) as $legacy_slug ) {
            remove_submenu_page( WPFNL_MAIN_PAGE_SLUG, $legacy_slug );
        }
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
        if ( 'wp_funnels' == sanitize_text_field( $_GET['page'] ) ||  'wpf_templates' == sanitize_text_field( $_GET['page'] ) ||  'trash_funnels' == sanitize_text_field( $_GET['page'] ) || 'wpfnl_settings' == sanitize_text_field( $_GET['page'] ) || 'wpf-license' == sanitize_text_field( $_GET['page'] ) || WPFNL_AI_FUNNEL_PAGE_SLUG == sanitize_text_field( $_GET['page'] ) ) {
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
	 * Drop every admin notice on the React admin screen.
	 *
	 * The React admin is a single-page app that owns the whole content column,
	 * so a notice printed above it by WordPress or by an unrelated plugin (the
	 * Stripe "Apple Pay" warning, update nags, review prompts) pushes the app
	 * down and breaks the layout. Unhooking the notice actions stops them from
	 * being rendered at all rather than hiding them after the fact; anything a
	 * plugin injects later from JavaScript is caught by the CSS fallback in
	 * src/scss/style.scss.
	 *
	 * Runs on `in_admin_header`, the last hook to fire before WordPress renders
	 * the notices, so a plugin that registers one late is still caught.
	 *
	 * @return void
	 * @since 3.13.2
	 */
	public function remove_notices_from_react_admin() {
		$page = empty( $_GET['page'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['page'] ) );

		if ( ! in_array( $page, array( WPFNL_MAIN_PAGE_SLUG, WPFNL_REACT_ADMIN_SLUG ), true ) ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
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
				'href'  => admin_url('admin.php?page=' . self::react_admin_slug( WPFNL_FUNNEL_PAGE_SLUG )),
			],
			[
				'id'    => 'wpfunnels-store-checkout',
				'title' => __('Store Checkout', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . self::react_admin_slug( WPFNL_STORE_CHECKOUT_SLUG )),
			],
			[
				'id'    => 'wpfunnels-automations',
				'title' => __('Automations', 'wpfnl'),
				'href'  => admin_url('admin.php?page=' . self::react_admin_slug( WPFNL_AUTOMATIONS_SLUG )),
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
			'href'  => admin_url('admin.php?page=' . WPFNL_MAIN_PAGE_SLUG) . '#/funnel-templates',
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

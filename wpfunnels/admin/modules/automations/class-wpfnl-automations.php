<?php
/**
 * Automations module
 *
 * @package
 */

namespace WPFunnels\Modules\Admin\Automations;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;

    public function __construct()
    {
        // Enqueue automation-canvas scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_automation_scripts']);
        
        // Initialize REST API
        require_once WPFNL_DIR . '/admin/modules/automations/class-wpfnl-automation-rest-api.php';
    }

    /**
     * Enqueue automation canvas scripts
     *
     * @param string $hook Current admin page hook
     * @since 3.9.5
     */
    public function enqueue_automation_scripts($hook)
    {
        // Only load on automation page
        if ('wpfunnels_page_wpfnl_automations' !== $hook) {
            return;
        }

        // Enqueue wp-tinymce for WYSIWYG editor support
        wp_tinymce_inline_scripts();
        wp_enqueue_editor();

        // Load automation-canvas script
        $automation_asset_file = WPFNL_DIR . '/admin/assets/dist/js/automation-canvas.min.asset.php';
        $automation_dependencies = ['wp-element', 'wp-i18n', 'wp-api-fetch', 'wp-data'];
        $automation_version = WPFNL_VERSION;

        if (file_exists($automation_asset_file)) {
            $automation_asset = require $automation_asset_file;
            $automation_dependencies = isset($automation_asset['dependencies']) ? $automation_asset['dependencies'] : $automation_dependencies;
            $automation_version = isset($automation_asset['version']) ? $automation_asset['version'] : $automation_version;
        }

        // The automation-canvas bundle ships its own copy of lodash (pulled in by
        // easy-email's `import { ... } from "lodash"`). Lodash's UMD wrapper self-registers
        // as the global `_`, clobbering WP core's Underscore.js that media-views.js relies
        // on for `_.each(list, iteratee, context)` context-binding — breaking the media
        // modal ("this.activateMode is not a function") once this script has run.
        // Force load-order against 'underscore' and snapshot/restore the real global
        // around it rather than reworking the bundled easy-email/lodash import graph.
        $automation_dependencies[] = 'underscore';
        $automation_dependencies   = array_unique($automation_dependencies);

        wp_add_inline_script(
            'underscore',
            'window.__wpfnlCoreUnderscore = window.__wpfnlCoreUnderscore || window._;',
            'after'
        );

        wp_enqueue_script(
            'wpfnl-automation-canvas',
            WPFNL_URL . 'admin/assets/dist/js/automation-canvas.min.js',
            $automation_dependencies,
            $automation_version,
            true
        );

        wp_add_inline_script(
            'wpfnl-automation-canvas',
            'if (window.__wpfnlCoreUnderscore) { window._ = window.__wpfnlCoreUnderscore; }',
            'after'
        );

        // Enqueue automation-canvas styles extracted by a production build.
        Wpfnl_functions::enqueue_bundle_style( 'automation-canvas', 'wpfnl-automation-canvas-styles' );

        // The automation editor's own full-screen overlay (.wpfnl-automation-modal, see
        // admin/src/automation-canvas/AutomationModal.scss) sits at z-index: 999999,
        // higher than WP core's default .media-modal z-index — pushing the media
        // library modal behind the editor chrome. Force it back on top.
        wp_add_inline_style(
            'media-views',
            '.media-modal { z-index: 9999999999 !important; } .media-modal-backdrop { z-index: 999999998 !important; }'
        );

        // Localize script with data
        wp_localize_script(
            'wpfnl-automation-canvas',
            'wpfnlAutomationVars',
            [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('wpfnl_automation_nonce'),
                'restUrl'   => rest_url(),
                'restNonce' => wp_create_nonce('wp_rest'),
            ]
        );

        // Provide MRM_Vars global that automation-canvas components depend on.
        $current_user       = wp_get_current_user();
        $email_settings     = get_option( '_mrm_email_settings', array() );
        $is_pro_active      = defined( 'MAIL_MINT_PRO_VERSION' );
        $is_pro_lic_active  = $is_pro_active && class_exists( 'MRM\\Common\\MrmCommon' ) && \MRM\Common\MrmCommon::is_mailmint_pro_license_active();
        $mint_trans         = class_exists( 'Mint\\MRM\\Utilities\\Helper\\TranslationString\\TransStrings' )
                                ? \Mint\MRM\Utilities\Helper\TranslationString\TransStrings::getStrings()
                                : array();
        $lists              = class_exists( 'Mint\\MRM\\DataBase\\Models\\ContactGroupModel' )
                                ? \Mint\MRM\DataBase\Models\ContactGroupModel::get_all_to_custom_select( 'lists' )
                                : array();
        $tags               = class_exists( 'Mint\\MRM\\DataBase\\Models\\ContactGroupModel' )
                                ? \Mint\MRM\DataBase\Models\ContactGroupModel::get_all_to_custom_select( 'tags' )
                                : array();

        wp_localize_script(
            'wpfnl-automation-canvas',
            'MRM_Vars',
            array(
                'ajaxurl'                        => admin_url( 'admin-ajax.php' ),
                'api_base_url'                   => get_rest_url(),
                'nonce'                          => wp_create_nonce( 'wp_rest' ),
                'current_userID'                 => get_current_user_id(),
                'admin_url'                      => get_admin_url(),
                'date_format'                    => get_option( 'date_format', 'F j, Y' ),
                'time_format'                    => get_option( 'time_format', 'H:i' ),
                'gmt_offset'                     => get_option( 'gmt_offset' ),
                'local_time'                     => date_i18n( 'Y-m-d H:i:s' ),
                'current_user_email'             => $current_user->user_email,
                'email_settings'                 => $email_settings,
                'lists'                          => $lists,
                'tags'                           => $tags,
                'is_wc_active'                   => class_exists( 'WooCommerce' ),
                'is_edd_active'                  => defined( 'EDD_VERSION' ),
                'is_mailmint_pro_active'         => $is_pro_active,
                'is_mailmint_pro_license_active' => $is_pro_lic_active,
                'is_tutor_active'                => defined( 'TUTOR_VERSION' ),
                'is_gform_active'                => class_exists( 'GFCommon' ),
                'is_jetform_active'              => class_exists( 'Jet_Form_Builder\\Plugin' ),
                'is_fluentform_active'           => defined( 'FLUENTFORM' ),
                'is_learndash_active'            => defined( 'LEARNDASH_VERSION' ),
                'is_memberpress_active'          => defined( 'MEPR_VERSION' ),
                'is_contact_form_active'         => defined( 'WPCF7_VERSION' ),
                'is_lifterlms_active'            => defined( 'LLMS_VERSION' ),
                'is_fluent_booking_active'       => defined( 'FLUENT_BOOKING_VERSION' ),
                'is_wp_form_active'              => class_exists( 'WPForms' ),
                'is_bricks_active'               => defined( 'BRICKS_VERSION' ),
                'is_wcs_active'                  => class_exists( 'WC_Subscriptions' ),
                'is_wcm_active'                  => class_exists( 'WC_Memberships' ),
                'is_wcw_active'                  => class_exists( 'TInvWishlist' ),
                'mint_trans'                     => $mint_trans,
                'mint_page'                      => 'automation',
                'open_ai_key'                    => array(),
                'twilio_settings'                => array(),
                'post_types'                     => array(),
                'contact_general_fields'         => array(),
                'contact_custom_fields'          => array(),
                'editor_data_source'             => $this->get_editor_data_source(),
            )
        );
    }

    /**
     * Provide the product/post category options the email builder's custom
     * block panels (e.g. ProductBlock) read from MRM_Vars.editor_data_source.
     * Without this key present, `options={undefined}` is passed to a Select
     * field, which throws "Cannot read properties of undefined (reading 'map')"
     * the moment that panel renders.
     *
     * @return array
     * @since 3.12.10
     */
    private function get_editor_data_source()
    {
        return array(
            'product_categories' => $this->get_formatted_categories( 'product_cat' ),
            'post_categories'    => $this->get_formatted_categories( 'category' ),
            'placeholder_image'  => '',
        );
    }

    /**
     * Fetch terms for a taxonomy formatted as {value, label} pairs.
     *
     * @param string $taxonomy Taxonomy name.
     * @return array
     * @since 3.12.10
     */
    private function get_formatted_categories( $taxonomy )
    {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }

        $terms = get_categories(
            array(
                'taxonomy'   => $taxonomy,
                'orderby'    => 'name',
                'hide_empty' => false,
            )
        );

        $formatted = array();
        foreach ( $terms as $term ) {
            $formatted[] = array(
                'value' => $term->term_id,
                'label' => $term->name,
            );
        }

        return $formatted;
    }

    public function get_view()
    {
        require_once WPFNL_DIR . '/admin/modules/automations/views/view.php';
    }

    public function get_name()
    {
        return __('automations','wpfnl');
    }

    public function init_ajax()
    {

    }
}


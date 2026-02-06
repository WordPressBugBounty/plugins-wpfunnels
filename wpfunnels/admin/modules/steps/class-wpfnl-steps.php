<?php
/**
 * Step module
 * 
 * @package
 */
namespace WPFunnels\Admin\Modules\Steps;

use WPFunnels\Admin\Module\Steps\Wpfnl_Steps_Factory;
use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
class Module extends Wpfnl_Admin_Module
{
    protected $steps = [];

    protected $id;

    protected $type;

    protected $step;

    public $step_title;

    public function __construct()
    {
        $this->steps = $this->get_steps();
    }

    /**
     * Get step
     * 
     * @return Obj
     */
    public function get_step($name)
    {
        $this->step = Wpfnl_Steps_Factory::build($name);
        return $this->step;
    }

    /**
     * Get all steps
     * 
     * @return Array
     * @since  2.0.4
     */
    public function get_steps()
    {
        return [
            'landing',
            'thankyou',
            'checkout',
            'upsell',
            'downsell',
        ];
    }


    /**
     * Initialize class
     * 
     * @return void
     * @since  2.0.4
     */
    public function init($id)
    {
        $this->set_id($id);
        $this->step = Wpfnl::get_instance()->step_store;
        $this->step->read($this->get_id());
        $this->set_type($this->step->get_type());
    }

    /**
     * Set step ID
     * 
     * @param Int $id
     * 
     * @return void
     * @since  2.0.4
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * Get step ID
     * 
     * @return Int
     * @since  2.0.4
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set step type
     * 
     * @param String $type
     * 
     * @return void
     * @since  2.0.4
     */
    public function set_type($type)
    {
        $this->type = $type;
    }


    /**
     * Get step type
     * 
     * @return String
     * @since  2.0.4
     */
    public function get_type()
    {
        return $this->type;
    }


    /**
     * Set internal meta fields for this step
     *
     * @since 1.0.0
     */
    public function set_internal_meta_value()
    {
        $meta_values = [];
        foreach ($this->_internal_keys as $key => $value) {
            $meta_value = $this->step->get_meta($this->get_id(), $key);
            $meta_values[$key] = $meta_value ? $meta_value : $value;
        }
        $this->_internal_keys = $meta_values;
    }


    /**
     * Get internal meta
     * 
     * @return Array
     * @since  2.0.4
     */
    public function get_internal_metas()
    {
        return $this->_internal_keys;
    }

    /**
     * Get meta value by key
     *
     * @param $key
     * 
     * @return mixed
     * @since  1.0.0
     */
    public function get_internal_metas_by_key($key)
    {
        if (isset($this->_internal_keys[$key])) {
            return $this->_internal_keys[$key];
        }

        return '';
    }

    /**
     * Get module name
     * 
     * @return String
     * @since  2.0.4
     */
    public function get_name()
    {
        return __('steps', 'wpfnl');
    }


    /**
     * Initialize ajax callback functions
     * 
     * @return void
     * @since  2.0.4
     */
    public function init_ajax()
    {
        wp_ajax_helper()->handle('step-edit')
            ->with_callback([ $this, 'step_edit' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('wpfnl-save-skip-offer-settings')
            ->with_callback([ $this, 'save_skip_offer_settings' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('wpfnl-get-skip-offer-settings')
            ->with_callback([ $this, 'get_skip_offer_settings' ])
            ->with_validation($this->get_validation_data());
    }

    /**
     * Edit step by ajax request
     *
     * @return array
     * @since  1.0.0
     */
    public function step_edit($payload)
    {
        $step_id = $payload['step_id'];
        $input = sanitize_text_field($payload['input']);

        $step_post = [
            'ID'           => $step_id,
            'post_title'   => $input,
        ];

        wp_update_post($step_post);
        return [
            'success' => true,
            'message' => "Step title updated",
        ];
    }

    /**
     * Save skip offer settings
     *
     * @param array $payload
     * @return array
     * @since  3.0.0
     */
    public function save_skip_offer_settings($payload)
    {
        $step_id = isset($payload['step_id']) ? absint($payload['step_id']) : 0;
        $funnel_id = isset($payload['funnel_id']) ? absint($payload['funnel_id']) : 0;

        if (!$step_id) {
            return [
                'success' => false,
                'message' => __('Invalid step ID', 'wpfnl'),
            ];
        }

        // Sanitize and save settings
        $skip_offer_enabled = isset($payload['skip_offer_enabled']) ? sanitize_text_field($payload['skip_offer_enabled']) : 'no';
        $skip_if_parent_product_exists = isset($payload['skip_if_parent_product_exists']) ? sanitize_text_field($payload['skip_if_parent_product_exists']) : 'no';
        $skip_if_ever_purchased = isset($payload['skip_if_ever_purchased']) ? sanitize_text_field($payload['skip_if_ever_purchased']) : 'no';

        // Update post meta
        update_post_meta($step_id, '_wpfnl_skip_offer_enabled', $skip_offer_enabled);
        update_post_meta($step_id, '_wpfnl_skip_if_parent_product_exists', $skip_if_parent_product_exists);
        update_post_meta($step_id, '_wpfnl_skip_if_ever_purchased', $skip_if_ever_purchased);

        return [
            'success' => true,
            'message' => __('Offer settings saved successfully', 'wpfnl'),
        ];
    }

    /**
     * Get skip offer settings
     *
     * @param array $payload
     * @return array
     * @since  3.0.0
     */
    public function get_skip_offer_settings($payload)
    {
        $step_id = isset($payload['step_id']) ? absint($payload['step_id']) : 0;

        if (!$step_id) {
            return [
                'success' => false,
                'message' => __('Invalid step ID', 'wpfnl'),
            ];
        }

        // Get settings from post meta
        $skip_offer_enabled = get_post_meta($step_id, '_wpfnl_skip_offer_enabled', true);
        $skip_if_parent_product_exists = get_post_meta($step_id, '_wpfnl_skip_if_parent_product_exists', true);
        $skip_if_ever_purchased = get_post_meta($step_id, '_wpfnl_skip_if_ever_purchased', true);

        // Set defaults if not set
        $skip_offer_enabled = $skip_offer_enabled ? $skip_offer_enabled : 'no';
        $skip_if_parent_product_exists = $skip_if_parent_product_exists ? $skip_if_parent_product_exists : 'no';
        $skip_if_ever_purchased = $skip_if_ever_purchased ? $skip_if_ever_purchased : 'no';

        return [
            'success' => true,
            'data' => [
                'skip_offer_enabled' => $skip_offer_enabled,
                'skip_if_parent_product_exists' => $skip_if_parent_product_exists,
                'skip_if_ever_purchased' => $skip_if_ever_purchased,
            ],
        ];
    }

    /**
     * Get view
     */
    public function get_view()
    {
    }
}


    
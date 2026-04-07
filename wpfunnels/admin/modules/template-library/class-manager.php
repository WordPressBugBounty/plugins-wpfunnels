<?php
/**
 * Template library manager
 *
 * @package
 */
namespace WPFunnels\TemplateLibrary;

use WPFunnels\Wpfnl;

class Manager
{

    /**
     * Register funnels sources
     *
     * @var array
     */
    protected $_registered_sources = [];

    /**
     * Ajax validation args
     *
     * @var Validation
     */
    protected $validations;


    /**
     * View of the template-library module
     *
     * @var View
     */
    protected $view;


    /**
     * Manager constructor.
     * Initializing the template library manger with
     * Ajax registering sources and ajax hooks
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->add_actions();
        $this->init_ajax();
        $this->register_default_sources();
    }


    /**
     * Initialize all ajax actions for template-library
     *
     * @since 1.0.0
     */
    public function init_ajax()
    {
        $this->validations = [
            'logged_in' => true,
            'user_can' => 'wpf_manage_funnels',
        ];
        wp_ajax_helper()->handle('wpfunnel-get-templates-data')
            ->with_callback([$this, 'get_funnel_templates'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnel-import-funnel')
            ->with_callback([$this, 'import_funnel'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnel-import-variation-step')
            ->with_callback([$this, 'import_variation_step'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnel-after-funnel-creation')
            ->with_callback([$this, 'after_funnel_creation'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnel-get-step-templates-data')
            ->with_callback([$this, 'get_step_templates'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnel-after-step-creation')
            ->with_callback([$this, 'after_step_creation'])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfunnels-activate-plugin')
            ->with_callback([$this, 'activate_plugin'])
            ->with_validation($this->validations);
    }


    /**
     * Add view for template-library
     *
     * @since 1.0.0
     */
    public function add_actions()
    {
        add_action('admin_footer', [$this, 'add_default_view']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook)
    {
        if (in_array($hook, ['wpfunnels_page_wp_funnels', 'wpfunnels_page_wpf_templates', 'wpfunnels_page_store_checkout'])) {
            $funnel_id = 0;
            if (isset($_GET['page'])) {
                if ($_GET['page'] === 'edit_funnel') {
                    if (isset($_GET['id'])) {
                        $funnel_id = sanitize_text_field($_GET['id']);
                    }
                }
            }
            wp_enqueue_script('funnel-template-library', WPFNL_URL . 'admin/assets/dist/js/template-library.min.js', ['jquery', 'wp-util'], WPFNL_VERSION, true);
        }
    }


    /**
     * Register template source for import/export
     *
     * @since 1.0.0
     */
    private function register_default_sources()
    {
        $sources = [
            'local',
            'remote',
        ];
        foreach ($sources as $source_filename) {
            $class_name = ucwords($source_filename);
            $class_name = str_replace('-', '_', $class_name);
            $this->register_source(__NAMESPACE__ . '\Wpfnl_Source_' . $class_name);
        }
    }


    /**
     * Register sources
     *
     * @param $source_class
     * @param array $args
     *
     * @return bool|\WP_Error
     * @since  1.0.0
     */
    public function register_source($source_class, $args = [])
    {
        if (!class_exists($source_class)) {
            return new \WP_Error('source_class_name_not_exists');
        }
        $source_instance = new $source_class($args);
        $source_id = $source_instance->get_source();

        if (isset($this->_registered_sources[$source_id])) {
            return new \WP_Error('source_exists');
        }
        $this->_registered_sources[$source_id] = $source_instance;
        return true;
    }


    /**
     * Get all registered sources
     *
     * @return array
     * @since  1.0.0
     */
    public function get_registered_sources()
    {
        return $this->_registered_sources;
    }


    /**
     * Get template source by source name.
     *
     * @param $id
     *
     * @return false|Wpfnl_Source_Base
     * @since  1.0.0
     */
    public function get_source($id)
    {
        $sources = $this->get_registered_sources();
        if (!isset($sources[$id])) {
            return false;
        }
        return $sources[$id];
    }


    /**
     * Get step templates
     *
     * @param $payload
     *
     * @return array
     */
    public function get_step_templates($payload)
    {
        $template_data = $this->get_source('remote')->get_funnels();
        
        // Filter for Store Checkout - only show checkout and thankyou templates
        if (isset($_GET['page']) && $_GET['page'] === 'store_checkout') {
            $template_data = $this->filter_store_checkout_templates($template_data);
        }
        
        return [
            'success' => true,
            'data' => $template_data,
        ];
    }

    /**
     * Get funnel templates for viewing
     *
     * @return array
     * @since  1.0.0
     */
    public function get_funnel_templates()
    {
        $template_data = $this->get_source('remote')->get_funnels();
        
        // Filter for Store Checkout - only show checkout and thankyou templates
        if (isset($_GET['page']) && $_GET['page'] === 'store_checkout') {
            $template_data = $this->filter_store_checkout_templates($template_data);
        }
        
        return [
            'success' => true,
            'data' => $template_data,
        ];
    }

    /**
     * Add default view for template library
     *
     * @since 1.0.0
     */
    public function add_default_view()
    {
        $default_view = [
            'admin/template-library/view/template-library.php',
        ];
        foreach ($default_view as $view) {
            $this->add_view(WPFNL_DIR . $view);
        }
        $this->print_template();
    }


    /**
     * Add view
     *
     * @param $view
     * @param string $type
     *
     * @since 1.0.0
     */
    public function add_view($view, $type = 'path')
    {
        if ('path' === $type) {
            ob_start();
            if (file_exists($view)) {
                include $view;
            }
            $this->view = ob_get_clean();
        }
    }

    /**
     * Print template
     *
     * @since 1.0.0
     */
    public function print_template()
    {
        echo $this->view;
    }


    /**
     * Import templates from
     * Remote server
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function import_funnel($payload)
    {
        // Check if Store Checkout flag is sent from frontend
        if (isset($payload['is_store_checkout']) && $payload['is_store_checkout']) {
            // Flag is already in payload
        } elseif (isset($_GET['page']) && $_GET['page'] === 'store_checkout') {
            // Fallback: check GET parameter
            $payload['is_store_checkout'] = true;
        }
        $source = $this->get_source($payload['source']);
        $result = $source->import_funnel($payload);

        if ( ! empty( $result['success'] ) && ! empty( $result['funnelID'] ) ) {
            do_action(
                'wpfunnels_template_imported',
                absint( $result['funnelID'] ),
                0,
                sanitize_text_field( $payload['source'] ?? '' )
            );
        }

        return $result;
    }

    /**
     * Import wp funnel steps
     * From remote servers
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function import_variation_step($payload)
    {
        $source = $this->get_source($payload['source']);
        return $source->import_variation_step($payload);
    }


    /**
     * After funnel import
     * Redirect to new funnel edit url
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function after_funnel_creation($payload)
    {
        // Check if Store Checkout flag is sent from frontend
        if (isset($payload['is_store_checkout']) && $payload['is_store_checkout']) {
            // Flag is already in payload
        } elseif (isset($_GET['page']) && $_GET['page'] === 'store_checkout') {
            // Fallback: check GET parameter
            $payload['is_store_checkout'] = true;
        } elseif (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'page=store_checkout') !== false) {
            // Fallback: check referer
            $payload['is_store_checkout'] = true;
        }
        
        $source = $this->get_source($payload['source']);
        return $source->after_funnel_creation($payload);
    }

    /**
     * After funnel import
     * Redirect to new funnel edit url
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function after_step_creation($payload)
    {
        $funnel_id = $payload['funnelID'];
        $step_id = $payload['stepID'];
        $step = get_post($step_id);
        $step_type = get_post_meta($step_id, '_step_type', true);
        $step_name = $step->post_title;
        $step_component = $step_type . $step_id;
        $node_id = 0;
        $step_edit_link = base64_encode(get_edit_post_link($step_id));
        $step_view_link = base64_encode(get_post_permalink($step_id));

        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        $funnel_data = $funnel_json;

        $node_data = $funnel_data['drawflow']['Home']['data'];

        foreach ($node_data as $node_key => $node_value) {
            $node_id = $node_value['id'] + 1;
        }

        $step_data = array(
            'step_id' => $step_id,
            'step_type' => $step_type,
            'step_edit_link' => $step_edit_link,
            'step_view_link' => $step_view_link,
        );
        $step_array = array(
            'id' => $node_id,
            'name' => $step_type,
            'data' => $step_data,
            'class' => $step_type,
            'html' => $step_component,
            'step_name' => $step_name,
            'typenode' => 'vue',
            'inputs' => $this->get_connector_input($step_type),
            'outputs' => $this->get_connector_output($step_type),
            'pos_x' => 100,
            'pos_y' => 100,
        );

        $funnel_data['drawflow']['Home']['data'][] = $step_array;

        $final_data = json_encode($funnel_data);

        $identifier_json = get_post_meta($funnel_id, 'funnel_identifier', true);
        $identifier_json = preg_replace('/\: *([0-9]+\.?[0-9e+\-]*)/', ':"\\1"', $identifier_json);
        $identifier = json_decode($identifier_json, true);
        $identifier[$node_id] = $step_id;
        $final_identifier = json_encode($identifier);

        update_post_meta($funnel_id, '_funnel_data', $funnel_data);
        update_post_meta($funnel_id, 'funnel_identifier', $final_identifier);

        $source = $this->get_source($payload['source']);
        return $source->after_step_creation($payload);
    }

    public function get_connector_input($type)
    {
        if ($type == 'landing') {
            $input = array();
        } elseif ($type == 'checkout') {
            $input = array(
                "input_1" => array(
                    "connections" => array(),
                ),
            );
        } else {
            $input = array(
                "input_1" => array(
                    "connections" => array(),
                ),
            );
        }
        return $input;
    }

    public function get_connector_output($type)
    {
        if ($type == 'landing') {
            $output = array(
                "output_1" => array(
                    "connections" => array(),
                ),
            );
        } elseif ($type == 'checkout') {
            $output = array(
                "output_1" => array(
                    "connections" => array(),
                ),
            );
        } else {
            $output = array();
        }
        return $output;
    }


    /**
     * Filter templates for Store Checkout - only checkout and thankyou
     *
     * @param array $template_data
     * @return array
     * @since 3.5.0
     */
    private function filter_store_checkout_templates($template_data)
    {
        if (empty($template_data) || !isset($template_data['steps'])) {
            return $template_data;
        }

        // Filter steps to only include one checkout and one thankyou
        $filtered_steps = [];
        $found_checkout = false;
        $found_thankyou = false;
        
        foreach ($template_data['steps'] as $step) {
            if (isset($step['step_type'])) {
                if ($step['step_type'] === 'checkout' && !$found_checkout) {
                    $filtered_steps[] = $step;
                    $found_checkout = true;
                } elseif ($step['step_type'] === 'thankyou' && !$found_thankyou) {
                    $filtered_steps[] = $step;
                    $found_thankyou = true;
                }
            }
        }

        // Filter templates to only include those with checkout and thankyou steps
        $filtered_templates = [];
        if (isset($template_data['templates'])) {
            foreach ($template_data['templates'] as $template) {
                $has_checkout = false;
                $has_thankyou = false;
                
                if (isset($template['steps'])) {
                    foreach ($template['steps'] as $step) {
                        if (isset($step['step_type'])) {
                            if ($step['step_type'] === 'checkout') {
                                $has_checkout = true;
                            } elseif ($step['step_type'] === 'thankyou') {
                                $has_thankyou = true;
                            }
                        }
                    }
                }
                
                // Only include templates that have both checkout and thankyou
                if ($has_checkout && $has_thankyou) {
                    // Filter the template's steps to only include one checkout and one thankyou
                    $filtered_template_steps = [];
                    $found_checkout = false;
                    $found_thankyou = false;
                    
                    foreach ($template['steps'] as $step) {
                        if (isset($step['step_type'])) {
                            if ($step['step_type'] === 'checkout' && !$found_checkout) {
                                $filtered_template_steps[] = $step;
                                $found_checkout = true;
                            } elseif ($step['step_type'] === 'thankyou' && !$found_thankyou) {
                                $filtered_template_steps[] = $step;
                                $found_thankyou = true;
                            }
                        }
                    }
                    
                    $template['steps'] = $filtered_template_steps;
                    $filtered_templates[] = $template;
                }
            }
        }

        $template_data['steps'] = $filtered_steps;
        $template_data['templates'] = $filtered_templates;

        return $template_data;
    }


    /**
     * Activate plugin with ajax call
     *
     * @param $payload
     *
     * @since 2.0.0
     */
    public function activate_plugin( $payload ) {
        if ( ! current_user_can( 'wpf_manage_funnels' ) ) {
            wp_send_json_error( array(
                'message'   => __('Sorry you are not allowed to do this operation', 'wpfnl'),
            ) );
        }
        \wp_clean_plugins_cache();
        $plugin_file = ( isset( $payload['pluginFile'] ) ) ? esc_attr( $payload['pluginFile'] ) : '';
        $activate = \activate_plugin( $plugin_file, '', false, true );

        if ( is_wp_error( $activate ) ) {
            wp_send_json_error(
                array(
                    'success' => false,
                    'message' => $activate->get_error_message(),
                )
            );
        }

        wp_send_json_success(
            array(
                'message'   => __('Plugin is installed successfully', 'wpfnl'),
            )
        );
    }
}

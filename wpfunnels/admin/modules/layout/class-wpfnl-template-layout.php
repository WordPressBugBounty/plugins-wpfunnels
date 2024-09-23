<?php 

namespace WPFunnels\Admin\Module\Layout;

use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;


/**
 * TemplateLayout class for managing the layout and steps of a funnel in WP Funnels.
 *
 * @since 3.2.1
 */
class TemplateLayout {

    /**
     * Funnel ID.
     *
     * @var int
     * @since 3.2.1
     */
    protected $funnel_id;

    /**
     * Funnel object.
     *
     * @var object
     * @since 3.2.1
     */
    protected $funnel;

    /**
     * Funnel type.
     *
     * @var string
     * @since 3.2.1
     */
    protected $funnel_type;


    /**
     * Array of steps.
     *
     * @var array
     * @since 3.2.1
     */
    protected $steps_array = [];

    /**
     * Funnel data.
     *
     * @var array
     * @since 3.2.1
     */
    protected $funnel_data = [
        'drawflow' => [
            'Home' => [
                'data' => [
                    
                ]
           
            ]
        ]
    ];

    /**
     * Constructor to initialize funnel properties.
     *
     * @param object $funnel Funnel object.
     * @param int $funnel_id Funnel ID.
     * @param string $funnel_type Funnel type.
     * @param array $steps_array Array of steps.
     *
     * @since 3.2.1
     */
    public function __construct($funnel, $funnel_id, $funnel_type, $steps_array = []) {
        $this->funnel = $funnel;
        $this->funnel_id = $funnel_id;
        $this->funnel_type = $funnel_type;
        $this->steps_array = $steps_array;
    }
    

    /**
     * Create steps for the funnel.
     *
     * @since 3.2.1
     */
    public function create_step() {
        $index = 1;
        $previous_index = null;
        if (!empty($this->steps_array)) {
            foreach ($this->steps_array as $single_step) {
                $step_index = $this->create_and_prepare_step($single_step, $index, $previous_index);
                $previous_index = $step_index;
                $index++;
            }
        }
    }
    

    /**
     * Create and prepare a step for the funnel.
     *
     * @param array $step_info Step information.
     * @param int $index Step index.
     * @param int|null $previous_index Previous step index.
     *
     * @return int Current step index.
     *
     * @since 3.2.1
     */
    private function create_and_prepare_step($step_info, $index, $previous_index) {
        $step = Wpfnl::get_instance()->step_store;
        $step_id = $step->create_step($this->funnel_id, $step_info['name'], $step_info['value']);
        $step->set_id($step_id);

        // Prepare step data
        $step_data = $this->prepare_step_data($step_id, $step_info, $index);
        
        if ($previous_index !== null) {
            $step_data['inputs']['input_1']['connections'][] = [
                'node' => $previous_index,
                'input' => 'output_1'
            ];
        }

        $this->funnel_data['drawflow']['Home']['data'][$index] = $step_data;

        if ($previous_index !== null) {
            $this->funnel_data['drawflow']['Home']['data'][$previous_index]['outputs']['output_1']['connections'][] = [
                'node' => $index,
                'output' => 'input_1'
            ];
        }

        if (isset($step_info['isCondition']) && $step_info['isCondition']) {
            $this->funnel->update_meta($step_id, '_wpfnl_maybe_enable_condition', 'yes');
            $this->handle_conditions($step_info, $index);
        }
        
        
        return $index;
    }


    /**
     * Prepare step data.
     *
     * @param int $step_id Step ID.
     * @param array $step_info Step information.
     * @param int $index Step index.
     *
     * @return array Prepared step data.
     *
     * @since 3.2.1
     */
    private function prepare_step_data($step_id, $step_info, $index) {
        $outputs = [
            'output_1' => [
                'connections' => []
            ]
        ];

        if (isset($step_info['isCondition']) && $step_info['isCondition']) {
            $outputs['output_2'] = [
                'connections' => []
            ];
        }
        $edit_post_link = base64_encode(get_edit_post_link($step_id));
		$view_link = base64_encode(get_the_permalink($step_id));
        return [
            'id' => $index,
            'name' => strtolower($step_info['name']),
            'data' => [
                'step_edit_link' => $edit_post_link, // Generate or provide the actual edit link
                'step_id' => $step_id,
                'step_type' => strtolower($step_info['value']),
                'step_view_link' => $view_link, // Generate or provide the actual view link
            ],
            'class' => strtolower($step_info['value']),
            'html' => strtolower($step_info['value']) . $step_id,
            'typenode' => 'vue',
            'inputs' => [
                'input_1' => [
                    'connections' => []
                ]
            ],
            'outputs' => $outputs,
            'pos_x' => $step_info['pos_x'],
            'pos_y' => $step_info['pos_y'],
        ];
    }


    /**
     * Handle conditions for steps.
     *
     * @param array $step_info Step information.
     * @param int $parent_index Parent step index.
     *
     * @since 3.2.1
     */
    private function handle_conditions($step_info, $parent_index) {
        $true_conditions = $step_info['trueCondition'];
        $false_conditions = $step_info['falseCondition'];
        $this->create_conditions($true_conditions, $parent_index, 'output_1');
        $this->create_conditions($false_conditions, $parent_index, 'output_2');
    }

    private function create_conditions($conditions, $parent_index, $parent_output) {
        $previous_index = $parent_index;
        $is_first_condition = true;

        foreach ($conditions as $condition) {
            $step = Wpfnl::get_instance()->step_store;
            $step_id = $step->create_step($this->funnel_id, $condition['name'], $condition['value']);
            $step->set_id($step_id);

            $current_index = count($this->funnel_data['drawflow']['Home']['data']) + 1;
            $step_data = $this->prepare_step_data($step_id, $condition, $current_index);

            if ($is_first_condition) {
                $step_data['inputs']['input_1']['connections'][] = [
                    'node' => $parent_index,
                    'input' => $parent_output
                ];
                $is_first_condition = false;

                $this->funnel_data['drawflow']['Home']['data'][$previous_index]['outputs'][$parent_output]['connections'][] = [
                    'node' => $current_index,
                    'output' => 'input_1'
                ];
                
            } else {
                $step_data['inputs']['input_1']['connections'][] = [
                    'node' => $previous_index,
                    'input' => 'output_1'
                ];

                $this->funnel_data['drawflow']['Home']['data'][$previous_index]['outputs']['output_1']['connections'][] = [
                    'node' => $current_index,
                    'output' => 'input_1'
                ];
                
            }

            $this->funnel_data['drawflow']['Home']['data'][$current_index] = $step_data;

            if (isset($condition['isCondition']) && $condition['isCondition']) {
                $this->handle_conditions($condition, $current_index);
            }
        
            $previous_index = $current_index;
        }
    }


    /**
     * Create conditions for steps.
     *
     * @param array $conditions Conditions data.
     * @param int $parent_index Parent step index.
     * @param string $parent_output Parent output connection.
     *
     * @since 3.2.1
     */
    public function save_funnel_data(){
        $this->funnel->update_meta($this->funnel_id, '_funnel_data', $this->funnel_data);
        $this->funnel->update_meta($this->funnel_id, 'wpfnls_is_newui_migrated', 'yes');
        $this->funnel->update_meta($this->funnel_id, 'funnel_identifier', []);
        $steps_order = $this->get_steps_order($this->funnel_data);
        $this->funnel->update_meta($this->funnel_id, '_steps', $steps_order);
        $this->funnel->update_meta($this->funnel_id, '_steps_order', $steps_order);
    }


    /**
     * Get the order of steps.
     *
     * @param array $funnel_data Funnel data.
     *
     * @return array Steps order.
     *
     * @since 3.2.1
     */
	private function get_steps_order($funnel_flow_data)
	{
		$drawflow = $funnel_flow_data['drawflow'];
		$step_order = array();
		$start_node = array();

		if (isset($drawflow['Home']['data'])) {
			$drawflow_data = $drawflow['Home']['data'];

			/**
			 * If has only one step, that only step will be the first step, no conditions should be checked.
			 * just return the step order
			 */
			if (1 === count($drawflow_data)) {
				$node_id = array_keys($drawflow_data)[0];
				$data = $drawflow_data[$node_id];
				$step_data = isset($data['data']) ? $data['data'] : array();
				$step_type = isset($step_data['step_type']) ? $step_data['step_type'] : '';
				$step_id = isset($step_data['step_id']) ? $step_data['step_id'] : 0;
				$step_order[] = array(
					'id' => $step_id,
					'step_type' => $step_type,
					'name' => sanitize_text_field(get_the_title($step_id)),
				);
				return $step_order;

			}

			/**
			 * First we will find the first node (the node which has only output connection but no input connection will be considered as first node) and the list of nodes array which has the
			 * step information includes output connection and input connection and it will be stored on $nodes
			 */
			foreach ($drawflow_data as $key => $data) {
				$step_data = $data['data'];

				$step_type = $step_data['step_type'];
				$step_id = 'conditional' !== $step_type && 'addstep' !== $step_type ? $step_data['step_id'] : 0;
				if (
					(isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections'])) ||
					(isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']))
				) {

					if ('conditional' === $step_type || 'addstep' === $step_type) {
						continue;
					}

					/**
					 * A starting node is a node which has only output connection but not any input connection.
					 * if the step is landing, then there should not be any input connection for this step. so we will only consider the output connection for landing only.
					 * for other step types (checkout, offer, thankyou), we will check if the step has any output connection and no input connection.
					 */
					if ('landing' === $step_type) {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset($data['inputs']) && (count($data['inputs']) == 0 || (isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) == 0)))
						) {
							$start_node = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						}
					} else {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) === 0)
						) {
							$start_node = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						} else {
							$step_order[] = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						}
					}
				}
			}

			$step_order = $this->array_insert($step_order, $start_node, 0);
		}
		return $step_order;
	}


    /**
	 * Array insert element on position
	 *
	 * @param $original
	 * @param $inserted
	 * @param int $position
	 *
	 * @return mixed
     * 
     * @since 3.2.1
	 */
	private function array_insert(&$original, $inserted, $position)
	{
		array_splice($original, $position, 0, array($inserted));
		return $original;
	}
}
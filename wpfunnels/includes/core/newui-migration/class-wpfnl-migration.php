<?php
/**
 * WPFNL New UI migration class
 *
 * This class is responsible for migrating old data to the new user interface (UI) of the WPFunnels plugin. It contains a set of methods that facilitate data migration, ensuring a smooth transition for users.
 *
 * @package WPFunnels\Migration
 * @since 3.0.0
 */
namespace WPFunnels\Migration;

use WPFunnels\Wpfnl_functions;
/**
 * Class Migration
 *
 * The `Migration` class encapsulates functionalities and methods essential for managing the migration process, ensuring a smooth transition to the new UI.
 */
class Migration {

	/**
	 * An array to store next node data.
	 *
	 * @var array
	 * @since 3.0.0
	 */
	public $next_node = [];


	/**
	 * Update funnel data during migration.
	 *
	 * @param int $funnel_id The ID of the funnel to update.
	 * @return bool True on success, false on failure.
	 *
	 * @since 3.0.0
	 */
	public function update_funnel_data( $funnel_id, $funnel_data){
		if( !$funnel_id || !$funnel_data ){
			return false;
		}

		$condition = $this->get_conditional_step_data( $funnel_data );

		$conditional_step_data = $condition['conditional_step_data'];
		$node_identifier = $condition['node_identifier'];
		// update_post_meta( $funnel_id, '_funnel_data', $funnel_data );
		$funnel_data = $this->update_step_id_in_funnel_data_and_identifier( $funnel_id, $funnel_data );
		Wpfnl_functions::generate_first_step( $funnel_id );
		$first_step = Wpfnl_functions::get_first_step( $funnel_id );
		$funnel_data = $this->generateFlowArray( $funnel_data, $first_step );
		$this->update_funnel_meta( $funnel_id, $funnel_data );

		if( !$conditional_step_data ){
			$flow_data = $funnel_data['drawflow']['Home']['data'];
			$hasThankyou = false;
			$thankyouStepId = '';

			//remove outputs for multiple inputs
			foreach( $flow_data as $key=> $data ){
				if( isset($data['data']['step_type']) && 'thankyou' ===  $data['data']['step_type'] ){
					$hasThankyou = true;
					$thankyouStepId = $data['data']['step_id'];
				}
			}
			$funnel_data['drawflow']['Home']['data'] = $this->add_node_for_empty_output( $funnel_data, $flow_data, $hasThankyou, $thankyouStepId );
			update_post_meta( $funnel_id, '_funnel_data', $funnel_data );
			return true;
		}
		$prev_conditions = [];
		if ($node_identifier) {
			$prev_conditions = get_post_meta( $funnel_id, $node_identifier, true );
		}
		$previous_data = $this->get_prev_step_of_conditional_step( $funnel_data );

		$this->next_node = $this->get_outputs_of_conditional_step( $funnel_data );

		$updated_data = $this->prepare_updated_data( $funnel_id, $funnel_data, $conditional_step_data, $prev_conditions, $previous_data );


		$updated_data = $this->rearrage_connections( $updated_data );
		$updated_data = $this->update_position( $updated_data );

		update_post_meta( $funnel_id, '_funnel_data', $updated_data );
		return true;
	}


	/**
	 * Update funnel identifier
	 *
	 * @param $remote_step
	 * @param $new_step
	 * @param $args
	 *
	 * @return void
	 */
	public function update_step_id_in_funnel_data_and_identifier( $funnel_id, $funnel_flow_array )
	{
		// Create an array to store used step types and their corresponding step ids
		$used_step_types = array();
		$step_id_array = get_post_meta( $funnel_id,'_steps', true );
		// Loop through each step in funnel
		foreach ($funnel_flow_array['drawflow']['Home']['data'] as &$step) {
			// Find corresponding step in step id data
			foreach ($step_id_array as $step_id_data) {
				// Check if step type is the same
				if ( $step['data']['step_type'] === $step_id_data['step_type'] && 'conditional' !== $step_id_data['step_type'] ) {
					// Check if step id is not in used_step_types array
					if ( !in_array($step_id_data['id'], $used_step_types) && false === array_search($step['data']['step_id'], array_column($step_id_array, 'id') )
					) {
						$post_edit_link = base64_encode(get_edit_post_link($step_id_data['id']));
						$post_view_link = base64_encode(get_post_permalink($step_id_data['id']));
						$step['data']['step_id'] = $step_id_data['id'];
						$step['data']['step_edit_link'] = $post_edit_link;
						$step['data']['step_view_link'] = $post_view_link;
						$step['html'] = $step['data']['step_type'] . $step_id_data['id'];
						$step['name'] = $step_id_data['name'];
						// Add step id to used_step_types array
						$used_step_types[] = $step_id_data['id'];
						break;
					}
				}
			}
		}
		return $funnel_flow_array;
	}



	/**
	 * generate formated array
	 *
	 * @param array $fullArray
	 * @param int $startStepId
	 *
	 * @return array
	 *
	 * @since 3.0.0
	 */
	public function generateFlowArray($fullArray, $startStepId) {
		$resultArray = array(
			'drawflow' => array(
				'Home' => array(
					'data' => array(),
				),
			),
		);

		// Fetch the starting step data from the full array
		$startStepData = $this->findFirstStepData($fullArray['drawflow']['Home']['data'], $startStepId);

		if ($startStepData) {
			// Add the starting step to the result array
			$resultArray['drawflow']['Home']['data'][$startStepData['id']] = $startStepData;

			// Recursively add connected steps
			$this->addConnectedSteps($resultArray['drawflow']['Home']['data'], $fullArray['drawflow']['Home']['data'], $startStepData);

			$this->removeUnconnectedInputs($resultArray['drawflow']['Home']['data']);
		}

		return $resultArray;
	}



	/**
	 * Add connected step to the array
	 *
	 * @param array $resultData
	 * @param array $fullData
	 * @param array $currentStep
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function addConnectedSteps(&$resultData, $fullData, $currentStep) {
		foreach ($currentStep['outputs'] as $outputKey => $output) {
			foreach ($output['connections'] as $connection) {
				$connectedStepId = $connection['node'];

				// Check if the connected step is not already in the array to avoid infinite recursion
				if (!isset($resultData[$connectedStepId])) {
					$connectedStepData = $this->findStepData($fullData, $connectedStepId);

					if ($connectedStepData) {
						// Add the connected step to the result array
						$resultData[$connectedStepId] = $connectedStepData;

						// Recursively add its connected steps
						$this->addConnectedSteps($resultData, $fullData, $connectedStepData);
					}
				}
			}
		}
	}


	/**
	 * Finbd first step from array
	 *
	 * @param array $data
	 * @param int $stepId
	 *
	 * @return array|null
	 *
	 * @since 3.0.0
	 */
	public function findFirstStepData($data, $stepId) {
		// Find step data based on step_id from the provided data array
		foreach ($data as $stepData) {
			if ( isset($stepData['data']['step_id']) && $stepData['data']['step_id'] == $stepId ) {
				return $stepData;
			}
		}

		return null;
	}


	/**
	 * Finb step data from array
	 *
	 * @param array $data
	 * @param int $nodeId
	 *
	 * @return array|null
	 *
	 * @since 3.0.0
	 */
	public function findStepData($data, $nodeId) {
		// Find step data based on step_id from the provided data array
		foreach ($data as $stepData) {
			if ( isset($stepData['id'])&& $stepData['id'] == $nodeId ) {
				return $stepData;
			}
		}

		return null;
	}


	/**
	 * Remove unconnected inputs
	 *
	 * @param array $resultData
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function removeUnconnectedInputs(&$resultData) {
		foreach ($resultData as &$stepData) {
			foreach ($stepData['inputs'] as $inputKey => $input) {
				if( isset($input['connections']) && is_array($input['connections']) ){
					foreach( $input['connections'] as $key=>$in ){
						$inputNode = $input['connections'][$key]['node'] ?? null;
						if ($inputNode !== null && !isset($resultData[$inputNode])) {
							// Remove unconnected inputs
							unset($stepData['inputs'][$inputKey]['connections'][$key]);

						}
					}
					$stepData['inputs'][$inputKey]['connections'] = array_values($stepData['inputs'][$inputKey]['connections']);
				}

			}
		}
	}



	/**
	 * Prepare updated data during migration.
	 *
	 * @param int   $funnel_id           The ID of the funnel.
	 * @param array $funnel_data         The funnel data to be updated.
	 * @param array $conditional_step_data The data related to conditional steps.
	 * @param array $previous_data       The data of previous steps.
	 * @return array|bool The updated funnel data or false on failure.
	 *
	 * @since 3.0.0
	 */
	public function prepare_updated_data( $funnel_id, $funnel_data, $conditional_step_data, $prev_conditions, $previous_data ){

		$flow_data = $funnel_data['drawflow']['Home']['data'];
		foreach( $conditional_step_data as $index=>$conditional_step ){
			foreach( $flow_data as $key=> $data ){
				if( isset($conditional_step['id']) && $conditional_step['id'] === $data['id'] ){
					unset($flow_data[$key]);
				}

				if( isset($previous_data[$conditional_step['id']]) ){
					if( $previous_data[$conditional_step['id']]['id'] === $data['id'] ){
						if( isset($this->next_node[$conditional_step['id']] ) ){
							$this->next_node[$data['id']] = $this->next_node[$conditional_step['id']];
							unset($this->next_node[$conditional_step['id']]);
						}
						$flow_data[$key]['outputs'] = $conditional_step['outputs'];
						foreach( $flow_data as $identifier=> $data ){
							if( isset($data['inputs']['input_1']['connections'][0]['node']) && $conditional_step['id'] == $data['inputs']['input_1']['connections'][0]['node'] ){
								$flow_data[$identifier]['inputs']['input_1']['connections'][0]['node'] = $previous_data[$conditional_step['id']]['id'];
							}
						}

						Wpfnl_functions::update_meta( $previous_data[$conditional_step['id']]['data']['step_id'], '_wpfnl_maybe_enable_condition', 'yes' );

						$conditions = get_post_meta( $funnel_id, $conditional_step['data']['node_identifier'], true );
						Wpfnl_functions::delete_meta( $funnel_id, $conditional_step['data']['node_identifier'] );
						if( is_array($prev_conditions) ){
							Wpfnl_functions::update_meta( $previous_data[$conditional_step['id']]['data']['step_id'], '_wpfnl_step_conditions', $prev_conditions );
						}
					}

				}

			}
		}
		$funnel_data['drawflow']['Home']['data'] = $flow_data;

		return $funnel_data;

	}


	/**
	 * Rearrange connections in the funnel data to ensure consistency and clarity.
	 *
	 * This method reorganizes the connections within the provided funnel data to enhance readability
	 * and maintain consistency. It processes the connections between nodes, removes redundant connections,
	 * and ensures that output connections are properly linked to input nodes.
	 *
	 * @param array $funnel_data An array representing the funnel data to be rearranged.
	 *
	 * @return array|false The rearranged funnel data, or false on failure.
	 *
	 * @since 3.0.0
	 */
	public function rearrage_connections( $funnel_data ){

		$flow_data = $funnel_data['drawflow']['Home']['data'];
		$muliple_input_ids = [];
		$current_connections = [];

		//remove multiple inputs
		$response = $this->remove_multiple_inouts( $flow_data );
		$muliple_input_ids = $response['muliple_input_ids'];
		$current_connections = $response['current_connections'];
		$flow_data = $response['flow_data'];

		$hasThankyou = false;
		$thankyouStepId = '';

		//remove outputs for multiple inputs
		foreach( $flow_data as $key=> $data ){
			if( isset($data['data']['step_type']) && 'thankyou' ===  $data['data']['step_type'] ){
				$hasThankyou = true;
				$thankyouStepId = $data['data']['step_id'];
			}
			if( isset( $data['outputs']['output_1']['connections'][0]['node'] ) ){
				if( isset($current_connections[$data['outputs']['output_1']['connections'][0]['node']]) && $current_connections[$data['outputs']['output_1']['connections'][0]['node']] != $data['id'] ){
					$flow_data[$key]['outputs']['output_1']['connections'] = [];
				}
			}

			if( isset( $data['outputs']['output_2']['connections'][0]['node'] ) ){
				if( isset($current_connections[$data['outputs']['output_2']['connections'][0]['node']]) && $current_connections[$data['outputs']['output_2']['connections'][0]['node']] != $data['id'] ){
					$flow_data[$key]['outputs']['output_2']['connections'] = [];
				}
			}
		}

		//Add node for the empty outputs
		$funnel_data['drawflow']['Home']['data'] = $this->add_node_for_empty_output( $funnel_data, $flow_data, $hasThankyou, $thankyouStepId );
		return $funnel_data;
	}


	/**
	 * Add node for empty outputs
	 *
	 * @param array $funnel_data
	 * @param array $flow_data
	 * @param bool $hasThankyou
	 * @param int $thankyouStepId
	 *
	 * @return array $flow_data
	 * @since 3.0.0
	 */
	public function add_node_for_empty_output( $funnel_data, $flow_data, $hasThankyou, $thankyouStepId ){
		foreach( $flow_data as $key=> $data ){

			if( isset ($data['id'], $data['data']['step_type'], $data['outputs']['output_1'] ) && 'thankyou' !== $data['data']['step_type'] ){
				if( isset($data['outputs']['output_1']['connections']) && empty($data['outputs']['output_1']['connections']) ){
					$node_id = $this->get_unique_node_id( $funnel_data );
					$position =  [
						'pos_x' =>  $data['pos_x'] + 350,
						'pos_y' =>  isset($data['outputs']['output_2']) ? $data['pos_y'] - 200 : $data['pos_y']
					];
					$input_node_id = $data['id'];

					if( $hasThankyou ){
						$flow_data[$node_id] = $this->add_thankyou_page( $position, $input_node_id, 'output_1', $node_id, $thankyouStepId );
					}else{
						$flow_data[$node_id] = $this->get_add_step_node_data( $position, $input_node_id, 'output_1', $node_id );
					}

					$flow_data[$key]['outputs']['output_1']['connections'][0]['node'] = $node_id;
					$flow_data[$key]['outputs']['output_1']['connections'][0]['output'] = 'input_1';
				}

				if( isset($data['outputs']['output_2']['connections']) && empty($data['outputs']['output_2']['connections'])){
					$node_id = $this->get_unique_node_id( $funnel_data );
					$position =  [
						'pos_x' =>  $data['pos_x'] + 350,
						'pos_y' =>  $data['pos_y'] + 200
					];
					$input_node_id = $data['id'];
					if( $hasThankyou ){
						$flow_data[$node_id] = $this->get_add_step_node_data( $position, $input_node_id, 'output_2', $node_id, $thankyouStepId);
					}else{
						$flow_data[$node_id] = $this->get_add_step_node_data( $position, $input_node_id, 'output_2', $node_id );
					}

					$flow_data[$key]['outputs']['output_2']['connections'][0]['node'] = $node_id;
					$flow_data[$key]['outputs']['output_2']['connections'][0]['output'] = 'input_1';
				}
			}
		}
		return $flow_data;
	}


	/**
	 * Remove multiple inputs
	 *
	 * @param array $flow_data
	 * @return array
	 * @since 3.0.0
	 */
	public function remove_multiple_inouts( $flow_data ){
		foreach( $flow_data as $key=> $data ){
			if( isset($data['inputs']['input_1']['connections'] ) && is_array($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) ){
				$is_multiple = false;

				if( 1 < count($data['inputs']['input_1']['connections']) ){
					$is_multiple = true;
				}

				if( $is_multiple ){
					foreach( $data['inputs']['input_1']['connections'] as $i=>$connection ){
						if( 0!==$i ){
							unset($flow_data[$key]['inputs']['input_1']['connections'][$i]);
						}
					}
					$id = $data['id'];
					$muliple_input_ids[]=$id;
					$index = array_search ($data['id'], $this->next_node);
					if( false !== $index ){
						$flow_data[$key]['inputs']['input_1']['connections'][0]['node'] = $index;
					}
					$current_connections[$data['id']] = $flow_data[$key]['inputs']['input_1']['connections'][0]['node'];
				}

			}
		}

		return [
			'current_connections'   => $current_connections,
			'muliple_input_ids'     => $muliple_input_ids,
			'flow_data'             => $flow_data,
		];
	}





	/**
	 * Store legacy funnel data during migration.
	 *
	 * @param int   $funnel_id   The ID of the funnel.
	 * @param array $funnel_data The funnel data to store as legacy data.
	 * @return bool True on success, false on failure.
	 *
	 * @since 3.0.0
	 */
	public function update_funnel_meta( $funnel_id, $funnel_data ){
		update_post_meta( $funnel_id, '_funnel_data', $funnel_data );
	}


	/**
	 * Retrieve conditional step data from funnel data.
	 *
	 * @param array $funnel_data The funnel data to extract conditional step data from.
	 * @return array|bool An array of conditional step data or false on failure.
	 *
	 * @since 3.0.0
	 */
	public function get_conditional_step_data( $funnel_data ){
		if( !isset($funnel_data['drawflow']['Home']['data']) || !is_array($funnel_data['drawflow']['Home']['data']) ){
			return false;
		}
		$flow_data = $funnel_data['drawflow']['Home']['data'];
		$conditional_step_data = [];
		$node_identifier = '';

		foreach( $flow_data as $key=> $data ){
			if( isset($data['data']['step_type']) && 'conditional' === $data['data']['step_type'] ){
				$conditional_step_data[] = $data;
				$node_identifier = str_replace('conditional', '', $data['html']);
			}
		}

		return [
			'conditional_step_data' => $conditional_step_data,
			'node_identifier' => $node_identifier,
		];
	}


	/**
	 * Retrieve previous step data of conditional steps.
	 *
	 * @param array $funnel_data The funnel data to extract previous step data from.
	 * @return array|bool An array of previous step data related to conditional steps or false on failure.
	 *
	 * @since 3.0.0
	 */
	public function get_prev_step_of_conditional_step( $funnel_data ){

		$flow_data = $funnel_data['drawflow']['Home']['data'];
		$previous_data = [];
		foreach( $flow_data as $index=> $data ){
			if( isset($data['data']['step_type'], $data['inputs']['input_1']['connections'][0]['node'] ) && 'conditional' === $data['data']['step_type'] ){
				$prev_node_id = $data['inputs']['input_1']['connections'][0]['node'];
				foreach( $flow_data as $key=> $prev_node_data ){
					if( $prev_node_id == $prev_node_data['id'] ){
						$previous_data[$data['id']] = $prev_node_data;
					}
				}
			}
		}
		return $previous_data;
	}


	/**
	 * Retrieve next node data of conditional steps.
	 *
	 * @param array $funnel_data The funnel data to extract next node data from.
	 * @return array|bool An array of next node data related to conditional steps or false on failure.
	 *
	 * @since 3.0.0
	 */
	public function get_outputs_of_conditional_step( $funnel_data ){

		$flow_data = $funnel_data['drawflow']['Home']['data'];
		$next_node = [];
		foreach( $flow_data as $index=> $data ){
			if( isset($data['data']['step_type'], $data['outputs']['output_1']['connections'][0]['node'] ) && 'conditional' === $data['data']['step_type'] ){
				$next_node[$data['id']] = $data['outputs']['output_1']['connections'][0]['node'];
			}
		}
		return $next_node;
	}


	/**
	 * Generate data for adding thankyou step.
	 *
	 * @param array  $position      The position of the step node.
	 * @param string $input_node_id The ID of the input node.
	 * @param string $input         The input name.
	 * @param string $node_id       The ID of the new node to be added.
	 * @param string $step_id       The ID of existing thankyou step.
	 * @return array The data for adding a step node.
	 *
	 * @since 3.0.0
	 */
	public function add_thankyou_page( $position, $input_node_id, $input, $node_id, $step_id ){
		$node_identifier = rand() * (500 - 100) + 100;
		$new_step_id = $this->duplicate_thankyou( $step_id );
		$step_edit_link =  get_edit_post_link($new_step_id);
		if( 'elementor' ===  Wpfnl_functions::get_builder_type() ){
			$step_edit_link = str_replace(['&amp;', 'edit'], ['&', 'elementor'], $step_edit_link);
			if ( is_plugin_active( 'elementor/elementor.php' ) && class_exists( '\Elementor\Plugin' ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache(); // Clearing cache of Elementor CSS.
			}
		}
		$step_edit_link = base64_encode($step_edit_link);
		$step_view_link = base64_encode(get_post_permalink($new_step_id));

		$data = [
			'id'   => $node_id,
			'name' => 'thankyou',
			'data' => [
				'step_edit_link'         => $step_edit_link,
				'step_type'         => 'thankyou',
				'step_id'         => $new_step_id,
				'step_view_link'   => $step_view_link
			],
			'class' => 'thankyou',
			'html'  => 'thankyou'.$new_step_id,
			'typenode' => 'vue',
			'inputs' => [
				'input_1' => [
					'connections' => [
						[
							'node'  => $input_node_id,
							'input' => $input,
						]
					]
				]
			],
			'outputs' => [],
			'pos_x' => isset($position['pos_x']) ? $position['pos_x'] : 383,
			'pos_y' => isset($position['pos_y']) ? $position['pos_y'] : 143,
		];
		return $data;
	}


	/**
	 * Duplicate thakyouy page
	 *
	 * @param init $step_id Thankyou page Id
	 * @return init $step_id
	 * @since 3.0.0
	 */
	public function duplicate_thankyou( $step_id ){
		$funnel_id = get_post_meta( $step_id, '_funnel_id', true );
		$step = new \WPFunnels\Data_Store\Wpfnl_Steps_Store_Data();
		$title = get_the_title($step_id);
		$page_template = get_post_meta($step_id, '_wp_page_template', true);
		$post_content = get_post_field('post_content', $step_id);
		$new_step_id = $step->create_step($funnel_id, $title, 'thankyou', $post_content, true);
		$step->update_meta($new_step_id, '_funnel_id', $funnel_id);
		$step->update_meta($new_step_id, '_wp_page_template', $page_template);
		$storeInstance = new \WPFunnels\Data_Store\Wpfnl_Funnel_Store_Data();
		$storeInstance->duplicate_all_meta( $step_id, $new_step_id, array('_funnel_id','_is_duplicate') );
		return $new_step_id;
	}


	/**
	 * Generate data for adding a step node.
	 *
	 * @param array  $position      The position of the step node.
	 * @param string $input_node_id The ID of the input node.
	 * @param string $input         The input name.
	 * @param string $node_id       The ID of the new node to be added.
	 * @return array The data for adding a step node.
	 *
	 * @since 3.0.0
	 */
	public function get_add_step_node_data( $position, $input_node_id, $input, $node_id ){
		$node_identifier = rand() * (500 - 100) + 100;

		$data = [
			'id'   => $node_id,
			'name' => 'addstep',
			'data' => [
				'step_type'         => 'addstep',
				'node_identifier'   => $node_identifier
			],
			'class' => 'addstep',
			'html'  => 'addstep'.$node_identifier,
			'typenode' => 'vue',
			'inputs' => [
				'input_1' => [
					'connections' => [
						[
							'node'  => $input_node_id,
							'input' => $input,
						]
					]
				]
			],
			'outputs' => [],
			'pos_x' => isset($position['pos_x']) ? $position['pos_x'] : 383,
			'pos_y' => isset($position['pos_y']) ? $position['pos_y'] : 143,
		];
		return $data;
	}


	/**
	 * Get a unique node ID.
	 *
	 * @param array $funnel_data The funnel data to check for uniqueness.
	 * @param string $node_id The suggested node ID.
	 * @return string The unique node ID.
	 *
	 * @since 3.0.0
	 */
	public function get_unique_node_id( $funnel_data, $node_id = '' ){

		$flow_data = $funnel_data['drawflow']['Home']['data'];
		$node_id = $node_id ? $node_id : rand(20,25);
		$is_unique = true;
		foreach( $flow_data as $index=> $data ){
			if( isset($data['id']) ){
				if( $node_id == $data['id'] ){
					$is_unique = false;
				}
			}
		}

		if( !$is_unique ){
			$node_id = rand();
			$this->get_unique_node_id( $funnel_data, $node_id );
		}
		return $node_id;
	}


	/**
	 * Update x position for a connected step
	 *
	 * @param array $funnel_data The funnel data
	 *
	 * @return array $funnel_data The funnel data
	 *
	 * @since 3.0.0
	 */
	public function update_position( $funnel_data ){
		$flow_data = $funnel_data['drawflow']['Home']['data'];
		foreach( $flow_data as $key=>$data ){
			if( isset($data['pos_x'], $data['outputs']) && is_array($data['outputs']) ){
				$pos_x = $data['pos_x'];
				foreach( $data['outputs'] as $index=>$output ){
					if( isset($output['connections'][0]['node']) ){
						foreach( $flow_data as $i=>$new_data ){
							if( ($new_data['id'] == $output['connections'][0]['node']) && ( (abs($pos_x - $new_data['pos_x']) > 250) || (abs($pos_x - $new_data['pos_x']) < 200) ) ){
								$flow_data[$i]['pos_x'] = $pos_x + (count($data['outputs']) > 1 ? 350 : 243) ;
							}
						}
					}
				}
			}
		}
		$funnel_data['drawflow']['Home']['data'] = $flow_data;
		return $funnel_data;
	}
}

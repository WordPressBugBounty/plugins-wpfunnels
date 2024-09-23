<?php
/**
 * Abstract class of source base
 *
 * @package
 */
namespace WPFunnels\TemplateLibrary;

use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

abstract class Wpfnl_Source_Base
{
    abstract public function get_source();

    abstract public function get_funnels($arg = []);

    abstract public function get_funnel($template_id);

    abstract public function get_data(array $args);

    abstract public function import_funnel($args = []);

    abstract public function import_step($args = []);


    /**
     * After funnel import
     * redirect to new funnel edit url
     *
     * @param $payload
	 *
     * @return array
     * @since  1.0.0
     */
    public function after_funnel_creation($payload)
    {
        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->set_id($payload['funnelID']);
        $funnel->set_steps_order();

        // rearrange steps order
        $funnel_data 	= get_post_meta( $payload['funnelID'], 'funnel_data', true );
        $funnel_data 	= $funnel_data ? $funnel_data : get_post_meta( $payload['funnelID'], '_funnel_data', true );

        $imported_steps = $payload['importedSteps'];

		// update the funnel metadata with newly created step
		$this->save_steps_meta_data( $payload['funnelID'], $imported_steps );

        // Reinitialize the funnel data if freemium steps are exists on funnel data. Because free user only can
		// import the free steps of a freemium funnel.
		$is_pro_active = apply_filters( 'wpfunnels/is_pro_license_activated', false );
        if ( !$is_pro_active && 'freemium' === $payload['templateType'] ) {
			$node_data 									= $funnel_data['drawflow']['Home']['data'];
			$filter_data 								= $this->filter_node_data($node_data);
			$funnel_data['drawflow']['Home']['data'] 	= $filter_data;
			update_post_meta($payload['funnelID'],'funnel_data', $funnel_data);
		}

        $redirect_link = add_query_arg(
            [
                'page'      => WPFNL_EDIT_FUNNEL_SLUG,
                'id'        => $payload['funnelID'],
                'step_id'   => $funnel->get_first_step_id(),
            ],
            admin_url('admin.php')
        );
        return [
            'success'       => true,
            'redirectLink'  => $redirect_link,
        ];
    }


	/**
	 * Update funnel metadata with newly created steps
	 *
	 * @param $funnel_id
	 * @param $imported_steps
	 * @since 3.1.2
	 */
    private function save_steps_meta_data( $funnel_id, $imported_steps ) {
		if ( !$funnel_id || empty( $imported_steps ) ) {
			return;
		}
		$steps 		= [];
		$identifier = [];
		foreach ( $imported_steps as $step_id ) {
			$step_title = get_the_title( $step_id );
			$step_type	= get_post_meta( $step_id, '_step_type', true );
			$identifier[] = $step_id;
			$steps[]	= array(
				'id'		=> $step_id,
				'step_type'	=> $step_type,
				'name'		=> $step_title
			);
		}
		$first_step_id = $this->get_first_step_id( $funnel_id, $steps );
		update_post_meta( $funnel_id, '_steps_order', $steps );
		update_post_meta( $funnel_id, '_steps', $steps );
		update_post_meta( $funnel_id, '_first_step', $first_step_id );

		$this->update_funnel_identifier( $funnel_id, $identifier );
    }


	/**
	 * Filter and reinitialize the drawflow data for freemium
	 * template. This reinitialization takes place if and only if
	 * free user import the freemium template.
	 *
	 * @param $steps_array
	 * @return array
	 * @since 3.1.2
	 */
	private function filter_node_data( $steps_array ) {

		if ( empty($steps_array) ) {
			return $steps_array;
		}
		$filtered_array = [];
		$thank_you_found = false;

		foreach ($steps_array as $key => $step) {
			// Skip upsell and downsell steps
			if ($step['data']['step_type'] === 'upsell' || $step['data']['step_type'] === 'downsell') {
				continue;
			}

			// If it's a thankyou step and hasn't been found before, add it
			if ($step['data']['step_type'] === 'thankyou' && !$thank_you_found) {
				$filtered_array[$key] = $step;
				$thank_you_found = true;
			}

			// If it's not an upsell, downsell, or the first thankyou, add it
			if ( ($step['data']['step_type'] !== 'upsell' && $step['data']['step_type'] !== 'downsell' && !$thank_you_found) || in_array($step['data']['step_type'], array('landing', 'checkout')) ) {
				$filtered_array[$key] = $step;
			}
		}

		// Find the checkout and thankyou steps
		$checkout_id = null;
		$thank_you_id = null;

		foreach ($filtered_array as $key => $step) {
			if ($step['data']['step_type'] === 'checkout') {
				$checkout_id = $key;
			} elseif ($step['data']['step_type'] === 'thankyou') {
				$thank_you_id = $key;
			}
		}

		// Update inputs of thankyou and outputs of checkout
		$filtered_array[$thank_you_id]['inputs'] = [
			'input_1' => [
				'connections' => [
					[
						'node' => $checkout_id,
						'input' => 'output_1'
					]
				]
			]
		];

		$filtered_array[$checkout_id]['outputs'] = [
			'output_1' => [
				'connections' => [
					[
						'node' => $thank_you_id,
						'output' => 'input_1'
					]
				]
			]
		];

		return $filtered_array;
	}


	/**
	 * Get the first step id of the funnel
	 *
	 * @param $funnel_id
	 * @param $steps
	 * @return mixed|void|null
	 * @since 3.1.2
	 */
	private function get_first_step_id( $funnel_id, $steps ) {
		if ( !$funnel_id || empty( $steps ) ) {
			return;
		}
		foreach ($steps as $step) {
			if ( !isset($step['step_id']) ) {
				continue;
			}
			if ($step['step_type'] === 'landing') {
				return $step['step_id'];
			} elseif ($step['step_type'] === 'checkout') {
				return $step['step_id'];
			}
		}

		// If neither landing nor checkout exists
		return null;
	}


	/**
	 * Update the funnel identifier data
	 *
	 * @param $funnel_id
	 * @param $identifier
	 * @since 3.1.2
	 */
	private function update_funnel_identifier( $funnel_id, $identifier ) {
		if ( !$funnel_id || empty( $identifier ) ) {
			return;
		}
		$identifier_json 	= get_post_meta($funnel_id, 'funnel_identifier', true);
		$identifier_json 	= preg_replace('/\: *([0-9]+\.?[0-9e+\-]*)/', ':"\\1"', $identifier_json);
		$identifier_data 	= json_decode( $identifier_json, true );

		if (is_array($identifier_data)) {
			foreach ($identifier_data as $key => $data) {
				if ( !in_array( $data, $identifier ) ) {
					unset($identifier_data[$key]);
				}
			}
			$funnel_identifier_json = json_encode($identifier_data, JSON_UNESCAPED_SLASHES);
			update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier_json);
		}
	}


    /**
     * After funnel import
     * redirect to new funnel edit url
     *
     * @param $payload
	 *
     * @return array
     * @since  1.0.0
     */
    public function after_step_creation($payload)
    {
        $redirect_link = add_query_arg(
            [
                'page'      => WPFNL_EDIT_FUNNEL_SLUG,
                'id'        => $payload['funnelID'],
                'step_id'   => $payload['stepID'],
            ],
            admin_url('admin.php')
        );
        return [
            'success'       => true,
            'redirectLink'  => $redirect_link,
        ];
    }


	/**
	 * Get steps order
	 *
	 * @param $funnel_flow_data
	 *
	 * @return array
	 *
	 * @since 2.2.6
	 */
	private function get_steps_order( $funnel_flow_data ) {
		$drawflow		= $funnel_flow_data['drawflow'];
		$nodes			= array();
		$step_order		= array();
		$first_node_id	= '';
		$start_node 	= array();


		if( isset( $drawflow['Home']['data'] ) ) {
			$drawflow_data = $drawflow['Home']['data'];

			/**
			 * If has only one step, that only step will be the first step, no conditions should be checked.
			 * just return the step order
			 */
			if( 1 === count( $drawflow_data ) ) {
				$node_id 	= array_keys($drawflow_data)[0];
				$data 		= $drawflow_data[$node_id];
				$step_data 	= $data['data'];
				$step_id 	= $step_data['step_id'];
				$step_type 	= $step_data['step_type'];
				$step_order[] 	= array(
					'id'		=> $step_id,
					'step_type'	=> $step_type,
					'name'		=> sanitize_text_field( get_the_title( $step_id ) ),
				);
				return $step_order;

			}

			/**
			 * First we will find the first node (the node which has only output connection but no input connection will be considered as first node) and the list of nodes array which has the
			 * Step information includes output connection and input connection and it will be stored on $nodes
			 */
			foreach ( $drawflow_data as $key => $data ) {
				$step_data 	= $data['data'];
				$step_type 	= $step_data['step_type'];
				$step_id 	= $step_type !== 'conditional' ? $step_data['step_id'] : 0;
				if(
					(isset( $data['outputs']['output_1']['connections'] ) && count( $data['outputs']['output_1']['connections'] ) ) ||
					(isset( $data['inputs']['input_1']['connections'] ) && count($data['inputs']['input_1']['connections']) )
				) {

					if('conditional' === $step_type) {
						continue;
					}

					/**
					 * A starting node is a node which has only output connection but not any input connection.
					 * If the step is landing, then there should not be any input connection for this step. so we will only consider the output connection for landing only.
					 * For other step types (checkout, offer, thankyou), we will check if the step has any output connection and no input connection.
					 */
					if( 'landing' === $step_type ) {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset( $data['inputs'] ) && count($data['inputs']) == 0 )
						) {
							$start_node 	= array(
								'id' 		=> $step_id,
								'step_type' => $step_type,
								'name' 		=> sanitize_text_field(get_the_title($step_id)),
							);
						}
					} else {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) === 0)
						) {
							$start_node 	= array(
								'id' 		=> $step_id,
								'step_type' => $step_type,
								'name' 		=> sanitize_text_field(get_the_title($step_id)),
							);
						} else {
							$step_order[] = array(
								'id' 		=> $step_id,
								'step_type' => $step_type,
								'name' 		=> sanitize_text_field(get_the_title($step_id)),
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
	 */
	private function array_insert(&$original, $inserted, $position) {
		array_splice($original, $position, 0, array($inserted));
		return $original;
	}

}

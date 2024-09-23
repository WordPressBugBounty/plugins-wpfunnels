<?php
/**
 * This class is responsible for accept / reject order bump in frontend
 * 
 * @package
 */
namespace WPFunnels\Classes\OrderBumpActions;

use Error;
use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Order_Bump_Action {

    use SingletonTrait;

    protected $ob_settings;
    protected $current_ob_settings;
    protected $selected_ob;
    protected $checkout_id;

    public function __construct() {

		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'add_order_bump_hidden_fields' ), 99 );
		add_action( 'wpfunnels/elementor_render_order_bump', array( $this, 'load_elementor_actions' ), 9999, 2 );
		add_action( 'wpfunnels/bricks_render_order_bump', array( $this, 'load_elementor_actions' ), 9999, 2 );
        add_action( 'wpfunnels/gb_render_order_bump_ajax', array( $this, 'load_gb_actions' ), 10, 2 );
        add_action( 'wpfunnels/before_checkout_form', array( $this, 'load_actions' ), 10, 2 );

    }



    public function add_order_bump_hidden_fields() {
		$checkout_meta 			= new Wpfnl_Default_Meta();

		$is_order_bump_enabled 	= $checkout_meta->get_checkout_meta_value(get_the_ID(), 'order-bump');

		$this->ob_settings = $checkout_meta->get_checkout_meta_value(get_the_ID(), 'order-bump-settings', wpfnl()->meta->get_default_order_bump_meta());
		foreach( $this->ob_settings as $key=>$settings ){
			if ( $settings['isEnabled'] ) {
				echo '<input type="hidden" name="_wpfunnels_order_bump_product_'.$key.'" value="">';
				echo '<input type="hidden" name="_wpfunnels_order_bump_clicked" value="">';
			}
		}
	}


	public function load_actions( $checkout_id, $settings = array() ) {
		if( !function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			if( is_plugin_active( 'elementor/elementor.php' ) ) {
				if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					return false;
				}
			}
		}
		else {
			if( is_plugin_active( 'elementor/elementor.php' ) ) {
				if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					return false;
				}
			}
		}

		$checkout_meta     = new Wpfnl_Default_Meta();
		$funnel_id         = get_post_meta( $checkout_id, '_funnel_id', true );
		$this->ob_settings = $checkout_meta->get_checkout_meta_value( $checkout_id, 'order-bump-settings', wpfnl()->meta->get_default_order_bump_meta() );
		$this->ob_settings = apply_filters( 'wpfunnels/order_bump_settings', $this->ob_settings, $funnel_id, $checkout_id );

		$this->trigger_ob_actions();
	}



	/**
	 * Load elementor action for order bump preview
	 *
	 * @param $checkout_id
	 * @param $settings
	 * 
	 * @return void
	 * @since  2.0.4
	 */
	public function load_elementor_actions( $checkout_id, $settings ) {
		$funnel_id         = get_post_meta( $checkout_id, '_funnel_id', true );
		$this->ob_settings = apply_filters( 'wpfunnels/order_bump_settings', $settings, $funnel_id, $checkout_id );
		$this->checkout_id = $checkout_id;
		$this->trigger_ob_actions();
	}


	/**
	 * Load action hooks for order bump render
	 *
	 * @param $checkout_id
	 * @param $ob_settings
	 *
	 * @return void
	 * 
	 * @since 2.0.4
	 */
	public function load_gb_actions( $checkout_id, $ob_settings ) {
		$funnel_id         = get_post_meta( $checkout_id, '_funnel_id', true );
		$this->ob_settings = $ob_settings;
		$this->ob_settings = apply_filters( 'wpfunnels/order_bump_settings', $this->ob_settings, $funnel_id, $checkout_id );
		$this->trigger_ob_actions();
	}



	/**
	 * Trigger WC action for order bump
	 * 
	 * @return void
	 * 
	 * @since 2.0.4
	 */
	private function trigger_ob_actions() {
		if( is_array( $this->ob_settings ) ) {
			foreach( $this->ob_settings as $key => $settings ) {
				$this->current_ob_settings = $settings;
				$position                  = $this->get_order_bump_attribute( $settings, 'position' );
				$is_enabled                = $this->get_order_bump_attribute( $settings, 'isEnabled' );
			
				if( 'yes' === $is_enabled || 1 === (int)$is_enabled || 'true' == $is_enabled ) {
					$is_enabled = true;
				}
				elseif( 'no' === $is_enabled || 0 === (int)$is_enabled || '' === $is_enabled ) {
					$is_enabled = false;
				}

				if( !$position ) {
					return;
				}
				if( !$is_enabled ) {
					continue;
				}
				
				if( $position === 'before-checkout' ) {
					add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump_before_checkout_form' ], 10 );
				}
				elseif( $position === 'after-order' ) {
					add_action( 'wpfunnels/after_order_total', [ $this, 'render_order_bump_after_order' ], 10 );
				}
				elseif( $position === 'before-order' ) {
					add_action( 'woocommerce_review_order_before_cart_contents', [ $this, 'render_order_bump_before_order' ], 10 );
				}
				elseif( $position === 'after-customer-details' ) {
					add_action( 'woocommerce_after_order_notes', [ $this, 'render_order_bump_after_customer_details' ], 10 );
				}
				elseif( $position === 'before-payment' ) {
					add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_order_bump_before_payment' ], 10 );
				}
				elseif( $position === 'after-payment' ) {
					add_action( 'woocommerce_review_order_after_payment', [ $this, 'render_order_bump_after_payment' ], 10 );
				}
				elseif( $position === 'after-lms-order' ) {
					add_action( 'wpfunnels/after_lms_order_deatils', [ $this, 'render_order_bump_after_lms_order_deatils' ], 10 );
				}
				elseif( $position === 'before-lms-order' ) {
					add_action( 'wpfunnels/before_lms_order_deatils', [ $this, 'render_order_bump_before_lms_order_deatils' ], 10 );
				}
				elseif( $position === 'popup' ) {
					$this->ob_settings[ $key ][ 'selectedStyle' ] = 'popup';
					$this->render_popup_in_elementor_editor();
				}
			}
		}


	}


	/**
	 * Add order bump extra meta data
	 * to main settings data
	 *
	 * @param $ob_settings
	 * @param $funnel_id
	 * @param $checkout_id
	 * 
	 * @return array
	 */
	public function add_extra_ob_data( $ob_settings, $funnel_id, $checkout_id ) {
		$ob_settings['step_id']			= $checkout_id;
		$checkout_meta					= new Wpfnl_Default_Meta();
		$main_product_ids 				= $checkout_meta->get_main_product_ids( $funnel_id, $checkout_id );
		$ob_settings['main_products'] 	= $main_product_ids;
		return $ob_settings;
	}


    /**
     * Get order bump attribute from order bump settings data
     *
     * @param $order_bump_data
     * @param $key
	 * 
     * @return bool|mixed
     */
    private function get_order_bump_attribute( $order_bump_data, $key ) {
        if( !isset($order_bump_data[$key]) ) {
            return false;
        }
        return $order_bump_data[$key];
    }


    /**
     * Render popup style for order bump in elementor
     * builder preview
     *
     * @since 2.0.3
     */
	public function render_popup_in_elementor_editor() {
		if( Wpfnl_functions::is_elementor_active() ) {
			if( \Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin() ) {
				add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
			}
			else {
				add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
			}
		}
		else {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
		}
	}


    /**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump() {

		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }


		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled'] && $settings['position'] == 'popup'){

					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {

							ob_start();
							if ( $settings['position'] == 'popup' ) {

								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}
        }

        echo $output;
    }

	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_before_checkout_form() {

		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}

		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled']  && $settings['position'] == 'before-checkout'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }
	
	
	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_before_lms_order_deatils() {

		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}
		
		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }
			$funnel_id = get_post_meta($checkout_id,'_funnel_id',true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
			foreach( $this->ob_settings as $key=> $settings ){
				
				if( $settings['isEnabled']  && $settings['position'] == 'before-lms-order'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									}else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}
			
        }

        echo $output;
    }
	
	
	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_after_lms_order_deatils() {

		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}
		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta($checkout_id,'_funnel_id',true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
			foreach( $this->ob_settings as $key=> $settings ){
				
				if( $settings['isEnabled']  && $settings['position'] == 'after-lms-order'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}
			
        }

        echo $output;
    }

	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_before_payment() {


		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}
		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }
			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled'] && $settings['position'] == 'before-payment'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }


	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_after_payment() {

		global $post;
        $checkout_id = 0;
        $output = '';

        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }
		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}
		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled'] && $settings['position'] == 'after-payment'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }


	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_after_customer_details() {

		global $post;
        $checkout_id = 0;
        $output = '';
        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}

		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){
				if( $settings['isEnabled'] && $settings['position'] == 'after-customer-details'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}
					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }


	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_after_order() {

		global $post;
        $checkout_id = 0;
        $output = '';

        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }
		
		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}
		
		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled'] && $settings['position'] == 'after-order'){


					if( $settings['isEnabled'] === 'no' ){
						continue;
					}

					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }


	/**
     * Render order bump markup
     *
     * @since 2.0.3
     */
    public function render_order_bump_before_order() {

		global $post;
        $checkout_id = 0;
        $output = '';


        if ( $post ) {
            $checkout_id = $post->ID;
        } elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
            $checkout_id = intval( $_POST['post_id'] );
        }

		if( !$checkout_id ){
			$checkout_id = $this->checkout_id;
		}

		if( Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' ) ) {
            if ( ! empty( $_POST['order_bump_data'] ) ) {
                $settings = $_POST['order_bump_data'];
            } else {
                $settings = $this->ob_settings;
            }

			$funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

			foreach( $this->ob_settings as $key=> $settings ){

				if( $settings['isEnabled'] && $settings['position'] == 'before-order'){
					if( $settings['isEnabled'] === 'no' ){
						continue;
					}

					if (isset($settings['product']) && $settings['product'] != '') {
						if ( !empty($settings['selectedStyle']) ) {
							ob_start();
							if ( $settings['position'] == 'popup' ) {
								if(Wpfnl_functions::is_elementor_active()) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
										echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
									} else {
										$order_bump_settings = $settings;
										require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
									}
								} else {
									echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
								}
							} else {
								$order_bump_settings = $settings;
								require WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
							}
							$output .= ob_get_clean();
						}
					}
					else {
						wc_clear_notices();
						wc_add_notice(__('No product is added to this order bump. Please select one.', 'wpfnl'), 'error');
					}
				}
			}

        }

        echo $output;
    }
}
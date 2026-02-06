<?php
/**
* Product Selection for Checkout
*
* This class handles the 'Your Products' section on the checkout page.
* It allows customers to select/deselect products based on the Product Options settings.
*
* Features:
* - Display a product selection table before the payment section
* - Support for three conditions:
*   1. restrict_all: All products are selected ( no UI shown )
*   2. select_one: User can select only one product ( radio buttons )
*   3. select_multiple: User can select multiple products ( checkboxes )
* - Real-time cart updates via AJAX
* - Responsive design matching WPFunnels patterns
* - Shows product images, names, and prices ( with discounts if applied )
* - Prevents cart from being empty ( at least one product must be selected )
*
* Settings are configured in:
* Admin > Funnel > Checkout Step > Products Tab > Product Options
*
* @package WPFunnels
* @since 3.2.0
*/

namespace WPFunnels\Modules\Frontend\Checkout;

use WPFunnels\Wpfnl_functions;

class Product_Selection {

    /**
    * Constructor
    */

    public function __construct() {
        add_action( 'woocommerce_before_order_notes', [ $this, 'render_product_selection' ], 5 );
        add_action( 'wp_ajax_wpfnl_update_product_selection', [ $this, 'ajax_update_product_selection' ] );
        add_action( 'wp_ajax_nopriv_wpfnl_update_product_selection', [ $this, 'ajax_update_product_selection' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
    * Enqueue scripts and styles
    */

    public function enqueue_scripts() {
        if ( ! Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
            return;
        }

        $step_id = get_the_ID();
        $product_options = get_post_meta( $step_id, '_wpfnl_checkout_product_options', true );

        // Only enqueue if product options are enabled
        if ( ! $product_options || 'yes' !== $product_options[ 'isEnabled' ] ) {
            return;
        }

        $condition = isset( $product_options[ 'condition' ] ) ? $product_options[ 'condition' ] : 'restrict_all';

        // Don't enqueue if restrict all
		if ( 'restrict_all' === $condition ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'wpfnl-product-selection',
			WPFNL_URL . 'public/assets/css/wpfnl-product-selection.css',
			array(),
			WPFNL_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'wpfnl-product-selection',
			WPFNL_URL . 'public/assets/js/wpfnl-product-selection.js',
			array( 'jquery' ),
			WPFNL_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'wpfnl-product-selection',
			'wpfnl_checkout_product_selection',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wpfnl-checkout' ),
				'step_id'  => $step_id,
			)
		);
	}

	/**
	 * Render product selection section
	 */
	public function render_product_selection() {
		if ( ! Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
			return;
		}

		$step_id = get_the_ID();
		$product_options = get_post_meta( $step_id, '_wpfnl_checkout_product_options', true );

		// Check if product options are enabled
		if ( ! $product_options || 'yes' !== $product_options['isEnabled'] ) {
			return;
		}

		$condition = isset( $product_options['condition'] ) ? $product_options['condition'] : 'restrict_all';
		
		// Don't show if restrict all
        if ( 'restrict_all' === $condition ) {
            return;
        }

        $products = get_post_meta( $step_id, '_wpfnl_checkout_products', true );
        if ( ! is_array( $products ) || empty( $products ) ) {
            return;
        }

        // Get cart items to determine selected products
        $cart_items = WC()->cart->get_cart();
        $cart_product_ids = array();
        foreach ( $cart_items as $cart_item ) {
            $cart_product_ids[] = $cart_item[ 'product_id' ];
            if ( $cart_item[ 'variation_id' ] ) {
                $cart_product_ids[] = $cart_item[ 'variation_id' ];
            }
        }

        ?>
        <div class = 'wpfnl-product-selection-wrapper'>
        <!-- <h3 class = 'wpfnl-product-selection-title'><?php esc_html_e( 'Your Products', 'wpfnl' );
        ?></h3> -->
        <div class = 'wpfnl-product-selection-list'>
        <input type = 'hidden' name = '_wpfunnels_product_option' value = ''>
        <table class = 'wpfnl-products-table'>
        <thead>
        <tr>
        <th class = 'product-select'><?php esc_html_e( 'SELECT', 'wpfnl' );
        ?></th>
        <th class = 'product-name'><?php esc_html_e( 'PRODUCT', 'wpfnl' );
        ?></th>
        <th class = 'product-price'><?php esc_html_e( 'PRICE', 'wpfnl' );
        ?></th>
        <!-- <th class = 'product-remove'></th> -->
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 0;
        foreach ( $products as $product_data ) {
            $product_id = $product_data[ 'id' ];
            $product = wc_get_product( $product_id );

            if ( ! $product || 'trash' === $product->get_status() ) {
                continue;
            }

            $is_selected = in_array( $product_id, $cart_product_ids );
            $input_type = 'select_one' === $condition ? 'radio' : 'checkbox';
            $input_name = 'select_one' === $condition ? 'wpfnl_selected_product' : 'wpfnl_selected_products[]';

            // Get product details
            $title = $product->get_type() == 'variation'
            ? Wpfnl_functions::get_formated_product_name( $product )
            : $product->get_name();
            $image = wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' );
            $price = $product->get_price();

            // Calculate discount if applied
            $discount = get_post_meta( $step_id, '_wpfnl_checkout_discount_main_product', true );
            $discounted_price = $price;
            if ( $discount && isset( $discount[ 'discountOptions' ] ) && 'original' !== $discount[ 'discountOptions' ] ) {
                if ( 'discount-percentage' === $discount[ 'discountOptions' ] ) {
                    $discount_amount = ( $price * $discount[ 'mutedDiscountValue' ] ) / 100;
                    $discounted_price = $price - $discount_amount;
                } elseif ( 'discount-price' === $discount[ 'discountOptions' ] ) {
                    $discounted_price = $price - $discount[ 'mutedDiscountValue' ];
                }
            }

            ?>
            <tr class = "wpfnl-product-row <?php echo $is_selected ? 'selected' : ''; ?>" data-product-id = "<?php echo esc_attr( $product_id ); ?>" data-index = "<?php echo esc_attr( $index ); ?>">
            <td class = 'product-select'>
            <input
            type = "<?php echo esc_attr( $input_type ); ?>"
            name = "<?php echo esc_attr( $input_name ); ?>"
            value = "<?php echo esc_attr( $product_id ); ?>"

            class = 'wpfnl-product-checkbox'
            <?php checked( $is_selected, true );
            ?>
            data-product-id = "<?php echo esc_attr( $product_id ); ?>"
            data-index = "<?php echo esc_attr( $index ); ?>"
            />
            </td>
            <td class = 'product-name'>
            <div class = 'product-info'>
            <?php if ( $image ) : ?>
            <img src = "<?php echo esc_url( $image[0] ); ?>" alt = "<?php echo esc_attr( $title ); ?>" class = 'product-image'>
            <?php endif;
            ?>
            <span class = 'product-title'><?php echo esc_html( $title );
            ?></span>
            </div>
            </td>
            <td class = 'product-price'>
            <?php echo wc_price( $discounted_price );
            ?>
            <?php if ( $discounted_price < $price ) : ?>
            <del><?php echo wc_price( $price );
            ?></del>
            <?php endif;
            ?>
            </td>
            <!-- <td class = 'product-remove'>
            <?php if ( $is_selected ) : ?>
            <button
            type = 'button'

            class = 'wpfnl-remove-product'
            data-product-id = "<?php echo esc_attr( $product_id ); ?>"
            data-index = "<?php echo esc_attr( $index ); ?>"
            title = "<?php esc_attr_e( 'Remove product', 'wpfnl' ); ?>"
            >
            <svg xmlns = 'http://www.w3.org/2000/svg' viewBox = '0 0 24 24' fill = 'currentColor'>
            <path d = 'M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z'/>
            </svg>
            </button>
            <?php endif;
            ?>
            </td> -->
            </tr>
            <?php
            $index++;
        }
        ?>
        </tbody>
        </table>
        </div>
        </div>
        <?php
    }

    /**
    * AJAX handler to update product selection
    */

    public function ajax_update_product_selection() {
        check_ajax_referer( 'wpfnl-checkout', 'security' );

        $product_id = isset( $_POST[ 'product_id' ] ) ? absint( $_POST[ 'product_id' ] ) : 0;
        $action_type = isset( $_POST[ 'action_type' ] ) ? sanitize_text_field( $_POST[ 'action_type' ] ) : 'add';
        $step_id = isset( $_POST[ 'step_id' ] ) ? absint( $_POST[ 'step_id' ] ) : get_the_ID();
        if ( ! $product_id || ! $step_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request', 'wpfnl' ) ) );
        }

        $product_options = get_post_meta( $step_id, '_wpfnl_checkout_product_options', true );
        $condition = isset( $product_options[ 'condition' ] ) ? $product_options[ 'condition' ] : 'restrict_all';

        // Get all products from this checkout step
        $products = get_post_meta( $step_id, '_wpfnl_checkout_products', true );
        if ( ! is_array( $products ) || empty( $products ) ) {
            wp_send_json_error( array( 'message' => __( 'No products found', 'wpfnl' ) ) );
        }

        // Build array of all product IDs and find the selected product data
        $all_product_ids = array();
        $product_data = null;
        foreach ( $products as $p ) {
            if ( isset( $p[ 'id' ] ) ) {
                $all_product_ids[] = absint( $p[ 'id' ] );
                if ( absint( $p[ 'id' ] ) === $product_id ) {
                    $product_data = $p;
                }
            }
        }

        if ( ! $product_data ) {
            wp_send_json_error( array( 'message' => __( 'Product not found', 'wpfnl' ) ) );
        }

        // Get WooCommerce product object
        $wc_product = wc_get_product( $product_id );
        if ( ! $wc_product ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product', 'wpfnl' ) ) );
        }

        // Get current cart
        $cart = WC()->cart->get_cart();
        // Find which checkout products are currently in cart
        $cart_product_ids = array();
        foreach ( $cart as $cart_item_key => $cart_item ) {
            $cart_product_id = !empty( $cart_item[ 'variation_id' ] ) ? absint( $cart_item[ 'variation_id' ] ) : absint( $cart_item[ 'product_id' ] );
            if ( in_array( $cart_product_id, $all_product_ids ) ) {
                $cart_product_ids[ $cart_product_id ] = $cart_item_key;
            }
        }

        // Handle based on action type
        if ( 'add' === $action_type ) {

            // For RADIO buttons ( select_one ): Remove ALL checkout products, then add ONLY selected one
            if ( 'select_one' === $condition ) {
                // Remove all checkout products from cart
                foreach ( $cart_product_ids as $pid => $cart_key ) {
                    WC()->cart->remove_cart_item( $cart_key );
                }

                // Add only the selected product
                $quantity = isset( $product_data[ 'quantity' ] ) ? absint( $product_data[ 'quantity' ] ) : 1;

                // Check if it's a variation or simple product
			if ( $wc_product->is_type( 'variation' ) ) {
				$parent_id = $wc_product->get_parent_id();
				$variation_id = $product_id;
				$cart_item_key = WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id );
			} else {
				$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );
			}
			
			if ( ! $cart_item_key ) {
				wp_send_json_error( array( 'message' => __( 'Failed to add product', 'wpfnl' ) ) );
			}
			
			WC()->cart->calculate_totals();				wp_send_json_success( array(
					'message' => __( 'Product selected', 'wpfnl' ),
					'cart_hash' => WC()->cart->get_cart_hash(),
					'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array() )
				) );
			}
			
		// For CHECKBOXES (select_multiple): Add selected product (keep others)
		elseif ( 'select_multiple' === $condition ) {
			// Check if product is already in cart
			if ( isset( $cart_product_ids[ $product_id ] ) ) {
				// Already in cart, just return success
				wp_send_json_success( array(
					'message' => __( 'Product already in cart', 'wpfnl' ),
					'cart_hash' => WC()->cart->get_cart_hash(),
					'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array() )
				) );
			}
			
			// Add product to cart
			$quantity = isset( $product_data['quantity'] ) ? absint( $product_data['quantity'] ) : 1;
			
			// Check if it's a variation or simple product
                if ( $wc_product->is_type( 'variation' ) ) {
                    $parent_id = $wc_product->get_parent_id();
                    $variation_id = $product_id;
                    $cart_item_key = WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id );
                } else {
                    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );
                }

                if ( ! $cart_item_key ) {
                    wp_send_json_error( array( 'message' => __( 'Failed to add product', 'wpfnl' ) ) );
                }
                WC()->cart->calculate_totals();

                wp_send_json_success( array(
                    'message' => __( 'Product added to cart', 'wpfnl' ),
                    'cart_hash' => WC()->cart->get_cart_hash(),
                    'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array() )
                ) );
            }

        } elseif ( 'remove' === $action_type ) {
            // Remove the product from cart
            WC()->cart->remove_cart_item( $cart_product_ids[ $product_id ] );

            wp_send_json_success( array(
                'message' => __( 'Product removed from cart', 'wpfnl' ),
                'cart_hash' => WC()->cart->get_cart_hash(),
                'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array() )
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Invalid action', 'wpfnl' ) ) );
    }
}

new Product_Selection();

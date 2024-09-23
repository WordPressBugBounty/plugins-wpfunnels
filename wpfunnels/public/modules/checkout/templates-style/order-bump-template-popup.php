<?php
/**
 * Orderbump popup template
 * 
 * @package
 */
$price_str = __('Price','wpfnl');
$pre_purchase_class = isset($settings['prePurchaseUpsell']) && 'yes' == $settings['prePurchaseUpsell'] ? 'wpfnl-pre-purchase' : '';
if( $type === 'lms' ){

    $course_meta = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_course_details_by_id( $settings['product'] );
    $orderbump_color 	= isset( $settings['obPrimaryColor'] ) ? $settings['obPrimaryColor'] : '#6E42D2';
    $price 				= $course_meta['price'] ? $course_meta['price'] : 'FREE';

    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
    
}elseif( is_plugin_active( 'woocommerce/woocommerce.php' ) ){
    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
    $type = 'wc';
    $product 			= wc_get_product($settings['product']);
    if( $product ){
        $regular_price 		= $product->get_regular_price();
        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
            $regular_price = $regular_price + $signUpFee;
        }
        $sale_price 		= $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
        $price 				= $product->get_price_html();
        $quantity			= $settings['quantity'];
        $orderbump_color 	= isset( $settings['obPrimaryColor'] ) ? $settings['obPrimaryColor'] : '#6E42D2';

        if( $product->is_on_sale() ) {
            $price = wc_format_sale_price( $regular_price * $quantity, $sale_price * $quantity );
        } else {
            $price = wc_price( $regular_price * $quantity );
        }

        if (isset($settings['discountOption'])) {
            if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
                $discount_price = preg_replace('/[^\d.]/', '', $settings['discountPrice'] );
                if ($settings['discountapply'] == 'regular') {
                    $price = wc_format_sale_price( $regular_price * $quantity, $discount_price * $quantity );
                } else {
                    $price = wc_format_sale_price( $sale_price * $quantity, $discount_price * $quantity );
                }
            }
        }
    }
}

?>

<div class="wpfnl-order-bump__popup-wrapper <?php echo $pre_purchase_class ?>">
    <span class="close-order-bump">
        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.167 1.723l-9.445 9.444m0-9.444l9.445 9.444"/></svg>
    </span>

    <div class="wpfnl-order-bump__content-wrapper">
        <div class="oderbump-loader">
            <span class="wpfnl-loader"></span>
        </div>

        
        <?php
            $img = wp_get_attachment_image_src(get_post_thumbnail_id($order_bump_settings['product']), 'single-post-thumbnail');
            if(isset($img[0])){
                $img = $img[0];
                if (isset($order_bump_settings['productImage'])) {
                    $img = $order_bump_settings['productImage'];
                    $img_id = attachment_url_to_postid($order_bump_settings['productImage']['url']);
                    if ($img_id) {
                        $thumbnail = wp_get_attachment_image_src($img_id);
                        $img = $thumbnail[0];
                    } else {
                        $img = $settings['productImage']['url'];
                    }
                }
            }
            
        ?>

        <div class="wpfnl-order-bump__content-left">
            <div class="wpfnl-order-bump__image-area">
                <img alt="Order bump popup image" src="<?php echo $img; ?>" class="img1">
            </div>

            <div class="wpfnl-order-bump__content-area">
                <h4 class="order-bump__product-title">
                    <?php echo $order_bump_settings['productName']; ?>
                </h4>
                <p class="order-bump__product-detail">
                    <?php echo $order_bump_settings['productDescriptionText']; ?>
                </p>

                <p class="order-bump__product-price">
                    <?php echo sprintf( __( '<strong>%s: </strong> %s', 'wpfnl' ), $price_str, $price ); ?>
                </p>
            </div>
        </div>

        <div class="wpfnl-order-bump__content-right">
            <div class="wpfnl-order-bump__button desktop-btn" >
                <label>
                    <input type="checkbox" id="wpfnl-order-bump-cb"
                           data-quantity="<?php echo $order_bump_settings['quantity']; ?>"
                           data-step="<?php echo get_the_ID(); ?>" class="wpfnl-order-bump-cb"
                           data-key="<?php echo $key; ?>"
                           data-lms="<?php echo $type; ?>"
                           data-replace="<?php echo $settings['isReplace']; ?>"
                           name="wpfnl-order-bump-cb" value="<?php echo $order_bump_settings["product"]; ?>">
                    <span class="wpfnl-bump-order-label"><?php echo $order_bump_settings["checkBoxLabel"]; ?></span>
                </label>
            </div>
        </div>
        <!-- /.wpfnl-order-bump__content-right -->
    </div>
    <!-- /.wpfnl-order-bump__content-wrapper -->

    <div style="display:none" >
        <?php
            if( $type === 'lms' ){
                if (is_user_logged_in() && WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::is_learndash_active() ){
                    $course_access = sfwd_lms_has_access($settings['product'], get_current_user_id() );
                    $next_step_url = WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id).'?wpfnl_ld_payment=free&wpfnl_course_status=enrolled';
                    $lms_button_text 		= get_option( 'learndash_settings_custom_labels' );
                    $button_text 			= !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : 'Take This Course';
                    if ($course_access ){
                        echo '<a class="btn-default" href="'.$next_step_url.'" id="wpfnl-lms-access-course">'.$button_text.'</a>';
                        echo '<span class="wpfnl-lms-access-course-message"></span>';
                    }else if($course_type == 'free'){
                        $next_step_url = WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id);
                        echo '<a class="btn-default" href="'.$next_step_url.'" user_id="'.get_current_user_id().'" step_id="'.$step_id.'" course_id="'.$course['id'].'" id="wpfnl-lms-free-course">'.$button_text.'</a>';
                        echo '<span class="wpfnl-lms-free-course-message"></span>';
                    }
                    else{
                        echo do_shortcode('[learndash_payment_buttons course_id='.$course['id'].']');
                    }
                }else{
                    echo do_shortcode('[learndash_login]');
                }
            }
        ?>
    </div>

    <style>
        .wpfnl-order-bump__popup-wrapper .wpfnl-order-bump__button input[type="checkbox"]:checked + .wpfnl-bump-order-label:before {
            background-color: <?php echo $orderbump_color; ?>;
            border-color: <?php echo $orderbump_color; ?>;
        }
    </style>
</div>

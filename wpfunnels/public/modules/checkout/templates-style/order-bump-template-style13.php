<?php
/**
 * Orderbump style13 template
 * 
 * @package
 */
$orderbump_color 	= isset( $settings['obPrimaryColor'] ) ? $settings['obPrimaryColor'] : '#6E42D2'; //getting order-bump color
$orderbump_bgcolor 	= isset( $settings['obBgColor'] ) ? $settings['obBgColor'] : ''; //getting order-bump background color
$orderbump_priceColor 	= isset( $settings['obPriceColor'] ) ? $settings['obPriceColor'] : ''; //getting order-bump price color

$ob_title_color 	        = isset( $settings['obTitleColor'] ) ? $settings['obTitleColor'] : '#363B4E'; //getting order-bump title color
$ob_highlight_color 	    = isset( $settings['obHighlightColor'] ) ? $settings['obHighlightColor'] : '#6E42D3'; //getting order-bump highlight color
$ob_checkbox_title_color 	= isset( $settings['obCheckboxTitleColor'] ) ? $settings['obCheckboxTitleColor'] : '#d9d9d9'; //getting order-bump checkbox title color
$ob_description_color 	    = isset( $settings['obDescriptionColor'] ) ? $settings['obDescriptionColor'] : '#7A8B9A'; //getting order-bump description color
$ob_choose_variant_color = isset($settings['obChooseVariantColor'])? $settings['obChooseVariantColor'] : '#F34D01';
$ob_choose_variant_name  = isset($settings['chooseVariantName'])? $settings['chooseVariantName'] : 'Choose an Option';

$price_str = __('Price','wpfnl');
if( $type === 'lms' ){
    $course_meta = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_course_details_by_id( $settings['product'] );
    
    $price 				= $course_meta['price'] ? $course_meta['price'] : 'FREE';
    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
    $course = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_course_details_by_id($settings['product']);
    $course_type = isset( $course[ 'type' ] ) ? $course[ 'type' ] : '';
}elseif( is_plugin_active( 'woocommerce/woocommerce.php' ) ){
    $course_type =  '';
    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
    $type = 'wc';
    $product 			= wc_get_product($settings['product']);
    $regular_price 		= $product->get_regular_price();
    if ($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
        $regular_price = $product->get_variation_regular_price('min') ? $product->get_variation_regular_price('min') : $product->get_price();
    } else {
        $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
    }
    if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
        $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
        $regular_price = $regular_price + $signUpFee;
    }
    $sale_price 		= $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
    $price 				= $product->get_price_html();
    $quantity			= !empty( $settings['quantity'] ) ? $settings['quantity'] : 1;

    if( $product->is_on_sale() ) {
        $price = wc_format_sale_price( (float)$regular_price * (float)$quantity, (float)$sale_price * (float)$quantity );
    } else {
        $price = wc_price( (float)$regular_price * (float)$quantity );
    }

    if (isset($settings['discountOption'])) {
        if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
            $discount_price = preg_replace('/[^\d.]/', '', $settings['discountPrice'] );
            $discount_price = apply_filters('wpfunnels/modify_order_bump_product_price', $discount_price);
            if ($settings['discountapply'] == 'regular') {
                $price = wc_format_sale_price( (float) $regular_price * (float) $quantity, (float) $discount_price );
            } else {
                $price = wc_format_sale_price( (float) $sale_price * (float) $quantity, (float) $discount_price);
            }
        }
    }
}

?>

<div class="wpfnl-reset wpfnl-order-bump__template-style13" style="background-color: <?php echo $orderbump_bgcolor; ?>">
    <div class="oderbump-loader">
        <span class="wpfnl-loader"></span>
    </div>

    <h6 class="subtitle" style="color: <?php echo $ob_highlight_color ?>" ><?php echo $settings['highLightText'] ?></h6>
    <?php 
        $isVariable = !empty($settings['isVariable']) ? $settings['isVariable'] : false; 
    ?>
    <div class="offer-checkbox" style="background-color: <?php echo $orderbump_color; ?>">
        <span class="wpfnl-checkbox">
            <input
                id="wpfnl-order-bump-cb-<?php echo $key ?>"
                class="wpfnl-order-bump-cb"
                type="checkbox"
                name="wpfnl-order-bump-cb-<?php echo $key ?>"
                data-key="<?php echo $key; ?>"
                data-quantity="<?php echo $settings['quantity']; ?>"
                data-replace="<?php echo $settings['isReplace']; ?>"
                data-step="<?php echo get_the_ID(); ?>"
                data-lms="<?php echo $type; ?>"
                data-is-variable="<?php echo $isVariable ? 'true' : 'false'; ?>"
                value="<?php echo $settings['product'] ?>"
            >

            <label for="wpfnl-order-bump-cb-<?php echo $key ?>" style="color: <?php echo $ob_checkbox_title_color ?>" >
                <?php echo $settings['checkBoxLabel'] ?>
                <span class="product-price" id="wpfnl-order-bump-price-<?php echo $key; ?>" style="--priceColor13: <?php echo $orderbump_priceColor ?> ">
                    <?php echo sprintf( __( '<strong>%s: </strong> %s', 'wpfnl' ), $price_str, $price ); ?>
                </span>
            </label>
        </span>
    </div>

    <div class="template-preview-wrapper">
        <?php
        $img = '';
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($settings['product']), 'single-post-thumbnail');

        if (isset($img[0])) {
            $img = $img[0];
        }
        if (isset($settings['productImage'])) {
            if ($settings['productImage'] != "") {
               
                $img_id = attachment_url_to_postid($settings['productImage']['url']);
                if ($img_id) {
                    $thumbnail = wp_get_attachment_image_src( $img_id, 'medium' );
                    $img = $thumbnail[0];
                }else {
                    $img = $settings['productImage']['url'];
                }
            }
        }

        ?>
        <div class="template-img" id="wpfnl-order-bump-image-<?php echo $key; ?>" style="background-image: url('<?php echo $img; ?>');">
            <img src="<?php echo $img; ?>" alt="" class="for-mobile">
        </div>

        <div class="template-content">
            <h5 class="template-title" id="wpfnl-order-bump-title-<?php echo $key; ?>" style="color: <?php echo $ob_title_color ?>" ><?php echo $settings['productName'] ?></h5>
            <div class="description" id="wpfnl-order-bump-description-<?php echo $key; ?>" style="color: <?php echo $ob_description_color ?>; --descColor13:<?php echo $ob_description_color ?>" ><?php echo $settings['productDescriptionText'] ?></div>
            <?php if ($isVariable): ?>
                <!-- Variable Product Option -->
                <div id="option-selector-<?php echo $key; ?>"  
                    class="option-selector" 
                    style="color: <?php echo $ob_choose_variant_color; ?>; cursor: pointer;">
                    <!-- <?php echo $ob_choose_variant_name; ?> -->
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    if ($isVariable) {
        include WPFNL_DIR . 'public/modules/checkout/templates/variable-product-modal.php';
    }
    ?>
    <style>
        .wpfnl-order-bump__template-style13 .template-preview-wrapper .description li,
        .wpfnl-order-bump__template-style13 .template-preview-wrapper .description span,
        .wpfnl-order-bump__template-style13 .template-preview-wrapper .description p {
            color: var(--descColor13);
        }
        .wpfnl-order-bump__template-style13 .product-price span,
        .wpfnl-order-bump__template-style13 .product-price {
            color: var(--priceColor13);
        }
    </style>

</div>
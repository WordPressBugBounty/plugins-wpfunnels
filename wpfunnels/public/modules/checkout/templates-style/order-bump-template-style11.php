<?php
/**
 * Orderbump style11 template
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


if( $type === 'lms' ){

    $course_meta = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_course_details_by_id( $settings['product'] );
    $price 				= $course_meta['price'] ? $course_meta['price'] : 'FREE';

    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
}elseif( is_plugin_active( 'woocommerce/woocommerce.php' ) ){
    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id,'_funnel_id');
    $type = 'wc';
    $product 			= wc_get_product($settings['product']);
    $regular_price 		= $product->get_regular_price();
    if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
        $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
        $regular_price = $regular_price + $signUpFee;
    }
    $sale_price 		= $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
    $price 				= $product->get_price_html();
    $quantity			= $settings['quantity'];

    if( $product->is_on_sale() ) {
        $price = wc_format_sale_price( $regular_price * $quantity, $sale_price * $quantity );
    } else {
        $price = wc_price( $regular_price * $quantity );
    }

    if (isset($settings['discountOption'])) {
        if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
            $discount_price = preg_replace('/[^\d.]/', '', $settings['discountPrice'] );
            if ($settings['discountapply'] == 'regular') {
                $price = wc_format_sale_price( $regular_price * $quantity, $discount_price);
            } else {
                $price = wc_format_sale_price( $sale_price * $quantity, $discount_price);
            }
        }
    }
}
?>

<div class="wpfnl-reset wpfnl-order-bump__template wpfnl-order-bump__template-style11" style="background-color: <?php echo $orderbump_bgcolor; ?>; border-color: <?php echo $orderbump_color; ?>">
    <div class="oderbump-loader">
        <span class="wpfnl-loader"></span>
    </div>
    
    <div class="template-preview-wrapper">
        <div class="template-content">
            <div class="template-title-area">
                <div class="offer-checkbox">
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
                            value="<?php echo $settings['product'] ?>"
                        >

                        <label for="wpfnl-order-bump-cb-<?php echo $key ?>" style="color: <?php echo $ob_checkbox_title_color ?>" ><?php echo $settings['checkBoxLabel'] ?></label>
                    </span>
                </div>

                <span class="product-price" style="--priceColor11: <?php echo $orderbump_priceColor ?> ">
                    <?php echo $price; ?>
                </span>
            </div>
            
            <div class="description" style="color: <?php echo $ob_description_color ?>; --descColor11:<?php echo $ob_description_color ?>" >
                <?php echo $settings['productDescriptionText'] ?>
            </div>
        </div>
    </div>

    <style>
        .wpfnl-order-bump__template-style11 .template-preview-wrapper .description li,
        .wpfnl-order-bump__template-style11 .template-preview-wrapper .description span,
        .wpfnl-order-bump__template-style11 .template-preview-wrapper .description p {
            color: var(--descColor11);
        }
        .wpfnl-order-bump__template-style11 .template-preview-wrapper .template-title-area .product-price span,
        .wpfnl-order-bump__template-style11 .template-preview-wrapper .template-title-area .product-price {
            color: var(--priceColor11);
        }
    </style>

</div>

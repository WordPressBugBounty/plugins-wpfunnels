<?php

/**
 * Orderbump style10 template
 * 
 * @package
 */
$orderbump_color     = isset($settings['obPrimaryColor']) ? $settings['obPrimaryColor'] : '#6E42D2'; //getting order-bump color
$orderbump_bgcolor     = isset($settings['obBgColor']) ? $settings['obBgColor'] : ''; //getting order-bump background color
$orderbump_priceColor     = isset($settings['obPriceColor']) ? $settings['obPriceColor'] : ''; //getting order-bump price color

$ob_title_color             = isset($settings['obTitleColor']) ? $settings['obTitleColor'] : '#363B4E'; //getting order-bump title color
$ob_highlight_color         = isset($settings['obHighlightColor']) ? $settings['obHighlightColor'] : '#6E42D3'; //getting order-bump highlight color
$ob_checkbox_title_color     = isset($settings['obCheckboxTitleColor']) ? $settings['obCheckboxTitleColor'] : '#d9d9d9'; //getting order-bump checkbox title color
$ob_description_color         = isset($settings['obDescriptionColor']) ? $settings['obDescriptionColor'] : '#7A8B9A'; //getting order-bump description color
$ob_choose_variant_color = isset($settings['obChooseVariantColor'])? $settings['obChooseVariantColor'] : '#F34D01';
$ob_choose_variant_name  = isset($settings['chooseVariantName'])? $settings['chooseVariantName'] : 'Choose an Option';

if ($type === 'lms') {

    $course_meta = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_course_details_by_id($settings['product']);
    $price                 = $course_meta['price'] ? $course_meta['price'] : 'FREE';

    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id, '_funnel_id');
} elseif (is_plugin_active('woocommerce/woocommerce.php')) {
    $step_id = $checkout_id;
    $funnel_id = get_post_meta($step_id, '_funnel_id');
    $type = 'wc';
    $product             = wc_get_product($settings['product']);
    if ($product) {
        $regular_price         = $product->get_regular_price();
        if ($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
            $regular_price = $product->get_variation_regular_price('min') ? $product->get_variation_regular_price('min') : $product->get_price();
        } else {
            $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
        }
        if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee($product);
            $regular_price = $regular_price + $signUpFee;
        }
        $sale_price         = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
        $price                 = $product->get_price_html();
        $quantity            = !empty($settings['quantity']) ? $settings['quantity'] : 1;

        if( $product->is_on_sale() ) {
            $price = wc_format_sale_price( (float)$regular_price * (float)$quantity, (float)$sale_price * (float)$quantity );
        } else {
            $price = wc_price( (float)$regular_price * (float)$quantity );
        }

        if (isset($settings['discountOption'])) {
            if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
                $discount_price = preg_replace('/[^\d.]/', '', $settings['discountPrice']);
                $discount_price = apply_filters('wpfunnels/modify_order_bump_product_price', $discount_price);
                if ($settings['discountapply'] == 'regular') {
                    $price = wc_format_sale_price((float) $regular_price * (float) $quantity, (float) $discount_price);
                } else {
                    $price = wc_format_sale_price((float) $sale_price * (float) $quantity, (float) $discount_price);
                }
            }
        }
    }
}
?>

<div class="wpfnl-reset wpfnl-order-bump__template wpfnl-order-bump__template-style10" style="background-color: <?php echo $orderbump_bgcolor; ?>">
    <div class="oderbump-loader">
        <span class="wpfnl-loader"></span>
    </div>

    <div class="template-preview-wrapper">
        <div class="template-content">
            <div class="template-title-area">
                <h5 class="template-title" style="color: <?php echo $ob_title_color ?>">
                    <span class="nav-arrow">
                        <svg width="15" height="13" viewBox="0 0 15 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M14.9081 6.72871L8.65523 12.9102C8.56587 12.9982 8.43279 13.0236 8.31471 12.9771C8.19788 12.9288 8.12226 12.816 8.12226 12.6908V9.2825H0.312405C0.139963 9.28247 0 9.14368 0 8.97264V4.01504C0 3.844 0.139963 3.70518 0.312405 3.70518H8.12229V0.309859C8.12229 0.18469 8.19853 0.0718956 8.31535 0.0235586C8.3541 0.00743675 8.39469 0 8.4347 0C8.51591 0 8.59589 0.0316048 8.65587 0.0904856L14.9088 6.28997C14.9675 6.34821 15 6.42693 15 6.50934C15 6.59175 14.9668 6.67044 14.9081 6.72871Z"
                                fill="#EE8134" />
                        </svg>
                    </span>

                    <span>Add <span id="wpfnl-order-bump-title-<?php echo $key; ?>"><?php echo esc_html($settings['productName']); ?></span> to your order for only
                        <span class="product-price" id="wpfnl-order-bump-price-<?php echo $key; ?>" style="--priceColor2: <?php echo esc_attr($orderbump_priceColor); ?> ">
                            <?php echo $price; ?>
                        </span>
                    </span>
                </h5>
                <?php 
                    $isVariable = !empty($settings['isVariable']) ? $settings['isVariable'] : false; 
                ?>
                <div class="offer-checkbox" style="<?php echo $isVariable ? 'width:145px;' : ''; ?>" >
                    <span class="wpfnl-checkbox">
                        <input type="checkbox" id="wpfnl-order-bump-cb-<?php echo $key ?>" data-quantity="<?php echo $settings['quantity']; ?>"
                            data-step="<?php echo get_the_ID(); ?>" class="wpfnl-order-bump-cb" name="wpfnl-order-bump-cb-<?php echo $key ?>"
                            data-key="<?php echo $key; ?>"
                            data-lms="<?php echo $type; ?>"
                            data-replace="<?php echo $settings['isReplace']; ?>"
                            data-is-variable="<?php echo $isVariable ? 'true' : 'false'; ?>"
                            value="<?php echo $settings['product'] ?>">

                        <label for="wpfnl-order-bump-cb-<?php echo $key ?>" id="wpfnl-order-bump-add-btn-<?php echo $key ?>" style="color: <?php echo $orderbump_color; ?>">Add</label>
                    </span>
                </div>
            </div>
            <?php if ($isVariable): ?>
                <!-- Variable Product Option -->
                <div id="option-selector-<?php echo $key; ?>"  
                    class="option-selector" 
                    style="color: <?php echo $ob_choose_variant_color; ?>; cursor: pointer;">
                    <!-- <?php echo $ob_choose_variant_name; ?> -->
                </div>
            <?php endif; ?>

            <div class="description" id="wpfnl-order-bump-description-<?php echo $key; ?>" style="color: <?php echo $ob_description_color ?>; --descColor10:<?php echo $ob_description_color ?>">
                <?php echo $settings['productDescriptionText'] ?>
            </div>
        </div>
    </div>
    <?php
    if ($isVariable) {
        include WPFNL_DIR . 'public/modules/checkout/templates/variable-product-modal.php';
    }
    ?>
    <style>
        .wpfnl-order-bump__template-style10 .template-preview-wrapper .description li,
        .wpfnl-order-bump__template-style10 .template-preview-wrapper .description span,
        .wpfnl-order-bump__template-style10 .template-preview-wrapper .description p {
            color: var(--descColor10);
        }

        .wpfnl-order-bump__template-style10 .template-preview-wrapper .template-title .product-price span,
        .wpfnl-order-bump__template-style10 .template-preview-wrapper .template-title .product-price {
            color: var(--priceColor10);
        }
    </style>
</div>
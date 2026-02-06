<div class="wpfnl-reset dynamic-offer-template-default dynamic-offer-template1 dynamic-offer-template3">
    <div class="template-left">
        <div class="product-img">
            <img src="<?php echo isset($product_info['img']) ? $product_info['img'] : '' ?>" alt="product-img" />
        </div>
    </div>

    <div class="template-right">
        <div class="template-content">
            <h2 class="template-product-title"><?php echo isset($product_info['title']) ? $product_info['title'] : '' ?></h2>
            <div class="template-product-description">
                <?php echo isset($product_info['description']) ? $product_info['description'] : '' ?>
            </div>

            <?php require_once WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/gutenberg/offer-button.php'; ?>

        </div>
    </div>

</div>
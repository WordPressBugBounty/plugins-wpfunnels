<div class="wpfnl-reset dynamic-offer-template-default dynamic-offer-template1 dynamic-offer-template2">
    <div class="template-left">
        <div class="product-img">
        <img src="<?php echo $offer_product ? get_the_post_thumbnail_url($offer_product->get_id()) : '' ?>" alt="product-img" />
        </div>
    </div>

    <div class="template-right">
        <div class="template-content">
            <h2 class="template-product-title"><?php echo $offer_product ? $offer_product->get_title() : '' ?></h2>
            <div class="template-product-description">
                <?php echo isset($offer_product->get_data()['short_description']) ? $offer_product->get_data()['short_description'] : '' ?>
            </div>
            <!-- start offer button -->
            <?php require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/'.$builder.'/offer-button.php'; ?>
            <!-- end offer button -->
            
        </div>
    </div>
</div>
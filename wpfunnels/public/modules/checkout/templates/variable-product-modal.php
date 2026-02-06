<?php
/**
 * Variable product modal
 *
 * @package
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

?>
<!-- Modal Structure -->
<div id="ob-modal-<?php echo esc_attr( $args['key'] ); ?>" class="ob-modal" style="display: none;">
    <div class="ob-modal-content">
        <span class="ob-close">&times;</span>
		<?php
		if ( $args['product'] && $args['product']->is_type( 'variable' ) ) {
			$available_variations = $args['product']->get_available_variations();
			$attributes           = $args['product']->get_variation_attributes();
			$product              = $args['product'];
			require WPFNL_DIR . 'public/modules/checkout/templates-style/variable-product.php';
		}
		?>
    </div>
</div>

<?php
/**
 * Store Checkout template selection view
 *
 * @package WPFunnels
 * @since 3.5.0
 */

use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_pro_active         = apply_filters( 'wpfunnels/is_pro_license_activated', false );

?>

<div class="wpfnl wpfnl-store-checkout-page wpfnl-templates-page">
    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>

            <?php
                if ( !$is_pro_active ) {
                    ?>
                    <div class="wpfnl-dashboard__nav-right">
                        <a href="https://getwpfunnels.com/pricing/" class="btn-default" target="_blank" title="Upgrade to Pro" aria-label="Upgrade to Pro">Upgrade to Pro</a>
                    </div>
                    <?php
                }
            ?>
        </nav>

        <div class="dashboard-nav__content">
            <div id="templates-library">

			</div>
        </div>
        <!-- /.dashboard-nav__content -->
    </div>
</div>


<script type="text/javascript">
    // Pass data to Vue app to indicate this is Store Checkout mode
    window.wpfnlStoreCheckoutMode = true;

    // Show template modal inline for Store Checkout page
    jQuery(document).ready(function($) {
        setTimeout(function() {
            $('#template-library-modal').show();
            $('#wpfnl-create-funnel__inner-content').show();
            $('#wpfnl-create-steps_inner-content').hide();
        }, 100);
    });
</script>

<?php
$is_pro_active         = apply_filters( 'wpfunnels/is_pro_license_activated', false );
?>

<div class="wpfnl wpfnl-templates-page">
    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php use WPFunnels\Wpfnl_functions;
            require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>

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
    </div>
</div>
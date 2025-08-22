<?php
/**
 * View general settings
 */
$builders = \WPFunnels\Wpfnl_functions::get_supported_builders();
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-funnel-type"><?php esc_html_e('Funnel Type', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-page-builder"><?php esc_html_e('Page Builder', 'wpfnl'); ?></li>
    </ul>
</div>
<div class="wpfnl-tab-content active" id="tab-funnel-type">
    <div class="wpfnl-field-wrapper">
        <div class="wpfnl-fields">
            <div class="wpfnl-items-wrapper" id="wpfunnels-funnel-type">
                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'lead' ? 'checked' : '' ?>" data-value="lead">
                    <!-- SVG omitted for brevity -->
                    <?php require WPFNL_DIR . '/admin/partials/icons/lead-funnel-icon.php'; ?>
                    <p class="wpfnl-title"><?php esc_html_e('Lead Funnel', 'wpfnl'); ?></p>
                </div>
                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'sales' ? 'checked' : '' ?>" data-value="sales">
                    <!-- SVG omitted for brevity -->
                    <?php require WPFNL_DIR . '/admin/partials/icons/complete-funnel-icon.php'; ?>
                    <p class="wpfnl-title"><?php esc_html_e('Complete Funnel', 'wpfnl'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="wpfnl-tab-content" id="tab-page-builder">
    <div class="wpfnl-field-wrapper">
        <div class="wpfnl-fields">
            <div class="wpfnl-items-wrapper" id="wpfunnels-page-builder">
                <?php foreach ($builders as $key => $value) { ?>
                    <div class="wpfnl-single-item <?php echo $this->general_settings['builder'] == $key ? 'checked' : '' ?>" data-value="<?php echo esc_attr($key); ?>">
                        <?php if ($key === 'gutenberg') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/gutenberg.png'); ?>" alt="">
                        <?php } elseif ($key === 'elementor') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/elementor.png'); ?>" alt="">
                        <?php } elseif ($key === 'divi-builder') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/divi.png'); ?>" alt="">
                        <?php } elseif ($key === 'oxygen') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/oxygen.png'); ?>" alt="">
                        <?php } elseif ($key === 'bricks') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/bricks.png'); ?>" alt="">
                        <?php } elseif ($key === 'other') { ?>
                            <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/others.png'); ?>" alt="">
                        <?php } ?>
                        <p class="wpfnl-title"><?php echo esc_html($value); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="wpfnl-field-wrapper sync-template">
    <label class="has-tooltip wpfnl-hidden">
        <?php esc_html_e('Sync Template', 'wpfnl'); ?>

        <span class="wpfnl-tooltip">
            <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
            <p><?php esc_html_e('Click to get the updated funnel templates, made using your preferred page builder, when creating funnels.', 'wpfnl'); ?></p>
        </span>
    </label>
    <div class="wpfnl-fields">
        <div class="sync-btn-wrapper">
            <button class="btn-default clear-template" id="clear-template">
                <span class="icon-sync" style="display: inline-block"><?php require WPFNL_DIR . '/admin/partials/icons/sync-icon.php'; ?></span>
                <span class="check-icon"><?php require WPFNL_DIR . '/admin/partials/icons/check-sync-icon.php'; ?></span>
                <span class="sync-btn-text"><?php esc_html_e( 'Sync Templates', 'wpfnl' ); ?></span>
            </button>
            <span class="wpfnl-alert"></span>
        </div>
    </div>
</div>

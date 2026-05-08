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
        <li class="inner-tab" data-tab="tab-canvas-mode"><?php esc_html_e('Canvas Mode', 'wpfnl'); ?></li>
    </ul>
</div>
<div class="wpfnl-tab-content active" id="tab-funnel-type">
    <div class="wpfnl-field-wrapper">
        <div class="wpfnl-fields">
            <div class="wpfnl-items-wrapper" id="wpfunnels-funnel-type">
                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'sales' ? 'checked' : '' ?>" data-value="sales">
                    <!-- SVG omitted for brevity -->
                    <?php require WPFNL_DIR . '/admin/partials/icons/complete-funnel-icon.php'; ?>
                    <p class="wpfnl-title"><?php esc_html_e('Sales Funnel', 'wpfnl'); ?></p>
                </div>
                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'lead' ? 'checked' : '' ?>" data-value="lead">
                    <!-- SVG omitted for brevity -->
                    <?php require WPFNL_DIR . '/admin/partials/icons/lead-funnel-icon.php'; ?>
                    <p class="wpfnl-title"><?php esc_html_e('Lead Funnel', 'wpfnl'); ?></p>
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
</div>

<div class="wpfnl-tab-content" id="tab-canvas-mode">
    <div class="wpfnl-field-wrapper">
        <div class="wpfnl-fields">
            <div class="wpfnl-items-wrapper" id="wpfunnels-canvas-mode">
                <?php $canvas_mode = isset($this->general_settings['funnel_builder_mode']) ? $this->general_settings['funnel_builder_mode'] : 'horizontal'; ?>
                <div class="wpfnl-single-item <?php echo $canvas_mode === 'horizontal' ? 'checked' : ''; ?>" data-value="horizontal">
                    <svg width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="18" width="16" height="24" rx="4" fill="#7c3aed" opacity="0.85"/>
                        <path d="M18 30h8" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="26" y="18" width="16" height="24" rx="4" fill="#7c3aed" opacity="0.55"/>
                        <path d="M42 30h8" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="50" y="18" width="10" height="24" rx="4" fill="#7c3aed" opacity="0.25"/>
                    </svg>
                    <p class="wpfnl-title"><?php esc_html_e('Horizontal', 'wpfnl'); ?></p>
                </div>
                <div class="wpfnl-single-item <?php echo $canvas_mode === 'vertical' ? 'checked' : ''; ?>" data-value="vertical">
                    <svg width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="10" y="2" width="40" height="14" rx="4" fill="#7c3aed" opacity="0.85"/>
                        <path d="M30 16v8" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="10" y="24" width="40" height="14" rx="4" fill="#7c3aed" opacity="0.55"/>
                        <path d="M30 38v8" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="10" y="46" width="40" height="12" rx="4" fill="#7c3aed" opacity="0.25"/>
                    </svg>
                    <p class="wpfnl-title"><?php esc_html_e('Vertical', 'wpfnl'); ?></p>
                </div>
            </div>
            <!-- <p class="wpfnl-help-text"><?php esc_html_e('Choose the default view for the funnel canvas. You can switch views on the canvas too, but this sets the permanent default.', 'wpfnl'); ?></p> -->
        </div>
    </div>
</div>

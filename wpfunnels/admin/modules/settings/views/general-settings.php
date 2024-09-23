<?php
/**
 * View general settings
 * 
 * @package
 */

$builders = \WPFunnels\Wpfnl_functions::get_supported_builders();
?>
<div class="wpfnl-box">
    <hr>
    <div class="wpfnl-field-wrapper">
        <label><?php esc_html_e('Funnel Type', 'wpfnl'); ?></label>
        <div class="wpfnl-fields">

            <div class="wpfnl-items-wrapper" id="wpfunnels-funnel-type">
                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'lead' ? 'checked' : '' ?>" data-value="lead">
                    <svg fill="none" width="24" height="26" viewBox="0 0 24 26" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M16.827 6.135c-2.694 0-4.878 2.128-4.878 4.752v.242h9.755v-.242c0-2.624-2.183-4.752-4.877-4.752zm0 0c1.392 0 2.521-1.1 2.521-2.456 0-1.357-1.129-2.457-2.521-2.457-1.393 0-2.522 1.1-2.522 2.457 0 1.356 1.129 2.456 2.522 2.456zm-9.756 0c-2.694 0-4.878 2.128-4.878 4.752v.242h9.756v-.242c0-2.624-2.184-4.752-4.878-4.752zm0 0c1.393 0 2.522-1.1 2.522-2.456 0-1.357-1.13-2.457-2.522-2.457-1.393 0-2.522 1.1-2.522 2.457 0 1.356 1.13 2.456 2.522 2.456zM23 11.162H1v3.46h22v-3.46z"/><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M21.021 14.621l-7.246 6.655v2.793l-3.55 1.153v-3.946L2.979 14.62"/></svg>

                    <p class="wpfnl-title"><?php esc_html_e('Lead Funnel', 'wpfnl'); ?></p>
                </div>

                <div class="wpfnl-single-item <?php echo $this->general_settings['funnel_type'] == 'sales' ? 'checked' : '' ?>" data-value="sales">
                    <svg fill="none" width="22" height="26" viewBox="0 0 22 26" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M19.744 10.623l-3.636 5.699m-10.217 0l-3.635-5.699M21 7.636H1v2.987h20V7.636zm-3.845-3.243c1.7 0 3.078 1.381 3.078 3.086v.157h-6.155v-.157a3.082 3.082 0 013.077-3.086z"/><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M17.155 4.393c.879 0 1.591-.714 1.591-1.595 0-.88-.712-1.595-1.59-1.595-.88 0-1.592.714-1.592 1.595s.712 1.595 1.591 1.595zm-6.155 0A3.081 3.081 0 007.922 7.48v.157h6.156V7.48A3.082 3.082 0 0011 4.393zm0 0c.879 0 1.59-.714 1.59-1.595 0-.88-.711-1.595-1.59-1.595-.879 0-1.591.714-1.591 1.595S10.121 4.393 11 4.393z"/><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M4.845 4.393A3.081 3.081 0 001.767 7.48v.157h6.155V7.48a3.082 3.082 0 00-3.077-3.086zm0 0c.878 0 1.59-.714 1.59-1.595 0-.88-.712-1.595-1.59-1.595-.879 0-1.591.714-1.591 1.595s.712 1.595 1.59 1.595zm7.178 12.619s-.403-.372-1.056-.34a1.742 1.742 0 00-.424.073c-.824.249-1.029 1.38-.468 1.82.22.173.517.325.892.479.172.07.36.14.565.213 1.46.517.92 2.58-.565 2.59-.58.004-.85-.033-1.359-.367m1.359 1.12v-.753m0-5.176v-.699"/><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M11 25.203c3.26 0 5.902-2.649 5.902-5.917S14.26 13.37 11 13.37c-3.26 0-5.902 2.65-5.902 5.917 0 3.268 2.642 5.917 5.902 5.917z"/></svg>

                    <p class="wpfnl-title"><?php esc_html_e('Complete Funnel', 'wpfnl'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <!-- /field-wrapper -->

    <hr>
    <div class="wpfnl-field-wrapper">
        <label><?php esc_html_e('Page Builder', 'wpfnl'); ?></label>
        <div class="wpfnl-fields">

            <div class="wpfnl-items-wrapper" id="wpfunnels-page-builder">
                <?php foreach ( $builders as $key => $value ) { ?>
                    <div class="wpfnl-single-item <?php echo $this->general_settings['builder'] == $key ? 'checked' : '' ?>" data-value="<?php echo $key; ?>">
                        <?php if($key == 'gutenberg'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/gutenberg.png' ); ?>" alt="">
                        <?php } ?>

                        <?php if($key == 'elementor'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/elementor.png' ); ?>" alt="">
                        <?php } ?>


                        <?php if($key == 'divi-builder'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/divi.png' ); ?>" alt="">
                        <?php } ?>

                        <?php if($key == 'oxygen'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/oxygen.png' ); ?>" alt="">
                        <?php } ?>

                        <?php if($key == 'bricks'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/bricks.png' ); ?>" alt="">
                        <?php } ?>

                        <?php if($key == 'other'){?>
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/others.png' ); ?>" alt="">
                        <?php } ?>

                        <p class="wpfnl-title"><?php echo $value; ?></p>
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
            <button class="btn-default clear-template" id="clear-template">
                <span class="icon-sync" style="display: inline-block"><?php require WPFNL_DIR . '/admin/partials/icons/sync-icon.php'; ?></span>
                <span class="check-icon"><?php require WPFNL_DIR . '/admin/partials/icons/check-icon.php'; ?></span>
                <?php esc_html_e( 'Sync Templates', 'wpfnl' );?>
            </button>
            <span class="wpfnl-alert"></span>
        </div>
    </div>

    
</div>
<!-- /settings-box -->

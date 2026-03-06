<?php

/**
 * This code snippet will check if pro addons is activated or not. if not activated
 * Total number of funnels will be maximum 3, otherwise customer can add as more funnels
 * As they want
 *
 * @package
 */
$is_pro_active         = apply_filters( 'wpfunnels/is_pro_license_activated', false );
$count_funnels         = wp_count_posts('wpfunnels')->publish + wp_count_posts('wpfunnels')->draft;
$total_allowed_funnels = 3;
$is_limit_reached      = ($count_funnels >= 3);

if ( $is_pro_active ) {
	$total_allowed_funnels = -1;
}

$is_wc = \WPFunnels\Wpfnl_functions::is_wc_active();
$is_lms = \WPFunnels\Wpfnl_functions::is_lms_addon_active();
$is_mint_pro_active = \WPFunnels\Wpfnl_functions::is_mint_mrm_active();
$global_funnel_type = \WPFunnels\Wpfnl_functions::get_global_funnel_type();

$is_wc_installed = 'no';
$is_lms_installed = 'no';
$puglins = get_plugins();

if ( isset( $puglins['woocommerce/woocommerce.php']) ) {
    $is_wc_installed = 'yes';
}

if ( isset( $puglins['wpfunnels-pro-lms/wpfunnels-pro-lms.php']) ) {
    $is_lms_installed = 'yes';
}
$trash_redirect_link = add_query_arg(
    [
        'page' => WPFNL_TRASH_FUNNEL_SLUG,
    ],
    admin_url('admin.php')
);

$live_redirect_link = add_query_arg(
    [
        'page' => WPFNL_FUNNEL_PAGE_SLUG,
    ],
    admin_url('admin.php')
);
?>


<div class="wpfnl">
    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php use WPFunnels\Wpfnl_functions;
            require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
        </nav>

        <div class="dashboard-nav__content">

            <div id="templates-library"></div>

            <div class="import-funnel-modal">
                <div class="import-funnel-modal-inner">
                    <div class="import-funnel-modal-wrapper">
                        <h4 class="import-funnel-modal-title"><?php echo __('Import Funnel','wpfnl') ?></h4>

                        <button class="close-modal"  type="button">
                            <?php require_once WPFNL_DIR . '/admin/partials/icons/cross-icon.php'; ?>
                        </button>

                        <form id="wpfnl-import-funnels" name="form-import" method="post" enctype="multipart/form-data" action >

                            <input id="wpfnl-file-import" name="import-data" type="file" title=" " accept="application/JSON"/>

                            <label for="wpfnl-file-import" class="import-label">
                                <span class="upload-icon">
                                    <svg width="25" height="25" fill="none" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" d="M18.557 8.628a6.242 6.242 0 00-12.11-.007 6.25 6.25 0 00.586 12.472h2.344a.781.781 0 100-1.562H7.033a4.688 4.688 0 01-.027-9.375.81.81 0 00.86-.667 4.68 4.68 0 019.266 0 .844.844 0 00.839.667 4.687 4.687 0 110 9.375h-2.344a.781.781 0 100 1.562h2.344a6.25 6.25 0 00.586-12.465z"/><path fill="#fff" d="M15.852 15.396a.781.781 0 001.105-1.105l-3.906-3.906a.781.781 0 00-1.105 0L8.04 14.291a.781.781 0 001.104 1.105l2.573-2.573v9.833a.781.781 0 101.563 0v-9.833l2.572 2.573z"/></svg>
                                </span>
                                <span class="upload-success-icon">
                                    <svg width="25" height="25" fill="none" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1138_1680)"><path fill="#fff" stroke="#239654" stroke-width="2" d="M24.268 7.675L11.079 20.863a2.503 2.503 0 01-3.538 0L.733 14.054a2.502 2.502 0 013.538-3.538l5.04 5.04 11.418-11.42a2.502 2.502 0 013.539 3.54z"/></g><defs><clipPath id="clip0_1138_1680"><path fill="#fff" d="M0 0h25v25H0z"/></clipPath></defs></svg>
                                </span>

                                <h4><?php echo __('Drag & Drop or ', 'wpfnl'); ?><span class="primary-color"><?php echo __('Choose file ', 'wpfnl'); ?></span> <?php echo __('to upload.', 'wpfnl'); ?></h4>
                                <p><?php echo __('Supported formats: JSON file.', 'wpfnl'); ?></p>
                            </label>

                            <span class="hints" id="wpfnl-export-import-warning" style="display:none; color: #d63638 !important" ><?php echo __('Please select a valid file.', 'wpfnl'); ?></span>

                            <div class="button-area">
                                <button class="btn-default close-modal cancel" type="button">
                                    <?php echo __('Cancel', 'wpfnl'); ?>
                                </button>

                                <button id="wpfnl-import-funnel" class="btn-default" type="submit">
                                    <?php echo __('Import', 'wpfnl'); ?>
                                    <span class="wpfnl-loader"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <div class="wpfnl-dashboard__header overview-header">
                <div class="wpfnl-dashboard-header-left <?php echo 'trash_funnels' == $_GET['page'] ? 'trash-funnels-header' : ''; ?>">
                <?php //if( isset($_GET['page']) && 'wp_funnels' === $_GET['page'] ) : ?>
                    <div class="wpfnl-dashboard-links-wrapper">
                        <a href="<?php echo $live_redirect_link ?>" class="wpfnl-all-funnels <?php echo 'wp_funnels' == $_GET['page'] ? 'active' : ''; ?> ">
                            <span> <?php
                                echo __('Live', 'wpfnl');
                            ?> </span>
                            <span class="wpfnl-count"><?php
                                echo $this->total_live_funnel;
                            ?></span>
                        </a>
                        <a href="<?php echo $trash_redirect_link ?>" class="wpfnl-trash-all-funnels <?php echo 'wp_funnels' !== $_GET['page'] ? 'active' : ''; ?>">
                            <span> <?php
                                    echo __('Trash', 'wpfnl');
                                ?></span>
                            <span class="wpfnl-count"><?php
                                echo $this->total_trash_funnel;
                            ?></span>
                        </a>
                    </div>
                    <?php if (count($this->funnels) || !empty($_GET['s'])) { ?>
                    <form class="funnel-search" method="get">
                        <?php
                            $s = '';
                            if (isset($_GET['s'])) {
                                $s = sanitize_text_field( $_GET['s'] );
                            }
                        ?>

                        <div class="search-group">
                            <input name="page" type="hidden" value="<?php echo 'trash_funnels' != sanitize_text_field( $_GET['page'])  ? WPFNL_FUNNEL_PAGE_SLUG : WPFNL_TRASH_FUNNEL_SLUG; ?>">
                            <?php require_once WPFNL_DIR . '/admin/partials/icons/search-icon.php'; ?>
                            <input name="s" type="text" value="<?php echo esc_attr($s); ?>" placeholder="<?php echo __('Search for a funnel...', 'wpfnl'); ?>">
                        </div>
                    </form>
                    <?php } ?>
                </div>


                <!-- Export import -->
                <?php if ( (count($this->funnels) || !empty($_GET['s'])) &&  isset($_GET['page']) && 'trash_funnels' != sanitize_text_field( $_GET['page'] ) ) : ?>
                    <a href="#" class="import-export wpfnl-export-all-funnels">
                        <?php
                            require WPFNL_DIR . '/admin/partials/icons/export-icon.php';
                            echo __('Export All', 'wpfnl');
                        ?>
                    </a>

                    <a href="#" class="import-export wpfnl-import-funnels">
                        <?php
                            require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                            echo __('Import', 'wpfnl');
                        ?>
                    </a>
                    <?php endif; ?>


                <?php
                    if( (count($this->funnels) || !empty($_GET['s'] )) && isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] ) ){
                ?>
                <?php
                    $classes = 'btn-default add-new-funnel-btn';
                    if ( $is_limit_reached && !$is_pro_active ) {
                        $classes .= ' disabled';
                    }
                ?>

                <a href="#" class="<?php echo esc_attr($classes); ?>">
                    <?php
                        if ( $is_limit_reached && !$is_pro_active ) {
                            require WPFNL_DIR . '/admin/partials/icons/lock-icon.php';
                        } else {
                            require WPFNL_DIR . '/admin/partials/icons/plus-icon.php';
                        }

                        echo esc_html__('Add new Funnel', 'wpfnl');
                    ?>
                </a>

                <?php
                }

                ?>
            </div>
            <?php if ( $is_limit_reached && !$is_pro_active ) : ?>
            <!-- upgrader to pro -->
            <div class="upgrade-to-pro">
                <div class="upgrade-to-pro-wrapper">
                    <div class="warning-icon-wrapper">
                        <span class="warning-icon">
                            <?php
                                require WPFNL_DIR . '/admin/partials/icons/warning-icon.php';
                            ?>
                        </span>
                    </div>
                    <div class="upgrade-to-pro-content">
                        <div class="upgrade-to-pro-message">
                            <h3>You have hit the limit! Upgrade To Pro for Unlimited Funnels!</h3>
                            
                            <p>You are using the free version of WPFunnels which allows you to create up to 3 funnels. To build more funnels, either move one funnel to trash or Upgrade To Pro.</p>
                        </div>
                    </div>
                    <div class="upgrade-to-pro-action">
                        <a href="https://getwpfunnels.com/pricing/" target="_blank" class="btn-upgrade-to-pro">Upgrade to Pro</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="wpfnl-dashboard__inner-content <?php echo count($this->funnels) ? '' : 'no-funnel' ?>">
                <div class="funnel-list__wrapper">
                    <?php if (count($this->funnels)) { ?>
                        <div class="funnel__single-list list-header">
                            <div class="bulk-action-wrapper">
                                <p>
                                    <span class="selected-funnel-count"><?php echo __('2 Funnel', 'wpfnl')?></span>
                                    <?php echo __('Seleted', 'wpfnl')?>
                                </p>

                                <button class="btn-default bulk-delete-toggler">
                                    <?php echo __('Bulk Actions', 'wpfnl'); ?>
                                    <svg width="8" height="6" fill="none" viewBox="0 0 8 6" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" stroke="#fff" stroke-width=".2" d="M4 5.28a.559.559 0 01-.396-.164l-3.44-3.44A.56.56 0 11.956.884L4 3.928 7.044.884a.56.56 0 01.792.792l-3.44 3.44A.559.559 0 014 5.28z"/></svg>

                                    <?php if ( isset($_GET['page']) && 'trash_funnels' === sanitize_text_field( $_GET['page'] ) ) { ?>
                                        <ul class="wpfnl-dropdown">

                                            <li>
                                                <a href="#" class="delete wpfnl-bulk-restore" id="funnel__bulk-restore" title="Restore Funnel">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/restore-icon.php'; ?>
                                                    <?php echo __('Restore', 'wpfnl'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="delete wpfnl-bulk-delete" id="funnel__bulk-delete" title="Delete Funnel">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
                                                    <?php echo __('Delete', 'wpfnl'); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    <?php
                                        }else{
                                    ?>
                                        <ul class="wpfnl-dropdown">
                                            <?php if ($is_pro_active && defined('WPFNL_PRO_VERSION') && version_compare( WPFNL_PRO_VERSION, "1.9.3", ">=" ) ) { ?>
                                                <li>
                                                    <a href="#" class="wpfnl-bulk-export">
                                                        <?php require WPFNL_DIR . '/admin/partials/icons/export-icon.php'; ?>
                                                        <?php echo __('Bulk Export', 'wpfnl'); ?>
                                                    </a>
                                                </li>
                                            <?php
                                            }
                                            ?>
                                            <li>
                                                <a href="#" class="delete wpfnl-bulk-trash" id="funnel__bulk-trash" title="Trash Funnel">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/trash-icon.php'; ?>
                                                    <?php echo __('Trash', 'wpfnl'); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    <?php
                                        }
                                    ?>
                                </button>
                            </div>

                            <div class="funnel-list__bulk-action">
                                <?php
                                    if (count($this->funnels) > 0) {
                                    ?>
                                        <div class="funnel-list__bulk-select select-all-funnels" >
                                            <span class="wpfnl-checkbox no-title">
                                                <input type="checkbox" name="funnel-list__bulk-select" id="funnel-list__bulk-select">
                                                <label for="funnel-list__bulk-select"></label>
                                            </span>
                                        </div>
                                    <?php
                                    }
                                ?>
                            </div>
                            <div class="list-cell wpfnl-name"><?php echo __('Name', 'wpfnl'); ?></div>
                            <?php if($is_pro_active){ ?>
                                <div class="list-cell wpfnl-intigrations"><?php echo __('Integration', 'wpfnl'); ?></div>
                            <?php } ?>
                            <div class="list-cell wpfnl-creation-date"><?php echo __('Creation Date', 'wpfnl'); ?></div>
                            <div class="list-cell wpfnl-status"><?php echo __('Status', 'wpfnl'); ?></div>
                            <div class="list-cell list-action"><?php echo __('Action', 'wpfnl'); ?></div>
                        </div>

                        <?php
                        foreach ($this->funnels as $funnel) {
                            $funnel_id = $funnel->get_id();
                            $edit_link = add_query_arg(
                                [
                                    'page' => WPFNL_EDIT_FUNNEL_SLUG,
                                    'id' => $funnel_id,
                                    'step_id' => $funnel->get_first_step_id(),
                                ],
                                admin_url('admin.php')
                            );
                            $isAutomationEnable = get_post_meta( $funnel_id, 'is_automation_enabled', true );
							$isAutomationData 	= get_post_meta( $funnel_id,'funnel_automation_data',true);
                            $isGbfInstalled 	= is_plugin_active( 'wpfunnels-pro-gbf/wpfnl-pro-gb.php' );
                            $start_condition 	= get_post_meta( $funnel_id, 'global_funnel_start_condition', true );
                            $builder 			= Wpfnl_functions::get_page_builder_by_step_id($funnel_id);
                            $utm_settings 		= Wpfnl_functions::get_funnel_utm_settings( $funnel_id );
                            $is_mint_automation = Wpfnl_functions::maybe_automation_exist_for_a_funnel( $funnel_id );
                            $funnel_status      = 'publish' === get_post_status( $funnel_id ) ? 'Draft': 'Publish';
                            $isGbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
                            $_type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );

                            if( 'lead' == $_type ){
                                $funnel_type = __('Lead', 'wpfnl');
                            }elseif( 'lms' == $_type ){
                                $funnel_type = __('LMS', 'wpfnl');
                            }else{
                                if( defined('WC_PLUGIN_FILE') ){
                                    $funnel_type = __('Woo', 'wpfnl');
                                    $_type = 'wc';
                                }else{
                                    $funnel_type = __('Lead', 'wpfnl');
                                    $_type = 'lead';
                                }
                            }
                            Wpfnl_functions::generate_first_step( $funnel_id );
                            $first_step_id = Wpfnl_functions::get_first_step( $funnel_id );

                            // Fallback for existing users' existing funnel
                            // For new funnel, this condition should not trigger
                            if( !$first_step_id ) {
                                Wpfnl_functions::generate_first_step( $funnel_id );
                                $first_step_id = Wpfnl_functions::get_first_step( $funnel_id );
                            }

                            if ($first_step_id) {
                                $view_link = apply_filters( 'wpfunnels/modify_funnel_view_link', get_the_permalink( $first_step_id ), $first_step_id, $funnel_id );

                            } else {
                                $view_link = '#';
                            }

                            if($utm_settings['utm_enable'] == 'on') {
                                unset($utm_settings['utm_enable']);
                                $view_link = add_query_arg($utm_settings,$view_link);
                                $view_link   = strtolower($view_link);
                            }
							if( 'lead' == $global_funnel_type && ( 'lms' == $_type  || 'wc' == $_type ) ){
								echo '<div class="funnel__single-list list-body funnel-disabled" title="'.__('To run/edit this funnel, please change the funnel type to sales from WPFunnels - Settings', 'wpfnl').'">';
							}elseif( 'sales' == $global_funnel_type && 'wc' == $_type &&  !$is_wc ){
								echo '<div class="funnel__single-list list-body funnel-disabled" title="'.__('To run/edit this funnel, please Activate WooCommerce.', 'wpfnl').'">';
							}elseif( 'sales' == $global_funnel_type && 'lms' == $_type &&  !$is_lms ){
								echo '<div class="funnel__single-list list-body funnel-disabled" title="'.__('To run/edit this funnel, please Activate LearnDash & WPFunnels Pro - LMS Funnel', 'wpfnl').'">';
							}else{
								echo '<div class="funnel__single-list list-body">';
							}
							?>
                                <div class="funnel-list__bulk-action">
                                    <span class="wpfnl-checkbox no-title">
                                        <input type="checkbox" name="funnel-list-select" id="funnel-list<?php echo $funnel->get_id(); ?>-select" data-id="<?php echo $funnel->get_id(); ?>">
                                        <label for="funnel-list<?php echo $funnel->get_id(); ?>-select"></label>
                                    </span>
                                </div>

                                <div class="list-cell wpfnl-name">
                                    <?php if( $builder ){ ?>
                                        <span class="builder-logo" title="<?php echo str_replace('-',' ',ucfirst($builder));?>">
                                            <?php include WPFNL_DIR . '/admin/partials/icons/'.$builder.'.php'; ?>
                                        </span>

                                    <?php } else{ ?>
                                        <span class="builder-logo" title="<?php echo __('No Builder Found', 'wpfnl') ?>">
                                        </span>
                                    <?php } ?>
                                    <?php if( ('lead' == $global_funnel_type && 'lead' !== $_type) || ( 'sales' == $global_funnel_type && ( ('wc' == $_type && !$is_wc) || ('lms' == $_type && !$is_lms) ) ) ){ ?>
                                        <a href="#" class="name disabled"> <?php echo $funnel->get_funnel_name() ?></a>
                                    <?php }else{
										$edit_funnel_url = isset($_GET['page']) && 'trash_funnels' === sanitize_text_field( $_GET['page'] ) ? '#' : esc_url_raw($edit_link);
										?>
                                        <a href="<?php echo $edit_funnel_url; ?>" class="name"> <?php echo $funnel->get_funnel_name() ?></a>
                                    <?php } ?>

                                    <span class="steps">
                                        <?php echo $funnel->get_total_steps(). ' '. Wpfnl_functions::get_formatted_data_with_phrase($funnel->get_total_steps(), 'step', 'steps'); ?> <?php echo ' - '.$funnel_type ?>
                                    </span>

                                </div>

                                <?php if($is_pro_active){ ?>
                                    <div class="list-cell wpfnl-intigrations">
                                        <?php if ( $is_pro_active && $is_mint_pro_active ) { ?>
                                            <?php if( !empty($is_mint_automation) ) { ?>
                                                <span class="automation-tag automation-active">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-green.php'; ?>
                                                <?php echo __('Mail Mint','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Mail mint automation is created for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php }else{ ?>
                                                <span class="automation-tag automation-inactive">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-gray.php'; ?>
                                                <?php echo __('Mail Mint','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Mail mint automation is not created for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php  } ?>
                                        <?php } ?>

                                        <?php if ($is_pro_active ) { ?>
                                            <?php if( !empty($isAutomationData) ) { ?>
                                                <span class="automation-tag automation-active">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-green.php'; ?>
                                                <?php echo __('Integration','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Integration is set for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php }else{ ?>
                                                <span class="automation-tag automation-inactive">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-gray.php'; ?>
                                                <?php echo __('Integration','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Integration is not set for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php  } ?>

                                        <?php } ?>

                                        <?php if ($isGbfInstalled) { ?>
                                            <?php if( $isGbf == 'yes' && !empty($start_condition) ) { ?>
                                                <span class="automation-tag automation-active">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-green.php'; ?>
                                                <?php echo __('Global funnel','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Global funnel is set for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php } elseif( $isGbf == 'yes' && !$start_condition ){ ?>
                                                <span class="automation-tag automation-inactive">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-warning.php'; ?>
                                                <?php echo __('Global funnel','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Opps.. It looks like you did not set any condition for global funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php } else{ ?>
                                                <span class="automation-tag automation-inactive">
                                                <?php include WPFNL_DIR . '/admin/partials/icons/success-icon-gray.php'; ?>
                                                <?php echo __('Global funnel','wpfnl') ?>
                                                <span class="tooltip"><?php echo __('Global funnel is not set for this funnel.','wpfnl') ?></span>
                                            </span>
                                            <?php  } ?>
                                        <?php } ?>

                                        <?php if(WPFNL_IS_REMOTE) {?>
                                            <div class="builder-type">
                                                <?php
                                                $builders = wp_get_post_terms( $funnel->get_id(), 'template_builder', array( 'fields' => 'all' ) );
                                                if($builders) {
                                                    foreach ($builders as $builder) {
                                                        echo "<span>{$builder->name}</span>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="list-cell wpfnl-creation-date">
                                    <span class="post-date"><?php echo $funnel->get_published_date() ?></span>
                                </div>

                                <div class="list-cell wpfnl-status <?php echo strtolower($funnel->get_status()) ?>">
                                    <span class="post-status"><?php echo $funnel->get_status() ?></span>
                                </div>

                                <div class="list-cell list-action">
                                    <?php
                                        if( isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] ) ){
                                    ?>
                                    <?php if( ('lead' == $global_funnel_type && 'lead' !== $_type) || ( 'sales' == $global_funnel_type && ( ('wc' == $_type && !$is_wc) || ('lms' == $_type && !$is_lms) ) ) ){ ?>
                                        <a href="#" class="edit disabled" title="<?php esc_attr_e( 'Lead funnel type is activated in global settings', 'wpfnl' ) ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/edit-icon.php'; ?>
                                        </a>
                                    <?php }else{ ?>
                                        <a href="<?php echo esc_url_raw($edit_link); ?>" class="edit" title="<?php esc_attr_e('Edit', 'wpfnl'); ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/edit-icon.php'; ?>

                                        </a>
                                    <?php } ?>

									<?php
										$disable_view_button = apply_filters( 'wpfunnels/disable_funnel_view_button', false, $funnel_id );

                                        if( ('lead' == $global_funnel_type && 'lead' !== $_type) || ( 'sales' == $global_funnel_type && ( ('wc' == $_type && !$is_wc) || ('lms' == $_type && !$is_lms) ) ) ){ ?>
                                            <a class="view <?php echo 'disabled'; ?>" title="<?php esc_attr_e( 'Lead funnel type is activated in global settings', 'wpfnl' ) ?>">
                                                <?php require WPFNL_DIR . '/admin/partials/icons/eye-icon.php'; ?>
                                            </a>
                                            <?php
                                        }elseif( $disable_view_button ){
                                            ?>
                                            <a class="view <?php echo $disable_view_button ? 'disabled' : ''; ?>" title="<?php esc_attr_e( 'This is a Global Funnel', 'wpfnl' ) ?>">
                                                <?php require WPFNL_DIR . '/admin/partials/icons/eye-icon.php'; ?>
                                            </a>
                                            <?php
                                        }else{
                                            ?>
                                            <a href="<?php echo esc_url_raw($view_link); ?>" class="view <?php echo $disable_view_button ? 'disabled' : ''; ?>" target="_blank" title="<?php esc_attr_e( 'View', 'wpfnl' ) ?>">
                                                <?php require WPFNL_DIR . '/admin/partials/icons/eye-icon.php'; ?>
                                            </a>
                                            <?php
                                        }
                                    }else{
                                    ?>
                                        <a href="#" class="restore wpfnl-restore-funnel" id="wpfnl-restore-funnel" title="<?php esc_attr_e('Restore funnel', 'wpfnl'); ?>" data-id="<?php echo $funnel_id; ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/restore-icon.php'; ?>
                                        </a>
                                        <a href="#" class="delete wpfnl-permanent-delete-funnel" id="wpfnl-permanent-delete-funnel" title="<?php esc_attr_e('Delete Permanently', 'wpfnl'); ?>" data-id="<?php echo $funnel_id; ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
                                        </a>
                                        <?php
                                    }
									?>
                                    <?php
                                        if( isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] ) ){
                                    ?>
                                    <span class="more-action funnel-list__more-action" >
                                        <?php require WPFNL_DIR . '/admin/partials/icons/dot-icon.php'; ?>

                                        <ul class="more-actions wpfnl-dropdown">
											<?php if(( $is_pro_active || $count_funnels < 3 ) && isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] ) ): ?>
												<li>
													<a href="#" class="duplicate wpfnl-duplicate-funnel" id="wpfnl-duplicate-funnel" data-id="<?php echo $funnel_id; ?>">
														<?php require WPFNL_DIR . '/admin/partials/icons/duplicate-icon.php'; ?>
														<?php echo __('Duplicate', 'wpfnl'); ?>
														<span class="wpfnl-loader"></span>
													</a>
												</li>
											<?php endif; ?>

                                            <?php if( isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] )): ?>
												<li>
													<a href="#" class="duplicate wpfnl-export-funnel" id="wpfnl-export-funnel" data-id="
                                                        <?php
                                                            echo $funnel_id;
                                                        ?>
                                                    ">
														<?php
                                                            require WPFNL_DIR . '/admin/partials/icons/export-icon.php';
                                                            echo __('Export', 'wpfnl');
                                                        ?>
														<span class="wpfnl-loader"></span>
													</a>
												</li>
											<?php endif; ?>

                                            <?php if( isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] )): ?>
                                            <li>
                                                <a href="" class="delete wpfnl-update-funnel-status" id="wpfnl-update-funnel-status" data-id="<?php echo $funnel_id; ?>" data-status="<?php echo strtolower($funnel_status); ?>">
                                                    <?php
                                                        if( 'draft' === strtolower($funnel_status) ){
                                                            require WPFNL_DIR . '/admin/partials/icons/draft-icon.php';
                                                        }else{
                                                            require WPFNL_DIR . '/admin/partials/icons/publish-icon.php';
                                                        }
                                                    ?>
                                                    <?php echo $funnel_status; ?>
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            <li>
                                                <?php
                                                    if( isset($_GET['page']) && 'trash_funnels' == sanitize_text_field( $_GET['page'] ) ){
                                                ?>
                                                <a href="" class="delete wpfnl-restore-funnel" id="wpfnl-restore-funnel" data-id="<?php echo $funnel_id; ?>">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/restore-icon.php'; ?>
                                                    <?php echo __('Restore', 'wpfnl'); ?>
                                                </a>
                                                <?php
                                                    }else{
                                                ?>
                                                 <a href="" class="delete wpfnl-delete-funnel" id="wpfnl-delete-funnel" data-id="<?php echo $funnel_id; ?>">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/trash-icon.php'; ?>
                                                    <?php echo __('Trash', 'wpfnl'); ?>
                                                </a>
                                                <?php
                                                    }
                                                ?>
                                            </li>

                                            <?php if( isset($_GET['page']) && 'trash_funnels' == sanitize_text_field( $_GET['page'] ) ): ?>
                                                <li>
                                                    <a href="" class="delete wpfnl-delete-funnel" id="wpfnl-delete-funnel" data-id="<?php echo $funnel_id; ?>">
                                                        <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
                                                        <?php echo __('Delete Parmanently', 'wpfnl'); ?>
                                                    </a>
                                                </li>
											<?php endif; ?>

                                        </ul>
                                    </span>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <!-- /list-action -->


                                <?php
                                // Show activation hint only if:
                                // 1. There's exactly one funnel
                                // 2. The funnel status is Draft
                                // 3. More than 24 hours have passed since creation
                                $should_show_hint = false;
                                if ( count($this->funnels) === 1 && 'draft' === get_post_status( $funnel_id ) ) {
                                    // Get the funnel creation time
                                    $funnel_post = get_post( $funnel_id );
                                    $funnel_creation_time = strtotime( $funnel_post->post_date );
                                    $current_time = current_time( 'timestamp' );
                                    $time_difference = $current_time - $funnel_creation_time;
                                    $hours_passed = $time_difference / 3600;
                                    
                                    if ( $hours_passed >= 24 ) {
                                        $should_show_hint = true;
                                    }
                                }
                                
                                if ( $should_show_hint ) {
                                ?>
                                <div class="funnel-alert">
                                    <div class="funnel-alert-inner">

                                        <div class="funnel-alert-left">
                                            <div class="funnel-icon">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                                            </div>

                                            <div class="funnel-alert-content">
                                                <p class="funnel-alert-title">
                                                    <?php echo __('This funnel is not published yet.', 'wpfnl'); ?>
                                                </p>
                                                <p class="funnel-alert-description">
                                                    <?php echo __('Publish to go live — start boosting revenue with upsells, bumps & higher AOV today.', 'wpfnl'); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <button type="button" class="funnel-activate-btn wpfnl-update-funnel-status" data-id="<?php echo $funnel_id; ?>" data-status="<?php echo strtolower($funnel_status); ?>" title="<?php echo __('Click to Publish', 'wpfnl'); ?>">
                                            <?php echo __('Click to Publish', 'wpfnl'); ?>
                                        </button>

                                    </div>
                                </div>

                                <?php } ?>

                            </div>
                            <?php
                        } //--end foreach--
                    } else {
                        if (isset($_GET['s'])) {
                            echo __('Sorry No Funnels Found', 'wpfnl');
                        } else {
                            $create_funnel_link = add_query_arg(
                                [
                                    'page' => WPFNL_CREATE_FUNNEL_SLUG,
                                ],
                                admin_url('admin.php')
                            ); ?>

                            <?php if( isset($_GET['page']) && 'wp_funnels' === $_GET['page'] ) {?>
                                <div class="no-funnel-wrapper">
                                <?php require WPFNL_DIR . '/admin/partials/icons/no-funnels-icon.php'; ?>
                                    <h1><?php echo __('Funnels', 'wpfnl'); ?></h1>
                                    <p class="short-desc"><?php echo __('Convert More Visitors into Customers: A Step-by-Step Funnel Blueprint', 'wpfnl'); ?></p>

                                    <div class="create-new-funnel">
                                        <a href="#" class="btn-default add-new-funnel-btn">
                                            <svg width="15" height="15" fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.723 1.387v11.986M1.717 7.38h12.008"/></svg>

                                            <?php echo __('Create Your First Funnel', 'wpfnl'); ?>
                                        </a>
                                        <a href="#" class="btn-default import-export wpfnl-import-funnels">
                                            <?php
                                                require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                                                echo __('Import Funnels', 'wpfnl');

                                            ?>
                                        </a>
                                    </div>
                                </div>
                            <?php }else{
                                ?>
                                <div class="no-trash-wrapper">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/no-data-icon.php'; ?>
                                    <p class="no-funnel"><?php echo __('No data Found', 'wpfnl'); ?></p>
                                </div>
                                <?php
                            } ?>

                            <!-- <div class="wpfnl-help-guide">
                                <button class="setup-guide" type="button" title="<?php //esc_attr_e( 'Setup Guide', 'wpfnl' ) ?>">
                                    <?php
                                        //require WPFNL_DIR . '/admin/partials/icons/setup-guide-icon.php';
                                        //echo __('Setup Guide', 'wpfnl');
                                    ?>
                                </button>

                                <div class="wpfnl-canvas-helper">
                                    <button class="helper-btn wpfnl-helper-btn" type="button" title="<?php //esc_attr_e( 'Help & Resources', 'wpfnl' ) ?>">
                                        <?php require WPFNL_DIR . '/admin/partials/icons/question-mark-icon.php'; ?>
                                    </button>

                                    <div class="help-resource" v-if="showHelperResource">
                                        <a href="" class="single-menu" target="_blank"><?php //__( 'YouTube Video', 'wpfnl' ) ?></a>
                                        <a href="" class="single-menu" target="_blank"><?php //__( 'Documantation', 'wpfnl' ) ?></a>
                                        <a href="" class="single-menu" target="_blank"><?php //__( 'Blog', 'wpfnl' ) ?></a>
                                    </div>
                                </div>
                            </div> -->

                            <?php
                        }
                    } ?>

                    <!-- funnel pagination -->
                    <?php if ($this->pagination) { ?>
                        <div class="list-footer">
                            <div class="pagination-number">
                                <p>
                                    <strong><?php
                                        echo __('Showing', 'wpfnl');
                                    ?></strong>
                                    <select name="wpfnl_listing_page_offset" id="wpfnl_listing_page_offset">
                                        <option value="10" <?php echo 10 === (int)$per_page ? 'selected' : ''?> >
                                            <?php echo __('10', 'wpfnl');  ?>
                                        </option>
                                        <option value="20" <?php echo 20 === (int)$per_page ? 'selected' : ''?>>
                                            <?php echo __('20', 'wpfnl');  ?>
                                        </option>
                                        <option value="30" <?php echo 30 === (int)$per_page ? 'selected' : ''?>>
                                            <?php echo __('30', 'wpfnl');  ?>
                                        </option>
                                    </select>
                                    <?php
                                    $limit_starts = $this->offset+1;
                                    $limit_ends = min( [ $this->offset+$per_page, $this->total_funnels ] );
                                    echo "{$limit_starts}-{$limit_ends} of {$this->total_funnels} ". __('items', 'wpfnl')
                                    ?>
                                </p>
                            </div>

                            <div class="pagination">
                                <?php
                                $s = '';
                                if (isset($_GET['s'])) {
                                    $s = '&s='. sanitize_text_field($_GET['s']);
                                } ?>

                                <div class="wpfnl-pagination">
                                    <a href="<?php if ($this->current_page <= 1) {
                                        echo '#';
                                    } else {
                                        echo "?page=wp_funnels&pageno=".($this->current_page - 1).$s."&per_page={$per_page}";
                                    } ?>" class="nav-link prev <?php if ($this->current_page <= 1) {
                                        echo 'disabled';
                                    } ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M6.002 12a.856.856 0 01-.609-.25L.25 6.586a.863.863 0 010-1.214L5.393.207a.855.855 0 011.415.62.863.863 0 01-.206.594L2.067 5.974l4.535 4.554a.862.862 0 01-.6 1.472z"/><path fill="#7A8B9A" d="M11.147 12a.856.856 0 01-.61-.25L5.395 6.586a.863.863 0 010-1.214L10.538.207a.855.855 0 011.414.62.862.862 0 01-.205.594L7.21 5.974l4.536 4.554a.862.862 0 01-.6 1.472z"/></svg>
                                    </a>

                                    <?php
                                    for ($i = 1; $i <= $this->total_page; $i ++) {
                                        if ($i < 1) {
                                            continue;
                                        }
                                        if ($i > $this->total_funnels) {
                                            break;
                                        }
                                        if ($i == $this->current_page) {
                                            $class = "active";
                                        } else {
                                            $class = "";
                                        } ?>
                                        <a href="?page=wp_funnels&pageno=<?php echo $i.$s."&per_page={$per_page}"; ?>" class="nav-link <?php echo $class; ?>"><?php echo $i; ?></a>
                                        <?php
                                    } ?>

                                    <a href="<?php if ($this->current_page == $this->total_page) {
                                        echo '#';
                                    } else {
                                        echo "?page=wp_funnels&pageno=".($this->current_page + 1)."&per_page={$per_page}";
                                    } ?>" class="nav-link next <?php if ($this->current_page >= $this->total_funnels) {
                                        echo 'disabled';
                                    } ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M5.998 12a.856.856 0 00.609-.25l5.144-5.164a.863.863 0 000-1.214L6.607.207a.855.855 0 00-1.415.62.863.863 0 00.206.594l4.535 4.553-4.535 4.554a.862.862 0 00.6 1.472z"/><path fill="#7A8B9A" d="M.853 12a.856.856 0 00.61-.25l5.143-5.164a.863.863 0 000-1.214L1.462.207a.855.855 0 00-1.414.62.863.863 0 00.205.594L4.79 5.974.253 10.528A.862.862 0 00.853 12z"/></svg>
                                    </a>
                                </div>

                            </div>
                        </div>
                    <?php } ?>

                </div>
                <!-- /funnel-list__wrapper -->

            </div>
            
            <!-- Toaster Starts-->
            <div id="wpfnl-toaster-wrapper">
                <div class="quick-toastify-alert-toast">
                    <div class="quick-toastify-alert-container">
                        <div class="quick-toastify-successfull-icon" id="wpfnl-toaster-icon"></div>
                        <p id="wpfnl-toaster-message"></p>
                        <div class="quick-toastify-cross-icon" id="wpfnl-toaster-close-btn">
                            <svg width="10" height="10" fill="none" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#686f7f" d="M.948 9.995a.94.94 0 01-.673-.23.966.966 0 010-1.352L8.317.278a.94.94 0 011.339.045c.323.35.342.887.044 1.258L1.611 9.765a.94.94 0 01-.663.23z" />
                                <path fill="#686f7f" d="M8.98 9.995a.942.942 0 01-.664-.278L.275 1.582A.966.966 0 01.378.23a.939.939 0 011.232 0L9.7 8.366a.966.966 0 010 1.399.94.94 0 01-.72.23z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Toaster End -->

            <!-- Pro Modal -->
            <div class="wpfnl-pro-modal-overlay" id="wpfnl-pro-modal">
                <div class="wpfnl-pro-modal-wrapper">
                    <div class="wpfnl-pro-modal-close">
                        <span class="wpfnl-pro-modal-close-btn" id="wpfnl-pro-modal-close">
                            <?php require WPFNL_DIR . '/admin/partials/icons/cross-icon.php'; ?>
                        </span>
                    </div>

                    <div class="wpfnl-pro-modal-content">
                        <div class="wpfnl-pro-modal-header">
                            <span class="wpfnl-pro-modal-header-icon">
                                <?php require WPFNL_DIR . '/admin/partials/icons/unlock-icon.php'; ?>
                            </span>
                            <h3 class="wpfnl-pro-heading">Unlock Advanced WooCommerce Funnel</h3>
                        </div>

                        <div class="wpfnl-pro-modal-body">
                            <div class="wpfnl-pro-modal-body_container">
                                <h4 class="feature-title">Features</h4>

                                <ul class="wpfnl-pro-features first-col">
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4.59954 0.0999756H2.34958C1.10887 0.0999756 0.0996094 1.10922 0.0996094 2.34991V4.59984C0.0996094 5.84053 1.10887 6.84977 2.34958 6.84977H4.59954C5.84025 6.84977 6.84951 5.84053 6.84951 4.59984V2.34991C6.84951 1.10922 5.84025 0.0999756 4.59954 0.0999756ZM5.34953 4.59984C5.34953 5.01328 5.01335 5.34982 4.59954 5.34982H2.34958C1.93577 5.34982 1.59959 5.01328 1.59959 4.59984V2.34991C1.59959 1.93647 1.93577 1.59993 2.34958 1.59993H4.59954C5.01335 1.59993 5.34953 1.93647 5.34953 2.34991V4.59984Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M12.8495 0.0999756H10.5996C9.35887 0.0999756 8.34961 1.10922 8.34961 2.34991V4.59984C8.34961 5.84053 9.35887 6.84977 10.5996 6.84977H12.8495C14.0902 6.84977 15.0995 5.84053 15.0995 4.59984V2.34991C15.0995 1.10922 14.0902 0.0999756 12.8495 0.0999756ZM13.5995 4.59984C13.5995 5.01328 13.2634 5.34982 12.8495 5.34982H10.5996C10.1858 5.34982 9.84959 5.01328 9.84959 4.59984V2.34991C9.84959 1.93647 10.1858 1.59993 10.5996 1.59993H12.8495C13.2634 1.59993 13.5995 1.93647 13.5995 2.34991V4.59984Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M4.59954 8.35016H2.34958C1.10887 8.35016 0.0996094 9.3594 0.0996094 10.6001V12.85C0.0996094 14.0907 1.10887 15.1 2.34958 15.1H4.59954C5.84025 15.1 6.84951 14.0907 6.84951 12.85V10.6001C6.84951 9.3594 5.84025 8.35016 4.59954 8.35016ZM5.34953 12.85C5.34953 13.2635 5.01335 13.6 4.59954 13.6H2.34958C1.93577 13.6 1.59959 13.2635 1.59959 12.85V10.6001C1.59959 10.1866 1.93577 9.85011 2.34958 9.85011H4.59954C5.01335 9.85011 5.34953 10.1866 5.34953 10.6001V12.85Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M12.8495 8.35016H10.5996C9.35887 8.35016 8.34961 9.3594 8.34961 10.6001V12.85C8.34961 14.0907 9.35887 15.1 10.5996 15.1H12.8495C14.0902 15.1 15.0995 14.0907 15.0995 12.85V10.6001C15.0995 9.3594 14.0902 8.35016 12.8495 8.35016ZM13.5995 12.85C13.5995 13.2635 13.2634 13.6 12.8495 13.6H10.5996C10.1858 13.6 9.84959 13.2635 9.84959 12.85V10.6001C9.84959 10.1866 10.1858 9.85011 10.5996 9.85011H12.8495C13.2634 9.85011 13.5995 10.1866 13.5995 10.6001V12.85Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/></svg>
                                        Unlimited Funnels
                                    </li>

                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M3.70086 11.7798L2.05474 13.4265C1.52539 13.956 1.52539 14.8145 2.05474 15.3441C2.5841 15.8736 3.44237 15.8736 3.97172 15.3441L5.61784 13.6974L6.56106 15.8822C6.62701 16.0349 6.72237 16.178 6.84717 16.3028C7.37653 16.8324 8.2348 16.8324 8.76415 16.3028C8.9442 16.1227 9.06301 15.9046 9.12058 15.6742L11.0376 8.00391C11.1493 7.55702 11.0304 7.06438 10.6811 6.71496C10.3318 6.36553 9.83933 6.24669 9.3926 6.35841L1.72468 8.27601C1.49441 8.3336 1.27631 8.45245 1.09624 8.63255C0.56688 9.16207 0.56688 10.0206 1.09624 10.5501C1.22101 10.675 1.36408 10.7703 1.51678 10.8363L3.70086 11.7798Z" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.9883 7.1239H16.6993" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.7969 10.0002L14.7139 11.9178" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.43945 2.68567L7.35643 4.60326" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.2324 0.700012V3.41187" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.0244 2.68567L13.1074 4.60326" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Advanced One-Click Upsells
                                    </li>

                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.99845 13.7986H2.79479C2.60888 13.7985 2.43063 13.7222 2.29922 13.5865C2.16781 13.4508 2.094 13.2668 2.094 13.0749V5.89545C2.094 5.74949 2.03781 5.6095 1.93779 5.50629C1.83777 5.40308 1.70212 5.34509 1.56067 5.34509C1.41922 5.34509 1.28357 5.40308 1.18355 5.50629C1.08353 5.6095 1.02734 5.74949 1.02734 5.89545V13.0749C1.0272 13.3145 1.07282 13.5518 1.16159 13.7731C1.25035 13.9945 1.38053 14.1957 1.54467 14.3651C1.70881 14.5346 1.90371 14.6689 2.11821 14.7606C2.33271 14.8523 2.56262 14.8994 2.79479 14.8994H7.99845C8.13989 14.8994 8.27555 14.8414 8.37557 14.7382C8.47558 14.6349 8.53177 14.495 8.53177 14.349C8.53177 14.203 8.47558 14.063 8.37557 13.9598C8.27555 13.8566 8.13989 13.7986 7.99845 13.7986Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13.371 7.42987C13.371 7.57584 13.4272 7.71582 13.5272 7.81903C13.6272 7.92225 13.7629 7.98023 13.9043 7.98023C14.0458 7.98023 14.1814 7.92225 14.2815 7.81903C14.3815 7.71582 14.4377 7.57584 14.4377 7.42987V4.28511H14.9331C15.0746 4.28511 15.2102 4.22712 15.3102 4.12391C15.4103 4.0207 15.4665 3.88071 15.4665 3.73475V1.61201C15.4665 1.18448 15.3019 0.774456 15.0089 0.472146C14.716 0.169836 14.3186 0 13.9043 0H1.56211C1.14781 0 0.750485 0.169836 0.457532 0.472146C0.164579 0.774456 0 1.18448 0 1.61201V3.73475C0 3.88071 0.0561895 4.0207 0.156208 4.12391C0.256226 4.22712 0.391879 4.28511 0.533326 4.28511H13.371V7.42987ZM1.06665 3.18439V1.61201C1.06665 1.47641 1.11885 1.34636 1.21177 1.25047C1.30469 1.15459 1.43071 1.10072 1.56211 1.10072H13.9043C14.0357 1.10072 14.1618 1.15459 14.2547 1.25047C14.3476 1.34636 14.3998 1.47641 14.3998 1.61201V3.18439H1.06665Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3908 8.55042C11.6769 8.55042 10.979 8.76887 10.3854 9.17816C9.79186 9.58745 9.32921 10.1692 9.05601 10.8498C8.78281 11.5304 8.71133 12.2794 8.85061 13.0019C8.98988 13.7245 9.33366 14.3882 9.83846 14.9091C10.3433 15.43 10.9864 15.7848 11.6866 15.9285C12.3868 16.0722 13.1126 15.9985 13.7721 15.7166C14.4317 15.4346 14.9954 14.9572 15.392 14.3447C15.7887 13.7321 16.0004 13.012 16.0004 12.2753C15.9998 11.2875 15.6193 10.3405 14.9425 9.64204C14.2657 8.94362 13.3479 8.551 12.3908 8.55042ZM12.3908 14.8994C11.8879 14.8994 11.3962 14.7455 10.978 14.4571C10.5599 14.1688 10.2339 13.759 10.0415 13.2795C9.849 12.8 9.79865 12.2723 9.89676 11.7633C9.99488 11.2543 10.2371 10.7867 10.5927 10.4197C10.9483 10.0527 11.4014 9.80281 11.8947 9.70156C12.388 9.60031 12.8993 9.65227 13.3639 9.85089C13.8286 10.0495 14.2257 10.3858 14.5051 10.8174C14.7846 11.2489 14.9337 11.7563 14.9337 12.2753C14.9333 12.9711 14.6652 13.6383 14.1884 14.1303C13.7116 14.6223 13.0651 14.8989 12.3908 14.8994Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.923 11.7249V10.7788C12.923 10.6329 12.8668 10.4929 12.7668 10.3897C12.6668 10.2864 12.5311 10.2285 12.3897 10.2285C12.2482 10.2285 12.1126 10.2864 12.0126 10.3897C11.9126 10.4929 11.8564 10.6329 11.8564 10.7788V11.7249H10.9396C10.7981 11.7249 10.6625 11.7829 10.5625 11.8861C10.4624 11.9893 10.4062 12.1293 10.4062 12.2752C10.4062 12.4212 10.4624 12.5612 10.5625 12.6644C10.6625 12.7676 10.7981 12.8256 10.9396 12.8256H11.8564V13.7717C11.8564 13.9176 11.9126 14.0576 12.0126 14.1608C12.1126 14.2641 12.2482 14.322 12.3897 14.322C12.5311 14.322 12.6668 14.2641 12.7668 14.1608C12.8668 14.0576 12.923 13.9176 12.923 13.7717V12.8256H13.8398C13.9812 12.8256 14.1169 12.7676 14.2169 12.6644C14.3169 12.5612 14.3731 12.4212 14.3731 12.2752C14.3731 12.1293 14.3169 11.9893 14.2169 11.8861C14.1169 11.7829 13.9812 11.7249 13.8398 11.7249H12.923Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.13294 4.99286C3.99149 4.99286 3.85583 5.05084 3.75582 5.15406C3.6558 5.25727 3.59961 5.39725 3.59961 5.54322V8.51132C3.59959 8.61095 3.62579 8.70873 3.67541 8.79421C3.72502 8.87968 3.7962 8.94966 3.88133 8.99666C3.96647 9.04367 4.06237 9.06594 4.15881 9.06111C4.25525 9.05628 4.34861 9.02451 4.42893 8.96922L5.67585 8.1101L6.92276 8.96812C7.00308 9.02341 7.09644 9.05518 7.19288 9.06001C7.28932 9.06484 7.38523 9.04257 7.47036 8.99556C7.5555 8.94856 7.62667 8.87858 7.67629 8.79311C7.7259 8.70763 7.7521 8.60985 7.75208 8.51021V5.54322C7.75208 5.39725 7.69589 5.25727 7.59588 5.15406C7.49586 5.05084 7.36021 4.99286 7.21876 4.99286C7.07731 4.99286 6.94166 5.05084 6.84164 5.15406C6.74162 5.25727 6.68543 5.39725 6.68543 5.54322V7.48269L5.97291 6.99177C5.88527 6.93143 5.78227 6.89923 5.67691 6.89923C5.57156 6.89923 5.46856 6.93143 5.38092 6.99177L4.66626 7.48269V5.54322C4.66626 5.39725 4.61007 5.25727 4.51005 5.15406C4.41004 5.05084 4.27438 4.99286 4.13294 4.99286Z" fill="#7a8b9a"/></svg>
                                        Smart Order Bump Rules
                                    </li>

                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x=".7" y=".7" width="8.45" height="5.98" rx="2.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x="6.85" y="9.32" width="8.45" height="5.98" rx="2.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x="11.78" y=".7" width="3.52" height="5.98" rx="1.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x=".7" y="9.32" width="3.52" height="5.98" rx="1.3" stroke="#7a8b9a" stroke-width="1.4"/></svg>
                                        Premium Checkout Templates
                                    </li>

                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M0.699219 4.70001L4.22722 1.17201C4.37606 1.02228 4.55307 0.903483 4.74804 0.822474C4.94301 0.741466 5.15209 0.699845 5.36322 0.700013H12.0352C12.2463 0.699845 12.4554 0.741466 12.6504 0.822474C12.8454 0.903483 13.0224 1.02228 13.1712 1.17201L16.6992 4.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.29883 8.70001V15.1C2.29883 15.5244 2.4674 15.9313 2.76746 16.2314C3.06752 16.5314 3.47448 16.7 3.89883 16.7H13.4988C13.9232 16.7 14.3301 16.5314 14.6302 16.2314C14.9303 15.9313 15.0988 15.5244 15.0988 15.1V8.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.0988 16.7V13.5C11.0988 13.0757 10.9303 12.6687 10.6302 12.3687C10.3301 12.0686 9.92317 11.9 9.49883 11.9H7.89883C7.47448 11.9 7.06752 12.0686 6.76746 12.3687C6.4674 12.6687 6.29883 13.0757 6.29883 13.5V16.7" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M0.699219 4.70001H16.6992" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.6992 4.70001V7.10001C16.6992 7.52436 16.5306 7.93132 16.2306 8.23138C15.9305 8.53144 15.5236 8.70001 15.0992 8.70001C14.6318 8.67429 14.1854 8.49741 13.8272 8.19601C13.7318 8.12703 13.617 8.0899 13.4992 8.0899C13.3814 8.0899 13.2667 8.12703 13.1712 8.19601C12.813 8.49741 12.3666 8.67429 11.8992 8.70001C11.4318 8.67429 10.9854 8.49741 10.6272 8.19601C10.5318 8.12703 10.417 8.0899 10.2992 8.0899C10.1814 8.0899 10.0667 8.12703 9.97122 8.19601C9.61303 8.49741 9.16663 8.67429 8.69922 8.70001C8.23181 8.67429 7.7854 8.49741 7.42722 8.19601C7.33176 8.12703 7.21699 8.0899 7.09922 8.0899C6.98145 8.0899 6.86667 8.12703 6.77122 8.19601C6.41303 8.49741 5.96663 8.67429 5.49922 8.70001C5.03181 8.67429 4.5854 8.49741 4.22722 8.19601C4.13176 8.12703 4.01699 8.0899 3.89922 8.0899C3.78145 8.0899 3.66667 8.12703 3.57122 8.19601C3.21303 8.49741 2.76663 8.67429 2.29922 8.70001C1.87487 8.70001 1.46791 8.53144 1.16785 8.23138C0.86779 7.93132 0.699219 7.52436 0.699219 7.10001V4.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Storewide Checkout
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpfnl-pro-modal-footer">
                            <a href="https://getwpfunnels.com/pricing/" target="_blank" title="Upgrade to Pro" aria-label="Upgrade to Pro" class="btn-default">Upgrade to Pro</a>
                        </div>
                    </div>
                </div>
            </div>
    </div>

</div>

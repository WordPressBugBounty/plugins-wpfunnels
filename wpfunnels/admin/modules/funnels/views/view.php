<?php

/**
 * This code snippet will check if pro addons is activated or not. if not activated
 * Total number of funnels will be maximum 3, otherwise customer can add as more funnels
 * As they want
 *
 * @package
 */
$is_pro_active = apply_filters( 'wpfunnels/is_pro_license_activated', false );
$count_funnels = wp_count_posts('wpfunnels')->publish + wp_count_posts('wpfunnels')->draft;
$total_allowed_funnels = 3;
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
                            <input name="page" type="hidden" value="<?php echo 'trash_funnels' != sanitize_text_field( $_GET['page'])  ? WPFNL_MAIN_PAGE_SLUG : WPFNL_TRASH_FUNNEL_SLUG; ?>">
                            <?php require_once WPFNL_DIR . '/admin/partials/icons/search-icon.php'; ?>
                            <input name="s" type="text" value="<?php echo esc_attr($s); ?>" placeholder="<?php echo __('Search for a funnel...', 'wpfnl'); ?>">
                        </div>
                    </form>
                    <?php } ?>
                </div>


                <!-- Export import -->
                <?php if ( (count($this->funnels) || !empty($_GET['s'])) && $is_pro_active &&  isset($_GET['page']) && 'trash_funnels' != sanitize_text_field( $_GET['page'] ) ) : ?>
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

                    <?php if( (count($this->funnels) || !empty($_GET['s'])) && !$is_pro_active &&  isset($_GET['page']) && 'trash_funnels' != sanitize_text_field( $_GET['page'] ) ) : ?>
                        <a id="wpfnl-export-all-pro" class="import-export">
                        <?php
                            require WPFNL_DIR . '/admin/partials/icons/export-icon.php';
                            echo __('Export All', 'wpfnl');
                        ?>
                        <span class="pro-tag-icon"><?php require WPFNL_DIR . '/admin/partials/icons/pro-icon.php'; ?></span>
                    </a>

                    <a id="wpfnl-import-funnels-pro" class="import-export ">
                        <?php
                            require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                            echo __('Import', 'wpfnl');
                        ?>
                        <span class="pro-tag-icon"><?php require WPFNL_DIR . '/admin/partials/icons/pro-icon.php'; ?></span>
                    </a>

                    <!-- Export import -->
                    <?php endif; ?>


                <?php
                    if( (count($this->funnels) || !empty($_GET['s'] )) && isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] ) ){
                ?>
                <a href="#" class="btn-default add-new-funnel-btn">
                    <?php
                        require WPFNL_DIR . '/admin/partials/icons/plus-icon.php';
                        echo __('Add new Funnel', 'wpfnl');
                    ?>
                </a>
                <?php
                }

                ?>
            </div>

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

                                            <?php if( $is_pro_active && isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] )): ?>
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

                                            <?php if( !$is_pro_active && isset($_GET['page']) && 'trash_funnels' !== sanitize_text_field( $_GET['page'] )): ?>
												<li>
													<a class="duplicate wpfnl-export-funnel-pro" data-id="
                                                        <?php
                                                            echo $funnel_id;
                                                        ?>
                                                    ">
														<?php
                                                            require WPFNL_DIR . '/admin/partials/icons/export-icon.php';
                                                            echo __('Export', 'wpfnl');
                                                        ?>

                                                        <span class="pro-tag-icon"><?php require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';?></span>
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
                                        <?php if( !$is_pro_active ) { ?>
                                            <a href="#" class="btn-default pro-import-button">
                                                <?php
                                                    require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                                                    echo __('Import Funnels', 'wpfnl');

                                                ?>
                                                <span class="pro-tag-icon"><?php require WPFNL_DIR . '/admin/partials/icons/pro-icon.php'; ?></span>
                                            </a>
                                        <?php }else{ ?>
                                            <a href="#" class="btn-default import-export wpfnl-import-funnels">
                                                <?php
                                                    require WPFNL_DIR . '/admin/partials/icons/import-icon.php';
                                                    echo __('Import Funnels', 'wpfnl');

                                                ?>
                                            </a>
                                        <?php } ?>
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
                            <h3 class="wpfnl-pro-heading">Unlock with Premium</h3>
                            <p class="wpfnl-pro-sub-heading">This feature is only available in the Pro version. Upgrade Now to continue all these awesome features</p>
                        </div>
                        <div class="wpfnl-pro-modal-body">
                            <div  class="wpfnl-pro-modal-body_container">
                            <ul class="wpfnl-pro-features first-col">
                                <li>
                                    <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                    <span>Unlimited Contacts</span>
                                </li>
                                <li>
                                    <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                    <span>Conditional Branching</span>
                                </li>
                                <li>
                                    <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                    <span>360 Contacts view</span>
                                </li>
                            </ul>
                            <ul class="wpfnl-pro-features second-col">
                                <li>
                                    <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                    <span>Connect with Form Plugins</span>
                                </li>
                                <li>
                                    <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                    <span>Over 60+ Integrations</span>
                                </li>
                            </ul>

                            </div>

                        </div>
                        <div class="wpfnl-pro-modal-footer">
                           <div class="wpfnl-pro-modal-footer_container">
                            <div  class="wpfnl-pro-modal-footer_packages">
                                <div class="wpfnl-pro-modal-footer_packages-type" id="pro-modal-package-type">
                                   <strong>Small</strong> <span>License for 1 site</span>
                                </div>
                                <div class="wpfnl-pro-modal-footer_packages-price" id="pro-modal-package-price">
                                   <strong>$97</strong> <span>/year</span>
                                </div>

                                <button type="button" class="wpfnl-pro-modal-footer_packages-btn " id="pro-modal-dropdown-btn">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/down-arrow.php'; ?>
                                </button>

                                <div class="wpfnl-pro-modal-select-container" id="pro-modal-dropdown-body">
                                  <ul class="wpfnl-pro-modal-dropdown wpfnl-pro-modal-select-dropdown">
                                    <li value="97" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/"><strong>Small</strong> <span>License for 1 site</span></li>
                                    <li value="147" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual-5-sites/steps/5-sites-annual-checkout/"><strong>Medium</strong> <span>License for 5 sites</span></li>
                                    <li value="237" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual-unlimited/steps/annual-unlimited-checkout/"><strong>Large</strong> <span>License for 50 sites</span></li>
                                  </ul>

                                </div>
                            </div>
                           <div class="wpfnl-footer-btn-wrapper">
                                <a class="btn-default confirmed" target="_blank" href="https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/">
                                    <span>Buy Now</span>
                                </a>
                            </div>
                           </div> <p class="wpfnl-pro-modal-footer-text">
                                <span>Easiest Funnel Builder : <strong>8000+</strong> Users, <strong>105+</strong> Five-Star Reviews</span>
                            </p>
                        </div>
                    </div>
                </div>
        </div>
        </div>
    </div>

</div>

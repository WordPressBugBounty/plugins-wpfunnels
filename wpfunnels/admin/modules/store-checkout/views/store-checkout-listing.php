<?php
/**
 * Store Checkout Listing view
 *
 * Shows all store checkout funnels in a list styled to match the funnel listing.
 *
 * @package WPFunnels
 * @since   3.6.0
 */

use WPFunnels\Wpfnl_functions;
use WPFunnels\WooCommerce\Wpfnl_Store_Checkout_Conditions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WPFunnels\\WooCommerce\\Wpfnl_Store_Checkout_Conditions' ) ) {
    require_once WPFNL_DIR . 'includes/core/woocommerce/class-wpfnl-store-checkout-conditions.php';
}

$is_pro_active = apply_filters( 'wpfunnels/is_pro_license_activated', false );

// Determine if we are viewing trash or live
$sc_view = isset( $_GET['sc_status'] ) && 'trash' === sanitize_text_field( $_GET['sc_status'] ) ? 'trash' : 'live';

// Live store checkout funnels
$all_live_funnels = Wpfnl_Store_Checkout_Conditions::get_all_store_checkout_funnels();
$total_live       = count( $all_live_funnels );

// Trash store checkout funnels
$all_trash_funnels = get_posts( array(
    'post_type'      => WPFNL_FUNNELS_POST_TYPE,
    'posts_per_page' => -1,
    'post_status'    => array( 'trash' ),
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        array(
            'key'   => '_wpfnl_funnel_type',
            'value' => 'store_checkout',
        ),
    ),
) );
$total_trash = count( $all_trash_funnels );

$all_funnels   = 'trash' === $sc_view ? $all_trash_funnels : $all_live_funnels;
$total_funnels = count( $all_funnels );

// --- Store Checkout Analytics ---
$sc_analytics = array(
    'total_orders'      => 0,
    'total_revenue'     => 0,
    'aov'               => 0,
    'orderbump_revenue' => 0,
);

if ( $total_live > 0 ) {
    global $wpdb;
    $stats_table = $wpdb->prefix . 'wpfnl_stats';
    $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $stats_table ) );

    if ( $table_exists ) {
        $live_ids = wp_list_pluck( $all_live_funnels, 'ID' );
        $placeholders = implode( ',', array_fill( 0, count( $live_ids ), '%d' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(DISTINCT id) AS total_orders,
                COALESCE(SUM(total_sales), 0) AS total_revenue,
                COALESCE(SUM(orderbump_sales), 0) AS orderbump_revenue
            FROM {$stats_table}
            WHERE funnel_id IN ({$placeholders}) AND status IN (%s, %s)",
            array_merge( $live_ids, array( 'completed', 'processing' ) )
        ) );

        if ( $row ) {
            $sc_analytics['total_orders']      = (int) $row->total_orders;
            $sc_analytics['total_revenue']     = (float) $row->total_revenue;
            $sc_analytics['aov']               = $sc_analytics['total_orders'] > 0
                ? round( $sc_analytics['total_revenue'] / $sc_analytics['total_orders'], 2 )
                : 0;
            $sc_analytics['orderbump_revenue'] = (float) $row->orderbump_revenue;
        }
    }
}

// Pagination
$per_page    = isset( $_GET['per_page'] ) ? max( 10, (int) sanitize_text_field( $_GET['per_page'] ) ) : 10;
$paged       = isset( $_GET['pageno'] ) ? max( 1, (int) sanitize_text_field( $_GET['pageno'] ) ) : 1;
$offset      = ( $paged - 1 ) * $per_page;
$funnels     = array_slice( $all_funnels, $offset, $per_page );
$total_pages = $per_page > 0 ? (int) ceil( $total_funnels / $per_page ) : 1;

$sc_listing_url = admin_url( 'admin.php?page=store_checkout' );
$sc_trash_url   = admin_url( 'admin.php?page=store_checkout&sc_status=trash' );

$condition_labels = array(
    'all'        => __( 'No Condition', 'wpfnl' ),
    'products'   => __( 'Specific Products', 'wpfnl' ),
    'categories' => __( 'Product Categories', 'wpfnl' ),
    'tags'       => __( 'Product Tags', 'wpfnl' ),
    'date_range' => __( 'Date Range', 'wpfnl' ),
    'rules'      => __( 'Custom Rules', 'wpfnl' ),
);

/**
 * Build tooltip text for a single rule by resolving its value IDs to names.
 */
function wpfnl_sc_rule_tooltip( $rule, $condition_labels, $total_rules = 1 ) {
    $rtype  = isset( $rule['type'] ) ? $rule['type'] : 'all';
    $values = isset( $rule['values'] ) ? (array) $rule['values'] : array();
    $or_note = $total_rules > 1 ? ' ' . __( '(OR with other rules)', 'wpfnl' ) : '';

    if ( empty( $values ) ) {
        return __( 'This checkout will appear when the condition is met.', 'wpfnl' ) . $or_note;
    }

    $names = array();
    foreach ( $values as $val_id ) {
        $val_id = (int) $val_id;
        if ( 'products' === $rtype ) {
            $names[] = get_the_title( $val_id );
        } elseif ( 'categories' === $rtype ) {
            $term = get_term( $val_id, 'product_cat' );
            $names[] = ( $term && ! is_wp_error( $term ) ) ? $term->name : '#' . $val_id;
        } elseif ( 'tags' === $rtype ) {
            $term = get_term( $val_id, 'product_tag' );
            $names[] = ( $term && ! is_wp_error( $term ) ) ? $term->name : '#' . $val_id;
        }
    }

    $names_str = implode( ', ', $names );

    switch ( $rtype ) {
        case 'products':
            /* translators: %s = comma-separated product names */
            return sprintf( __( 'This checkout will appear when the cart contains: %s.', 'wpfnl' ), $names_str ) . $or_note;
        case 'categories':
            /* translators: %s = comma-separated category names */
            return sprintf( __( 'This checkout will appear when the cart has products from: %s.', 'wpfnl' ), $names_str ) . $or_note;
        case 'tags':
            /* translators: %s = comma-separated tag names */
            return sprintf( __( 'This checkout will appear when the cart has products tagged: %s.', 'wpfnl' ), $names_str ) . $or_note;
        default:
            return __( 'This checkout will appear when the condition is met.', 'wpfnl' ) . $or_note;
    }
}

?>

<div class="wpfnl wpfnl-store-checkout-listing-page">
    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>

            <?php if ( ! $is_pro_active ) : ?>
                <div class="wpfnl-dashboard__nav-right">
                    <a href="https://getwpfunnels.com/pricing/" class="btn-default" target="_blank"
                       title="<?php esc_attr_e( 'Upgrade to Pro', 'wpfnl' ); ?>"
                       aria-label="<?php esc_attr_e( 'Upgrade to Pro', 'wpfnl' ); ?>">
                        <?php esc_html_e( 'Upgrade to Pro', 'wpfnl' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="dashboard-nav__content">

            <div id="templates-library"></div>

            <?php
            $sc_count_active  = Wpfnl_functions::count_store_checkout_funnels();
            $sc_limit_reached = ! $is_pro_active && $sc_count_active >= 3;
            ?>

            <!-- Page header (matches funnel-listing header style) -->
            <div class="wpfnl-dashboard__header overview-header">
                <div class="wpfnl-dashboard-header-left">
                    <!-- <div class="wpfnl-dashboard-links-wrapper">
                        <a href="<?php echo esc_url( $sc_listing_url ); ?>" class="wpfnl-all-funnels <?php echo 'live' === $sc_view ? 'active' : ''; ?>">
                            <span><?php esc_html_e( 'Live', 'wpfnl' ); ?></span>
                            <span class="wpfnl-count"><?php echo esc_html( $total_live ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( $sc_trash_url ); ?>" class="wpfnl-trash-all-funnels <?php echo 'trash' === $sc_view ? 'active' : ''; ?>">
                            <span><?php esc_html_e( 'Trash', 'wpfnl' ); ?></span>
                            <span class="wpfnl-count"><?php echo esc_html( $total_trash ); ?></span>
                        </a>
                    </div> -->
                </div>
                <?php if ( 'live' === $sc_view && ( $total_live || $total_funnels ) ) : ?>
                    <?php
                    $sc_btn_classes = 'btn-default add-new-funnel-btn';
                    if ( $sc_limit_reached ) {
                        $sc_btn_classes .= ' disabled';
                    }
                    ?>
                    <a href="#" id="wpfnl-create-store-checkout" class="<?php echo esc_attr( $sc_btn_classes ); ?>">
                        <?php
                        if ( $sc_limit_reached ) {
                            require WPFNL_DIR . '/admin/partials/icons/lock-icon.php';
                        } else {
                            require WPFNL_DIR . '/admin/partials/icons/plus-icon.php';
                        }
                        esc_html_e( 'Store Checkout', 'wpfnl' );
                        ?>
                    </a>
                <?php endif; ?>

            </div><!-- /.wpfnl-dashboard__header -->

            <?php if ( $sc_limit_reached ) : ?>
            <div class="upgrade-to-pro">
                <div class="upgrade-to-pro-wrapper">
                    <div class="warning-icon-wrapper">
                        <span class="warning-icon">
                            <?php require WPFNL_DIR . '/admin/partials/icons/warning-icon.php'; ?>
                        </span>
                    </div>
                    <div class="upgrade-to-pro-content">
                        <div class="upgrade-to-pro-message">
                            <h3><?php esc_html_e( 'You have hit the limit! Upgrade to Pro for unlimited store checkouts!', 'wpfnl' ); ?></h3>
                            <p><?php esc_html_e( 'You are using the free version of WPFunnels which allows you to create up to 3 store checkouts. To build more store checkouts, either move one to trash or Upgrade to Pro.', 'wpfnl' ); ?></p>
                        </div>
                    </div>
                    <div class="upgrade-to-pro-action">
                        <a href="https://getwpfunnels.com/pricing/" target="_blank" class="btn-upgrade-to-pro"><?php esc_html_e( 'Upgrade to Pro', 'wpfnl' ); ?></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( 'live' === $sc_view ) : ?>
            <!-- Store Checkout Analytics Overview -->
            <div class="wpfnl-sc-analytics-overview">
                <div class="wpfnl-sc-analytics-cards">
                    <div class="wpfnl-sc-analytics-card">
                        <div class="wpfnl-sc-analytics-card__icon">
                            <span class="icon-wrap icon-wrap--purple">
                                <svg width="20" height="21" fill="none" viewBox="0 0 20 21" xmlns="http://www.w3.org/2000/svg"><g fill="#6E42D3" clip-path="url(#clip_sc_gs)"><path d="M6.66 4.279h6.68l1.442-1.15c.491-.39.674-1.024.467-1.615A1.438 1.438 0 0013.876.54h-7.75a1.44 1.44 0 00-1.374.973 1.433 1.433 0 00.466 1.614l1.443 1.15zm6.794 1.245H6.546C4.27 7.751 2.5 11.658 2.5 14.868c0 2.79 1.482 5.607 4.792 5.607h5.625c2.827 0 4.583-2.149 4.583-5.607 0-3.21-1.77-7.117-4.046-9.344zm-3.77 6.853h.633c.974 0 1.766.79 1.766 1.76 0 .873-.631 1.592-1.458 1.738v.654a.624.624 0 01-1.25 0v-.622h-.833a.624.624 0 01-.625-.623c0-.344.28-.623.625-.623h1.775a.516.516 0 00.516-.515.518.518 0 00-.516-.524h-.634c-.974 0-1.766-.79-1.766-1.76 0-.873.631-1.592 1.458-1.738V9.47a.624.624 0 011.25 0v.622h.833a.624.624 0 110 1.246H9.683a.516.516 0 00-.516.515c0 .293.231.524.516.524z"/></g><defs><clipPath id="clip_sc_gs"><path fill="#fff" d="M0 0h20v19.934H0z" transform="translate(0 .54)"/></clipPath></defs></svg>
                            </span>
                        </div>
                        <div class="wpfnl-sc-analytics-card__content">
                            <span class="card-title"><?php esc_html_e( 'Total Orders', 'wpfnl' ); ?></span>
                            <span class="card-value"><?php echo esc_html( number_format_i18n( $sc_analytics['total_orders'] ) ); ?></span>
                        </div>
                    </div>

                    <div class="wpfnl-sc-analytics-card">
                        <div class="wpfnl-sc-analytics-card__icon">
                            <span class="icon-wrap icon-wrap--orange">
                                <svg width="18" height="21" fill="none" viewBox="0 0 18 21" xmlns="http://www.w3.org/2000/svg"><path fill="#EE8134" d="M6.023 20.488a1.253 1.253 0 01-.885-.361 1.244 1.244 0 01.875-2.13h.01a1.243 1.243 0 110 2.492zm9.254-1.246c0-.33-.131-.647-.366-.88a1.253 1.253 0 00-.884-.366h-.01a1.253 1.253 0 00-1.149.776 1.241 1.241 0 00.278 1.355 1.252 1.252 0 002.131-.885zm2.69-12.598l-1.01 6.16a2.564 2.564 0 01-.926 1.882 2.583 2.583 0 01-2.025.57H5.732a2 2 0 01-1.313-.488 1.985 1.985 0 01-.667-1.227L2.25 3.128a1.238 1.238 0 00-1.24-1.079H.75A.752.752 0 010 1.302.746.746 0 01.75.554h.26a2.736 2.736 0 011.808.666c.501.434.827 1.035.916 1.69l.209 1.382h12.063a2.007 2.007 0 011.532.72 1.99 1.99 0 01.43 1.632zm-5.455 1.272a.75.75 0 00-1.061 0l-2.137 2.129-.8-.798a.752.752 0 00-1.272.53.746.746 0 00.21.527l1.334 1.33a.754.754 0 001.061 0l2.665-2.658a.747.747 0 000-1.06z"/></svg>
                            </span>
                        </div>
                        <div class="wpfnl-sc-analytics-card__content">
                            <span class="card-title"><?php esc_html_e( 'Total Revenue', 'wpfnl' ); ?></span>
                            <span class="card-value"><?php echo wp_kses_post( wc_price( $sc_analytics['total_revenue'] ) ); ?></span>
                        </div>
                    </div>

                    <div class="wpfnl-sc-analytics-card">
                        <div class="wpfnl-sc-analytics-card__icon">
                            <span class="icon-wrap icon-wrap--blue">
                                <svg width="20" height="21" fill="none" viewBox="0 0 20 21" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="13.54" width="4" height="7" rx="1" fill="#2E90FA"/><rect x="8" y="8.54" width="4" height="12" rx="1" fill="#2E90FA"/><rect x="14" y="3.54" width="4" height="17" rx="1" fill="#2E90FA"/></svg>
                            </span>
                        </div>
                        <div class="wpfnl-sc-analytics-card__content">
                            <span class="card-title"><?php esc_html_e( 'Avg. Order Value', 'wpfnl' ); ?></span>
                            <span class="card-value"><?php echo wp_kses_post( wc_price( $sc_analytics['aov'] ) ); ?></span>
                        </div>
                    </div>

                    <div class="wpfnl-sc-analytics-card">
                        <div class="wpfnl-sc-analytics-card__icon">
                            <span class="icon-wrap icon-wrap--green">
                                <svg width="18" height="21" fill="none" viewBox="0 0 18 21" xmlns="http://www.w3.org/2000/svg"><path fill="#12B76A" d="M7.576.554a.678.678 0 00-.69.668c0 1.116-.403 1.77-.924 2.607-.286.458-.742 1.215-.938 1.796-.205-.396-.322-.69-.504-1.324-.148-.528-.859-.669-1.212-.24C.711 7.167-.052 9.498.003 11.855c.11 4.748 4.037 8.632 9 8.632 4.961 0 8.997-3.879 8.997-8.646 0-1.225-.295-2.815-.905-4.31-.61-1.495-1.529-2.915-2.938-3.639-.397-.202-.888.008-.996.428a9.852 9.852 0 01-.347 1.15 7.633 7.633 0 00-1.037-2.496C10.905 1.63 9.467.555 7.576.555zm-.888 8.793c.573 0 1.038.447 1.038.998 0 .55-.465.997-1.038.997s-1.039-.447-1.039-.997.466-.998 1.039-.998zm4.912.007a.634.634 0 01.514.16c.286.242.316.66.066.937L6.86 16.27a.712.712 0 01-.975.064.647.647 0 01-.061-.941l5.314-5.819c.106-.117.28-.2.462-.22zm-.288 5.148c.573 0 1.039.446 1.039.997 0 .55-.466.998-1.039.998s-1.038-.447-1.038-.998c0-.55.464-.997 1.038-.997z"/></svg>
                            </span>
                        </div>
                        <div class="wpfnl-sc-analytics-card__content">
                            <span class="card-title"><?php esc_html_e( 'Bump Offer Revenue', 'wpfnl' ); ?></span>
                            <span class="card-value"><?php echo wp_kses_post( wc_price( $sc_analytics['orderbump_revenue'] ) ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- <div class="wpfnl-sc-analytics-toggle">
                    <span class="wpfnl-sc-analytics-toggle__btn" id="wpfnl-sc-analytics-toggle">
                        <svg class="toggle-arrow" width="14" height="8" fill="none" viewBox="0 0 14 8" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6 6-6" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </div> -->
            </div>
            <?php endif; ?>

            <!-- Funnel-style listing -->
            <div class="wpfnl-dashboard__inner-content <?php echo $total_funnels ? '' : 'no-funnel'; ?>">
                <div class="funnel-list__wrapper">

                    <?php if ( $total_funnels ) : ?>

                        <!-- Header row -->
                        <div class="funnel__single-list list-header">
                            <div class="bulk-action-wrapper">
                                <p>
                                    <span class="selected-funnel-count">0 <?php esc_html_e( 'Store Checkout', 'wpfnl' ); ?></span>
                                    <?php esc_html_e( 'Selected', 'wpfnl' ); ?>
                                </p>
                                <button class="btn-default bulk-delete-toggler">
                                    <?php esc_html_e( 'Bulk Actions', 'wpfnl' ); ?>
                                    <svg width="8" height="6" fill="none" viewBox="0 0 8 6" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" stroke="#fff" stroke-width=".2" d="M4 5.28a.559.559 0 01-.396-.164l-3.44-3.44A.56.56 0 11.956.884L4 3.928 7.044.884a.56.56 0 01.792.792l-3.44 3.44A.559.559 0 014 5.28z"/></svg>
                                    <ul class="wpfnl-dropdown">
                                        <?php if ( 'trash' === $sc_view ) : ?>
                                            <li>
                                                <a href="#" class="delete wpfnl-sc-bulk-restore" title="<?php esc_attr_e( 'Restore Selected', 'wpfnl' ); ?>">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/restore-icon.php'; ?>
                                                    <?php esc_html_e( 'Restore', 'wpfnl' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="delete wpfnl-sc-bulk-delete" title="<?php esc_attr_e( 'Delete Permanently', 'wpfnl' ); ?>">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
                                                    <?php esc_html_e( 'Delete', 'wpfnl' ); ?>
                                                </a>
                                            </li>
                                        <?php else : ?>
                                            <li>
                                                <a href="#" class="delete wpfnl-sc-bulk-trash" title="<?php esc_attr_e( 'Trash Selected', 'wpfnl' ); ?>">
                                                    <?php require WPFNL_DIR . '/admin/partials/icons/trash-icon.php'; ?>
                                                    <?php esc_html_e( 'Trash', 'wpfnl' ); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </button>
                            </div>

                            <div class="funnel-list__bulk-action">
                                <div class="funnel-list__bulk-select select-all-funnels">
                                    <span class="wpfnl-checkbox no-title">
                                        <input type="checkbox" name="wpfnl-sc-bulk-select" id="wpfnl-sc-bulk-select">
                                        <label for="wpfnl-sc-bulk-select"></label>
                                    </span>
                                </div>
                            </div>

                            <div class="list-cell wpfnl-name"><?php esc_html_e( 'Name', 'wpfnl' ); ?></div>
                            <div class="list-cell wpfnl-sc-priority"><?php esc_html_e( 'Priority', 'wpfnl' ); ?></div>
                            <div class="list-cell wpfnl-creation-date"><?php esc_html_e( 'Creation Date', 'wpfnl' ); ?></div>
                            <div class="list-cell wpfnl-status"><?php esc_html_e( 'Status', 'wpfnl' ); ?></div>
                            <div class="list-cell list-action"><?php esc_html_e( 'Action', 'wpfnl' ); ?></div>
                        </div>

                        <?php
                        // Pre-compute priority for all funnels.
                        // Conditional (published) funnels first (newest = #1), then catch-all.
                        $priority_map    = array();
                        $conditional_ids = array();
                        $catchall_ids    = array();

                        foreach ( $all_funnels as $_f ) {
                            $_sid  = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $_f->ID );
                            $_cond = $_sid ? Wpfnl_Store_Checkout_Conditions::get_condition( $_sid ) : array( 'condition_type' => 'all' );
                            if ( Wpfnl_Store_Checkout_Conditions::is_catch_all( $_cond ) ) {
                                $catchall_ids[] = $_f->ID;
                            } else {
                                $conditional_ids[] = $_f->ID;
                            }
                        }

                        // $all_funnels is already newest-first (DESC) from the query.
                        $pri = 1;
                        foreach ( $conditional_ids as $_fid ) {
                            $priority_map[ $_fid ] = $pri++;
                        }
                        // Among catch-all funnels, only the first (newest) acts as true fallback.
                        $first_catchall = true;
                        $catchall_overridden = array();
                        foreach ( $catchall_ids as $_fid ) {
                            $priority_map[ $_fid ] = $pri++;
                            if ( $first_catchall ) {
                                $first_catchall = false;
                            } else {
                                $catchall_overridden[ $_fid ] = true;
                            }
                        }
                        ?>

                        <?php foreach ( $funnels as $funnel ) :
                            $funnel_id = $funnel->ID;
                            $step_id   = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $funnel_id );

                            // Edit URL
                            $edit_url = add_query_arg(
                                array(
                                    'page'    => WPFNL_EDIT_FUNNEL_SLUG,
                                    'id'      => $funnel_id,
                                    'step_id' => 0,
                                ),
                                admin_url( 'admin.php' )
                            );

                            // View URL (checkout step permalink)
                            $view_url = '#';
                            if ( $step_id ) {
                                $permalink = get_the_permalink( $step_id );
                                if ( $permalink ) {
                                    $view_url = $permalink;
                                }
                            }

                            // Build per-rule condition badges
                            $condition = $step_id
                                ? Wpfnl_Store_Checkout_Conditions::get_condition( $step_id )
                                : array( 'condition_type' => 'all' );
                            $cond_type = isset( $condition['condition_type'] ) ? $condition['condition_type'] : 'all';

                            $cond_badges = array(); // each entry: array( 'label' => '…', 'active' => bool, 'tooltip' => '…', 'is_date' => bool )
                            $rules_count = 0;
                            if ( 'rules' === $cond_type ) {
                                $rules = isset( $condition['rules'] ) && is_array( $condition['rules'] ) ? $condition['rules'] : array();
                                $rules_count = count( $rules );
                                foreach ( $rules as $rule ) {
                                    $rtype = isset( $rule['type'] ) ? $rule['type'] : 'all';
                                    $label = isset( $condition_labels[ $rtype ] ) ? $condition_labels[ $rtype ] : $condition_labels['all'];
                                    $cond_badges[] = array(
                                        'label'   => $label,
                                        'active'  => ( $label !== $condition_labels['all'] ),
                                        'tooltip' => wpfnl_sc_rule_tooltip( $rule, $condition_labels, $rules_count ),
                                        'is_date' => false,
                                    );
                                }
                            }

                            // Check for date range
                            $use_date = ! empty( $condition['use_date_range'] );
                            if ( $use_date ) {
                                $d_from   = isset( $condition['date_from'] ) ? $condition['date_from'] : '';
                                $d_to     = isset( $condition['date_to'] )   ? $condition['date_to']   : '';
                                $has_rules = $rules_count > 0;
                                if ( $d_from && $d_to ) {
                                    if ( $has_rules ) {
                                        $date_tip = sprintf( __( 'Additionally, this checkout will only be active between %1$s and %2$s.', 'wpfnl' ), $d_from, $d_to );
                                    } else {
                                        $date_tip = sprintf( __( 'This checkout will only be active between %1$s and %2$s.', 'wpfnl' ), $d_from, $d_to );
                                    }
                                } elseif ( $d_from ) {
                                    if ( $has_rules ) {
                                        $date_tip = sprintf( __( 'Additionally, this checkout will only be active from %s onwards.', 'wpfnl' ), $d_from );
                                    } else {
                                        $date_tip = sprintf( __( 'This checkout will only be active from %s onwards.', 'wpfnl' ), $d_from );
                                    }
                                } elseif ( $d_to ) {
                                    if ( $has_rules ) {
                                        $date_tip = sprintf( __( 'Additionally, this checkout will only be active until %s.', 'wpfnl' ), $d_to );
                                    } else {
                                        $date_tip = sprintf( __( 'This checkout will only be active until %s.', 'wpfnl' ), $d_to );
                                    }
                                } else {
                                    $date_tip = __( 'This checkout has a date range condition.', 'wpfnl' );
                                }
                                $cond_badges[] = array(
                                    'label'   => $condition_labels['date_range'],
                                    'active'  => true,
                                    'tooltip' => $date_tip,
                                    'is_date' => true,
                                );
                            }

                            // Fallback: no rules at all
                            $is_catch_all = empty( $cond_badges );
                            if ( $is_catch_all ) {
                                $cond_badges[] = array(
                                    'label'   => $condition_labels['all'],
                                    'active'  => false,
                                    'tooltip' => __( 'This store checkout will apply as the default WooCommerce checkout without any condition.', 'wpfnl' ),
                                );
                            }

                            // Builder icon
                            $builder = Wpfnl_functions::get_page_builder_by_step_id( $funnel_id );

                            // Status
                            $status_class  = ( 'publish' === $funnel->post_status ) ? 'enabled' : $funnel->post_status;
                            $status_label  = ( 'publish' === $funnel->post_status ) ? __( 'Enabled', 'wpfnl' ) : __( 'Disabled', 'wpfnl' );
                            $toggle_status = ( 'publish' === $funnel->post_status ) ? 'draft' : 'publish';
                            $toggle_label  = ( 'publish' === $funnel->post_status ) ? __( 'Disable', 'wpfnl' ) : __( 'Enable', 'wpfnl' );

                            // Creation date
                            $creation_date = get_the_date( 'Y-m-d g:i A', $funnel_id );
                        ?>

                            <div class="funnel__single-list list-body">

                                <div class="funnel-list__bulk-action">
                                    <span class="wpfnl-checkbox no-title">
                                        <input type="checkbox" name="wpfnl-sc-list-select" id="wpfnl-sc-list<?php echo esc_attr( $funnel_id ); ?>-select" data-id="<?php echo esc_attr( $funnel_id ); ?>">
                                        <label for="wpfnl-sc-list<?php echo esc_attr( $funnel_id ); ?>-select"></label>
                                    </span>
                                </div>

                                <div class="list-cell wpfnl-name wpfnl-sc-name-cell">
                                    <?php if ( $builder ) : ?>
                                        <span class="builder-logo" title="<?php echo esc_attr( str_replace( '-', ' ', ucfirst( $builder ) ) ); ?>">
                                            <?php include WPFNL_DIR . '/admin/partials/icons/' . $builder . '.php'; ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="builder-logo" title="<?php esc_attr_e( 'No Builder Found', 'wpfnl' ); ?>"></span>
                                    <?php endif; ?>
                                    <div class="wpfnl-sc-name-inner">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="name">
                                            <?php echo esc_html( get_the_title( $funnel_id ) ); ?>
                                        </a>
                                        <?php
                                            // Build a flat text summary for the single-line display
                                            $badge_texts = array();
                                            foreach ( $cond_badges as $idx => $badge ) {
                                                if ( $idx > 0 ) {
                                                    $badge_texts[] = ! empty( $badge['is_date'] ) ? 'AND' : 'OR';
                                                }
                                                $badge_texts[] = $badge['label'];
                                            }
                                            $condition_summary = implode( ' ', $badge_texts );

                                            // Build full tooltip with all rules explained
                                            $tooltip_parts = array();
                                            foreach ( $cond_badges as $badge ) {
                                                $tooltip_parts[] = $badge['tooltip'];
                                            }
                                            $full_tooltip = implode( ' ', $tooltip_parts );
                                        ?>
                                        <span class="sc-condition-line <?php echo $is_catch_all ? 'sc-condition-line--inactive' : 'sc-condition-line--active'; ?>">
                                            <span class="sc-condition-line__text"><?php echo esc_html( $condition_summary ); ?></span>
                                            <span class="sc-condition-line__tooltip"><?php echo esc_html( $full_tooltip ); ?></span>
                                        </span>
                                    </div>
                                </div>

                                <div class="list-cell wpfnl-sc-priority">
                                    <?php
                                        $pri_num       = isset( $priority_map[ $funnel_id ] ) ? $priority_map[ $funnel_id ] : '-';
                                        $is_overridden = isset( $catchall_overridden[ $funnel_id ] );

                                        if ( $is_overridden ) {
                                            $pri_text  = __( 'Overridden', 'wpfnl' );
                                            $pri_class = 'sc-priority-badge--overridden';
                                            $pri_tip   = __( 'This fallback checkout is overridden by a newer fallback. Only the most recently created fallback is active when no conditional checkout matches.', 'wpfnl' );
                                        } elseif ( $is_catch_all ) {
                                            $pri_text  = __( 'Default', 'wpfnl' );
                                            $pri_class = 'sc-priority-badge--fallback';
                                            $pri_tip   = __( 'This checkout has no condition and acts as the default fallback. It will only be used when no other conditional store checkout matches the customer\'s cart.', 'wpfnl' );
                                        } elseif ( 1 === $pri_num ) {
                                            $pri_text  = __( 'Highest', 'wpfnl' );
                                            $pri_class = 'sc-priority-badge--active';
                                            $pri_tip   = __( 'This is the most recently created conditional checkout. It will be evaluated first and takes priority over all other store checkouts.', 'wpfnl' );
                                        } else {
                                            /* translators: %d = priority position number */
                                            $pri_text  = sprintf( __( '#%d', 'wpfnl' ), $pri_num );
                                            $pri_class = 'sc-priority-badge--active';
                                            /* translators: %d = priority position number */
                                            $pri_tip   = sprintf( __( 'This checkout has evaluation priority #%d. Store checkouts with conditions are evaluated from newest to oldest. The first match wins.', 'wpfnl' ), $pri_num );
                                        }
                                    ?>
                                    <span class="sc-priority-badge <?php echo esc_attr( $pri_class ); ?>">
                                        <?php echo esc_html( $pri_text ); ?>
                                        <span class="sc-priority-badge__tooltip"><?php echo esc_html( $pri_tip ); ?></span>
                                    </span>
                                </div>

                                <div class="list-cell wpfnl-creation-date">
                                    <span class="post-date"><?php echo esc_html( $creation_date ); ?></span>
                                </div>

                                <div class="list-cell wpfnl-status <?php echo esc_attr( $status_class ); ?>">
                                    <span class="post-status"><?php echo esc_html( $status_label ); ?></span>
                                </div>

                                <div class="list-cell list-action">
                                    <?php if ( 'trash' === $sc_view ) : ?>
                                        <span class="more-action funnel-list__more-action"
                                              title="<?php esc_attr_e( 'More options', 'wpfnl' ); ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/dot-icon.php'; ?>
                                            <ul class="more-actions wpfnl-dropdown">
                                                <li>
                                                    <a href="#" class="wpfnl-sc-restore-funnel"
                                                       data-id="<?php echo esc_attr( $funnel_id ); ?>">
                                                        <?php require WPFNL_DIR . '/admin/partials/icons/restore-icon.php'; ?>
                                                        <?php esc_html_e( 'Restore', 'wpfnl' ); ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="delete wpfnl-sc-delete-funnel"
                                                       data-id="<?php echo esc_attr( $funnel_id ); ?>">
                                                        <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
                                                        <?php esc_html_e( 'Delete Permanently', 'wpfnl' ); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </span>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="edit"
                                           title="<?php esc_attr_e( 'Edit', 'wpfnl' ); ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/edit-icon.php'; ?>
                                        </a>
                                        <a href="<?php echo esc_url( $view_url ); ?>" class="view" target="_blank"
                                           title="<?php esc_attr_e( 'View', 'wpfnl' ); ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/eye-icon.php'; ?>
                                        </a>
                                        <span class="more-action funnel-list__more-action"
                                              title="<?php esc_attr_e( 'More options', 'wpfnl' ); ?>">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/dot-icon.php'; ?>
                                            <ul class="more-actions wpfnl-dropdown">
                                                <li>
                                                    <a href="#" class="wpfnl-sc-status-toggle"
                                                       data-id="<?php echo esc_attr( $funnel_id ); ?>"
                                                       data-status="<?php echo esc_attr( $toggle_status ); ?>">
                                                        <?php if ( 'publish' === $funnel->post_status ) : ?>
                                                            <?php require WPFNL_DIR . '/admin/partials/icons/draft-icon.php'; ?>
                                                        <?php else : ?>
                                                            <?php require WPFNL_DIR . '/admin/partials/icons/publish-icon.php'; ?>
                                                        <?php endif; ?>
                                                        <?php echo esc_html( $toggle_label ); ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="delete wpfnl-sc-trash-funnel"
                                                       data-id="<?php echo esc_attr( $funnel_id ); ?>"
                                                       title="<?php esc_attr_e( 'Trash', 'wpfnl' ); ?>">
                                                        <?php require WPFNL_DIR . '/admin/partials/icons/trash-icon.php'; ?>
                                                        <?php esc_html_e( 'Trash', 'wpfnl' ); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                        <!-- Pagination footer -->
                        <div class="list-footer">
                            <div class="pagination-number">
                                <p>
                                    <strong><?php esc_html_e( 'Showing', 'wpfnl' ); ?></strong>
                                    <select id="wpfnl-sc-per-page">
                                        <?php foreach ( array( 10, 20, 30 ) as $pp ) : ?>
                                            <option value="<?php echo esc_attr( $pp ); ?>" <?php selected( $per_page, $pp ); ?>>
                                                <?php echo esc_html( $pp ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php
                                    $limit_starts = $offset + 1;
                                    $limit_ends   = min( $offset + $per_page, $total_funnels );
                                    echo esc_html( "{$limit_starts}-{$limit_ends} of {$total_funnels} " . __( 'items', 'wpfnl' ) );
                                    ?>
                                </p>
                            </div>
                            <div class="pagination">
                                <div class="wpfnl-pagination">
                                    <?php
                                    $pag_args = array( 'page' => 'store_checkout', 'per_page' => $per_page );
                                    if ( 'trash' === $sc_view ) {
                                        $pag_args['sc_status'] = 'trash';
                                    }
                                    $prev_url = $paged <= 1 ? '#' : add_query_arg( array_merge( $pag_args, array( 'pageno' => $paged - 1 ) ), admin_url( 'admin.php' ) );
                                    $next_url = $paged >= $total_pages ? '#' : add_query_arg( array_merge( $pag_args, array( 'pageno' => $paged + 1 ) ), admin_url( 'admin.php' ) );
                                    ?>
                                    <a href="<?php echo esc_url( $prev_url ); ?>"
                                       class="nav-link prev <?php echo $paged <= 1 ? 'disabled' : ''; ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M6.002 12a.856.856 0 01-.609-.25L.25 6.586a.863.863 0 010-1.214L5.393.207a.855.855 0 011.415.62.863.863 0 01-.206.594L2.067 5.974l4.535 4.554a.862.862 0 01-.6 1.472z"/><path fill="#7A8B9A" d="M11.147 12a.856.856 0 01-.61-.25L5.395 6.586a.863.863 0 010-1.214L10.538.207a.855.855 0 011.414.62.862.862 0 01-.205.594L7.21 5.974l4.536 4.554a.862.862 0 01-.6 1.472z"/></svg>
                                    </a>

                                    <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                                        <a href="<?php echo esc_url( add_query_arg( array_merge( $pag_args, array( 'pageno' => $i ) ), admin_url( 'admin.php' ) ) ); ?>"
                                           class="nav-link <?php echo $i === $paged ? 'active' : ''; ?>">
                                            <?php echo esc_html( $i ); ?>
                                        </a>
                                    <?php endfor; ?>

                                    <a href="<?php echo esc_url( $next_url ); ?>"
                                       class="nav-link next <?php echo $paged >= $total_pages ? 'disabled' : ''; ?>">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M5.998 12a.856.856 0 00.609-.25l5.144-5.164a.863.863 0 000-1.214L6.607.207a.855.855 0 00-1.415.62.863.863 0 00.206.594l4.535 4.553-4.535 4.554a.862.862 0 00.6 1.472z"/><path fill="#7A8B9A" d="M.853 12a.856.856 0 00.61-.25l5.143-5.164a.863.863 0 000-1.214L1.462.207a.855.855 0 00-1.414.62.863.863 0 00.205.594L4.79 5.974.253 10.528A.862.862 0 00.853 12z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else : ?>

                        <?php if ( 'trash' === $sc_view ) : ?>
                            <!-- Trash empty state -->
                            <div class="no-trash-wrapper">
                                <?php require WPFNL_DIR . '/admin/partials/icons/no-data-icon.php'; ?>
                                <p class="no-funnel"><?php esc_html_e( 'No data Found', 'wpfnl' ); ?></p>
                            </div>
                        <?php else : ?>
                            <!-- Empty state (matches funnel-listing no-funnel-wrapper style) -->
                            <div class="no-funnel-wrapper">
                                <div class="no-funnel-info">
                                    <div class="info-content">
                                        <h2 class="info-title">
                                            <?php esc_html_e( 'Create your first Store Checkout', 'wpfnl' ); ?>
                                        </h2>
                                        <p class="info-description">
                                            <?php esc_html_e( 'Replace the default WooCommerce checkout with a custom checkout funnel to boost conversions.', 'wpfnl' ); ?>
                                        </p>

                                        <ul class="info-features">
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="8" viewBox="0 0 11 8" fill="none"><path d="M10.0326 0.699951L3.61589 7.11662L0.699219 4.19995" stroke="#444d5e" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <?php esc_html_e( 'Custom Checkout Pages', 'wpfnl' ); ?>
                                            </li>
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="8" viewBox="0 0 11 8" fill="none"><path d="M10.0326 0.699951L3.61589 7.11662L0.699219 4.19995" stroke="#444d5e" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <?php esc_html_e( 'Order Bumps', 'wpfnl' ); ?>
                                            </li>
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="8" viewBox="0 0 11 8" fill="none"><path d="M10.0326 0.699951L3.61589 7.11662L0.699219 4.19995" stroke="#444d5e" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <?php esc_html_e( 'Conditional Checkout Routing', 'wpfnl' ); ?>
                                            </li>
                                            <li class="feature-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="8" viewBox="0 0 11 8" fill="none"><path d="M10.0326 0.699951L3.61589 7.11662L0.699219 4.19995" stroke="#444d5e" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <?php esc_html_e( 'Conversion Templates', 'wpfnl' ); ?>
                                            </li>
                                        </ul>

                                        <div class="create-new-funnel">
                                            <a href="#" id="wpfnl-create-store-checkout"
                                               class="btn-default add-new-funnel-btn">
                                                <?php require WPFNL_DIR . '/admin/partials/icons/plus-icon.php'; ?>
                                                <?php esc_html_e( 'Store Checkout', 'wpfnl' ); ?>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="video-wrapper">
                                        <div class="video-preview" tabindex="0" role="button" aria-label="Play video">
                                            <img
                                                src="<?php echo esc_url( WPFNL_URL . 'admin/assets/images/no-funnel-video-poster.webp' ); ?>"
                                                alt="no-funnel-video-poster"
                                                class="video-poster"
                                                width="569"
                                                height="373"
                                            />

                                            <div class="play-overlay">
                                                <div class="play-button no-funnel-play-button">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" viewBox="0 0 16 20" fill="none"><path d="M14.408 7.19013L3.24942 0.312012C2.93046 0.110674 2.56721 0.00302319 2.19661 0C1.61403 0 1.05532 0.246909 0.643373 0.686411C0.231428 1.12591 0 1.722 0 2.34355V17.1052C7.40914e-05 17.5176 0.10371 17.9225 0.300131 18.2778C0.496553 18.6331 0.778589 18.9257 1.11691 19.1253C1.45524 19.3249 1.8375 19.4241 2.22396 19.4127C2.61041 19.4013 2.98695 19.2797 3.31441 19.0604L14.4859 11.5306C14.8333 11.2986 15.1184 10.9746 15.3135 10.5902C15.5086 10.2057 15.607 9.77389 15.5992 9.33678C15.5913 8.89967 15.4775 8.47219 15.2687 8.09598C15.0599 7.71978 14.7634 7.40769 14.408 7.19013Z" fill="#6e42d3"/></svg>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Video Popup -->
                                        <div id="no-funnel-video-popup" class="video-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="video-popup-title">
                                            <div class="video-popup-content">
                                                <button class="video-popup-close" aria-label="Close video">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1 1L13 13M13 1L1 13" stroke="#1D2327" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                                <h2 id="video-popup-title" class="visually-hidden"><?php esc_html_e( 'Video Tutorial', 'wpfnl' ); ?></h2>
                                                <div class="video-container">
                                                    <iframe
                                                        id="no-funnel-video-iframe"
                                                        width="550"
                                                        height="310"
                                                        src=""
                                                        data-src="https://www.youtube.com/embed/GrzIRl5jfBE?autoplay=1"
                                                        title="WPFunnels Store Checkout Tutorial"
                                                        frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen
                                                    ></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div><!-- /funnel-list__wrapper -->
            </div><!-- /wpfnl-dashboard__inner-content -->

            <!-- Toaster -->
            <div id="wpfnl-toaster-wrapper">
                <div class="quick-toastify-alert-toast">
                    <div class="quick-toastify-alert-container">
                        <div class="quick-toastify-successfull-icon" id="wpfnl-toaster-icon"></div>
                        <p id="wpfnl-toaster-message"></p>
                        <div class="quick-toastify-cross-icon" id="wpfnl-toaster-close-btn">
                            <svg width="10" height="10" fill="none" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#686f7f" d="M.948 9.995a.94.94 0 01-.673-.23.966.966 0 010-1.352L8.317.278a.94.94 0 011.339.045c.323.35.342.887.044 1.258L1.611 9.765a.94.94 0 01-.663.23z"/>
                                <path fill="#686f7f" d="M8.98 9.995a.942.942 0 01-.664-.278L.275 1.582A.966.966 0 01.378.23a.939.939 0 011.232 0L9.7 8.366a.966.966 0 010 1.399.94.94 0 01-.72.23z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.dashboard-nav__content -->
    </div>
</div>

<style>
/* Analytics icon-wrap: blue variant */
.wpfnl-sc-analytics-overview .wpfnl-sc-analytics-card__icon .icon-wrap--blue {
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    border: 1px solid #BFDBFE;
}

/* Enabled status — green pill */
.wpfnl-store-checkout-listing-page .funnel-list__wrapper .funnel__single-list .wpfnl-status.enabled .post-status {
    background: rgba(35, 150, 84, 0.1);
    color: #239654;
}

/* Page title */
.wpfnl-sc-page-title {
    font-size: 16px;
    font-weight: 600;
    color: #363B4E;
    margin: 0;
    line-height: 1.4;
}

/* ── Name cell layout ─────────────────────────────────────── */
.wpfnl-store-checkout-listing-page .wpfnl-sc-name-cell {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
}
.wpfnl-store-checkout-listing-page .wpfnl-sc-name-inner {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 4px;
}

/* ── Condition line (single row, ellipsis) ────────────────── */
.wpfnl-store-checkout-listing-page .sc-condition-line {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    cursor: default;
}
.wpfnl-store-checkout-listing-page .sc-condition-line__text {
    font-size: 12px;
    line-height: 1.25;
    font-weight: 500;
    text-transform: capitalize;
    border-radius: 100px;
    padding: 4px 10px;
    display: inline-block;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* Active condition — green pill (like Published) */
.wpfnl-store-checkout-listing-page .sc-condition-line--active .sc-condition-line__text {
    background: rgba(35, 150, 84, 0.1);
    color: #239654;
}
/* No condition — gray pill (like Draft) */
.wpfnl-store-checkout-listing-page .sc-condition-line--inactive .sc-condition-line__text {
    background: rgba(122, 139, 154, 0.15);
    color: #7A8B9A;
}

/* ── Priority badge (pill, follows Published pattern) ─────── */
.wpfnl-store-checkout-listing-page .wpfnl-sc-priority {
    width: 100px;
}
.wpfnl-store-checkout-listing-page .list-body .wpfnl-sc-priority {
    display: flex;
    align-items: center;
}
.wpfnl-store-checkout-listing-page .sc-priority-badge {
    font-size: 12px;
    line-height: 1.25;
    font-weight: 500;
    text-transform: capitalize;
    border-radius: 100px;
    display: inline-block;
    padding: 5px 10px 4px;
    cursor: default;
    white-space: nowrap;
}
/* Active priority (Highest / #N) — purple pill */
.wpfnl-store-checkout-listing-page .sc-priority-badge--active {
    background: rgba(110, 66, 211, 0.1);
    color: #6E42D3;
}
/* Fallback — gray pill */
.wpfnl-store-checkout-listing-page .sc-priority-badge--fallback {
    background: rgba(122, 139, 154, 0.15);
    color: #7A8B9A;
}
/* Overridden — red/muted pill */
.wpfnl-store-checkout-listing-page .sc-priority-badge--overridden {
    background: rgba(220, 53, 69, 0.1);
    color: #DC3545;
}

/* ── Condition line tooltip ───────────────────────────────── */
.wpfnl-store-checkout-listing-page .sc-condition-line {
    position: relative;
}
.wpfnl-store-checkout-listing-page .sc-condition-line__tooltip {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 0;
    background: #0C1015;
    border-radius: 6px;
    padding: 7px 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.4;
    white-space: normal;
    width: 240px;
    z-index: 4;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}
.wpfnl-store-checkout-listing-page .sc-condition-line__tooltip:before {
    content: "";
    position: absolute;
    left: 16px;
    bottom: -4px;
    width: 10px;
    height: 10px;
    background: #0C1015;
    transform: rotate(45deg);
    border-radius: 2px;
}
.wpfnl-store-checkout-listing-page .sc-condition-line:hover .sc-condition-line__tooltip {
    opacity: 1;
    visibility: visible;
}

/* ── Priority badge tooltip ───────────────────────────────── */
.wpfnl-store-checkout-listing-page .sc-priority-badge {
    position: relative;
}
.wpfnl-store-checkout-listing-page .sc-priority-badge__tooltip {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    background: #0C1015;
    border-radius: 6px;
    padding: 7px 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.4;
    white-space: normal;
    width: 200px;
    z-index: 4;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}
.wpfnl-store-checkout-listing-page .sc-priority-badge__tooltip:before {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -4px;
    width: 10px;
    height: 10px;
    background: #0C1015;
    transform: rotate(45deg) translateX(-50%);
    border-radius: 2px;
}
.wpfnl-store-checkout-listing-page .sc-priority-badge:hover .sc-priority-badge__tooltip {
    opacity: 1;
    visibility: visible;
}
</style>

<script type="text/javascript">
(function ($) {
    'use strict';

    var scListingUrl = <?php echo wp_json_encode( esc_url_raw( $sc_listing_url ) ); ?>;
    var scTrashUrl   = <?php echo wp_json_encode( esc_url_raw( $sc_trash_url ) ); ?>;
    var scView       = <?php echo wp_json_encode( $sc_view ); ?>;

    $(document).ready(function () {

        // ── Analytics toggle ────────────────────────────────────────────
        $('.wpfnl-sc-analytics-toggle').on('click', function () {
            $('.wpfnl-sc-analytics-overview').toggleClass('collapsed');
        });

        // ── Bulk selection ──────────────────────────────────────────────
        var selectedScIds = [];

        function updateScBulkBar() {
            var count = selectedScIds.length;
            if ( count > 0 ) {
                $('.funnel__single-list.list-header .bulk-action-wrapper').css('display', 'flex');
            } else {
                $('.funnel__single-list.list-header .bulk-action-wrapper').css('display', 'none');
            }
            $('.selected-funnel-count').text( count + ' ' + (count === 1 ? 'Store Checkout' : 'Store Checkouts') );
        }

        // Master checkbox: select / deselect all visible rows
        $(document).on('change', '#wpfnl-sc-bulk-select', function () {
            var checked = $(this).is(':checked');
            $('input[name="wpfnl-sc-list-select"]').each(function () {
                $(this).prop('checked', checked);
                var id = parseInt( $(this).data('id'), 10 );
                if ( checked ) {
                    if ( selectedScIds.indexOf(id) === -1 ) {
                        selectedScIds.push(id);
                    }
                } else {
                    selectedScIds = selectedScIds.filter(function (v) { return v !== id; });
                }
            });
            updateScBulkBar();
        });

        // Per-row checkbox
        $(document).on('change', 'input[name="wpfnl-sc-list-select"]', function () {
            var id = parseInt( $(this).data('id'), 10 );
            if ( $(this).is(':checked') ) {
                if ( selectedScIds.indexOf(id) === -1 ) {
                    selectedScIds.push(id);
                }
            } else {
                selectedScIds = selectedScIds.filter(function (v) { return v !== id; });
                $('#wpfnl-sc-bulk-select').prop('checked', false);
            }
            updateScBulkBar();
        });

        // Bulk trash
        $(document).on('click', '.wpfnl-sc-bulk-trash', function (e) {
            e.preventDefault();
            if ( selectedScIds.length === 0 ) return;
            if ( ! confirm('Are you sure you want to trash the selected store checkouts?') ) return;

            var ids    = selectedScIds.slice();
            var done   = 0;
            var failed = 0;

            ids.forEach(function (funnel_id) {
                wpAjaxHelperRequest('trash-funnel', { funnel_id: funnel_id })
                    .success(function () {
                        done++;
                        if ( done + failed === ids.length ) {
                            localStorage.setItem('wpfnl_show_toast', 'trash_success');
                            window.location.href = scListingUrl;
                        }
                    })
                    .error(function () {
                        failed++;
                        if ( done + failed === ids.length ) {
                            window.location.href = scListingUrl;
                        }
                    });
            });
        });

        // ── End bulk selection ──────────────────────────────────────────

        // Create store checkout — open template library
        $(document).on('click', '#wpfnl-create-store-checkout', function (e) {
            e.preventDefault();
            window.wpfnlStoreCheckoutMode = true;
            setTimeout(function () {
                $('#template-library-modal').show();
                $('#wpfnl-create-funnel__inner-content').show();
                $('#wpfnl-create-steps_inner-content').hide();
            }, 100);
        });

        // Toggle status: Publish ↔ Move to Draft
        // Use direct binding — delegated $(document).on() is blocked by
        // stopPropagation() in wpfnl-admin.js on .wpfnl-dropdown.
        $('.wpfnl-sc-status-toggle').on('click', function (e) {
            e.preventDefault();
            var funnel_id  = $(this).data('id');
            var new_status = $(this).data('status');
            if ( ! confirm('Are you sure?') ) return;
            wpAjaxHelperRequest('update-funnel-status', { funnel_id: funnel_id, status: new_status })
                .success(function () {
                    localStorage.setItem('wpfnl_show_toast', 'status_updated');
                    window.location.href = scListingUrl;
                })
                .error(function () {
                    console.error('Status update failed.');
                });
        });

        // Trash store checkout funnel
        $('.wpfnl-sc-trash-funnel').on('click', function (e) {
            e.preventDefault();
            var funnel_id = $(this).data('id');
            if ( ! confirm('Are you sure you want to trash this store checkout?') ) return;
            wpAjaxHelperRequest('trash-funnel', { funnel_id: funnel_id })
                .success(function () {
                    localStorage.setItem('wpfnl_show_toast', 'trash_success');
                    window.location.href = scListingUrl;
                })
                .error(function () {
                    console.error('Trash failed.');
                });
        });

        // Restore store checkout funnel
        $('.wpfnl-sc-restore-funnel').on('click', function (e) {
            e.preventDefault();
            var funnel_id = $(this).data('id');
            if ( ! confirm('Are you sure you want to restore this store checkout?') ) return;
            wpAjaxHelperRequest('restore-funnel', { funnel_id: funnel_id })
                .success(function () {
                    localStorage.setItem('wpfnl_show_toast', 'restore_success');
                    window.location.href = scTrashUrl;
                })
                .error(function () {
                    console.error('Restore failed.');
                });
        });

        // Delete store checkout funnel permanently
        $('.wpfnl-sc-delete-funnel').on('click', function (e) {
            e.preventDefault();
            var funnel_id = $(this).data('id');
            if ( ! confirm('Are you sure you want to permanently delete this store checkout? This cannot be undone.') ) return;
            wpAjaxHelperRequest('delete-funnel', { funnel_id: funnel_id })
                .success(function () {
                    localStorage.setItem('wpfnl_show_toast', 'delete_success');
                    window.location.href = scTrashUrl;
                })
                .error(function () {
                    console.error('Delete failed.');
                });
        });

        // Bulk restore
        $(document).on('click', '.wpfnl-sc-bulk-restore', function (e) {
            e.preventDefault();
            if ( selectedScIds.length === 0 ) return;
            if ( ! confirm('Are you sure you want to restore the selected store checkouts?') ) return;

            var ids = selectedScIds.slice(), done = 0, failed = 0;
            ids.forEach(function (funnel_id) {
                wpAjaxHelperRequest('restore-funnel', { funnel_id: funnel_id })
                    .success(function () {
                        done++;
                        if ( done + failed === ids.length ) {
                            localStorage.setItem('wpfnl_show_toast', 'restore_success');
                            window.location.href = scTrashUrl;
                        }
                    })
                    .error(function () {
                        failed++;
                        if ( done + failed === ids.length ) { window.location.href = scTrashUrl; }
                    });
            });
        });

        // Bulk delete permanently
        $(document).on('click', '.wpfnl-sc-bulk-delete', function (e) {
            e.preventDefault();
            if ( selectedScIds.length === 0 ) return;
            if ( ! confirm('Are you sure you want to permanently delete the selected store checkouts? This cannot be undone.') ) return;

            var ids = selectedScIds.slice(), done = 0, failed = 0;
            ids.forEach(function (funnel_id) {
                wpAjaxHelperRequest('delete-funnel', { funnel_id: funnel_id })
                    .success(function () {
                        done++;
                        if ( done + failed === ids.length ) {
                            localStorage.setItem('wpfnl_show_toast', 'delete_success');
                            window.location.href = scTrashUrl;
                        }
                    })
                    .error(function () {
                        failed++;
                        if ( done + failed === ids.length ) { window.location.href = scTrashUrl; }
                    });
            });
        });

        // Per-page selector
        $(document).on('change', '#wpfnl-sc-per-page', function () {
            var pp  = $(this).val();
            var url = new URL( scView === 'trash' ? scTrashUrl : scListingUrl );
            url.searchParams.set('per_page', pp);
            url.searchParams.set('pageno', 1);
            window.location.href = url.toString();
        });

    });
})(jQuery);
</script>

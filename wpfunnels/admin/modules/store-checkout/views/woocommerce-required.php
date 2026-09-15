<?php
/**
 * Store Checkout — WooCommerce required view
 *
 * Store Checkout replaces the WooCommerce checkout, so the screen is useless
 * without WooCommerce. Instead of rendering an empty listing we render the
 * dashboard shell with a blocking modal that installs or activates
 * WooCommerce in place.
 *
 * @package WPFunnels
 * @since   3.13.1
 */

use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$wpfnl_wc_plugin = 'woocommerce/woocommerce.php';
$wpfnl_wc_status = Wpfnl_functions::is_plugin_installed( $wpfnl_wc_plugin ) ? 'inactive' : 'not-installed';

// Installing and activating need different capabilities — only offer the
// action the current user is actually allowed to perform.
if ( 'not-installed' === $wpfnl_wc_status ) {
    $wpfnl_can_act      = current_user_can( 'install_plugins' );
    $wpfnl_button_label = __( 'Install WooCommerce', 'wpfnl' );
} else {
    $wpfnl_can_act      = current_user_can( 'activate_plugins' );
    $wpfnl_button_label = __( 'Activate WooCommerce', 'wpfnl' );
}

$wpfnl_dashboard_url = add_query_arg( array( 'page' => WPFNL_MAIN_PAGE_SLUG ), admin_url( 'admin.php' ) );
?>

<div class="wpfnl wpfnl-store-checkout-page wpfnl-wc-required-page">
    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
        </nav>

        <div class="dashboard-nav__content"></div>
        <!-- /.dashboard-nav__content -->
    </div>

    <div class="wpfnl-wc-required-modal" role="dialog" aria-modal="true" aria-labelledby="wpfnl-wc-required-title">
        <div class="wpfnl-wc-required-modal__backdrop"></div>

        <div class="wpfnl-wc-required-modal__panel">
            <div class="wpfnl-wc-required-modal__body">
                <span class="wpfnl-wc-required-modal__icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M12 16.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <div class="wpfnl-wc-required-modal__content">
                    <h2 id="wpfnl-wc-required-title" class="wpfnl-wc-required-modal__title">
                        <?php esc_html_e( 'Plugin Required', 'wpfnl' ); ?>
                    </h2>
                    <p class="wpfnl-wc-required-modal__text">
                        <?php esc_html_e( 'You need WooCommerce plugin installed and activated to access this page.', 'wpfnl' ); ?>
                    </p>
                    <?php if ( ! $wpfnl_can_act ) : ?>
                        <p class="wpfnl-wc-required-modal__text">
                            <?php esc_html_e( 'You do not have permission to do this. Please contact your site administrator.', 'wpfnl' ); ?>
                        </p>
                    <?php endif; ?>
                    <p class="wpfnl-wc-required-modal__error" style="display: none;"></p>
                </div>
            </div>

            <div class="wpfnl-wc-required-modal__footer">
                <a href="<?php echo esc_url( $wpfnl_dashboard_url ); ?>" class="wpfnl-wc-required-modal__btn wpfnl-wc-required-modal__btn--secondary">
                    <?php esc_html_e( 'Go Back', 'wpfnl' ); ?>
                </a>

                <?php if ( $wpfnl_can_act ) : ?>
                    <button type="button" id="wpfnl-wc-required-action" class="wpfnl-wc-required-modal__btn wpfnl-wc-required-modal__btn--primary">
                        <span class="wpfnl-wc-required-modal__spinner" aria-hidden="true"></span>
                        <span class="wpfnl-wc-required-modal__label"><?php echo esc_html( $wpfnl_button_label ); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Core prints the filesystem credentials form here; wp.updates needs it when
// the install cannot be written directly.
if ( function_exists( 'wp_print_request_filesystem_credentials_modal' ) ) {
    wp_print_request_filesystem_credentials_modal();
}
?>

<style>
.wpfnl-wc-required-page .dashboard-nav__content {
    min-height: 420px;
}

.wpfnl-wc-required-modal {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.wpfnl-wc-required-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(54, 59, 78, 0.6);
}

.wpfnl-wc-required-modal__panel {
    position: relative;
    width: 100%;
    max-width: 512px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 24px 48px -12px rgba(16, 24, 40, 0.25);
    overflow: hidden;
}

.wpfnl-wc-required-modal__body {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 28px 28px 20px;
}

.wpfnl-wc-required-modal__icon {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.1);
    color: #DC3545;
}

.wpfnl-wc-required-modal__content {
    min-width: 0;
}

.wpfnl-wc-required-modal__title {
    margin: 0 0 8px;
    padding: 0;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
    color: #363B4E;
}

.wpfnl-wc-required-modal__text {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: #7A8B9A;
}

.wpfnl-wc-required-modal__text + .wpfnl-wc-required-modal__text {
    margin-top: 8px;
}

.wpfnl-wc-required-modal__error {
    margin: 12px 0 0;
    font-size: 13px;
    line-height: 1.5;
    color: #DC3545;
}

.wpfnl-wc-required-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 4px 28px 24px;
}

/* Buttons are styled from scratch rather than reusing .btn-default so the
   dashboard's global button rules cannot bleed in (capitalize, letter-spacing,
   130px min-width, washed-out label colour). */
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-sizing: border-box;
    height: 42px;
    min-width: 0;
    margin: 0;
    padding: 0 20px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: none;
    box-shadow: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    line-height: 1;
    letter-spacing: 0;
    text-align: center;
    text-transform: none;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn > span {
    color: inherit;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn:focus {
    outline: none;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(110, 66, 211, 0.25);
}

/* Secondary — quiet escape hatch. */
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--secondary,
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--secondary:visited {
    border-color: #E4E6EB;
    background: #fff;
    color: #3C3F47;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--secondary:hover,
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--secondary:focus {
    border-color: #D5D8E0;
    background: #F5F6FA;
    color: #3C3F47;
}

/* Primary — the action we actually want taken. */
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary {
    background-color: #6E42D3;
    color: #fff;
    box-shadow: 0 6px 14px -4px rgba(110, 66, 211, 0.45);
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary:hover,
.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary:focus {
    background-color: #4C25A5;
    color: #fff;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary:active {
    box-shadow: none;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary.is-busy {
    pointer-events: none;
    background-color: #6E42D3;
    box-shadow: none;
    opacity: 0.75;
}

.wpfnl-wc-required-modal__spinner {
    display: none;
    flex: 0 0 14px;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #fff;
    border-radius: 50%;
    animation: wpfnl-wc-required-spin 0.7s linear infinite;
}

.wpfnl-wc-required-page .wpfnl-wc-required-modal__btn--primary.is-busy .wpfnl-wc-required-modal__spinner {
    display: block;
}

@keyframes wpfnl-wc-required-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

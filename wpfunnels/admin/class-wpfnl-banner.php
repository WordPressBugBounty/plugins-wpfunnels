<?php

namespace WPFunnels\Admin\Banner;

/**
 * SpecialOccasionBanner Class
 *
 * This class is responsible for displaying a special occasion banner in the WordPress admin.
 *
 * @package WPFunnels\Admin\Banner
 */
class SpecialOccasionBanner
{

    /**
     * The occasion identifier.
     *
     * @var string
     */
    private $occasion;

    /**
     * The button link.
     *
     * @var string
     */
    private $btn_link;

    /**
     * The start date and time for displaying the banner.
     *
     * @var int
     */
    private $start_date;

    /**
     * The end date and time for displaying the banner.
     *
     * @var int
     */
    private $end_date;

    /**
     * Constructor method for SpecialOccasionBanner class.
     *
     * @param string $occasion   The occasion identifier.
     * @param string $start_date The start date and time for displaying the banner.
     * @param string $end_date   The end date and time for displaying the banner.
     */
    public function __construct($occasion, $start_date, $end_date, $btn_link = '#')
    {
        $this->occasion     = $occasion;
        $this->btn_link     = $btn_link;
        $this->start_date   = strtotime($start_date);
        $this->end_date     = strtotime($end_date);

        if ('yes' === get_option('_is_show_halloween_26_banner', 'yes')) {
            // Hook into the admin_notices action to display the banner
            add_action('admin_notices', [$this, 'display_banner']);
            add_action('admin_head', array($this, 'add_styles'));
        }
    }

    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_banner()
    {
        $screen                     = get_current_screen();
        // Legacy screen ids only. The React admin (`toplevel_page_wpfunnels`,
        // which serves #/funnels, #/integrations and the other routes) drops
        // every admin notice before render — see
        // Wpfnl_Menus::remove_notices_from_react_admin() — so it cannot carry
        // this banner. `wpfunnels_page_wpfunnels_integrations` stays for the
        // legacy Integrations page (opt-out, older Pro).
        $promotional_notice_pages   = ['dashboard', 'plugins', 'wpfunnels_page_store_checkout','wpfunnels_page_wp_funnels', 'wpfunnels_page_wpfunnels_integrations', 'wpfunnels_page_wpfnl_automations', 'wpfunnels_page_edit_funnel', 'wpfunnels_page_wpfnl_settings'];
        $current_date_time          = current_time('timestamp');
        $discount                   = '30% OFF';

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ($current_date_time < $this->start_date || $current_date_time > $this->end_date) {
            return;
        }
    ?>
        <div class="gwpf-promotional-notice <?php echo esc_attr($this->occasion); ?>-banner notice wp-exclude-emoji">
            <div class="gwpf-tb__notification">
                <div class="banner-overflow">
                    <section class="gwpf-promotional-banner" aria-label="<?php esc_attr_e('Halloween sale', 'wpfunnels'); ?>">
                        <div class="gwpf-container">
                            <div class="promotional-banner">
                                <div class="banner-content">
                                    <p class="banner-text banner-text--occasion">
                                        <span class="banner-emoji" aria-hidden="true">👻</span>
                                        <span>
                                            <?php
                                            printf(
                                                /* translators: %s: "Halloween" highlighted. */
                                                esc_html__('Spooktacular %s Sale!', 'wpfunnels'),
                                                '<span class="highlighted-text">' . esc_html__('Halloween', 'wpfunnels') . '</span>'
                                            );
                                            ?>
                                        </span>
                                        <span class="banner-emoji" aria-hidden="true">🎃</span>
                                    </p>

                                    <p class="banner-text banner-text--offer">
                                        <?php
                                        printf(
                                            /* translators: 1: discount amount, e.g. "30% OFF". 2: product name. */
                                            esc_html__('Flat %1$s On %2$s', 'wpfunnels'),
                                            '<span class="discount-text">' . esc_html($discount) . '</span>',
                                            '<strong>' . esc_html__('WPFunnels!', 'wpfunnels') . '</strong>'
                                        );
                                        ?>
                                    </p>

                                    <a href="<?php echo esc_url($this->btn_link); ?>" class="cta-button" target="_blank" rel="noopener noreferrer">
                                        <?php
                                        /* translators: %s: discount amount, e.g. "30% OFF". */
                                        printf(esc_html__('Get %s', 'wpfunnels'), esc_html($discount));
                                        ?>
                                        <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" stroke="currentColor" stroke-width=".5" d="M11 1.781v8.438a.781.781 0 11-1.563 0V3.667l-7.103 7.104a.781.781 0 01-1.105-1.105l7.104-7.104H1.78A.781.781 0 111.78 1h8.438a.781.781 0 01.78.781z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <button class="close-promotional-banner" type="button" aria-label="<?php esc_attr_e('Close banner', 'wpfunnels'); ?>">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.97L1 11.96m0-9.99l10 9.99"/></svg>
                </button>
            </div>
        </div>
        <!-- .gwpf-tb-notification end -->
    <?php
    }

    /**
     * Adds internal CSS styles for the special occasion banners.
     */
    public function add_styles() {
        ?>
        <style type="text/css">
            .gwpf-tb__notification,
            .gwpf-tb__notification * {
                box-sizing: border-box;
            }

            .gwpf-promotional-notice.notice {
                display: block;
                background: none;
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            .gwpf-tb__notification {
                width: calc(100% - 20px);
                margin: 20px 0 20px;
                position: relative;
                border: none;
                box-shadow: none;
                display: block;
            }

            .wpfunnels_page_wpfnl_settings .gwpf-tb__notification,
            .wpfunnels_page_wp_funnels .gwpf-tb__notification {
                width: calc(100% - 40px);
                margin: 20px 0 20px 20px;
            }

            .gwpf-tb__notification .banner-overflow {
                position: relative;
                width: 100%;
                z-index: 1;
                overflow: hidden;
                border-radius: 6px;
            }

            /* ---banner style start--- */
            .gwpf-promotional-banner {
                position: relative;
                z-index: 1111;
                background-color: #2B1067;
                background-image:
                    linear-gradient(90deg, #7047B7 0, rgba(43, 16, 103, 0) 150px),
                    linear-gradient(270deg, #4E2C90 0, rgba(43, 16, 103, 0) 220px);
                font-family: 'Inter', sans-serif;
            }
            .gwpf-promotional-banner * {
                font-family: 'Inter', sans-serif;
            }
            .gwpf-promotional-banner .promotional-banner {
                color: #fff;
                padding: 15px 50px 15px 20px;
                min-height: 60px;
                text-align: center;
                font-size: 16px;
                line-height: 1.4;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .gwpf-promotional-banner .banner-content {
                max-width: 1200px;
                margin: 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-wrap: wrap;
                column-gap: 24px;
                row-gap: 8px;
            }
            .gwpf-promotional-banner .banner-text {
                display: inline-flex;
                align-items: center;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
                margin: 0;
                padding: 0;
                font-size: 16px;
                font-weight: 500;
                line-height: 1.4;
                letter-spacing: 0;
                color: #fff;
            }
            .gwpf-promotional-banner .banner-text--offer {
                display: inline-block;
            }
            .gwpf-promotional-banner .banner-emoji {
                font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;
                font-size: 18px;
                line-height: 1;
                flex: none;
            }
            .gwpf-promotional-banner .highlighted-text {
                color: #EE8134;
                font-size: 18px;
                font-weight: 800;
            }
            .gwpf-promotional-banner .discount-text {
                color: #24EC2C;
                font-size: 18px;
                font-weight: 800;
            }
            .gwpf-promotional-banner .banner-text strong {
                color: #fff;
                font-weight: 700;
            }

            /* ---cta--- */
            .gwpf-promotional-banner .cta-button {
                margin-left: 30px;
                padding: 0 0 3px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: #EE8134;
                font-size: 15px;
                font-weight: 700;
                line-height: 1.2;
                letter-spacing: 0;
                text-decoration: none;
                border-bottom: 2px solid #EE8134;
                background: transparent;
                box-shadow: none;
                flex: none;
                transition: all .3s ease;
            }
            .gwpf-promotional-banner .cta-button svg {
                display: block;
                transition: transform .3s ease;
            }
            .gwpf-promotional-banner .cta-button:hover,
            .gwpf-promotional-banner .cta-button:focus,
            .gwpf-promotional-banner .cta-button:visited {
                color: #EE8134;
                text-decoration: none;
                box-shadow: none;
                outline: none;
            }
            .gwpf-promotional-banner .cta-button:hover svg {
                transform: translate(3px, -3px);
            }

            .gwpf-tb__notification .close-promotional-banner {
                position: absolute;
                top: 50%;
                right: 10px;
                transform: translateY(-50%);
                background: transparent;
                border: none;
                padding: 0;
                border-radius: 0;
                cursor: pointer;
                z-index: 9;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .gwpf-tb__notification .close-promotional-banner svg {
                width: 11px;
                height: 11px;
                display: block;
            }

            @media only screen and (max-width: 1199px) {
                .gwpf-promotional-banner .cta-button {
                    margin-left: 0;
                }
            }

            @media only screen and (max-width: 991px) {
                .gwpf-tb__notification {
                    margin: 60px 0 20px;
                }
            }

            @media only screen and (max-width: 575px) {
                .gwpf-promotional-banner .promotional-banner {
                    padding: 16px 40px;
                }

                .gwpf-promotional-banner .banner-text {
                    font-size: 14px;
                }
            }
        </style>
        <?php
    }

}

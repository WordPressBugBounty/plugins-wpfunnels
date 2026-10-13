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

        if ('yes' === get_option('_is_show_us_banner', 'yes')) {
            // Hook into the admin_notices action to display the banner
            add_action('admin_notices', [$this, 'display_banner']);
            add_action('admin_head', array($this, 'add_styles'));
        }
    }

    /**
     * Calculate time remaining until Halloween
     *
     * @return array Time remaining in days, hours, and minutes
     */
    public function wpf_get_promotion_banner_countdown()
    {
        $end_date = strtotime('2026-07-07 23:59:59');
        $now      = current_time('timestamp');
        $diff     = $end_date - $now;

        return array(
            'days' => floor($diff / (60 * 60 * 24)),
            'hours' => floor(($diff % (60 * 60 * 24)) / (60 * 60)),
            'mins' => floor(($diff % (60 * 60)) / 60),
            'secs' => $diff % 60,
        );
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

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ($current_date_time < $this->start_date || $current_date_time > $this->end_date) {
            return;
        }

        // Calculate the time remaining in seconds
        $time_remaining = $this->end_date - $current_date_time;

        $countdown = $this->wpf_get_promotion_banner_countdown();
    ?>
        <div class="gwpf-promotional-notice <?php echo esc_attr($this->occasion); ?>-banner notice">
            <div class="gwpf-tb__notification">
                <div class="banner-overflow">
                    <section class="gwpf-promotional-banner" aria-labelledby="US independence day offer">
                        <div class="gwpf-container">
                            <div class="promotional-banner">
                                <div class="banner-content">
									<div class="banner-logo-area">
										<span class="us-flag">
											<img src="<?php echo WPFNL_DIR_URL . 'admin/assets/images/us-flag.webp'; ?>" alt="us-flag" width="117" height="70">
										</span>
										<span class="discount-text">
											<small><?php echo __('Happy 4th Of July!', 'wpfunnels'); ?></small>
											<?php echo __('Get 30% Off', 'wpfunnels'); ?>
										</span>
									</div>

                                    <div class="countdown-timer" id="wpf-promo-countdown">
                                        <div class="countdown-item">
                                            <span id="wpf-promo-days"><?php echo esc_html(str_pad($countdown['days'], 2, '0', STR_PAD_LEFT)); ?></span>
                                            <small><?php echo __('DAY', 'wpfunnels'); ?></small>
                                        </div>
                                        <div class="countdown-item">
                                            <span id="wpf-promo-hours"><?php echo esc_html(str_pad($countdown['hours'], 2, '0', STR_PAD_LEFT)); ?></span>
                                            <small><?php echo __('HR', 'wpfunnels'); ?></small>
                                        </div>
                                        <div class="countdown-item">
                                            <span id="wpf-promo-mins"><?php echo esc_html(str_pad($countdown['mins'], 2, '0', STR_PAD_LEFT)); ?></span>
                                            <small><?php echo __('MIN', 'wpfunnels'); ?></small>
                                        </div>
                                        <div class="countdown-item">
                                            <span id="wpf-promo-secs"><?php echo esc_html(str_pad($countdown['secs'], 2, '0', STR_PAD_LEFT)); ?></span>
                                            <small><?php echo __('SEC', 'wpfunnels'); ?></small>
                                        </div>
                                    </div>

                                    <a href="<?php echo esc_url($this->btn_link); ?>" class="cta-button" role="button" aria-label="claim your deal" target="_blank">
                                        <?php
                                            echo __('Claim Your Deal', 'wpfunnels');
                                        ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="9" viewBox="0 0 8 9" fill="none"><path d="M8 0.703124V8.29686C8 8.48334 7.93415 8.66218 7.81694 8.79405C7.69973 8.92591 7.54076 8.99999 7.375 8.99999C7.20924 8.99999 7.05027 8.92591 6.93306 8.79405C6.81585 8.66218 6.75 8.48334 6.75 8.29686V2.40061L1.06695 8.79406C0.949738 8.92592 0.790765 9 0.625004 9C0.459243 9 0.30027 8.92592 0.183059 8.79406C0.0658485 8.6622 0 8.48335 0 8.29687C0 8.11039 0.0658485 7.93155 0.183059 7.79968L5.86613 1.40625H0.625012C0.459252 1.40625 0.300281 1.33217 0.183071 1.20031C0.0658608 1.06845 1.28672e-05 0.889604 1.28672e-05 0.703124C1.28672e-05 0.516644 0.0658608 0.337802 0.183071 0.20594C0.300281 0.0740789 0.459252 0 0.625012 0L7.375 0C7.54076 0 7.69973 0.0740789 7.81694 0.20594C7.93415 0.337802 8 0.516644 8 0.703124Z" fill="#fff"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <button class="close-promotional-banner" type="button" aria-label="close banner">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.97L1 11.96m0-9.99l10 9.99"/></svg>
                </button>
            </div>
        </div>
        <!-- .gwpf-tb-notification end -->

        <script>
            (function() {
                var countdownInterval;

                function pad(num) {
                    return String(num).padStart(2, '0');
                }

                function updateCountdown() {
                    var endDate = new Date("2026-07-07 23:59:59").getTime();
                    var now = new Date().getTime();
                    var timeLeft = endDate - now;

                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);

                        var banner = document.querySelector('.gwpf-promotional-notice');
                        if (banner) {
                            banner.style.display = 'none';
                        }

                        return;
                    }

                    var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                    var daysElement = document.getElementById('wpf-promo-days');
                    var hoursElement = document.getElementById('wpf-promo-hours');
                    var minsElement = document.getElementById('wpf-promo-mins');
                    var secsElement = document.getElementById('wpf-promo-secs');

                    if (daysElement) {
                        daysElement.innerHTML = pad(days);
                    }

                    if (hoursElement) {
                        hoursElement.innerHTML = pad(hours);
                    }

                    if (minsElement) {
                        minsElement.innerHTML = pad(minutes);
                    }
                    if (secsElement) {
                        secsElement.innerHTML = pad(seconds);
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    updateCountdown();
                    countdownInterval = setInterval(updateCountdown, 1000);
                });
            })();
        </script>
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
                background-repeat: no-repeat;
                background-size: cover;
                position: relative;
                border: none;
                box-shadow: none;
                display: block;
                max-height: 110px;
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
            }

            /* ---banner style start--- */
            .gwpf-promotional-banner {
                position: relative;
                z-index: 1111;
                padding: 0;
                /* padding: 6px 0; */
				background: #7D4CEF;
				box-shadow: 0 1px 1px 0 rgba(32, 28, 254, 0.10);
            }
            .gwpf-promotional-banner .promotional-banner {
                /* padding: 12px 20px; */
				padding-top: 1px;
				text-align: center;
				font-size: 14px;
				line-height: 1.4;
				position: relative;
				height: 70px;
            }
            .gwpf-promotional-banner .banner-content {
                max-width: 1200px;
                margin: 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-wrap: wrap;
                gap: 70px;
                row-gap: 8px;
                position: relative;
            }
			.gwpf-promotional-banner .banner-logo-area {
				display: flex;
				align-items: center;
				gap: 17px;
			}

			.gwpf-promotional-banner .banner-logo-area img {
				display: block;
				position: relative;
				top: -1px;
			}

			.gwpf-promotional-banner .discount-text {
				color: #FFF;
				font-family: 'Inter', sans-serif;
				font-size: 24px;
				font-weight: 700;
				line-height: 1.2;
				letter-spacing: -0.084px;
				display: flex;
				flex-flow: column;
				align-items: flex-start;
				gap: 7px;

			}

			.gwpf-promotional-banner .discount-text small {
				color: #FFF;
				font-family: 'Inter', sans-serif;
				font-size: 16px;
				font-weight: 500;
				line-height: 1;
				letter-spacing: -0.32px;
			}
            .gwpf-promotional-banner .banner-text {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
                row-gap: 0;
                justify-content: center;
                font-size: 15px;
    			color: #100627;
                font-weight: 400;
                line-height: 1.4;
                text-transform: capitalize;
                letter-spacing: 0;
            }
            .gwpf-promotional-banner .banner-text svg {
                display: block;
            }
            .gwpf-promotional-banner .banner-text .highlighted-text {
                color: #ee8134;
                font-weight: 700;
            }

            .gwpf-promotional-banner .countdown-timer {
                display: flex;
                align-items: center;
                gap: 2px;
            }
            .gwpf-promotional-banner .countdown-item {
                display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: 4px;
				min-width: 50px;
				height: 50px;
				padding: 4px 6px;
				color: #fff;
				line-height: 1.2;
				border: 1px solid #8555f5;
				background: rgba(54, 30, 107, 0.40);

            }

			.gwpf-promotional-banner .countdown-item:first-child {
				border-radius: 4px 0 0 4px;
			}

			.gwpf-promotional-banner .countdown-item:last-child {
				border-radius: 0 4px 4px 0;
			}

            .gwpf-promotional-banner .countdown-item span {
                font-size: 20px;
				font-weight: 600;
				line-height: 1;
				font-family: 'Inter', sans-serif;
            }
            .gwpf-promotional-banner .countdown-item small {
                text-transform: uppercase;
				color: rgba(255, 255, 255, 0.80);
				text-align: center;
				font-family: Inter;
				font-size: 12px;
				font-weight: 400;
				line-height: 1;
				letter-spacing: 0.24px;
            }
            .gwpf-promotional-banner .cta-button {
                color: #fff;
				font-size: 15px;
				font-style: normal;
				font-weight: 600;
				line-height: 1;
				text-decoration: none;
				display: inline-flex;
				align-items: center;
				gap: 5px;
				transition: all 0.2s ease;
				background: #F7941D;
				border-radius: 6px;
				padding: 12px 15px;
				font-family: 'Inter', sans-serif;
            }
            .gwpf-promotional-banner .cta-button svg {
                transition: transform 0.3s ease;
            }

            /* .gwpf-promotional-banner .cta-button:focus,
            .gwpf-promotional-banner .cta-button:visited {
                color: #3C1F7D !important;
            }
            .gwpf-promotional-banner .cta-button:hover {
                color: #3C1F7D !important;
            }
            .gwpf-promotional-banner .cta-button:hover svg path {
                stroke: #3C1F7D;
                fill: #3C1F7D;
            } */

			.gwpf-promotional-banner .cta-button:hover {
                text-decoration: none;
				color: #fff;
            }
            .gwpf-promotional-banner .cta-button:hover svg {
                transform: translateX(3px);
            }


            .gwpf-tb__notification .close-promotional-banner {
                position: absolute;
				top: 50%;
				right: 10px;
				transform: translateY(-50%);
                background: transparent;
                border: none;
                padding: 0;
                border-radius:0;
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
				.gwpf-promotional-banner .banner-content {
					gap: 40px;
					row-gap: 8px;
				}
			}

            @media only screen and (max-width: 991px) {
                .promotional-banner {
                    padding: 16px 20px;
                }

                .gwpf-tb__notification {
                    margin: 60px 0 20px;
                }

                .gwpf-tb__notification .close-promotional-banner {
                    width: 25px;
                    height: 25px;
                }
            }

            @media only screen and (max-width: 767px) {
                .wpvr-promotional-banner {
                    padding-top: 20px;
                    padding-bottom: 30px;
                    max-height: none;
                }

                .wpvr-promotional-banner {
                    max-height: none;
                }
            }

            @media only screen and (max-width: 575px) {
                .promotional-banner {
                    padding: 16px 55px;
                    font-size: 13px;
                }
                .gwpf-promotional-banner .halloween-bird-left {
                    left: -12px;
                    bottom: inherit;
                    top: -5px;
                }
                .gwpf-promotional-banner .halloween-bird-right {
                    right: -12px;
                    bottom: -7px;
                    top: inherit;
                }
            }
        </style>
        <?php
    }

}

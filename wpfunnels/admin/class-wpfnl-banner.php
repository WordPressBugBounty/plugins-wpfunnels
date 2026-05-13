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

        if ('yes' === get_option('_is_show_ugcify_banner', 'yes')) {
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
        $end_date = strtotime('2026-01-07 23:59:59');
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
        $promotional_notice_pages   = ['dashboard', 'plugins', 'wpfunnels_page_store_checkout','wpfunnels_page_wp_funnels', 'wpfunnels_page_wpfunnels_integrations', 'wpfunnels_page_wpfnl_automations', 'wpfunnels_page_edit_funnel', 'wpfunnels_page_wpfnl_settings'];
        $current_date_time          = current_time('timestamp');

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ($current_date_time < $this->start_date || $current_date_time > $this->end_date) {
            //return;
        }

        // Calculate the time remaining in seconds
        $time_remaining = $this->end_date - $current_date_time;

        $countdown = $this->wpf_get_promotion_banner_countdown();
    ?>
        <div class="gwpf-promotional-notice <?php echo esc_attr($this->occasion); ?>-banner notice">
            <div class="gwpf-tb__notification">
                <div class="banner-overflow">
                    <section class="gwpf-promotional-banner" aria-labelledby="UGCify-offer">
                        <div class="gwpf-container">
                            <div class="promotional-banner">
                                <div class="banner-content">
									<div class="banner-logo-area">
										<span class="new-tool-text"><?php echo __('New Tool Coming:', 'wpfunnels'); ?></span>
										<svg xmlns="http://www.w3.org/2000/svg" width="23" height="20" viewBox="0 0 23 20" fill="none"><path d="M1.06618 12.1359C8.90142 22.3152 17.4084 17.0302 21.0104 12.1256C21.0976 12.0069 21.286 12.1052 21.2364 12.2439C17.5346 22.5755 4.52336 22.5942 0.84551 12.2448C0.797424 12.1095 0.97859 12.0221 1.06618 12.1359Z" fill="#201cfe"/><path d="M3.38441 14.7801C7.55671 19.3578 14.4767 19.3577 18.6683 14.7962L19.2305 18.0621C19.4048 19.0746 18.6283 20 17.6009 20H4.4095C3.37407 20 2.59495 19.0608 2.78274 18.0425L3.38441 14.7801ZM18.4936 13.7809C14.7261 17.1915 8.66183 18.7568 3.58106 13.7132L5.28946 4.4507H16.8876L18.4936 13.7809ZM11.4455 8.30703C11.1791 7.76715 10.7417 7.76715 10.4726 8.30703L9.97917 9.30214C9.91189 9.44065 9.73246 9.57354 9.58387 9.59899L8.68947 9.74872C8.11753 9.84484 7.98578 10.2633 8.39511 10.676L9.09037 11.377C9.20812 11.4957 9.27268 11.7247 9.23624 11.8887L9.0371 12.7566C8.88013 13.4406 9.24466 13.7091 9.84463 13.3502L10.6829 12.8498C10.8371 12.7594 11.0866 12.7593 11.238 12.8498L12.0763 13.3502C12.679 13.7091 13.0407 13.4435 12.8837 12.7566L12.6847 11.8887C12.6482 11.7247 12.7127 11.4957 12.8304 11.377L13.5258 10.676C13.9379 10.2633 13.8032 9.84483 13.2313 9.74872L12.337 9.59899C12.1856 9.57355 12.0062 9.44065 11.9389 9.30214L11.4455 8.30703Z" fill="#201cfe"/><path d="M2.13955 12.1528C1.65099 12.0253 0.571895 12.1146 0.164049 13.491C-0.473269 12.5989 0.864968 10.942 2.13955 12.1528Z" fill="#201cfe"/><path d="M21.652 13.492C21.5932 12.9905 21.118 12.0176 19.6874 12.1378C20.287 11.2198 22.3169 11.8646 21.652 13.492Z" fill="#201cfe"/><path d="M14.02 3.50497C14.02 2.57539 13.6977 1.68389 13.1241 1.02658C12.5504 0.369272 11.7724 7.01809e-08 10.9611 0C10.1499 -7.01809e-08 9.37184 0.369272 8.7982 1.02658C8.22455 1.68389 7.90228 2.57539 7.90228 3.50496H8.7852C8.7852 2.84371 9.01445 2.20953 9.42252 1.74195C9.83058 1.27437 10.384 1.01169 10.9611 1.01169C11.5382 1.01169 12.0917 1.27437 12.4997 1.74195C12.9078 2.20953 13.1371 2.84371 13.1371 3.50497H14.02Z" fill="#201cfe"/></svg>
										<span class="tool-name">UGCify</span>
									</div>

                                    <div class="banner-text">
										<?php echo __('Build trust and increase conversions with UGC for WooCommerce!', 'wpfunnels'); ?>
                                    </div>

                                    <a href="<?php echo esc_url($this->btn_link); ?>" class="cta-button" role="button" aria-label="get special discount " target="_blank">
                                        <?php
                                            echo __('Request Early Access', 'wpfunnels');
                                        ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="9" viewBox="0 0 8 9" fill="none"><path d="M8 0.703124V8.29686C8 8.48334 7.93415 8.66218 7.81694 8.79405C7.69973 8.92591 7.54076 8.99999 7.375 8.99999C7.20924 8.99999 7.05027 8.92591 6.93306 8.79405C6.81585 8.66218 6.75 8.48334 6.75 8.29686V2.40061L1.06695 8.79406C0.949738 8.92592 0.790765 9 0.625004 9C0.459243 9 0.30027 8.92592 0.183059 8.79406C0.0658485 8.6622 0 8.48335 0 8.29687C0 8.11039 0.0658485 7.93155 0.183059 7.79968L5.86613 1.40625H0.625012C0.459252 1.40625 0.300281 1.33217 0.183071 1.20031C0.0658608 1.06845 1.28672e-05 0.889604 1.28672e-05 0.703124C1.28672e-05 0.516644 0.0658608 0.337802 0.183071 0.20594C0.300281 0.0740789 0.459252 0 0.625012 0L7.375 0C7.54076 0 7.69973 0.0740789 7.81694 0.20594C7.93415 0.337802 8 0.516644 8 0.703124Z" fill="#201cfe"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <button class="close-promotional-banner" type="button" aria-label="close banner">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.97L1 11.96m0-9.99l10 9.99"/></svg>
                </button>
            </div>
        </div>
        <!-- .gwpf-tb-notification end -->

        <script>
            function updateCountdown() {
                var endDate = new Date("2026-01-07 23:59:59").getTime();
                var now = new Date().getTime();
                var timeLeft = endDate - now;

                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                var daysElement = document.getElementById('wpf-halloween-days');
                var hoursElement = document.getElementById('wpf-halloween-hours');
                var minsElement = document.getElementById('wpf-halloween-mins');
                var secsElement = document.getElementById('wpf-halloween-secs');

                if (daysElement) {
                    daysElement.innerHTML = days;
                }

                if (hoursElement) {
                    hoursElement.innerHTML = hours;
                }

                if (minsElement) {
                    minsElement.innerHTML = minutes;
                }
                if (secsElement) {
                    secsElement.innerHTML = seconds;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                updateCountdown();
                setInterval(updateCountdown, 1000); // Update every minute
            });
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
                padding: 6px 0;
				border-radius: 10px;
				background: #FFF;
				box-shadow: 0 1px 1px 0 rgba(32, 28, 254, 0.10);
            }
            .gwpf-promotional-banner .promotional-banner {
                color: white;
                padding: 3px 20px 0;
                /* padding: 12px 20px; */
                text-align: center;
                font-size: 14px;
                line-height: 1.4;
                position: relative;
            }
            .gwpf-promotional-banner .banner-content {
                max-width: 1200px;
                margin: 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-wrap: wrap;
                gap: 30px;
                row-gap: 8px;
                position: relative;
            }
			.gwpf-promotional-banner .banner-logo-area {
				display: flex;
				align-items: center;
				gap: 5px;
			}
			.gwpf-promotional-banner .new-tool-text {
				color: #666;
				font-size: 12px;
				font-weight: 500;
				line-height: 1;
				display: block;
			}
			.gwpf-promotional-banner .tool-name {
				color: #090939;
				font-size: 14px;
				font-weight: 600;
				line-height: 1;
				display: block;
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
            .gwpf-promotional-banner .halloween-bird-left {
                position: absolute;
                left: -47px;
                bottom: -7px;
            }
            .gwpf-promotional-banner .halloween-bird-right {
                position: absolute;
                right: -50px;
                top: -5px;
            }
            .gwpf-promotional-banner .cta-button {
                color: #201CFE;
				font-size: 15px;
				font-style: normal;
				font-weight: 500;
				line-height: 1;
				text-decoration: underline;
				text-decoration-thickness: 1px;
                text-underline-offset: 5px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                transition: all 0.2s ease;
                background: #fff;
                padding: 9px 14px;
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
            }
            .gwpf-promotional-banner .cta-button:hover svg {
                transform: translateX(3px);
            }


            .gwpf-tb__notification .close-promotional-banner {
                position: absolute;
				top: 50%;
				right: 10px;
				transform: translateY(-50%);
                background: #fff;
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
					gap: 20px;
					row-gap: 8px;
				}

				.gwpf-promotional-banner .cta-button {
					padding-top: 0;
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

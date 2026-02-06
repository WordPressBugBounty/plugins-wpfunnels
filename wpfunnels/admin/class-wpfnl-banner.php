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

        if ('yes' === get_option('_is_show_newyear2026_banner', 'yes')) {
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
        $promotional_notice_pages   = ['dashboard', 'plugins', 'wpfunnels_page_wp_funnels', 'wpfunnels_page_edit_funnel', 'wp-funnels_page_wpfnl_settings'];
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
                    <section class="gwpf-promotional-banner" aria-labelledby="wpf-halloween-offer">
                        <div class="gwpf-container">
                            <div class="promotional-banner">
                                <div class="banner-content">
                                    <div class="banner-text">
                                        <svg style="margin-right: 5px;" width="117" height="45" fill="none" viewBox="0 0 117 45" xmlns="http://www.w3.org/2000/svg"><path fill="#EC813F" d="M15.015 7.931v7.862h-4.579V0h4.58v6.346h3.732V0h4.576v15.777h-4.576V7.915l-3.733.016zM33.208 0l4.403 15.777H33l-.944-3.418h-4.745l-1.007 3.418H24.53L29.199 0h4.01zm-5.443 10.774h3.87l-1.86-6.994-2.01 6.994zm11.072 5.003V0h6.808c3.858 0 5.788 1.545 5.79 4.635 0 3.124-2.146 4.685-6.438 4.683h-1.572v6.46h-4.588zm4.575-7.915h1.029c1.677 0 2.51-1.102 2.497-3.305 0-2.065-.833-3.098-2.497-3.098h-1.029v6.403zm9.561 7.915V0h6.808c3.858 0 5.787 1.545 5.79 4.635 0 3.124-2.146 4.685-6.438 4.683h-1.572v6.46h-4.588zm4.575-7.915h1.029c1.677 0 2.51-1.102 2.497-3.305 0-2.065-.833-3.098-2.497-3.098h-1.029v6.403zM78.134 0l-3.846 9.953v5.824h-4.579V9.953L65.454 0h4.752l3.214 7.607L76.31 0h1.824z"/><path fill="#fff" d="M24.453 33.353c-2.076-5.308-5.488-14.764-7.862-20.013C14.075 25.051 7.157 36.941.223 34.687c-.268-.176-.315-.444-.029-.384 8.953 1.66 14.5-15.724 15.479-21.884.028-.415 0-1.067.028-1.422-.028-.031 0-.06-.028-.088.028-.415 1.097.057 1.274.591 1.185 3.381 5.188 12.928 7.62 19.422.314-3.144 1.037-7.355 1.392-9.786.091-.799.24-1.6.387-2.371.77-4.063 2.46-10.793 5.752-10.29.355.032.267.359.088.387-2.311.387-3.821 4.478-4.827 10.23-.092.742-.24 1.421-.315 2.163-.446 2.934-1.258 7.862-1.544 11.595-.028.264-.84 1.038-1.047.503zm6.554-7.443a2.778 2.778 0 01-.148-.859c0-1.305.89-2.638 2.343-2.698.918 0 1.776.89 1.453 1.748-.387.944-1.66 1.66-2.699 1.66.088.564 1.126 1.513 2.164 1.513 2.786 0 4.862-2.075 6.107-3.38.683-.71.563-.028.148.802-.83 1.72-2.727 3.173-4.774 3.29-1.748.09-4.003-.237-4.594-2.076zm.918-.859c1.258-.563 2.076-1.365 1.748-1.689-.622-.622-1.776.947-1.748 1.69z"/><path fill="#fff" d="M46.752 22.856c.563-.314 1.007.535.944 1.01a3.908 3.908 0 001.72.355 7.623 7.623 0 002.85-.63c.826-.266.204.535-.268.803a5.66 5.66 0 01-2.73.739 5.223 5.223 0 01-1.512-.236c0 .918-.472 2.43-1.66 2.49-.711.06-1.778-.534-2.372-1.424-.314.89-.918 1.84-1.868 1.84a1.967 1.967 0 01-.918-.268 2.236 2.236 0 01-.83-1.157 3.27 3.27 0 01-.18-1.305 6.183 6.183 0 011.07-2.962c.056-.18.355-.12.59.056.237.176.356.475.208.63a6.032 6.032 0 00-.943 2.905 2.54 2.54 0 00.12.83.63.63 0 00.801.387c.74-.267 1.422-2.255 1.513-2.67.028-.267 0-.314.06-.383.088-.447 1.006 0 .943.474.06.06.032.117 0 .265.088.566.148 1.396.77 1.78 1.337.89 1.72-1.306 1.692-2.202-.386-.645-.298-1.12 0-1.327zm20.432 2.403c-.862 1.157-2.227 2.343-3.205 2.343-.978 0-1.927-.743-2.311-1.692a4.343 4.343 0 01-.267-1.422 3.405 3.405 0 01.207-1.217 1.04 1.04 0 01.595-.71.864.864 0 01.738.295c.24.239.24.506.092.563-.148.057-.268.207-.415.506a8.59 8.59 0 00-.18 1.098c.06.471.12 1.333.63 1.629 1.257.71 3.855-2.758 3.915-3.705 0-.06.056-.091.088-.12-.06-.03-.032-.03 0-.059a.477.477 0 01.267-.088.994.994 0 01.83.682c.137.993.206 1.993.208 2.994v.116l.028-.028c.802-.475 4.566-2.311 5.249-1.688.267.235.235.295 0 .355-1.186.355-3.145 1.421-4.387 2.163l-.862.595-.028.355-.092 2.758c-.235 3.736-1.037 7.796-3.616 8.33-.742.148-1.928-.314-2.758-1.157-.83-.843-1.274-1.887-1.126-2.965.444-2.283 2.83-5.246 5.812-7.469l.742-.535-.154-1.927zm.116 3.32l-.682.63c-2.4 2.2-4.403 4.594-4.742 6.581a2.478 2.478 0 00.628 2.076c.444.415 1.007.65 1.365.563 2.246-.45 3.463-5.698 3.428-9.85h.003z"/><path fill="#fff" d="M72.284 25.91a2.694 2.694 0 01-.15-.859c0-1.305.89-2.638 2.342-2.698.922 0 1.78.89 1.453 1.748-.383.944-1.66 1.66-2.698 1.66.091.564 1.129 1.513 2.167 1.513 2.786 0 4.862-2.075 6.107-3.38.682-.71.563-.028.148.802-.83 1.72-2.727 3.173-4.774 3.29-1.749.09-4.003-.237-4.595-2.076zm.919-.859c1.258-.563 2.075-1.365 1.748-1.689-.622-.622-1.78.947-1.748 1.69z"/><path fill="#fff" d="M80.505 25.466a2.177 2.177 0 011.066-1.808c.85-.54 1.84-.82 2.846-.802a6.006 6.006 0 012.403.506h.028l.503-.682c.18-.314 1.189.314.981.682a7.267 7.267 0 00-.475 1.541c-.031.504.117 1.098.651 1.258 1.513.296 4.063-1.217 5.13-2.402.506-.504.943.235-.683 1.868-.74.742-2.786 1.688-4.032 1.393a2.78 2.78 0 01-2.103-1.868c-.83.742-2.79 2.255-4.36 2.255a1.965 1.965 0 01-1.068-.296 2.011 2.011 0 01-.887-1.645zm5.808-1.512a4.238 4.238 0 00-1.154-.148c-1.484 0-3.44.563-3.774 1.927a.542.542 0 00.535.711c1.415-.17 3.35-1.393 4.387-2.49h.006z"/><path fill="#fff" d="M93.483 22.589c0-.208.315-.208.595-.06a.869.869 0 01.443.629l-.028 1.157c.742-.943 2.934-2.667 4.566-2.4.682.117 1.097.74.89.979-.415.475-.83.295-.802.06l.031-.24c-1.484-.028-3.943 1.78-4.657 3.825a7.063 7.063 0 00-.207 1.276c.031.384-.944 0-1.067-.446-.12-.27-.163-1.044.236-4.78z"/><path fill="#fff" stroke="#fff" stroke-width=".2" d="M101.461 23.63a.417.417 0 01.016-.305.384.384 0 01.226-.202l7.3-2.415a.38.38 0 01.3.028.415.415 0 01.179.54.38.38 0 01-.225.201l-7.3 2.415a.382.382 0 01-.301-.027.412.412 0 01-.195-.235zm-.367 3.953a.365.365 0 01-.004-.222l.028-.07h.001a.399.399 0 01.52-.172h.001l6.832 3.265a.37.37 0 01.169.51.397.397 0 01-.521.174l-6.832-3.266h-.001a.373.373 0 01-.193-.219zM97.95 30.23a.361.361 0 01.036-.298.444.444 0 01.243-.191.443.443 0 01.308.009c.098.04.174.118.207.217l2.32 7.013a.36.36 0 01-.036.298.441.441 0 01-.242.191.445.445 0 01-.31-.008.362.362 0 01-.206-.218l-2.32-7.013zm-7.586 6.428a.374.374 0 01.024-.29h.001l3.537-6.698a.394.394 0 01.226-.19.403.403 0 01.295.018.38.38 0 01.195.22.37.37 0 01-.025.292l-3.538 6.696h0a.398.398 0 01-.52.172h-.002a.374.374 0 01-.193-.22zm11.456-10.93a.376.376 0 01-.018-.145h.001a.382.382 0 01.407-.348h.001l14.099.875a.378.378 0 01.332.253.436.436 0 01.016.072l.002.073a.39.39 0 01-.134.256.389.389 0 01-.275.092l-14.098-.875a.38.38 0 01-.333-.253zm-1.854 3.507a.36.36 0 01.025-.286.39.39 0 01.222-.187.394.394 0 01.289.017.371.371 0 01.118.088l9.153 10.235v.001a.367.367 0 01.064.378.395.395 0 01-.423.236.378.378 0 01-.224-.12l-9.151-10.236-.043-.059a.356.356 0 01-.03-.067zM94.94 44.318a.371.371 0 01-.095-.258v-.005l.97-13.596a.368.368 0 01.565-.286.373.373 0 01.17.34h0l-.97 13.597v.005a.37.37 0 01-.394.324.369.369 0 01-.246-.121z"/></svg>
                                        Get Ready To Sell More New Year With <span class="highlighted-text">30% OFF!</span>
                                    </div>

                                    <a href="<?php echo esc_url($this->btn_link); ?>" class="cta-button" role="button" aria-label="get special discount " target="_blank">
                                        <?php
                                            echo __('View Offer', 'getwpfunnels');
                                        ?>
                                        <svg width="11" height="11" fill="none" viewBox="0 0 11 11" xmlns="http://www.w3.org/2000/svg"><path fill="#3C1F7D" stroke="#3C1F7D" stroke-width=".2" d="M9.419.1a.88.88 0 01.88.881V9.42a.88.88 0 11-1.761 0V3.11l-6.934 6.933A.88.88 0 01.358 8.796l6.934-6.934H.982A.88.88 0 11.981.1h8.437z"/></svg>
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
                background-color: #2d1568;
                background: linear-gradient(90deg, #AC77FD -6.28%, #3C1F7D 21.34%, #3C1F7D 87.74%, #AC77FD 130.48%);
                z-index: 1111;
                padding: 6px 0;
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
                gap: 20px;
                row-gap: 8px;
                position: relative;
            }
            .gwpf-promotional-banner .banner-text {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
                row-gap: 0;
                justify-content: center;
                font-size: 16px;
                color: #fff;
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
                color: #3C1F7D;
                font-size: 16px;
                font-style: normal;
                font-weight: 700;
                line-height: normal;
                text-decoration: none;
                text-decoration-thickness: 2px;
                text-underline-offset: 5px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                transition: all 0.2s ease;
                background: #fff;
                padding: 9px 14px;
                border-radius: 50px;
            }
            .gwpf-promotional-banner .cta-button svg {
                transition: transform 0.3s ease;
            }

            .gwpf-promotional-banner .cta-button:focus, 
            .gwpf-promotional-banner .cta-button:visited {
                color: #3C1F7D !important;
            }
            .gwpf-promotional-banner .cta-button:hover {
                color: #3C1F7D !important;
            }
            .gwpf-promotional-banner .cta-button:hover svg path {
                stroke: #3C1F7D;
                fill: #3C1F7D;
            }

            .gwpf-promotional-banner .cta-button:hover svg {
                transform: translateX(3px);
            }


            .gwpf-tb__notification .close-promotional-banner {
                position: absolute;
                top: -10px;
                right: -9px;
                background: #fff;
                border: none;
                padding: 0;
                border-radius: 50%;
                cursor: pointer;
                z-index: 9;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .gwpf-tb__notification .close-promotional-banner svg {
                width: 22px;
                display: block;
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

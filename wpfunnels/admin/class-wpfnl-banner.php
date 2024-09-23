<?php
namespace WPFunnels\Admin\Banner;

/**
 * SpecialOccasionBanner Class
 *
 * This class is responsible for displaying a special occasion banner in the WordPress admin.
 *
 * @package WPFunnels\Admin\Banner
 */
class SpecialOccasionBanner {

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
    public function __construct($occasion, $start_date, $end_date, $btn_link = '#' ) {
        $this->occasion     = $occasion;
        $this->btn_link     = $btn_link;
        $this->start_date   = strtotime($start_date);
        $this->end_date     = strtotime($end_date);

        if ( !defined('WPFNL_PRO_VERSION') && 'yes' === get_option( '_is_wpfnl_eid_promotion', 'yes' )) {
            // Hook into the admin_notices action to display the banner
            add_action('admin_notices', [$this, 'display_banner']);
            add_action('admin_head', array($this, 'add_styles'));
        }
    }

    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_banner() {
        $screen                     = get_current_screen();
        $promotional_notice_pages   = ['dashboard', 'plugins', 'toplevel_page_wp_funnels', 'wp-funnels_page_wpfnl_settings'];
        $current_date_time          = current_time('timestamp');

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ( $current_date_time < $this->start_date || $current_date_time > $this->end_date ) {
            return;
        }
        // Calculate the time remaining in seconds
        $time_remaining = $this->end_date - $current_date_time;

        ?>


            <!-- Name: WordPress Anniversary Notification Banner -->
            <div class="<?php echo esc_attr($this->occasion); ?>-banner notice">
            <div class="gwpf-tb__notification" id="rex_deal_notification">

                <div class="banner-overflow">
                    <div class="gwpf-anniv__container-area">

                        <div class="gwpf-anniv__image gwpf-anniv__image--left">
                        <figure>
                                    <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/banner-image/eid-ul-adha-moon.webp' ); ?>" alt="Eid Ul Adha Moon" />
                                    </figure>
                        </div>

                        <div class="gwpf-anniv__content-area">


                            <div class="gwpf-anniv__image--group">

                                <div class='gwpf-anniv__image gwpf-anniv__image--eid-mubarak'>
                                <figure>
                                    <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/banner-image/eid-utl-adha-text.webp' ); ?>" alt="Eid Ul Adha Moon" />
                                    </figure>
                                </div>

                                <div class='gwpf-anniv__image gwpf-anniv__image--wpfunnel-logo'>
                                <figure>
                                    <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/banner-image/eid-ul-adha-funnel.webp' ); ?>" alt="WP Funnels Logo" />
                                    </figure>
                                </div>

                                <div class="gwpf-anniv__image gwpf-anniv__image--four">
                                <figure>
                                    <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/banner-image/eid-ul-adha-tweenty.webp' ); ?>" alt="Eid Ul Adha Discount" />
                                    </figure>
                                </div>



                                <div class="gwpf-anniv__text-divider">

                                    <div class="gwpf-anniv__lead-text">
                                        <span>
                                            <svg width="33" height="30" fill="none" viewBox="0 0 33 30" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="#EE8133" stroke="#EE8133" d="M28.584 25.483a257.608 257.608 0 00-.525-1.495c-.28-.795-.569-1.614-.769-2.199a1.432 1.432 0 01-.084-.552c.014-.211.106-.57.487-.726a.828.828 0 01.416-.064.754.754 0 01.38.161c.139.11.248.274.309.366l.02.032.003.004c.127.191.203.355.265.49l.04.09.572 1.176a185.411 185.411 0 011.49 3.11c.193.412.306.86.404 1.245l.027.106h0c.077.301.093.67-.128.977-.224.313-.587.415-.925.429h0a54.91 54.91 0 01-3.43.022h-.001l-.166-.003c-1.395-.027-2.84-.055-4.268-.29h-.003c-.312-.053-.574-.138-.78-.299a1.212 1.212 0 01-.371-.523l-.01-.024-.008-.024a.692.692 0 01.175-.694c.137-.136.31-.205.428-.243.248-.08.538-.105.687-.117a5.511 5.511 0 011.039 0c.766.051 1.528.104 2.297.157l.16.01c-5.037-2.4-9.838-5.23-14.007-9.083C7.962 13.508 4.206 9.005 1.53 3.652h0l-.002-.004-.02-.04c-.183-.377-.397-.817-.517-1.283A2.45 2.45 0 00.985 2.3c-.025-.088-.08-.28-.068-.479.016-.273.144-.526.401-.728l.027-.02.029-.018a.729.729 0 01.792.026c.18.117.325.3.442.47.17.24.35.506.507.787l.001.002c2.4 4.35 5.404 8.244 8.893 11.79l-.343.338.343-.338c4.39 4.463 9.63 7.735 15.16 10.655.463.242.93.466 1.415.697z" />
                                            </svg>
                                        </span>

                                        <h2 class="gwpf-wp-anniversary__title-end">
                                            <?php echo __("Ends <br> Soon", 'wpfnl') ?>
                                        </h2>

                                    </div>

                                </div>

                            </div>

                            <!-- .gwpf-anniv__image end -->
                            <div class="gwpf-anniv__btn-area">

                                <a href="<?php echo esc_url($this->btn_link); ?>"  role="button" class="gwpf-anniv__btn" target="_self">
                                    <?php echo __('Get It Now', 'wpfnl') ?>
                                </a>
                                <svg width="70" height="63" fill="none" viewBox="0 0 70 63" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#FF44BC" d="M4.607 7.083c1.027-1.909 1.655-3.536 1.59-5.337a5.106 5.106 0 00-3.25-.907A9.527 9.527 0 011.34 6.08c1.486-.104 2.372.062 3.267 1.002z" />
                                    <path fill="url(#paint0_linear_2007_3)" d="M67.79 55.948c-1.79-.111-3.545-.96-6.504-4.16a9.216 9.216 0 00-1.916 5.447 16.383 16.383 0 007.3 3.89 8.389 8.389 0 011.12-5.177z" />
                                    <path fill="#EE8134" d="M60.724 14.364a18.229 18.229 0 01-6.33 3.313 5.826 5.826 0 001.984 3.154 24.284 24.284 0 006.717-2.97c-1.715-1.08-2.408-1.907-2.37-3.497z" />
                                    <defs>
                                        <linearGradient id="paint0_linear_2007_3" x1="27276.4" x2="27248.7" y1="7756.52" y2="7812.21" gradientUnits="userSpaceOnUse">
                                            <stop stop-color="#4D8EFF" />
                                            <stop offset=".43" stop-color="#3F76FF" />
                                            <stop offset="1" stop-color="#2850FF" />
                                        </linearGradient>
                                    </defs>
                                </svg>

                            </div>

                        </div>

                        <div class="gwpf-anniv__image gwpf-anniv__image--right">
                        <figure>
    <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/banner-image/eid-ul-adha-right.webp' ); ?>" alt="Eid-ul-adha-mosque" />
</figure>
                        </div>

                    </div>

                </div>

                <button class="close-promotional-banner" type="button" aria-label="close banner">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.97L1 11.96m0-9.99l10 9.99"/></svg>
                </button>


            </div>
    </div>
            <!-- .gwpf-tb-notification end -->

            
  

        <script>
            // var timeRemaining = <?php 
            // echo esc_js($time_remaining); 
            ?>;

            // Update the countdown every second
            // setInterval(function() {
            //     // var countdownElement    = document.getElementById('wpfnl_countdown');
            //     // var daysElement         = document.getElementById('wpfnl_days');
            //     // var hoursElement        = document.getElementById('wpfnl_hours');
            //     // var minutesElement      = document.getElementById('wpfnl_minutes');

            //     // Decrease the remaining time
            //     timeRemaining--;

            //     // Calculate new days, hours, and minutes
            //     var days = Math.floor(timeRemaining / (60 * 60 * 24));
            //     var hours = Math.floor((timeRemaining % (60 * 60 * 24)) / (60 * 60));
            //     var minutes = Math.floor((timeRemaining % (60 * 60)) / 60);


            //     // Format values with leading zeros
            //     days = (days < 10) ? '0' + days : days;
            //     hours = (hours < 10) ? '0' + hours : hours;
            //     minutes = (minutes < 10) ? '0' + minutes : minutes;

            //     // Update the HTML
            //     // daysElement.textContent = days;
            //     // hoursElement.textContent = hours;
            //     // minutesElement.textContent = minutes;

            //     // Check if the countdown has ended
            //     if (timeRemaining <= 0) {
            //         countdownElement.innerHTML = 'Campaign Ended';
            //     }
            // }, 1000); // Update every second
        </script>
        <?php
    }

    /**
     * Adds internal CSS styles for the special occasion banners.
     */
    public function add_styles() {
        ?>
        <style type="text/css">
            @font-face {
                font-family: "Circular Std Book";
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/CircularStd-Book.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/CircularStd-Book.woff'; ?>) format("woff");
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Lexend Deca';
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-Bold.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-Bold.woff'; ?>) format("woff");
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Lexend Deca';
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-ExtraBold.woff.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-ExtraBold.woff.woff'; ?>) format("woff");
                font-weight: 800;
                font-style: normal;
                font-display: swap;
            }


            .gwpf-tb__notification,
            .gwpf-tb__notification * {
                box-sizing: border-box;
            }

            .gwpf-tb__notification {
                background-color: #d6e4ff;
                width: calc(100% - 20px);
                margin: 20px 0 20px;
                background-color: #6e42d3;
                background-image: url(<?php echo WPFNL_URL.'admin/assets/images/banner-image/notification-br-bg.webp'; ?>);
                background-repeat: no-repeat;
                background-size: cover;
                position: relative;
                border: none;
                box-shadow: none;
                display: block;
                max-height: 110px;
            }

            .gwpf-tb__notification .banner-overflow {
                overflow: hidden;
                position: relative;
                width: 100%;
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
            }

            .gwpf-tb__notification .close-promotional-banner svg {
                display: block;
                width: 15px;
                height: 15px;
            }

            .gwpf-anniv__container {
                width: 100%;
                margin: 0 auto;
                max-width: 1640px;
                position: relative;
                padding-right: 15px;
                padding-left: 15px;
            }

            .gwpf-anniv__container-area {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .gwpf-anniv__content-area {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-evenly;
                max-width: 1310px;
                position: relative;
                padding-right: 15px;
                padding-left: 15px;
                margin: 0 auto;
                z-index: 1;
            }

            .gwpf-anniv__image--left {
                position: absolute;
                left: 140px;
                top: 50%;
                transform: translateY(-50%);
            }

            .gwpf-anniv__image--right {
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
            }

            .gwpf-anniv__image--group {
                display: flex;
                align-items: center;
                gap: 50px;
            }

            .gwpf-anniv__image--left img {
                width: 100%;
                max-width: 108px;
            }

            .gwpf-anniv__image--eid-mubarak img {
                width: 100%;
                max-width: 165px;
            }

            .gwpf-anniv__image--wpfunnel-logo img {
                width: 100%;
                max-width: 140px;
            }

            .gwpf-anniv__image--four img {
                width: 100%;
                max-width: 254px;
            }

            .gwpf-anniv__lead-text {
                display: flex;
                gap: 11px;
            }

            .gwpf-anniv__lead-text h2 {
                font-size: 42px;
                line-height: 1;
                margin: 0;
                color: #EC813F;
                font-weight: 700;
                font-family: 'Lexend Deca';

            }



            .gwpf-anniv__image--right img {
                width: 100%;
                max-width: 152px;
            }

            .gwpf-anniv__image figure {
                margin: 0;
            }

            .gwpf-anniv__text-container {
                position: relative;
                max-width: 330px;
            }

            .gwpf-anniv__campaign-text-images {
                position: absolute;
                top: -10px;
                right: -15px;
                max-width: 100%;
                max-height: 24px;
            }



            .gwpf-anniv__btn-area {
                display: flex;
                align-items: flex-end;
                justify-content: flex-end;
                position: relative;
            }

            .gwpf-anniv__btn-area svg {
                position: absolute;
                width: 70px;
                right: -20px;
                top: -15px;
            }

            .gwpf-anniv__btn {
                font-family: "Circular Std Book";
                font-size: 20px;
                font-weight: 700;
                line-height: 1;
                text-align: center;
                border-radius: 13px;
                background: linear-gradient(0deg, #FFC8A6 0%, #FFF 100%);
                box-shadow: 0px 11px 30px 0px rgba(19, 13, 57, 0.25);
                color: #6E42D3;
                padding: 17px 26px;
                display: inline-block;
                cursor: pointer;
                text-transform: capitalize;
                transition: all 0.5s linear;
                text-decoration: none;
            }

            a.gwpf-anniv__btn:hover {
                box-shadow: none;
            }

            .gwpf-anniv__btn-area a:focus {
                color: #fff;
                box-shadow: none;
                outline: 0px solid transparent;
            }

            .gwpf-anniv__btn:hover {
                background-color: #201cfe;
                color: #6E42D3;
            }

            .wpcartlift-banner-title p {
                margin: 0;
                font-weight: 700;
                max-width: 315px;
                font-size: 24px;
                color: #ffffff;
                line-height: 1.3;
            }

            @media only screen and (min-width: 1921px) {
                .gwpf-anniv__image--left img {
                    max-width: 108px;
                }
            }


            @media only screen and (max-width: 1710px) {

                .gwpf-anniv__image--left {
                    left: 100px;
                }

                .gwpf-anniv__lead-text h2 {
                    font-size: 36px;
                }

                .gwpf-anniv__content-area {
                    justify-content: center;
                }

                .gwpf-anniv__image--group {
                    gap: 30px;
                }

                .gwpf-anniv__content-area {
                    gap: 30px;
                }

                .gwpf-anniv__btn {
                    font-size: 18px;
                }

                .gwpf-anniv__btn-area svg {
                    position: absolute;
                    width: 70px;
                    right: -20px;
                    top: -15px;
                }

            }


            @media only screen and (max-width: 1440px) {

                .gwpf-tb__notification {
                    max-height: 99px;
                }

                .gwpf-anniv__image--left {
                    left: 40px;
                }

                .gwpf-anniv__image--left img {
                    width: 90%;
                }

                .gwpf-anniv__image--eid-mubarak img {
                    width: 90%;
                }

                .gwpf-anniv__image--wpfunnel-logo img {
                    width: 90%;
                }

                .gwpf-anniv__image--four img {
                    width: 90%;
                }

                .gwpf-anniv__image--right img {
                    width: 90%;
                }

                .gwpf-anniv__lead-text h2 {
                    font-size: 28px;
                }

                .gwpf-anniv__image--group {
                    gap: 25px;
                }

                .gwpf-anniv__content-area {
                    gap: 30px;
                    justify-content: center;
                }

                .gwpf-anniv__btn {
                    font-size: 16px;
                    font-weight: 400;
                    border-radius: 30px;
                    padding: 12px 16px;
                }

                .gwpf-anniv__btn-area svg {
                    position: absolute;
                    width: 60px;
                    right: -15px;
                    top: -15px;
                }

            }


            @media only screen and (max-width: 1399px) {

                .gwpf-tb__notification {
                    max-height: 79px;
                }

                .gwpf-anniv__image--left {
                    left: 20px;
                }

                .gwpf-anniv__image--left img {
                    max-width: 86.39px;
                }

                .gwpf-anniv__image--eid-mubarak img {
                    max-width: 132px;
                }

                .gwpf-anniv__image--wpfunnel-logo img {
                    max-width: 108px;
                }

                .gwpf-anniv__image--four img {
                    max-width: 203px;
                }

                .gwpf-anniv__image--right img {
                    max-width: 121.5px;
                }

                .gwpf-anniv__lead-text h2 {
                    font-size: 24px;
                }

                .gwpf-anniv__image--group {
                    gap: 20px;
                }

                .gwpf-anniv__content-area {
                    gap: 35px;
                }

                .gwpf-anniv__btn {
                    font-size: 14px;
                    font-weight: 600;
                    border-radius: 30px;
                    padding: 12px 16px;
                }

                .gwpf-anniv__btn-area svg {
                    width: 45px;
                    right: -13px;
                    top: -21px;
                }

            }

            @media only screen and (max-width: 1024px) {
                .gwpf-tb__notification {
                    max-height: 75px;
                }

                .gwpf-anniv__image--left img {
                    max-width: 76.39px;
                }

                .gwpf-anniv__image--eid-mubarak img {
                    max-width: 122px;
                }

                .gwpf-anniv__image--wpfunnel-logo img {
                    max-width: 100px;
                }

                .gwpf-anniv__image--four img {
                    max-width: 193px;
                }

                .gwpf-anniv__image--right img {
                    max-width: 111.5px;
                }

                .gwpf-anniv__lead-text h2 {
                    font-size: 22px;
                }

                .gwpf-anniv__lead-text svg {
                    width: 25px;
                    margin-top: -10px;
                }


                .gwpf-anniv__content-area {
                    gap: 30px;
                }

                .gwpf-anniv__image--group {
                    gap: 15px;
                }

                .gwpf-anniv__btn {
                    font-size: 12px;
                    line-height: 1.2;
                    padding: 11px 12px;
                    font-weight: 400;
                }

                .gwpf-anniv__btn {
                    box-shadow: none;
                }

                .gwpf-anniv__image--right,
                .gwpf-anniv__image--left {
                    display: none;
                }

                .gwpf-anniv__btn-area svg {
                    width: 40px;
                    right: -15px;
                    top: -23px;
                }


            }

            @media only screen and (max-width: 768px) {

                .gwpf-tb__notification {
                    margin: 60px 0 20px;
                }

                .gwpf-anniv__container-area {
                    padding: 0 15px;
                }

                .gwpf-anniv__container-area {
                    justify-content: center;
                    gap: 20px;
                }

                .gwpf-tb__notification {
                    max-height: 64px;
                }

                .gwpf-anniv__image--left img {
                    max-width: 76.39px;
                }

                .gwpf-anniv__image--eid-mubarak img {
                    max-width: 92px;
                }

                .gwpf-anniv__image--wpfunnel-logo img {
                    max-width: 90px;
                }

                .gwpf-anniv__image--four img {
                    max-width: 163px;
                }

                .gwpf-anniv__image--right img {
                    max-width: 111.5px;
                }

                .gwpf-anniv__lead-text h2 {
                    font-size: 22px;
                }

                .gwpf-anniv__content-area {
                    gap: 30px;
                }

                .gwpf-anniv__image--group {
                    gap: 15px;
                }

                .gwpf-tb__notification .close-promotional-banner {
                    width: 25px;
                    height: 25px;
                }

                .gwpf-anniv__image--group {
                    gap: 20px;
                }

                .gwpf-anniv__image--left,
                .gwpf-anniv__image--right {
                    display: none;
                }

                .gwpf-anniv__btn {
                    font-size: 12px;
                    line-height: 1;
                    font-weight: 400;
                    padding: 10px 12px;
                    margin-left: 0;
                    box-shadow: none;
                }

                .gwpf-anniv__content-area {
                    display: contents;
                    gap: 25px;
                    text-align: center;
                    align-items: center;
                }

                .gwpf-anniv__lead-text svg {
                    width: 22px;
                    margin-top: -8px;
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

                .gwpf-anniv__image--right,
                .gwpf-anniv__image--left {
                    display: none;
                }

                .gwpf-anniv__stroke-font {
                    font-size: 16px;
                }

                .gwpf-anniv__content-area {
                    display: contents;
                    gap: 25px;
                    text-align: center;
                    align-items: center;
                }

                .gwpf-anniv__btn-area {
                    justify-content: center;
                    padding-top: 5px;
                }

                .gwpf-anniv__btn {
                    font-size: 12px;
                    padding: 15px 24px;
                }

                .gwpf-anniv__image--group {
                    gap: 10px;
                    padding: 0;
                }
            }



          
        </style>
        <?php
    }


    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_new_ui_notice(){
        $screen                     = get_current_screen();
        $promotional_notice_pages   = ['dashboard', 'plugins', 'toplevel_page_wp_funnels', 'wp-funnels_page_wpfnl_settings'];

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }
        ?>
        <div class="wpfunnels-newui-notice notice">
            <a href="https://youtu.be/OrDQg-XcOLY" target="_blank">
                <div class="newui-notice-wrapper">
                    <figure class="newui-template-img">
                        <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/newui-template-img-2x.webp' ); ?>" alt="newui-template-img" />
                    </figure>

                    <h4 class="newui-notice-title">
                        <span class="highlighted">WPFunnels 3.0 Is Here!</span>

                        <figure class="newui-version">
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/wpfunnel-version.svg' ); ?>" alt="wpfunnel-version" />
                        </figure>
                    </h4>
                    <p class="newui-notice-description">Now experience a better funnel-building experience with a better and more intuitive canvas for designing your funnel journey easily.</p>
                </div>
            </a>

            <button class="close-newui-notice" type="button" aria-label="close banner">
                <svg width="20" height="20" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="9.5" fill="#fff" stroke="#FE9A1B"/><path stroke="#FE9A1B" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.5 7.917l-5 5m0-5l5 5"/></svg>
            </button>
        </div>
        <?php
    }


    /**
     * Adds internal CSS styles for new ui notice.
     */
    public function add_new_ui_notice_styles() {
        ?>
        <style type="text/css">
            .wpfunnels-newui-notice * {
                box-sizing: border-box;
            }

            .wpfunnels-newui-notice {
                position: relative;
                border-radius: 5px;
                padding: 0;
                border: none;
                border-left: 3px solid #6E42D3;
                background: #ffffff;
                box-shadow: 0px 1px 2px 0px rgba(39, 25, 72, 0.10);
                box-sizing: border-box;
                background-image: url(<?php echo WPFNL_URL.'admin/assets/images/new-ui-notice-bg.svg'; ?>);
                background-repeat: no-repeat;
                background-size: cover;
                background-position: right center;
            }

            .wpfunnels-newui-notice.notice {
                display: block;
            }

            .wp-funnels_page_wpfnl_settings .wpfunnels-newui-notice,
            .toplevel_page_wp_funnels .wpfunnels-newui-notice {
                margin: 20px 0;
                width: calc(100% - 20px);
            }

            .wpfunnels-newui-notice a {
                text-decoration: none;
            }

            .wpfunnels-newui-notice .newui-notice-wrapper {
                padding: 24px 40px;
                position: relative;
                overflow: hidden;
                border-radius: 5px;
            }

            .wpfunnels-newui-notice .newui-template-img {
                position: absolute;
                right: 0;
                top: 0;
                display: block;
                margin: 0;
            }
            .wpfunnels-newui-notice figure.newui-template-img img {
                max-width: 482px;
                margin: 0;
                display: block;
            }

            .wpfunnels-newui-notice .newui-notice-title {
                margin: 0;
                color: #363B4E;
                font-size: 20px;
                font-weight: 500;
                font-family: "Roboto", sans-serif;
                position: relative;
                display: inline-block;
                z-index: 1;
            }

            .wpfunnels-newui-notice .newui-version {
                position: absolute;
                top: -25px;
                left: calc(100% + 30px);
                margin: 0;
                display: block;
            }

            .wpfunnels-newui-notice .newui-version img {
                display: block;
            }

            .wpfunnels-newui-notice .highlighted {
                color: #6E42D3;
                font-weight: 600;
            }
            
            .wpfunnels-newui-notice .newui-notice-description {
                color: #7A8B9A;
                font-size: 14px;
                font-weight: 400;
                font-family: "Roboto", sans-serif;
                line-height: 1.5;
                max-width: 632px;
                margin: 12px 0 0;
                position: relative;
                z-index: 1;
                padding: 0;
            }

            .wpfunnels-newui-notice .close-newui-notice {
                border: none;
                padding: 0;
                background: transparent;
                display: block;
                line-height: 1;
                cursor: pointer;
                box-shadow: none;
                outline: none;
                position: absolute;
                top: -6px;
                right: -6px;
            }


            @media only screen and (max-width: 1399px) {
                .wpfunnels-newui-notice .newui-template-img {
                    right: -100px;
                }

                .wpfunnels-newui-notice .newui-notice-description {
                    max-width: 592px;
                }

            }

            @media only screen and (max-width: 1199px) {
                .wpfunnels-newui-notice .newui-notice-wrapper {
                    padding: 24px 24px;
                }
                .wpfunnels-newui-notice .newui-notice-description {
                    max-width: 532px;
                }
                .wpfunnels-newui-notice .newui-template-img {
                    right: -226px;
                }
            }
        </style>
        <?php
    }


}
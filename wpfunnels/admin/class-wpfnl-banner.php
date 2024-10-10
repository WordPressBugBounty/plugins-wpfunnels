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

        if ( !defined('WPFNL_PRO_VERSION') && 'yes' === get_option( '_is_wpfnl_hallowen_promotion_24', 'yes' )) {
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
    public function wpf_get_halloween_countdown() {
        $halloween = strtotime('2024-10-21 23:59:59'); // Set this to the next Halloween
        $now = current_time('timestamp');
        $diff = $halloween - $now;

        return array(
            'days' => floor($diff / (60 * 60 * 24)),
            'hours' => floor(($diff % (60 * 60 * 24)) / (60 * 60)),
            'mins' => floor(($diff % (60 * 60)) / 60),
        );
    }

    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_banner() {
        $screen                     = get_current_screen();
        $promotional_notice_pages   = ['dashboard', 'plugins', 'wpfunnels_page_wp_funnels', 'wpfunnels_page_edit_funnel', 'wp-funnels_page_wpfnl_settings'];
        $current_date_time          = current_time('timestamp');
       
        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ( $current_date_time < $this->start_date || $current_date_time > $this->end_date ) {
            return;
        }
        // Calculate the time remaining in seconds
        $time_remaining = $this->end_date - $current_date_time;

        $countdown = $this->wpf_get_halloween_countdown();



        ?>


            <!-- Name: WordPress Anniversary Notification Banner -->
            <div class="<?php echo esc_attr($this->occasion); ?>-banner notice">
            <div class="gwpf-tb__notification" id="rex_deal_notification">

                <div class="banner-overflow">
                    <section class="wpf-notification-counter default-notification" aria-labelledby="wpf-halloween-offer-title">
                        <div class="wpf-notification-counter__container">
                            <div class="wpf-notification-counter__content">

                                <figure class="wpf-notification-counter__figure-logo">
                                    <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/halloween/halloween-default.webp '); ?>" alt="Halloween special offer banner" class="wpf-notification-counter__img">
                                </figure>

                                <figure class="wpf-notification-counter__figure-percentage">
                                    <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/halloween/percentage.webp'); ?>" alt="Halloween special offer banner" class="wpf-notification-counter__img">
                                </figure>

                                <div id="wpf-halloween-countdown" class="wpf-notification-counter__countdown" aria-live="polite">
                                    <h3 class="screen-reader-text"><?php echo __('Offer Countdown', 'wpfnl'); ?></h3>
                                    <ul class="wpf-notification-counter__list">

                                            <?php foreach (['days', 'hours', 'mins'] as $unit): ?>
                                            <li class="wpf-notification-counter__item ">
                                                <span id="wpf-halloween-<?php echo esc_attr($unit); ?>" class="wpf-notification-counter__time">
                                                    <?php echo esc_html($countdown[$unit]); ?>
                                                </span>
                                                <span class="wpf-notification-counter__label">
                                                    <?php echo esc_html($unit); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="wpf-notification-counter__btn-area">
                                    <a href="<?php echo esc_url('https://getwpfunnels.com/pricing/?utm_source=website&utm_medium=wpf-ui&utm_campaign=halloween24'); ?>" class="wpf-notification-counter__btn" role="button">

                                    <span class="wpf-btn-inner">
                                        <span class="screen-reader-text"><?php echo __('Click to view Halloween sale products', 'wpfnl'); ?></span>
                                        <span aria-hidden="true" class="wpf-notification-counter__mint-button"> <?php echo __('FLAT', 'wpfnl'); ?> <strong class="wpf-notification-counter__stroke-font"><?php echo __('30%', 'wpfnl'); ?></strong> <?php echo __('OFF', 'wpfnl'); ?></span>
                                    </span>
                                        
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
                var endDate = new Date("2024-10-21 23:59:59").getTime();
                var now = new Date().getTime();
                var timeLeft = endDate - now;

                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));

                var daysElement = document.getElementById('wpf-halloween-days');
                var hoursElement = document.getElementById('wpf-halloween-hours');
                var minsElement = document.getElementById('wpf-halloween-mins');

                if (daysElement) {
                    daysElement.innerHTML = days;
                }

                if (hoursElement) {
                    hoursElement.innerHTML = hours;
                }

                if (minsElement) {
                    minsElement.innerHTML = minutes;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                updateCountdown();
                setInterval(updateCountdown, 60000); // Update every minute
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

            .wp-anniversary-banner.notice {
                display: block !important;
                background: none;
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
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
                z-index: 1;
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

             /* Halloween */

             .wpf-notification-counter {
                position: relative;
                background-image: url(<?php echo esc_url(WPFNL_URL . 'admin/assets/images/halloween/promotional-banner.png'); ?>);
                background-position: center;
                background-repeat: no-repeat;
                background-size: 100% 100%;
                object-fit: cover;
                background-color: #03031E;
                z-index: 1111;
                padding: 9px 0 4px;
            }

            .wpf-notification-counter__container {
                position: relative;
                width: 100%;
                max-height: 110px;
                max-width: 1310px;
                margin: 0 auto;
                padding: 0px 15px;
            }

            .wpf-notification-counter__content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .wpf-notification-counter__figure-logo {
                max-width: 268px;
            }

            .wpf-notification-counter__figure-percentage {
                max-width: 248px;
                margin-left: -75px;
            }

            .wpf-notification-counter__img {
                width: 100%;
                max-width: 100%;
            }

            .wpf-notification-counter__list {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .wpf-notification-counter__item {
                display: flex;
                flex-direction: column;
                width: 56.14px;
                font-family: "Circular Std Book";
                font-size: 15px;
                font-style: normal;
                font-weight: 500;
                line-height: normal;
                letter-spacing: 0.75px;
                text-transform: uppercase;
                text-align: center;
                color: #FFF;
            }

            .wpf-notification-counter__time {
                font-size: 32px;
                font-family: "Inter";
                font-style: normal;
                font-weight: 700;
                line-height: normal;
                color: #fff;
                text-align: center;
                margin-bottom: 6px;
                border-radius: 3px 3px 10px 10px;
                border-top: 1px solid #6746D0;
                border-right: 1px solid #6746D0;
                border-bottom: 5px solid #6746D0;
                border-left: 1px solid #6746D0;
                background: linear-gradient(155deg, #6746D0 2.02%, #100E35 55.1%, #100E35 131.47%);
            }

            .wpf-notification-counter__btn-area {
                display: flex;
                align-items: flex-end;
                justify-content: flex-end;
                margin-bottom: 30px;
            }

            .wpf-notification-counter__btn {
                position: relative;
                font-family: "Inter";
                font-size: 20px;
                line-height: normal;
                color: #FFF;
                text-align: center;
                filter: drop-shadow(0px 30px 60px rgba(21, 19, 119, 0.20));
                padding: 12px 22px;
                display: inline-block;
                cursor: pointer;
                text-transform: uppercase;
                background: #6746D0;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 400;
                transition: all 0.3s ease;
            }

            .wpf-notification-counter__btn:hover {
                background-color: #201cfe;
                color: #ffffff;
            }

            .wpf-notification-counter__stroke-font {
                font-size: 26px;
                font-family: "Inter";
                font-weight: 700;
            }

            /* Media Queries */
            @media only screen and (max-width: 1199px) {
                .wpf-notification-counter__container {
                    max-width: 1010px;
                }
                .wpf-notification-counter__figure-percentage {
                    margin-left: -60px;
                }
                .wpf-notification-counter__figure-percentage,
                .wpf-notification-counter__figure-logo {
                    max-width: 220px;
                }
                .wpf-notification-counter__btn {
                    font-size: 15px;
                    line-height: 20px;
                    padding: 10px 16px;
                    font-weight: 400;
                }
                .wpf-notification-counter__stroke-font {
                    font-size: 20px;
                }

                .wpf-notification-counter {
                    padding: 5px 0 4px;
                }
                .wpf-notification-counter__figure-percentage {
                    margin-left: 0px;
                }
                .wpf-notification-counter__figure-logo {
                    max-width: 160px;
                }
                .wpf-notification-counter__figure-percentage {
                    max-width: 150px;
                }
                .wpf-notification-counter__btn {
                    font-size: 14px;
                    line-height: 18px;
                    padding: 9px 10px;
                }
                .wpf-notification-counter__stroke-font {
                    font-size: 18px;
                }
                .wpf-notification-counter__time {
                    font-size: 24px;
                }
            }

            @media only screen and (max-width: 767px) {
                .wpf-notification-counter {
                    padding: 50px 0;
                    background-image: url(<?php echo esc_url(WPFNL_URL . 'admin/assets/images/halloween/promotional-banner-mobile.webp'); ?>);
                }
                .wpf-notification-counter__container {
                    max-height: none;
                }
                .wpf-notification-counter__figure-logo {
                    max-width: 174px;
                }
                .wpf-notification-counter__figure-percentage {
                    max-width: 150px;
                }
                .wpf-notification-counter__content {
                    flex-flow: column;
                    gap: 12px;
                    text-align: center;
                }
                .wpf-notification-counter__btn {
                    font-size: 16px;
                    padding: 11px 16px;
                }
                .wpf-notification-counter__stroke-font {
                    font-size: 22px;
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
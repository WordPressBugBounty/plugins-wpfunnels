<?php

/**
 * View Integration Page
 *
 * @package
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$is_pro_active             = apply_filters( 'wpfunnels/is_pro_license_activated', false );
$is_integration_active     = apply_filters( 'wpfunnels/is_integration_license_active', false );
?>

<div class="wpfnl">
    <div class="wpfnl-integrations">
        <div class="wpfnl-dashboard">
            <nav class="wpfnl-dashboard__nav">
                <?php use WPFunnels\Wpfnl_functions;
                require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>

                <?php if ( ! $is_integration_active ) : ?>
                    <div class="wpfnl-dashboard__nav-right">
                        <?php if ( $is_pro_active ) : ?>
                            <a href="https://getwpfunnels.com/pricing/" class="btn-default" target="_blank" title="<?php echo __('Upgrade Plan', 'wpfnl'); ?>" aria-label="<?php echo __('Upgrade Plan', 'wpfnl'); ?>"><?php echo __('Upgrade Plan', 'wpfnl'); ?></a>
                        <?php else : ?>
                            <a href="https://getwpfunnels.com/pricing/" class="btn-default" target="_blank" title="<?php echo __('Upgrade to Pro', 'wpfnl'); ?>" aria-label="<?php echo __('Upgrade to Pro', 'wpfnl'); ?>"><?php echo __('Upgrade to Pro', 'wpfnl'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="dashboard-nav__content">
                <div class="integrations-content">
                    <p class="integration-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 19 19" fill="none"><path d="M6.33038 8.40767H2.17653C1.6257 8.40767 1.09742 8.18885 0.707926 7.79935C0.318427 7.40985 0.0996094 6.88158 0.0996094 6.33074V2.1769C0.0996094 1.62606 0.318427 1.09779 0.707926 0.708292C1.09742 0.318794 1.6257 0.0999756 2.17653 0.0999756H6.33038C6.88121 0.0999756 7.40948 0.318794 7.79898 0.708292C8.18848 1.09779 8.4073 1.62606 8.4073 2.1769V6.33074C8.4073 6.88158 8.18848 7.40985 7.79898 7.79935C7.40948 8.18885 6.88121 8.40767 6.33038 8.40767ZM2.17653 1.48459C1.99292 1.48459 1.81683 1.55753 1.687 1.68736C1.55716 1.8172 1.48422 1.99329 1.48422 2.1769V6.33074C1.48422 6.51435 1.55716 6.69045 1.687 6.82028C1.81683 6.95011 1.99292 7.02305 2.17653 7.02305H6.33038C6.51399 7.02305 6.69008 6.95011 6.81991 6.82028C6.94975 6.69045 7.02268 6.51435 7.02268 6.33074V2.1769C7.02268 1.99329 6.94975 1.8172 6.81991 1.68736C6.69008 1.55753 6.51399 1.48459 6.33038 1.48459H2.17653Z" fill="#7a8b9a" stroke="#7a8b9a" stroke-width=".2"/><path d="M16.0218 8.40767H11.8679C11.3171 8.40767 10.7888 8.18885 10.3993 7.79935C10.0098 7.40985 9.79102 6.88158 9.79102 6.33074V2.1769C9.79102 1.62606 10.0098 1.09779 10.3993 0.708292C10.7888 0.318794 11.3171 0.0999756 11.8679 0.0999756H16.0218C16.5726 0.0999756 17.1009 0.318794 17.4904 0.708292C17.8799 1.09779 18.0987 1.62606 18.0987 2.1769V6.33074C18.0987 6.88158 17.8799 7.40985 17.4904 7.79935C17.1009 8.18885 16.5726 8.40767 16.0218 8.40767ZM11.8679 1.48459C11.6843 1.48459 11.5082 1.55753 11.3784 1.68736C11.2486 1.8172 11.1756 1.99329 11.1756 2.1769V6.33074C11.1756 6.51435 11.2486 6.69045 11.3784 6.82028C11.5082 6.95011 11.6843 7.02305 11.8679 7.02305H16.0218C16.2054 7.02305 16.3815 6.95011 16.5113 6.82028C16.6412 6.69045 16.7141 6.51435 16.7141 6.33074V2.1769C16.7141 1.99329 16.6412 1.8172 16.5113 1.68736C16.3815 1.55753 16.2054 1.48459 16.0218 1.48459H11.8679Z" fill="#7a8b9a" stroke="#7a8b9a" stroke-width=".2"/><path d="M6.33038 18.0999H2.17653C1.6257 18.0999 1.09742 17.8811 0.707926 17.4916C0.318427 17.1021 0.0996094 16.5738 0.0996094 16.023V11.8692C0.0996094 11.3183 0.318427 10.7901 0.707926 10.4006C1.09742 10.0111 1.6257 9.79224 2.17653 9.79224H6.33038C6.88121 9.79224 7.40948 10.0111 7.79898 10.4006C8.18848 10.7901 8.4073 11.3183 8.4073 11.8692V16.023C8.4073 16.5738 8.18848 17.1021 7.79898 17.4916C7.40948 17.8811 6.88121 18.0999 6.33038 18.0999ZM2.17653 11.1769C1.99292 11.1769 1.81683 11.2498 1.687 11.3796C1.55716 11.5095 1.48422 11.6855 1.48422 11.8692V16.023C1.48422 16.2066 1.55716 16.3827 1.687 16.5125C1.81683 16.6424 1.99292 16.7153 2.17653 16.7153H6.33038C6.51399 16.7153 6.69008 16.6424 6.81991 16.5125C6.94975 16.3827 7.02268 16.2066 7.02268 16.023V11.8692C7.02268 11.6855 6.94975 11.5095 6.81991 11.3796C6.69008 11.2498 6.51399 11.1769 6.33038 11.1769H2.17653Z" fill="#7a8b9a" stroke="#7a8b9a" stroke-width=".2"/><path d="M13.9449 18.0999C13.1233 18.0999 12.3202 17.8563 11.6371 17.3999C10.954 16.9435 10.4216 16.2947 10.1072 15.5357C9.79282 14.7767 9.71056 13.9415 9.87083 13.1357C10.0311 12.3299 10.4267 11.5898 11.0077 11.0089C11.5886 10.4279 12.3287 10.0323 13.1345 9.87205C13.9403 9.71178 14.7755 9.79404 15.5345 10.1084C16.2935 10.4228 16.9422 10.9552 17.3987 11.6383C17.8551 12.3214 18.0987 13.1245 18.0987 13.9461C18.0976 15.0474 17.6596 16.1033 16.8809 16.8821C16.1021 17.6608 15.0462 18.0988 13.9449 18.0999ZM13.9449 11.1769C13.3972 11.1769 12.8618 11.3393 12.4064 11.6436C11.951 11.9478 11.596 12.3803 11.3864 12.8863C11.1768 13.3924 11.122 13.9492 11.2288 14.4863C11.3357 15.0235 11.5994 15.5169 11.9867 15.9042C12.374 16.2915 12.8674 16.5553 13.4046 16.6621C13.9418 16.769 14.4986 16.7141 15.0046 16.5045C15.5106 16.2949 15.9431 15.94 16.2474 15.4846C16.5517 15.0292 16.7141 14.4938 16.7141 13.9461C16.7141 13.2116 16.4223 12.5073 15.903 11.9879C15.3837 11.4686 14.6793 11.1769 13.9449 11.1769Z" fill="#7a8b9a" stroke="#7a8b9a" stroke-width=".2"/></svg>
                        <?php echo __('Integrations', 'wpfnl'); ?>
                    </p>

                    <div class="integrations-banner">
                        <span class="circle-blur"></span>
                        <h1 class="banner-title">
                            <?php if ( $is_pro_active ) : ?>
                                <?php echo __('Unlock Every Integration with a Higher Plan', 'wpfnl'); ?>
                            <?php else : ?>
                                <?php echo __('Unlock Every Integration with WPFunnels Pro', 'wpfnl'); ?>
                            <?php endif; ?>
                        </h1>

                        <p class="banner-description">
                            <?php if ( $is_pro_active ) : ?>
                                <?php echo __('You are on the Starter plan. Upgrade to the Medium Annual or Large Annual plan to connect ActiveCampaign, Mailchimp, HubSpot, and 15 more CRM & email tools.', 'wpfnl'); ?>
                            <?php else : ?>
                                <?php echo __('Connect your favorite marketing tools and automate your sales funnel effortlessly. Unlock all integrations with a single upgrade and streamline your business operations.', 'wpfnl'); ?>
                            <?php endif; ?>
                        </p>

                        <a href="https://getwpfunnels.com/pricing/" class="banner-cta-btn" title="<?php echo $is_pro_active ? __('Upgrade Plan', 'wpfnl') : __('Upgrade to Pro', 'wpfnl'); ?>" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16" fill="none"><path d="M13.3876 5.99025L11.4214 6.47988L8.9656 3.41968C8.85807 3.28565 8.72182 3.17748 8.56689 3.10315C8.41197 3.02882 8.24233 2.99023 8.07049 2.99023C7.89866 2.99023 7.72902 3.02882 7.57409 3.10315C7.41917 3.17748 7.28291 3.28565 7.17538 3.41968L4.71957 6.47988L2.71514 5.99025C2.52304 5.94427 2.32237 5.94832 2.13228 6.002C1.94219 6.05568 1.76904 6.15721 1.62937 6.29688C1.4897 6.43655 1.38818 6.6097 1.3345 6.79978C1.28082 6.98987 1.27677 7.19055 1.32275 7.38264L2.67689 12.1413C2.72316 12.3067 2.8238 12.4518 2.96259 12.5531C3.10137 12.6545 3.27022 12.7061 3.44194 12.6997H12.6225C12.7943 12.7061 12.9631 12.6545 13.1019 12.5531C13.2407 12.4518 13.3413 12.3067 13.3876 12.1413L14.7494 7.38264C14.7933 7.1936 14.789 6.99653 14.7367 6.80962C14.6843 6.62272 14.5858 6.45201 14.4501 6.31325C14.3144 6.17449 14.1459 6.07216 13.9602 6.01571C13.7745 5.95926 13.5776 5.9505 13.3876 5.99025Z" fill="#2fcf5c"/><path d="M0.956313 5.16397C1.48447 5.16397 1.91263 4.73581 1.91263 4.20766C1.91263 3.6795 1.48447 3.25134 0.956313 3.25134C0.428156 3.25134 0 3.6795 0 4.20766C0 4.73581 0.428156 5.16397 0.956313 5.16397Z" fill="#2fcf5c"/><path d="M15.1106 5.16397C15.6388 5.16397 16.0669 4.73581 16.0669 4.20766C16.0669 3.6795 15.6388 3.25134 15.1106 3.25134C14.5825 3.25134 14.1543 3.6795 14.1543 4.20766C14.1543 4.73581 14.5825 5.16397 15.1106 5.16397Z" fill="#2fcf5c"/><path d="M8.03249 1.91263C8.56064 1.91263 8.9888 1.48447 8.9888 0.956313C8.9888 0.428156 8.56064 0 8.03249 0C7.50433 0 7.07617 0.428156 7.07617 0.956313C7.07617 1.48447 7.50433 1.91263 8.03249 1.91263Z" fill="#2fcf5c"/><path d="M12.6234 15.4924H3.44279C3.23988 15.4924 3.04529 15.4118 2.90181 15.2683C2.75834 15.1248 2.67773 14.9302 2.67773 14.7273C2.67773 14.5244 2.75834 14.3298 2.90181 14.1864C3.04529 14.0429 3.23988 13.9623 3.44279 13.9623H12.6234C12.8263 13.9623 13.0209 14.0429 13.1644 14.1864C13.3078 14.3298 13.3884 14.5244 13.3884 14.7273C13.3884 14.9302 13.3078 15.1248 13.1644 15.2683C13.0209 15.4118 12.8263 15.4924 12.6234 15.4924Z" fill="#2fcf5c"/></svg>
                            <?php echo $is_pro_active ? __('Upgrade Plan', 'wpfnl') : __('Get WPFunnels Pro', 'wpfnl'); ?>
                        </a>
                    </div>

                    <?php
                    $integrations = array(
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-active-campaign-icon.svg',
                            'title' => 'ActiveCampaign',
                            'description' => 'Create advanced automated sequences and tag customers based on funnel behavior.',
                            'is_locked' => true,
                            'is_new' => true
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-mailchimp-icon.svg',
                            'title' => 'Mailchimp',
                            'description' => 'Sync your contacts instantly and trigger personalized email campaigns for every new lead.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-mailpoet-icon.svg',
                            'title' => 'MailPoet',
                            'description' => 'Manage your newsletter and automated emails directly within your WordPress dashboard.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-constant-contact-icon.svg',
                            'title' => 'Constant Contact',
                            'description' => 'Seamlessly move your funnel leads into professional email marketing workflows.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-mailerlite-icon.svg',
                            'title' => 'MailerLite',
                            'description' => 'A simple yet powerful way to capture leads and grow your email subscriber list.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-sendinblue-icon.svg',
                            'title' => 'Breo (Sendinblue)',
                            'description' => 'Send transactional emails and SMS alerts to your customers in real-time.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-encharge-icon.svg',
                            'title' => 'Encharge',
                            'description' => 'Automate your customer journey with behavior-based emails and deep CRM synchronization.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-aweber-icon.svg',
                            'title' => 'AWeber',
                            'description' => 'Automate your lead generation and build long-term relationships with your audience.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-convertkit-icon.svg',
                            'title' => 'ConvertKit',
                            'description' => 'Designed for creators—trigger tailored automations based on specific funnel actions.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-hubspot-icon.svg',
                            'title' => 'HubSpot',
                            'description' => 'Automatically sync funnel data with your CRM to manage your entire sales pipeline.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-zoho-icon.svg',
                            'title' => 'Zoho CRM',
                            'description' => 'Track every customer interaction and organize your leads for better conversion.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-zapier-icon.svg',
                            'title' => 'Zapier',
                            'description' => 'Automate workflows by connecting your favorite apps and services.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-getresponse-icon.svg',
                            'title' => 'GetResponse',
                            'description' => 'Manage your email marketing campaigns and automate your customer journeys.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-omnisend-icon.svg',
                            'title' => 'Omnisend',
                            'description' => 'Enhance your e-commerce marketing with automated workflows and personalized messaging.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-wpfusion-icon.svg',
                            'title' => 'WPFusion',
                            'description' => 'Integrate your WordPress site with your CRM to automate marketing and sales processes.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-drip-icon.svg',
                            'title' => 'Drip',
                            'description' => 'Automate your email marketing campaigns and nurture your leads effectively.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-pabblyemail-icon.svg',
                            'title' => 'Pabbly Connect',
                            'description' => 'Connect your apps and automate workflows seamlessly.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                        array(
                            'icon' => WPFNL_URL .'admin/assets/images/integration-pabblyemail-icon.svg',
                            'title' => 'Pabbly Email Marketing',
                            'description' => 'Manage your email marketing campaigns and automate your customer journeys.',
                            'is_locked' => true,
                            'is_new' => false
                        ),
                    );
                    ?>

                    <div class="integrations-list">
                        <div class="integrations-header">
                            <h2 class="integrations-title"><?php echo __('Available Integrations', 'wpfnl'); ?></h2>
                            <div class="integrations-search">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M13.815 12.6396L11.1124 9.93698C11.9315 8.86475 12.3807 7.56419 12.3809 6.19181C12.3809 4.53796 11.7368 2.98299 10.5671 1.81356C9.39768 0.644124 7.84292 0 6.18886 0C4.53501 0 2.98004 0.644124 1.81061 1.81356C-0.603536 4.22791 -0.603536 8.15613 1.81061 10.5701C2.98004 11.7397 4.53501 12.3838 6.18886 12.3838C7.56124 12.3837 8.8618 11.9344 9.93403 11.1154L12.6366 13.818C12.7992 13.9807 13.0126 14.0621 13.2258 14.0621C13.439 14.0621 13.6524 13.9807 13.815 13.818C14.1405 13.4926 14.1405 12.9649 13.815 12.6396ZM2.98899 9.39168C1.22467 7.62736 1.22487 4.75647 2.98899 2.99194C3.84369 2.13745 4.98016 1.66667 6.18886 1.66667C7.39777 1.66667 8.53404 2.13745 9.38873 2.99194C10.2434 3.84664 10.7142 4.98311 10.7142 6.19181C10.7142 7.40072 10.2434 8.53699 9.38873 9.39168C8.53404 10.2464 7.39777 10.7172 6.18886 10.7172C4.98016 10.7172 3.84369 10.2464 2.98899 9.39168Z" fill="#707070"/></svg>
                                <input type="text" id="wpfnl-integration-search" class="integrations-search-input" placeholder="<?php echo __('Search', 'wpfnl'); ?>">
                            </div>
                        </div>

                        <div class="integrations-grid" id="wpfnl-integrations-grid">
                            <?php foreach ($integrations as $integration) : ?>
                                <div class="integration-card" data-title="<?php echo esc_attr(strtolower($integration['title'])); ?>">
                                    <?php if ($integration['is_locked']) : ?>
                                        <div class="integration-card-lock">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="20" viewBox="0 0 17 20" fill="none"><path d="M14.1667 7.02V5.83333C14.1667 4.28624 13.5521 2.80251 12.4581 1.70854C11.3642 0.614582 9.88043 0 8.33333 0C6.78624 0 5.30251 0.614582 4.20854 1.70854C3.11458 2.80251 2.5 4.28624 2.5 5.83333V7.02C1.7578 7.34392 1.12608 7.8771 0.682083 8.55434C0.238088 9.23158 0.00106531 10.0235 0 10.8333V15.8333C0.00132321 16.938 0.440735 17.997 1.22185 18.7782C2.00297 19.5593 3.062 19.9987 4.16667 20H12.5C13.6047 19.9987 14.6637 19.5593 15.4448 18.7782C16.2259 17.997 16.6653 16.938 16.6667 15.8333V10.8333C16.6656 10.0235 16.4286 9.23158 15.9846 8.55434C15.5406 7.8771 14.9089 7.34392 14.1667 7.02ZM4.16667 5.83333C4.16667 4.72826 4.60565 3.66846 5.38706 2.88706C6.16846 2.10565 7.22827 1.66667 8.33333 1.66667C9.4384 1.66667 10.4982 2.10565 11.2796 2.88706C12.061 3.66846 12.5 4.72826 12.5 5.83333V6.66667H4.16667V5.83333ZM15 15.8333C15 16.4964 14.7366 17.1323 14.2678 17.6011C13.7989 18.0699 13.163 18.3333 12.5 18.3333H4.16667C3.50363 18.3333 2.86774 18.0699 2.3989 17.6011C1.93006 17.1323 1.66667 16.4964 1.66667 15.8333V10.8333C1.66667 10.1703 1.93006 9.53441 2.3989 9.06557C2.86774 8.59672 3.50363 8.33333 4.16667 8.33333H12.5C13.163 8.33333 13.7989 8.59672 14.2678 9.06557C14.7366 9.53441 15 10.1703 15 10.8333V15.8333Z" fill="#7a8b9a"/></svg>
                                        </div>
                                    <?php endif; ?>

                                    <div class="integration-card-icon">
                                        <?php echo '<img src="' . esc_url($integration['icon']) . '" alt="' . esc_attr($integration['title']) . '" width="40" height="40">'; ?>
                                    </div>

                                    <h3 class="integration-card-title">
                                        <?php echo esc_html($integration['title']); ?>
                                        <?php if ($integration['is_new']) : ?>
                                            <span class="integration-card-badge">New</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="integration-card-description"><?php echo esc_html($integration['description']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="integrations-no-results" id="wpfnl-no-results" style="display: none;">
                            <p><?php echo __('No integrations found in your search', 'wpfnl'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        
    </div>

    <?php require_once WPFNL_DIR . '/admin/partials/helper-resource.php'; ?>
</div>

<script>
(function() {
    var searchInput = document.getElementById('wpfnl-integration-search');
    var integrationsGrid = document.getElementById('wpfnl-integrations-grid');
    var noResults = document.getElementById('wpfnl-no-results');
    var cards = integrationsGrid.querySelectorAll('.integration-card');

    searchInput.addEventListener('input', function() {
        var searchTerm = this.value.toLowerCase().trim();
        var hasResults = false;

        cards.forEach(function(card) {
            var title = card.getAttribute('data-title');
            
            if (title.indexOf(searchTerm) !== -1) {
                card.style.display = 'block';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        if (!hasResults) {
            integrationsGrid.style.display = 'none';
            noResults.style.display = 'block';
        } else {
            integrationsGrid.style.display = 'grid';
            noResults.style.display = 'none';
        }
    });
})();
</script>
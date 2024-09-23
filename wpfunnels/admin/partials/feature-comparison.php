<?php
// Define the array of objects
$features = [
    [
        'label' => 'Opt-in form',
        'free' => true,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Collect leads (via Mail Mint)',
        'free' => true,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Order bumps at the checkout',
        'free' => true,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Conditional steps',
        'free' => true,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Course funnels (for course products in WooCommerce)',
        'free' => true,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Multiple order bumps',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'One-click upsell & downsell',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'A/B Testing',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Funnel Analytics',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Multi-step checkout',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Basic checkout field editor',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Quantity selector during checkout',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Replace one offer with another',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Custom bridge steps',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Subscription products support',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Email automation based on buyer actions (via Mail Mint)',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Import/Export funnels',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Webhooks support',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Advanced Checkout form customizer for WooCommerce',
        'free' => false,
        'small' => true,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Integration with external CRMs & automation tools',
        'free' => false,
        'small' => false,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Conditional WooCommerce funnels',
        'free' => false,
        'small' => false,
        'medium' => true,
        'large' => true,
    ],
    [
        'label' => 'Funnels for LearnDash courses',
        'free' => false,
        'small' => false,
        'medium' => true,
        'large' => true,
    ]
];

// Function to return the appropriate SVG based on boolean value
function getSvgIcon($value) {
    if ($value) {
        return '<svg fill="none" width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="10.5" fill="#F5F5F7" stroke="#00B67C"/><path stroke="#00B67C" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.182 7l-7 7L6 10.818"/></svg>';
    } else {
        return '<svg fill="none" width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="10.5" fill="#F5F5F7" stroke="#EC813F"/><path stroke="#EC813F" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7l-8 8m0-8l8 8"/></svg>';
    }
}
?>

<section class="wpfnl-pro-vs-free-page-wrapper">
    <h1 class="wpfnl-pvf-heading"><?php echo __( 'WPFunnels Free vs Pro', 'wpfnl' ); ?></h1>
    <p class="wpfnl-pvf-sub-heading"><?php 
    // echo __( 'Here’s a basic overview of WPFunnels Free vs Pro.', 'wpfnl' ); 
    ?>
    </p>

    <div class="wpfnl-pvf-table-wrapper">
        <div class="wpfnl-pvf-table-header">
            <div class="wpfnl-pvf-table-row plan">
                <div class="wpfnl-pvf-head-cell col-feature"></div>
                <div class="wpfnl-pvf-head-cell col-free">
                    <p>Basic</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-small">
                    <p>Small</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-medium">
                    <p>Medium</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-large">
                    <p>Large</p>
                </div>
            </div>

            <div class="wpfnl-pvf-table-row price">
                <div class="wpfnl-pvf-head-cell col-feature"></div>
                <div class="wpfnl-pvf-head-cell col-free">
                    <p><strong>Free</strong></p>
                </div>
                <div class="wpfnl-pvf-head-cell col-small">
                    <p><strong>$97</strong>/Year</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-medium">
                    <p><strong>$147</strong>/Year</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-large">
                    <p><strong>$237</strong>/Year</p>
                </div>
            </div>

            <div class="wpfnl-pvf-table-row cta">
                <div class="wpfnl-pvf-head-cell col-feature">
                    <p>Features</p>
                </div>
                <div class="wpfnl-pvf-head-cell col-free">
                    <p>
                        <svg fill="none" width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="11" fill="#363B4E"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.182 7l-7 7L6 10.818"/></svg>
                        <span>Your current plan</span>
                    </p>
                </div>
                <div class="wpfnl-pvf-head-cell col-small">
                    <a href="https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/" target="_blank" class="wpfn-btn-upgrade">Upgrade</a>
                </div>
                <div class="wpfnl-pvf-head-cell col-medium">
                    <a href="https://useraccount.getwpfunnels.com/wpfunnels-annual-5-sites/steps/5-sites-annual-checkout/" target="_blank" class="wpfn-btn-upgrade">Upgrade</a>
                </div>
                <div class="wpfnl-pvf-head-cell col-large">
                    <a href="https://useraccount.getwpfunnels.com/wpfunnels-annual-unlimited/steps/annual-unlimited-checkout/" target="_blank" class="wpfn-btn-upgrade">Upgrade</a>
                </div>
            </div>
        </div>

        <div class="wpfnl-pvf-table-content">
            <div class="wpfnl-pvf-table-row">
                <div class="table-col col-feature">
                    <p>License usage (no. of sites)</p>
                </div>

                <div class="table-col col-free">
                    <p>None</p>
                </div>

                <div class="table-col col-small">
                    <p>1 site</p>
                </div>

                <div class="table-col col-medium">
                    <p>5 sites</p>
                </div>

                <div class="table-col col-large">
                    <p>50 sites</p>
                </div>
            </div>
            <div class="wpfnl-pvf-table-row">
                <div class="table-col col-feature">
                    <p>No. of Funnels</p>
                </div>

                <div class="table-col col-free">
                    <p>3</p>
                </div>

                <div class="table-col col-small">
                    <p>Unlimited</p>
                </div>

                <div class="table-col col-medium">
                    <p>Unlimited</p>
                </div>

                <div class="table-col col-large">
                    <p>Unlimited</p>
                </div>
            </div>
            <div class="wpfnl-pvf-table-row">
                <div class="table-col col-feature">
                    <p>Funnel templates</p>
                </div>

                <div class="table-col col-free">
                    <p>Limited</p>
                </div>

                <div class="table-col col-small">
                    <p>All</p>
                </div>

                <div class="table-col col-medium">
                    <p>All</p>
                </div>

                <div class="table-col col-large">
                    <p>All</p>
                </div>
            </div>

            <?php foreach($features as $feature): ?>
                <div class="wpfnl-pvf-table-row">
                    <div class="table-col col-feature">
                        <p><?php echo $feature['label']; ?></p>
                    </div>

                    <div class="table-col col-free">
                        <p><?php echo getSvgIcon($feature['free']); ?></p>
                    </div>

                    <div class="table-col col-small">
                        <p><?php echo getSvgIcon($feature['small']); ?></p>
                    </div>

                    <div class="table-col col-medium">
                        <p><?php echo getSvgIcon($feature['medium']); ?></p>
                    </div>

                    <div class="table-col col-large">
                        <p><?php echo getSvgIcon($feature['large']); ?></p>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    </div>
</section>
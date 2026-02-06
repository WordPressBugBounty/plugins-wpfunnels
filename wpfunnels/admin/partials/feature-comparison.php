<?php
/* ---------------- Plans ---------------- */
$plans = [
    ["name" => "Basic", "price" => "Free", "cta" => "Current plan", "currentPlan" => true],
    ["name" => "Small", "price" => "$97", "cta" => "Upgrade", "link" => "https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/"],
    ["name" => "Medium", "price" => "$147", "cta" => "Upgrade", "popular" => true, "link" => "https://useraccount.getwpfunnels.com/wpfunnels-annual-5-sites/steps/5-sites-annual-checkout/"],
    ["name" => "Large", "price" => "$237", "cta" => "Upgrade", "link" => "https://useraccount.getwpfunnels.com/wpfunnels-annual-unlimited/steps/annual-unlimited-checkout/"],
];

/* ---------------- Features ---------------- */
$features = [
    [
        "group" => "Funnels & Usage Limits",
        "items" => [
            [
                "label" => "License usage (no. of sites)",
                "tooltipText" => "Defines how many websites you can activate with this license.",
                "values" => ["None", "1 site", "5 sites", "50 sites"],
                "hasBorderTop" => false,
                "isHighlighted" => false,
            ],
            [
                "label" => "No. of Funnels",
                "tooltipText" => "The total number of funnels you can create with this plan.",
                "values" => ["3", "Unlimited", "Unlimited", "Unlimited"],
                "hasBorderTop" => true,
                "isHighlighted" => false,
            ],
            [
                "label" => "Funnel Templates",
                "tooltipText" => "Pre-built funnel designs you can import and customize instantly.",
                "values" => ["Limited", "All", "All", "All"],
                "hasBorderTop" => true,
                "isHighlighted" => true,
            ],
        ],
    ],
    [
        "group" => "Checkout Features",
        "items" => [
            ["label" => "Multi-step Checkout", "tooltipText" => "Break the checkout process into multiple easy steps for higher conversions.", "values" => [false, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
            ["label" => "Basic Checkout Field Editor", "tooltipText" => "Easily add, remove, or edit basic checkout fields to fit your business needs.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Quantity Selector During Checkout", "tooltipText" => "Allow customers to adjust product quantities directly on the checkout page.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Advanced Checkout Form Customizer", "tooltipText" => "Advanced customization options for the WooCommerce checkout form.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Order Bumps", "tooltipText" => "Smart order bumps using customer data to display most relevant products or offers.", "values" => [true, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
        ],
    ],
    [
        "group" => "One-Click Upsells / Downsells",
        "items" => [
            ["label" => "Dynamic One-Click Upsells", "tooltipText" => "Use cart contents or customer data to display relevant upsells for maximum conversion.", "values" => [false, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
            ["label" => "Dynamic Upsell Templates", "tooltipText" => "Professional templates to help you sell more even if you’re not a designer.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Replace One Offer with Another", "tooltipText" => "Easily swap out one offer for another in your funnel.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
        ],
    ],
    [
        "group" => "Order Bump Features",
        "items" => [
            ["label" => "Multiple Order Bumps", "tooltipText" => "Add multiple order bumps to a single checkout so customers can accept more than one offer with one click.", "values" => [false, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
        ],
    ],
    [
        "group" => "Advanced Funnel Features",
        "items" => [
            ["label" => "Funnel Analytics", "tooltipText" => "Analyze transactions and user behavior to refine conversions and make more profit.", "values" => [false, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
            ["label" => "A/B Testing", "tooltipText" => "Increase conversions and sales with WPFunnels A/B Testing by running simple tests.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Conditional WooCommerce Funnels", "tooltipText" => "Create personalized funnels based on user behavior and conditions.", "values" => [false, false, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Import/Export Funnels", "tooltipText" => "Easily import and export your funnels for use on other sites or for backup purposes.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Course Funnels", "tooltipText" => "Create funnels specifically for course products in WooCommerce.", "values" => [true, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            
        ],
    ],
    [
        "group" => "Integrations",
        "items" => [
            ["label" => "Integration With Automation Tools", "tooltipText" => "Connect your account to popular automation tools like Mail Mint, FluentCRM, MailPoet, Zapier and so on.", "values" => [false, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
            ["label" => "Integration with External CRMs", "tooltipText" => "Sync and manage contacts with popular CRMs including MailChimp, ActiveCampaign, Constant Contact, AWeber, Sendinblue, MailerLite and so on.", "values" => [false, false, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Webhooks Support", "tooltipText" => "Send real-time data from WPFunnels to any external app or service using webhooks.", "values" => [false, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
            ["label" => "Funnels for LearnDash Courses", "tooltipText" => "Create funnels specifically for LearnDash courses to boost sales and engagement.", "values" => [false, false, true, true], "hasBorderTop" => true, "isHighlighted" => false],
        ],
    ],
    [
        "group" => "Others Benefits",
        "items" => [
            ["label" => "Amazing User Community", "tooltipText" => "Amazing User Community is already a great message unless you’re looking for a different meaning.", "values" => [true, true, true, true], "hasBorderTop" => false, "isHighlighted" => false],
            ["label" => "Great Documentation & Video Tutorials", "tooltipText" => "Comprehensive guides and video tutorials to help you get the most out of WPFunnels.", "values" => [true, true, true, true], "hasBorderTop" => true, "isHighlighted" => false],
        ],
    ],
];

$listItems = [
    "Regular Product Updates",
    "Priority Support",
    "14-Day Money-back Guarantee",
    "Instant Download",
];

/* ---------------- Helper Functions ---------------- */
function renderValueCell($value) {
    if (is_bool($value)) {
        if ($value) {
            $icon = file_get_contents(WPFNL_DIR . '/admin/partials/icons/free-vs-pro-check-icon.php');
            return '<span class="check">' . $icon . '</span>';
        } else {
            $icon = file_get_contents(WPFNL_DIR . '/admin/partials/icons/free-vs-pro-cross-icon.php');
            return '<span class="cross">' . $icon . '</span>';
        }
    }
    return '<span>' . htmlspecialchars($value) . '</span>';
}


function renderCheckIcon() {
    return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
}
?>


<section>
    <div class="wpfnl-free-vs-pro-comparison-wrapper">
        <div class="table-content-wrapper">
            <!-- Section Header -->
            <div class="free-vs-pro-menu">
                <h2 class="title"><?php echo __( 'WPFunnels Free vs Pro', 'wpfnl' ); ?></h2>
                <p class="free-pro-description"><?php echo __( 'Discover the differences between Free and Pro to decide which plan works for you.', 'wpfnl' ); ?></p>
            </div>

            <!-- Comparison Table -->
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th class="feature-column">
                            <h3 class="feature-header"><?php echo __( 'Features', 'wpfnl' ); ?></h3>
                        </th>
                        <?php foreach ($plans as $i => $plan): ?>
                            <?php
                            $isPopular = isset($plan['popular']) && $plan['popular'];
                            $isCurrentPlan = isset($plan['currentPlan']) && $plan['currentPlan'];
                            ?>
                            <th class="<?= $isPopular ? 'popular' : 'regular' ?>">
                                <div class="<?= $isPopular ? 'plan-card popular-card' : 'plan-card' ?>">
                                    <?php if ($isPopular): ?>
                                        <span class="badge"><?php echo __( 'Most Popular', 'wpfnl' ); ?></span>
                                    <?php endif; ?>

                                    <div class="card-content">
                                        <h3 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                                        <p class="price">
                                            <?= htmlspecialchars($plan['price']) ?>
                                            <?php if (strtolower($plan['name']) !== 'basic'): ?>
                                                <span class="time"><?php echo __( '/year', 'wpfnl' ); ?></span>
                                            <?php endif; ?>
                                        </p>

                                        <?php if (isset($plan['badge'])): ?>
                                            <span class="current"><?= htmlspecialchars($plan['badge']) ?></span>
                                        <?php else: ?>
                                            <a href="<?= isset($plan['link']) ? htmlspecialchars($plan['link']) : 'https://getwpfunnels.com/pricing/' ?>" target="_blank" role="button" 
                                            class="upgrade-btn <?= $isCurrentPlan ? 'current-plan-btn' : '' ?>">
                                                <?php if ($isCurrentPlan): ?>
                                                    <?= renderCheckIcon() ?>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($plan['cta']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($features as $gi => $group): ?>
                        <!-- Group Title Row -->
                        <tr class="group-row">
                            <td colspan="<?= count($plans) + 1 ?>"><?= htmlspecialchars($group['group']) ?></td>
                        </tr>

                        <!-- Feature Rows -->
                        <?php foreach ($group['items'] as $fi => $feature): ?>
                            <tr class="feature-row <?= empty($feature['hasBorderTop']) ? 'no-border-top' : '' ?> <?= !empty($feature['isHighlighted']) ? 'highlight-feature' : '' ?>">
                                <td class="feature-label">
                                    <div class="feature-label-content">
                                        <span><?= htmlspecialchars($feature['label']) ?></span>
                                        <?php if (!empty($feature['tooltipText'])): ?>
                                            <span class="tooltip-wrapper">
                                                <?php require WPFNL_DIR . '/admin/partials/icons/tooltip-icon.php';?>
                                                <span class="tooltip-text"><?= htmlspecialchars($feature['tooltipText']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <?php foreach ($feature['values'] as $vi => $value): ?>
                                    <td class="feature-cell">
                                        <?= renderValueCell($value) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="pro-features-link-row">
                        <td colspan="<?= count($plans) + 1 ?>">
                            <a href="https://getwpfunnels.com/features/" target="_blank">
                                <span><?php echo __( 'See all WPFunnels Pro features', 'wpfnl' ); ?></span>
                                <?php require WPFNL_DIR . '/admin/partials/icons/external-link-icon.php'; ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <ul class="list-item-wrapper">
                <?php foreach ($listItems as $idx => $item): ?>
                    <li class="list-item">
                        <?php require WPFNL_DIR . '/admin/partials/icons/list-check-icon.php';?>
                        <span><?= htmlspecialchars($item) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Section Footer -->
        <div class="free-vs-pro-table-footer">
            <div class="badges-wrapper">
                <div class="security-badges">
                    <div class="payment-image">
                        <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/payment-badge.webp'); ?>?>" alt="Payment Badge" />
                    </div>
                    <div class="payment-image satisfaction-image">
                        <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/satisfaction-badge.webp'); ?>?>" alt="Satisfaction Badge" />
                    </div>
                    <div class="review-image">
                        <img src="<?php echo esc_url(WPFNL_URL . 'admin/assets/images/review-badge.webp'); ?>?>" alt="Review Badge" />
                    </div>
                </div>
                <p class="badges-description"><?php echo __( "Get access to powerful features and build high-converting funnels effortlessly — save time, boost sales, and grow without limits.", 'wpfnl' ); ?></p>
            </div>
    </div>
</section>
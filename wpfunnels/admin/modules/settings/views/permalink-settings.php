<?php
/**
 * View permalink settings
 * 
 * @package
 */
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-permalink-preference"><?php esc_html_e('Preference Link', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-permalink-base"><?php esc_html_e('Post Type Permalink Base', 'wpfnl'); ?></li>
    </ul>
</div>

<div class="wpfnl-tab-content active" id="tab-permalink-preference">
    <div class="wpfnl-field-wrapper">
        <div class="wpfnl-all-fields">
            <?php
            $structures = [
                'default'     => __('Default Permalink', 'wpfnl'),
                'funnel-step' => __('Funnel and Step Slug', 'wpfnl'),
                'funnel'      => __('Funnel Slug', 'wpfnl'),
                'step'        => __('Step Slug', 'wpfnl')
            ];
            foreach ($structures as $slug => $label_title) :
                $id = $slug . '-permalink';
                ?>
                <div class="wpfnl-fields">
                    <div class="wpfnl-radiobtn">
                        <input type="radio" name="wpfunnels-set-permalink" id="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($slug); ?>" <?php checked($this->permalink_settings['structure'], $slug); ?> />
                        <label for="<?php echo esc_attr($id); ?>">
                            <span class="label-title"><?php echo esc_html($label_title); ?></span>
                            <span class="label-subheading">
                                <?php
                                switch ($slug) {
                                    case 'default':
                                        esc_html_e('Default WordPress Permalink', 'wpfnl');
                                        break;
                                    case 'funnel-step':
                                        echo home_url(); ?>/<?php echo '<code class="funnelbase"></code>/%funnelname%/<code class="stepbase"></code>/%stepname%/';
                                        break;
                                    case 'funnel':
                                        echo home_url(); ?>/<?php echo '<code class="funnelbase"></code>/%funnelname%/%stepname%/';
                                        break;
                                    case 'step':
                                        echo home_url(); ?>/%funnelname%/<?php echo '<code class="stepbase"></code>/%stepname%/';
                                        break;
                                }
                                ?>
                            </span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="wpfnl-tab-content" id="tab-permalink-base">
    <div class="wpfnl-field-wrapper parmalink-base">
        <div class="wpfnl-parmalink-inputs-wrapper">
            <div class="wpfnl-fields">
                <label><?php esc_html_e('Funnel Base', 'wpfnl'); ?></label>
                <input type="text" name="wpfnl-permalink-funnel-base" id="wpfunnels-permalink-funnel-base" value="<?php echo sanitize_text_field($this->permalink_settings['funnel_base']); ?>" />
            </div>

            <div class="wpfnl-fields">
                <label><?php esc_html_e('Step Base', 'wpfnl'); ?></label>
                <input type="text" name="wpfnl-permalink-step-base" id="wpfunnels-permalink-step-base" value="<?php echo sanitize_text_field($this->permalink_settings['step_base']); ?>" />
            </div>
        </div>
    </div>
</div>
<!-- /settings-box -->

<?php
/**
 * Next step button
 * 
 * @package
 */
namespace WPFunnels\Widgets\Oxygen;

use WPFunnels\Wpfnl_functions;

/**
 * Class NextStepButton
 *
 * @package WPFunnels\Widgets\Oxygen
 */
class NextStepButton extends Elements {

    function init() {
        // Do some initial things here.
    }

    function afterInit() {
        // Do things after init, like remove apply params button and remove the add button.
        $this->removeApplyParamsButton();
        // $this->removeAddButton();
    }

    function name() {
        return 'Next Step Button';
    }

    function slug() {
        return "next-step-button";
    }

    function icon() {
		return	plugin_dir_url(__FILE__) . 'icon/next_steps.svg';
    }

//    function button_place() {
//        // return "interactive";
//    }

    function button_priority() {
        // return 9;
    }


    function render($options, $defaults, $content) {
		if (Wpfnl_functions::check_if_this_is_step_type('checkout') || Wpfnl_functions::check_if_this_is_step_type('upsell') || Wpfnl_functions::check_if_this_is_step_type('downsell') || Wpfnl_functions::check_if_this_is_step_type('thankyou')){
			echo __('Sorry, Please place the element in WPFunnels Landing page','wpfnl');
		}else{
			if( $this->is_builder_mode() ) {
				$id = '';
			} else {
				$id = 'wpfunnels_next_step_controller';
			}

			if( 'url-path' === $options['button_type_selector'] ){
				$url = isset($options['url_path_field']) ? $options['url_path_field'] : '';

			}elseif( 'another-funnel' === $options['button_type_selector'] ){
				$url = $options['another_funnel_field'];

			}else{
				$url = '';
			}

			?>
			
			<div class="">
				<a href="" data-button-type="<?php echo $options['button_type_selector']; ?>"  data-url="<?php echo $url; ?>" class="btn-default wpfnl-oxy-next-step-btn" id="<?php echo $id; ?>"> <?= $options['title_text'] ?> 
					<span class="wpfnl-loader"></span>
				</a>
			</div>
			<?php
		}

    }
    function controls() {
		$button_type = $this->addControlSection("button_type", __("Button",'wpfnl'), "assets/icon.png", $this);
		$button_type->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Select Button Type","wpfnl"),
                "slug" => 'button_type_selector',
                "default" => "checkout"
            )
        )->setValue(array(
            'checkout'       => __('Next Step',"wpfnl" ),
            'url-path'       => __('Go To URL Path',"wpfnl" ),
            'another-funnel'       => __('Another Funnel',"wpfnl" ),
        ))->rebuildElementOnChange();

		$button_type->addOptionControl(
			array(
				"type" => "textfield",
				"name" => __("URL Path",'wpfnl'),
				"slug" => "url_path_field",
				"default" => "",
				"condition" => 'button_type_selector=url-path',
			)
		)->rebuildElementOnChange();

		$button_type->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Choose funnel","wpfnl"),
                "slug" => 'another_funnel_field',
                "default" => "Select Funnel",
				"condition" => 'button_type_selector=another-funnel',
            )
        )->setValue(
			Wpfnl_functions::get_funnel_list()
		)->rebuildElementOnChange();

		$button_type->addOptionControl(
			array(
				"type" => "textfield",
				"name" => __("Button Text",'wpfnl'),
				"slug" => "title_text",
				"default" => "Buy Now"
			)
		)->rebuildElementOnChange();

		$button = $this->addControlSection("button_style", __("Button Style",'wpfnl'), "assets/icon.png", $this);

		$icon_selector = '.wpfnl-oxy-next-step-btn';

		$button->addPreset(
			"padding",
			"menu_item_padding",
			__("Button Padding","wpfnl"),
			$icon_selector
		)->whiteList();

		$button->addPreset(
			"margin",
			"menu_item_margin",
			__("Button Margin","wpfnl"),
			$icon_selector
		)->whiteList();

		$button->addStyleControls(
			array(
				array(
					"name" => __('Background Color','wpfnl'),
					"selector" => $icon_selector."",
					"property" => 'background-color',
				),
				array(
					"name" => __('Background Hover Color','wpfnl'),
					"selector" => $icon_selector.":hover",
					"property" => 'background-color',
				),
				array(
					"name" => __('Hover Text Color','wpfnl'),
					"selector" => $icon_selector.":hover",
					"property" => 'color',
				),

			)
		);

		$button->borderSection(
			__("Button Border",'wpfnl'),
			$icon_selector."",
			$this
		);
		$button->typographySection(
			__("Typography",'wpfnl'),
			".wpfnl-oxy-next-step-btn",
			$this
		);


	}

    function defaultCSS() {

    }

}

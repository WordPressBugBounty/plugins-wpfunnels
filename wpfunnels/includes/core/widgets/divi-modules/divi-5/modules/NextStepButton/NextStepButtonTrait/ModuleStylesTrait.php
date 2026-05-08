<?php
namespace WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButtonTrait;

if ( ! defined( 'ABSPATH' ) ) die();

trait ModuleStylesTrait {

	/**
	 * Apply module styles including the native Divi 5 button decoration styles.
	 *
	 * Divi 5 automatically processes the `button` attribute's decoration
	 * (background, border, font, spacing, button icon/style, etc.) when
	 * `$elements->style(['attrName' => 'button'])` is called here.
	 *
	 * @param array $args Style arguments passed by Divi's Module::render().
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$elements = $args['elements'] ?? null;
		if ( ! $elements ) {
			return;
		}

		if ( ! class_exists( 'ET\Builder\FrontEnd\Module\Style' ) ) {
			return;
		}

		\ET\Builder\FrontEnd\Module\Style::add(
			[
				'id'            => $args['id'] ?? '',
				'name'          => $args['name'] ?? '',
				'orderIndex'    => $args['orderIndex'] ?? 0,
				'storeInstance' => $args['storeInstance'] ?? null,
				'styles'        => [
					$elements->style( [ 'attrName' => 'button' ] ),
					$elements->style( [ 'attrName' => 'subtitle' ] ),
				],
			]
		);
	}
}

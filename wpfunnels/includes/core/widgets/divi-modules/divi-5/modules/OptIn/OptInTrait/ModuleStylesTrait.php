<?php
namespace WPFunnels\Widgets\DiviModules\D5\OptIn\OptInTrait;
if ( ! defined( 'ABSPATH' ) ) die();
trait ModuleStylesTrait {
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
					$elements->style( [ 'attrName' => 'module' ] ),
					$elements->style( [ 'attrName' => 'button' ] ),
					$elements->style( [ 'attrName' => 'formText' ] ),
				],
			]
		);
	}
}

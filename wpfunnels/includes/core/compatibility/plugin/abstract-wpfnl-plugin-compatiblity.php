<?php
/**
 * Abstract class of plugin compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

abstract class PluginCompatibility
{
    /**
	 * Check the plugin is activated
	 *
	 * @return bool
	 * @since  2.7.7
	 */
    abstract public function maybe_activate();

}

<?php
/**
 * Plugin Loader - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @uses		EarthAsylumConsulting\Traits\plugin_loader
 * @uses		EarthAsylumConsulting\Traits\plugin_environment
 *
 * @wordpress-plugin
 * Plugin Name:			{eac}Doojigger
 * Plugin URI:			https://eacDoojigger.earthasylum.com/
 * Update URI: 			https://swregistry.earthasylum.com/software-updates/eacdoojigger.json
 * Description:			{eac}Doojigger for WordPress - A new path to rapid plugin development. A powerful, extensible, multi-function architectural framework and utility plugin for WordPress.
 * Version:				2.6.2-RC2
 * Requires at least:	5.8
 * Tested up to: 		6.6
 * Requires PHP:		7.4
 * Author:				EarthAsylum Consulting
 * Author URI:			http://www.earthasylum.com
 * License: 			EarthAsylum Consulting Proprietary License - {eac}PLv1
 * License URI:			https://eacDoojigger.earthasylum.com/end-user-license-agreement/
 * Text Domain:			eacDoojigger
 * Domain Path:			/languages
 * Network: 			true
 */

/*
 * 	                                    										/ abstract_frontend.class.php \
 *	eacDoojigger.php -> eacDoojigger.class.php - abstract_context.class.php -                or                	 - abstract_core.class.php = object of class eacDoojigger
 *	                                    										\ abstract_backend.class.php  /
 *
 */

/*
	See http://rachievee.com/the-wordpress-hooks-firing-sequence/
	We trigger loading/initializing/hooks on 'plugins_loaded' action.
	Derrivatives & Extensions should use 'init' or 'wp_loaded' (headers are sent before wp_loaded)
	or {classname}_startup, {classname}_extensions_loaded, or {classname}_ready
*/


namespace EarthAsylumConsulting
{
	if (!trait_exists('\\EarthAsylumConsulting\\Traits\\plugin_loader'))
	{
		require __NAMESPACE__.'/Traits/plugin_loader.trait.php';
		require __NAMESPACE__.'/Traits/plugin_environment.trait.php';
	}

	/* deprecated (may be referenced in derivatives and extensions) */
	if (!defined('EAC_DOOJIGGER_VERSION')) define('EAC_DOOJIGGER_VERSION','2.6.2');
	/* prefered (as of 2.6.0) */
	if (!defined('EACDOOJIGGER_VERSION')) define('EACDOOJIGGER_VERSION','2.6.2');

	/**
	 * loader/initialization class
	 */
	class eacDoojigger
	{
		use \EarthAsylumConsulting\Traits\plugin_loader;
		use \EarthAsylumConsulting\Traits\plugin_environment;


		/**
		 * @var array $plugin_detail
		 * 	'PluginFile' 	- the file path to this file (__FILE__)
		 * 	'NameSpace' 	- the root namespace of our plugin class (__NAMESPACE__)
		 * 	'PluginClass' 	- the full classname of our plugin (to instantiate)
		 */
		protected static $plugin_detail =
			[
				'PluginFile'		=> __FILE__,
				'NameSpace'			=> __NAMESPACE__,
				'PluginClass'		=> __NAMESPACE__.'\\Plugin\\eacDoojigger',
				'RequiresWP'		=> '5.8',			// WordPress
				'RequiresPHP'		=> '7.4',			// PHP
				'NetworkActivate'	=>	true,			// require (or forbid) network activation
				'AutoUpdate'		=> 'self',			// automatic update 'self' or 'wp'
			];
	} // eacDoojigger
} // namespace


namespace  // global scope
{
	defined( 'ABSPATH' ) or exit;

	/**
	 * Global function to return an instance of the plugin
	 *
	 * @return object
	 */
	function eacDoojigger()
	{
		return \EarthAsylumConsulting\eacDoojigger::getInstance();
	}

	/**
	 * Run the plugin loader - only for php files?
	 */
 	\EarthAsylumConsulting\eacDoojigger::loadPlugin(false);

	/**
	 * Load required extension(s)
	 */
	add_filter( 'eacDoojigger_required_extensions', function($extensionDirectories)
		{
			$extensionDirectories[ plugin_basename( __DIR__.'/Required' ) ] = [plugin_dir_path( __FILE__ ).'EarthAsylumConsulting/Required'];
			return $extensionDirectories;
		}
	);
}

<?php
namespace EarthAsylumConsulting
{
	if (!class_exists('\\EarthAsylumConsulting\\eacDoojiggerAutoloader'))
	{
		require  WP_PLUGIN_DIR.'/eacDoojigger/autoload.php';
	}
}

namespace EarthAsylumConsulting\Traits
{
	/**
	 * Custom Plugin Loader trait - {eac}Doojigger for WordPress
	 *
	 * Sets auto-updater, text domain, includes primary class file, instantiates class object, fires plugin methods and actions.
	 * We trigger loading/initializing/hooks on 'plugins_loaded' action.
	 * Extensions should use 'init' or 'wp_loaded' (headers are sent before wp_loaded),
	 * or {classname}_extensions_loaded or {classname}_ready
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Traits
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
	 * @version		24.1120.1
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	trait plugin_loader
	{
		/**
		 * @var object static instance of the instantiated plugin object
		 */
		protected static $instance;


		/**
		 * Load/instantiate the main plugin and extensions.
		 * 	Initialize the plugin & extensions
		 * 	Add filters/actions and shortcodes once all plugins are loaded
		 *
		 * @param bool $onlyPHP - only load for PHP requests
		 * @return void
		 */
		public static function loadPlugin(bool $onlyPHP = true): void
		{
			if (! $onlyPHP || self::isPHP() )
			{
				self::$instance = self::load_plugin();
			}
		}


		/**
		 * Convenience method to get existing instance of the plugin object.
		 * @example MyPlugin()->myMethod(...)
		 *
		 * @return object (null if not instantiated)
		 */
		public static function getInstance(): ?object
		{
			return self::$instance;
		}


		/**
		 * Convenience method to call a function in our instantiated plugin object
		 * @example MyPlugin::myMethod(...)
		 *
		 * @return mixed
		 */
		public static function __callStatic(string $name, array $arguments)
		{
			return self::$instance->{$name}(...$arguments);
		}


		/**
		 * Load/instantiate the main plugin and extensions
		 *
		 * @return void
		 */
		private static function load_plugin()
		{
			$_pluginfile 	= self::$plugin_detail['PluginFile'];
			$_namespace 	= self::$plugin_detail['NameSpace'];
			$_className 	= self::$plugin_detail['PluginClass'];

			/*
			 * initialize i18n
			 */
			if (!isset(self::$plugin_detail['TextDomain']))
			{
				self::$plugin_detail['TextDomain'] = basename(str_replace('\\', '/', $_className));
			}
			load_plugin_textdomain(self::$plugin_detail['TextDomain'], false, dirname(plugin_basename( $_pluginfile )) . '/languages');

			/*
			 * run the version check (if trait was USEd)
			 */
			if (is_admin())
			{
				if (method_exists(self::class,'check_loader_environment') && !self::check_loader_environment())
				{
					return;
				}
			}

			/*
			 * set the autoloader for the object namespace folder
			 */
			$dirName = dirname($_pluginfile).DIRECTORY_SEPARATOR.$_namespace;
			if (is_dir($dirName))
			{
				\EarthAsylumConsulting\eacDoojiggerAutoloader::setAutoLoader($_namespace, $dirName);
			}
			else
			{
				\EarthAsylumConsulting\eacDoojiggerAutoloader::setAutoLoader($_namespace, dirname($_pluginfile));
			}

			/*
			 * instantiate plugin object
			 */
			$plugin = new $_className( self::$plugin_detail );

			/*
			 * wait for all plugins to load...
			 */
			add_action( 'plugins_loaded', function() use ($plugin)
				{
					/*
					 * action {classname}_startup
					 */
					$plugin->do_action( 'startup' );

					/*
					 * instantiate extension classes
					 */
					$plugin->loadAllExtensions();

					/*
					 * action {classname}_extensions_loaded
					 */
					$plugin->do_action( 'extensions_loaded' );

					/*
					 * initialize the plugin and extensions
					 */
					$plugin->initialize();

					/*
					 * action {classname}_initialize
					 */
					$plugin->do_action( 'initialize' );

					/*
					 * add callbacks to hooks
					 */
					$plugin->addActionsAndFilters();

					/*
					 * add shortcodes
					 */
					$plugin->addShortcodes();

					/*
					 * action {classname}_ready
					 */
					$plugin->do_action( 'ready' );

					/*
					 * register plugin updater
					 */
					if (is_admin())
					{
						if (isset(self::$plugin_detail['AutoUpdate']))
						{
							\do_action('eacDoojigger_register_plugin_updater',self::$plugin_detail);
						}
					}
				}
			);

			return $plugin;
		}


		/**
		 * Load object (once) for automatic updates.
		 * Now (as of v2.6) only used by extensions.
		 *
		 * @param string $pluginFile plugin file pathname ($plugin_detail['PluginFile'])
		 * @param string $updateType auto-update type ('self' | 'wp') ($plugin_detail['AutoUpdate'])
		 * @return void
		 */
		public static function loadPluginUpdater(string $pluginFile, string $updateType): void
		{
			if (! is_admin()) return; // if called directly (by extensions)

			$plugin_slug = plugin_basename($pluginFile);
			$plugin_detail = array_merge(self::$plugin_detail,
				[
					'PluginFile'	=> $pluginFile,
					'PluginClass'	=> basename($plugin_slug,'.php'),
					'AutoUpdate'	=> $updateType,
				]
			);
			\do_action('eacDoojigger_register_plugin_updater',$plugin_detail);
		}


		/**
		 * Filter for automatic updates.
		 *
		 * @param string $pluginFile plugin file pathname ($plugin_detail['PluginFile'])
		 * @param string $updateType auto-update type ('self' | 'wp') ($plugin_detail['AutoUpdate'])
		 * @return void
		 */
/*
		public static function plugin_updater_filter(string $pluginFile, string $updateType): void
		{
			static $updater = [];
			$pluginFile = plugin_basename($pluginFile);
			if (isset($updater[$pluginFile])) return;

			switch ($updateType)
			{
				case 'self':	// self-hosted updates
					new class($pluginFile,self::class)
					{
						use \EarthAsylumConsulting\Traits\plugin_update;
						public function __construct($pluginFile,$className)
						{
							$this->addPluginUpdateFile($pluginFile,$className);
						}
					};
				break;
				case 'wp':		// WP-hosted updates (fixes upgrade_notice)
					new class($pluginFile,self::class)
					{
						use \EarthAsylumConsulting\Traits\plugin_update_notice;
						public function __construct($pluginFile,$className)
						{
							$this->addPluginUpdateNotice($pluginFile,$className);
						}
					};
				break;
			}
			$updater[$pluginFile] = true;
		}
*/


		/**
		 * Only for PHP requests.
		 * We only want to load the plugin for php files
		 *
		 * @return bool
		 */
		public static function isPHP(): bool
		{
			return \EarthAsylumConsulting\is_php_request();
		}
	} // trait
} // namespace

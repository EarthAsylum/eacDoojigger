<?php
namespace EarthAsylumConsulting;

/**
 * {eac}DoojiggerPluginUpdater class - Manage {eac}Doojigger, derivative and extension software updates
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Utilities
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * Version: 	1.0.1
 */

/*
 * Usage:
 *
 * In the plugin loader trait:
 *
 *		if (isset(self::$plugin_detail['AutoUpdate']))
 *		{
 *			\do_action('eacDoojigger_register_plugin_updater',self::$plugin_detail);
 *		}
 *
 * *
 *
 * For extension plugins, in the main plugin file:
 *
 * 		eacDoojigger::loadPluginUpdater(__FILE__,'self');
 *
 * *
 *
 * When installed by {eac}Doojigger, the following is used in the autoloader mu-plugin:
 *
 *		require_once WP_PLUGIN_DIR.'/eacDoojigger/EarthAsylumConsulting/Utilities/eacDoojiggerPluginUpdater.class.php';
 *		eacDoojiggerPluginUpdater::setPluginUpdates();
 *
 */

class eacDoojiggerPluginUpdater
{
	const REGISTER_OPTION_NAME 	= __NAMESPACE__.'_registered_plugins';


	/**
	 * setPluginUpdates.
	 * add actions and filters for plugin updates
	 *
	 * @return void
	 */
	public static function setPluginUpdates(): void
	{
		if (is_admin())
		{
			// triggered from plugin_loader trait to register for updates
			\add_action( 'eacDoojigger_register_plugin_updater',	[self::class, 'register_plugin_updater']);
			// when preparing for updates
			\add_filter( 'pre_set_site_transient_update_plugins',	[self::class, 'prepare_plugin_updater']);
			// when getting plugin info
			\add_filter( 'plugins_api_args',						[self::class, 'prepare_plugin_updater']);
		}
	}


	/**
	 * register_plugin_updater.
	 * eacDoojigger_register_plugin_updater action triggered from plugin_loader trait.
	 *
	 * @params array $plugin_detail from plugin_loader
	 * @return void
	 */
	public static function register_plugin_updater(array $plugin_detail): void
	{
		$registered_plugins = \get_site_option(self::REGISTER_OPTION_NAME,[]);

		$plugin_slug = plugin_basename($plugin_detail['PluginFile']);
		$className 	 = basename(str_replace('\\', '/', $plugin_detail['PluginClass']));

		/**
		 * filter {className}_plugin_update_parameters - filter plugin update parameters.
		 * required here because plugin may not be active or network activated when update runs.
		 * @param 	array parameters
		 * @return 	array parameters
		 */
		$plugin_detail['PluginUpdater'] = \apply_filters( $className.'_plugin_update_parameters',
			[
					'plugin_slug' 			=> $plugin_slug,
					'plugin_uri'			=> '',
					'plugin_options'		=> [],						// parameters added to uri
					'transient_name'		=> true,					// use transient
					'transient_time'		=> HOUR_IN_SECONDS,			// transient time
					'disableAutoUpdates' 	=> false,					// disable auto updating
					'requestTimeout'		=> 6,						// timeout
					'requestHeaders'		=> [],						// optional headers in request
					'requestSslVerify'		=> true,					// verify valid ssl cert
			]
		);

		if (!isset($registered_plugins[ $plugin_slug ]) || $plugin_detail !== $registered_plugins[ $plugin_slug ])
		{
			$registered_plugins[ $plugin_slug ] = $plugin_detail;
			\update_site_option(self::REGISTER_OPTION_NAME,$registered_plugins);
		}
	}


	/**
	 * prepare_plugin_updater.
	 * pre_site_transient_update_plugins & plugins_api_args
	 *
	 * @params object|array transient or args from calling filter
	 * @return object|array
	 */
	public static function prepare_plugin_updater($args)
	{
		static $once = false;
		if ($once) return $args;
		$once = true;

		$registered_plugins = \get_site_option(self::REGISTER_OPTION_NAME,[]);
		foreach ($registered_plugins as $plugin_slug => $plugin_detail)
		{
			// is plugin still installed (does not need to be active)
			if (file_exists($plugin_detail['PluginFile']))
			{
				self::trigger_plugin_updater($plugin_slug,$plugin_detail);
			}
			else
			{
				unset($registered_plugins[$plugin_slug]);
				\update_site_option(self::REGISTER_OPTION_NAME,$registered_plugins);
			}
		}
		return $args;
	}

	/**
	 * trigger_plugin_updater
	 *
	 * @params array $$plugin_slug from plugin_basename()
	 * @params array $plugin_detail from plugin_loader
	 * @return void
	 */
	public static function trigger_plugin_updater(string $plugin_slug, array $plugin_detail): void
	{
		static $updater = [];
		if (isset($updater[$plugin_slug])) return;

		$pluginFile = $plugin_detail['PluginFile'];
		$className  = $plugin_detail['PluginClass'];
		$updateType = strtolower($plugin_detail['AutoUpdate']);

		switch ($updateType)
		{
			case 'self':	// self-hosted updates
				new class($pluginFile,$className,$plugin_detail['PluginUpdater'])
				{
					use \EarthAsylumConsulting\Traits\plugin_update;
					public function __construct($pluginFile,$className,$plugin_info)
					{
						$pluginUri = get_file_data( $pluginFile, ['UpdateURI'=>'Update URI'], 'plugin' );
						$plugin_info['plugin_uri'] =  $pluginUri['UpdateURI'];
						$this->addPluginUpdateHooks($plugin_info,$className);
					}
				};
			break;
			case 'wp':		// WP-hosted updates (fixes upgrade_notice)
				new class($pluginFile,$className)
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
}

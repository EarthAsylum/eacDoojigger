<?php
namespace EarthAsylumConsulting\Traits;

/*
 * loader/initialization trait with environment check
 *
 * Usage:
 *
 * Within your loader class, after:
 * 		use \EarthAsylumConsulting\Traits\plugin_loader;
 * use this trait:
 *		use \EarthAsylumConsulting\Traits\plugin_environment
 *
 * Define $plugin_detail array...
 *
 *		protected $plugin_detail =
 *			[
 *				'PluginFile'		=> // the file path to this file (__FILE__)
 *				'NameSpace'			=> // the root namespace of our plugin class (__NAMESPACE__)
 *				'PluginClass'		=> // the full classname of our plugin (to instantiate)
 *				'TextDomain'		=> // optional, defaults to classname
 *				// if USEing plugin_environment trait
 *				'RequiresWP'		=> '5.5.0',	// check WordPress version
 *				'RequiresPHP'		=> '7.2',	// check PHP version
 *				'RequiresWC'		=> '6.8.0',	// check WooCommerce version
 *				'RequiresEAC'		=> '1.1.0',	// check {eac}Doojigger version
 *				'NetworkActivate'	=>	bool,	// require (or forbid) network activation
 *			];
 */

/**
 * Plugin Loader environment check trait - {eac}Doojigger for WordPress
 *
 * Add environment checks of PHP, WordPress, WooCommerce, and eacDoojigger versions
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

trait plugin_environment
{
	/**
	 * Check versions and give an error message if the user's version is less than the required version
	 *
	 * @return bool
	 */
	private static function check_loader_environment(): bool
	{
		/*
		 * Check PHP version
		 */
		if (isset(self::$plugin_detail['RequiresPHP']))		// check PHP version
		{
			if (! is_php_version_compatible(self::$plugin_detail['RequiresPHP']))
			{
				\add_action( 'all_admin_notices', function()
					{
						self::output_version_notice('PHP', self::$plugin_detail['RequiresPHP'], PHP_VERSION);
					}
				);
				return false;
			}
		}

		/*
		 * Check WordPress version
		 */
		if (isset(self::$plugin_detail['RequiresWP']))		// check WordPress version
		{
			if (! is_wp_version_compatible(self::$plugin_detail['RequiresWP']))
			{
				\add_action( 'all_admin_notices', function()
					{
						self::output_version_notice('WordPress', self::$plugin_detail['RequiresWP'], get_bloginfo('version'));
					}
				);
				return false;
			}
		}

		/*
		 * Check WooCommerce version
		 */
		if (isset(self::$plugin_detail['RequiresWC']))		// check WooCommerce version
		{
			\add_action('plugins_loaded', function()
				{
					if (! defined('WC_VERSION') || version_compare(WC_VERSION, self::$plugin_detail['RequiresWC'], '<') )
					{
						\add_action( 'all_admin_notices', function()
							{
								$wcVersion = defined('WC_VERSION') ? WC_VERSION : false;
								self::output_version_notice('WooCommerce', self::$plugin_detail['RequiresWC'], $wcVersion);
							}
						);
						return false;
					}
				}
			);
		}

		/*
		 * Check eacDoojigger version
		 */
		if (isset(self::$plugin_detail['RequiresEAC']))		// check eacDoojigger version
		{
			if (! defined('EACDOOJIGGER_VERSION') || version_compare(EACDOOJIGGER_VERSION, self::$plugin_detail['RequiresEAC'], '<') )
			{
				\add_action( 'all_admin_notices', function()
					{
						$eacVersion = defined('EACDOOJIGGER_VERSION') ? EACDOOJIGGER_VERSION : false;
						self::output_version_notice('{eac}Doojigger', self::$plugin_detail['RequiresEAC'], $eacVersion);
					}
				);
				return false;
			}
		}

		/*
		 * Check Network Activation
		 */
		if (is_multisite() && isset(self::$plugin_detail['NetworkActivate']))
		{
			register_activation_hook(self::$plugin_detail['PluginFile'], self::class.'::check_network_activation');
		}

		return true;
	}


	/**
	 * admin notice for module version
	 *
	 * @return void
	 */
	private static function output_version_notice(string $moduleName, string $required, string $version): void
	{
		$notice  = __("This plugin requires %s version %s or greater",self::$plugin_detail['TextDomain']);
		$notice .= ($version) ? ", ".__("your server is running version %s.",self::$plugin_detail['TextDomain']) : ".";

		echo  '<div class="notice notice-error"><h4>' .
			sprintf("Error from %s:<br>".$notice,
					plugin_basename(self::$plugin_detail['PluginFile']),
					$moduleName,
					$required,
					$version
			) . "</h4><p>Plugin deactivated.</p></div>";

		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		deactivate_plugins(plugin_basename(self::$plugin_detail['PluginFile']));
	}


	/**
	 * Check network activation
	 *
	 * @return bool
	 */
	public static function check_network_activation(): bool
	{
		if ( is_network_admin() )
		{
			if (self::$plugin_detail['NetworkActivate'] === false)
			{
				self::output_network_error(true);
			}
		}
		else
		{
			if (self::$plugin_detail['NetworkActivate'] === true)
			{
				self::output_network_error(false);
			}
		}
		// When WP does its "error scrape", it's no longer as network admin
		// so if we made it here and we're scraping, must be network activation failure
		if ($_REQUEST['action'] == 'error_scrape' &&
			$_REQUEST['plugin'] == plugin_basename(self::$plugin_detail['PluginFile']))
		{
			self::output_network_error(true);
		}

		return  true;
	}


	/**
	 * admin error for network activation
	 *
	 * @return void
	 */
	public static function output_network_error($isNetwork): void
	{
		$notice  = ($isNetwork)
			? __("This plugin is not intended to be activated or used by the network administrator.",self::$plugin_detail['TextDomain'])
			: __("This plugin must be network-activated by the network administrator.",self::$plugin_detail['TextDomain']);

		trigger_error(
				sprintf("from %s >> ".$notice, plugin_basename(self::$plugin_detail['PluginFile'])).
				'<div style="display:none;">', // swallows up the remaining PHP error "in {file} on line n"
				E_USER_ERROR
		);
	}
} // trait

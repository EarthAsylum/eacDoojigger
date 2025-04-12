<?php
namespace EarthAsylumConsulting\Traits;

/*
 * loader/initialization trait with environment check - before plugin class instantiation
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
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.earthasylum.com>
 * @version		25.0411.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

trait plugin_environment
{
	/**
	 * @var string transient name
	 */
	protected static $check_env_transient;

	/**
	 * Check versions and display an error notification if the user's version is less than the required version.
	 * Called from plugin_loader before plugin is instantiated.
	 *
	 * @param string $_slugName short plugin name
	 * @param string $_textDomain text domain
	 * @return bool
	 */
	protected static function check_plugin_environment($_slugName,$_textDomain): bool
	{
		self::$check_env_transient = "{$_slugName}_environment_check";

		// force check on activation
		register_activation_hook(
			self::$plugin_detail['PluginFile'],
			self::class.'::do_plugin_environment_check'
		);
		// force check when something is updated (maybe running the old version here)
		add_action(
			'upgrader_process_complete',
			function() {delete_site_transient(self::$check_env_transient);}
		);

		// check previously done, maybe output/deactivate on error
		if ($environment = get_site_transient(self::$check_env_transient))
		{
			if (isset($environment['error'])) {
				if (!wp_doing_ajax() && !wp_doing_cron()) {
					self::output_plugin_environment_check($environment['error'],$_textDomain);
					delete_site_transient(self::$check_env_transient);
				}
				return false;
			}
			return true;
		}

		// if no transient
		return self::do_plugin_environment_check();
	}


	/**
	 * admin notice for module version
	 *
	 * @param array $error $environment['error'] from transient
	 * @param string $_textDomain text domain
	 * @return void
	 */
	private static function output_plugin_environment_check(array $error, string $_textDomain): void
	{
		if (isset($error['module']))
		{
			$notice  = "This plugin requires %s version %s or greater";
			$notice .= ($error['module']['current'])
				? ", "."your server is running version %s." : ".";
		}
		else if (isset($error['network']))
		{
			$notice  = ($error['network']['active'])
				? "This plugin is not intended to be activated or used by the network administrator."
				: "This plugin must be network-activated by the network administrator.";
		}

		\add_action( 'all_admin_notices', function() use($error,$notice,$_textDomain)
			{
				$notice  = sprintf(__("Error from %s:",$_textDomain),
								basename(dirname(self::$plugin_detail['PluginFile']))
							) . "<br>" .
						   sprintf(__($notice,$_textDomain),
						   		$error['module']['name'],
						   		$error['module']['required'],
						   		$error['module']['current']
						   	);
				add_action( 'admin_footer', function() use ($notice)
					{
						echo '<div class="notice notice-error"><p><strong>' .
							 $notice  .
							 "</strong></p><p>Plugin deactivated.</p></div>";
					}
				);
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				deactivate_plugins(plugin_basename(self::$plugin_detail['PluginFile']));
			},
			PHP_INT_MAX
		);
	}


	/**
	 * The actual checks triggered by above hooks.
	 * Check versions and display an error notification if the user's version is less than the required version.
	 *
	 * @return bool
	 */
	public static function do_plugin_environment_check(): bool
	{
		/*
		 * Check PHP version
		 */
		if (isset(self::$plugin_detail['RequiresPHP']))
		{
			if (! is_php_version_compatible(self::$plugin_detail['RequiresPHP']))
			{
				self::set_plugin_environment_version_error(
					'PHP',
					self::$plugin_detail['RequiresPHP'],
					PHP_VERSION
				);
				return false;
			}
		}

		/*
		 * Check WordPress version
		 */
		if (isset(self::$plugin_detail['RequiresWP']))
		{
			if (! is_wp_version_compatible(self::$plugin_detail['RequiresWP']))
			{
				self::set_plugin_environment_version_error(
					'WordPress',
					self::$plugin_detail['RequiresWP'],
					get_bloginfo('version')
				);
				return false;
			}
		}

		/*
		 * Check WooCommerce version
		 */
		if (isset(self::$plugin_detail['RequiresWC']))
		{
			if (! defined('WC_VERSION') || version_compare(WC_VERSION, self::$plugin_detail['RequiresWC'], '<') )
			{
				$wcVersion = defined('WC_VERSION') ? WC_VERSION : false;
				self::set_plugin_environment_version_error(
					'WooCommerce',
					self::$plugin_detail['RequiresWC'],
					$wcVersion
				);
				return false;
			}
		}

		/*
		 * Check eacDoojigger version
		 */
		if (isset(self::$plugin_detail['RequiresEAC']))
		{
			if (! defined('EACDOOJIGGER_VERSION') || version_compare(EACDOOJIGGER_VERSION, self::$plugin_detail['RequiresEAC'], '<') )
			{
				$eacVersion = defined('EACDOOJIGGER_VERSION') ? EACDOOJIGGER_VERSION : false;
				self::set_plugin_environment_version_error(
					'{eac}Doojigger',
					self::$plugin_detail['RequiresEAC'],
					$eacVersion
				);
				return false;
			}
		}

		/**
		 * Check network activation
		 */
		if (is_multisite() && isset(self::$plugin_detail['NetworkActivate']))
		{
			if ( is_network_admin() )
			{
				if (self::$plugin_detail['NetworkActivate'] === false)
				{
					self::set_plugin_environment_network_error(true,false);
					return false;
				}
			}
			else
			{
				if (self::$plugin_detail['NetworkActivate'] === true)
				{
					self::set_plugin_environment_network_error(false,true);
					return false;
				}
			}
		}

		set_site_transient(self::$check_env_transient,['check' => true],DAY_IN_SECONDS);
		return true;
	}


	/**
	 * save environmen_check_transient on error
	 *
	 * @param string $moduleName the software name
	 * @param string $required	the required version
	 * @param string $version the current version
	 * @return void
	 */
	private static function set_plugin_environment_version_error(string $moduleName, string $required, string $version): void
	{
		set_site_transient(self::$check_env_transient,[
			'error'	=> [
				'module' => [
					'name' 		=> $moduleName,
					'required' 	=> $required,
					'current' 	=> $version
				]
			]
		],HOUR_IN_SECONDS);
	}


	/**
	 * admin error for network activation
	 *
	 * @param bool $isNetwork activated by network admin
	 * @param bool $required network activation required
	 * @return bool
	 */
	private static function set_plugin_environment_network_error(bool $isNetwork, bool $required): bool
	{
		set_site_transient(self::$check_env_transient,[
			'error'	=> [
				'network' => [
					'active'	=> $isNetwork,
					'required'	=> $required
				]
			]
		],HOUR_IN_SECONDS);
	}
} // trait

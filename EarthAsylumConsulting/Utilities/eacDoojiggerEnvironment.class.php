<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger Environment - {eac}Doojigger for WordPress,
 * Set WP_ENVIRONMENT_TYPE used by wp_get_environment_type()
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger Utilities\{eac}Doojigger_environment
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 * @link		https://eacDoojigger.earthasylum.com/
 *
 * @wordpress-plugin
 * Plugin Name: 		{eac}Doojigger Environment
 * Description:			Environment Switcher - set WP_ENVIRONMENT_TYPE from the network or general settings page.
 * Version:				1.0.8
 * Requires at least:	5.5.0
 * Tested up to: 		6.4
 * Requires PHP:		7.2
 * Author:				EarthAsylum Consulting
 * Author URI:			http://www.earthasylum.com
 */

class eacDoojiggerEnvironment
{
	/**
	 * @var string option name added to 'General' or 'Network' settings to set WP_ENVIRONMENT_TYPE
	 * see register_wp_environment_field & wpmu_wp_environment_field
	 */
 	const OPTION_WP_ENVIRONMENT = 'eacDoojigger_wp_environment';

	/**
	 * @var string option name added to 'General' or 'Network' settings to set WP_DEVELOPMENT_MODE
	 * see register_wp_environment_field & wpmu_wp_environment_field
	 */
 	//const OPTION_WP_DEVELOPMENT = 'eacDoojigger_wp_development';


	/**
	 * set wp_environment, custom option, robots, and add network/general settings field
	 *
	 * WP_DEBUG must be defined before loading wp-settings.php
	 * otherwise wp_get_environment_type() is set (static variable)
	 *
	 * @return void
	 */
	public static function setEnvironment(): void
	{
		if (! defined( 'WP_DEBUG' )) return;

		// set WP_ENVIRONMENT_TYPE
		if (! defined( 'WP_ENVIRONMENT_TYPE' ))
		{
			if ($env = (is_network_admin())
				? \get_site_option(self::OPTION_WP_ENVIRONMENT)
				: \get_option(self::OPTION_WP_ENVIRONMENT))
			{
				define( 'WP_ENVIRONMENT_TYPE', $env);
			}
		}
		else if (is_admin())
		{
			$env = (is_network_admin())
				? \update_site_option(self::OPTION_WP_ENVIRONMENT,WP_ENVIRONMENT_TYPE)
				: \update_option(self::OPTION_WP_ENVIRONMENT,WP_ENVIRONMENT_TYPE);
		}

		// set WP_DEVELOPMENT_MODE
/*
		if (! defined( 'WP_DEVELOPMENT_MODE' ))
		{
			if ($dev = (is_network_admin())
				? \get_site_option(self::OPTION_WP_DEVELOPMENT)
				: \get_option(self::OPTION_WP_DEVELOPMENT))
			{
				if (WP_ENVIRONMENT_TYPE == 'development')
				{
					define( 'WP_DEVELOPMENT_MODE', $dev);
				}
			}
		}
		else if (is_admin())
		{
			$dev = (is_network_admin())
				? \update_site_option(self::OPTION_WP_DEVELOPMENT,WP_DEVELOPMENT_MODE)
				: \update_option(self::OPTION_WP_DEVELOPMENT,WP_DEVELOPMENT_MODE);
		}
*/

		// for non-production sites, block/discourage search engines
		if ( wp_get_environment_type() != 'production' )
		{
			add_filter( 'robots_txt', function()
			{
			    return 	'User-agent: *' . PHP_EOL.
			    		'Disallow: /';
			}, PHP_INT_MAX );
			add_filter( 'pre_option_blog_public', '__return_false', PHP_INT_MAX );
		}

		// add actions for environment field on settings page
		if (is_admin())
		{
			self::adminSetEnvironment();
		}
	}

	/**
	 * Administrator function
	 */
	private static function adminSetEnvironment(): void
	{
		if (is_network_admin())
		{
			// add WP_ENVIRONMENT field to 'Network Settings page
			add_action( 'wpmu_options', 			array( self::class, 'wpmu_wp_environment_field') );
			// post WP_ENVIRONMENT field from network settings page
			add_action( 'update_wpmu_options', 		array( self::class, 'wpmu_wp_environment_field') );
		}
		else
		{
			// add WP_ENVIRONMENT field to 'General' settings
			add_action( 'current_screen',			array( self::class, 'register_wp_environment_field') );
			// post WP_ENVIRONMENT field from 'General' settings
			add_action( 'admin_init',				array( self::class, 'update_wp_environment_field') );
		}

		//  show environment in the admin bar
		add_action( 'admin_bar_menu', function($admin_bar)
		{
			// show environment on the menu bar
			$title = ucwords(__(wp_get_environment_type()));
			$dev   = defined('WP_DEVELOPMENT_MODE') ? ucwords(__(WP_DEVELOPMENT_MODE)) : '';
			$meta  = __('Set WordPress Environment').
			  "\n\t" . __('Environment Type').': '.$title.
			  "\n\t" . __('Development Mode').': '.(!empty($dev) ? $dev : '(not set)').
			  "\n\t" . __('WP Debugging')    .': '.((defined('WP_DEBUG') && WP_DEBUG) ? __('On') : __('Off') );

			if ($admin_bar->get_node('eacDoojigger')) {
				$parent = 'eacDoojigger-environment-switch';
				$title 	= 'Set WP Environment';
				$admin_bar->add_group(['id' => $parent, 'parent' => 'eacDoojigger',]);
			} else {
				$parent = 'top-secondary';
				$title = '<span>&langd;'.$title[0].'&rangd;</span>';
			}
			$admin_bar->add_menu(
				[
					'id' 		=> 'eacDoojigger-set-environment',
					'parent' 	=> $parent,
					'title' 	=> $title,
					'href'  	=> (is_network_admin())
									? network_admin_url('settings.php').'#wp-environment'
									: admin_url('options-general.php').'#wp-environment',
					'meta' 		=> ['title' => $meta]
				]
			);

			// add contextual help tab to options page
			if ($screen = self::get_options_screen())
			{
				$content = sprintf(
					__("
					The <em>WP Environment</em> field is added by %s via the <em>Environment Switcher</em> utility.

					This defines the %s constant (if not set in %s), allowing the administrator to set
					the environment to either 'Production', 'Staging', 'Development', or 'Local'.

					The WP_DEVELOPMENT_MODE constant must be set (to one of 'core', 'plugin', 'theme', or 'all') in %s.
					"),
					'<em>{eac}Doojigger</em>','<code>WP_ENVIRONMENT_TYPE</code>','wp-config.php','wp-config.php'
				);
				$screen->add_help_tab(
					[
						'id'      => 'eac-wp-environment',
						'title'   => 'WP Environment',
						'content' => wp_kses_post( wpautop($content,false) ),
					]
				);
			}
		}, PHP_INT_MAX );
	}


	/**
	 * get screen if on the right page
	 *
	 * @return	void
	 */
	private static function get_options_screen()
	{
		$screen = get_current_screen();
		return (in_array($screen->id,['options-general','settings-network'])) ? $screen : false;
	}


	/**
	 * add the environment select field to general settings page
	 *
	 * @return	void
	 */
	public static function register_wp_environment_field(): void
	{
		if (! ($screen = self::get_options_screen()) ) return;

		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		require_once(ABSPATH . 'wp-admin/includes/template.php');

		register_setting( 'general', self::OPTION_WP_ENVIRONMENT, ['default' => wp_get_environment_type()] );

		add_settings_field
		(
			self::OPTION_WP_ENVIRONMENT,
			__('WP Environment'),
			array(self::class,'add_wp_environment_field'),
			'general',
			'default',
			array(
				'name'		=> self::OPTION_WP_ENVIRONMENT,
				'value'		=> \get_option(self::OPTION_WP_ENVIRONMENT),
				'label_for' => self::OPTION_WP_ENVIRONMENT
			)
		);

/*
		register_setting( 'general', self::OPTION_WP_DEVELOPMENT, ['default' => wp_get_development_mode()] );

		add_settings_field
		(
			self::OPTION_WP_DEVELOPMENT,
			__('WP Development'),
			array(self::class,'add_wp_development_field'),
			'general',
			'default',
			array(
				'name'		=> self::OPTION_WP_DEVELOPMENT,
				'value'		=> \get_option(self::OPTION_WP_DEVELOPMENT),
				'label_for' => self::OPTION_WP_DEVELOPMENT
			)
		);
*/
	}


	/**
	 * save the environment select field to general settings page
	 *
	 * @return	void
	 */
	public static function update_wp_environment_field(): void
	{
		if (isset($_POST) && isset($_POST[self::OPTION_WP_ENVIRONMENT]))
		{
			\update_option(self::OPTION_WP_ENVIRONMENT, sanitize_text_field($_POST[self::OPTION_WP_ENVIRONMENT]));
			return;
		}
	}


	/**
	 * add the environment select field to network settings page
	 *
	 * @return	void
	 */
	public static function wpmu_wp_environment_field(): void
	{
		if (! ($screen = self::get_options_screen()) ) return;

		if (isset($_POST) && isset($_POST[self::OPTION_WP_ENVIRONMENT]))
		{
			\update_site_option(self::OPTION_WP_ENVIRONMENT, sanitize_text_field($_POST[self::OPTION_WP_ENVIRONMENT]));
			return;
		}
		?>
			<h2><?php _e( 'Environment Settings' ); ?></h2>
			<table id="environment" class="form-table">
				<tr>
					<th scope="row"><?php _e( 'WP Environment' ); ?></th>
					<td>
		<?php
			self::add_wp_environment_field([
				'name'		=> self::OPTION_WP_ENVIRONMENT,
				'value'		=> \get_site_option(self::OPTION_WP_ENVIRONMENT,wp_get_environment_type()),
			]);
		?>
					</td>
				</tr>
			</table>
		<?php
	}


	/**
	 * add the environment select field html
	 *
	 * @return	void
	 */
	public static function add_wp_environment_field($options): void
	{
		$environments =	[
			'production',
			'staging',
			'development',
			'local',
		];
		echo "<select name='".$options['name']."' id='wp-environment'>";
		foreach ($environments as $name)
		{
			echo "<option value='{$name}'";
			if ($name == $options['value']) echo " selected";
			echo ">".__(ucfirst($name))."</option>";
		}
		echo "</select>";
	}


	/**
	 * add the development select field html
	 *
	 * @return	void
	 */
/*
	public static function add_wp_development_field($options): void
	{
		$environments =	[
			'core',
			'plugin',
			'theme',
			'all',
		];
		echo "<select name='".$options['name']."' id='wp-development'>";
		foreach ($environments as $name)
		{
			echo "<option value='{$name}'";
			if ($name == $options['value']) echo " selected";
			echo ">".__(ucfirst($name))."</option>";
		}
		echo "</select>->{$options['value']}";
	}
*/
}

eacDoojiggerEnvironment::setEnvironment();

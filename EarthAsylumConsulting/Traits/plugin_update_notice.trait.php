<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Plugin updater notice trait - {eac}Doojigger for WordPress
 *
 * Handles plugin upgrade notice from the plugins page in WordPress for self-or-WP hosted plugins
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		abstract_backend.class.php
 */

/*
 * Usage:
 *
 * Use this trait in your class file...
 *
 *		use \EarthAsylumConsulting\Traits\plugin_update_notice;
 *
 * Add the updater hooks in your __constructor method...
 *
 *		$this->addPluginUpdateNotice($plugin_slug);
 *
 * Note: for WordPress to pass 'upgrade_notice' from your readme.txt file,
 * the upgrade notice section must be versioned with = version = matching the stable_tag version
 *
 * == Upgrade Notice ==
 *
 * = M.m.p =
 * your upgrade notice here
 */


trait plugin_update_notice
{
	/**
	 * plugin update notice - call from class constructor
	 *
	 * @param	string $plugin_slug (required) 'directory/pluginfile.php' - plugin_basename( __FILE__ )
	 * @param	string $className the self::class name of the loading plugin class.
	 * @return	void
	 */
	protected function addPluginUpdateNotice(string $plugin_slug, string $className=''): void
	{
		if (empty($plugin_slug))		// we must know the slug name to do anything
		{
			return;
		}

		/*
		 * add admin hooks for upgrade notice
		 * display notice on plugins page when upgrade available
		 */
		if ( ! has_action( "in_plugin_update_message-{$plugin_slug}") )
		{
			add_action( "in_plugin_update_message-{$plugin_slug}", array($this, 'plugin_admin_upgrade_notice'), 10, 2 );
		}
	}


	/**
	 * display notice on plugins page when upgrade available
	 *
	 * @param	array 	$plugin plugin data
	 * @return	void
	 */
	public function plugin_admin_upgrade_notice( $plugin, $response )
	{
		$upgrade_notice = $plugin['upgrade_notice']
						?? $response->upgrade_notice
						?? $response->sections->upgrade_notice
						?? null;
		if ( $upgrade_notice )
		{
			printf( '</p>'. // inserted inside an opening <p>aragraph
					'<div class="upgrade-message notice inline">%s</div>'.
					'<p style="display:none">', // swallow up closing paragraph (</p>)
					wpautop( $upgrade_notice )
			);
		}
	}
}

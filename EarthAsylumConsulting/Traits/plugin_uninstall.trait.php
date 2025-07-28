<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Custom Plugin uninstaller trait - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		25.0728.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

trait plugin_uninstall
{
	/**
	 * prefix is plugin classname
	 */
	private static $className;

	/**
	 * Get the prefixed version input $name suitable for storing in WP options
	 */
	private static function prefixName($name)
	{
		return self::$className . '_' . trim($name);
	}

	/**
	 * get uninstall options
	 */
	public static function getUninstallOptions($network = false)
	{
		$options = ($network)
			? \get_network_option(null,self::prefixName('uninstall_options'))
			: \get_option(self::prefixName('uninstall_options'));

		// if option not set, default to all
		if ($options === false)
		{
			$options = ['tables','options','transients'];
		}
		return $options;
	}

	/**
	 * Remove scheduled events
	 */
	private static function removeScheduledEvents()
	{
		foreach ( ['hourly_event','daily_event','weekly_event','registry_refresh'] as $eventName)
		{
			$eventName = self::prefixName($eventName);
			wp_unschedule_hook($eventName);
		}
	}

	/**
	 * drop custom tables
	 */
	private static function dropCustomTables()
	{
		global $wpdb;
		$prefix	= $wpdb->prefix . strtolower(self::prefixName('%'));

		$tables = $wpdb->get_col("SHOW TABLES LIKE '{$prefix}'");
		foreach ($tables as $tableName)
		{
			$wpdb->query("DROP TABLE IF EXISTS {$tableName}");
		}
		//error_log( self::$className.' '.__METHOD__ );
	}

	/**
	 * delete site options
	 */
	private static function deleteSiteOptions()
	{
		global $wpdb;
		$prefix = self::prefixName('%');

		$wpdb->query( "DELETE FROM {$wpdb->options} ".
					  "WHERE `option_name` LIKE '{$prefix}'"
		);
	    try {
			$table = $wpdb->get_blog_prefix() . 'eac_key_value';
			$wpdb->query( "DELETE FROM {$table} ".
					  "WHERE `key` LIKE '{$prefix}' AND `expires` = '0000-00-00 00:00:00'"
			);
		} catch (\Throwable $e) {}
		//error_log( self::$className.' '.__METHOD__ );
	}

	/**
	 * delete site transients
	 */
	private static function deleteSiteTransients()
	{
		global $wpdb;
		$prefix = strtolower(self::prefixName('%'));

		$wpdb->query( "DELETE FROM {$wpdb->options} ".
					"WHERE `option_name` LIKE '%_transient_{$prefix}'"
		);
		$wpdb->query( "DELETE FROM {$wpdb->options} ".
					"WHERE `option_name` LIKE '%_transient_timeout_{$prefix}'"
		);
	    try {
			$table = $wpdb->get_blog_prefix() . 'eac_key_value';
			$wpdb->query( "DELETE FROM {$table} ".
						  "WHERE `key` LIKE '{$prefix}' AND `expires` <> '0000-00-00 00:00:00'"
			);
		} catch (\Throwable $e) {}
		//error_log( self::$className.' '.__METHOD__ );
	}

	/**
	 * delete network options
	 */
	private static function deleteNetworkOptions()
	{
		global $wpdb;
		$prefix = self::prefixName('%');

		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} ".
					"WHERE `meta_key` LIKE '{$prefix}'"
		);
	    try {
			$table = $wpdb->get_blog_prefix() . 'eac_key_value_site';
			$wpdb->query( "DELETE FROM {$table} ".
					  "WHERE `key` LIKE '{$prefix}' AND `expires` = '0000-00-00 00:00:00'"
			);
		} catch (\Throwable $e) {}
		//error_log( self::$className.' '.__METHOD__ );
	}

	/**
	 * delete network transients
	 */
	private static function deleteNetworkTransients()
	{
		global $wpdb;
		$prefix = strtolower(self::prefixName('%'));

		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} ".
					"WHERE `meta_key` LIKE '%_transient_{$prefix}'"
		);
		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} ".
					"WHERE `meta_key` LIKE '%_transient_timeout_{$prefix}'"
		);
	    try {
			$table = $wpdb->get_blog_prefix() . 'eac_key_value_site';
			$wpdb->query( "DELETE FROM {$table} ".
						  "WHERE `key` LIKE '{$prefix}' AND `expires` <> '0000-00-00 00:00:00'"
			);
		} catch (\Throwable $e) {}
		//error_log( self::$className.' '.__METHOD__ );
	}

	/**
	 * uninstall single site
	 */
	public static function uninstallSite()
	{
		error_log( self::$className.' uninstall site - '.\get_option('blogname') );

		$options = self::getUninstallOptions();

		self::removeScheduledEvents();
		if (in_array('tables', $options))		self::dropCustomTables();
		if (in_array('options', $options))		self::deleteSiteOptions();
		if (in_array('transients', $options))	self::deleteSiteTransients();
	}

	/**
	 * uninstall network
	 */
	public static function uninstallNetwork()
	{
		error_log( self::$className.' uninstall network - '.\get_network_option(null,'site_name') );

		$options = self::getUninstallOptions(true);

		self::removeScheduledEvents();
		if (in_array('options', $options))		self::deleteNetworkOptions();
		if (in_array('transients', $options))	self::deleteNetworkTransients();
	}

	/**
	 * uninstall
	 *
	 * @param string $className the short (sans namespace) class name of the plugin being uninstalled
	 * @return void
	 */
	public static function uninstall(string $className = null)
	{
		self::$className = $className ?: basename(str_replace('\\', '/', self::class));

		if ( is_multisite() )	// network admin, multi site
		{
		    $sites = get_sites();
			foreach ( $sites as $site )
			{
				switch_to_blog( $site->blog_id );
				self::uninstallSite();
				restore_current_blog();
			}
			self::uninstallNetwork();
		}
		else 					// single site
		{
			self::uninstallSite();
		}
	}
}

<?php
namespace EarthAsylumConsulting\Traits;

/**
 * standard options trait - {eac}Doojigger for WordPress
 *
 * Add standard options when registering a plugin or extension.
 * siteEnvironment, adminSettingsMenu, UninstallOptions, emailFatalNotice, backupOptions, restoreOptions, backupNetwork, restoreNetwork, clearCache, networkCache, networkActive
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

if ( !is_admin() )
{
	trait standard_options {}
}
else require_once "standard_options.admin.php";

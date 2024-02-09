<?php
namespace EarthAsylumConsulting\uninstall;

/**
 * Plugin uninstaller - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2021 EarthAsylum Consulting <www.earthasylum.com>
 * @version		1.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

defined( 'WP_UNINSTALL_PLUGIN' ) or exit;

if (!trait_exists('\\EarthAsylumConsulting\\Traits\\plugin_uninstall'))
{
	require __NAMESPACE__.'/Traits/plugin_uninstall.trait.php';
}

/**
 * Uninstaller class using plugin_uninstall
 */
class eacDoojigger
{
	use \EarthAsylumConsulting\Traits\plugin_uninstall;
}
eacDoojigger::uninstall();

if (file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php')) {
	unlink(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php');
}

if (file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerEnvironment.php')) {
	unlink(WPMU_PLUGIN_DIR.'/eacDoojiggerEnvironment.php');
}

if (file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerActionTimer.php')) {
	unlink(WPMU_PLUGIN_DIR.'/eacDoojiggerActionTimer.php');
}

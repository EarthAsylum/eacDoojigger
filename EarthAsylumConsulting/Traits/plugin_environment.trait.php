<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Plugin Loader environment check trait - {eac}Doojigger for WordPress
 *
 * Add environment checks of PHP, WordPress, WooCommerce, and eacDoojigger versions
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @ignore
 */

if ( ! is_admin() )
{
	trait plugin_environment {}
}
else require "plugin_environment.admin.php";


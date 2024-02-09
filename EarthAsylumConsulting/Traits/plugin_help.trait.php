<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Plugin help trait - {eac}Doojigger for WordPress
 *
 * Add help content to plugin administration screens
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/contextual-help/
 * @used-by		abstract_backend.class.php
 */

if ( !is_admin() )
{
	trait plugin_help {}
}
else require_once "plugin_help.admin.php";

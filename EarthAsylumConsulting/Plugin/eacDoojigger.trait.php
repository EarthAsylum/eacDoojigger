<?php
namespace EarthAsylumConsulting\Plugin;

/**
 * Primary plugin file - {eac}Doojigger for WordPress
 *
 * load administrator traits
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @ignore
 */

if ( ! is_admin() )
{
	trait eacDoojigger_admin_traits {}
}
else require "eacDoojigger.admin.php";


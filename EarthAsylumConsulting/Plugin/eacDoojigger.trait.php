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
 */

if ( ! is_admin() )
{
	trait eacDoojigger_administration {}
}
else require "eacDoojigger.admin.php";


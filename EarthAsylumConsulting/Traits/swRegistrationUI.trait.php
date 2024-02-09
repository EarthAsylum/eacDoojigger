<?php
namespace EarthAsylumConsulting\Traits;

/**
 * software registration UI for use with {eac}SoftwareRegistry - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		2.x
 */

if ( !is_admin() )
{
	trait swRegistrationUI {}
}
else require_once "swRegistrationUI.admin.php";

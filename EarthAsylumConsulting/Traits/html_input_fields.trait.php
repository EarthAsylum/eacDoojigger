<?php
namespace EarthAsylumConsulting\Traits;

/**
 * html input field methods - {eac}Doojigger for WordPress
 *
 * Adds html fields with html display, post processing, sanitization and validation.
 * Used by admin outside of options/settings page. Uses private settings_options_ methods.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

if ( !is_admin() )
{
	trait html_input_fields {}
}
else require_once "html_input_fields.admin.php";

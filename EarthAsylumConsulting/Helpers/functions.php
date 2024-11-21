<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger functions - Common utility functions
 *
 * @category 	WordPress Plugin
 * @package 	{eac}Doojigger\Helpers\Functions
 * @author 		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright 	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.1121.1
 */

/*
 * Included from eacDoojiggerAutoloader.php mu-plugin -> autoload.php.
 */


/**
 * function: \EarthAsylumConsulting\is_request_type().
 * check request uri for file type
 * @param string $type - the file type ('php')
 */
function is_request_type(string $type='php'): bool
{
	if (array_key_exists('REQUEST_URI', $_SERVER))
	{
		$ext = explode('?',$_SERVER['REQUEST_URI']);
		$ext = pathinfo(trim($ext[0],'/'),PATHINFO_EXTENSION);
		if (!empty($ext) && $ext != $type) return false;
	}
	return true;
}


/**
 * function: \EarthAsylumConsulting\is_php_request().
 * check request uri for php script
 */
function is_php_request(): bool
{
	return is_request_type('php');
}


/**
 * function: \EarthAsylumConsulting\is_admin_request().
 * admin-ajax & admin-post request always return is_admin() == true
 * are we calling for the frontend or backend?
 */
function is_admin_request(): bool
{
	static $is_admin = null;

	if (!is_bool($is_admin))
	{
		if (wp_doing_ajax() || (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/admin-post.php') !== false))
		{
			$is_admin = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], admin_url()) !== false);
		}
		else
		{
			$is_admin = \is_admin();
		}
	}
	return $is_admin;
}


/**
 * function: \EarthAsylumConsulting\is_network_admin_request().
 * admin-ajax & admin-post request always return is_network_admin() == false
 * are we calling for network admin?
 */
function is_network_admin_request(): bool
{
	static $is_network_admin = null;

	if (!is_bool($is_network_admin))
	{
		if (! is_multisite())
		{
			$is_network_admin = false;
		}
		else if (wp_doing_ajax() || (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/admin-post.php') !== false))
		{
			$is_network_admin = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], network_admin_url()) !== false);
		}
		else
		{
			$is_network_admin = \is_network_admin();
		}
	}
	return $is_network_admin;
}

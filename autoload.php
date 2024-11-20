<?php
namespace EarthAsylumConsulting;

/**
 * {eac}DoojiggerAutoloader - Autoloader for {eac}Doojigger and derivatives
 *
 * @category 	WordPress Plugin
 * @package 	{eac}Doojigger\Utilities\{eac}DoojiggerAutoloader
 * @author 		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright 	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.0904.1
 */

/*
 * Included from eacDoojiggerAutoloader.php mu-plugin.
 * Maybe included from eacDoojigger.php if autoloader is missing (not installed).
 * Maybe included from eacDoojiggerAutoloader.class.php if eacDoojiggerAutoloader.php is outdated.
 */


/**
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
 * check request uri for php script
 */
function is_php_request(): bool
{
	return is_request_type('php');
}


/**
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
			$is_admin = is_admin();
		}
	}
	return $is_admin;
}


/**
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
			$is_network_admin = (is_multisite() && is_network_admin());
		}
	}
	return $is_network_admin;
}


// should be defined in eacDoojiggerAutoloader.php mu-plugin
if (! defined('EACDOOJIGGER_HOME') ) define( 'EACDOOJIGGER_HOME', __DIR__ );

// ftp credentials used by file system utility
require_once __NAMESPACE__.'/Utilities/eacDoojigger_ftp_credentials.class.php';
eacDoojigger_ftp_credentials::addFilters();

// plugin automatic updater utility
require_once __NAMESPACE__.'/Utilities/eacDoojiggerPluginUpdater.class.php';
eacDoojiggerPluginUpdater::setPluginUpdates();

// autoloader for this (and additional) namespaces
require_once __NAMESPACE__.'/Utilities/eacDoojiggerAutoloader.class.php';
eacDoojiggerAutoloader::setAutoLoader();
// autoload PSR specific to PHP version (PHPv7=PSR-3v1, PHPv8=PSR-3v3)
eacDoojiggerAutoloader::addNamespace('Psr\Log',
	__DIR__.'/'.__NAMESPACE__.'/Helpers/vendor/Psr/Log/PHPv'.PHP_MAJOR_VERSION
);

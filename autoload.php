<?php
namespace EarthAsylumConsulting;

/**
 * {eac}DoojiggerAutoloader - Autoloader for {eac}Doojigger and derivatives
 *
 * @category 	WordPress Plugin
 * @package 	{eac}Doojigger\Utilities\{eac}DoojiggerAutoloader
 * @author 		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright 	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.0903.1
 */

// should be defined in eacDoojiggerAutoloader.php mu-plugin
if (! defined('EACDOOJIGGER_HOME') ) define( 'EACDOOJIGGER_HOME', dirname(__DIR__) );

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

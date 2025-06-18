<?php
namespace EarthAsylumConsulting;
defined( 'ABSPATH' ) or exit;

/**
 * {eac}DoojiggerAutoloader - Autoloader for {eac}Doojigger and derivatives
 *
 * @category 	WordPress Plugin
 * @package 	{eac}Doojigger\Utilities\{eac}DoojiggerAutoloader
 * @author 		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright 	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	25.0603.1
 */

/*
 * Included from eacDoojiggerAutoloader.php mu-plugin.
 * Maybe included from eacDoojigger.php if autoloader is missing (not installed).
 * Maybe included from eacDoojiggerAutoloader.class.php if eacDoojiggerAutoloader.php is outdated.
 */

// Set the permission constants if not already set. WP does this only with WP_Filesystem (admin)
if ( ! defined( 'FS_CHMOD_DIR' ) ) {
	define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
}
if ( ! defined( 'FS_CHMOD_FILE' ) ) {
	define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
}

// common functions
if (! class_exists('\EarthAsylumConsulting\eacKeyValue')) {
	require_once __NAMESPACE__.'/Helpers/eacKeyValue.php';
}
require_once __NAMESPACE__.'/Helpers/functions.php';

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

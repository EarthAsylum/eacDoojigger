<?php
namespace EarthAsylumConsulting;

/**
 * {eac}DoojiggerAutoloader class - {eac}Doojigger for WordPress,
 * Autoloader class for EarthAsylum Consulting {eac} Doojigger for WordPress classes and traits
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Utilities\{eac}DoojiggerAutoloader
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * Version: 	24.0903.1
 * @link		https://eacDoojigger.earthasylum.com/
 */

/*
 * Usage:
 *
 * This autoloader class ('\EarthAsylumConsulting\eacDoojiggerAutoloader') should be loaded
 * by the installed `eacDoojiggerAutoloader.php` mu-plugin and the eacDoojigger 'autoload.php'.
 *
 * eacDoojiggerAutoloader::setAutoLoader( $namespace, '/path/to/namespace/folder' );
 * - replaces $namespace with '/path/to/namespace/folder' when building file pathname from namespace name.
 *
 * Examples:
 *
 * eacDoojiggerAutoloader::setAutoLoader( 'MyNamespace', __DIR__.'/MyNamespace' );
 * use MyNamespace\Traits\MyTrait 	__DIR__/MyNamespace/Traits/MyTrait.trait.php
 * 								or	__DIR__/MyNamespace/Traits/class.MyTrait.trait.php
 * 								or	__DIR__/MyNamespace/Traits/MyTrait.class.php
 * 								or	__DIR__/MyNamespace/Traits/class.MyTrait.php
 * 								or	__DIR__/MyNamespace/Traits/MyTrait.php
 * eacDoojiggerAutoloader::setAutoLoader( 'Psr\Log', __DIR__.'/Helpers/vendor/Psr/Log' );
 * use Psr\Log\LoggerTrait 			__DIR__/Helpers/vendor/Psr/Log/LoggerTrait.php
 *
 *
 * Giving the base namespace name and the root directory for that namespace...
 *
 * 		Set the autoloader with something like this,
 * 		(assuming the namespace folder is a sub-directory in the plugin folder)
 *
 *		eacDoojiggerAutoloader::setAutoLoader( $_namespace, dirname($_pluginfile).DIRECTORY_SEPARATOR.$_namespace );
 *
 * 		Additional namespace(s) may be added with:
 *
 *		eacDoojiggerAutoloader::addNamespace( $_namespace, $_directory );
 *
 * The default 'EarthAsylumConsulting' namespace/directory is set on the first call to setAutoLoader().
 *
 * The plugin loader trait (for eacDoojigger and derivatives) also sets:
 *
 * 		eacDoojiggerAutoloader::setAutoLoader($_namespace, $_directory);
 *
 *
 * AUTOLOAD_TYPES provides a sub-namespace name to file suffix map...
 *
 *		...\namespace\<sub-ns>\<classname>'		= 	<classname>.<suffix>.php
 * 												or 	class.<classname>.<suffix>.php
 */

class eacDoojiggerAutoloader
{
	/**
	 * @var array map object namespace to file type
	 */
	const AUTOLOAD_TYPES = [
		// namespace 		= file suffix (maybe)
		'Plugin'			=> 'plugin',
		'Plugins'			=> 'plugin',
		'Extensions'		=> 'extension',
		'Helpers'			=> 'helper',
		'Classes'			=> 'class',		// default
		'Traits'			=> 'trait',
		'Interfaces'		=> 'interface',
		'Exceptions'		=> 'exception',
	];

	/**
	 * @var array file name patterns to look for - %1$s = classname, %2$s = classtype
	 */
	const FILE_PATTERNS = [
		'%1$s.%2$s.php',					// <classname>.<type>.php
		'class.%1$s.%2$s.php',				// class.<classname>.<type>.php
		'%1$s.class.php',					// <classname>.class.php
		'class.%1$s.php',					// class.<classname>.php
		'%1$s.php'							// <classname>.php
	];

	/**
	 * @var array static array of [namespace => [ root_directory, ... ] ]
	 */
	public static $validNamespace = [];

	/**
	 * @var array static debugging history
	 */
	public static $history = [];

	/**
	 * register the autoloader for the default and (maybe) derivative namespace
	 *
	 * @params string $namespace 	derivative plugin root namespace
	 * @params string $root 		derivative plugin root directory - 'wp-content/plugins/plugin_folder/NameSpace'
	 * @return void
	 */
	public static function debugAutoLoader(array $debug_array): array
	{
		$debug_array['Autoloader'] = [
			'Namespaces'	=> self::$validNamespace,
		//	'Objects'		=> self::$history
		];
		return $debug_array;
	}


	/**
	 * register the autoloader for the default and (maybe) derivative namespace
	 *
	 * @params string $namespace 	derivative plugin root namespace
	 * @params string $root 		derivative plugin root directory - 'wp-content/plugins/plugin_folder/NameSpace'
	 * @return void
	 */
	public static function setAutoLoader(string $namespace = '', string $root = ''): void
	{
		// add this default namespace and register the autoloader
		if (! isset( self::$validNamespace[ __NAMESPACE__ ] ))
		{
			self::addNamespace( __NAMESPACE__, EACDOOJIGGER_HOME.DIRECTORY_SEPARATOR.__NAMESPACE__ );
			spl_autoload_register( [self::class, 'autoLoader'] );
			\add_filter( 'eacDoojigger_debugging',[self::class,'debugAutoLoader'] );
		}

		// add custom/alternate/derivative namespace or alternate directory
		self::addNamespace( $namespace, $root );
	}


	/**
	 * add namespace and root directory
	 *
	 * @params string $namespace 	root namespace name (namespace or namespace\subnamespace)
	 * @params string $root 		root directory for above namespace
	 * @return bool success
	 */
	public static function addNamespace(string $namespace, string $root): bool
	{
		if ( empty($root) || empty($namespace) )  return false;

		if (! is_dir($root) )
		{
			$root = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.ltrim($root,DIRECTORY_SEPARATOR);
		}
		if (! is_dir($root) ) return false;

		$namespace = trim($namespace,'\\').'\\';

		if (!isset( self::$validNamespace[ $namespace ] ))
		{
			self::$validNamespace[ $namespace ] = [];
		}
		if (!in_array($root, self::$validNamespace[ $namespace ]) )
		{
			self::$validNamespace[ $namespace ][] = $root;
		}
		return true;
	}


	/**
	 * autoloader
	 *
	 * @params string $class class to be loaded (NameSpace\SubNameSpace\MaybeType\ClassName)
	 * @return void
	 */
	public static function autoLoader(string $class)
	{
		// find in our registered namespace(s)
		foreach(array_keys(self::$validNamespace) as $ns)
		{
			if (0 === strpos( $class, $ns )) break;
			$ns = false;
		}
		// bail if not in our namespaces
		if (empty($ns)) return;

		// remove root namespace and split to directory array
		$namespace 		= explode('\\',str_replace($ns,'',$class));
		// get class name
		$classname 		= array_pop($namespace);
		// maybe file type is part of namespace path (...\Traits\classname.php)
		$classtype 		= (count($namespace) > 0) ? end($namespace) : 'Classes';
		$classtype 		= self::AUTOLOAD_TYPES[$classtype] ?? strtolower($classtype);
		// maybe strip '_type' from class name for file name (e.g. class something_extension in something.extension.php)
		$classname 		= basename($classname,"_{$classtype}");

		// each root directory registered for namespace
		foreach( self::$validNamespace[ $ns ] as $root)
		{
			// prepend root directory
			$path 		= $namespace;
			array_unshift($path,$root);
			$path 		= implode(DIRECTORY_SEPARATOR,$path).DIRECTORY_SEPARATOR;

			foreach (self::FILE_PATTERNS as $file)
			{
				$file = $path . sprintf($file,$classname,$classtype);
				if (is_file($file))
				{
				//	self::$history[$class] = str_replace(WP_PLUGIN_DIR,'',$file);
					return require $file;
				}
			}
		}
	}


	/**
	 * set the fatal email loader - no longer supported
	 *
	 * @params string $loaderClassName plugin class name (short)
	 * @return void
	 */
	public static function setEmailNotification(string $loaderClassName): void
	{
	/*
		self::$loaderClassName = $loaderClassName;
		$emailOption = $loaderClassName .'_emailFatalNotice';
		if ($emailAddress = \get_option( $emailOption ))
		{
			$emailAddress = ltrim($email,'/');
			add_filter( 'recovery_mode_email', function( $email ) use($emailAddress) {
				$email['to'] = $emailAddress;
				return $email;
			} );
		}
	*/
	}
}

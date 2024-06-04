<?php
namespace EarthAsylumConsulting;

/**
 * {eac}DoojiggerAutoloader class - {eac}Doojigger for WordPress,
 * Autoloader class for EarthAsylum Consulting {eac} Doojigger for WordPress classes and traits
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger Utilities\{eac}DoojiggerAutoloader
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * Version: 	2.2.0
 * @link		https://eacDoojigger.earthasylum.com/
 */

/*
 * Usage:
 *
 * If the autoloader hasn't been loaded yet, this is needed...
 *
 *		if (!class_exists('\\EarthAsylumConsulting\\eacDoojiggerAutoloader'))
 *		{
 *			require dirname(__DIR__).'/Utilities/eacDoojiggerAutoloader.class.php';
 *		}
 *
 * set the autoloader with something like this
 *
 *		eacDoojiggerAutoloader::setAutoLoader( $_namespace, dirname($_pluginfile).DIRECTORY_SEPARATOR.$_namespace );
 *			- and/or -
 *		eacDoojiggerAutoloader::addNamespace( $_namespace, dirname($_pluginfile).DIRECTORY_SEPARATOR.$_namespace );
 *
 * giving the base namespace name and the root directory for that namespace
 *
 *
 * When installed by {eac}Doojigger, the following is used in the mu-plugin:
 *
 *		define('EACDOOJIGGER_VERSION','2.3.4');
 *		require_once WP_PLUGIN_DIR.'/eacDoojigger/EarthAsylumConsulting/Utilities/eacDoojiggerAutoloader.class.php';
 *		eacDoojiggerAutoloader::setAutoLoader();
 *		eacDoojiggerAutoloader::setEmailNotification( 'eacDoojigger' );
 *
 */

class eacDoojiggerAutoloader
{
	/**
	 * @var array map object namespace to file type
	 */
	const AUTOLOAD_TYPES = [
		'Plugin'		=>'plugin',
		'Helpers'		=>'class',
		'Traits'		=>'trait',
		'Interfaces'	=>'interface',
		'Extensions'	=>'extension'
	];

	/**
	 * @var bool is autoloader registered
	 */
	public static $isRegistered = false;

	/**
	 * @var array static array of [namespace => root_directory]
	 */
	public static $validNamespace = [];

	/**
	 * @var string who loaded us (short class name used for emailing fatal errors)
	 */
	public static $loaderClassName;


	/**
	 * add namespace and root directory
	 *
	 * @params string $namespace plugin object root namespace name
	 * @params string $root plugin root directory - '.../wp-content/plugins/plugin_folder/namespace_folder'
	 * @return bool success
	 */
	public static function addNamespace(string $namespace, string $root): bool
	{
		if ( empty($root) || empty($namespace) )  return false;

		if (! is_dir($root) ) $root = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.ltrim($root,DIRECTORY_SEPARATOR);
		if (! is_dir($root) ) return false;

		// plugin namespace maps to namespace folder
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
	 * register the autoloader
	 *
	 * @params string $namespace plugin object root namespace
	 * @params string $root plugin root directory - '.../wp-content/plugins/plugin_folder/NameSpace'
	 * @return void
	 */
	public static function setAutoLoader(string $namespace = '', string $root = ''): void
	{
		// add this namespace (vendor folder)
		self::addNamespace( __NAMESPACE__, dirname(__DIR__) );

		// add custom namespace
		if ($namespace && ($namespace != __NAMESPACE__ || $root != dirname(__DIR__)))
		{
			self::addNamespace( $namespace, $root );
		}

		// register the autoloader for these namespaces
		if (! empty(self::$validNamespace) && !self::$isRegistered )
		{
			self::$isRegistered = spl_autoload_register( [self::class, 'autoLoader'] );
		}
	}


	/**
	 * autoloader
	 *
	 * @params string $class class to be loaded (NameSpace\SubNameSpace\ClassName)
	 * @return void
	 */
	public static function autoLoader(string $class)
	{
		$namespace = explode('\\',$class);									// NameSpace\Traits\TraitName = ['NameSpace','Traits','TraitName']
		if (!isset(self::$validNamespace[ $namespace[0] ])) return;			// not in our namespaces

		foreach( self::$validNamespace[ $namespace[0] ] as $root)			// each root directory registered for namespace
		{
			$path 		= $namespace;										// ['NameSpace','Traits','TraitName']
			$path[0] 	= $root;											// ['.../wp-content/plugins/plugin_folder/namespace_folder','Traits','TraitName']
			$name 		= array_pop($path);									// 'TraitName'

			if (count($path) > 1) {											// get the type (namespace) 'Traits' and folder 'traits'
				$type 	= $path[1];
				$type 	= (array_key_exists($type, self::AUTOLOAD_TYPES)) ? self::AUTOLOAD_TYPES[$type] : strtolower($type);
			} else {
				$type	= 'class';
			}

			$path = implode(DIRECTORY_SEPARATOR,$path);						// .../wp-content/plugins/plugin_folder/namespace_folder/traits'

			// look in plugin_folder/namespace_folder/<type>s/, plugin_folder/namespace_folder/
			foreach([ 	$path.DIRECTORY_SEPARATOR,
						dirname($path).DIRECTORY_SEPARATOR,
					] as $dir)
			{
				foreach (array_unique([$name, basename($name,"_{$type}")]) as $slug)
				{
					if (is_file("{$dir}{$slug}.php"))								// <classname>.php
					{
						return require "{$dir}{$slug}.php";
					}
					if (is_file("{$dir}{$slug}.{$type}.php"))						// <classname>.<type>.php
					{
						return require "{$dir}{$slug}.{$type}.php";
					}
					if (is_file("{$dir}{$type}.{$slug}.php"))						// <type>.<classname>.php
					{
						return require "{$dir}{$type}.{$slug}.php";
					}
					if ($type != 'class')
					{
						if (is_file("{$dir}class.{$slug}.{$type}.php"))				// class.<classname>.<type>.php
						{
							return require "{$dir}class.{$slug}.{$type}.php";
						}
						if (is_file("{$dir}{$type}.{$slug}.php"))					// <type>.<classname>.class.php
						{
							return require "{$dir}{$type}.{$slug}.class.php";
						}
					}
				}
			}
		}
	}


	/**
	 * set the fatal email loader
	 *
	 * @params string $loaderClassName plugin class name (short)
	 * @return void
	 */
	public static function setEmailNotification(string $loaderClassName): void
	{
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
	}
}

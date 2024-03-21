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
 * Version: 	2.1.1
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
 *		define('EAC_DOOJIGGER_VERSION','2.3.4');
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

	/*
		add_filter( 'wp_php_error_message',self::class.'::php_fatal_error_email', 10, 2 );
		add_action( 'php_fatal_email_reset', function($emailOption)
			{
				if ($email = \get_option( $emailOption )) {
					$email = ltrim($email,'/');
					\update_option( $emailOption, $email );
				}
			}
		);
	*/
	}


	/**
	 * email_fatal_error on shutdown with wp_php_error_message filter.
	 * no longer  used in  lue of WordPress recover mode email
	 *
	 * @params string $wpMessage WordPress message
	 * @params array $error PHP error array
	 * @return string $wpMessage
	 */
/*
	public static function php_fatal_error_email(string $wpMessage='', array $error=[]): string
	{
		if (function_exists('\wp_mail'))
		{
			$emailOption = self::$loaderClassName .'_emailFatalNotice';
			$sendTo = \get_option( $emailOption );
			if (empty($sendTo)) return $wpMessage;
			// so we don't keep sending email. Must reset.
			if (substr($sendTo,0,2) == '//') {
				return $wpMessage;
			} else {
				\update_option( $emailOption, '//'.$sendTo );
			}
			$sendFrom = get_bloginfo('admin_email');
			$message = 	'<h4>Requested URL '.$_SERVER['REQUEST_METHOD'].' '.home_url($_SERVER['REQUEST_URI']).'</h4>'.
						(isset($_SERVER['HTTP_REFERER']) ? '<h4>Referring URL '.$_SERVER['HTTP_REFERER'].'</h4>' : '').
						'<div>'.$wpMessage.'</div>'.
						'<pre>'.var_export($error,true).'</pre>'.
						'<p><em>* Notifications temporarily disabled. Reset \'Email Fatal Errors\' option in <a href=\''.
							admin_url('/admin.php?page='.self::$loaderClassName.'SiteSettings').
						'\'>'.self::$loaderClassName.' settings</a> to receive further notifications.</em></p>';
			\wp_mail(
				$sendTo,
				'WordPress PHP Error Notification',
				$message,
				array('From: '.$sendFrom, 'Content-type: text/html')
			);
			// reset the email notification in x minutes from now
			wp_schedule_single_event( time()+(MINUTE_IN_SECONDS * 5), 'php_fatal_email_reset', [$emailOption] );
		}
		return $wpMessage;
	}
*/
}

<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger for WordPress - Base class for custom plugin extensions.
 *
 * Extensions create additional plugin options & methods while being managed by the main plugin
 *
 * @example class myAwesomeExtension extends \EarthAsylumConsulting\abstract_extension
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.1003.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		\EarthAsylumConsulting\abstract_core
 */

abstract class abstract_extension
{
	/**
	 * @var string in child class for version updating
	 */
	const VERSION		= false;

	/**
	 * @var string in child class for alias class name
	 */
	const ALIAS			= null;

	/**
	 * @var string to set default tab name
	 */
	const TAB_NAME		= null;

	/**
	 * @var string|array|bool to set (or disable) default group display/switch
	 * 		false 		disable the 'Enabled'' option for this group
	 * 		string 		the label for the 'Enabled' option
	 * 		array 		override options for the 'Enabled' option (label,help,title,info, etc.)
	 */
	const ENABLE_OPTION	= null;

	/**
	 * @var constructor flags (actual values subject to change)
	 */
	const ALLOW_ADMIN		= 0b00000001;		// enabled on admin pages (default is disabled)
	const ONLY_ADMIN		= 0b00000010;		// enabled only on admin pages, not front-end
	const ALLOW_NETWORK		= 0b00000100;		// enabled for network admin in multisite (uses network options)
	const ALLOW_CRON		= 0b00001000;		// enabled for cron requests
	const ALLOW_CLI			= 0b00010000;		// enabled for wp-cli requests
	const ALLOW_ALL			= self::ALLOW_ADMIN|self::ALLOW_NETWORK|self::ALLOW_CRON|self::ALLOW_CLI;
	const DEFAULT_DISABLED	= 0b00100000;		// force {classname}_enabled' option to default to not enabled
	const ALLOW_NON_PHP		= 0b01000000;		// enabled when loaded for a url not ending in .php

	/**
	 * @var bool is this extension network enabled
	 */
	protected $networkEnabled = null;

	/**
	 * @var string path to extension plugin file (set for extension plugin updates).
	 * Extension must use \EarthAsylumConsulting\Traits\plugin_update and set $update_plugin_file.
	 *
	 * @deprecated use pluginName::loadPluginUpdater(__FILE__,[ 'self' | 'wp' ]);
	 */
	protected $update_plugin_file;

	/**
	 * @var plugin class method that can be called directly from extensions ($this->method() vs $this->plugin->method())
	 */
/*
	const PLUGIN_SHORTCUT_METHODS = [
		// site options
			'is_option',
			'get_option',
			'get_option_decrypt',
		//	'set_option',
			'add_option',
			'delete_option',
			'update_option',
			'update_option_encrypt',
		// network options
		//	'is_network_option',
			'get_network_option',
			'get_network_option_decrypt',
		//	'set_network_option',
			'add_network_option',
			'delete_network_option',
			'update_network_option',
			'update_network_option_encrypt',
		// site or network options
			'get_site_option',
			'get_site_option_decrypt',
		//	'set_site_option',
			'add_site_option',
			'delete_site_option',
			'update_site_option',
			'update_site_option_encrypt',
		// filters
			'has_filter',
			'add_filter',
			'remove_filter',
			'apply_filters',
			'did_filter',
		// actions
			'has_action',
			'add_action',
			'remove_action',
			'do_action',
			'did_action',
		// contextual help
			'addPluginHelpTab',
			'addPluginSidebarText',
			'addPluginSidebarLink',
		// other
			'is_admin',
			'is_network_admin',
			'add_option_error',
			'add_option_warning',
			'add_option_success',
			'add_option_notice',
	];
*/

	/**
	 * @var object Holds the parent plugin class object
	 */
	protected $plugin;

	/**
	 * @var string The class name of the extension class (sans namespace)
	 */
	protected $className;

	/**
	 * @var string The name of the plugin class (sans namespace) aka $className
	 */
	protected $pluginName;

	/**
	 * @var string The default tab name (set by registerExtension, used by registerExtensionOptions)
	 */
	protected $defaultTab;

	/**
	 * @var int flags passed to constructor
	 */
	protected $flags = true;

	/**
	 * @var string the option name to enable (false=no option)
	 * 		can be set (as ENABLED_OPTION) before constructor to override 'Enabled' option
	 */
	protected $enable_option = null;

	/**
	 * @var bool is this class enabled
	 */
	protected $enabled = true;

	/**
	 * @var object WordPress DB
	 */
	protected $wpdb;

	/**
	 * Extension constructor method
	 *
	 * Child class constructors MUST call parent::__construct($plugin,$flags);
	 *
	 * @param	object 	$plugin parent/loader class object
	 * @param	int 	$flags see const values
	 * @return	bool	is enabled
	 */
	public function __construct($plugin,$flags=null)
	{
		$this->flags			= $flags;
		$this->plugin			= $plugin;
		$this->pluginName		= $this->getClassName($plugin);
		$this->wpdb 			= $GLOBALS['wpdb'];
		$this->getClassName();

		// add automatic update through plugin_update trait (when so configured) -deprecated-
		if ( !empty($this->update_plugin_file) && $this->plugin->isPluginsPage() )
		{
			if (file_exists($this->update_plugin_file))
			{
				if (method_exists($this,'addPluginUpdateHooks')) {
					$plugin_data = get_file_data( $this->update_plugin_file, ['UpdateURI' => 'Update URI'], 'plugin' );
					$this->addPluginUpdateHooks(
						[
							'plugin_slug'	=> plugin_basename($this->update_plugin_file),
							'plugin_uri'	=> $plugin_data['UpdateURI'],
						]
					);
				} else if (method_exists($this,'addPluginUpdateNotice')) {
					$this->addPluginUpdateNotice(plugin_basename($this->update_plugin_file));
				}
			}
		}

		// check request uri for .php
		if ( ! ($flags & self::ALLOW_NON_PHP) )
		{
			if ( ! eacDoojigger::isPHP() ) {
				return $this->isEnabled(false);
			}
		}

		// check network admin
		if ( ! ($flags & self::ALLOW_NETWORK) )
		{
			if ( $this->plugin->is_network_admin() ) {
				return $this->isEnabled(false);
			}
		}

		// check cron request
		if ( ! ($flags & self::ALLOW_CRON) )
		{
			if ( wp_doing_cron() ) {
				return $this->isEnabled(false);
			}
		}

		// check wp-cli request
		if ( ! ($flags & self::ALLOW_CLI) )
		{
			if ( (PHP_SAPI === 'cli') || (defined('WP_CLI') && WP_CLI) ) {
				return $this->isEnabled(false);
			}
		}

		// always enabled on settings page
		if ( $this->plugin->isSettingsPage() )
		{
			return $this->isEnabled(true);
		}

		// check admin usage
		if ( $this->plugin->is_backend() )
		{
			if ( ! ($flags & (self::ALLOW_ADMIN|self::ONLY_ADMIN)) )
			{
				return $this->isEnabled(false);
			}
		}
		else
		{
			if ( ($flags & self::ONLY_ADMIN) )
			{
				return $this->isEnabled(false);
			}
		}

		return $this->isEnabled(true);
	}


	/**
	 * Extension destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
	}


	/**
	 * Register this extension and options
	 *
	 * @param	string|array 	$optionGroup group name or [groupname, tabname]]
	 * @param	array 			$optionMeta group option meta
	 * @return	void
	 */
	protected function registerExtension($optionGroup, $optionMeta = array())
	{
		if (empty($optionGroup))
		{
			$this->enable_option = false;
			return;
		}

		$optionGroup = $this->plugin->standardizeOptionGroup($optionGroup);
		$groupName = (is_array($optionGroup)) ? $optionGroup[0] : $optionGroup;
		$this->defaultTab = (is_array($optionGroup)) ? $optionGroup[1] : static::TAB_NAME;

		if ( $this->enable_option === false || static::ENABLE_OPTION === false)
		{
			$enabledOption = [];
		}
		else
		{
			$override = static::ENABLE_OPTION ?: $this->enable_option;
			// group names, sanitized, suffixed with '_extension_enabled'
			$this->enable_option = basename(sanitize_key(str_replace(' ','_',$groupName)),'_extension').'_extension_enabled';
			$enabledOption = [
					'type'			=> 'checkbox',
					'options'		=> ['Enabled'],
					'default'		=> ($this->flags & self::DEFAULT_DISABLED) ? '' : 'Enabled',
			];
			if ($override)
			{
				if (is_array($override)) {
					$enabledOption = array_replace($enabledOption,$override);
				} else {
					$enabledOption['label'] = $override;
				}
			}
			$enabledOption = [ $this->enable_option => $enabledOption ];

			if ( $this->plugin->isSettingsPage() && isset($_POST[$this->enable_option]) )
			{
				if ($_POST[$this->enable_option] == '')
				{
					$this->isEnabled(false);
				}
			}
			else if (!$this->is_option($this->enable_option))
			{
				$this->isEnabled(false);
			}
		}

		$this->registerExtensionOptions($optionGroup, array_merge($enabledOption,$optionMeta));
	}


	/**
	 * Register this extension and options
	 *
	 * @param	string|array 	$optionGroup group name or [groupname, tabname]]
	 * @param	array 			$optionMeta group option meta
	 * @return	void
	 */
	protected function registerExtensionOptions($optionGroup, $optionMeta = array())
	{
		if (! $this->plugin->isSettingsPage() ) return;

		if (!is_array($optionGroup) && !empty($this->defaultTab))
		{
			$optionGroup = [$optionGroup,$this->defaultTab];
		}

		if ( is_multisite() && is_network_admin() )
		{
			if ( $this->flags & self::ALLOW_NETWORK ) {
				$this->plugin->registerNetworkOptions($optionGroup, $optionMeta);
			}
		}
		else
		{
			$this->plugin->registerPluginOptions($optionGroup,$optionMeta);
		}
	}


	/**
	 * Extension initialization
	 *
	 * Called after loading and instantiating all extensions
	 *
	 * @return	bool is enabled
	 */
	public function initialize()
	{
	//	if (is_null($this->enable_option))
	//	{
	//		throw new \LogicException("Extension {$this->className} did not register with {$this->pluginName}. Use 'registerExtension(...)' to register." );
	//	}

	//	if ($this->isEnabled())
	//	{
	//		$plugin 	= $this->plugin;
	//		$className 	= $this->className;
	//		/**
	//		 * filter {pluginName}_{className} call an extension method
	//		 * $value = apply_filters( '{pluginName}_{className}', 'method', ...arguments );
	//		 * @return	mixed
	//		 */
	//		$this->plugin->add_filter( $this->className, function($value, $method, ...$arguments) use ($plugin,$className) {
	//			return $plugin->callExtension($className,$method,...$arguments);
	//		}, 10, 5); // upto 5 arguments
	//	}

		return $this->enabled;
	}


	/**
	 * Add extension actions and filter
	 *
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @return	void
	 */
	public function addActionsAndFilters()
	{
	}


	/**
	 * Add extension shortcodes
	 *
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @return	void
	 */
	public function addShortcodes()
	{
	}


	/**
	 * isEnabled - set or test extension enabled for use
	 *
	 * @param	bool|string	$enabled true|false or other extension name
	 * @param	bool		$perm optional, to permanently set enabled option
	 * @return	bool
	 */
	public function isEnabled($enabled=null,$perm=null)
	{
		if (is_bool($enabled))
		{
			$this->enabled = $enabled;
			if ($perm === true && $this->enable_option) {
				$this->plugin->update_option($this->enable_option,($enabled ? 'Enabled' : ''));
			}
		}
		else if (is_string($enabled)) 	// checking another extension's 'enabled'
		{
			$this->enabled = (bool)( $this->plugin->isExtension($enabled,true) );
		}

		/**
		 * filter {pluginName}_extension_enabled allow disabling extensions
		 * @param	bool current enabled value
		 * @param	string $this->className (extension name)
		 * @return	bool enabled value
		 */
		$this->enabled = $this->plugin->apply_filters( 'extension_enabled', $this->enabled, $this->className );

		return $this->enabled;
	}


	/**
	 * is_network_enabled - set or test extension enabled for use at the network level
	 *
	 * @return	bool
	 */
	public function is_network_enabled()
	{
		if ( ! is_bool($this->networkEnabled))
		{
			if ( ! $this->flags & self::ALLOW_NETWORK )
			{
				$this->networkEnabled = false;
			}
			else if ( ! is_multisite())
			{
				$this->networkEnabled = false;
			}
			else if ( ! $this->plugin->is_network_enabled() )
			{
				$this->networkEnabled = false;
			}
			else if ($this->enable_option)
			{
				$this->networkEnabled = $this->plugin->is_network_option($this->enable_option);
			}
			else if ( ! $this->enabled ) // must check above first
			{
				$this->networkEnabled = false;
			}
			else
			{
				$this->networkEnabled = true;
			}
		}
		return $this->networkEnabled;
	}


	/**
	 * is this class network enabled and does option match a value
	 *
	 * @param	string 		$optionName option name
	 * @param	mixed 		$value check this value
	 * @return	bool|mixed 	option is set and has value
	 */
	public function is_network_option($optionName, $value = null)
	{
		return ($this->is_network_enabled()) ? $this->plugin->is_network_option($optionName,$value) : false;
	}


	/**
	 * getClassName - get the class/extension name without namespace
	 *
	 * @param	object	$class optional class object
	 * @return	bool
	 */
	public function getClassName($class=null)
	{
		if ( empty($class) || $class === $this )
		{
			if ( empty($this->className) ) {
				$this->className = basename(str_replace('\\', '/', get_class($this)));
			}
			return $this->className;
		}
		return $this->plugin->getClassName($class);
	}


	/**
	 * get the extension version
	 *
	 * @return const VERSION string
	 */
	public function getVersion()
	{
		return static::VERSION;
	}


	/**
	 * get the extension alias
	 *
	 * @return const ALIAS string
	 */
	public function getAlias()
	{
		return static::ALIAS;
	}


	/**
	 * Add the prefixed of this class name (plugin adds plugin name prefix)
	 *
	 * @param	string 	$name key name
	 * @return	string 	name with prefix
	 */
	private function addClassPrefix($name)
	{
		return $this->className.'.'.$name;
	}


	/**
	 * A wrapper function to WP get_transient() with prefixed transient name
	 *
	 * @param	string 	$transientName transient name
	 * @param	mixed 	$callback callable function or default value if not found
	 * @param 	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	mixed 	transient value
	 */
	protected function get_transient($transientName, $callback = false, $expiration = 0 )
	{
		return $this->plugin->get_transient( $this->addClassPrefix($transientName), $callback, $expiration );
	}


	/**
	 * A wrapper function to WP set_transient() with prefixed transient name
	 *
	 * @param	string 	$transientName transient name
	 * @param	mixed 	$value value to save
	 * @param 	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	bool
	 */
	protected function set_transient($transientName, $value, $expiration = 0 )
	{
		return $this->plugin->set_transient( $this->addClassPrefix($transientName), $value, $expiration );
	}


	/**
	 * A wrapper function to WP delete_transient() with prefixed transient name
	 *
	 * @param	string 	$transientName transient name
	 * @return	bool
	 */
	protected function delete_transient($transientName)
	{
		return $this->plugin->delete_transient( $this->addClassPrefix($transientName) );
	}


	/**
	 * magic method to call plugin or extension methods
	 *
	 * @param	mixed 	$method	the method name or [extension,method]
	 * @param	mixed 	$arguments the arguments to method name
	 * @return	mixed	result of method called
	 */
	public function __call($method, $arguments)
	{
		if (is_callable([$this->plugin,$method]))
		{
			return $this->plugin->{$method}(...$arguments);
		}
	//	if (in_array($method, self::PLUGIN_SHORTCUT_METHODS))
	//	{
	//		return call_user_func( [$this->plugin, $method], ...$arguments);
	//	}
		$this->plugin->fatal($this->className,"Call to unknown plugin method: '{$this->pluginName}->{$method}'",['class'=>$this->className,'method'=>$method,'arguments'=>$arguments]);
	}


	/**
	 * magic method to call parent plugin __get()
	 *
	 * @param string $property the property name or extension name
	 * @return mixed result of plugin __get()
	 */
	public function __get($property)
	{
		if (is_callable([$this->plugin,'__get']))
		{
			return $this->plugin->__get($property);
		}
		trigger_error('Undefined property: '.__CLASS__.'::'.$property,E_USER_NOTICE);
		return null;
	}


	/**
	 * version updated
	 *
	 * @param	string	$curVersion currently installed version number
	 * @param	string	$newVersion version being installed/updated
	 * @return	bool
	 */
	//public function adminVersionUpdate($curVersion,$newVersion)
	//{
	//}
}
?>

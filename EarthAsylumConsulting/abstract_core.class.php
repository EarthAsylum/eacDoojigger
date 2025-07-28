<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger for WordPress - Plugin core methods, hooks, and settings.
 *
 * abstract_frontend and abstract_backend extend abstract_core.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.earthasylum.com>
 * @version		25.0717.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see			https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		\EarthAsylumConsulting\abstract_frontend
 * @used-by		\EarthAsylumConsulting\abstract_backend
 */

abstract class abstract_core
{
	/**
	 * @trait methods for logging using Logger helper
	 */
	use \EarthAsylumConsulting\Traits\logging;

	/**
	 * @trait methods for actions & filters
	 */
	use \EarthAsylumConsulting\Traits\hooks;

	/**
	 * @trait methods for loading extensions
	 */
	use \EarthAsylumConsulting\Traits\load_extensions;

	/**
	 * @trait methods for advanced mode
	 */
	use \EarthAsylumConsulting\Traits\advanced_mode;

	/**
	 * @trait methods for cookie consent using WP Consent API
	 */
	use \EarthAsylumConsulting\Traits\cookie_consent;

	/**
	 * @var string transient name for plugin header info
	 * @see EarthAsylumConsulting\abstract_core\setPluginHeaderValues()
	 */
	const PLUGIN_HEADER_TRANSIENT		= 'plugin_header_data';

	/**
	 * @var string transient name for plugin extensions to load
	 */
	const PLUGIN_EXTENSION_TRANSIENT	= 'plugin_loaded_extensions';

	/**
	 * @var string base class name of extensions
	 */
	const EXTENSION_BASE_CLASS			= __NAMESPACE__.'\abstract_extension';

	/** @var string environment - live */
	const ENVIRONMENT_LIVE				= 'Production (live)';
	/** @var string environment - test */
	const ENVIRONMENT_TEST				= 'Development (test)';

	/**
	 * @var string plugin options name
	 */
	const PLUGIN_OPTION_NAME			= 'plugin_options';

	/**
	 * @var string network options name
	 */
	const NETWORK_OPTION_NAME			= 'network_options';

	/**
	 * @var array reserved options (not part of options array)
	 */
	const RESERVED_OPTIONS				= [
		'uninstall_options',		// {classname}_uninstall_options for uninstall trait
		'selected_update_channel',	// {classname}_selected_update_channel for plugin_update trait
	//	'emailFatalNotice'			// {classname}_emailFatalNotice for recovery_mode_email - removed
	];


	/**
	 * @var array plugin header values from the base file headers
	 */
	protected $pluginData;

	/**
	 * @var string plugin slug name (directory/pluginname.php)
	 */
	public $PLUGIN_SLUG;

	/**
	 * @var string Language name of this plugin, must match 'Text Domain' comment
	 */
	public $PLUGIN_TEXTDOMAIN;


	/**
	 * @var array plugin/extension options/settings array (optionName=>value)
	 */
	public $pluginOptions				= array();

	/**
	 * @var bool plugin option(s) have been changed
	 */
	private $pluginOptionsUpdated		= false;

	/**
	 * @var array network options/settings array (optionName=>value)
	 */
	public $networkOptions				= array();

	/**
	 * @var bool network option(s) have been changed
	 */
	private $networkOptionsUpdated		= false;

	/**
	 * @var array reserved option keys (typically used outside of the plugin)
	 * option keys stored individually (true/false)
	 */
	public $reservedOptions				= array();


	/**
	 * @var string The name of this plugin class (sans namespace) aka $pluginName
	 * @used-by getClassName()
	 */
	public $className;

	/**
	 * @var object $this
	 */
	public $plugin;

	/**
	 * @var string The name of this plugin class (sans namespace) aka $className
	 * @used-by getClassName()
	 */
	public $pluginName;

	/**
	 * @var array extension objects (class_name=>class_object)
	 * @used-by loadextensions()
	 */
	public $extension_objects			= array();

	/**
	 * @var array extension aliases (alias_name=>class_object)
	 */
	public $extension_aliases			= array();

	/**
	 * @var array check that required methods are called
	 */
	private $requiredMethods			=
		[
			'__construct'				=> false,
			'initialize'				=> false,
			'addActionsAndFilters'		=> false,
			'addShortcodes'				=> false,
		];

	/**
	 * @var bool cached (is_admin())
	 */
	protected $is_admin					= null;

	/**
	 * @var bool cached (is_multisite() && is_network_admin())
	 */
	protected $is_network_admin			= null;

	/**
	 * @var bool plugin is network enabled
	 */
	protected $is_network_enabled		= null;

	/**
	 * @var object WordPress DB
	 */
	public $wpdb;

	/**
	 * @var string date/time format
	 */
	public $date_time_format;

	/**
	 * @var string date format
	 */
	public $date_format;

	/**
	 * @var string time format
	 */
	public $time_format;


	/**
	 * Plugin constructor method.
	 * Child class constructors MUST call parent::__construct($header);
	 *
	 * @param array $plugin_detail header passed from loader script
	 * @return	void
	 */
	protected function __construct(array $header)
	{
		$this->requiredMethods['__construct'] = true;

		$this->PLUGIN_SLUG		= plugin_basename( $header['PluginFile'] );
		// is_admin & is_network_admin taking into account ajax requests
		$this->is_admin( static::CONTEXT_IS_BACKEND );
		$this->is_network_admin( static::CONTEXT_IS_NETWORK );

		$this->wpdb				= $GLOBALS['wpdb'];
		$this->date_format		= \get_option( 'date_format' );
		$this->time_format		= \get_option( 'time_format' );
		$this->date_time_format = "{$this->date_format}, {$this->time_format}";

		$this->plugin			= $this;
		$this->pluginName		= $this->getClassName();

		foreach (self::RESERVED_OPTIONS as $reserved)
		{
			$this->isReservedOption($reserved,true);
		}

		$this->load_all_plugin_options();
		$this->load_all_network_options();

		if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false))
		{
			\EarthAsylumConsulting\eacDoojiggerActionTimer::timeAction([
				$this->prefixHookName('extensions_loaded'),
				$this->prefixHookName('initialize'),
				$this->prefixHookName('ready'),
			]);
		}

		$this->setPluginHeaderValues($header);
		$this->setSiteEnvironment();

		/**
		 * action {pluginName}_startuo, fired in plugin_loader after loading, before extensions
		 * @return	void
		 */
		$this->add_action( 'startup',			array($this, '_plugin_startup') );

		\add_action( 'shutdown',				array($this, '_plugin_shutdown') );
	}


	/**
	 * Plugin destructor
	 *
	 * @internal
	 *
	 * @return	void
	 */
	public function __destruct()
	{
	}


	/**
	 * Plugin startup (after plugins_loaded, before loading extensions)
	 *
	 * @internal
	 *
	 * @return	void
	 */
	public function _plugin_startup()
	{
		// initialize 'advanced mode' settings
		$this->advanced_mode_init();

		// cookie consent interface with wp_consent_api
		$this->cookie_consent_init( $this->pluginHeader( 'PluginSlug' ), $this->pluginName );
	}


	/**
	 * Plugin shutdown.
	 * Save option array(s) and verify that we called required parent methods
	 *
	 * @internal
	 *
	 * @return	void
	 */
	public function _plugin_shutdown()
	{
		$this->save_all_plugin_options();
		$this->save_all_network_options();

	//	foreach ($this->requiredMethods as $method => $wasCalled)
	//	{
		//	if (! $wasCalled)
		//	{
		//		trigger_error(
		//			sprintf(__('The %1$s() method in %2$s must call the parent method - parent::%1$s()',$this->PLUGIN_TEXTDOMAIN),
		//				$method,$this->className),
		//			E_USER_ERROR);
		//	}
	//	}
	}


	/**
	 * set/detect site environment (test/live)
	 *
	 * @internal
	 *
	 * @return	void
	 */
	private function setSiteEnvironment(): void
	{
		$wpEnvironment	= (defined('WP_ENVIRONMENT_TYPE')) ? WP_ENVIRONMENT_TYPE : getenv('WP_ENVIRONMENT_TYPE');
		switch ($wpEnvironment)
		{
			case 'local':
			case 'development':
			case 'staging':
				$wpEnvironment = self::ENVIRONMENT_TEST;
				break;
			case 'production':
				$wpEnvironment = self::ENVIRONMENT_LIVE;
				break;
			default:
				if ($wpEnvironment = $this->get_option('siteEnvironment')) return;
				$wpEnvironment = $this->get_network_option('siteEnvironment');
		}

		if ($wpEnvironment)
		{
			$this->get_option('siteEnvironment'); // cleanup old option record
			$this->update_option('siteEnvironment',$wpEnvironment);
		}
	}


	/**
	 * class initialization.
	 * Called after instantiating and loading extensions
	 *
	 * @return	void
	 */
	public function initialize(): void
	{
		$this->requiredMethods['initialize'] = true;

		// initialize extensions
		$this->callAllExtensions('initialize');
	}


	/**
	 * Add plugin actions and filter.
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @see https://codex.wordpress.org/Plugin_API
	 *
	 * @return	void
	 */
	public function addActionsAndFilters(): void
	{
		$this->requiredMethods['addActionsAndFilters'] = true;

		/**
		 * action {classname}_ready, fired in plugin_loader
		 * @return	void
		 */
		$this->add_action( 'ready',				array($this, 'plugin_ready') );

		/**
		 * action {classname}_hourly_event to run hourly
		 * @return	void
		 */
		//$this->add_action( 'hourly_event', 	array($this, 'plugin_hourly_event') );

		/**
		 * action {classname}_daily_event to run daily
		 * @return	void
		 */
		//$this->add_action( 'daily_event',		array($this, 'plugin_daily_event') );

		/**
		 * action {classname}_weekly_event to run hourly
		 * @return	void
		 */
		//$this->add_action( 'weekly_event', 	array($this, 'plugin_weekly_event') );

		/**
		 * action {classname}_flush_caches tell others to clear caches/transient data
		 * @param	bool	full cache flush
		 * @return	void
		 */
		$this->add_action( 'flush_caches',		array($this,'flush_caches') );

		// load extension actions & filters
		$this->callAllExtensions('addActionsAndFilters');

		/**
		 * filter {classname}_reserved_options
		 * to add option names to reserved array to preserve individual option record
		 * @param	array reserved options
		 * @return	array
		 */
		$this->reservedOptions = $this->apply_filters('reserved_options', $this->reservedOptions);

		// admin action link(s)
		add_action( 'admin_bar_init', 			array($this, 'do_admin_action_links'), 20 );
	}


	/**
	 * Add plugin shortcodes.
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @see https://codex.wordpress.org/Shortcode_API
	 *
	 * @return	void
	 */
	public function addShortcodes(): void
	{
		$this->requiredMethods['addShortcodes'] = true;

		// load extension shortcodes
		$this->callAllExtensions('addShortcodes');
	}


	/**
	 * Plugin is fully loaded and ready.
	 * Called when plugin is ready (all extensions/filters/shortcodes loaded)
	 *
	 * @return	void
	 */
	public function plugin_ready(): void
	{
		$this->logInfo(__FUNCTION__,$this->className);
	}


	/**
	 * get plugin data from transient array or from the main plugin header.
	 *
	 * @see https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
	 *
	 * @param array header passed from loader script (required: PluginFile), force reload if empty ([])
	 * @return	void
	 */
	protected function setPluginHeaderValues(array $header): void
	{
		if (empty($header)) $this->delete_site_transient( self::PLUGIN_HEADER_TRANSIENT );

		$this->pluginData = $this->get_site_transient(self::PLUGIN_HEADER_TRANSIENT, function($key) use ($header)
			{
				$default_headers = array(
					'Title'			=> 'Plugin Name',			// The name of your plugin, which will be displayed in the Plugins list in the WordPress Admin.
					'Description'	=> 'Description',			// A short description of the plugin, as displayed in the Plugins section in the WordPress Admin
					'Version'		=> 'Version',				// The current version number of the plugin.
					'RequiresWP'	=> 'Requires at least',		// The lowest WordPress version that the plugin will work on.
					'RequiresPHP'	=> 'Requires PHP',			// The minimum required PHP version.
					'RequiresEAC'	=> 'Requires EAC',			// The minimum required eacDoojigger version.
					'RequiresWC'	=> 'WC requires at least',	// The lowest WooCommerce version that the plugin will work on.
					'Author'		=> 'Author',				// The name of the plugin author.
					'AuthorURI'		=> 'Author URI',			// The author's website or profile on another website, such as WordPress.org.
					'License'		=> 'License',				// The short name (slug) of the plugin's license (e.g. GPLv2).
					'LicenseURI'	=> 'License URI',			// A link to the full text of the license (e.g. https://www.gnu.org/licenses/gpl-2.0.html).
					'TextDomain'	=> 'Text Domain',			// The gettext text domain of the plugin.
					'DomainPath'	=> 'Domain Path',			// The domain path lets WordPress know where to find the translations.
					'Network'		=> 'Network',				// Whether the plugin can be activated network-wide.
					'PluginURI'		=> 'Plugin URI',			// The home page or update link of the plugin.
					'UpdateURI'		=> 'Update URI',			// Allows third-party plugins to avoid accidentally being overwritten with an update of a plugin of a similar name from the WordPress.org Plugin Directory.
					'StableTag'		=> 'Stable Tag',			// From readme.txt
					'LastUpdated'	=> 'Last Updated',			// From readme.txt
				);
				if (empty($header)) {
					$header['PluginFile'] = WP_PLUGIN_DIR . '/' . $this->PLUGIN_SLUG;
				}
				$readme = dirname($header['PluginFile']).'/readme.txt';
				$pluginData = array_replace(
					$header,
					array_filter(get_file_data($readme, $default_headers, 'readme')),
					array_filter(get_file_data($header['PluginFile'], $default_headers, 'plugin') )
				);

				$pluginData['PluginSlug']		= plugin_basename( $pluginData['PluginFile'] );						// Plugin slug
				$pluginData['PluginDir']		= untrailingslashit(plugin_dir_path( $pluginData['PluginFile'] ));	// Plugin directory
				$pluginData['PluginDirUrl']		= plugin_dir_url( $pluginData['PluginFile'] );						// URL to plugin directory
				$pluginData['VendorDir']		= (is_dir($pluginData['PluginDir'].'/'.__NAMESPACE__))
												? $pluginData['PluginDir'].'/'.__NAMESPACE__						// vendor directory default
												: $pluginData['PluginDir'];
				$pluginData['Name']				= dirname($pluginData['PluginSlug']);

				return $pluginData;
			},
			HOUR_IN_SECONDS * 12
		);
		$this->pluginData['RequestTime'] 		= WP_START_TIMESTAMP;
		$this->PLUGIN_TEXTDOMAIN = $this->pluginData['TextDomain'] ?? $this->className;
	}


	/**
	 * get plugin value array from pluginData (base file header).
	 *
	 * @return	array	values from plugin header
	 */
	public function pluginHeaders(): array
	{
		return $this->pluginData;
	}


	/**
	 * get plugin value array from pluginData (base file header).
	 *
	 * @deprecated - use pluginHeaders()
	 *
	 * @return	array	values from plugin header
	 */
	public function getPluginValues(): array
	{
		\_deprecated_function( __FUNCTION__, '2.4.0', 'pluginHeaders()');
		return $this->pluginData;
	}


	/**
	 * get plugin value from pluginData (base file header)
	 *
     * @param   string  $name name of plugin data value
     *      'Title'         => 'Plugin Name',            The name of your plugin, which will be displayed in the Plugins list in the WordPress Admin.
     *      'Description'   => 'Description',            A short description of the plugin, as displayed in the Plugins section in the WordPress Admin
     *      'Version'       => 'Version',                The current version number of the plugin.
     *      'RequiresWP'    => 'Requires at least',      The lowest WordPress version that the plugin will work on.
     *      'RequiresPHP'   => 'Requires PHP',           The minimum required PHP version.
     *      'RequiresEAC'   => 'Requires EAC',           The minimum required eacDoojigger version.
     *      'RequiresWC'    => 'WC requires at least',   The lowest WooCommerce version that the plugin will work on.
     *      'Author'        => 'Author',                 The name of the plugin author.
     *      'AuthorURI'     => 'Author URI',             The author’s website or profile on another website, such as WordPress.org.
     *      'License'       => 'License',                The short name (slug) of the plugin’s license (e.g. GPLv2).
     *      'LicenseURI'    => 'License URI',            A link to the full text of the license (e.g. https://www.gnu.org/licenses/gpl-2.0.html).
     *      'TextDomain'    => 'Text Domain',            The gettext text domain of the plugin.
     *      'DomainPath'    => 'Domain Path',            The domain path lets WordPress know where to find the translations.
     *      'Network'       => 'Network',                Whether the plugin can be activated network-wide.
     *      'PluginURI'     => 'Plugin URI',             The home page or update link of the plugin.
     *      'UpdateURI'     => 'Update URI',             Allows third-party plugins to avoid accidentally being overwritten with an update of a plugin of a similar name from the WordPress.org Plugin Directory.
	 *		'StableTag'		=> 'Stable Tag',		 	 From readme.txt.
	 *		'LastUpdated'	=> 'Last Updated',			 From readme.txt.
     *      'Name'                                       The directory name within plugins.
     *      'PluginSlug'                                 The plugin_basename() slug.
     *      'PluginDir'                                  The directory name plugin_dir_path().
     *      'PluginDirUrl'                               The plugin url plugin_dir_url()
     *      'VendorDir'                                  The namespace directory within plugin directory.
	 * @return	string	data value from plugin header
	 */
	public function pluginHeader(string $name = null): string
	{
		if (empty($name)) return $this->pluginData;
		return $this->pluginData[$name] ?? '';
	}


	/**
	 * get plugin value from pluginData (base file header)
	 *
	 * @deprecated - use pluginHeader()
	 *
	 * @param	string	$name name of plugin data value
	 * @return	string	data value from plugin header
	 */
	public function getPluginValue(string $name = null): string
	{
	//	\_deprecated_function( __FUNCTION__, '2.6.0', 'pluginHeader()');
		return $this->pluginHeader($name);
	}


	/*
	 *
	 * utility/helper functions
	 *
	 */


	/**
	 * is admin (is_admin() or admin url) w/ability to set
	 *
	 * @param bool $set set/override is_admin
	 * @return	bool
	 */
	public function is_admin($set = null): bool
	{
		if (is_bool($set))
		{
			$this->is_admin = $set;
		}
		return $this->is_admin;
	}


	/**
	 * is network admin (is_network_admin() or network admin url) w/ability to set
	 *
	 * @param bool $set set/override is_network_admin
	 * @return	bool
	 */
	public function is_network_admin($set = null): bool
	{
		if (is_bool($set))
		{
			$this->is_network_admin = $set;
		}
		return $this->is_network_admin;
	}


	/**
	 * is front-end (not is_admin() or admin url)
	 *
	 * @return	bool
	 */
	public function is_frontend(): bool
	{
		return static::CONTEXT_IS_FRONTEND;
	}


	/**
	 * is back-end (is_admin() or admin url)
	 *
	 * @return	bool
	 */
	public function is_backend(): bool
	{
		return static::CONTEXT_IS_BACKEND;
	}


	/**
	 * add an action link (for menu and/or clickable actions)
	 *
	 * @param string $action action name
	 * 				advanced_mode_enable, advanced_mode_disable, or custom action name
	 * @return string href
	 */
	public function add_admin_action_link(string $action): string
	{
		$action = esc_attr($action);
		$actionKey = '_'.sanitize_key($this->className).'_action'; // _eacdoojiggerFN
		return wp_nonce_url( add_query_arg( [$actionKey=>$action] ),$this->className );
	}


	/**
	 * process action links from admin page.
	 * additional/custom actions may be added by adding an action for the function name:
	 * 		$this->add_action('my_action_name',function(){...});
	 *
	 * @param object $admin_bar wp_admin_bar
	 * @return void
	 */
	public function do_admin_action_links($admin_bar)
	{
		$actionKey = '_'.sanitize_key($this->className).'_action'; // _eacdoojiggerFN
		if (!isset($_GET[$actionKey]) || !isset($_GET['_wpnonce'])) return;

		$menuFN 	= $this->varGet($actionKey);
		$wpnonce 	= $this->varGet('_wpnonce');
		if (wp_verify_nonce($wpnonce,$this->className))
		{
			switch ($menuFN)
			{
				default:
					$this->do_action($menuFN);
					break;
			}
		}
		// so a reload doesn't initiate again
		wp_safe_redirect( remove_query_arg([$actionKey,'_wpnonce']) );
		exit;
	}


	/**
	 * Add admin notice, log when not admin/backend
	 *
	 * @param string $message message text
	 * @param string $errorType 'error', 'warning', 'notice', 'success'
	 * @param string $moreInfo additional message text
	 * @return void
	 */
	public function add_admin_notice(string $message, string $errorType='notice', string $moreInfo=''): void
	{
		do_action( "qm/{$errorType}", $message );
		switch ($errorType) {
			case 'warning':
			case 'error':
				$this->log($errorType,rtrim(strip_tags($message).'; '.strip_tags($moreInfo),' ;'));
				break;
			default:
				return;
		}
	}


	/**
	 * write admin notice immediately, noop when not admin/backend
	 *
	 * @param string $message message text
	 * @param string $errorType 'error', 'warning', 'notice', 'success'
	 * @param string $moreInfo additional message text
	 * @return void
	 */
	public function print_admin_notice(string $message, string $errorType='notice', string $moreInfo=''): void
	{
	}


	/**
	 * is plugin active (may be called from inactive site)
	 *
	 * @param string $plugin plugin slug
	 * @return	bool
	 */
	public function is_plugin_active(string $plugin = null): bool
	{
		$slug = $plugin ?? $this->PLUGIN_SLUG;
		return $this->is_network_enabled($plugin) || in_array( $slug, (array) \get_option( 'active_plugins', [] ), true );
	}


	/**
	 * is plugin network-enabled
	 *
	 * @return	bool
	 */
	public function is_network_enabled(string $plugin = null): bool
	{
		if (is_null($this->is_network_enabled) || !is_null($plugin))
		{
			if ( ! is_multisite() )
			{
				$this->is_network_enabled = false;
			}
			else
			{
				$plugins = \get_site_option( 'active_sitewide_plugins' );
				if (!empty($plugin)) return (isset( $plugins[ $plugin ] ));
				$this->is_network_enabled = (isset( $plugins[ $this->PLUGIN_SLUG ] ));
			}
		}
		return $this->is_network_enabled;
	}


	/**
	 * in multisite environment, perform callback for each site from network admin, not as network admin
	 *
	 * @example $this->forEachNetworkSite( function() {...} );
	 *
	 * @param callable $callback
	 * @return bool is network admin
	 */
	public function forEachNetworkSite(callable $callback,...$arguments)
	{
		if ($this->is_network_admin())
		{
			$this->is_network_admin(false);
			$current_blog 	= get_current_blog_id();
			$switched_stack = $GLOBALS[ '_wp_switched_stack' ];
			$switched 		= $GLOBALS[ 'switched' ];
			$siteIds 		= \get_sites( ['fields' => 'ids', 'orderby' => 'id'] );
			foreach($siteIds as $siteId)
			{
				$this->switch_to_blog( $siteId );
				if ( $this->is_plugin_active() )
				{
					call_user_func( $callback, ...$arguments );
				}
				//$this->restore_current_blog();
			}
			$this->switch_to_blog( $current_blog );
			$GLOBALS[ '_wp_switched_stack' ] 	= $switched_stack;
			$GLOBALS[ 'switched' ] 				= $switched;
			$this->is_network_admin(true);
		}
		return $this->is_network_admin();
	}


	/**
	 * switch_to_blog wrapper.
	 * enables calls to $this->before_switch_blog() and $this->after_switch_blog()
	 *
	 * @param	string	$new_blog_id switching to blog
	 * @return	bool always true
	 */
	public function switch_to_blog(int $new_blog_id): bool
	{
		$prev_blog_id = get_current_blog_id();
		$this->before_switch_blog( $new_blog_id, $prev_blog_id, 'switch' );
		$result = \switch_to_blog( $new_blog_id );
		$this->after_switch_blog( $new_blog_id, $prev_blog_id, 'switch');
		return $result;
	}


	/**
	 * restore_current_blog wrapper.
	 * enables calls to $this->before_switch_blog() and $this->after_switch_blog()
	 *
	 * @return	bool
	 */
	public function restore_current_blog(): bool
	{
		if ( empty( $GLOBALS['_wp_switched_stack'] ) ) return false;
		$new_blog_id  = end( $GLOBALS['_wp_switched_stack'] );
		$prev_blog_id = get_current_blog_id();
		$this->before_switch_blog( $new_blog_id, $prev_blog_id, 'restore' );
		$result = \restore_current_blog();
		$this->after_switch_blog( $new_blog_id, $prev_blog_id, 'restore');
		return $result;
	}


	/**
	 * before switching blogs, save internal option array.
	 * called from $this->switch_to_blog() (not WP).
	 *
	 * @param	string	$new_blog_id switching to blog
	 * @param	string	$prev_blog_id switching from blog
	 * @param	string	$switch 'switch' or 'restore'
	 * @return	void
	 */
	public function before_switch_blog($new_blog_id, $prev_blog_id, $switch): void
	{
		if ($new_blog_id != $prev_blog_id)
		{
			$this->save_all_plugin_options();
		}
	}


	/**
	 * after switching blogs, load internal option array.
	 * called from WP 'switch_blog' action.
	 *
	 * @param	string	$new_blog_id switching to blog
	 * @param	string	$prev_blog_id switching from blog
	 * @param	string	$switch 'switch' or 'restore'
	 * @return	void
	 */
	public function after_switch_blog($new_blog_id, $prev_blog_id, $switch): void
	{
		if ($new_blog_id != $prev_blog_id)
		{
			$this->load_all_plugin_options();
		}
	}


	/**
	 * pluginHelpEnabled
	 *
	 * @param	bool $tabs enable/disable tabs
	 * @param	bool $fields enable/disable fields
	 * @return	bool
	 */
	public function pluginHelpEnabled($tabs = null, $fields = null): bool
	{
		if ( $this->is_admin() && method_exists( $this, 'plugin_help_enabled' ) )
		{
			return $this->plugin_help_enabled($tabs,$fields);
		}
		return false;
	}


	/**
	 * Generates a Universally Unique IDentifier (UUID), version 4.
	 *
	 * @see http://www.ietf.org/rfc/rfc4122.txt (RFC 4122)
	 *
	 * @return string UUID
	 */
	public function createUniqueId()
	{
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}


	/**
	 * plugin_hourly_event
	 *
	 * @return	void
	 */
	public function plugin_hourly_event(): void
	{
	}


	/**
	 * plugin_daily_event
	 *
	 * @return	void
	 */
	public function plugin_daily_event(): void
	{
		// WP does this with delete_expired_transients
		//$this->purge_expired_transients();
	}


	/**
	 * plugin_weekly_event
	 *
	 * @return	void
	 */
	public function plugin_weekly_event(): void
	{
	}


	/**
	 * getClassReflection - get the reflection variables
	 *
	 * @return	void
	 */
	private function getClassReflection(): void
	{
		$reflect = new \ReflectionClass($this);
		$namespace = $reflect->getNamespaceName();
		$namespace = explode("\\",$namespace);
		$namespace = end($namespace);
		$pathname  = $reflect->getFileName();

		$this->className		= $reflect->getShortName();
		$this->pluginName		= $this->className;
		$this->classNameSpace	= $namespace;
		$this->classFileName	= pathinfo($pathname,PATHINFO_FILENAME);
	}


	/**
	 * get the class name without namespace
	 *
	 * @param	object	$object optional class object
	 * @return	string|bool
	 */
	public function getClassName($object=null): ?string
	{
		if ( empty($object) || $object == $this )
		{
			if ( empty($this->className) ) {
				$this->className = basename(str_replace('\\', '/', get_class($this)));
			}
			return $this->className;
		}

		if (is_string($object))
		{
			$object = $this->getExtension($object,false);
		}

		if (is_object($object))
		{
			return basename(str_replace('\\', '/', get_class($object)));
		}

		return null;
	}


	/**
	 * version of this code from the plugin header or from given extension
	 *
	 * @param	string	extension name
	 * @param 	string 	default value
	 * @return	string	'n.n.n'
	 */
	public function getVersion($extension=null,$default=''): string
	{
		if (!empty($extension))
		{
			if ($extension = $this->getExtension($extension,false)) {
				return $extension->getVersion() ?: $default;
			} else {
				return $default;
			}
		}

		return $this->pluginHeader('Version');
	}


	/**
	 * get release (stable tag/Last Updated) from the readme header
	 *
	 * @param 	string 	default value
	 * @return	string	'Release __ (__)'
	 */
	public function getRelease($default=''): string
	{
		if ($stable = $this->pluginHeader('StableTag'))
		{
			if ($update = $this->pluginHeader('LastUpdated')) {
				$stable .= ' ('.$update.')';
			}
			 return sprintf( 'Release %s', esc_attr($stable) );
		}
		return $default;
	}


	/**
	 * Parse a Semantic or Calendar version number.
	 * Semantic Version (SemVer) = major . minor . patch [- release + build]
	 * Calendar Version (CalVer) = yy . mmdd . patch [- release + build].
	 *
	 * Any version-release is _almost_ always less than version (no release)
	 * 1.2.3 > 1.2.3-Release+Build, 1.2.3-release > 1.2.3-Release+Build, 1.2.3-release < 1.2.3
	 *
	 * @see https://semver.org
	 *
	 * @param string $version
	 * @return object|null
	 *	   'original'	=> '1.2.3-Release+Build',
	 *	   'major'		=> '1',
	 *	   'minor'		=> '2',
	 *	   'patch'		=> '3',
	 *	   'release'	=> 'Release',
	 *	   'build'		=> 'Build',
	 *	   'version'	=> '1.2.3-release', (use with version compare)
	 *	   'primary'	=> '1.2.3',
	 *		__toString() = '1.2.3-release+build'
	 */
	public function getSemanticVersion(string $version = null): ?object
	{
		static $default		=	['original'=>'','major'=>'0','minor'=>'0','patch'=>'0','release'=>null,'build'=>null];
		// Semantic Version
		static $pcreSemVer	=	'/^(?P<major>0|[1-9]\d*)\.' .
								'(?P<minor>0|[1-9]\d*)' .
								'(?:\.(?P<patch>0|[1-9]\d*))?';
		// Calendar Version - since we use this versioning in extensions
		static $pcreCalVer	=	'/^(?P<major>0|\d{2})\.' .
								'(?P<minor>(1[0-2]|0[1-9])(3[01]|[12][0-9]|0[1-9]))' .
								'(?:\.(?P<patch>0|[1-9]\d*))?';
		// common -release+build (should not allow leading 0, but we do [0-9] instead of 0|[1-9])
		static $pcreRelBld	=	'(?:-(?P<release>(?:[0-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:[0-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?' .
								'(?:\+(?P<build>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

		// default input version
		if (empty($version)) $version = $this->getVersion();

		if (! preg_match($pcreSemVer.$pcreRelBld,$version,$properties,PREG_UNMATCHED_AS_NULL))
		{
			if (! preg_match($pcreCalVer.$pcreRelBld,$version,$properties,PREG_UNMATCHED_AS_NULL))
			{
				return null; // invalid version string
			}
		}

		// ensure all keys, in order
		$properties = array_merge($default,array_filter($properties));

		$properties['original'] = $properties[0];
		$properties['primary'] = $properties['version'] =
			$properties['major'].'.'.$properties['minor'].'.'.$properties['patch'];

		// version includes release (lc)
		if (isset($properties['release']))
		{
			$properties['version'] .= '-'.strtolower($properties['release']);
		}

		// return a stringable object with filtered properties
		return new class($properties)
			{
				public $original,$major,$minor,$patch,$release,$build,$primary,$version;
				function __construct($p) {
					foreach ($p as $k=>$v) {if (is_string($k)) $this->$k=$v;}
				}
				function __toString() {
					return $this->version.rtrim('+'.strtolower($this->build),'+');
				}
			};
	}


	/**
	 * customized wp_kses
	 *
	 * @param string   $content				Text content to filter.
	 * @param array[]  $allowed_html		An array of allowed HTML elements and attributes,
	 * @param string[] $allowed_protocols	An array of allowed URL protocols.
	 * @return string Filtered content containing only the allowed HTML.
	 */
	public function wp_kses( string $content, array $allowed_html = [], $allowed_protocols = [] ): string
	{
		static $allowed_tags = null;

		if (is_null($allowed_tags))
		{
			$allowed_tags = wp_kses_allowed_html('post');
			$allowed_tags['datalist']	= _wp_add_global_attributes(true);
			$allowed_tags['option']		= _wp_add_global_attributes(['name'=>true,'value'=>true,'label'=>true]);
			$allowed_tags['input']		= _wp_add_global_attributes(['name'=>true,'value'=>true,'type'=>true]);
			$allowed_tags['output']		= _wp_add_global_attributes(['name'=>true,'form'=>true,'for'=>true]);
			$allowed_tags['abbr']		= _wp_add_global_attributes(['title'=>true]);
			$allowed_tags['form']		= false;
		}

		$allowed_html = array_filter(array_merge($allowed_tags,$allowed_html));

		return wp_kses($content,$allowed_html,$allowed_protocols);
	}


	/**
	 * safeEcho - only echo output when not wp_doing_ajax(), prevents interference with multiple/auto installs
	 *
	 * @param string $string to echo
	 * @return	bool
	 */
	public function safeEcho($string): bool
	{
		if (!$this->doing_ajax() && !wp_doing_cron())
		{
			echo $string;
			return true;
		}
		return false;
	}


	/**
	 * reload the current page
	 *
	 * @param bool $die true to die()
	 * @return	void
	 */
	public function page_reload(bool $die = false): void
	{
		if (! headers_sent())
		{
		//	nocache_headers();
		//	header('Location: '.wp_sanitize_redirect($_SERVER['REQUEST_URI']));
			wp_safe_redirect($_SERVER['REQUEST_URI']);
		}
		else
		{
			echo "\n<script>window.location.replace('".wp_sanitize_redirect($_SERVER['REQUEST_URI'])."')</script>\n";
		}
		if ($die) die();
	}


	/**
	 * redirect the current page
	 *
	 * @param string $url destination url
	 * @param int $status http status (302)
	 * @param bool $die true to die()
	 * @return	void
	 */
	public function page_redirect(string $url, $status = 302, bool $die = false): void
	{
		if (! headers_sent())
		{
			wp_redirect($url,$status);
		}
		else
		{
			echo "\n<script>window.location.assign('".wp_sanitize_redirect($url)."')</script>\n";
		}
		if ($die) die();
	}


	/**
	 * output access denied response
	 *
	 * @example wp_die( $this->access_denied( 'access_denied' ) );
	 *
	 * @param string 	$logMsg to log error message
	 * @param string 	$message override default message
	 * @param int 		$status override default status
	 * @return WP_Error
	 */
	public function access_denied(string $logMsg='', int $status=0, string $message='')
	{
		if (!$status)
		{
			$status = (http_response_code() !== 200) ? http_response_code() : 403;
		}

		$message = sprintf("%s (%s)",
			 __($message ?: 'Sorry, your request could not be processed', $this->PLUGIN_TEXTDOMAIN ),
			 strtolower(__(get_status_header_desc($status), $this->PLUGIN_TEXTDOMAIN))
		);

		if ($logMsg)
		{
			$this->logError(get_status_header_desc($status).' - '.$logMsg, __FUNCTION__);
			\error_log(__FUNCTION__.': '.$logMsg.'; '.'status '.$status.', '.$this->getVisitorIP().', '.$this->varServer('request_uri'));
		}

		while (ob_get_level()) {ob_end_clean();}
		if (! headers_sent())
		{
			$send_headers = function() use($status)
			{
				header_remove('Set-Cookie');
				header_remove('Link');
				nocache_headers();
				http_response_code( $status );
			};
			if (did_action('send_headers')) {
				$send_headers();
			} else {
				add_action('send_headers', fn()=>$send_headers()); // WP hiccups on variable function (Undefined array key)
			}
		}
		return new \WP_Error(__FUNCTION__, $message, ['status' => $status, 'log' => $logMsg]);
	}


	/**
	 * is this a test or live production site
	 *
	 * @param	bool	$site_is_test false=live, true=test on first call to override option
	 * @return	bool	sets $site_is_test
	 */
	public function isTestSite(/* $site_is_test=null */): bool
	{
		static $site_is_test = null;
		if ( is_bool($site_is_test) )
		{
			return $site_is_test;
		}

		$site_is_test = (func_num_args() > 0) ? func_get_arg(0) : null;
		if ( is_bool($site_is_test) )
		{
			$getEnv = ($site_is_test) ? 'test' : 'live';
		}
		else
		{
			$getEnv = ($this->is_option('siteEnvironment',self::ENVIRONMENT_LIVE)) ? 'live' : 'test';
		}

		/**
		 * filter {classname}_get_environment to override environment setting (test/live)
		 * @param	string	environment (test|live)
		 * @return	string
		 */
		$setEnv = strtolower( $this->apply_filters( 'get_environment', $getEnv ) );
		if ( $setEnv != $getEnv)
		{
			$environment = ($setEnv == 'live') ? self::ENVIRONMENT_LIVE : self::ENVIRONMENT_TEST;
			$this->update_option('siteEnvironment',$environment);
		}

		$site_is_test = ($setEnv != 'live');
		return $site_is_test;
	}


	/**
	 * clear cache and transient data
	 *
	 * @param	bool	full cache flush
	 * @return	void
	 */
	public function flush_caches(bool $fullFlush=false): void
	{
		if ($this->get_transient('flush_cache_lock')) return;
		$this->set_transient('flush_cache_lock',time(),MINUTE_IN_SECONDS);

		$message = 'cache';
		if (method_exists($this,'deleteTransients') && $fullFlush && !wp_using_ext_object_cache())
		{
			$this->deleteTransients($this->is_network_admin());
			$message .= ' & transient';
		}

		$caches = array();

		//$this->purge_expired_transients();
		// use WP function
		\delete_expired_transients(true);
		$caches[] ='Expired Transients';

		// WP object cache and plugins that use the api
		if (!wp_using_ext_object_cache() && function_exists('\wp_cache_flush')) {
			\wp_cache_flush();
			$caches[] = 'WP Object Cache';
			$this->logDebug('wp_cache_flush',__METHOD__);
		}

		// WP update cache
		if (function_exists('\wp_clean_update_cache')) {
			\wp_clean_update_cache();
			$caches[] = 'WP Update Cache';
			$this->logDebug('wp_clean_update_cache',__METHOD__);
		}

		// Cache_Enabler
		if (class_exists('\Cache_Enabler')) {
			\do_action( 'cache_enabler_clear_site_cache' );
			\do_action( 'ce_clear_cache' );
			$caches[] = 'Cache Enabler';
			$this->logDebug('Cache_Enabler',__METHOD__);
		}
		// Autoptimize
		if (method_exists('\autoptimizeCache','clearall')) {
			\autoptimizeCache::clearall();
			$caches[] = 'Autoptimize';
			$this->logDebug('autoptimizeCache',__METHOD__);
		}
		// W3 Total Cache
		if (function_exists('\w3tc_cache_flush')) {
			\w3tc_cache_flush();
			$caches[] = 'W3 Total Cache';
			$this->logDebug('w3tc_cache_flush',__METHOD__);
		}
		// WP-Optimize
		if (function_exists('\wpo_cache_flush')) {
			\wpo_cache_flush();
			$caches[] = 'WP-Optimize';
			$this->logDebug('wpo_cache_flush',__METHOD__);
		}
		// WP Rocket
		if (function_exists('\rocket_clean_domain')) {
			\rocket_clean_domain();
			$caches[] = 'WP Rocket';
			$this->logDebug('rocket_clean_domain',__METHOD__);
		}
		// WP Super Cache
		if (function_exists('\wp_cache_clear_cache')) {
			\wp_cache_clear_cache(get_current_blog_id());
			$caches[] = 'WP Super Cache';
			$this->logDebug('wp_cache_clear_cache',__METHOD__);
		}
		// $this used to be an action (with no return value)
		//$this->do_action('after_flush_caches');
		$actions = $this->apply_filters('after_flush_caches',$caches);
		if (!empty($actions)) $caches = $actions;

		$message	= sprintf("The %s cleanup action has been triggered.",$message);
		$more		= 'Caches cleared: '.implode(', ',$caches);
		$this->add_admin_notice($message,'success',$more);
		$this->logDebug($more,$message);
	}


	/**
	 * purge expired transients for current blog
	 */
	public function purge_expired_transients()
	{
	/*
		global $wpdb;

		$expired    = time() - HOUR_IN_SECONDS;
		$table      = ($this->is_network_admin()) ? $this->wpdb->sitemeta : $this->wpdb->options;

		// delete expired transients
		$sql = "
				delete from t1, t2 using $table t1
				join $table t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
				where (t1.option_name like '\_transient\_timeout\_%' or t1.option_name like '\_site\_transient\_timeout\_%')
				  and t1.option_value < '$expired'
		";
		$wpdb->query($sql);

		// delete orphaned transient expirations
		$sql = "
				delete from $table
				where (option_name like '\_transient\_timeout\_%' or option_name like '\_site\_transient\_timeout\_%')
				  and option_value < '$expired'
		";
		$wpdb->query($sql);
	*/
		\delete_expired_transients(true);
	}


	/**
	 * is value true? true,on,yes,1
	 *
	 * @param	string	$value value to check
	 * @return	bool
	 */
	public function isTrue($value): bool
	{
		return (\filter_var($value,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE) === true);
	}


	/**
	 * is value false? - false,off,no,0
	 *
	 * @param	string	$value value to check
	 * @return	bool
	 */
	public function isFalse($value): bool
	{
		return (\filter_var($value,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE) === false);
	}


	/**
	 * detect an ajax request
	 *
	 * @since 2.6.1
	 * @return	bool
	 */
	public function doing_ajax(): bool
	{
		static $doing_ajax = null;
		if (is_null($doing_ajax)) {
			$doing_ajax = (wp_doing_ajax() || $this->varServer("HTTP_X_REQUESTED_WITH") == "XMLHttpRequest");
		}
		return $doing_ajax;
	}


	/**
	 * now an alias to $tthis->doing_ajax()
	 * @deprecated use $this->doing_ajax()
	 *
	 * @return	bool
	 */
	public function isAjaxRequest(): bool
	{
		\_deprecated_function( __FUNCTION__, '2.6.1', 'doing_ajax()');
		return $this->doing_ajax();
	}


	/**
	 * get the current url based on WP request
	 * @since 3.0
	 */
	public function getRequestURL(): string
	{
		global $wp;
		static $url = null;
		if ( ! $url )
		{
			$url = (is_null($wp)) ? $this->currentURL()
								  : add_query_arg( $wp->query_vars, network_home_url( $wp->request ) );
		}
		return $url;
	}


	/**
	 * get the current url part(s) based on WP request
	 *
	 * @since 3.0
	 * @param int PHP url part constant
	 */
	public function getRequestParts($part=null): string|array
	{
		static $url_parts = null;
		if ( ! $url_parts )
		{
			$url_parts = parse_url( $this->getRequestURL() );
		}
		if (!is_null($part)) {
			$part = match($part) {
				PHP_URL_SCHEME 		=> 'scheme',
				PHP_URL_HOST		=> 'host',
				PHP_URL_PORT		=> 'port',
				PHP_URL_USER		=> 'user',
				PHP_URL_PASS		=> 'pass',
				PHP_URL_PATH		=> 'path',
				PHP_URL_QUERY		=> 'query',
				PHP_URL_FRAGMENT 	=> 'fragment',
			};
			return $url_parts[$part];
		}
		return $url_parts;
	}


	/**
	 * Returns the request scheme
	 * @since 3.2
	 */
	public function getRequestScheme(): string
	{
		global $wp;
		static $url_scheme = null;
		if ( ! $url_scheme )
		{
			$url_scheme = $this->getRequestParts( PHP_URL_SCHEME );
		}
		return $url_scheme;
	}


	/**
	 * Returns the request host
	 * @since 3.0
	 */
	public function getRequestHost(): string
	{
		static $url_host = null;
		if ( ! $url_host )
		{
			$url_host = $this->getRequestParts( PHP_URL_HOST );
		}
		return $url_host;
	}


	/**
	 * get the current url path based on WP request
	 * @since 3.0
	 */
	public function getRequestPath(): string
	{
		static $url_path = null;
		if ( ! $url_path )
		{
			$url_path = $this->getRequestParts( PHP_URL_PATH );
		}
		return $url_path;
	}


	/**
	 * get the the request origin
	 *
	 * @since 3.0
	 * @return	string the origin host
	 */
	public function getRequestOrigin(): string
	{
		static $origin = false;
		if ( $origin === false )
		{
			if ($origin = $this->varServer('HTTP_ORIGIN')) {
			} else if ($origin = $this->varServer('HTTP_REFERER')) {
				$origin = parse_url($origin);
				$origin = $origin['scheme'].'://'.$origin['host'];
			} else {
				$origin = $this->getRequestScheme() . '://' . gethostbyaddr($this->getVisitorIP());
			}
		}
		return $origin;
	}


	/**
	 * allow CORS origin
	 *
	 * @since 3.2
	 * @param string origin url (optional)
	 * @return	bool
	 */
	public function allow_request_origin($origin=null): string
	{
		if (empty($origin)) $origin = $this->getRequestOrigin();
		add_filter( 'http_origin', function() use ($origin) {
			return $origin;
		});
		add_filter( 'allowed_http_origins', function($allowed) use ($origin) {
			$allowed[] = $origin;
			return $allowed;
		});
	}


	/**
	 * get the current url based on http request
	 *
	 * @return	string the full url of the current request
	 */
	public function currentURL(): string
	{
		return sprintf('%s://%s%s',
				(is_ssl()) ? 'https' : 'http',
				$this->varServer('HTTP_HOST'),
				$this->varServer('REQUEST_URI')
		);
	}


	/**
	 * Get the page "name"
	 *
	 * @param int|WP_Post|null $post, if null return uri name
	 * @param bool $permalink get post permalink name
	 * @return string|null the requested page name (sans extension)
	 */
	public function getPageName($post=null, $permalink=false)
	{
		if ($post)
		{
			if ($permalink)
			{
				if ($post = get_permalink($post))
				{
					$name = parse_url($post);
					$name = trim(basename($name['path']));
					$name = str_replace('.php','',basename($name));
					return $name;
				}
			}
			if ($post = get_post($post))
			{
				return $post->post_name;
			}
		}
		else
		{
			$name = explode('?',$this->varServer('REQUEST_URI'));
			$name = trim($name[0],'/');
			$name = (empty($name)) ? 'index' : basename($name,'.php');
			return $name;
		}
		return null;
	}


	/**
	 * get settings name (i.e. the page for setting options)
	 *
	 * @return	string	the settings slug name
	 */
	public function getSettingsSlug($tab=null): string
	{
		return strtolower($this->className) .
		//		($this->is_network_admin() ? '-network' : '-site') .
				'-settings' .
		//		($tab ? '-'.$this->toKeyString($tab) : '');
				($tab ? '&tab='.$this->toKeyString($tab) : '');
	}


	/**
	 * when we're on our settings page
	 *
	 * @param string $isTab check specific tab name
	 * @return	bool
	 */
	public function isSettingsPage($isTab = null): bool
	{
		return false;
	}


	/**
	 * get settings link (i.e. the page for setting options)
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $tab add tab name to url
	 * @param string $name link name ('Settings')
	 * @param string $title title ('Settings')
	 * @return	string	the settings link
	 */
	public function getSettingsLink($plugin=true,$tab=null,$name='Settings',$title='Settings'): string
	{
		if ($plugin === true)
		{
			$pluginId = $this->pluginHeader('Title');
		}
		else if (is_array($plugin))
		{
			if (isset($plugin['Name'])) $pluginId = $plugin['Name'];
		}
		else
		{
			$pluginId = $plugin;
		}
		if (!is_string($pluginId)) $pluginId = '';

		$link = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$this->getSettingsURL($plugin,$tab),
				esc_attr( sprintf( __( "%s {$title}", $this->PLUGIN_TEXTDOMAIN ), $pluginId ) ),
				__( $name, $this->PLUGIN_TEXTDOMAIN )
		);
		return $link;
	}


	/**
	 * get settings link (i.e. the page for setting options)
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $tab add tab name to url
	 * @return string the settings link
	 */
	public function getSettingsURL($plugin=true,$tab=null): string
	{
		$query = [ 'page' => $this->getSettingsSlug($tab) ];

		return ($this->is_network_admin())
			? esc_url( add_query_arg( $query, network_admin_url( 'admin.php' ) ) )
			: esc_url( add_query_arg( $query, self_admin_url( 'admin.php' ) ) );
	}


	/**
	 * get documentation link (i.e. the home/documentation page for this plugin)
	 * requires 'Plugin URI' in plugin header
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $permalink uri (/sample-post, ?p=nnn, /2022/08/26/sample-post/, etc.)
	 * @param string $name link name ('Docs')
	 * @param string $title title ('Documentation')
	 * @return	string	the Documentation link
	 */
	public function getDocumentationLink($plugin=true,$permalink=null,$name='Docs',$title='Documentation'): string
	{
		if ($plugin === true)
		{
			$pluginId = $this->pluginHeader('Title');
		}
		else if (is_array($plugin))
		{
			if (isset($plugin['Name'])) $pluginId = $plugin['Name'];
		}
		else  if (is_scalar($plugin))
		{
			$pluginId = $plugin;
		}
		else $pluginId = '';

		$link = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$this->getDocumentationURL($plugin,$permalink),
				esc_attr( sprintf( __( "%s {$title}", $this->PLUGIN_TEXTDOMAIN ), $pluginId ) ),
				__( $name, $this->PLUGIN_TEXTDOMAIN )
		);
		return $link;
	}


	/**
	 * get documentation url (i.e. the home/documentation page for this plugin)
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $permalink uri (/sample-post, ?p=nnn, /2022/08/26/sample-post/, etc.)
	 * @return	string	the Documentation link
	 */
	public function getDocumentationURL($plugin=true,$permalink=null): string
	{
		if ($plugin === true)
		{
			$url = $this->pluginHeader('PluginURI');
		}
		else if (is_array($plugin))
		{
			if (isset($plugin['PluginURI'])) $url = $plugin['PluginURI'];
		}
		else  if (is_scalar($plugin))
		{
			$url = $plugin;
		}
		else $url = '';

		if ($permalink) $url = rtrim($url,'/').$permalink;

		return esc_url( $url );
	}


	/**
	 * get WordPress support link for this plugin
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $slug uri plugin slug
	 * @param string $name link name ('Support')
	 * @param string $title title ('Support')
	 * @return	string	the Support link
	 */
	public function getSupportLink($plugin=true,$slug=null,$name='Support',$title='Support'): string
	{
		if ($plugin === true)
		{
			$pluginId = $this->pluginHeader('Title');
		}
		else if (is_array($plugin))
		{
			if (empty($slug) && isset($plugin['slug'])) $slug = basename($plugin['slug']);
			if (isset($plugin['Name'])) $pluginId = $plugin['Name'];
		}
		else if (is_scalar($plugin))
		{
			$pluginId = $plugin;
		}
		else $pluginId = '';

		if (empty($slug)) $slug = $this->PLUGIN_SLUG;
		$url = "https://wordpress.org/support/plugin/{$slug}";

		$link = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$this->getSupportURL($plugin,$slug),
				esc_attr( sprintf( __( "%s {$title}", $this->PLUGIN_TEXTDOMAIN ), $pluginId ) ),
				__( $name, $this->PLUGIN_TEXTDOMAIN )
		);
		return $link;
	}


	/**
	 * get WordPress support URL for this plugin
	 *
	 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
	 * @param string $slug uri plugin slug
	 * @return	string	the Support URL
	 */
	public function getSupportURL($plugin=true,$slug=null): string
	{
		if (is_array($plugin))
		{
			if (empty($slug) && isset($plugin['slug'])) $slug = basename($plugin['slug']);
		}

		if (empty($slug)) $slug = $this->PLUGIN_SLUG;
		return esc_url( "https://wordpress.org/support/plugin/{$slug}" );
	}


	/**
	 * when we're on the plugins page
	 *
	 * @return	bool
	 */
	public function isPluginsPage(): bool
	{
		global $pagenow;
		static $is_plugins_page = null;
		if (is_null($is_plugins_page))
		{
			if (wp_doing_ajax() && defined('WP_PLUGIN_URL'))
			{
				$is_plugins_page = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], WP_PLUGIN_URL) !== false);
			}
			else
			{
				$is_plugins_page = ( $pagenow == 'plugins.php' ); // ? true : ($this->is_admin() && $this->getPageName() == 'plugins');
			}
		}
		return $is_plugins_page;
	}


	/**
	 * get or check the current screen name
	 *
	 * @param	string	name to check for
	 * @return	null|bool|string
	 */
	public function isCurrentScreen($screen = null)
	{
		if (! function_exists('get_current_screen')) return null;
		$currentScreen = get_current_screen();
		if ( $screen === null ) return $currentScreen->id;
		return ( strtolower($currentScreen->id) == strtolower($screen) );
	}


	/**
	 * get a post (or page) by slug
	 *
	 * @param string $slug the name to search for
	 * @param array $options augment/override query	 options
	 * @return object the first matching post
	 */
	public function get_post_by_slug(string $slug, $options=[])
	{
		$query = new \WP_Query( array_merge(
			array(
				'name'			=> $slug,
				'post_type'		=> 'any',
				'post_status'	=> ['publish','private']
			), $options )
		);
		$result = $query->have_posts() ? reset($query->posts) : null;
		wp_reset_postdata();
		return $result;
	}


	/**
	 * get a template page header
	 *
	 * @param string $name special header name
	 * @return string the header content
	 */
	public function get_page_header(string $name = null)
	{
		ob_start();
		\get_header( $name ); // header-<name>.php
		return ob_get_clean();
	}


	/**
	 * get a template page footer
	 *
	 * @param string $name special footer name
	 * @return string the footer content
	 */
	public function get_page_footer(string $name = null)
	{
		ob_start();
		\get_footer( $name ); // footer-<name>.php
		return ob_get_clean();
	}


	/**
	 * get a template page part
	 *
	 * @param string $slug template file name {$slug}.php
	 * @param string $name special name/part {$slug}-{$name}.php
	 * @return string the template content
	 */
	public function get_page_template(string $slug, string $name = null, $args = [])
	{
		ob_start();
		\get_template_part( $slug, $name, $args );
		return ob_get_clean();
	}


	/**
	 * Check an IP address against list of IPs/subnets
	 *
	 * @param string $ipAddress IPv4 or IPv6 address
	 * @param array  $ipList list of IP addresses or subnets (cidr)
	 * @return bool
	 */
	public function isIpInList(string $ipAddress, array $ipList): bool
	{
		return !empty($ipList) && \EarthAsylumConsulting\Helpers\ipUtil::checkIp($ipAddress, $ipList);
	}


	/**
	 * Get the visitor's IP address
	 *
	 * @return	string	IP address
	 */
	public function getVisitorIP(): ?string
	{
		static $remote_ip = null;

		if ($remote_ip) return $remote_ip;

		$remote_ip = \EarthAsylumConsulting\Helpers\ipUtil::getRemoteIP();

		/**
		 * filter {classname}_set_visitor_ip get the visitor's IP address
		 * @param	string	$remote_ip IP address
		 * @return	string	IP address
		 */
		$remote_ip = $this->apply_filters( 'set_visitor_ip', $remote_ip );

		if (!empty($remote_ip)) {
			$this->setVariable('remote_ip',$remote_ip);
			return $remote_ip;
		}
		return null;
	}


	/**
	 * Get the visitor's country from the language header
	 *
	 * @param	string	2 character country code default
	 * @return	string	2 character country code (default=US)
	 */
	public function getVisitorCountry(string $default='US'): string
	{
		if ($country = $this->getVariable('remote_country')) return $country;

		if ($country = $this->varServer('GEOIP_COUNTRY_CODE')			// kinsta, et.al.
					?: $this->varServer('CF-IPCOUNTRY')					// cloudflare
					?: $this->varServer('CLOUDFRONT-VIEWER-COUNTRY')	// aws cloudfront
					?: $this->varServer('X-APPENGINE-COUNTRY')			// google app engine
					?: $this->varServer('X-GEO-COUNTRY')				// Acquia cloud
		){
			// trust country code set by server/proxy
			if ($country == 'XX' || $country == 'ZZ') {
				$country = '';
			} else {
				$default = $country;
			}
		}

		if (!$country && ($httpLang = $this->varServer('HTTP_ACCEPT_LANGUAGE')))
		{
			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $httpLang, $lang_parse);

			if (isset($lang_parse[1])) {
				// create a list like "en-us" => 0.8
				$langs = array_combine($lang_parse[1], $lang_parse[4]);
				// set default to 1 for any without q factor
				foreach ($langs as $lang => $val) {
					if ($val === '') $langs[$lang] = 1;
				}
				// sort list based on value
				arsort($langs, SORT_NUMERIC);
				// find a country code
				foreach ($langs as $lang => $val) {
					list ($l,$c) = explode('-',$lang.'-');	// en-us
					if ($c) $default = strtoupper($c);		// US
				}
			}
		}

		/**
		 * filter {classname}_set_visitor_country get the visitor's country
		 * @param	string	$country country code (if set from header)
		 * @param	string	$default country code (from accept_language)
		 * @return	string	country code
		 */
		$country = strtoupper(
			$this->apply_filters( 'set_visitor_country', $country, $default )
			?: $default
		);

		$this->setVariable('remote_country',$country); // if visitor returns here
		return $country;
	}


	/**
	 * Get a unique visitor id
	 *
	 * @param bool $forRequest unique to this request
	 * @return	string	ID
	 */
	public function getVisitorId(bool $forRequest=false): string
	{
		static $setCookie = true;

		// allow array for old cookie name
		$cookieName = ['wp-'.$this->className.'-id',$this->className.'id'];
		/**
		 * filter {classname}_visitor_cookie_name set the visitor cookie name
		 * @param	string	visitor cookie name
		 * @return	string
		 */
		$cookieName[0] = $this->apply_filters( 'visitor_cookie_name', $cookieName[0] );

		$value = (!$forRequest)
			? $this->getVariable($cookieName[0] ?: $this->get_cookie($cookieName))
			: null;

		$cookieName = $cookieName[0];

		if (!$value)
		{
			$value =	/*get_current_user_id() .*/
						$this->getVisitorIP() .
						$this->varServer('HTTP_ACCEPT_ENCODING') .
						$this->varServer('HTTP_ACCEPT_LANGUAGE') .
						$this->varServer('HTTP_USER_AGENT') .
						$this->varServer('REMOTE_PORT');
			if ($forRequest) {
				$value .= $this->pluginHeader('RequestTime');
			} else {
				$this->setVariable('is_new_visitor',true);
			}
			/**
			 * filter {classname}_set_visitor_id get the visitor's id
			 * @param	string	$id
			 * @param	string	visitor cookie name
			 * @return	string	id
			 */
			$value = $this->apply_filters( 'set_visitor_id', $value, $cookieName );

			$value = sha1($value);
		}

		/**
		 * filter {classname}_enable_visitor_cookie to enable setting visitor cookie
		 * @param	bool		false
		 * @param	string		visitor cookie name
		 * @return	bool|int	true|false or #days (30)
		 */
		$setCookie = ($setCookie && $this->apply_filters( 'enable_visitor_cookie', false, $cookieName ));

		if ($setCookie && !$forRequest)
		{
			$days = (is_int($setCookie)) ? $setCookie : 30;
			if (\did_action('init')) {
				$this->setVisitorCookie($cookieName,$value,$days);
			} else {
				\add_action('init', function() use ($cookieName,$value,$days) {
					$this->setVisitorCookie($cookieName,$value,$days);
				});
			}
			$setCookie = false;
		}
		$this->setVariable($cookieName,$value);
		return $value;
	}


	/**
	 * Set the visitor cookie (on or after WP init)
	 *
	 * @param string $cookieName
	 * @param string $value
	 * @param int $days
	 * @return	void
	 */
	private function setVisitorCookie(string $cookieName, string $value, int $days): void
	{
		if (!headers_sent())
		{
			$this->set_cookie($cookieName, $value, "{$days} Days", [/* default options */],
				[
					'category' => 'preferences',
					'function' => __( '%s sets this cookie to assign a unique visitor ID to track preferences and activity.', $this->PLUGIN_TEXTDOMAIN )
				]
			);
		}
	}


	/**
	 * Check visitor cookie
	 *
	 * @return bool
	 */
	public function isNewVisitor(): bool
	{
		static $isNew = null;
		if (!is_null($isNew)) return $isNew;

		$this->getVisitorId();

		// get/set per-session (if session manager active)
		$isNew	= $this->getVariable('is_new_visitor');
		/**
		 * filter {classname}_is_new_visitor is this a new visitor
		 * @param	bool
		 * @return	bool
		 */
		$isNew = $this->apply_filters( 'is_new_visitor', $isNew );
		$this->setVariable('is_new_visitor',$isNew);
		return ($isNew) ? true : false;
	}


	/**
	 * Query MySQL DB for its version
	 *
	 * @deprecated use $this->wpdb->db_server_info()
	 *
	 * @return	string|false
	 */
	public function getMySqlVersion(): string
	{
		return $this->wpdb->db_server_info();
	//	$rows = $this->wpdb->get_results('select version() as mysqlversion');
	//	if (!empty($rows)) {
	//		return $rows[0]->mysqlversion;
	//	}
	//	return false;
	}


	/**
	 * retrieve a stored variable
	 *
	 * @param	string	$key stored key
	 * @param	mixed	$default default value
	 * @return	mixed	stored variable (unserialized)
	 */
	public function getVariable( string $key, $default=null )
	{
		if ($value = $this->pluginHeader($key)) return $value;

		$key = \sanitize_key( $key );

		/**
		 * filter {classname}_get_variable to get a stored variable
		 * @param	string	$key stored key
		 * @return	mixed	stored variable (unserialized)
		 */
		if ($this->has_filter( 'get_variable' )) {
			$value = $this->apply_filters( 'get_variable', $default, $key );
		} else {
			$value = \apply_filters( 'eacDoojigger_get_variable', $default, $key );
		}
		return maybe_unserialize($value);
	}


	/**
	 * retrieve a stored variable
	 *
	 * @deprecated use getVariable()
	 *
	 * @param	string	$key stored key
	 * @param	mixed	$default default value
	 * @return	mixed	stored variable (unserialized)
	 */
	public function get( string $key, $default=null )
	{
		\_deprecated_function( __FUNCTION__, '0.0.5', 'getVariable()');
		return $this->getVariable( $key, $default );
	}


	/**
	 * set a stored variable
	 *
	 * @param	string	$key stored key
	 * @param	mixed	$value stored variable
	 * @return	string	stored variable (serialized)
	 */
	public function setVariable( string $key, $value )
	{
		$key = \sanitize_key( $key );
		/**
		 * filter {classname}_set_variable to set a stored variable
		 * @param	mixed	$value stored variable
		 * @param	string	$key stored key
		 * @return	string	stored variable
		 */
		if ($this->has_filter( 'set_variable' )) {
			$value = $this->apply_filters( 'set_variable', $value, $key );
		} else {
			$value = \apply_filters( 'eacDoojigger_set_variable', $value, $key );
		}
		return $value;
	}


	/**
	 * set a stored variable
	 *
	 * @deprecated use setVariable()
	 *
	 * @param	string	$key stored key
	 * @param	mixed	$value stored variable
	 * @return	string	stored variable (serialized)
	 */
	public function set( string $key, $default=null )
	{
		\_deprecated_function( __FUNCTION__, '0.0.5', 'setVariable()');
		return $this->setVariable( $key, $default );
	}


	/**
	 * implode an associative array into a string
	 *
	 * @param	string	$separator the glue value
	 * @param	array	$array the associative array to implode
	 * @param	string	$delimiter the key=value delimiter
	 * @return	string	the imploded string
	 */
	public function implode_with_keys(string $separator, array $array, string $delimiter='='): string
	{
		array_walk($array, function(&$value,$key) use ($delimiter)
			{
				$value = trim($key) . $delimiter . trim($value);
			}
		);
		return implode($separator, $array);
	}


	/**
	 * explode a string into an associative array
	 *
	 * @param	string		$separator the glue value
	 * @param	string[]	$string the string value to explode | array of $string
	 * @param	string		$delimiter the key=value delimiter
	 * @return	array		the exploded array
	 */
	public function explode_with_keys(string $separator, string|array $string, string $delimiter='='): array
	{
		$output = array();
		if (is_array($string))
		{
			foreach ($string as $value) {
				$value = $this->explode_with_keys($separator,$value,$delimiter);
				foreach ($value as $k=>$v) $output[$k] = $v;
			}
			return $output;
		}

		foreach (explode($separator, $string) as $key => $value)
		{
			if (strpos($value,$delimiter) > 0) {
				list($key,$value) = explode($delimiter,$value,2);
			}
			$output[trim($key)] = trim($value);
		}
		return $output;
	}


	/**
	 * parse delimited string to arrray
	 *
	 * @param	string	$string delimited by \n or $delimiters
	 * @param	array 	$delimiters split string on these delimiters
	 * @param 	callable $callback array_map callback function (i.e. sanitize_textarea_field)
	 * @return	array
	 */
	public function text_to_array(string $string, array $delimiters=[';'], $callback = 'trim'): array
	{
		$delimiters = (array)$delimiters;
		return array_filter(
			array_map($callback, explode("\n", str_replace($delimiters,"\n",$string) ) )
		);
	}


	/**
	 * convert a display title/name to a key/class string
	 *
	 * @param	string 	$string	to be converted
	 * @param	string 	$with	to replace ' '
	 * @return	string
	 */
	public function toKeyString(string $string, string $with = '-'): string
	{
		return \sanitize_key(str_replace(' ',$with,$string));
	}


	/**
	 * Simple minify for JS or CSS content.
	 * Strips comments, leading/ttrailing white-space, new-lines.
	 * Warning: not all js/css strings may succesfully pass through this regex!
	 *
	 * @param	string	js/css content
	 * @param	bool|string	strip new-line (set false to preserve line-endings)
	 * @return	string	minified content
	 */
	public function minifyString(string $content,$stripNL=true): string
	{
		$search =
			[
				'|/\*\s[\s\S\t\n\r]*?\*/|',		// remove comment blocks	(/* ... */)
				'|//\s[\s\S]*?$|m',				// remove line-ending comments (//{space}...)
				'|^\s*|m',						// remove leading whitespace
				'|\s*$|m',						// remove trailing whitespace
				'|\t|',							// remove tabs
			];
		$search[] = ($stripNL)
			?	'|\R|m'							// remove line-endings
			:	'|^\R|m';						// remove blank lines
		return preg_replace( $search, '', str_replace('&amp;&amp;','&&',$this->wp_kses($content,[])) );
	}


	/**
	 * Increases or decreases the brightness of a color by a percentage of the current brightness.
	 *
	 * @param	string	$hexCode		Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
	 * @param	float	$adjustPercent	A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
	 *
	 * @author	maliayas (https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php)
	 *
	 * @return	string
	 */
	public function modifyColor($hexCode, $adjustPercent)
	{
		$hexCode = ltrim($hexCode, '#');
		if (strlen($hexCode) == 3) {
			$hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
		}
		$hexCode = array_map('hexdec', str_split($hexCode, 2));
		foreach ($hexCode as & $color) {
			$adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
			$adjustAmount = ceil($adjustableLimit * $adjustPercent);
			$color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
		}
		return '#' . implode($hexCode);
	}


	/**
	 * parse options/attributes to a key=value array using SimpleXMLElement
	 *
	 * @param	array|string	$attributes		options/attributes -
	 *		single string		"name name" or "name=value name=value ..."
	 *		array of strings	[ "name", "name" ] or [ "name=value", "name=value", ... ]
	 *		associative array	[ "name"=>"value", "name"=>"value", ... ]
	 *		array of arrays		[ ["name"=>"value"], ["name"=>"value"], ... ]
	 * @param	bool			$inArray	true if returning arrays-in-array [ [name=>value],[...] ]
	 * @return	array			array of [name=>value] or [ [name=>value],[...] ]
	 */
	protected function parseAttributes($attributes,$inArray=false): array
	{
		if (empty($attributes)) return [];

		if (is_string($attributes))								// string of attributes
		{
			$dom = new \SimpleXMLElement("<element {$attributes} />");
		}
		else													// array of attributes
		{
			$dom = new \SimpleXMLElement("<element/>");
			foreach ((array)$attributes as $name=>$value)
			{
				if (is_int($name))								// array of...
				{
					if (is_array($value)) {						// arrays
						$name = key($value);
						$value = current($value);
						if (is_int($name)) $name = $value;		// [names]
					} else {									// strings
						list($name,$value,$check) = explode('=',$value."=x=");
						if (empty($check)) $value = $name;		// names
					}
				}
				if ($name) $dom->addAttribute($name,$value);
			}
		}

		$attributes = (array)$dom->attributes();
		$attributes = ($inArray)
			? array_chunk($attributes['@attributes'],1,true)	// array of arrays (required for admin option arrays)
			: $attributes['@attributes'];						// associative array

		//echo "<pre>parseAttributes:";print_r($attributes);echo "</pre>\n";
		return $attributes;
	}


	/**
	 * like WordPress insert_with_markers, works with multiple file types
	 *
	 * @param	string	$filename - path name to file
	 * @param	string	$marker - marker text
	 * @param	array	$insertion - line(s) to be inserted
	 * @param	string	$commentBegin - beginning of a comment (i.e. '#', ';', '//', '/*')
	 * @param	string	$commentEnd - ending of a comment (i.e. '* /')
	 * @param	bool	$insertAtTop - true to insert new block at beginning of file
	 * @return	bool
	 */
	public function insert_with_markers(string $filename, string $marker, $insertion, string $commentBegin='#', string $commentEnd='', bool $insertAtTop=false): bool
	{
		global $wp_filesystem;
		if ($fs = apply_filters('eacDoojigger_load_filesystem',$wp_filesystem))
		{
			if ( ! $fs->exists( $filename ) ) {
				if ( ! $fs->is_writable( dirname( $filename ) ) ) {
					return false;
				}
			} elseif ( ! $fs->is_writable( $filename ) ) {
				return false;
			}
		}
		else
		{
			if ( ! file_exists( $filename ) ) {
				if ( ! is_writable( dirname( $filename ) ) ) {
					return false;
				}
				if ( ! touch( $filename ) ) {
					return false;
				}
				if ($perms = fileperms( $filename )) {
					chmod( $filename, $perms | 0644 );
				}
			} elseif ( ! is_writable( $filename ) ) {
				return false;
			}
		}

		if (is_string($insertion)) {
			$insertion = explode("\n", $insertion);
		}

		$start_marker = trim( $commentBegin." BEGIN " . $marker . ' ' . $commentEnd );
		$end_marker	  = trim( $commentBegin." END " . $marker . ' ' . $commentEnd );

		if ( is_file( $filename ) ) {
			$content = trim(file_get_contents($filename));
		}

		$pattern = '/'.preg_quote($start_marker,'/').'.*'.preg_quote($end_marker,'/').'/isU';

		if (!empty($insertion)) {
			$string = $start_marker;
			$string .= PHP_EOL.implode(PHP_EOL, $insertion);
			$string .= PHP_EOL.$end_marker;
		} else {
			$string = '';
		}

		//$this->logDebug($string,__METHOD__);

		if (preg_match($pattern, $content)) {
			$content = preg_replace($pattern, $string, $content);
		} else if (!empty($string)) {
			if ($insertAtTop) {
				$content = $string.PHP_EOL.$content;
			} else {
				$content .= PHP_EOL.$string;
			}
		}

		$bytes = ($fs)
			? $fs->put_contents($filename, trim($content).PHP_EOL, FS_CHMOD_FILE)
			: file_put_contents($filename, trim($content).PHP_EOL, LOCK_EX);

		return (bool) $bytes;
	}


	/**
	 * When creating a file, find appropriate path
	 * a. where the debug log is stored
	 * b. in the upload folder
	 *
	 * @param string relative file path name or directory (end with /)
	 * @param bool $create create path/file
	 * @param string first record when creating file
	 * @return string|wp_error file path name
	 */
	public function get_output_file(string $filePath, bool $create=true, string $firstRecord='')
	{
		global $wp_filesystem;
		if (! ($fs = apply_filters('eacDoojigger_load_filesystem',$wp_filesystem))) {
			return $this->error('wp_filesystem_error','Unable to load wp_filesystem');
		}

		$pathParts 	= explode(DIRECTORY_SEPARATOR,trim($filePath,DIRECTORY_SEPARATOR));
		$filePath 	= (! str_ends_with($filePath,DIRECTORY_SEPARATOR))
					  ? array_pop($pathParts)
					  : false;

		// look for {PLUGINNAME}_OUTPUT_PATH
		$const 		= strtoupper($this->pluginName).'_OUTPUT_PATH';
		$pathName 	= (defined($const) && is_string(constant($const)))
					? realpath(constant($const)) ?: constant($const) : '';

		// look for WP_DEBUG_LOG
		if (empty($pathName))
		{
			$pathName = (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && dirname(WP_DEBUG_LOG))
					? realpath(dirname(WP_DEBUG_LOG)) ?: dirname(WP_DEBUG_LOG) : '';
		}

		// look for upload directory or ABSPATH
		if (empty($pathName))
		{
			$pathName = wp_get_upload_dir()['basedir'];
		}
		else if (str_starts_with($pathName,'.'.DIRECTORY_SEPARATOR))
		{
			$pathName = ABSPATH.substr($pathName,2);
		}

		/**
		 * filter {pluginName}_get_output_path - get pathname (directory) for output files
		 * @param string pathname
		 */
		$pathName = $this->apply_filters('get_output_path',$pathName);

		$pathName = rtrim($pathName,DIRECTORY_SEPARATOR);

		// create the directory
		foreach($pathParts as $pathFolder)
		{
			$pathName .= DIRECTORY_SEPARATOR.$pathFolder;
			if (!is_dir($pathName) && $create)
			{
				if (($fsPath = $fs->find_folder(dirname($pathName))) && $fs->is_writable($fsPath)) {
					$fsPath .= basename($pathName);
					// since we write to this not using $fs, we need onwner & group write access
					$fs->mkdir($fsPath,FS_CHMOD_DIR|0660);
				}
			}
		}

		// make sure the folder is writeable
		if (!$fs->is_writable($pathName)) {
			$error = sprintf("Unable to access %s for file %s",basename($pathName),func_get_arg(0));
			return $this->error('write_acces_required',$error,func_get_arg(0));
		}

		if (!$filePath) return $pathName;

		$filePath = $pathName . DIRECTORY_SEPARATOR . $filePath;

		// create the file
		if (!$fs->exists($filePath) && $create)
		{
			if (($fsPath = $fs->find_folder(dirname($filePath))) && $fs->is_writable($fsPath)) {
				$fsPath .= basename($filePath);
				// since we write to this not using $fs, we need onwner & group write access
				$fs->put_contents($fsPath,$firstRecord,FS_CHMOD_FILE|0660);
			}
		}

		// make sure we can write to the file (without using $fs)
		if (!$filePath || !$fs->exists($filePath) || !is_writable($filePath)) {
			$error = sprintf("Unable to access %s",func_get_arg(0));
			return $this->error('write_acces_required',$error,func_get_arg(0));
		}

		return $filePath;
	}


	/*
	 *
	 * get filtered superglobals imput
	 *
	 */


	/**
	 * Safely get $_COOKIE data using PHP filter.
	 *
	 * @example $data = $this->varCookie('cookie_name');
	 * @example $data = $this->get_cookie('cookie_name','default value');
	 *
	 * @param	string				$name the name of the cookie
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed, null if not found
	 */
	public function varCookie( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		if (func_num_args() == 1) return $this->get_cookie($name);
		$filter = $this->getFilterCallback($filter,$options);
		return filter_input(INPUT_COOKIE, $name, ...$filter);
	}

	/**
	 * Safely get $_COOKIE data using PHP filter.
	 *
	 * @deprecated - use $this->varCookie(...)
	 */
	public function _COOKIE( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		return $this->varCookie( $name, $filter, $options );
	}


	/**
	 * Safely get $_GET data if set using PHP filter
	 *
	 * @example $data = $this->varGet('get_name');
	 *
	 * @param	string				$name the name of the variable
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed, null if not found
	 */
	public function varGet( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		$filter = $this->getFilterCallback($filter,$options);
		return filter_input(INPUT_GET, $name, ...$filter);
	}

	/**
	 * Safely get $_GET data using PHP filter.
	 *
	 * @deprecated - use $this->varGet(...)
	 */
	public function _GET( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		return $this->varGet( $name, $filter, $options );
	}


	/**
	 * Safely get $_POST data if set using PHP filter
	 *
	 * @example $data = $this->varPost('post_name');
	 *
	 * @param	string				$name the name of the variable
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed, null if not found
	 */
	public function varPost( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		$filter = $this->getFilterCallback($filter,$options);
		return filter_input(INPUT_POST, $name, ...$filter);
	}

	/**
	 * Safely get $_POST data using PHP filter.
	 *
	 * @deprecated - use $this->varPost(...)
	 */
	public function _POST( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		return $this->varPost( $name, $filter, $options );
	}


	/**
	 * Safely get $_REQUEST data if set, using PHP filter
	 *
	 * @example $data = $this->varRequest('param_name');
	 *
	 * @param	string				$name the name of the variable
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed, null if not found
	 */
	public function varRequest( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		$filter = $this->getFilterCallback($filter,$options);
		if ( $result = filter_input(INPUT_GET, $name, ...$filter) )	 return $result;
		if ( $result = filter_input(INPUT_POST, $name, ...$filter) )  return $result;
		return null;
	}

	/**
	 * Safely get $_REQUEST data using PHP filter.
	 *
	 * @deprecated - use $this->varRequest(...)
	 */
	public function _REQUEST( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		return $this->varRequest( $name, $filter, $options );
	}


	/**
	 * Safely get $_SERVER or $_ENV data if set, using PHP filter
	 *
	 * @example $data = $this->varServer('server_name');
	 *
	 * @param	string				$name the name of the variable
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed, null if not found
	 */
	public function varServer( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		$filter = $this->getFilterCallback($filter,$options);
		$name = strtoupper(str_replace('-','_',$name));
		if ( $result = filter_input(INPUT_SERVER, $name, ...$filter) ) return $result;
		$httpname = 'HTTP_'.$name;
		if ( $result = filter_input(INPUT_SERVER, $httpname, ...$filter) ) return $result;
		if ( $result = filter_input(INPUT_ENV, $name, ...$filter) ) return $result;
		return null;
	}

	/**
	 * Safely get $_SERVER data using PHP filter.
	 *
	 * @deprecated - use $this->varServer(...)
	 */
	public function _SERVER( string $name, $filter = FILTER_CALLBACK, $options = null )
	{
		return $this->varServer( $name, $filter, $options );
	}


	/**
	 * to filter/sanitize a variable
	 *
	 * @example $data = $this->sanitize($variable);
	 * @example $data = $this->sanitize($variable, 'my_sanitizer');
	 * @example $data = $this->sanitize($variable, FILTER_CALLBACK, 'my_sanitizer');
	 *
	 * @param	scalar|array		$input the value to be filtered
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @return	mixed				the filtered value, false if filter failed
	 */
	public function sanitize( $input, $filter = FILTER_CALLBACK, $options = null )
	{
		if (is_array($input))
		{
			foreach ($input as &$value) {
				$value = $this->sanitize($value,$filter,$options);
			}
			return $input;
		}

		$filter = $this->getFilterCallback($filter,$options);
		return \filter_var($input, ...$filter);
	}


	/**
	 * to filter/escape a variable
	 *
	 * @example $data = $this->escape($variable);
	 * @example $data = $this->escape($variable, 'esc_attr');
	 *
	 * @param	scalar|array	$input the value to be filtered
	 * @param	callable		$callback optional callback function
	 * @return	mixed			the filtered value, false if filter failed
	 */
	public function escape( $input, $callback = 'esc_attr' )
	{
		return $this->sanitize($input,FILTER_CALLBACK,$callback);
	}


	/**
	 * to parse the PHP filter name and options
	 *
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @param	array				$args additional arguments passed to callable
	 * @return	array				[filter, options]
	 */
	public function getFilterCallback($filter = FILTER_CALLBACK, $options = null, $args = null )
	{
		// i.e. $this->sanitize('something', FILTER_CALLBACK, 'my_sanitizer');
		if ($filter == FILTER_CALLBACK)
		{
			if (empty($options)) {
				$options = ['options' => 'sanitize_text_field'];
			} else if (is_callable($options)) {
				$options = ['options' => $options];
			}
		}
		// i.e. $this->sanitize('something', 'my_sanitizer');
		else if (is_callable($filter))
		{
			if (is_int($options)) {
				$options = ['flags' => $options];
			} else if (!is_array($options)) {
				$options = [];
			}
			$options['options'] = $filter;
			$filter = FILTER_CALLBACK;
		}

		// a callable function that calls the specified function passing value and arguments
		if (is_array($args) && $filter == FILTER_CALLBACK)
		{
			$options['options'] = call_user_func(function($fn,$args)
				{
					return function($value) use($fn,$args)
					{
						return call_user_func($fn, $value, ...$args);
					};
				},
				$options['options'],$args
			);
		}

		return [ $filter, $options ];
	}


	/**
	 * alias to getFilterCallback()
	 * @deprecated use getFilterCallback()
	 *
	 * @param	int|callable		$filter the filter to use
	 * @param	int|array|callable	$options passed to the filter
	 * @param	array				$args additional arguments passed to callable
	 * @return	array				[filter, options]
	 */
	protected function _parseFilter($filter = FILTER_CALLBACK, $options = null, $args = null )
	{
		\_deprecated_function( __FUNCTION__, '2.6.1', 'getFilterCallback()');
		return $this->getFilterCallback($filter,$options,$args);
	}


	/**
	 * __get magic method allows direct access to extension methods.
	 * @example $this->extension->method()
	 *
	 * @param string $property the property name or extension name
	 * @return object|null
	 */
	public function __get($property)
	{
		if ($object = $this->getExtension($property))
		{
			return $object;
		}
		trigger_error('Undefined property: '.__CLASS__.'::'.$property,E_USER_NOTICE);
		return null;
	}


	/**
	 * call an internal plugin or extension method
	 *
	 * @param	string|array	$method the method name or [extension,method]
	 * @param	mixed			$arguments the arguments to method name
	 * @return	mixed			result of call
	 */
	public function callMethod($method, ...$arguments)
	{
		if (is_array( $method ))
		{
			return $this->callExtension( $method[0], $method[1], ...$arguments );
		}
		if (is_callable( [$this, $method] ))
		{
			return call_user_func( [$this, $method], ...$arguments );
		}
		$this->fatal($this->className,
					"Call to unknown plugin method: '{$this->className}->{$method}'",
					['class'=>$this->className,'method'=>$method,'arguments'=>$arguments]
		);
	}


	/**
	 * call an internal plugin or extension method - from a filter (ignore unknown failure)
	 *
	 * @param	string|array	$method the method name or [extension,method]
	 * @param	mixed			$arguments the arguments to method name
	 * @return	mixed			result of call
	 */
	protected function callMethodIgnore($method, ...$arguments)
	{
		if (is_array( $method ))
		{
			if ( ($object = $this->getExtension( $method[0] )) && (method_exists( $object, $method[1] )) )
			{
				return call_user_func( [$object, $method[1]], ...$arguments );
			}
			return null;
		}
		if (is_callable( [$this, $method] ))
		{
			return call_user_func( [$this, $method], ...$arguments );
		}
		return null;
	}


	/**
	 * call a specific extension method
	 *
	 * @param	string	$extension the extension name
	 * @param	string	$method the method name
	 * @param	mixed	$arguments the arguments to method name
	 * @return	mixed	result of extension method called
	 */
	public function callExtension($extension, $method, ...$arguments)
	{
		if ($extension == $this->className && method_exists( $this, $method ))
		{
			return call_user_func( [$this, $method], ...$arguments);
		}
		if ( ($object = $this->getExtension( $extension )) && (method_exists( $object, $method )) )
		{
			return call_user_func( [$object, $method], ...$arguments);
		}
		$this->fatal($this->className,
					"Call to unknown extension method: '{$extension}->{$method}'",
					['class'=>$extension,'method'=>$method,'arguments'=>$arguments]
		);
	}


	/**
	 * Execute a method in each/all loaded extension class
	 *
	 * @param	string	$method the method name
	 * @param	mixed	$arguments the arguments to method name
	 * @return	array	results from each call
	 */
	public function callAllExtensions($method, ...$arguments): array
	{
		$result = array();
		foreach ($this->extension_objects as $name => $object)
		{
			if ( $this->isExtension($object) && method_exists( $object, $method ) )
			{
				$result[$name] = call_user_func( [$object, $method], ...$arguments);
			}
		}
		return $result;
	}


	/**
	 * get class or extension object
	 *
	 * @param	string	$className	class/extension name
	 * @return	object	class object or null
	 */
	public function getClassObject(string $className=null): ?object
	{
		if (empty($className) || $className == $this->className)
		{
			$object = $this;
		}
		else
		{
			$object = $this->extension_objects[$className] ?? $this->extension_aliases[$className] ?? null;
		}

		return (is_object($object)) ? $object : null;
	}


	/**
	 * get extension, loaded and enabled
	 *
	 * @param	object|string	$extension extension class or name
	 * @param	bool			$checkEnabled check isEnabled()
	 * @return	bool|object		false or extension object
	 */
	public function getExtension($extension, bool $checkEnabled=true)
	{
		$object = (is_object($extension)) ? $extension : $this->getClassObject($extension);
		if (is_a($object,self::EXTENSION_BASE_CLASS))
		{
			if (!$checkEnabled) return $object;
			return ($object->isEnabled()) ? $object : false;
		}
		return false;
	}


	/**
	 * alias to getExtension - is extension loaded and enabled
	 *
	 * @param	object|string	$extension extension class or name
	 * @param	bool			$checkEnabled check isEnabled()
	 * @return	bool|object		false or extension object
	 */
	public function isExtension($extension, bool $checkEnabled=true)
	{
		return $this->getExtension($extension, $checkEnabled);
	}


	/**
	 *
	 * options/settings helpers
	 *
	 * Prior to version 2.0, all options where stored as individual wp_options records.
	 * Since 2.0, options are in an array loaded in _construct()  and written as a single record on shutdown.
	 * Code below includes conversion from old to new, getting old record (and deleting it).
	 * $this->reservedOptions is an array of option names that retain their individual records.
	 * Individual records always override array records. See isReservedOption() or the reserved_options filter.
	 *
	 * * wp option filters on individual options are circumvented by this change.
	 */


	/**
	 * standardize the options group display name
	 *
	 * @param	string|array	$optionGroup group name or [groupname, tabname]]
	 * @return	string|array	standardized option group name
	 */
	public function standardizeOptionGroup($optionGroup)
	{
		// prevent translation early load (Function _load_textdomain_just_in_time was called incorrectly)
		if ( ! doing_action( 'after_setup_theme' ) && ! did_action( 'after_setup_theme' ) )
		{
			// no translations
			if (is_array($optionGroup))
			{
				return [
					esc_attr(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup[0])))),
					esc_attr(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup[1])))),
				];
			}
			return esc_attr(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup))));
		}
		else
		{
			// with translations
			if (is_array($optionGroup))
			{
				return [
					esc_attr__(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup[0]))),$this->PLUGIN_TEXTDOMAIN),
					esc_attr__(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup[1]))),$this->PLUGIN_TEXTDOMAIN),
				];
			}
			return esc_attr__(ucwords(str_replace(['-','_'],' ',$this->sanitize($optionGroup))),$this->PLUGIN_TEXTDOMAIN);
		}
	}


	/**
	 * standardize the option name
	 *
	 * @param	string	$optionName option name
	 * @param	bool	$toLC convert to lower case
	 * @return	string	standardized option name
	 */
	public function standardizeOptionName(string $optionName, $toLC=true): string
	{
		if ($toLC)
		{
			return $this->sanitize( strtolower($this->unprefixOptionName($optionName)) );
		}
		return $this->sanitize( $this->unprefixOptionName($optionName) );
	}


	/**
	 * add additional options (values) for the plugin (or extension)
	 *
	 * @param	string|array	$optionGroup group name or [groupname, tabname]]
	 * @param	array			$optionMeta group option meta
	 * @return	void
	 */
	public function registerPluginOptions($optionGroup, array $optionMeta = []): void
	{
	}


	/**
	 * add network options (values) for the plugin
	 *
	 * @param	string|array	$optionGroup group name or [groupname, tabname]]
	 * @param	array			$optionMeta group option meta
	 * @return	void
	 */
	public function registerNetworkOptions($optionGroup, array $optionMeta = []): void
	{
	}


	/**
	 * load plugin options array
	 *
	 * @return	void
	 */
	private function load_all_plugin_options(): void
	{
		$optionName = $this->prefixOptionName(self::PLUGIN_OPTION_NAME);

		$this->pluginOptions = array_change_key_case(\get_key_value(
			$optionName,
			function($key) {
				// convert from options api
				if ($result = \get_option($key)) {
					\delete_option($key);
					$this->pluginOptionsUpdated = true;
					return $result;
				}
				return [];
			},
			'nocache' 	// don't persist in object cache
		),CASE_LOWER);

	//	$this->pluginOptions = array_change_key_case(
	//		\get_option( $this->prefixOptionName(self::PLUGIN_OPTION_NAME), [] ),
	//		CASE_LOWER
	//	);
	}


	/**
	 * save plugin options array
	 *
	 * @return	void
	 */
	private function save_all_plugin_options(): void
	{
		if ( $this->pluginOptionsUpdated )
		{
			$optionName = $this->prefixOptionName(self::PLUGIN_OPTION_NAME);

			\set_key_value(
				$optionName,
				array_filter( $this->pluginOptions, fn($value) => !is_null($value) ),
				'nocache' 	// don't persist in object cache
			);

	//		\update_option(
	//			$this->prefixOptionName(self::PLUGIN_OPTION_NAME),
	//			array_filter( $this->pluginOptions, function($value) { return !is_null($value); } )
	//		);
			$this->pluginOptionsUpdated = false;
		}
	}


	/**
	 * load network options array
	 *
	 * @return	void
	 */
	private function load_all_network_options(): void
	{
		if ( $this->is_network_enabled() )
		{
			$optionName = $this->prefixOptionName(self::NETWORK_OPTION_NAME);

			$this->networkOptions = array_change_key_case(\get_site_key_value(
				$optionName,
				function($key) {
					// convert from options api
					if ($result = \get_network_option(null,$key)) {
						\delete_network_option(null,$key);
						$this->networkOptionsUpdated = true;
						return $result;
					}
					return [];
				},
				'nocache' 	// don't persist in object cache
			),CASE_LOWER);

	//		$this->networkOptions = array_change_key_case(
	//			\get_network_option( null, $this->prefixOptionName(self::NETWORK_OPTION_NAME), [] ),
	//			CASE_LOWER
	//		);
		}
	}


	/**
	 * save network options array
	 *
	 * @return	void
	 */
	private function save_all_network_options(): void
	{
		if ( $this->networkOptionsUpdated && $this->is_network_enabled() )
		{
			$optionName = $this->prefixOptionName(self::NETWORK_OPTION_NAME);

			\set_site_key_value(
				$optionName,
				array_filter( $this->networkOptions, fn($value) => !is_null($value) ),
				'nocache' 	// don't persist in object cache
			);

	//		\update_network_option( null,
	//			$this->prefixOptionName(self::NETWORK_OPTION_NAME),
	//			array_filter( $this->networkOptions, function($value) { return !is_null($value); } )
	//		);
			$this->networkOptionsUpdated = false;
		}
	}


	/**
	 * add/update/delete an option value in plugin option array
	 *
	 * @internal
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value saved (null gets removed)
	 * @return	mixed	$value
	 */
	private function _update_plugin_option_array(string $optionName, $value)
	{
		if (!array_key_exists($optionName,$this->pluginOptions) || $this->pluginOptions[$optionName] !== $value)
		{
			$this->pluginOptions[$optionName] = $value;
			$this->pluginOptionsUpdated = true;
			// this is being over-cautious...
			//$this->save_all_plugin_options();
		}
		return $value;
	}


	/**
	 * add/update/delete an option value in network option array
	 *
	 * @internal
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value saved (null gets removed)
	 * @return	mixed	$value
	 */
	private function _update_network_option_array(string $optionName, $value)
	{
		if (!array_key_exists($optionName,$this->networkOptions) || $this->networkOptions[$optionName] !== $value)
		{
			$this->networkOptions[$optionName] = $value;
			$this->networkOptionsUpdated = true;
			// this is being over-cautious...
			//$this->save_all_network_options();
		}
		return $value;
	}


	/**
	 * test or set reserved option key
	 *
	 * @example $this->isReservedOption('my_option',true);
	 * @example if ($this->isReservedOption('my_option')) {...}
	 *
	 * @param	string	$optionName option name
	 * @param	bool	$set set as reserved or not
	 * @return	bool
	 */
	public function isReservedOption(string $optionName, $set = null): bool
	{
		$original	= $this->standardizeOptionName($optionName, false);
		$optionName = $this->standardizeOptionName($optionName);

		if (is_bool($set))
		{
			$this->reservedOptions[$optionName] = $set;
			if ($set) //  set as reserved option, do we need to convert it?
			{
				// if in option array, remove it & write option record
				if (array_key_exists($optionName,$this->pluginOptions))
				{
					$value = $this->pluginOptions[$optionName];
					$this->_update_plugin_option_array( $optionName, null );
					\update_option( $this->prefixOptionName($original), $value );
				}
				// if in network array, remove it & write option record
				if (array_key_exists($optionName,$this->networkOptions))
				{
					$value = $this->networkOptions[$optionName];
					$this->_update_network_option_array( $optionName, null );
					\update_network_option(null, $this->prefixOptionName($original), $value);
				}
			}
		}

		return ( $this->reservedOptions[$optionName] ?? false );
	}


	/**
	 * is option a value or in a set of values
	 *
	 * @example $this->is_option('my_option') - returns my_option value
	 * @example $this->is_option('my_option','this_value') - returns 'this_value' or false
	 * @example $this->is_option('my_option',['this_value','that_value']) - returns 'this_value' or 'that_value' or false
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value check for this value (optional)
	 * @param	bool	$network using network options (internal)
	 * @return	bool|mixed	null or option value
	 */
	public function is_option($optionName, $value = null, $network = false)
	{
		$option = ($network || ($this->is_network_admin()))
					? $this->get_network_option($optionName, null)
					: $this->get_option($optionName, null);

		if (!is_null($value) && !is_null($option))
		{
			if (is_string($option) && is_string($value)) {
				// lowercase string compare
				if (strtolower($option) != strtolower($value)) return false;
			} else
			if (is_array($value) && !is_array($option)) {
				// option must be one of value array
				if (!in_array($option,$value)) return false;
			} else
			if (is_array($option) && !is_array($value)) {
				// value must be one of option array
				if (!in_array($value,$option)) return false;
			} else {
				if ($option != $value) return false;
			}
			return $option;
		}

		if (is_string($option))
		{
			switch (strtolower($option)) {
				case 'disabled':
				case 'disabled (admin)':
				case 'network disabled':
				case 'false':
				case 'no':
				case 'off':
				case '0':
				case '':
					return false;
					break;
				case 'enabled':
				case 'enabled (admin)':
				case 'network enabled':
				case 'true':
				case 'yes':
				case 'on':
				case '1':
					return true;
					break;
				case 'null':
					return null;
					break;
			}
		}

		return $option;
	}


	/**
	 * get_option() with prefixed option name, optional callback default
	 *
	 * @example $this->get_option('my_option') - returns my_option value or false
	 * @example $this->get_option('my_option',[]) - returns my_option value or []
	 * @example $this->get_option('my_option','setMyOption') - returns my_option value or value returned by setMyOption()
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function to set value
	 * @return	mixed	option value
	 */
	public function get_option($optionName, $default = false)
	{
		if ($this->is_network_admin()) {
			return $this->get_network_option($optionName, $default);
		}

		$preOption	= $this->prefixOptionName($this->standardizeOptionName($optionName, false));
		$optionName = $this->standardizeOptionName($optionName);
		$isReserved = $this->isReservedOption($optionName);

		if (isset($this->pluginOptions[$optionName]) && !$isReserved)
		{
			$value = $this->pluginOptions[$optionName];
		}
		else
		{
			// get pre-2.0 record (or orphaned record) and maybe delete it
			// this causes a db read on all get_option() calls to options not reserved
			//		$value = \get_option( $this->prefixOptionName($optionName) );
			// instead, check pre-loaded option cache (only)...
			$alloptions = \wp_load_alloptions();
			$value = $alloptions[ $preOption ] ?? wp_cache_get( $preOption, 'options' );

			if ( isset( $value ) && $value !== false )
			{
				$value = maybe_unserialize($value);
				if (!$isReserved )
				{
					$this->_update_plugin_option_array( $optionName, $value );
					// delete pre-2.0 record
					\delete_option( $preOption );
				}
			}
			else if ( is_callable($default) )
			{
				try {
					$value = call_user_func($default);
				} catch (\Throwable $e) {
					return ! $this->logError($e,__METHOD__);
				}
				if (!is_wp_error($value))
				{
					$this->update_option($optionName,$value);
				}
			}
			else
			{
				$value = $default;
			}
		}
		return $value;
	}


	/**
	 * get_option() and decrypt with prefixed option name, optional callback default
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function
	 * @return	mixed	option value
	 */
	public function get_option_decrypt($optionName, $default = false)
	{
		if ( ($value = $this->get_option($optionName, $default)) )
		{
			$value = \apply_filters( 'eacDoojigger_decrypt_string', $value );
			if (!is_wp_error($value))
			{
				return maybe_unserialize($value);
			}
		}
		return $value;
	}


	/**
	 * set an option value
	 *
	 * @deprecated use update_option()
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value value to set
	 * @return	mixed	option value
	 */
	public function set_option($optionName, $value)
	{
		if ($this->is_network_admin()) {
			return $this->set_network_option($optionName, $value);
		}
		\_deprecated_function( __FUNCTION__, '2.0.0', 'update_option()');
		$optionName = $this->standardizeOptionName($optionName);
		return $this->_update_plugin_option_array( $optionName, $value );
	}


	/**
	 * delete_option() with prefixed option name
	 *
	 * @param	string	$optionName option name
	 * @return	bool	returned from delete_option
	 */
	public function delete_option($optionName)
	{
		if ($this->is_network_admin()) {
			return $this->delete_network_option($optionName);
		}
		$optionName = $this->standardizeOptionName($optionName);
		// delete pre-2.0 record
		\delete_option( $this->prefixOptionName($optionName) );
		$this->_update_plugin_option_array( $optionName, null );
		return true;
	}


	/**
	 * add_option() with prefixed option name
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	option value
	 */
	public function add_option($optionName, $value, $autoload = true)
	{
		if ($this->is_network_admin()) {
			return $this->add_network_option($optionName, $value, $autoload);
		}
		$optionName = $this->standardizeOptionName($optionName);
		if ($this->isReservedOption($optionName))
		{
			$this->_update_plugin_option_array( $optionName, null );
			return \add_option( $this->prefixOptionName($optionName), $value, '', $autoload );
		}
		return $this->_update_plugin_option_array( $optionName, $value );
	}


	/**
	 * update_option() with prefixed option name
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	option value
	 */
	public function update_option($optionName, $value, $autoload = true)
	{
		if ($this->is_network_admin()) {
			return $this->update_network_option($optionName, $value, $autoload);
		}
		$optionName = $this->standardizeOptionName($optionName);
		if ($this->isReservedOption($optionName))
		{
			$this->_update_plugin_option_array( $optionName, null );
			return \update_option( $this->prefixOptionName($optionName), $value, $autoload );
		}
		return $this->_update_plugin_option_array( $optionName, $value );
	}


	/**
	 * encrypt and update_option() with prefixed option name
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from update_option
	 */
	public function update_option_encrypt($optionName, $value, $autoload = true)
	{
		return $this->update_option($optionName,
				\apply_filters( 'eacDoojigger_encrypt_string', maybe_serialize($value) ),
				$autoload
		);
	}


	/**
	 * rename an option
	 *
	 * @param	string	$oldOptionName old (current) option name
	 * @param	string	$newOptionName new option name
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from update_option
	 */
	public function rename_option($oldOptionName, $newOptionName, $autoload = true)
	{
		if ($this->is_network_admin()) {
			return $this->rename_network_option($oldOptionName, $newOptionName, $autoload);
		}
		$value = $this->get_option($oldOptionName,null);
		if (!is_null($value))
		{
			$this->delete_option($oldOptionName);
			return $this->add_option($newOptionName, $value, $autoload);
		}
		return false;
	}


	/**
	 * is network option a value or in a set of values (only network enabled)
	 *
	 * @example $this->is_network_option('my_option') - returns my_option value
	 * @example $this->is_network_option('my_option','this_value') - returns 'this_value' or false
	 * @example $this->is_network_option('my_option',['this_value','that_value']) - returns 'this_value' or 'that_value' or false
	 *
	 * @param	string		$optionName option name
	 * @param	mixed		$value check this value
	 * @return	bool|mixed	option is set and has value
	 */
	public function is_network_option($optionName, $value = null)
	{
		if (! $this->is_network_enabled()) return null;
		return $this->is_option($optionName, $value, true);
	}


	/**
	 * get_network_option() with prefixed option name, optional callback default (only network enabled)
	 *
	 * @example $this->get_network_option('my_option') - returns my_option value or false
	 * @example $this->get_network_option('my_option',[]) - returns my_option value or []
	 * @example $this->get_network_option('my_option','setMyOption') - returns my_option value or value returned by setMyOption()
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function to set value
	 * @return	mixed	option value
	 */
	public function get_network_option($optionName, $default = false)
	{
		if (! $this->is_network_enabled()) return $default;

		$preOption	= $this->prefixOptionName($this->standardizeOptionName($optionName, false));
		$optionName = $this->standardizeOptionName($optionName);
		$isReserved = $this->isReservedOption($optionName);

		if (isset($this->networkOptions[$optionName]) && !$isReserved)
		{
			$value = $this->networkOptions[$optionName];
		}
		else
		{
			// get pre-2.0 record (or reserved/orphaned record) and maybe delete it
			$value = \get_network_option( null, $preOption );
			if ( isset( $value ) && $value !== false )
			{
				if (!$isReserved)
				{
					$this->_update_network_option_array( $optionName, $value );
					// delete pre-2.0 record
					\delete_network_option( null, $preOption );
				}
			}
			else if ( is_callable($default) )
			{
				try {
					$value = call_user_func($default);
				} catch (\Throwable $e) {
					return ! $this->logError($e,__METHOD__);
				}
				if (!is_wp_error($value))
				{
					$this->update_network_option($optionName,$value);
				}
			}
			else
			{
				$value = $default;
			}
		}
		return $value;
	}


	/**
	 * get_network_option() and decrypt with prefixed option name, optional callback default
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function
	 * @return	mixed	option value
	 */
	public function get_network_option_decrypt($optionName, $default = false)
	{
		if ( ($value = $this->get_network_option($optionName, $default)) )
		{
			$value = \apply_filters( 'eacDoojigger_site_decrypt_string', $value );
			if (!is_wp_error($value))
			{
				return maybe_unserialize($value);
			}
		}
		return $value;
	}


	/**
	 * set a network option value (only network enabled)
	 *
	 * @deprecated use update_network_option()
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value value to set
	 * @return	mixed	option value
	 */
	public function set_network_option($optionName, $value)
	{
		\_deprecated_function( __FUNCTION__, '2.0.0', 'update_network_option()');
		if (! $this->is_network_enabled()) return false;
		$optionName = $this->standardizeOptionName($optionName);
		return $this->_update_network_option_array( $optionName, $value );
	}


	/**
	 * delete_network_option() with prefixed option name (only network enabled)
	 *
	 * @param	string	$optionName option name
	 * @return	bool	returned from delete_option
	 */
	public function delete_network_option($optionName)
	{
		if (! $this->is_network_enabled()) return false;
		$optionName = $this->standardizeOptionName($optionName);
		// delete pre-2.0 record
		\delete_network_option(null, $this->prefixOptionName($optionName));
		$this->_update_network_option_array( $optionName, null );
		return true;
	}


	/**
	 * add_network_option() with prefixed option name (only network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @return	mixed	option value
	 */
	public function add_network_option($optionName, $value)
	{
		if (! $this->is_network_enabled()) return false;
		$optionName = $this->standardizeOptionName($optionName);
		if ($this->isReservedOption($optionName))
		{
			$this->_update_network_option_array( $optionName, null );
			return \add_network_option(null, $this->prefixOptionName($optionName), $value);
		}
		return $this->_update_network_option_array( $optionName, $value );
	}


	/**
	 * update_network_option() with prefixed option name (only network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @return	mixed	option value
	 */
	public function update_network_option($optionName, $value)
	{
		if (! $this->is_network_enabled()) return false;
		$optionName = $this->standardizeOptionName($optionName);
		if ($this->isReservedOption($optionName))
		{
			$this->_update_network_option_array( $optionName, null );
			return \update_network_option(null, $this->prefixOptionName($optionName), $value);
		}
		return $this->_update_network_option_array( $optionName, $value );
	}


	/**
	 * encrypt and update_network_option() with prefixed option name
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @return	mixed	returned from update_option
	 */
	public function update_network_option_encrypt($optionName, $value)
	{
		return $this->update_network_option($optionName,
				\apply_filters( 'eacDoojigger_site_encrypt_string', maybe_serialize($value) )
		);
	}


	/**
	 * rename a network option (only network enabled)
	 *
	 * @param	string	$oldOptionName old (current) option name
	 * @param	string	$newOptionName new option name
	 * @return	mixed	returned from update_option
	 */
	public function rename_network_option($oldOptionName, $newOptionName)
	{
		if (! $this->is_network_enabled()) return false;
		$value = $this->get_network_option($oldOptionName,null);
		if (!is_null($value))
		{
			$this->delete_network_option($oldOptionName);
			return $this->add_network_option($newOptionName, $value);
		}
		return false;
	}


	/**
	 * is_option a value or in a set of values (single site or network enabled)
	 *
	 * @example $this->is_site_option('my_option')
	 * @example $this->is_site_option('my_option','this_value')
	 * @example $this->is_site_option('my_option',['this_value','that_value'])
	 *
	 * @param	string		$optionName option name
	 * @param	mixed		$value check this value
	 * @return	bool|mixed	option is set and has value
	 */
	public function is_site_option($optionName, $value = null)
	{
		return ($this->is_network_enabled())
			? $this->is_network_option($optionName, $value)
			: $this->is_option($optionName, $value);
	}


	/**
	 * get_option() with prefixed option name, optional callback default (single site or network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function
	 * @return	mixed	option value
	 */
	public function get_site_option($optionName, $default = false)
	{
		return ($this->is_network_enabled())
			? $this->get_network_option($optionName, $default)
			: $this->get_option($optionName, $default);
	}


	/**
	 * get_site_option() and decrypt with prefixed option name, optional callback default
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$default default value or callable function
	 * @return	mixed	option value
	 */
	public function get_site_option_decrypt($optionName, $default = false)
	{
		return ($this->is_network_enabled())
			? $this->get_network_option_decrypt($optionName, $default)
			: $this->get_option_decrypt($optionName, $default);
	}


	/**
	 * temporarily set an option value (single site or network enabled)
	 *
	 * @deprecated use update_site_option()
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value value to set
	 * @return	mixed	option value
	 */
	public function set_site_option($optionName, $value)
	{
		\_deprecated_function( __FUNCTION__, '2.0.0', 'update_site_option()');
		return ($this->is_network_enabled())
			? $this->set_network_option($optionName, $value)
			: $this->set_option($optionName, $value);
	}


	/**
	 * delete_option() with prefixed option name (single site or network enabled)
	 *
	 * @param	string	$optionName option name
	 * @return	bool	returned from delete_option
	 */
	public function delete_site_option($optionName)
	{
		return ($this->is_network_enabled())
			? $this->delete_network_option($optionName)
			: $this->delete_option($optionName);
	}


	/**
	 * add_option() with prefixed option name (single site or network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from add_option
	 */
	public function add_site_option($optionName, $value, $autoload = true)
	{
		return ($this->is_network_enabled())
			? $this->add_network_option($optionName, $value)
			: $this->add_option($optionName, $value, $autoload);
	}


	/**
	 * update_site_option() with prefixed option name (single site or network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from update_option
	 */
	public function update_site_option($optionName, $value, $autoload = true)
	{
		return ($this->is_network_enabled())
			? $this->update_network_option($optionName, $value)
			: $this->update_option($optionName, $value, $autoload);
	}


	/**
	 * encrypt and update_site_option() with prefixed option name (single site or network enabled)
	 *
	 * @param	string	$optionName option name
	 * @param	mixed	$value option value
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from update_option
	 */
	public function update_site_option_encrypt($optionName, $value, $autoload = true)
	{
		return ($this->is_network_enabled())
			? $this->update_network_option_encrypt($optionName, $value)
			: $this->update_option_encrypt($optionName, $value, $autoload);
	}


	/**
	 * rename an option (single site or network enabled)
	 *
	 * @param	string	$oldOptionName old (current) option name
	 * @param	string	$newOptionName new option name
	 * @param	bool	$autoload WordPress autoload/cache
	 * @return	mixed	returned from update_option
	 */
	public function rename_site_option($oldOptionName, $newOptionName, $autoload = true)
	{
		return ($this->is_network_enabled())
			? $this->rename_network_option($oldOptionName, $newOptionName)
			: $this->rename_option($oldOptionName, $newOptionName, $autoload);
	}


	/**
	 * Get the prefixed version of the option name
	 *
	 * @param	string	$optionName option name
	 * @return	string	$optionName with prefix
	 */
	public function prefixOptionName(string $optionName): string
	{
		return $this->addClassNamePrefix($optionName);
	}


	/**
	 * Get the un-prefixed version of the option name
	 *
	 * @param	string	$optionName prefixed option name
	 * @return	string	$optionName without prefix
	 */
	public function unprefixOptionName(string $optionName): string
	{
		return $this->removeClassNamePrefix($optionName);
	}


	/*
	 *
	 * transient helpers - uses is_network_admin
	 *
	 */


	/**
	 * A similar function to WP get_transient() with prefixed transient name and callable function
	 *
	 * @example $this->get_transient(name, function, nnn) to call function if transient is not set or has expired
	 *
	 * @param	string	$transientName transient name
	 * @param	mixed	$default default value or callable function
	 * @param	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	mixed	transient value
	 */
	public function get_transient(string $transientName, $default = false, $expiration = 0 )
	{
		$transientName = $this->prefixTransientName($transientName);

		// try from key/value helper (or object cache)
		return ($this->is_network_admin())
			? \get_site_key_value( $transientName, $default, $expiration, 'transient' )
			: \get_key_value( $transientName, $default, $expiration, 'transient' );

		// fallback to the old fashioned way
		// when wp cache is disabled, transients may be exported back to wp
/*
		if (is_null($value))
		{
			if (! wp_using_ext_object_cache() ) {
				$value = ($this->is_network_admin())
					? \get_site_transient( $transientName )
					: \get_transient( $transientName );
			} else $value = false;

			if ( $value === false )
			{
				if ( is_callable($default) ) {
					$value = call_user_func($default,$transientName);
					if (is_wp_error($value)) return $value;
				} else {
					$value = $default;
				}
			}

			// passing expiration shows intent
			if ($value && $expiration) {
				$this->set_transient($transientName,$value,$expiration);
			}
		}
		return $value;
*/
	}


	/**
	 * A similar function to WP set_transient() with prefixed transient name
	 *
	 * @param	string	$transientName transient name
	 * @param	mixed	$value value to save
	 * @param	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	bool
	 */
	public function set_transient(string $transientName, $value, $expiration = 0 )
	{
		if ( is_null($value) || is_wp_error($value) ) return false;

		$transientName = $this->prefixTransientName($transientName);

		return ($this->is_network_admin())
			? \set_site_key_value( $transientName, $value, $expiration, 'transient' )
			: \set_key_value( $transientName, $value, $expiration, 'transient' );
	/*
		return ($this->is_network_admin())
			? \set_site_transient( $transientName, $value, $expiration )
			: \set_transient( $transientName, $value, $expiration );
	*/
	}


	/**
	 * A similar function to WP delete_transient() with prefixed transient name
	 *
	 * @param	string	$transientName transient name
	 * @return	bool
	 */
	public function delete_transient(string $transientName)
	{
		$transientName = $this->prefixTransientName($transientName);

		return ($this->is_network_admin())
			? \set_site_key_value( $transientName, null, 'transient' )
			: \set_key_value( $transientName, null, 'transient' );
	/*
		return ($this->is_network_admin())
			? \delete_site_transient( $transientName )
			: \delete_transient( $transientName );
	*/
	}


	/**
	 *
	 * transient helpers - is_network_admin || is_network_enabled()
	 *
	 */


	/**
	 * A similar function to WP get_site_transient() with prefixed transient name and callable function
	 *
	 * @example $this->get_site_transient(name, function, nnn) to call function if transient is not set or has expired
	 *
	 * @param	string	$transientName transient name
	 * @param	mixed	$default default value or callable function
	 * @param	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	mixed	transient value
	 */
	public function get_site_transient(string $transientName, $default = false, $expiration = 0 )
	{
		$transientName = $this->prefixTransientName($transientName);

		// try from key/value helper (or object cache)
		return ($this->is_network_admin() || $this->is_network_enabled())
			? \get_site_key_value( $transientName, $default, $expiration, 'transient' )
			: \get_key_value( $transientName, $default, $expiration, 'transient' );

		// fallback to the old fashioned way
		// when wp cache is disabled, transients may be exported back to wp
/*
		if (is_null($value))
		{
			if (! wp_using_ext_object_cache() ) {
				$value = ($this->is_network_admin() || $this->is_network_enabled())
					? \get_site_transient( $transientName )
					: \get_transient( $transientName );
			} else $value = false;

			if ( $value === false )
			{
				if ( is_callable($default) ) {
					$value = call_user_func($default,$transientName);
					if (is_wp_error($value)) return $value;
				} else {
					$value = $default;
				}
			}

			// passing expiration shows intent
			if ($value && $expiration) {
				$this->set_site_transient($transientName,$value,$expiration);
			}
		}
		return $value;
*/
	}


	/**
	 * A similar function to WP set_site_transient() with prefixed transient name
	 *
	 * @param	string	$transientName transient name
	 * @param	mixed	$value value to save
	 * @param	int		$expiration time until expiration in seconds. Default 0 (no expiration)
	 * @return	bool
	 */
	public function set_site_transient(string $transientName, $value, $expiration = 0 )
	{
		if ( is_null($value) || is_wp_error($value) ) return false;

		$transientName = $this->prefixTransientName($transientName);

		return ($this->is_network_admin() || $this->is_network_enabled())
			? \set_site_key_value( $transientName, $value, $expiration, 'transient' )
			: \set_key_value( $transientName, $value, $expiration, 'transient' );
	/*
		return ($this->is_network_admin() || $this->is_network_enabled())
			? \set_site_transient( $transientName, $value, $expiration )
			: \set_transient( $transientName, $value, $expiration );
	*/
	}


	/**
	 * A similar function to WP delete_site_transient() with prefixed transient name
	 *
	 * @param	string	$transientName transient name
	 * @return	bool
	 */
	public function delete_site_transient(string $transientName)
	{
		$transientName = $this->prefixTransientName($transientName);

		return ($this->is_network_admin() || $this->is_network_enabled())
			? \set_site_key_value( $transientName, null, 'transient' )
			: \set_key_value( $transientName, null, 'transient' );
	/*
		return ($this->is_network_admin() || $this->is_network_enabled())
			? \delete_site_transient( $transientName )
			: \delete_transient( $transientName );
	*/
	}


	/**
	 * Get the prefixed version of the transient name
	 *
	 * @param	string	$transientName transient name
	 * @param	string	$prefix override default prefix
	 * @return	string	transient name with prefix
	 */
	public function prefixTransientName(string $transientName, $prefix=null): string
	{
		return strtolower( $this->addClassNamePrefix($transientName,$prefix) );
	}


	/*
	 *
	 * option/hook/table (etc.) name prefixed with short class name
	 *
	 */


	/**
	 * Get the prefix to the input $name suitable for storing
	 *
	 * @param	string	$name option/hook/table name
	 * @param	string	$prefix override default prefix
	 * @return	string	option/hook/table name with prefix
	 */
	public function addClassNamePrefix(string $name, $prefix=null): string
	{
		$classNamePrefix = $this->getClassNamePrefix($prefix);
		return (stripos($name, $classNamePrefix) === 0)
			? trim($name)
			: $classNamePrefix . trim($name);
	}


	/**
	 * Remove the prefix from the input $name
	 *
	 * @param	string	$name option/hook/table name
	 * @param	string	$prefix override default prefix
	 * @return	string	option/hook/table name without prefix
	 */
	public function removeClassNamePrefix(string $name, $prefix=null): string
	{
		$classNamePrefix = $this->getClassNamePrefix($prefix);
		return (stripos($name, $classNamePrefix) === 0)
			? substr($name, strlen($classNamePrefix))
			: trim($name);
	}


	/**
	 * get the class name prefix
	 *
	 * @param	string	$prefix override default prefix
	 * @return	string	short_classname_
	 */
	public function getClassNamePrefix($prefix=null): string
	{
		return ($prefix ?: $this->className) . '_';
	}


	/**
	 * has the class name prefix
	 *
	 * @param	string	$name option/hook/table name
	 * @param	string	$prefix override default prefix
	 * @return	bool
	 */
	public function hasClassNamePrefix(string $name, $prefix=null): bool
	{
		$classNamePrefix = $this->getClassNamePrefix($prefix);
		return (stripos($name, $classNamePrefix) === 0);
	}


	/*
	 *
	 * prefix custom table name(s)
	 *
	 */


	/**
	 * Prefix the table name with wpdb prefix and our class prefix (wpdb_classname_tablename)
	 *
	 * @param	string	name of a database table
	 * @param	string	$prefix override default prefix
	 * @return	string	full table name
	 */
	public function prefixTableName(string $name, $prefix=null): string
	{
		return $this->wpdb->prefix . strtolower( $this->addClassNamePrefix($name,$prefix) );
	}
}

<?php
namespace EarthAsylumConsulting;

use EarthAsylumConsulting\Helpers\wp_config_editor;

/**
 * {eac}Doojigger for WordPress - Plugin back-end (administration) methods and hooks.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.earthasylum.com>
 * @version		25.0728.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		\EarthAsylumConsulting\abstract_context
 * @uses		\EarthAsylumConsulting\abstract_core
 */

abstract class abstract_backend extends abstract_core
{
	/**
	 * @trait methods for version compare
	 */
	use \EarthAsylumConsulting\Traits\version_compare;

	/**
	 * @trait methods for codemirrir & wp_editor
	 */
	use \EarthAsylumConsulting\Traits\code_editor;

	/**
	 * @trait methods for html fields used externally
	 * Derivative plugins and extensions should use this when needed
	 */
	//	use \EarthAsylumConsulting\Traits\html_input_fields;

	/**
	 * @var string option name for installed version
	 */
	const PLUGIN_INSTALLED_VERSION		= 'plugin_installed_version';

	/**
	 * @var string option name for installed extension versions
	 */
	const PLUGIN_INSTALLED_EXTENSIONS	= 'plugin_installed_extensions';

	/**
	 * @var array required options meta keys
	 */
	const OPTION_META_KEYS 				= [
			'type'			=> '',
			'label'			=> '',
			'title'			=> '',
			'before'		=> '',
			'default'		=> '',
			'after'			=> '',
			'info'			=> '',
			'tooltip'		=> '',
			'advanced'		=> false,
	];

	/**
	 * @var bool automatically set tooltip to info when not set
	 */
	protected $automatic_tooltips 		= true;

	/*
	 * Define options meta data as a group of arrays,
	 *	A group is usually the main plugin or an extension, typically the class name
	 *	where each element in the array returns key=array(options)
	 *
	 * group_name logically groups options for accessing through getOptionMetaData()
	 * but is not used when storing options. optionName must be unique across all groups.
	 *
	 *	'group_name' =
	 *	[
	 *		optionName => array(
	 *			'type'			=> [html input type + custom, codeedit-js, codeedit-html, codeedit-css, codeedit-php, html, disabled, readonly]
	 *			'label'			=> display name
	 *			'title'			=> information text/html to be displayed aboce the field (<blockquote>)
	 *			'before'		=> information text/html to be displayed before the field
	 *			'options'		=> [optionA,optionB,...,[optionZ=>valueZ]] for checkbox,radio,select
	 *			'default'		=> default field value
	 *			'after'			=> information text/html to be displayed after the field
	 *			'info'			=> additional information text/html to be displayed below the field (<cite>)
	 *			'tooltip'		=> additional information presented as a tool-top on hover
	 *			'attributes'	=> html attributes array ["name='value'", "name='value'"]
	 *			'class'			=> css class name(s) (added to attributes)
	 *			'style'			=> css style declaration(s) (added to attributes)
	 *			'encrypt'		=> truthy = encrypt/decrypt option
	 *			'sanitize'		=> callable function overrides internal sanitization
	 *			'filter'		=> array passed to PHP filter_var [filter_type, options]
	 *		)
	 *	]
	 */

	/**
	 * @var array options meta data array
	 * @see registerPluginOptions()
	 */
	protected $optionMetaData 			= array(
			'Plugin Settings'	=> []
	);

	/**
	 * @var array options meta tab names
	 */
	protected $optionTabNames = array();

	/**
	 * @var array network options meta data array
	 * @see registerNetworkOptions()
	 */
	protected $networkMetaData 			= array(
			'Network Settings'	=> []
	);

	/**
	 * @var array network options meta tab names
	 */
	protected $networkTabNames 			= array();

	/**
	 * @var array extension versions
	 * @used-by loadAllExtensions()
	 */
	protected $extensionVersions 		= array();

	/**
	 * @var bool are we on the settings page
	 */
	protected $is_settings_page			= null;

	/**
	 * @var array default tab name(s) - set to control order
	 */
	public $defaultTabs 				= array('general');


	/**
	 * Plugin constructor method.
	 * Adds hooks for install/update/registration
	 *
	 * {@inheritDoc}
	 *
	 * @param array $plugin_detail header passed from loader script
	 * @return	void
	 */
	protected function __construct(array $header)
	{
		parent::__construct($header);

		/*
		 * add admin hooks for plugin updates/activation
		 */

		// Register the Plugin Activation Hook
		register_activation_hook($header['PluginFile'],		array( $this, 'plugin_admin_activated') );
		// Register the Plugin Deactivation Hook
		register_deactivation_hook($header['PluginFile'],	array( $this, 'plugin_admin_deactivated') );

		// when upgrade completes (old version is active)
		add_action( 'upgrader_process_complete',			array( $this, 'plugin_admin_upgraded'), 10, 2 );
		// Register admin_init to check for new install or upgraded version of the plugin
		add_action( 'admin_init',							array( $this, 'plugin_admin_installed') );
	}


	/**
	 * class initialization.
	 * Called after instantiating and loading extensions
	 *
	 * @return	void
	 */
	public function initialize(): void
	{
		/**
		 * action {classname}_http_headers_ready triggered on PHP header_register_callback()
		 * add_action( '{className}_admin_http_headers_ready', function(){...} );
		 */
		header_register_callback( function()
			{
				$this->do_action('admin_http_headers_ready');
			}
		);

		parent::initialize();
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
		/*
		 * add options settings administration page menu and plugin page link
		 */

		if ( $this->is_network_admin() )
		{
			add_action( 'network_admin_menu',			array( $this, 'plugin_admin_add_settings_menu') );
			add_filter( 'network_admin_plugin_action_links_'.$this->PLUGIN_SLUG,
														array( $this, 'plugin_admin_add_settings_link'), 30, 3 );
		}
		else
		{
			add_action( 'admin_menu',					array( $this, 'plugin_admin_add_settings_menu') );
			add_filter( 'plugin_action_links_'.$this->PLUGIN_SLUG,
														array( $this, 'plugin_admin_add_settings_link'), 30, 3 );
		}

		// when stable tag <> versiion, show in meta row on plugins page
		add_action( 'after_plugin_row_meta',			array( $this, 'plugin_admin_add_plugin_meta'), 10, 2 );

		// settings errors / admin notices - on all pages, triggered in admin_footer
		add_action( 'all_admin_notices',				array( $this, 'plugin_admin_notices'), 50 );

		// get admin colors
		add_action('admin_init', 						array( $this, 'admin_color_scheme'), 10);

		/*
		 * add contextual help and settings page style & javascript
		 */

		if ($this->isSettingsPage() && current_user_can('manage_options'))
		{
			// add contextual help
			add_action( ($this->is_network_admin() ? 'network_' : '').'admin_menu',
														array( $this, 'plugin_admin_add_settings_help'), 1 );

			// code editor
			add_action('admin_enqueue_scripts', function ()
				{
					// only needed if overridong styles/options */
					// $this->codeedit_enqueue(/* $styles = '', $options = [] */);

					$this->options_settings_page_style();
					$this->options_settings_page_script();
	    		}
	    	);
		}

		parent::addActionsAndFilters();
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
		parent::addShortcodes();
	}


	/*
	 *
	 * Plugin install/update methods
	 *
	 */


	/**
	 * after plugin upgrade on 'upgrader_process_complete' filter
	 *
	 * $hook_extra may only have 'action' and 'type'; action may be 'update' or 'install' (on a manual update).
	 * Since we can't check that it's actually our plugin being updated, we do it for any plugin.
	 * And can't perform updates since we're still in the old version of our code at this point.
	 * We may pass through here more than once, if network-enabled.
	 * In multisite environment, network admin calls upgrade for each active site.
	 *
	 * This doesn't work if instaling on multisite but not network activated.
	 *
	 * @param	object	$upgrader_object
	 * @param	array	$hook_extra
	 * @return	void
	 */
	public function plugin_admin_upgraded( object $upgrader_object, array $hook_extra ): void
	{
		if ( $hook_extra['type'] === 'plugin' && in_array($hook_extra['action'], ['install','update']) )
		{
			// are we updating this plugin?
			if ((array_key_exists('plugin',$hook_extra) &&  $hook_extra['plugin'] == $this->PLUGIN_SLUG)
			||  (array_key_exists('plugins',$hook_extra) && in_array($this->PLUGIN_SLUG, $hook_extra['plugins'])) )
			{
				$this->delete_site_transient( self::PLUGIN_HEADER_TRANSIENT );
				// delete transient on all sites
				$this->forEachNetworkSite(function()
					{
						$this->delete_site_transient( self::PLUGIN_HEADER_TRANSIENT );
					}
				);
			}
		}
	}


	/**
	 * Activate the plugin (via register_activation_hook)
	 *
	 * In multisite environment, if network-enabled, network admin calls activate for each active site.
	 * May occur twice on primary site (as network admin and not)
	 *
	 * @param bool $isNetwork true if network activated
	 * @return	void
	 */
	public function plugin_admin_activated($isNetwork=false): void
	{
		$this->logInfo('',__METHOD__);

		$this->deleteTransients( ($isNetwork===true) );
		$this->createScheduledEvents();

		/**
		 * action {classname}_plugin_activated when plugin is activated
		 * @param	bool	$$isNetwork activated network-wide
		 * @param	bool	$asNetworkAdmin running as network admin
		 * @return	void
		 */
		$this->do_action( 'plugin_activated', $isNetwork, $this->is_network_admin() );

		// WP 6.4+ - autoload our option array
		if (function_exists('wp_set_options_autoload'))
		{
			wp_set_options_autoload(
				[
					$this->prefixOptionName(self::PLUGIN_OPTION_NAME),
					$this->prefixOptionName(self::NETWORK_OPTION_NAME)
				],
				'yes'
			);
		}

		if ($isNetwork)
		{
			$this->forEachNetworkSite(function()
				{
					$this->plugin_admin_activated( ($isNetwork===true) );
				}
			);
		}
	}


	/**
	 * Deactivate the plugin (via register_deactivation_hook)
	 *
	 * In multisite environment, if network-enabled,  network admin calls deactivate for each active site.
	 * May occur twice on primary site (as network admin and not)
	 *
	 * @param bool $isNetwork true if network deactivated
	 * @return	void
	 */
	public function plugin_admin_deactivated($isNetwork=false): void
	{
		$this->logInfo('',__METHOD__);

		$this->deleteTransients( ($isNetwork===true) );
		$this->removeScheduledEvents();

		/**
		 * action {classname}_plugin_deactivated when plugin is deactivated
		 * @param	bool	$$isNetwork activated network-wide
		 * @param	bool	$asNetworkAdmin running as network admin
		 * @return	void
		 */
		$this->do_action( 'plugin_deactivated', $isNetwork, $this->is_network_admin() );

		// WP 6.4+ - don't autoload our option array
		if (function_exists('wp_set_options_autoload'))
		{
			wp_set_options_autoload(
				[
					$this->prefixOptionName(self::PLUGIN_OPTION_NAME),
					$this->prefixOptionName(self::NETWORK_OPTION_NAME)
				],
				'no'
			);
		}

		if ($isNetwork)
		{
			$this->forEachNetworkSite(function() use ($isNetwork)
				{
					$this->plugin_admin_deactivated( ($isNetwork===true) );
				}
			);
		}
	}


	/**
	 * Install/Upgrade the plugin (via admin_init, there is no register_install_hook)
	 *
	 * @return	void
	 */
	public function plugin_admin_installed(): void
	{
		if ( ! ($version = $this->getInstalledVersion()) )
		{
			$this->logDebug($this->className.' install',__METHOD__);
			$this->admin_install_plugin();			// New install
		}
		else
		{
			$this->admin_upgrade_plugin($version);	// Maybe version upgrade(?)
		}

		// if network-admin, install/upgrade each activated site
		$this->forEachNetworkSite(function()
			{
				$this->plugin_admin_installed();
			}
		);
	}


	/**
	 * Perform any installation activities
	 *
	 * In multisite environment, network admin calls install for each active site.
	 * May occur twice on primary site (as network admin and not)
	 *
	 * @return void
	 */
	protected function admin_install_plugin(): void
	{
		$version = $this->getSemanticVersion()->version;

		$this->logInfo($this->className.sprintf(' %s install version %s',\get_option('blogname'),$version),__METHOD__);

		if (!$this->is_network_admin())
		{
			// install/update tables used by the plugin
			$this->createCustomTables();
			// Create scheduled events
			$this->createScheduledEvents();
		}

		// Save installed version, avoid running install() more then once
		$this->markAsInstalled();

		/**
		 * action {classname}_version_installed when plugin version installed
		 * @param	string	$oldVersion null
		 * @param	string	$version currently installed version
		 * @param	bool	$asNetworkAdmin running as network admin
		 * @return	void
		 */
		$this->do_action( 'version_installed', null, $version, $this->is_network_admin() );
	}


	/**
	 * Perform any version-upgrade activities
	 *
	 * In multisite environment, network admin calls upgrade for each active site.
	 * May occur twice on primary site (as network admin and not)
	 *
	 * @parm string $oldVersion currently installed version
	 * @return void
	 */
	protected function admin_upgrade_plugin(string $oldVersion): void
	{
		if ( is_multisite() && !$this->is_network_enabled() ) {
			// because we can't detect plugin update
			$this->setPluginHeaderValues([]); // force reload of header values
		}
		$newVersion = $this->getSemanticVersion()->version;
		if ( ($compare = $this->isVersionCompare($oldVersion, $newVersion, true, 'upgrade', 'downgrade')) === true ) return;

		$this->logInfo($this->className.sprintf(" %s {$compare} from version %s to %s",\get_option('blogname'),$oldVersion,$newVersion),__METHOD__);

		//	if ($this->isVersionLessThan($newVersion,'3.0.0')) {...}

		// delete (old) transients
		$this->deleteTransients();

		if (!$this->is_network_admin())
		{
			// install/update tables used by the plugin
			$this->createCustomTables();
			// Create scheduled events
			$this->createScheduledEvents();
		}

		// record the installed version
		$this->markAsInstalled();

		/**
		 * action {classname}_version_updated when plugin version changes
		 * @param	string	$oldVersion previously installed version
		 * @param	string	$newVersion newly installed version
		 * @param	bool	$asNetworkAdmin running as network admin
		 * @return	void
		 */
		$this->do_action( 'version_updated', $oldVersion, $newVersion, $this->is_network_admin() );
	}


	/*
	 *
	 * Installer helpers
	 *
	 */


	/**
	 * is installed
	 *
	 * @return	bool indicating if the plugin is installed already
	 */
	protected function isInstalled(): bool
	{
		return ( $this->getInstalledVersion() !== false );
	}


	/**
	 * record in DB that the plugin is installed
	 *
	 * @return	void
	 */
	protected function markAsInstalled(): void
	{
		$this->update_option( self::PLUGIN_INSTALLED_VERSION, $this->getSemanticVersion()->version );
	}


	/**
	 * get the version string stored in the DB (currently installed)
	 *
	 * @return	string	version (n.n.n) from database option
	 */
	protected function getInstalledVersion(): string
	{
		return $this->get_option( self::PLUGIN_INSTALLED_VERSION );
	}


	/**
	 * Install/update database tables
	 *
	 * @used-by	admin_install_plugin()
	 * @used-by	admin_upgrade_plugin()
	 * @see		https://codex.wordpress.org/Creating_Tables_with_Plugins - specific requirements for dbDelta()
	 * @return	void
	 */
	protected function createCustomTables(): void
	{
	}


	/**
	 * Create scheduled events
	 *
	 * @used-by	admin_install_plugin()
	 * @used-by	admin_upgrade_plugin()
	 * @return	void
	 */
	protected function createScheduledEvents(): void
	{
	/* -- now handled through event_scheduler extension */

	//	$this->removeScheduledEvents();

	//	/**
	//	 * action {pluginname}_hourly_event to run hourly
	//	 * @return	void
	//	 */
	//	$scheduledTime = new \DateTime( wp_date('Y-m-d H:00:00'), wp_timezone() );
	//	$scheduledTime->modify('next hour');
	//	wp_schedule_event( $scheduledTime->getTimestamp(), 'hourly', $this->prefixHookName('hourly_event') );

	//	/**
	//	 * action {pluginname}_daily_event to run daily
	//	 * @return	void
	//	 */
	//	$scheduledTime = new \DateTime( 'tomorrow 1am', wp_timezone() );
	//	wp_schedule_event( $scheduledTime->getTimestamp(), 'daily', $this->prefixHookName('daily_event') );

	//	/**
	//	 * action {pluginname}_weekly_event to run start of week
	//	 * @return	void
	//	 */
	//	$startOfWeekDay = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
	//	$startOfWeekDay = $startOfWeekDay[ get_option( 'start_of_week' ) ];
	//	$scheduledTime = new \DateTime( 'next '.$startOfWeekDay.' midnight', wp_timezone() );
	//	wp_schedule_event( $scheduledTime->getTimestamp(), 'weekly', $this->prefixHookName('weekly_event') );
	}


	/**
	 * Remove scheduled events
	 *
	 * @used-by	createScheduledEvents()
	 * @used-by	plugin_admin_deactivated()
	 * @return	void
	 */
	protected function removeScheduledEvents(): void
	{
	/* -- now handled through event_scheduler extension */

	//	foreach ( ['hourly_event','daily_event','weekly_event'] as $eventName)
	//	{
	//		$eventName = $this->prefixHookName($eventName);
	//		wp_unschedule_hook($eventName);
	//	}
	}


	/*
	 *
	 * When loading plugin extensions
	 *
	 */


	/**
	 * Called after instantiation of this class to load all extension classes.
	 * Tracks extension versions and upgrades in backend
	 *
	 * @return	void
	 */
	public function loadAllExtensions(): void
	{
		$this->logInfo('',__METHOD__);

		$this->extensionVersions = $this->get_option(self::PLUGIN_INSTALLED_EXTENSIONS, []);

		// abstract_core loads extensions
		parent::loadAllExtensions();

		// save extension versions
		if ( count($this->extensionVersions) > 0 )
		{
			$this->update_option(self::PLUGIN_INSTALLED_EXTENSIONS, $this->extensionVersions);
		}
		unset($this->extensionVersions);
	}


	/**
	 * is this an updated extension (when loaded from abstract_core)
	 *
	 * @param	string	$className extension name
	 * @param	string	$extVersion extension version loaded
	 * @return	void
	 */
	protected function checkExtensionUpgrade(string $className, string $extVersion): void
	{
		if ($extVersion == 'unknown') return;

		$curVersion = $this->extensionVersions[$className] ?? '0.0';

		if (version_compare($curVersion, $extVersion) !== 0)					// version change
		{
			$this->logInfo('updated from version '.$curVersion.' to '.$extVersion, $className);
			/**
			 * action {classname}_version_updated_<extension> when <extension> version changes
			 * @param	string	$curVersion currently installed version
			 * @param	string	$extVersion newly installed version
			 * @return	void
			 */
			$this->do_action( "version_updated_{$className}", $curVersion, $extVersion );
			/**
			 * AND/OR - use a direct call method
			 */
			if (method_exists($this->extension_objects[ $className ], 'adminVersionUpdate'))
			{
				call_user_func([$this->extension_objects[ $className ], 'adminVersionUpdate'], $curVersion, $extVersion);
			}
		}
		$this->extensionVersions[$className] = $extVersion;
	}


	/*
	 *
	 * Updating configuration file(s)
	 *
	 */


	/**
	 * Generic config file writable pathname (administrators only)
	 *
	 * @param string $filePath - path to config file
	 * @param string $fileId - used for hook name
	 * @param bool $useWPfs use wp filesystem (default)
	 * @return string|bool
	 */
	public function get_config_path(string $filePath, string $fileId='', bool $useWPfs=true)
	{
		global $wp_filesystem;
		// see if we can get to the config file (only single site or network admin)
		if ($this->is_admin() && (!is_multisite() || $this->is_network_admin()))
		{
			if (!$fileId) $fileId = pathinfo($filepath, PATHINFO_FILENAME);
			/**
			 * filter {classname}_{$fileId}_path
			 * set the pathname to the {filePath} file
			 * @param	string	default pathname
			 * @return	string	pathname
			 */
			if ($filePath = $this->apply_filters("{$fileId}_path}",$filePath))
			{
				if ($useWPfs && ($fs = apply_filters('eacDoojigger_load_filesystem',$wp_filesystem)))
				{
					$fsFilePath = $filePath;
					if (! $fs->exists($filePath)) {
						if (($fsFilePath = $fs->find_folder(dirname($filePath))) && $fs->is_writable($fsFilePath)) {
							$fsFilePath .= basename($filePath);
							$fs->put_contents($fsFilePath,'',FS_CHMOD_FILE & ~0007); // no world access
						}
					}
					return ($fs->is_writable($fsFilePath)) ? $fsFilePath : false;
				}

				if ( ! file_exists( $filePath ) ) {
					touch($filePath);
				}
				return (is_writable($filePath)) ? $filePath : false;
			}
		}
		return false;
	}


	/**
	 * Get the .htaccess pathname (administrators only)
	 *
	 * @param string $filePath - path to .htaccess
	 * @return	string|bool
	 */
	public function htaccess_handle($filePath=null)
	{
		// see if we can get to the .htaccess file (only single site or network admin)
		return $this->get_config_path($filePath ?: ABSPATH . '.htaccess', 'htaccess');
	}


	/**
	 * Get the wp-config.php editor (administrators only)
	 *
	 * @param string $filePath - path to wp-config.php
	 * @param bool $useTransformer
	 * @return	object|bool
	 */
	public function wpconfig_handle($filePath=null,$useTransformer=true)
	{
		// see if we can get to the wp-config file (only single site or network admin)
		if ($filePath = $this->get_config_path($filePath ?: ABSPATH . 'wp-config.php', 'wpconfig'))
		{
			if ($useTransformer)			// using wp-config-transformer
			{
				try {
					return new \EarthAsylumConsulting\Helpers\wpconfig_editor( $filePath );
				} catch (\Throwable $e) {return false;}
			}
		}
		return $filePath;
	}


	/**
	 * Get the .user.ini pathname (administrators only)
	 *
	 * @param string $filePath - path to .user.ini
	 * @return	string|bool
	 */
	public function userini_handle($filePath=null)
	{
		// see if we can get to the .user.ini file (only single site or network admin)
		return $this->get_config_path($filePath ?: ABSPATH . ini_get('user_ini.filename'), 'userini');
	}


	/**
	 * delete identifiable transients belonging to this plugin
	 *
	 * @used-by	flush_caches()
	 * @used-by	plugin_admin_deactivated()
	 * @param 	bool 	is network
	 * @return	void
	 */
	public function deleteTransients(bool $isNetwork=false): void
	{
		// clear our known transients (from caches)
		$this->delete_site_transient( self::PLUGIN_HEADER_TRANSIENT );
		$this->delete_transient( self::PLUGIN_EXTENSION_TRANSIENT );
		if (method_exists($this, 'deleteUpdaterTransient')) {
			$this->deleteUpdaterTransient();
		}
	/*
		// delete plugin transient data
		if ($isNetwork || $this->is_network_admin())
		{
			$this->wpdb->query(
					"DELETE FROM {$this->wpdb->sitemeta} ".
					"WHERE meta_key LIKE '%_transient_".$this->prefixTransientName('%')."'"
			);
			$this->wpdb->query(
					"DELETE FROM {$this->wpdb->sitemeta} ".
					"WHERE meta_key LIKE '%_transient_timeout_".$this->prefixTransientName('%')."'"
			);
		}
		else
		{
			$this->wpdb->query(
					"DELETE FROM {$this->wpdb->options} ".
					"WHERE option_name LIKE '%_transient_".$this->prefixTransientName('%')."'"
			);
			$this->wpdb->query(
					"DELETE FROM {$this->wpdb->options} ".
					"WHERE option_name LIKE '%_transient_timeout_".$this->prefixTransientName('%')."'"
			);
		}
	*/
	}


	/**
	 * Get the selected admin color scheme
	 *
	 * @return	array
	 */
	public function admin_color_scheme(): array
	{
		static $admin_colors = null;
		global $_wp_admin_css_colors;

		if (empty($admin_colors))
		{
			$colors = $_wp_admin_css_colors[ get_user_option( 'admin_color' ) ]
				?? 	array_shift($_wp_admin_css_colors);			// default

			$colors = $colors->colors;
			$admin_colors['base'] 		= array_shift($colors);	// 1st color
			$admin_colors['notify'] 	= array_pop($colors); 	// 4th or 3rd color
			$admin_colors['highlight']	= array_pop($colors);	// 3rd or 2nd color
			$admin_colors['icon'] 		= $colors[0] ??			// remaining color or
					$this->modifyColor($admin_colors['base'],-0.10);	// darken base
			$admin_colors['subtle'] 	=						// lighten icon color
					$this->modifyColor($admin_colors['icon'],0.85);
		}
		return $admin_colors;
	}


	/*
	 *
	 * admin notifications/settings errors
	 *
	 */


	/**
	 * settings errors / admin notices - on 'all_admin_notices' action.
	 * Add an action to output settings errors in the page footer
	 *
	 * @return	void
	 */
	public function plugin_admin_notices(): void
	{
		\add_action('admin_footer', function()
			{
				$this->settings_errors();
			}
		);
	}


	/**
	 * like WP add_settings_error() - add a settings error.
	 * prefer using add_option_error() which in turn uses this
	 *
	 * @param string $setting setting id ('settings-error','admin-notice')
	 * @param string $code field id
	 * @param string $message error / notice message
	 * @param string|array $errorType 'error', 'warning', 'info', 'success'
	 * @return	void
	 */
	public function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void
	{
		static $count = 0;
		$settings_errors = $this->get_settings_errors();
		$settings_errors[ md5($setting.$code.$message.$type) ] = array(
			'setting' => $setting,
			'code'    => $code.'-'.++$count,
			'message' => $message,
			'type'    => $type,
		);
		$this->set_transient('settings_errors',$settings_errors,10);
	}


	/**
	 * like WP get_settings_errors() - get any saved settings errors
	 *
	 * @return	array
	 */
	private function get_settings_errors(): array
	{
		return $this->get_transient('settings_errors') ?: [];
	}


	/**
	 * like WP settings_errors() - output any saved settings errors
	 *
	 * @return	void
	 */
	private function settings_errors(): void
	{
		$settings_errors = $this->get_settings_errors();
		if ( empty( $settings_errors ) ) return;
		$output = "<div style='margin-left: 160px;'>\n"; // we may get stuck in the footer
		foreach ( $settings_errors as $key => $details )
		{
			$output .= $this->get_admin_notice($details['message'],[
				'type'					=> $details['type'],
				'id'					=> $details['code'],
				'dismissible' 			=> true,
				'additional_classes' 	=> [ $details['setting'] ]
			]);
		}
		$output .= "</div>\n";
		echo $this->wp_kses($output);
		$this->delete_transient('settings_errors'); // we only think it was displayed
	}


	/**
	 * like WP wp_get_admin_notice() with polyfill
	 *
	 * @param string $message error / notice message
	 * @param array  $args wp_get_admin_notice args
	 * @return	string
	 */
	private function get_admin_notice(string $message, array $args): string
	{
		if (function_exists('wp_get_admin_notice'))
		{
			return wp_get_admin_notice($message,$args);
		}
		// polyfill
		$args = wp_parse_args($args,[
			'type'					=> 'info',
			'id'					=> null,
			'dismissible' 			=> true,
			'additional_classes' 	=> [],
			'attributes' 			=> [],
			'paragraph_wrap' 		=> true,
		]);
		$css_id 	= ($args['id']) ? " id='".esc_attr($args['id'])."'" : "";
		$css_class 	= ['notice','notice-'.esc_attr($args['type'])];
		if ($args['dismissible']) {
			$css_class[] = 'is-dismissible';
		}
		foreach ($args['additional_classes'] as $class) {
			$css_class[] = esc_attr($class);
		}
		if ($args['paragraph_wrap']) {
			$message = "<p>".$message."</p>";
		}
		return "<div{$css_id} class='".implode(' ',$css_class)."'>{$message}</div>\n";
	}


	/**
	 * helper function for option settings error
	 *
	 * @param string $optionName option/field name
	 * @param string $message error / notice message
	 * @param string $errorType error type (error)
	 * @param string $moreInfo additional line
	 * @return	void
	 */
	public function add_option_error(string $optionName, string $message, string $errorType='error', string $moreInfo=''): void
	{
		$this->add_settings_error(
			'settings-error',
			$optionName,
			$this->admin_notice_msg($message, $moreInfo),
			$errorType
		);
	}


	/**
	 * helper function for option settings warning
	 *
	 * @param string $optionName option/field name
	 * @param string $message error / notice message
	 * @param string $errorType error type (warning)
	 * @param string $moreInfo additional line
	 * @return	void
	 */
	public function add_option_warning(string $optionName, string $message, string $errorType='warning', string $moreInfo=''): void
	{
		$this->add_option_error($optionName, $message, $errorType, $moreInfo);
	}


	/**
	 * helper function for option settings notice
	 *
	 * @param string $optionName option/field name
	 * @param string $message error / notice message
	 * @param string $errorType error type (notice)
	 * @param string $moreInfo additional line
	 * @return	void
	 */
	public function add_option_notice(string $optionName, string $message, string $errorType='notice', string $moreInfo=''): void
	{
		$this->add_option_error($optionName, $message, $errorType, $moreInfo);
	}


	/**
	 * helper function for option settings info
	 *
	 * @param string $optionName option/field name
	 * @param string $message error / notice message
	 * @param string $errorType error type (info)
	 * @param string $moreInfo additional line
	 * @return	void
	 */
	public function add_option_info(string $optionName, string $message, string $errorType='info', string $moreInfo=''): void
	{
		$this->add_option_error($optionName, $message, $errorType, $moreInfo);
	}


	/**
	 * helper function for option settings success
	 *
	 * @param string $optionName option/field name
	 * @param string $message error / notice message
	 * @param string $errorType error type (success)
	 * @param string $moreInfo additional line
	 * @return	void
	 */
	public function add_option_success(string $optionName, string $message, string $errorType='success', string $moreInfo=''): void
	{
		$this->add_option_error($optionName, $message, $errorType, $moreInfo);
	}


	/**
	 * Add admin notice, maybe after all_admin_notices has fired.
	 *
	 * @param string $message message text
	 * @param string $errorType 'error', 'warning', 'info', 'success'
	 * @param string $moreInfo additional message text
	 * @return void
	 */
	public function add_admin_notice(string $message, string $errorType='info', string $moreInfo=''): void
	{
		static $adminNoticeCount = 1;
		$optionName = 'admin-notice-'.$adminNoticeCount++;
		do_action( "qm/{$errorType}", $message );
		$this->add_settings_error(
			'admin-notice',
			'admin-notice',
			$this->admin_notice_msg($message, $moreInfo),
			$errorType
		);
	}


	/**
	 * output admin notice immediately, like wp_admin_notice()
	 *
	 * @param string $message message text
	 * @param string $errorType 'error', 'warning', 'info', 'success'
	 * @param string $moreInfo additional message text
	 * @return void
	 */
	public function print_admin_notice(string $message, string $errorType='info', string $moreInfo=''): void
	{
		echo $this->wp_kses(
			$this->get_admin_notice(
				$this->admin_notice_msg($message, $moreInfo),
				[ 'type' => $errorType, 'dismissible' => true ]
			)
		);
	}


	/**
	 * format option message string
	 *
	 * @param string $message error / notice message
	 * @param string $moreInfo additional line
	 * @return	string
	 */
	private function admin_notice_msg(string $message, string $moreInfo=''): string
	{
		// $message may end up wrapped in <p>...</p> by get_admin_notice()
		$message = "<strong>".nl2br(__($message,$this->PLUGIN_TEXTDOMAIN))."</strong>";
		if (!empty($moreInfo))
		{
			$message .= '<br>'.nl2br(__($moreInfo,$this->PLUGIN_TEXTDOMAIN));
		}
		return $message;
	}


	/*
	 *
	 * plugin optional content (settings menu, contextual help)
	 *
	 */


	/**
	 * Required plugin files
	 *
	 * @return	void
	 */
	private function requireExtraPluginFiles(): void
	{
		require_once(ABSPATH . 'wp-includes/pluggable.php');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}


	/**
	 * Puts the configuration page in the menus - on 'admin_menu' action
	 *
	 * @return	void
	 */
	public function plugin_admin_add_settings_menu(): void
	{
		if (!current_user_can('manage_options')) return;

		$this->requireExtraPluginFiles();
		$displayName = $this->pluginHeader('Title');

		/*
		 * Technically, we should have unique slugs for each menu/sub-menu
		 * but that complicates things with multiple urls for the same page.
		 * Because of that, only 1 "load-{$hook_suffix}" action is fired and
		 * the only way to know {$hook_suffix} is from the current_screen action.
		 *		add_action('current_screen', function($screen)
		 *			{
		 *				// use global $hook_suffix or $screen->base
		 *			}
		 *		);
		 */

		// action: load-admin_page_eacdoojigger-site-settings
		add_submenu_page('options.php',
				$displayName,
				$displayName,
				'manage_options',
				$this->getSettingsSlug(),
			    $this->getSettingsCallback()
		);

		$optionsArray = $this->get_option('adminSettingsMenu');

		if ( is_array($optionsArray) )
		{
			if ( in_array('Plugins Menu',$optionsArray) )
			{
				// action: load-plugins_page_eacdoojigger-site-settings
				add_plugins_page($displayName,
							$displayName,
							'manage_options',
							$this->getSettingsSlug(),
						    $this->getSettingsCallback()
				);
			}
			if ( in_array('Settings Menu',$optionsArray) )
			{
				// action: load-settings_page_eacdoojigger-site-settings
				if ($this->is_network_admin()) {
					add_submenu_page('settings.php',
							$displayName,
							$displayName,
							'manage_options',
							$this->getSettingsSlug(),
						    $this->getSettingsCallback()
					);
				} else {
					add_options_page(
							$displayName,
							$displayName,
							'manage_options',
							$this->getSettingsSlug(),
						    $this->getSettingsCallback()
					);
				}
			}
			if ( in_array('Tools Menu',$optionsArray) )
			{
				// action: load-tools_page_eacdoojigger-site-settings
				add_management_page(
							$displayName,
							$displayName,
							'manage_options',
							$this->getSettingsSlug(),
						    $this->getSettingsCallback()
				);
			}
			if ( in_array('Main Sidebar',$optionsArray) )
			{
				// action: load-toplevel_page_eacdoojigger-site-settings
				add_menu_page(
							$displayName,
							$displayName,
							'manage_options',
							$this->getSettingsSlug(),
						    $this->getSettingsCallback(),
							'dashicons-admin-plugins',
							(($this->is_network_admin()) ? 21 : 66) // position after Plugins (20/65) menu item
				);
				$tab = false;
				$tabNames 	= $this->getTabNames();
				foreach ($tabNames as $tabName)
				{
					add_submenu_page($this->getSettingsSlug(),
							$displayName,//.'->'.$tabName,
							$tabName,
							'manage_options',
							$this->getSettingsSlug(($tab)?$tabName:''),
							$this->getSettingsCallback(),
					);
					$tab = true;
				}
			}
		}
	}


	/**
	 * when we're on our settings page
	 *
	 * @param string $isTab check specific tab name
	 * @return	bool
	 */
	public function isSettingsPage($isTab = null): bool
	{
		if (is_null($this->is_settings_page) || !empty($isTab))
		{
			$this->is_settings_page = false;
			if (!is_admin())
			{
				return $this->is_settings_page;
			}

			if ($isTab) $isTab = $this->toKeyString($isTab);
			if (wp_doing_ajax())
			{
				if (isset($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], $this->getSettingsSlug()))
				{
					$this->is_settings_page = true;
					if ($isTab) return ( str_ends_with($_SERVER['HTTP_REFERER'],$isTab) );
				}
			}
			else
			{
				if ( str_starts_with( $this->varGet('page') ?? '', $this->getSettingsSlug() ) )
				{
					$this->is_settings_page = true;
					if ($isTab) return ($isTab == $this->getCurrentTab());
				}
			}
		}
		return $this->is_settings_page;
	}


	/**
	 * get settings page callback
	 *
	 * @return	array	the settings page callback
	 */
	public function getSettingsCallback(): array
	{
		return [$this,'options_settings_page'];
	}


	/**
	 * Add Setting link on Plugins page - on 'plugin_action_links_{plugin}' action
	 *
	 * @return	array
	 */
	public function plugin_admin_add_settings_link(array $links, string $plugin_file, $plugin_data = []): array
	{
		if (empty($plugin_data)) return $links;
		$newLinks = [
			'settings' 	=> $this->getSettingsLink(),
		];

		return array_merge($newLinks,$links);
	}


	/**
	 * Add meta value to plugin row - on after_plugin_row_meta action
	 *
	 * @return	void
	 */
	public function plugin_admin_add_plugin_meta(string $plugin_file, $plugin_data = []): void
	{
		if ($plugin_file != $this->PLUGIN_SLUG) return;
		if ($stable = $this->pluginHeader('StableTag'))
		{
			if ($stable != $this->getVersion())
			{
				echo '<p>'.$this->getRelease(),'</p>';
			}
		}
	}


	/**
	 * Add contextual help via plugin_help trait (on admin_menu action)
	 *
	 * @return	void
	 */
	public function plugin_admin_add_settings_help(): void
	{
		if ( $this->pluginHelpEnabled() )
		{
			\add_action('current_screen', function($screen)
				{
					/**
					 * action {classname}_options_settings_help - contextual help tabs
					 * @return	void
					 */
					$this->do_action( 'options_settings_help' );
				},5 			// run this first to get our tab content
			);

			// actors may use 'current_screen' or 'options_settings_help' to add help content

			\add_action('current_screen', function($screen)
				{
					if ($this->className != 'eacDoojigger')
					{
						$this->addPluginSidebarLink(
							"<span class='dashicons dashicons-admin-plugins eac-gray'></span>".
								"<span class='eac-logo-orange'>{<span class='eac-logo-green'>eac</span>}Doojigger</span>",
							"https://eacdoojigger.earthasylum.com/",
							"This plugin is an {eac}Doojigger Derivative"
						);
					}

					$this->options_settings_page_info(true);
					$this->plugin_help_render($screen);
				},PHP_INT_MAX 	// run this last to render all content
			);
		}
	}


	/*
	 *
	 * options/settings backup/restore
	 *
	 */


	/**
	 * get all saved options for this plugin
	 *
	 * @param 	string	$prefix override default option prefix
	 * @return	array 	full (prefixed) option_name => option_value
	 */
	public function getSavedPluginOptions($prefix=null): ?array
	{
		$prefix = ($prefix) ? $prefix.'_%' : $this->prefixOptionName('%');
		$records = $this->wpdb->get_results(
			"SELECT option_name,option_value FROM {$this->wpdb->options} WHERE option_name LIKE '{$prefix}'"
		);

		if (is_wp_error($records) || empty($records)) return null;

		return array_reduce($records, function ($result, $record)
			{
				$result[ $record->option_name ] = maybe_unserialize($record->option_value);
				return $result;
			}, []
		);
	}


	/**
	 * get all saved options for this network
	 *
	 * @param 	string	$prefix override default option prefix
	 * @return	array 	full (prefixed) option_name => option_value
	 */
	public function getSavedNetworkOptions($prefix=null): ?array
	{
		if ( !is_multisite() ) return false;
		$prefix = ($prefix) ? $prefix.'_%' : $this->prefixOptionName('%');
		$records = $this->wpdb->get_results(
			"SELECT meta_key,meta_value FROM {$this->wpdb->sitemeta} WHERE meta_key LIKE '{$prefix}'"
		);

		if (is_wp_error($records) || empty($records)) return false;

		return array_reduce($records, function ($result, $record)
			{
				$result[ $record->option_name ] = maybe_unserialize($record->option_value);
				return $result;
			}, []
		);
	}


	/**
	 * option backup name
	 *
	 * @return	string
	 */
	protected function option_backup_name(): string
	{
		// prefix with 'backup_' to avoid collection with class options
		return 'backup_'.$this->prefixOptionName('options');
	}


	/**
	 * get the previously backed up options
	 *
	 * @return	array
	 */
	public function get_option_backup()
	{
		$optionName = $this->option_backup_name();
		return ($this->is_network_admin())
			? \get_network_option(null,$optionName)
			: \get_option($optionName);
	}


	/**
	 * backup currently registered options
	 *
	 * @return	void
	 */
	public function do_option_backup(): void
	{
		$optionName = $this->option_backup_name();
		if ($this->is_network_admin())
		{
			$backup = [
				'version' 	=> $this->getSemanticVersion()->primary,
				'timestamp'	=> time(),
				'network' 	=> [
					$this->networkOptions,
					$this->getSavedNetworkOptions(),
				],
			];
			\update_network_option( null, $optionName, $backup, 'no' );
		}
		else
		{
			$backup = [
				'version' 	=> $this->getSemanticVersion()->primary,
				'timestamp'	=> time(),
				'site'		=> [
					get_current_blog_id() => [
						$this->pluginOptions,
						$this->getSavedPluginOptions(),
					],
				]
			];
			\update_option( $optionName, $backup, 'no' );
		}
	}


	/**
	 * restore previously backed up options
	 *
	 * @return	void
	 */
	public function do_option_Restore(): void
	{
		// get unserialized options
		if ( $backup = $this->get_option_backup() )
		{
			if (isset($backup['{timestamp}']))		// older (pre-3.2) backup
			{
				foreach ($backup as $optionName => $optionValue) {
					if ($optionName == '{timestamp}') continue;
					$this->update_option( $this->removeClassNamePrefix($optionName), $optionValue );
				}
			}
			else
			{
				if (isset($backup['network']) && $this->is_network_admin())
				{
					$options = $backup['network'];
					$this->networkOptions  = $options[0];
					foreach ($options[1] as $optionName => $optionValue) {
						$this->update_network_option( $this->removeClassNamePrefix($optionName), $optionValue );
					}
					$this->networkOptionsUpdated = true;
				}
				if (isset($backup['site'],$backup['site'][get_current_blog_id()]))
				{
					$options = $backup['site'][get_current_blog_id()];
					$this->pluginOptions = $options[0];
					foreach ($options[1] as $optionName => $optionValue) {
						$this->update_option( $this->removeClassNamePrefix($optionName), $optionValue );
					}
					$this->pluginOptionsUpdated = true;
				}
			}
		}
	}


	/**
	 * get the previously backed up network options
	 *
	 * @return	array
	 */
	public function get_network_backup()
	{
		$optionName = 'network_'.$this->option_backup_name();
		return \get_network_option(null,$optionName);
	}


	/**
	 * backup current options for all sites
	 *
	 * @return	void
	 */
	public function do_network_backup(): void
	{
		if ($this->is_network_admin())
		{
			$optionName = 'network_'.$this->option_backup_name();
			$backup = [
				'version' 	=> $this->getSemanticVersion()->primary,
				'timestamp'	=> time(),
				'network' 	=> [
					$this->networkOptions,
					$this->getSavedNetworkOptions(),
				],
				'site' 		=> []
			];
			$this->forEachNetworkSite(function() use(&$backup) {
				$backup['site'][get_current_blog_id()] = [
					$this->pluginOptions,
					$this->getSavedPluginOptions(),
				];
			});
			\update_network_option( null, $optionName, $backup, 'no' );
		}
	}


	/**
	 * restore options for all sites
	 *
	 * @return	void
	 */
	public function do_network_restore(): void
	{
		if ($this->is_network_admin())
		{
			if ($backup = $this->get_network_backup())
			{
				if (isset($backup['{timestamp}']))		// older (pre-3.2) backup
				{
					foreach($backup as $siteId => $siteBackup) {
						if ($siteId == '{timestamp}') continue;
						if ($siteId == '{network}') {
							foreach($siteBackup as $optionName => $optionValue) {
								$this->update_network_option( null, $this->removeClassNamePrefix($optionName), $optionValue );
							}
						} else {
							$this->switch_to_blog( $siteId );
							foreach($siteBackup as $optionName => $optionValue) {
								$this->update_option( $this->removeClassNamePrefix($optionName), $optionValue );
							}
							$this->restore_current_blog();
						}
					}
				}
				else
				{
					if (isset($backup['network']) && $this->is_network_admin())
					{
						$options = $backup['network'];
						$this->networkOptions  = $options[0];
						foreach ($options[1] as $optionName => $optionValue) {
							$this->update_network_option( $this->removeClassNamePrefix($optionName), $optionValue );
						}
						$this->networkOptionsUpdated = true;
					}
					if (isset($backup['site']))
					{
						foreach ($backup['site'] as $siteId => $options) {
							$this->switch_to_blog( $siteId );
							$this->pluginOptions = $options[0];
							foreach ($options[1] as $optionName => $optionValue) {
								$this->update_option( $this->removeClassNamePrefix($optionName), $optionValue );
							}
							$this->pluginOptionsUpdated = true;
							$this->restore_current_blog();
						}
					}
				}
			}
		}
	}


	/*
	 *
	 * options/settings on admin page methods
	 *
	 */


	/**
	 * initialize options to default values and save to the database with add_option
	 *
	 * @return	void
	 */
	private function set_option_defaults(): void
	{
		$optionMetaData = $this->getOptionMetaData();
		foreach ($optionMetaData as $optionKey => $optionMeta)
		{
			if (!isset($optionMeta['default']) || in_array($optionKey[0],['_','-','.'])) continue;
			if ($this->get_option($optionKey) === false)
			{
				if (isset($optionMeta['encrypt']) && $optionMeta['encrypt']) {
					$this->update_option_encrypt($optionKey, $optionMeta['default']);
				} else {
					$this->add_option($optionKey, $optionMeta['default']);
				}
			}
		}
	}


	/**
	 * standardize the option's meta data
	 *
	 * @param	array 	option meta array
	 * @return	array 	optionName=>optionMeta
	 */
	private function standardizeOptionMeta(array $optionGroup): array
	{
		$optionMetaData = array();
		if (!empty($optionGroup))
		{
			foreach ($optionGroup as $optionName=>$optionMeta)
			{
				$optionName = $this->standardizeOptionName($optionName,false); // retain case of option name
				$optionMetaData[$optionName] = array_merge(self::OPTION_META_KEYS,$optionMeta);

				// advanced mode setting
				if ($optionMetaData[$optionName]['advanced'])
				{
					$level = ($this->isTrue($optionMetaData[$optionName]['advanced']))
						? 'default'
						: $optionMetaData[$optionName]['advanced'];
					if (!$this->isAdvancedMode('settings',$level))
					{
						$levels = (array)$level;
						foreach ($levels as $level)
						{
							if ($level != 'default' && $level[0] != '-' && !in_array($optionMetaData[$optionName]['type'],['display','hidden'])) {
								$level = ltrim($level,'-');
								/**
								 * filter {classname}_advanced_mode_field - add field to show (not) advanced enabled
								 *
								 * @param array field metadata [type,label,default,advancedMode]
								 * @param string field name
								 */
								if ($advanced = $this->apply_filters('advanced_mode_field',
									array_merge(self::OPTION_META_KEYS,[
										'type' 			=> 'advanced-mode',
										'label' 		=> $optionMetaData[$optionName]['label'],
										'default'		=> (str_word_count($level) > 1) ? $level : ucfirst($level).' Level Feature',
										'advancedMode' 	=> $level,
									]),$optionName)
								) {
									$optionMetaData["-advanced-{$optionName}"] = $advanced;
								}
							}
						}
						unset($optionMetaData[$optionName]);
					}
				}
			}
		}
		return $optionMetaData;
	}


	/**
	 * Get the $optionMetaData array (ungrouped)
	 *
	 * @param	string 	$optionGroup group name
	 * @return	array 	[optionName=>optionMeta]
	 */
	public function getOptionMetaData(string $optionGroup = ''): array
	{
		if ($this->is_network_admin()) {
			return $this->getNetworkMetaData($optionGroup);
		}
		$optionGroup = $this->standardizeOptionGroup($optionGroup);
		return ($optionGroup)
			? $this->optionMetaData[$optionGroup] ?? []
			: array_merge(...array_values($this->optionMetaData));
	}


	/**
	 * Get the $networkMetaData array (ungrouped)
	 *
	 * @param	string 	$optionGroup group name
	 * @return	array 	[optionName=>optionMeta]
	 */
	public function getNetworkMetaData(string $optionGroup = ''): array
	{
		$optionGroup = $this->standardizeOptionGroup($optionGroup);
		return ($optionGroup)
			? $this->networkMetaData[$optionGroup] ?? []
			: array_merge(...array_values($this->networkMetaData));
	}


	/**
	 * add additional options (meta) for the plugin (or extension)
	 *
	 * @param	string|array 	$optionGroup group name or [groupname, tabname]]
	 * @param	array 			$optionMeta group option meta
	 * @return	void
	 */
	public function registerPluginOptions($optionGroup, array $optionMeta = []): void
	{
		if (!$this->isSettingsPage()) return;

		if ( ! doing_action( 'set_current_user' ) && ! did_action( 'set_current_user' ) )
		{
			add_action( 'set_current_user', function() use($optionGroup, $optionMeta)
				{
					$this->registerPluginOptions($optionGroup, $optionMeta);
				}
			);
			return;
		}

		$optionGroup = $this->standardizeOptionGroup($optionGroup);
		if (is_array($optionGroup))
		{
			$optionTab 		= $optionGroup[1];
			$optionGroup 	= $optionGroup[0];
			/**
			 * filter {classname}_settings_tab_name - change tab name for option group
			 *
			 * @param string $optionTab
			 * @param string $optionGroup
			 * @param bool $isNetworkSettings
			 */
			$this->optionTabNames[$optionGroup] = $this->apply_filters('settings_tab_name',$optionTab,$optionGroup,false);
		}

		$optionMeta = array_merge($this->getOptionMetaData($optionGroup),$optionMeta);
		$this->optionMetaData[$optionGroup] = $this->standardizeOptionMeta($optionMeta);
		parent::registerPluginOptions($optionGroup, $optionMeta);
	}


	/**
	 * add network options (meta) for the plugin
	 *
	 * @param	string|array 	$optionGroup group name or [groupname, tabname]]
	 * @param	array 			$optionMeta group option meta
	 * @return	void
	 */
	public function registerNetworkOptions($optionGroup, array $optionMeta = []): void
	{
		if (!$this->isSettingsPage() || !is_multisite()) return;

		if ( ! doing_action( 'set_current_user' ) && ! did_action( 'set_current_user' ) )
		{
			add_action( 'set_current_user', function() use($optionGroup, $optionMeta)
				{
					$this->registerNetworkOptions($optionGroup, $optionMeta);
				}
			);
			return;
		}

		$optionGroup = $this->standardizeOptionGroup($optionGroup);
		if (is_array($optionGroup))
		{
			$optionTab 		= $optionGroup[1];
			$optionGroup 	= $optionGroup[0];
			/**
			 * filter {classname}_settings_tab_name - change tab name for option group
			 *
			 * @param string $optionTab
			 * @param string $optionGroup
			 * @param bool $isNetworkSettings
			 */
			$this->networkTabNames[$optionGroup] = $this->apply_filters('settings_tab_name',$optionTab,$optionGroup,true);
		}

		$optionMeta = array_merge($this->getNetworkMetaData($optionGroup),$optionMeta);
		$this->networkMetaData[$optionGroup] = $this->standardizeOptionMeta($optionMeta);
		parent::registerNetworkOptions($optionGroup, $optionMeta);
	}


	/**
	 * Tab groups
	 *
	 * @return	array
	 */
	private function getTabGroups(): array
	{
		if ($this->is_network_admin())
		{
			$optionMetaData = $this->networkMetaData;
			$optionTabNames = $this->networkTabNames;
			$tabTransient   = 'network_tab_names';
		}
		else
		{
			$optionMetaData = $this->optionMetaData;
			$optionTabNames = $this->optionTabNames;
			$tabTransient   = 'option_tab_names';
		}

		$tabGroups = array();
		foreach ($this->defaultTabs as $tabName)
		{
			$tabName = $this->standardizeOptionGroup($tabName);
			$tabGroups[$tabName] = [];
		}

		foreach ($optionMetaData as $optionGroup => $optionMeta)
		{
			if (empty($optionMeta)) continue;
			$tabName = (isset($optionTabNames[$optionGroup])) ? $optionTabNames[$optionGroup] : key($tabGroups);
			/**
			 * filter {classname}_{$optionGroup}_option_tabname
			 * set the tab name in the settings page for this group/class/extension
			 * @param	string	default tab name (general)
			 * @return	string	tab name
			 */
			$tabName = $this->apply_filters( "{$optionGroup}_option_tabname", $tabName );
			$tabGroups[$tabName][$optionGroup] = $optionMeta;
		}

		$tabGroups = array_filter($tabGroups);
		// save tab names for use outside of the admin options page - getTabGroups()
		$this->set_transient( $tabTransient, array_keys($tabGroups) );
		return $tabGroups;
	}


	/**
	 * Get tab names from transient saved in getTabGroups() from options_settings_page()
	 *
	 * @return	array
	 */
	protected function getTabNames(): array
	{
		$tabTransient 	= ($this->is_network_admin()) ? 'network_tab_names' : 'option_tab_names';
		$tabNames 		= $this->get_transient( $tabTransient, [] );
		if (empty($tabNames))
		{
			$tabNames 	= array_keys($this->getTabGroups());
		}
		return $tabNames;
	}


	/**
	 * get settings tab (i.e. the page for setting options)
	 *
	 * @return	string	the settings slug name
	 */
	public function getCurrentTab($tabNames=null): ?string
	{
		$plugin_page = $this->varGet('page');
		if (str_starts_with($plugin_page,$this->getSettingsSlug()))
		{
			if (empty($tabNames)) $tabNames = $this->getTabNames();
			return ltrim(
					$this->varGet('tab')
				?: 	str_replace($this->getSettingsSlug(),'',$plugin_page)
				?: 	$this->toKeyString(current($tabNames)),
			'-');
		}
		return null;
	}


	/**
	 * Settings group name
	 *
	 * @return	void
	 */
	private function getSettingsGroup($tab=''): string
	{
		return $this->toKeyString( $this->prefixOptionName($tab.'_settings') );
	}


	/**
	 * Creates HTML for the Administration page to set options for this plugin.
	 *
	 * @return	void
	 */
	public function options_settings_page(): void
	{
		if (!current_user_can('manage_options'))
		{
			$this->print_admin_notice("You do not have sufficient permissions to access this page.",'error');
			wp_die( __( 'Security Violation' ), 403 );
		}

		/**
		 * action {classname}_options_settings_page - last chance to register options
		 * @return	void
		 */
		$this->do_action( 'options_settings_page' );

		// clear the update transient to force an update check from the plugins page
		if (method_exists($this, 'deleteUpdaterTransient')) {
			$this->deleteUpdaterTransient();
		}

		$tabGroups 		= $this->getTabGroups();
		$currentTab 	= $this->getCurrentTab(array_keys($tabGroups));
		$tabName 		= array_filter(array_keys($tabGroups), function($tab) use($currentTab)
			{
				return $currentTab == $this->toKeyString($tab);
			}
		);
		if (empty($tabName))
		{
			$this->print_admin_notice("Invalid Request. Tab '{$currentTab}' not found.",'error');
			return;

		}
		$currentTab 	= $tabName = current($tabName); // tab display value
		$settingsGroup 	= $this->getSettingsGroup($currentTab);

		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			// set default values for non-existing options
			$this->set_option_defaults();
		}
		else
		{
			// delete enabled extension list transient
			$this->delete_transient( self::PLUGIN_EXTENSION_TRANSIENT );
			// process the posted fields
			$this->options_settings_page_post($settingsGroup);
		}

		// HTML for the page

		$pluginClass = $this->toKeyString( $this->prefixOptionName($this->className.'_settings'),'_' );
		echo "<div class='wrap {$pluginClass} {$settingsGroup}'>\n";

		$h1 = 	"<h1 id='settings_h1'>".
				__( $this->pluginHeader('Title') . ($this->is_network_admin() ? ' Network' : ' Site') . ' Settings', $this->PLUGIN_TEXTDOMAIN ).
				"</h1>\n";
		/**
		 * filter {classname}_options_form_h1_html
		 * @param	string	$h1 current html for h1 header
		 * @return	string	html for h1 header
		 */
		$h1 = $this->apply_filters( "options_form_h1_html", $h1 );

		$h2Version = ($stable = $this->pluginHeader('StableTag'))
			? " <small>(<abbr title='".$this->getRelease()."'>".
				"v".$this->getSemanticVersion()->primary."</abbr>)</small>"
			: " <small>(v".$this->getSemanticVersion()->primary.")</small>";
		$h2  =
		$h2a =	"<h2 id='settings_h2'>".
				"(<span style='color:var(--eac-admin-icon)' class='dashicons dashicons-admin-settings'></span>) ".
				__( $this->pluginHeader('Name'), $this->PLUGIN_TEXTDOMAIN ).
				$h2Version.' - '.
				__( $currentTab, $this->PLUGIN_TEXTDOMAIN ).
				"</h2>\n";
		// add clickable link to enable/disable advanced mode
	/*
		if ($this->allowAdvancedMode())
		{
			$switchTo 	= ($this->isAdvancedMode()) ? 'Disable' : 'Enable';
			$href 		= $this->add_admin_action_link( strtolower($switchTo).'_advanced_mode' );
			$h2a = preg_replace("|<span.*></span>|",
					"<a href='{$href}'>".
					"<span class='tooltip dashicons dashicons-admin-settings'".
					"title='{$switchTo} advanced mode'>".
					"</span></a>",
					$h2a
			);
		}
	*/
		if ($this->allowAdvancedMode())
		{
			$switchTo 	= ($this->isAdvancedMode()) ? 'Disable' : 'Enable';
			$switchFr 	= ($this->isAdvancedMode()) ? 'Advanced' : 'Essentials';
			$href 		= $this->add_admin_action_link( strtolower($switchTo).'_advanced_mode' );
			$h2a = preg_replace("|</h2>|",
					"<span style='float:right;font-size:.75em;font-weight:normal'>".
					"( <a href='{$href}'><abbr title='{$switchTo} advanced mode'>{$switchFr}</abbr></a> )".
					"</span></h2>",
					$h2
			);
		}
		/**
		 * filter {classname}_options_form_h2_html
		 * @param	string	$h2a current html for h2 header with advanced-mode link
		 * @param	string	$h2 current html for h2 header
		 * @return	string	html for h2 header
		 */
		$h2 = $this->apply_filters( "options_form_h2_html", $h2a, $h2 );

		/*
		 * Wrap headers in 'settings_heading' div.
		 * Above 'h1' filter may wrap <h1> tag with:
		 *		<div id='settings_banner'> to add a sticky banner.
		 * Above 'h1' filter may append <h1> tag with:
		 *		<div id='settings_info'> to add a block to the right side of the header.
		 *	e.g. <div id='settings_banner'><h1>...</h1><div id='settings_info'>...</div></div>
		 * .wp-header-end is a place holder where WP notices (settings_errors) will be placed.
		 */
		echo "<div id='settings_heading'>\n" .
			 $h1 .
			 "<div class='wp-header-end'></div>" .
			 $h2 .
			 "</div>\n";

		// add the system info drop-down
		$this->options_settings_page_info();

		/**
		 * action {classname}_options_settings_form - after $_POST fields processed, before form output
		 * @return	void
		 */
		$this->do_action( 'options_settings_form' );

		echo "<form name='options_form' id='options_form' method='post' enctype='multipart/form-data' action='".esc_url($_SERVER['REQUEST_URI'])."' style='margin-top: 1em;'>\n";
			// settings api - outputs hidden nonce, action, option_page and referer fields for a settings page.
			settings_fields($settingsGroup);

			$foundSubmit = false; // add '_btnSubmitOptions' to override default submit button

			// navigation tabs
			echo "\n<nav class='nav-tab-wrapper'>\n";
			foreach ($tabGroups as $tabName => $optionGroup)
			{
				if (empty($optionGroup)) continue;
				parse_str('page='.$this->getSettingsSlug($tabName),$query_args);
				echo "\t<a href='".
					add_query_arg($query_args,
					$_SERVER['REQUEST_URI']).
				//	admin_url('admin.php')).
					"' class='wp-ui-highlight nav-tab";
				if ($currentTab == $tabName) echo " nav-tab-active active";
				echo " button-primary'>$tabName</a>\n";
			}
			echo "</nav>\n";

			$optionGroup	= $tabGroups[$currentTab];

			echo "<div id='{$settingsGroup}' class='tab-content ".$this->unprefixOptionName($settingsGroup)."'>\n";
			echo "<nav class='tab-container'><!-- placeholder to convert groups to tabs --></nav>\n";
			foreach ($optionGroup as $groupName => $optionMeta)
			{
				// show plugin/extension group <section>, <header> and <fieldset>
				$this->options_settings_page_section($groupName, $optionMeta, $currentTab);

				// show all group/extension options
				foreach ($optionMeta as $optionKey => $optionData)
				{
					// add help field
					if ($optionData['type'] == 'help')
					{
						$this->options_settings_page_help([$groupName,$currentTab], $optionKey, $optionData);
						continue;
					}

					if (substr($optionKey,1) == 'btnSubmitOptions') $foundSubmit = true;

					// get option value
					$optionValue = (isset($optionData['encrypt']) && $optionData['encrypt'])
						? $this->get_option_decrypt($optionKey,$optionData['default'] ?: false)
						: $this->get_option($optionKey,$optionData['default'] ?: false);

					// add label and field with grid <div>s
					$this->options_settings_page_block($optionKey, $optionData, $optionValue);

					// add field help
					$this->options_settings_page_help([$groupName,$currentTab], $optionKey, $optionData);
				}
				echo "</fieldset>\n";
				echo "</section>\n";
			}

			echo "</div>\n"; // this tab

			// primary submit button
			if (!$foundSubmit)
			{
				echo "<p id='option_submit'>";
				submit_button("Save {$currentTab} Settings",'primary','_btnSubmitOptions',false);
				echo "</p>\n";
			}

		echo "</form>\n";

		/**
		 * action {classname}_options_settings_page_footer - end of options settings page
		 * @return	void
		 */
		$this->do_action( 'options_settings_page_footer' );
		echo "</div>\n";	// wrap
	}


	/**
	 * Show system information on settings page
	 *
	 * @return	void
	 */
	private function options_settings_page_info($asHelp = false): void
	{
		static $done = null;
		if ($done) return;

		$phpUser = (function_exists('posix_getpwuid')) ? posix_getpwuid(posix_geteuid())['name'] : getenv('USERNAME');

		if ($ftpUser = \get_option('ftp_credentials','')) {
			$ftpUser = 'User: '.$ftpUser['username'];
		}

		$environment = (function_exists('\wp_get_environment_type'))
			? \wp_get_environment_type()
			: $this->get_option('siteEnvironment');

		$info = "";

		if (function_exists('php_uname')) { // may be disabled
			$info .= "<tr><td>". __('System', $this->PLUGIN_TEXTDOMAIN) ."</td>";
			$info .= "<td>". esc_attr(php_uname()) ."</td></tr>\n";
		}

		$info .= "<tr><td>". __('Server', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". esc_attr($this->varServer('SERVER_SOFTWARE')) .
				 " (". esc_attr($this->varServer('SERVER_NAME'))." | ".esc_attr($this->varServer('SERVER_ADDR')) .")</td></tr>\n";

		$info .= "<tr><td>". __('PHP Version', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". phpversion()." (".php_sapi_name().")" ."</td></tr>\n";

		$info .= "<tr><td>". __('PHP Memory Used', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". round((memory_get_peak_usage(false) / 1024) / 1024).'M of '.ini_get('memory_limit') ."</td></tr>\n";

		$info .= "<tr><td>". __('PHP User', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". $phpUser ."</td></tr>\n";

		$info .= "<tr><td>". __('WordPress Version', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". get_bloginfo('version') ."</td></tr>\n";

		$info .= "<tr><td>". __('WordPress Environment', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". esc_attr($environment) ."</td></tr>\n";

		$info .= "<tr><td>". __('WordPress File Access', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". get_filesystem_method()." ".$ftpUser ."</td></tr>\n";

		$info .= "<tr><td>". __('MySQL Version', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". $this->wpdb->db_server_info() ."</td></tr>\n";

		$info .= "<tr><td>". __('Database', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". __('Name',$this->PLUGIN_TEXTDOMAIN).': "'.$this->wpdb->dbname.'", '.
					 __('Table Prefix',$this->PLUGIN_TEXTDOMAIN).': "'.$this->wpdb->prefix.'"' ."</td></tr>\n";

		if (defined('WC_VERSION')) {
			$info .= "<tr><td>". __('WooCommerce Version', $this->PLUGIN_TEXTDOMAIN) ."</td>";
			$info .= "<td>". WC_VERSION ."</td></tr>\n";
		}

		if (defined('EACDOOJIGGER_VERSION')) {
			$info .= "<tr><td>". __('eacDoojigger Version', $this->PLUGIN_TEXTDOMAIN) ."</td>";
			$info .= "<td>". $this->getRelease() ."</td></tr>\n";
		}

		$info .= "<tr><td>". __('Plugin Base', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". $this->PLUGIN_SLUG .' ('.get_class($this).')' ."</td></tr>\n";

		$info .= "<tr><td>". __('Plugin Installed', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". wp_date($this->date_time_format,filemtime($this->pluginHeader('PluginDir'))) ."</td></tr>\n";

		$info .= "<tr><td>". __('Network Enabled', $this->PLUGIN_TEXTDOMAIN) ."</td>";
		$info .= "<td>". ( ($this->is_network_enabled()) ? 'True' : 'False' ) ."</td></tr>\n";

	//	$info .= "<tr><td>". __('Parent Page', $this->PLUGIN_TEXTDOMAIN) ."</td>";
	//	$info .= "<td>". get_admin_page_parent() ."</td></tr>\n";

	//	$info .= "<tr><td>". __('Page Title', $this->PLUGIN_TEXTDOMAIN) ."</td>";
	//	$info .= "<td>". get_admin_page_title() ."</td></tr>\n";

		/**
		 * filter {classname}_options_form_system_info
		 * @param	string	$info current table rows for system info
		 * @return	string	updated table rows for system info
		 */
		$info = "<table id='settings_system_info'><tbody'>\n".
				$this->apply_filters( "options_form_system_info", $info ).
				"</tbody></table>\n";

		if ($asHelp)
		{
			$this->addPluginHelpTab('System Info',$info,['System Information','open'],PHP_INT_MAX);
		}
		else
		{
			echo "<details><summary>System Information</summary>\n{$info}\n</details>\n";
		}
		$done = true;
	}


	/**
	 * Process _POSTed settings
	 *
	 * @param 	string $settingsGroup form/tab settings group
	 * @return	void
	 */
	private function options_settings_page_post($settingsGroup): void
	{
		$validNonce = (isset($_REQUEST['_fs_nonce']))
			? wp_verify_nonce($_REQUEST['_fs_nonce'],'filesystem-credentials')
			: wp_verify_nonce($_REQUEST['_wpnonce'], "{$settingsGroup}-options");
		if (! $validNonce)
		{
			$this->print_admin_notice("This page has expired or is no longer accessable",'error');
			wp_die( __( 'Security Violation' ), 403 );
		}
		if ( ! current_user_can('manage_options') )
		{
			$this->print_admin_notice("You do not have sufficient permissions to access this page.",'error');
			wp_die( __( 'Security Violation' ), 403 );
		}

		// Save Posted Options
		$optionMetaData = $this->getOptionMetaData();
		$optionMetaPosted  = [];
		foreach ($optionMetaData as $optionKey => $optionMeta)
		{
			if ( ! array_key_exists($optionKey,$_POST) ) continue;

			// get currently saved value
			$savedOptionValue = (isset($optionMeta['encrypt']) && $optionMeta['encrypt'])
				? $this->get_option_decrypt($optionKey,$optionMeta['default'] ?: false)
				: $this->get_option($optionKey,$optionMeta['default'] ?: false);

			// clean POST value
			$_POST[$optionKey] = (!is_array($_POST[$optionKey]))
				? wp_unslash(trim($_POST[$optionKey]))
				: wp_unslash($_POST[$optionKey]);

			// sanitize value
			$values = $this->options_settings_page_sanitize(
				$_POST[$optionKey],
				$optionKey,$optionMeta,$savedOptionValue
			);

			// sanitize mustn't change value
			if ($_POST[$optionKey] != $values)
			{
				$this->add_option_error($optionKey,
					sprintf('%s : The value entered does not meet the criteria for this field.',$optionMeta['label'])
				);
				// revert to prior value;
				$values = $savedOptionValue;
			}

			/**
			 * filter {classname}_sanitize_option sanitize option value when posted from admin page
			 * @param	mixed	$values posted option value(s)
			 * @param	string	$optionKey option name
			 * @param	array	$optionMeta option meta data
			 * @param	mixed	$optionValue current option value
			 * @return	mixed	new option value(s)
			 */
			$values = $this->apply_filters( "sanitize_option", $values, $optionKey, $optionMeta, $savedOptionValue );

			// optional validate callback, unlike sanitize, may change value
			$values = $this->options_settings_page_validate(
				$values,
				$optionKey,$optionMeta,$savedOptionValue
			);

			if ($values === false)
			{
				$this->add_option_error($optionKey,
					sprintf('%s : The value entered does not meet the criteria for this field.',$optionMeta['label'])
				);
				// revert to prior value;
				$values = $savedOptionValue;
			}

			// apply optional PHP filter
			if ($filter = (isset($optionMeta['filter'])) ? $optionMeta['filter'] : false)
			{
				if (!is_array($filter)) $filter = [$filter,null];
				$filter = $this->getFilterCallback($filter[0],$filter[1],[$optionKey, $optionMeta, $savedOptionValue]);
				$values = (is_array($values) && empty($filter[1]))
						? \filter_var($values,$filter[0],FILTER_REQUIRE_ARRAY)
						: \filter_var($values,$filter[0],$filter[1]);
			}

			/**
			 * filter {classname}_options_form_post_{optionKey} capture option value when posted from admin page
			 * @param	mixed	$values posted option value(s)
			 * @param	string	$optionKey option name
			 * @param	array	$optionMeta option meta data
			 * @param	mixed	$optionValue current option value
			 * @return	mixed	new option value(s)
			 */
			$values = $this->apply_filters( "options_form_post_{$optionKey}", $values, $optionKey, $optionMeta, $savedOptionValue );

			// update the option value
			if (!in_array($optionKey[0],['_','-','.']) && ($savedOptionValue !== $values))
			{
				if (isset($optionMeta['encrypt']) && $optionMeta['encrypt']) {
					$this->update_option_encrypt($optionKey, $values);
				} else {
					$this->update_option($optionKey, $values);
				}

				// store this field for 'options_form_post' action
				$optionMeta['priorValue'] 		= $savedOptionValue;
				$optionMeta['postValue'] 		= $values;
				$optionMetaPosted[$optionKey] 	= $optionMeta;
			}
		}

		/**
		 * action {classname}_options_form_post after processing all fields
		 * @param	array	$optionMetaPosted posted fields [fieldName => fieldMetaData]
		 * @return	void
		 */
		$this->do_action( "options_form_post", $optionMetaPosted );

		// debugging turned on
		if (!empty($this->logging_filter))
		{
			foreach ($optionMetaPosted as $fieldName => $metaData)
			{
				$this->logDebug( [$metaData['priorValue'], $metaData['postValue'] ], $fieldName.' updated');
			}
		}

		if (!empty($optionMetaPosted))
		{
			$this->add_option_success($settingsGroup,"Your settings have been updated!");
		}
	}


	/**
	 * Sanitize _POSTed fields
	 *
	 * @param	mixed	$values posted option value(s)
	 * @param	string	$optionKey option name
	 * @param	array	$optionMeta option meta data
	 * @param	mixed 	$savedOptionValue current value for $optionKey
	 * @return	mixed	sanitized option value(s)
	 */
	public function options_settings_page_sanitize($values,$optionKey,$optionMeta,$savedOptionValue=null)
	{
		if (empty($values)) return $values;

		if (isset($optionMeta['sanitize']))
		{
			return ($this->isFalse($optionMeta['sanitize']))
				? $values
				: call_user_func($optionMeta['sanitize'],$values,$optionKey,$optionMeta,$savedOptionValue);
		}

		switch ($optionMeta['type'])
		{
			case 'select':
			case 'radio':
			case 'checkbox':
			case 'toggle':
			case 'switch':
				// submitted value must be in the option values
				$valid = array_column($this->getOptionChoiceArray($optionMeta['options']),'value');
				if (is_array($values)) {
					if (array_diff($values, $valid)) {
						$values = null; 	// invalid value, revert to saved
					}
				} else {
					if (!empty($values) && !in_array($values,$valid)) {
						$values = null; 	// invalid value, revert to saved
					}
				}
				break;
			case 'date': 				// Y-m-d
				if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$values)) $values = null;
				break;
			case 'datetime':
			case 'datetime-local':		// Y-m-d\TH:i
				if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$values)) $values = null;
				break;
			case 'time':				// H:i
				if (!preg_match('/^\d{2}:\d{2}$/',$values)) $values = null;
				break;
			case 'week':				// Y-\Ww
				if (!preg_match('/^\d{4}-W\d{1,2}$/',$values)) $values = null;
				break;
			case 'month':				// Y-m
				if (!preg_match('/^\d{4}-\d{2}$/',$values)) $values = null;
				break;
			case 'range':
				$values  = \filter_var( $values,
								FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE
							);
				break;
			case 'number':
				$values  = \filter_var( $values,
								FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION|FILTER_NULL_ON_FAILURE
							);
				break;
			case 'email':
				$values  = sanitize_email($values) ?: null;
				break;
			case 'url':
				$values  = sanitize_url($values) ?: null;
				break;
			case 'color':
				$values  = sanitize_hex_color($values) ?: null;
				break;
			case 'hidden':
			case 'textarea':
				$values  = sanitize_textarea_field($values) ?: null;
				break;
			case 'codeedit-js':
			case 'codeedit-css':
			case 'codeedit-html':
			case 'codeedit-php':
				$values = $this->wp_kses( $values );
				break;
			case 'html':
				// the wp_editor forces code format incompatible with wp_kses
				$result = $this->wp_kses( $values );
				if (str_replace([';',' '],'',$values) == str_replace([';',' '],'',$result)) {
					$_POST[$optionKey] = $result;
				}
				$values = $result;
				break;
			case 'file':
				$values = wp_handle_upload( $_FILES["{$optionKey}_file"], ['test_form' => false] );
				if (array_key_exists('error',$values))
				{
					$this->add_option_error(
						$optionKey,
						sprintf('%s : %s',$optionMeta['label'],$values['error'])
					);
				}
				$_POST[$optionKey] = $values;
				break;
			case 'custom':
				break;
			default:
				$values  = sanitize_text_field($values) ?: null;
				break;
		}
		return $values;
	}


	/**
	 * Validate _POSTed fields
	 *
	 * @param	mixed	$values posted option value(s)
	 * @param	string	$optionKey option name
	 * @param	array	$optionMeta option meta data
	 * @param	mixed 	$savedOptionValue current value for $optionKey
	 * @return	mixed	sanitized option value(s)
	 */
	public function options_settings_page_validate($values,$optionKey,$optionMeta,$savedOptionValue=null)
	{
		if (isset($optionMeta['validate']))
		{
			return ($this->isFalse($optionMeta['validate']))
				? $values
				: call_user_func($optionMeta['validate'],$values,$optionKey,$optionMeta,$savedOptionValue);
		}
		return $values;
	}


	/**
	 * for html input fields, get section headers
	 *
	 * @param	string 	$groupName option group name
	 * @param	array 	$optionMeta meta-data for $group
	 * @return	void
	 */
	public function options_settings_page_section(string $groupName, array &$optionMeta, string $currentTab=''): void
	{
		$groupName = esc_attr($groupName);

		/**
		 * filter {classname}_options_group_meta_{optionKey} customize field input arrays
		 * @param	array	$optionMeta option meta data
		 * @return	array	updated meta
		 */
		$optionMeta = $this->apply_filters( "options_group_meta_{$groupName}", $optionMeta );

		// show plugin/extension group
		$groupClass 	= $this->toKeyString($groupName,'_');
		echo "<!-- [ {$groupName} ] -->\n";
		echo "<section class='settings-grid {$groupClass}' data-name='{$groupClass}' data-title='{$groupName}'>\n";

		// the '_enabled' option
		$optionKey 		= basename($this->toKeyString($groupName,'_'),'_extension').'_extension_enabled';
		$optionData 	= $optionMeta[$optionKey] ?? [];
		$optionValue 	= ($optionData)
			? ($optionData['value'] ?? $this->get_option($optionKey))
			: false;
		/**
		 * filter {classname}_settings_group_label - change display label for option group
		 *
		 * @param string $groupLabel
		 * @param string $groupName
		 */
		$displayName 	= $this->apply_filters('settings_group_label',
			($optionData) ? $optionData['label'] ?: $groupName : $groupName,
			$groupName,
		);

		echo "<header class='settings-grid-container {$groupClass}' data-name='{$groupClass}'>\n";

		// data-toggle is clickable and toggles the $groupClass fieldset
		echo "\t<details ".($optionValue===''?'':'open').
			 " class='settings-grid-head settings-grid-head-label wp-ui-highlight' data-toggle='{$groupClass}'>";
		echo "<summary>{$displayName}</summary>";
		echo "</details>\n";

		echo "\t<div class='settings-grid-head settings-grid-head-enable wp-ui-highlight'>";
		if ($optionData)
		{
			if (is_string($optionData['tooltip']) && !empty($optionData['tooltip']))
			{
				$optionData['tooltip'] = $this->wp_kses($optionData['tooltip'],['a'=>false,'details'=>false]);
			}
			// add tooltip
			if (is_string($optionData['tooltip']) && !empty($optionData['tooltip']))
			{
				echo "\n\t\t<span class='settings-tooltip dashicons dashicons-editor-help' style='margin-left: -1.35em;' title=\"".trim(strip_tags($displayName))."\">".
					 __($optionData['tooltip'],$this->PLUGIN_TEXTDOMAIN)."</span>";
			}
			$optionData['attributes']['onclick'] = "eacDoojigger.toggle_settings('{$groupClass}',this.firstElementChild.checked);";
			$this->options_settings_page_field($optionKey, $optionData, $optionValue);
			unset($optionMeta[$optionKey]);
			// add field help
		}
		if ($currentTab) {
			if (!isset($optionData['help'])) $optionData['help'] = '&nbsp;';
			$optionData['label'] = "<strong>".$displayName."</strong>";
			$this->options_settings_page_help([$groupName,$currentTab], $optionKey, $optionData);
		}
		echo "</div>\n"; 	// enabled
		echo "</header>\n";	// container

		$optionClass 	= ($optionValue==='') // set but not enabled
			? "{$groupClass} settings-closed"
			: "{$groupClass} settings-opened";

		echo "<fieldset class='settings-grid-container {$optionClass}' data-name='{$groupClass}'>\n";

		/**
		 * filter {classname}_automatic_tooltip convert info to tooltip when true
		 * @param	bool
		 * @param	string	$groupName group name
		 * @param	array	$optionData option meta data
		 * @return	bool
		 */
		$this->automatic_tooltips = $this->apply_filters("automatic_tooltips",$this->automatic_tooltips,$groupName,$optionData);
	}


	/**
	 * for html input fields, get input field block
	 *
	 * @param	string 	$optionKey option/field name
	 * @param	array 	$optionMeta meta-data for $fieldName
	 * @param	mixed 	$optionValue current value for $fieldName
	 * @param	string	$width optional, override default field width (columns)
	 * @param	string	$height optional, override default field height (rows)
	 * @return	void
	 */
	public function options_settings_page_block(string $optionKey, array $optionMeta, $optionValue, $width='50', $height='4'): void
	{
		if (! is_array($optionMeta) || count($optionMeta) < 2) return;
		if ($optionMeta['type'] == 'help') return;

		$optionMeta = array_merge(self::OPTION_META_KEYS,$optionMeta);
		$optionMeta['value'] =  $optionValue ?: $optionMeta['default'];

		$displayName = __($this->wp_kses($optionMeta['label'] ?? ''),$this->PLUGIN_TEXTDOMAIN);
		$style = ($optionMeta['type'] == 'hidden') ? " style='display:none;'" : "";

		// process string replacement [meta name] with meta value
		$meta 	= array_filter($optionMeta,function($v,$k){
			return in_array($k,['type','label','default','title','before','after','info','tooltip','help','value']) && is_scalar($v);
		},ARRAY_FILTER_USE_BOTH);
		$keys 	= array_map(function($k){return "[{$k}]";},array_keys($meta));
		$values = array_values($meta);

		foreach ($optionMeta as $k => $v)
		{
			if (array_key_exists($k, $meta)) {
				$optionMeta[$k] = trim(str_replace($keys,$values,$v));
			}
		}

		echo "\t<!-- ".trim(strip_tags($displayName))." -->\n";
		echo "\t<div class='settings-grid-item settings-grid-item-label'{$style}>\n\t\t".
				"<label class='settings-grid-label' for='{$optionKey}'>{$displayName}</label>";

		// maybe move [info] to [tooltip]
		if ( $this->automatic_tooltips && !empty($optionMeta['info']) && ($optionMeta['tooltip'] === '' || $optionMeta['tooltip'] === true) )
		{
			$info 	= str_replace(['<br>','<br/>'],'<br />',$optionMeta['info']);
			$tooltip= $this->wp_kses($info,['a'=>false,'details'=>false]);
			if ($info == $tooltip) {
				$tooltip = str_replace(['<mark>','</mark>'],['<small><em>','</em></small>'],$optionMeta['info']);
				$optionMeta['tooltip'] = str_replace(['"',"\n"],['&quot;',"<br>"],trim($tooltip));
				$optionMeta['info'] = '';
			}
		}
		else if (is_string($optionMeta['tooltip']) && !empty($optionMeta['tooltip']))
		{
			$optionMeta['tooltip'] = $this->wp_kses($optionMeta['tooltip'],['a'=>false,'details'=>false]);
		}

		// add tooltip
		if (is_string($optionMeta['tooltip']) && !empty($optionMeta['tooltip']))
		{
			echo "\n\t\t<span class='settings-tooltip dashicons dashicons-editor-help' title=\"".trim(strip_tags($displayName))."\">".
				 __($optionMeta['tooltip'],$this->PLUGIN_TEXTDOMAIN)."</span>";
		}

		echo "\n\t</div>\n";

		$inputClass = explode('-',esc_attr($optionMeta['type']));
		$inputClass = "input-{$inputClass[0]}";

		// add the input field
		echo "\t<div class='settings-grid-item settings-grid-item-{$inputClass}'{$style}>";
		$this->options_settings_page_field($optionKey, $optionMeta, $optionValue, $width, $height);
		echo "\n\t</div>\n";
	}


	/**
	 * create the correct form input control for an option (used here and in custom WP_POST fields)
	 *
	 * @param	string 	$optionKey option name
	 * @param	array 	$optionMeta meta-data for $optionKey
	 * @param	mixed 	$savedOptionValue current value for $optionKey
	 * @param	string	$width optional, override default field width (columns)
	 * @param	string	$height optional, override default field height (rows)
	 * @return	void
	 */
	public function options_settings_page_field(string $optionKey, array $optionMeta, $savedOptionValue, $width='50', $height='4'): void
	{
		if (! is_array($optionMeta) || count($optionMeta) < 2) return;
		if ($optionMeta['type'] == 'help') return;

		$optionKey = esc_attr($optionKey);
		$displayName = __($this->wp_kses($optionMeta['label'] ?? ''),$this->PLUGIN_TEXTDOMAIN);

		// maybe convert single checkbox to switch
		if ($optionMeta['type'] == 'checkbox')
		{
			if (count($optionMeta['options']) == 1 && isset($optionMeta['options'][0]) && $optionMeta['options'][0] == 'Enabled') {
				$optionMeta['type'] = 'switch';
			}
		}

		/**
		 * filter {classname}_options_field_meta_{optionKey} customize field input array
		 * @param	array	$optionMeta option meta data
		 * @param	mixed	$optionValue current option value
		 * @return	array	updated meta
		 */
		$optionMeta = $this->apply_filters( "options_field_meta_{$optionKey}", $optionMeta, $savedOptionValue );

		// parse field attributes
		$attributes = (! empty($optionMeta['attributes']))
			? $this->parseAttributes($optionMeta['attributes'])
			: [];

		// class on <input> tag (full type required)
		$inputClass = "input-".esc_attr($optionMeta['type']);

		if (isset($optionMeta['encrypt']) && $optionMeta['encrypt'])
		{
			$inputClass .= ' input-encrypted';
		}
		if (isset($optionMeta['class']))
		{
			$inputClass .=  ' '.esc_attr($optionMeta['class']);
		}
		if (isset($attributes['class']))
		{
			$inputClass .=  ' '.esc_attr($attributes['class']);
		}
		else if (in_array($optionMeta['type'],['button','submit','reset','image']))
		{
			$inputClass .=  ' button button-large';
		}
		unset($attributes['class']);

		// override default width for number field
		if ($optionMeta['type'] == 'number' && $width == '50')
		{
			$width = (isset($attributes['max'])) ? strlen($attributes['max']) : 10;
		}

		$maxWidth 	= $optionMeta['width'] = esc_attr( $optionMeta['width'] ?? $width );
		$maxHeight 	= $optionMeta['height']= esc_attr( $optionMeta['height'] ?? $height );

		// check/add 'style'
		$attributes['style'] = "--text-width:{$maxWidth}ch;";
		if (in_array($optionMeta['type'],['radio','checkbox','switch','toggle']))
		{
			$attributes['style'] .= 'white-space:nowrap;';
		}
		if (isset($optionMeta['style']))
		{
			$attributes['style'] .= esc_attr($optionMeta['style']);
		}

		// implode attributes to a valid html string
		$parentAtts = '';
		array_walk($attributes, function(&$value,$key) use (&$parentAtts)
			{
				$value = esc_attr($key) . '=' . '"'.str_replace(['"',"\\'"],"'",trim($value,"'\"")).'"';
				if (in_array($key,['disabled','required'])) $parentAtts .= ' '.$value;
			}
		);
		$attributes = ' '.implode(' ', $attributes);

		$optionMeta['attributes'] = $attributes; // formatted when passed through filters

		// build the html for the input field
		$html = '';
		$tab  = "\n\t\t";

		if (!empty($optionMeta['title']))
		{
			$html .= $tab."<blockquote>".__($this->wp_kses($optionMeta['title']),$this->PLUGIN_TEXTDOMAIN)."</blockquote>";
		}
		if (!empty($optionMeta['before']))
		{
			$html .= $tab.__($this->wp_kses($optionMeta['before']),$this->PLUGIN_TEXTDOMAIN);
		}

		switch ($optionMeta['type'])
		{
			case 'select':
				$choices = $this->getOptionChoiceArray($optionMeta['options']); // uses esc_attr()
				$html 	.= $tab."<select class='{$inputClass}' name='{$optionKey}' id='{$optionKey}'{$attributes}>";
				$current = (is_array($savedOptionValue)) ? reset($savedOptionValue) : $savedOptionValue;
				foreach ($choices as $choice) {
					if (is_null($choice)) continue;
					$selected = ($choice['value'] == $current) ? ' selected' : '';
					$html .= "<option value='{$choice['value']}'{$selected}>{$choice['option']}</option>";
				}
				$html .= "</select>";
				break;

			case 'radio':
				$choices = $this->getOptionChoiceArray($optionMeta['options']); // uses esc_attr()
				$html 	.= $tab;
				$current = (is_array($savedOptionValue)) ? reset($savedOptionValue) : $savedOptionValue;
				foreach ($choices as $id=>$choice) {
					if (is_null($choice)) continue;
					$selected = ($choice['value'] == $current) ? " checked='checked'" : "";
					$html .= "<span class='{$inputClass}-wrap'{$attributes}>".
							 "<input class='{$inputClass}' type='radio' name='{$optionKey}' id='{$optionKey}_{$id}'{$parentAtts} ".
							 "value='{$choice['value']}'{$selected} />".
							 "<label for='{$optionKey}_{$id}'>{$choice['option']}</label></span> ";
				}
				break;

			case 'checkbox':
				$choices = $this->getOptionChoiceArray($optionMeta['options']); // uses esc_attr()
				$name 	 = (count($choices) == 1) ? $optionKey : $optionKey.'[]';
				$html 	.= $tab."<input type='hidden' name='{$optionKey}' value=''{$parentAtts} />";
				$current = (!is_array($savedOptionValue)) ? array($savedOptionValue) : $savedOptionValue;
				foreach ($choices as $id=>$choice) {
					if (is_null($choice)) continue;
					$selected = (in_array($choice['value'],$current)) ? " checked='checked'" : "";
					$html .= "<span class='{$inputClass}-wrap'{$attributes}>".
							 "<input class='{$inputClass}' type='checkbox' name='{$name}' id='{$optionKey}_{$id}'{$parentAtts} ".
							 "value='{$choice['value']}'{$selected} />".
							 "<label for='{$optionKey}_{$id}'>{$choice['option']}</label></span> ";
				}
				break;

			case 'toggle':
			case 'switch':
				$choices = $this->getOptionChoiceArray($optionMeta['options']); // uses esc_attr()
				$name 	 = (count($choices) == 1) ? $optionKey : $optionKey.'[]';
				$html 	.= $tab."<input type='hidden' name='{$optionKey}' value=''{$parentAtts} />";
				$current = (!is_array($savedOptionValue)) ? array($savedOptionValue) : $savedOptionValue;
				foreach ($choices as $id=>$choice) {
					if (is_null($choice)) continue;
					$selected = (in_array($choice['value'],$current)) ? " checked='checked'" : "";
					$html .= "<span class='input-switch-wrap'>".
							 "<label for='{$optionKey}_{$id}'{$attributes}>".
							 "<input class='input-switch' type='checkbox' name='{$name}' id='{$optionKey}_{$id}'{$parentAtts} ".
							 "value='{$choice['value']}'{$selected} />".
							 "{$choice['option']}".
							 "<div class='input-switch-slider'></div>".
							 "</label></span> ";
				}
				break;

			case 'button': // backwards compatible (use 'submit')
			case 'submit':
				// attributes should include "onclick='doSomething();return false;'"
				$value = esc_attr($savedOptionValue);
				$html .= $tab."<input class='{$inputClass}' type='submit' name='{$optionKey}' id='{$optionKey}'".
						 "value='{$value}'{$attributes} />";
				break;

			case 'reset':
			case 'image':
				$value = esc_attr($savedOptionValue);
				$html .= $tab."<input class='{$inputClass}' type='{$optionMeta['type']}' name='{$optionKey}' id='{$optionKey}'".
						 "value='{$value}'{$attributes} />";
				break;

			case 'textarea':
				// allow multiple values to be stored but show/enter 1st value
				$value = esc_textarea(wp_unslash( (is_array($savedOptionValue)) ? $savedOptionValue[0] : $savedOptionValue ));
				$html .= $tab."<textarea class='{$inputClass}' name='{$optionKey}' id='{$optionKey}' ".
						 "cols='{$maxWidth}' rows='{$maxHeight}'{$attributes}>{$value}</textarea>";
				break;

			case 'codeedit-js':
			case 'codeedit-css':
			case 'codeedit-html':
			case 'codeedit-php':
				$type 	= explode('-',$optionMeta['type']);
				$type 	= end($type);
				$value = wp_unslash( (is_array($savedOptionValue)) ? $savedOptionValue[0] : $savedOptionValue );
				$html .= $tab . $this->codeedit_get_codemirror($optionKey,$value,$type,$inputClass,$attributes);
				break;

			case 'html':
				$value = wp_unslash( (is_array($savedOptionValue)) ? $savedOptionValue[0] : $savedOptionValue );
				$html .= $tab . $this->codeedit_get_wpeditor($optionKey,$value,$inputClass);
				break;

			case 'display':
				$value = $savedOptionValue;
				$html .= $tab."<div class='{$inputClass}' id='{$optionKey}'{$attributes}>";
				switch (true) {
					case is_bool($value):
						$html .= ($value) ? 'True' : 'False';
						break;
					case is_null($value):
						$html .= 'Null';
						break;
					case is_scalar($value):
						$html .= $this->wp_kses( wp_unslash($value) );
						break;
					default:
						try {
							$html .= '<pre>' . @var_export($value,true) . '</pre>';
						} catch (\Throwable $e) {
							$html .= '<pre>' . @var_export($e,true) . '</pre>';
						}
						break;
				}
				$html .= "</div>";
				break;

			case 'custom':
				// custom fields must be handled by the '{classname}_options_form_input_{fieldname}' filter
				$html .= $tab."<code type='custom'>".__("requires",$this->PLUGIN_TEXTDOMAIN).": add_filter('{$this->className}_options_form_input_{$optionKey}','custom_input_function',10,4)</code>";
				break;

			case 'file':
				// file upload
				$value = esc_attr($optionMeta['default'] ?? 'Upload');
				$html .= $tab."<div class='{$inputClass}-wrap'>".
						 "<input type='submit' name='{$optionKey}' id='{$optionKey}' value='{$value}' class='button button-large' />".
						 "<label for='{$optionKey}_file' class='button button-small input-file-label'>Choose File</label>".
						 "<input class='{$inputClass}' type='{$optionMeta['type']}' name='{$optionKey}_file' id='{$optionKey}_file'{$attributes} />".
						 "</div>";
				break;

			case 'advanced-mode':
				$advanced = $this->toKeyString($optionMeta['advancedMode']);
				$html .= $tab."<div class='{$inputClass} {$advanced}' id='{$optionKey}'>";
				$html .= $optionMeta['default'];
				$html .= "</div>";
				break;

			case 'disabled':
				$attributes .= " disabled=\"disabled\"";
				// no break
			case 'readonly':
				$optionMeta['type'] = (isset($optionMeta['encrypt']) && $optionMeta['encrypt']) ? 'passwword' : 'text';
				$attributes .= " readonly=\"readonly\"";
					//ondblclick=\"this.readOnly=false;this.type='text';\" ontouchend=\"this.readOnly=false;this.type='text';\" onblur=\"this.readOnly=true\"";
				// no break
			default:
				// allow multiple values to be stored but show/enter 1st value
				$value = esc_attr(wp_unslash( (is_array($savedOptionValue)) ? $savedOptionValue[0] ?? '' : $savedOptionValue ));
				$html .= $tab."<input class='{$inputClass}' type='{$optionMeta['type']}' name='{$optionKey}' id='{$optionKey}' ".
						 "value='{$value}' size='{$maxWidth}'{$attributes} />";
				break;
		}

		if (!empty($optionMeta['after']))
		{
			$html .= $tab.__($this->wp_kses($optionMeta['after']),$this->PLUGIN_TEXTDOMAIN);
		}

		if (!empty($optionMeta['info']))
		{
			$html .= $tab."<cite>".__($this->wp_kses($optionMeta['info']),$this->PLUGIN_TEXTDOMAIN)."</cite>";
		}

		/**
		 * filter {classname}_options_form_input_{optionKey} customize field input html
		 * @param	string	$html current html for field
		 * @param	string	$optionKey option name
		 * @param	array	$optionMeta option meta data
		 * @param	mixed	$optionValue current option value
		 * @return	string	new html for field
		 */
		$html = $this->apply_filters( "options_form_input_{$optionKey}", $html, $optionKey, $optionMeta, $savedOptionValue );

		if (isset($optionMeta['script']) && !empty($optionMeta['script']))
		{
			$html .= $tab.wp_get_inline_script_tag(trim(wp_kses($optionMeta['script'],[])));
		}

		echo trim($html);
	}


	/**
	 * add contextual help from meta data
	 *
	 * @param	string|array 	$helpTabs [$groupname,$tabname] - uses first found in help tabs
	 * @param	string			$optionKey option name
	 * @param	array			$optionMeta option meta data
	 * @return	void
	 */
	public function options_settings_page_help($helpTabs,$optionKey,$optionMeta): void
	{
		if ( !$this->pluginHelpEnabled() ) return;

		$optionMeta = array_merge(self::OPTION_META_KEYS,$optionMeta);
		$optionMeta['value'] =  $optionMeta['value'] ?? $optionMeta['default'];

		if ( !in_array($optionMeta['type'],['display','hidden']) || !empty($optionMeta['help']) )
		{
			$help = ($optionMeta['type'] == 'help')
				? $optionMeta['help'] ?? '[default] [info]' 	// help field
				: $optionMeta['help'] ?? '[title] [info]'; 		// field help

			$meta 	= array_filter($optionMeta,function($v,$k){
				return in_array($k,['type','label','default','title','before','after','info','tooltip','help','value']) && is_scalar($v);
			},ARRAY_FILTER_USE_BOTH);
			$keys 	= array_map(function($k){return "[{$k}]";},array_keys($meta));
			$values = array_values($meta);

			if (is_array($help))	// ['help_tab' => 'help text']
			{
				foreach ($help as &$h) {
					$h = trim(str_replace($keys,$values,$h));
				}
			}
			else					// 'help text'
			{
				$help = trim(str_replace($keys,$values,$help));
			}

			/**
			 * filter {classname}_plugin_help_field
			 * @param	array	field parameters (tab, label, content)
			 * @param	string	$optionKey option name
			 * @param	array	$optionMeta option meta data
			 * @return	array
			 */
			$help = $this->apply_filters( "plugin_help_field",
				[
					'tab'		=> $helpTabs,
					'label'		=> __($optionMeta['label'],$this->PLUGIN_TEXTDOMAIN),
					'content'	=> $help,
				],
				$optionKey, $optionMeta
			);
			if (!empty($help) && !empty($help['content']))
			{
				$this->addPluginHelpField($help['tab'],$help['label'],$help['content']);
			}
		}
	}


	/**
	 * Creates Style tag for the Administration page to set options for this plugin.
	 *
	 * @return	string the stylesheet id
	 */
	public function options_settings_page_style(): string
	{
		$styleId = sanitize_title($this->className.'-palette');
		wp_enqueue_style( $styleId,
			plugins_url( 'eacDoojigger/assets/css/color-palette.css' ),
			[],
			EACDOOJIGGER_VERSION
		);

		$styleId = sanitize_title($this->className.'-settings');
		wp_enqueue_style( $styleId,
			plugins_url( 'eacDoojigger/assets/css/admin-options.css' ),
			[],
			EACDOOJIGGER_VERSION
		);
		wp_add_inline_style( $styleId, $this->options_settings_page_admin_style() );

		/**
		 * action {classname}_admin_enqueue_styles when stylesheet is loaded.
		 * Allow actors to add inline css using stylesheet id
		 * @param string $styleId stylesheet id
		 */
		$this->do_action( 'admin_enqueue_styles', $styleId );
		return $styleId;
	}


	/**
	 * Get admin color variables
	 *
	 * @see https://make.wordpress.org/core/2021/02/23/standardization-of-wp-admin-colors-in-wordpress-5-7/
	 * superseded by https://make.wordpress.org/design/handbook/design-guide/foundations/colors/
	 *
	 * @return	string the root style variables
	 */
	public function options_settings_page_admin_style(): string
	{
		$style = ":root {";
		foreach ($this->admin_color_scheme() as $id => $code) {
			$style .= "--eac-admin-{$id}:{$code};";
		}
		$style .= "}";
		return $style;
	}


	/**
	 * Creates Script tag for the Administration page to set options for this plugin.
	 *
	 * @return string the javascript id
	 */
	private function options_settings_page_script(): string
	{
		// Script for the page
		$this->options_settings_page_jquery();
		$scriptId = sanitize_title($this->className.'-settings');
		wp_enqueue_script( $scriptId,
			plugins_url( 'eacDoojigger/assets/js/admin-options.js' ),
			['jquery'],
			EACDOOJIGGER_VERSION,
			['strategy' => 'defer']
		);

		/**
		 * action {classname}_admin_enqueue_scripts when javascript is loaded.
		 * Allow actors to add inline script using script id
		 * @param string $scriptId script id
		 */
		$this->do_action( 'admin_enqueue_scripts', $scriptId );
		return $scriptId;
	}


	/**
	 * Creates Script tag for the jQuery/jQuery-ui.
	 *
	 * @return void
	 */
	public function options_settings_page_jquery(): void
	{
		// Script for the page
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tooltip');
		ob_start();
		?>
		jQuery(function($) {
			// for our options-settings page fields
			$( '.settings-tooltip.dashicons' ).tooltip({
				content: function() {
					var e = $( this );
					var v = e.data( 'tooltip' ) || e.html();
					var t = e.attr( 'title' );
					return (v)
						? ( (t) ? '<div><strong>'+t+'</strong></div> '+v : v ) : t;
				}
			});
			// make tooltip icon visible
			document.head.appendChild( document.createElement("style") )
					.innerHTML = ".settings-tooltip.dashicons::before { visibility: visible; }";
			// for other pages/sections/fields
			$( 'abbr,.tooltip.dashicons,[data-tooltip]:not(.settings-tooltip)' ).tooltip({
				content: function() {
					var e = $( this );
					return e.data( 'tooltip' ) || e.attr( 'title' );
				}
			});
		});
		<?php
		$script = ob_get_clean();
		wp_add_inline_script( 'jquery-ui-tooltip',$this->plugin->minifyString($script) );
	}


	/**
	 * parse option choices to array of (escaped) [ ['option'=>option, 'value'=>value], ... ]
	 *
	 * @param	array|string 	$choices	options (parsed by parseAttributes())
	 * @return	array of arrays [ ['option'=>option, 'value'=>value] ]
	 */
	protected function getOptionChoiceArray($choices): array
	{
		return array_map(function($choice)
			{
				return [
				//	'option'	=> esc_attr__(key($choice), $this->PLUGIN_TEXTDOMAIN),
					'option'	=> $this->wp_kses(key($choice)),
					'value'		=> esc_attr(current($choice))
				];
			},
			$this->parseAttributes($choices,true)	// array of arrays
		);
	}
}

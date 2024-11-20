<?php
namespace EarthAsylumConsulting\Plugin;

/**
 * Primary plugin file - {eac}Doojigger for WordPress
 *
 * load administrator traits
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 * @version		24.1114.1
 */

trait eacDoojigger_admin_traits
{
	/**
	 * @trait standard options
	 */
	use \EarthAsylumConsulting\Traits\standard_options;

	/**
	 * @trait methods for contextual help tabs
	 */
	use \EarthAsylumConsulting\Traits\plugin_help;


	/**
	 * constructor method (for admin/backend)
	 *
	 * @access public
	 * @param array header passed from loader script
	 * @return void
	 */
	public function admin_construct(array $header)
	{
		// to put settings first on general tab
		if ($this->is_network_admin()) {
			$this->registerNetworkOptions('network_settings');
		} else {
			$this->registerPluginOptions('plugin_settings');
		}

		// Register plugin options
		$this->add_action( 'options_settings_page', array( $this, 'admin_options_settings' ) );
		// Add contextual help
		$this->add_action( 'options_settings_help', array( $this, 'admin_options_help' ) );

		// check existance of required autoloader
		\add_action('all_admin_notices', 			array( $this, 'verify_autoloader' ), 5 );

		// When this plugin is installed
		$this->add_action( 'version_installed',		array( $this, 'admin_plugin_installed' ), 10, 3 );
		// When this plugin is updated
		$this->add_action( 'version_updated',		array( $this, 'admin_plugin_updated' ), 10, 3 );

		// When this plugin is registered (create,activate,verify) - verify_autoloader should catch this
		//$this->add_action( 'update_registration',	array( $this, 'install_autoloader') );
		// When the registration is purged
		$this->add_action( 'purge_registration',	array( $this, 'uninstall_autoloader') );
		// When the plugin is deactivated
		register_deactivation_hook($header['PluginFile'],	array( $this, 'uninstall_autoloader') );


		// on plugins page, add documentation link
		add_filter( (is_network_admin() ? 'network_admin_' : '').'plugin_action_links_' . $this->PLUGIN_SLUG,
			function($pluginLinks, $pluginFile, $pluginData) {
				return array_merge(
					['documentation'=>$this->getDocumentationLink($pluginData)],
					$pluginLinks
				);
			},20,3
		);

		// fix plugins list title column to wrap
		add_action('admin_print_styles',	function()
		{
			global $pagenow;
			if ($pagenow == 'plugins.php') {
				echo "<style>.row-actions {white-space: normal;}</style>\n";
			}
		}, 100 );
	}


	/**
	 * register options on options_settings_page
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options_settings()
	{
		// format the h1 title, add documentation link buttons

		$this->add_filter("options_form_h1_html", function($h1)
			{
				return	"<div id='settings_banner'>".
						$this->formatPluginHelp($h1).
						"<div id='settings_info'>".

						$this->getDocumentationLink(true,'/eacdoojigger',
						"<span class='tooltip dashicons dashicons-editor-help button eac-logo-orange' title='{eac}Doojigger Documentation'></span>").
						"&nbsp;&nbsp;&nbsp;".

						$this->getDocumentationLink(true,'/phpdoc',
						"<span class='tooltip dashicons dashicons-editor-code button eac-logo-orange' title='{eac}Doojigger PHP Reference'></span>",'PHP Reference').
						"&nbsp;&nbsp;&nbsp;".

						"<a href='".network_admin_url('/plugin-install.php?s=earthasylum&tab=search&type=term')."'>".
						"<span class='tooltip dashicons dashicons-admin-plugins button eac-logo-orange' title='Plugins from EarthAsylum Consulting'></span></a>".
						"&nbsp;&nbsp;&nbsp;".

						"<a href='https://earthasylum.com'>".
						"<span class='tooltip dashicons dashicons-admin-site-alt3 button eac-logo-orange' title='About EarthAsylum Consulting'></span></a>".

						"</div></div>";
			}
		);

		// from standard_options
		$options = $this->standard_options(['adminSettingsMenu','uninstallOptions'/*,'emailFatalNotice'*/]);
		$options['adminSettingsMenu']['options'][] = 'Menu Bar';
		$options['adminSettingsMenu']['default'][] = 'Menu Bar';
		$options['optimize_options'] = array(
				'type'		=>	'checkbox',
				'label'		=>	'Optimizations',
				'options'	=>	[
					"<abbr title='Add early hints link header for style sheets'>CSS Early Hints</abbr>"	=>	'style',
					"<abbr title='Preload stylesheets asynchronously'>Asynchronous CSS</abbr>"	=>	'style-async',
					"<abbr title='Add early hints link header for JavaScript'>JS Early Hints</abbr>"	=>	'script',
					"<abbr title='Add async attribute to non-deferred JavaScript tags'>Asynchronous JS</abbr>"	=>	'script-async',
				],
				'default'	=>	['style','script'],
				'info'		=> 	'Add browser optimizations: early-hints and asynchronous loading.',
				'help'		=>	"[info] Use ".
								"`eacDoojigger_style_preload_exclude`, ".
								"`eacDoojigger_style_async_exclude` ".
								"`eacDoojigger_script_preload_exclude`, or ".
								"`eacDoojigger_script_async_exclude` ".
								"filters to exclude specific handles from these optimizations.",
		);

		// WP Environment setting
		if ( $this->isAdvancedMode('settings') && function_exists('\wp_get_environment_type') )
		{
			// use mu-plugin on multisite so each site can set environment
			if	( 	( is_multisite() )
				&& 	( $this->is_network_admin() )
				&&	( file_exists($this->pluginHeader('VendorDir').'/Utilities/eacDoojiggerEnvironment.class.php') )
			) {
				$default = $_POST['_btnEnvironment']
							?? ( (file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerEnvironment.php')) ? 'Install' : 'Uninstall' );
				$default = ($default=='Install') ? 'Uninstall' : 'Install';
				$tools = ['_btnEnvironment' =>
					[
							'type'		=>	'button',
							'label'		=>	'Environment Switcher',
							'default'	=>	$default,
							'info'		=>	$default." the Environment Switcher in the 'mu_plugins' folder.",
							'validate'	=>	[$this, 'install_environment'],
							'advanced'	=> 	true,
					]
				];
			}
			// on single site, set via admin option
			else if (!is_multisite() && ($this->wpConfig = $this->wpconfig_handle()) )
			{
				// remove old 'environment switcher'
				if ( file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerEnvironment.php') ) {
					$this->install_environment('uninstall');
				}

				$tools = ['_environment' =>
					[
							'type'		=> 	'select',
							'label'		=> 	'WordPress Environment',
							'options'	=>  [
												'Production'	=>'production',
												'Staging'		=>'staging',
												'Development'	=>'development',
												'Local'			=>'local',
											],
							'default'	=>	\wp_get_environment_type(),
							'info'		=> 	'Sets the WordPress environment type.',
							'validate'	=>	[$this,'validate_environment_option'],
							'attributes'=>	['onchange'=>'this.form.requestSubmit()'],
							'advanced'	=> 	true,
					]
				];
			}
		}

		if ($this->is_network_admin()) {
			$this->registerNetworkOptions('network_settings',$options);
			if (isset($tools)) $this->registerNetworkOptions(['network_environment','tools'],$tools);
		} else {
			$this->registerPluginOptions('plugin_settings',$options);
			if (isset($tools)) $this->registerPluginOptions(['site_environment','tools'],$tools);
		}
	}


	/**
	 * set WP_ENVIRONMENT_TYPE
	 *
	 * @access public
	 * @return void
	 */
	public function validate_environment_option($value, $fieldName, $metaData, $priorValue)
	{
		$current = \wp_get_environment_type();
		if ($value == $current) return $value;
		$this->wpConfig->update( 'constant', 'WP_ENVIRONMENT_TYPE', $value );
	}


	/**
	 * Add help tab on admin page
	 *
	 * @return	void
	 */
	public function admin_options_help()
	{
		include 'eacDoojigger.help.php';
	}


	/**
	 * Additional formatting calback for help content
	 *
	 * @param string $content tab content
	 * @return string
	 */
	public function formatPluginHelp(string $content): string
	{
		return preg_replace(
			"/{eac}(\w+)/",
			"<span class='eac-logo-orange'>{<span class='eac-logo-green'>eac</span>}$1</span>",
			$content
		);
	}


	/**
	 * install/uninstall {eac}DoojiggerEnvironment
	 *
	 * @param string $action install/update/uninstall
	 * @return	void
	 */
	public function install_environment($action)
	{
		if ($action == 'uninstall') {
			\delete_option('eacDoojigger_wp_environment');
		}
		$this->installer->invoke($action,false,
			[
				'title'			=> 'The {eac}Doojigger Environment Switcher',
				'sourcePath'	=> $this->pluginHeader('VendorDir').'/Utilities',
				'sourceFile'	=> 'eacDoojiggerEnvironment.class.php',
				'targetFile'	=> 'eacDoojiggerEnvironment.php',
			]
		);
		return $action;
	}


	/**
	 * verify that autoloader is installed (all_admin_notices)
	 *
	 * @return	void
	 */
	public function verify_autoloader(): void
	{
		if ($this->isBasicLicense() && !file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php'))
		{
			$this->installer->enqueue('install',[$this,'install_autoloader']);
		}
	}


	/*
	 *
	 * When installing, updating or registering this plugin,
	 * install or uninstall the autoloader used by this and any plugins based on this
	 *
	 */


	/**
	 * Install autoloader in mu_plugins
	 * (may be triggered by registration refresh on front-end activity)
	 *
	 * @param string $action install/update
	 * @return	void
	 */
	public function install_autoloader($action='install'): void
	{
		// load (update) auto-loader if already installed or license is basic or better
		if ( $this->isBasicLicense() || file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php') )
		{
			$action = (file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php')) ? 'update' : 'install';
			$this->installer->invoke($action, [$this,__FUNCTION__],
				[
					'title'			=> 'The {eac}Doojigger Autoloader',
					'sourcePath'	=> $this->pluginHeader('VendorDir').'/Utilities',
					'sourceFile'	=> 'eacDoojiggerAutoloader.php',
					'targetFile'	=> 'eacDoojiggerAutoloader.php',
					'return_url'	=> remove_query_arg('fs'),	// force reload after install
				],
				function($action,$installOptions): bool		// callback onSuccess
				{
					$eacHomeDir  	= str_replace(WP_PLUGIN_DIR,'',$this->pluginHeader('PluginDir'));
					$lines	= [
						"define('EACDOOJIGGER_HOME',WP_PLUGIN_DIR.'{$eacHomeDir}');",
						"if (is_file(EACDOOJIGGER_HOME .'/autoload.php')) {",
						"    define('EACDOOJIGGER_VERSION','".$this->getVersion()."'); // preferred",
						"    define('EAC_DOOJIGGER_VERSION',EACDOOJIGGER_VERSION); // deprecated",
						"    define('EACDOOJIGGER_LICENSE','".$this->Registration->isRegistryValue('license')."');",
						"    include EACDOOJIGGER_HOME .'/autoload.php';",
						"}",
					/*
						"",
						"   // ftp credentials used by file system utility",
						"   require_once EACDOOJIGGER_HOME.'/Utilities/eacDoojigger_ftp_credentials.class.php';",
						"   eacDoojigger_ftp_credentials::addFilters();",
						"",
						"   // plugin automatic updater utility",
						"   require_once EACDOOJIGGER_HOME.'/Utilities/eacDoojiggerPluginUpdater.class.php';",
 						"   eacDoojiggerPluginUpdater::setPluginUpdates();",
						"",
						"   // autoloader for this (and additional) namespaces",
						"   require_once EACDOOJIGGER_HOME.'/Utilities/eacDoojiggerAutoloader.class.php';",
						"   eacDoojiggerAutoloader::setAutoLoader();",
						"   // autoload PSR specific to PHP version (PHPv7=PSR-3v1, PHPv8=PSR-3v3)",
						"   eacDoojiggerAutoloader::addNamespace('Psr',EACDOOJIGGER_HOME.'/Helpers/vendor/Psr/php'.PHP_MAJOR_VERSION);",
					//	"   eacDoojiggerAutoloader::setEmailNotification( '".$this->className."' );",
					*/
					];
					$marker = $this->pluginHeader('NameSpace').' eacDoojiggerAutoloader '.wp_date('Y-m-d H:i:s');
					return (bool) $this->insert_with_markers(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php', $marker, $lines, '/*','*/');
				}
			);
		}
	}


	/**
	 * Deactivate the plugin (via purge_registration action), uninstall autoloader
	 *
	 * @return	void
	 */
	public function uninstall_autoloader($action='uninstall'): void
	{
		if (! $this->installer->uninstall([$this,__FUNCTION__],
			[
				'title'			=> 'The {eac}Doojigger Autoloader',
				'sourcePath'	=> $this->pluginHeader('VendorDir').'/Utilities',
				'sourceFile'	=> 'eacDoojiggerAutoloader.php',
				'targetFile'	=> 'eacDoojiggerAutoloader.php',
			]
		)) {
			if ( file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php') )
			{
				unlink(WPMU_PLUGIN_DIR.'/eacDoojiggerAutoloader.php');
			}
		}
	}


	/*
	 *
	 * When this plugin is installed or updated
	 *
	 */


	/**
	 * version installed (action {classname}_version_installed)
	 *
	 * May be called more than once on a given site (once as network admin).
	 *
	 * @param	string|null $curVersion currently installed version number (null)
	 * @param	string		$newVersion version being installed/updated
	 * @param	bool		$asNetworkAdmin running as network admin
	 * @return	void
	 */
	public function admin_plugin_installed($curVersion, $newVersion, $asNetworkAdmin)
	{
		$this->install_autoloader();
	}


	/**
	 * version updated (action {classname}_version_updated)
	 *
	 * May be called more than once on a given site (once as network admin).
	 *
	 * @param	string|null $curVersion currently installed version number
	 * @param	string		$newVersion version being installed/updated
	 * @param	bool		$asNetworkAdmin running as network admin
	 * @return	void
	 */
	public function admin_plugin_updated($curVersion, $newVersion, $asNetworkAdmin)
	{
		// enqueue auto-loader update
		if ($this->installer)
		{
			$this->installer->enqueue('update',[$this,'install_autoloader']);

			// enqueue environment switcher update
			if (( file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerEnvironment.php') )
			&&	( function_exists('\wp_get_environment_type'))
			) {
				$this->installer->enqueue('update',[$this,'install_environment']);
			}

			// run the installer
			$this->installer->invoke();
		}
	}

}

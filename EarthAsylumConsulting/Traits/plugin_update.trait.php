<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Plugin updater trait - {eac}Doojigger for WordPress
 *
 * Handles plugin updates from the plugins page in WordPress for self-hosted plugins
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		abstract_backend.class.php
 */

/*
 * Usage:
 *
 * Use this trait in your class file...
 *
 *		use \EarthAsylumConsulting\Traits\plugin_update;
 *
 * Add the updater hooks in your __constructor method...
 *
 *		$this->addPluginUpdateHooks(
 *			[
 *				'plugin_slug'		=> (required) 'directory/pluginfile.php' - plugin_basename( __FILE__ )
 *				'plugin_uri'		=> (required) uri to JSON updater file - from plugin file header : 'Update URI' or 'Plugin URI'
 *
 *   			'plugin_options'	=> (optional) name=>value array added as query options to plugin_uri.
 * 										defaults to ['environment' => wp_get_environment_type()]
 *
 *				'transient_name'	=> (optional) the full name of the transient used to cache the external update info (false=no caching)
 *										defaults to 'plugin_update_<directory>' (directory from plugin_slug should be the plugin name)
 *				'transient_time'	=> (optional) time (in seconds) to keep the transient (transient expiration), defaults to 1 hour
 *
 *				'disableAutoUpdates'=> (optional) true or string (message to display), disables automatic updates on plugins page
 *										defaults to false, or when true, 'Auto-updates disabled'
 *
 *				'requestTimeout'	=> (optional) timeout (in seconds) for remote request (default=6),
 *				'requestHeaders'	=> (optional) array of additional headers to send on remote request [ 'header_name'=>'header_value' ],
 *				'requestSslVerify'	=> (optional) boolean to verify ssl certificate. Default = true.
 *			],
 * 			__CLASS__
 *		);
 */

/*
 * The expected remote response template...
 *
 *	$plugin_install_info = array
 *	(
 *		"slug" 				=> "pluginName",							// main plugin directory
 *		"plugin" 			=> "pluginName/pluginName.php",				// main plugin file once installed
 *		"name" 				=> "Plugin Name",
 *		"description" 		=> "Plugin Description",
 *		"version" 			=> "0.0.0",
 *		"last_updated" 		=> "Y-m-d H:m:s",							// Last update date/time
 *		"homepage" 			=> "url to homepage",						// source page
 *		"download_link" 	=> "{plugin_install_url}/pluginName.zip",	// source zip
 *		"requires" 			=> "5.3.0",									// WordPress version minimum
 *		"tested" 			=> "5.8",									// WordPress version tested
 *		"requires_php" 		=> "7.2",									// PHP version minimum
 *		"author"			=> "<a href='author_url'>Author Name</a>",
 *		'contributors' 		=> array
 *		(
 *			'username' => [
 *				'display_name'	=> '',
 *				'profile'		=> 'url',
 *				'avatar'		=> 'https://secure.gravatar.com/avatar/...'
 *			]
 *		),
 *		"sections" 			=> array
 *		(
 *			"description" 	=> "html for description tab",
 *			"installation" 	=> "html for installation tab",
 *			"changelog" 	=> "html for changelog tab",
 *			"screenshots" 	=> "html for screenshots tab",
 *		),
 *		"banners" 			=> array
 *		(
 *			"low" 		=> "url to low res (772x250) image",
 *			"high" 		=> "url to high res (1544x500) image"
 *		),
 *		"icons" 			=> array
 *		(
 *			"low" 		=> "url to low res (128x128) image",
 *			"high" 		=> "url to high res (256x256) image"
 *		),
 *	);
 *	return wp_json_encode($plugin_install_info);
 */

trait plugin_update
{
	/**
	 * enables upgrade_notice on plugins page
	 */
	use \EarthAsylumConsulting\Traits\plugin_update_notice;

	/**
	 * @var array parameters passed to addPluginUpdateHooks()
	 */
	protected $update_plugin_info;


	/**
	 * plugin update hooks - add hooks using plugin file header values
	 *
	 * @param	string	$plugin_file path to plugin file (for get_file_data())
	 * @param	string $className the class name of the loading plugin class.
	 * @return	void
	 */
	protected function addPluginUpdateFile(string $plugin_file, string $className=''): void
	{
		if (!file_exists($plugin_file))
		{
			$plugin_file = WP_PLUGIN_DIR.'/'.ltrim($plugin_file,'/');
		}
		$pluginUri = get_file_data( $plugin_file, ['UpdateURI'=>'Update URI'], 'plugin' );
		$this->addPluginUpdateHooks(
			[
				'plugin_slug' 			=> plugin_basename($plugin_file),
				'plugin_uri'			=> $pluginUri['UpdateURI']
			],
			$className
		);
	}


	/**
	 * plugin update hooks - add hooks using plugin_info array
	 *
	 * @param	array	$plugin_info, ['plugin_slug', 'plugin_uri', 'transient_name', 'disableAutoUpdates']
	 * @param	string $className the class name of the loading plugin class.
	 * @return	void
	 */
	protected function addPluginUpdateHooks(array $plugin_info, string $className=''): void
	{
		$className = basename(str_replace('\\', '/', $className));

		$plugin_update_options 	= [
			'environment' => wp_get_environment_type()
		];

		// check for constant ('{classname}_PLUGIN_UPDATE_CHANNEL')
		// 		 	or option ('{classname}_selected_update_channel')
		// define( '{classname}_PLUGIN_UPDATE_CHANNEL', 'branch/default' );
		// define( '{classname}_PLUGIN_UPDATE_CHANNEL', 'release/latest' );
		$plugin_update_source 	= strtoupper($className).'_PLUGIN_UPDATE_CHANNEL';
		if (defined($plugin_update_source))
		{
			$plugin_update_source = constant($plugin_update_source);
		}
		else	// update_option( '{classname}_selected_update_channel', ... );
		{
			$plugin_update_source = \get_site_option($className.'_selected_update_channel');
		}
		if (!empty($plugin_update_source) && is_string($plugin_update_source))
		{
			$plugin_update_source = explode('/',$plugin_update_source);
			$plugin_update_options['update_source'] = $plugin_update_source[0];
			if (isset($plugin_update_source[1])) {
				$plugin_update_options['update_id'] = $plugin_update_source[1];
			}
			if ($plugin_update_source != 'release') {
				$plugin_update_options['cache'] = 'no';
			}
		}

		/**
		 * filter {className}_plugin_update_parameters - filter plugin update parameters
		 * @param 	array parameters
		 * @return 	array parameters
		 */
		$this->update_plugin_info = \apply_filters( $className.'_plugin_update_parameters',
			array_replace_recursive([
					'plugin_slug' 			=> '',						// required
					'plugin_uri'			=> '',						// required if disableAutoUpdates=false
					'plugin_options'		=> $plugin_update_options,	// parameters added to uri
					'transient_name'		=> true,					// use transient
					'transient_time'		=> HOUR_IN_SECONDS,			// transient time
					'disableAutoUpdates' 	=> false,					// disable auto updating
					'requestTimeout'		=> 6,						// timeout
					'requestHeaders'		=> [],						// optional headers in request
					'requestSslVerify'		=> true,					// verify valid ssl cert
			],$plugin_info)
		);

		// we must know the slug name to do anything
		if (empty($this->update_plugin_info['plugin_slug']))
		{
			return;
		}
		$this->update_plugin_info['plugin_name'] = dirname($this->update_plugin_info['plugin_slug']);

		// should we disable automatic updating?
		if ($this->update_plugin_info['disableAutoUpdates'])
		{
			if (!is_string($this->update_plugin_info['disableAutoUpdates']))
			{
				$this->update_plugin_info['disableAutoUpdates'] = 'Auto-updates disabled';
			}
			add_filter( 'plugin_auto_update_setting_html',array( $this, 'plugin_update_disable_auto_update'), 10, 3 );
		}

		// display notice on plugins page when upgrade available
		$this->addPluginUpdateNotice($this->update_plugin_info['plugin_slug']);

		// for updating, we need the uri to the remote 'plugin_information' data
		if (empty($this->update_plugin_info['plugin_uri']))
		{
			return;
		}

		// we should use transient caching
		if ($this->update_plugin_info['transient_name'])
		{
			if (!is_string($this->update_plugin_info['transient_name']))
			{
				$this->update_plugin_info['transient_name'] = sanitize_key( 'update_plugin_'.$this->update_plugin_info['plugin_name'] );
			}
		}

		/*
		 * add admin hooks for automatic update
		 */
		// when 'update_plugins' transient is deleted, delete our transient
		add_action( 'delete_site_transient_update_plugins', array( $this, 'deleteUpdaterTransient') );

		// update the plugin version
		if ( version_compare( get_bloginfo('version' ), '5.8.0', '>=' ) )
		{
			$hostname = wp_parse_url( sanitize_url( $this->update_plugin_info['plugin_uri'] ), PHP_URL_HOST );
			add_filter( "update_plugins_{$hostname}", 	array( $this, 'plugin_update_update_plugins_hostname'), 10, 4 );
		}
		else
		{
			add_filter( 'site_transient_update_plugins',array( $this, 'plugin_update_update_plugins_transient') );
		}

		// requests plugin info from remote source
		add_filter( 'plugins_api',						array( $this, 'plugin_update_get_plugin_info'), 10, 3 );

		// when update completes
		add_action( 'upgrader_process_complete',		array( $this, 'plugin_update_after_plugin_update'), 10, 2 );

		// allow external host
		add_filter( 'http_request_host_is_external',	array( $this, 'plugin_update_allow_external_host'), 10, 3 );
	}


	/*
	 *
	 * Plugin Update Management methods
	 *
	 */


	/**
	 * delete updater transient
	 *
	 * @return	bool
	 */
	public function deleteUpdaterTransient(): bool
	{
		if ($this->update_plugin_info['transient_name'])
		{
			return \delete_site_transient( $this->update_plugin_info['transient_name'] );
		}
		return false;
	}


	/**
	 * disable auto-update for this plugin - plugin_auto_update_setting_html
	 *
	 * @param	string 	default html for auto-update option
	 * @param	string 	The path to the main plugin file relative to the plugins directory
	 * @param	array 	An array of plugin data
	 * @return	string 	updated html
	 */
	public function plugin_update_disable_auto_update( string $html, string $plugin_file, array $plugin_data ): string
	{
		if ($this->update_plugin_info['plugin_slug'] == $plugin_file)
		{
			$html = __( $this->update_plugin_info['disableAutoUpdates'] );
		}
		return $html;
	}


	/**
	 * get plugin information on 'plugins_api' filter
	 *
	 * @param	object|array|false 	result
	 * @param	string 	$action 'plugin_information'
	 * @param	object 	$args( 'slug' => 'plugin-slug', 'locale' => 'en_US', 'wp_version' => '5.5' )
	 * @return	object 	plugin information used by WordPress
	 */
	public function plugin_update_get_plugin_info( $result, string $action, object $args )
	{
		if ($action !== 'plugin_information') return $result;

		if ($this->update_plugin_info['plugin_name'] !== $args->slug) return $result;

		$result = $this->get_plugin_info_cache('info');

		// specified fields
		if (!empty($result) && isset($args->fields) && !empty($args->fields))
		{
			$filtered = new \stdclass();
			// include fieldName = true (ignore false)
			foreach ($args->fields as $fieldName => $fieldBool)
			{
				if ($fieldBool) {
					if ($fieldName == 'downloadlink') $fieldName = 'download_link';
					$filtered->{$fieldName} = $result[$fieldName];
				}
			}
			if (!empty($filtered)) return $filtered;

			// exclude fieldName = false
			foreach ($args->fields as $fieldName => $fieldBool)
			{
				if (!$fieldBool) {
					if ($fieldName == 'downloadlink') $fieldName = 'download_link';
					unset($result[$fieldName]);
				}
			}
		}
		return $result;
	}


	/**
	 * check for plugin update on 'update_plugins_{$hostname}' filter (WP 5.8.0+)
	 *
	 * @param array|false $update 			false or update data with latest details
	 * @param array       $plugin_data      Plugin headers.
	 * @param string      $plugin_file      Plugin filename.
	 * @param array       $locales          Installed locales to look translations for.
	 * @return	object plugin information used by WordPress
	 */
	public function plugin_update_update_plugins_hostname( $update, $plugin_data, $plugin_file, $locales )
	{
		if ($this->update_plugin_info['plugin_slug'] !== $plugin_file) return $update;

		return $this->get_plugin_info_cache('update');
	}


	/**
	 * check for plugin update on 'site_transient_update_plugins' filter.
	 * the filter is called often
	 *
	 * @param	object $transient plugin_information transient
	 * 		$transient->last_checked 	= time();
	 * 		$transient->checked 		= array();
	 * 		$transient->response     	= array();
	 * 		$transient->translations 	= array();
	 * 		$transient->no_update    	= array();
	 * @return	object plugin information used by WordPress
	 */
	public function plugin_update_update_plugins_transient( $transient )
	{
		if (empty($transient->checked)) return $transient;

		if ($result = $this->get_plugin_info_cache('update'))
		{
			$version 	= $transient->checked[ $this->update_plugin_info['plugin_slug'] ] ?? null;
			$update 	= ( $version && version_compare($version, $result->version, '<') );
			$transient->checked[$result->plugin] 		= $version;
			if ( $update ) {
				$transient->response[$result->plugin]	= $result;
			} else {
				$transient->no_update[$result->plugin]	= $result;
			}
		}
		return $transient;
	}


	/**
	 * get remote plugin information and cache
	 * see: https://developer.wordpress.org/reference/functions/plugins_api/
	 * see: https://developer.wordpress.org/reference/functions/install_plugin_information/
	 *
	 * @param 	string	$context 'info' for plugin_info or 'update' for plugin_update
	 * @return	object 	info object or update object
	 */
	private function get_plugin_info_cache($context='info')
	{
		// if the transient exists, use it
		if ($this->update_plugin_info['transient_name'])
		{
			if ($result = \get_site_transient($this->update_plugin_info['transient_name']))
			{
				return $result[$context];
			}
		}

		// else get the remote upgrade object
		$result = wp_remote_get(
			add_query_arg($this->update_plugin_info['plugin_options'],$this->update_plugin_info['plugin_uri']),
			[
				'timeout' 	=> intval($this->update_plugin_info['requestTimeout']),
				'sslverify'	=> $this->update_plugin_info['requestSslVerify'],
				'headers' 	=> array_merge(
								['Accept' => 'application/json'],
								$this->update_plugin_info['requestHeaders']
							)
			]
		);

		$result = (wp_remote_retrieve_response_code($result) == '200')
			? json_decode( wp_remote_retrieve_body($result), true ) : null;
		if (empty($result)) return null;

		if (isset($result['eac_github_hosting']))
		{
			// updating from expected/trusted source (github hosting extension of {eac}SoftwareRegistry)
			unset($result['eac_github_hosting']); 	// extraneous data
			$result = [ 'info' => (object) $result, 'update' => (object) $result[ $result['slug'] ] ];
		}
		else
		{
			// updating from unknown source, inspect and format array(s)
			$result = $this->get_plugin_info_unknown((object) $result);
		}

		// check version tested to proper length - tested = 6.0 == 6.0.1|6.0.2
		$blogVersion = explode('-',get_bloginfo('version')); // strip '-RCx'
		$blogVersion = $blogVersion[0];
		if ($result['info']->tested) {
			if (version_compare( $result['info']->tested, substr($blogVersion,0,strlen($result['info']->tested)) ) == 0) {
				$result['info']->tested = substr($blogVersion,0,5);
			}
		}
		if ($result['update']->tested) {
			if (version_compare( $result['update']->tested, substr($blogVersion,0,strlen($result['update']->tested)) ) == 0) {
				$result['update']->tested = substr($blogVersion,0,5);
			}
		}

		// make sure we have the correct slug & plugin (case-sensitive)
		$result['info']->slug 	= $result['update']->slug 	= $this->update_plugin_info['plugin_name'];
		$result['info']->plugin = $result['update']->plugin = $this->update_plugin_info['plugin_slug'];

		// save to transient
		if ($this->update_plugin_info['transient_name'] && $this->update_plugin_info['transient_time'])
		{
			\set_site_transient($this->update_plugin_info['transient_name'], $result, $this->update_plugin_info['transient_time']);
		}

		return $result[$context];
	}


	/**
	 * process remote result from old or unknown source
	 *
	 * @param 	object 	$result from remote request
	 * @return	array 	info and update objects
	 */
	private function get_plugin_info_unknown($result)
	{
		$update = new \stdclass();
		$update_properties 	= [
			'slug','plugin','version','new_version','url','package','requires','tested','requires_php','icons','translations'];
		foreach (
			[//	property				default value
				'slug' 				=> $this->update_plugin_info['plugin_name'],
				'plugin'			=> $this->update_plugin_info['plugin_slug'],
				'name'				=> 'slug',
				'description'		=> 'name',
				'version'			=> 'new_version',
				'new_version'		=> 'version',
				'download_link' 	=> 'package',
				'package'			=> 'download_link',
				'requires'			=> 'tested',
				'tested'			=> 'requires',
				'homepage' 			=> 'url',
				'url'				=> 'homepage',
				'author_uri'		=> 'author_profile',
				'author_profile'	=> 'author_uri',
			//	'sections'			=> [/* 'description'=>'','installation'=>'','FAQ'=>'','changelog'=>'', 'reviews'=>'', 'other_notes'=>'' */],
			//	'banners'			=> [/* 'low' => '.../772x250.jpg', 'high' => '.../1544x500.jpg' */],
			//	'banners_rtl'		=> [/* 'low' => '.../772x250.jpg', 'high' => '.../1544x500.jpg' */],
			//	'icons'				=> [/* 'low' => '.../icon-128x128.jpg', 'high' => '.../icon-256x256.jpg' */],
			//	'translations'		=> [/* [language'=>, 'package'=>] */],
			] as $property => $value)
		{
			if (!isset($result->{$property}))
			{
				if (!empty($value) && isset($result->{$value})) {
					$result->{$property} = $result->{$value};
				}
			}
			$value = $result->{$property};
			switch ($property)
			{
				case 'sections':
					if (array_key_exists('upgrade_notice',$value) && !empty($value['upgrade_notice'])) {
						$update->upgrade_notice	= trim($value['upgrade_notice']);
					}
					break;
				case 'banners':
				case 'banners_rtl':
				case 'icons':
					if (!empty($value)) {
						$value['1x'] = $value['low'] ?? null;
						$value['2x'] = $value['high'] ?? null;
					}
					break;
			}
			$result->{$property} = $value;
			if (in_array($property,$update_properties) && !empty($value)) {
				$update->{$property} = $value;
			}
		}

		return [ 'info' => $result, 'update' => $update ];
	}


	/**
	 * cleanup after plugin update on 'upgrader_process_complete' filter
	 * occurs before new code is loaded
	 *
	 * @param	object	$upgrader_object
	 * @param	array	$hook_extra
	 * @return	void
	 */
	public function plugin_update_after_plugin_update( object $WP_Upgrader, array $hook_extra ): void
	{
		if ( $hook_extra['action'] == 'update' && $hook_extra['type'] === 'plugin' )
		{
			if ( in_array($this->update_plugin_info['plugin_slug'], $hook_extra['plugins']) )
			{
				$this->deleteUpdaterTransient();
			}
		}
	}


	/**
	 * allow access to external host on 'http_request_host_is_external' filter
	 *
	 * @param	bool 	$allow allow external
	 * @param	string 	$host remote host name
	 * @param	string 	$url remote url
	 * @return	bool
	 */
	public function plugin_update_allow_external_host( bool $allow, string $host, string $url ): bool
	{
		if (strpos($url,$this->update_plugin_info['plugin_uri']) !== false)
		{
			$allow = true;
		}
		return $allow;
	}
}

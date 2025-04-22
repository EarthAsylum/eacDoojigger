<?php
namespace EarthAsylumConsulting\Traits;

/**
 * software registration UI for use with {eac}SoftwareRegistry - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	25.0416.1
 */

trait swRegistrationUI
{
	/**
	 * swRegistrationUI method
	 *
	 * should be called from __construct() in 'options_settings_page' action
	 * 	add_action( 'options_settings_page', [$this,'swRegistrationUI'], PHP_INT_MAX );
	 *
	 * @return 	void
	 */
	public function swRegistrationUI()
	{
		$this->plugin->isReservedOption(self::SOFTWARE_REGISTRY_OPTION,true);

		$title = $this->plugin->pluginHeader('Title');
		$this->registerExtension( [$title,'registration'] );

		$registrationKey = $this->getRegistrationKey();
		$pluginOptions = [ // hides the submit button
							'_btnSubmitOptions'		=> array(
									'type'		=> 	'hidden',
									'label'		=> 	'submit',
									'default'	=> 	'',
								),
		];

		if (empty($registrationKey))
		{
			$current_user = wp_get_current_user();

			// either enter and activate an existing registration key...
			$pluginOptions['_registry_activate'] 	= array(
									'type'		=> 	'display',
									'label'		=> 	'Request Registration',
									'default'	=> 	'You are currently using an unregistered version of '.$this->pluginName.'.<br/>'.
													'To activate your registration key, enter it below...',
								);
			$pluginOptions['registry_key']			= array(
									'type'		=> 	'text',
									'label'		=> 	'Registration Key',
									'default'	=>	$registrationKey,
								);
			$pluginOptions['_activate_registration'] = array(
									'type'		=> 	'button',
									'label'		=> 	'Activate Registration',
									'default'	=> 	'Activate',
									'info'		=> 	'Activate the above entered registration key with the registry server.',
								);
			// ...or request a new registration key
			$pluginOptions['_registry_new'] 		= array(
									'type'		=> 	'display',
									'label'		=> 	'Request Registration',
									'default'	=> 	'To request a new registration key, please complete the following information...',
								);
			$pluginOptions['_registry_name'] 		= array(
									'type'		=> 	'text',
									'label'		=> 	'Registrant\'s Full Name',
									'default'	=>	$current_user->display_name,
									'attributes'=> 	['autocomplete="name"']
								);
			$pluginOptions['_registry_company']		= array(
									'type'		=> 	'text',
									'label'		=> 	'Registrant\'s Organization ',
									'default'	=>	$this->get_network_option('_registry_company'),
									'attributes'=> 	['autocomplete="organization"']
								);
			$pluginOptions['_registry_address']		= array(
									'type'		=> 	'textarea',
									'label'		=> 	'Registrant\'s Address ',
									'default'	=>	$this->get_network_option('_registry_address'),
									'attributes'=> 	['autocomplete="street-address address-level1 address-level2 postal-code"']
								);
			$pluginOptions['_registry_email']		= array(
									'type'		=> 	'email',
									'label'		=> 	'Registrant\'s Email Address',
									'default'	=>	$current_user->user_email,
									'attributes'=> 	['autocomplete="email"']
								);
			$pluginOptions['_create_registration'] 	= array(
									'type'		=> 	'button',
									'label'		=> 	'Submit Registration',
									'default'	=> 	'Register',
									'info'		=> 	'Submit a request for a registration key to the registry server.',
								);
		}
		else
		{
			// display the current registration with options to refresh & delete
			if ($refresh = $this->nextRegistryRefreshEvent()) {
				$refresh = wp_date($this->plugin->date_time_format,$refresh->timestamp);
			} else {
				$refresh = __('none, please refresh now.');
			}
			$refresh = sprintf(__('Next scheduled refresh: %s'),$refresh);
			$pluginOptions['registry_key']			= array(
									'type'		=> 	'disabled',
									'label'		=> 	'Registration Key',
									'default'	=>	$registrationKey,
								);
			$pluginOptions['_registry_info']		= array(
									'type'		=> 	'display',
									'label'		=> 	'Registration Information',
									'default'	=> 	$this->getRegistryHtml($registrationKey),
									'advanced'	=> 	true,
								);
			$pluginOptions['_refresh_registration']	= array(
									'type'		=> 	'button',
									'label'		=> 	'Refresh Registration',
									'default'	=> 	'Refresh',
									'info'		=> 	'Refresh registration by re-validating registration key with the registry server.'.
													'<br/><small>'.$refresh.'</small>',
								);
			$pluginOptions['_delete_registration'] 	= array(
									'type'		=> 	'button',
									'label'		=> 	'Delete Registration',
									'default'	=> 	'Delete',
									'info'		=> 	'Delete this registration by removing the registration key and deactivating on the registry server.',
								);
		}

		if ($this->plugin->is_network_enabled())
		{
			// only register from network administration
			$this->plugin->registerNetworkOptions([$title,'registration'],$pluginOptions);
			$this->plugin->registerPluginOptions([$title,'registration'],
			[
				'registry_key'					=> 	array(
									'type'		=> 	'disabled',
									'label'		=> 	'Registration Key',
									'default'	=>	$registrationKey,
								),
				'_registry_info'				=> 	array(
									'type'		=> 	'display',
									'label'		=> 	'Registration Information',
									'default'	=> 	$this->getRegistryHtml($registrationKey),
									'advanced'	=> 	true,
								)
			]);
		}
		else
		{
			// single site
			$this->plugin->registerPluginOptions([$title,'registration'],$pluginOptions);
		}

		// when our submit buttons post
		$this->add_filter( 'options_form_post__create_registration', 	array($this, 'form_request_create'), 10, 4 );
		$this->add_filter( 'options_form_post__activate_registration', 	array($this, 'form_request_activate'), 10, 4 );
		$this->add_filter( 'options_form_post__refresh_registration', 	array($this, 'form_request_refresh'), 10, 4 );
		$this->add_filter( 'options_form_post__delete_registration', 	array($this, 'form_request_delete'), 10, 4 );

		$this->add_action('options_settings_page_footer', function()
			{
				if ($supp = $this->getRegistrySupplemental()) {
					echo "<div id='settings-page-footer' class='supplemental-content'>".$supp."</div>\n";
				}
			},
			100
		);
	}


	/**
	 * add additional actions and filters
	 *
	 * should be called from addActionsAndFilters()
	 *	$this->swRegistrationActionsAndFilters();
	 *
	 * @return	void
	 */
	private function swRegistrationActionsAndFilters(): void
	{
		if (is_admin())
		{
			// when updated, refresh the registration
			$this->add_action( 'version_updated', 				array($this, 'update_request_refresh'),10,3 );

			// pass registration key in plugin updater request (from plugin_update.trait)
			$this->add_filter( 'plugin_update_parameters', function($parameters)
				{
					if ($RegistrationKey = $this->getRegistrationKey()) {
						$parameters['requestHeaders']['Authorization'] 	= "token ".base64_encode($RegistrationKey);
					}
					return $parameters;
				}
			);

			// Add contextual help
			$this->add_action( 'options_settings_help', 		array( $this, 'getRegistryHelp') );

			add_action('admin_init', 							array($this, 'swRegistrationAdminInit'));
		}
	}


	/**
	 * add additional actions and filters - on admin_init to allow for translations
	 *
	 * @return	void
	 */
	public function swRegistrationAdminInit(): void
	{
		// add registration link on plugins page
		$registrationLink = $this->plugin->getSettingsLink(true,'registration','Registration','Registration');

		\add_filter( (is_network_admin() ? 'network_admin_' : '').'plugin_action_links_' . $this->plugin->PLUGIN_SLUG,
			function($pluginLinks, $pluginFile, $pluginData) use ($registrationLink) {
				return array_merge(['registration' => $registrationLink], $pluginLinks);
			},25,3
		);

		if (! $this->isValidRegistration())
		{
			/**
			 * filter {classname}_unregistered_notice
			 * @param string
			 * @return	string
			 */
			$notice = $this->apply_filters('unregistered_notice',
				"%1\$s is currently unregistered or inactive.\n".
				"You may check your %2\$s on the settings page."
			);
			$notice = sprintf(__($notice, $this->plugin->PLUGIN_TEXTDOMAIN),
				'<em>'.$this->plugin->pluginHeader('Title').'</em>', $registrationLink );

			if (! $this->isSettingsPage('registration'))
			{
				$this->add_admin_notice($notice,'error');
			}

			// display notice on plugins page
			\add_action( 'after_plugin_row_'.$this->plugin->PLUGIN_SLUG, function($plugin_file,$plugin_data) use ($notice)
				{
					$wp_list_table = _get_list_table( 'WP_Plugins_List_Table', ['screen' => get_current_screen()] );
					printf(
						'<tr class="plugin-update-tr active" data-slug="%s" data-plugin="%s">'.
						'<td colspan="%s" class="plugin-update colspanchange">'.
						'<div class="update-message notice inline notice-error"><p>%s</p></div>'.
						'</td></tr>',
						sanitize_title($plugin_data['Name']),
						plugin_basename($plugin_file),
						$wp_list_table->get_column_count(),
						$notice
					);
				},10,2
			);
		}
	}


	/*
	 *
	 * Filters for form post, by submit button name (_create_registration, _activate_registration, _delete_registration, _refresh_registration)
	 *
	 */


	/**
	 * filter for options_form_post_ _create_registration
	 *
	 * @param string 	$function - the value (button) POSTed
	 * @param string	$fieldName - the name of the field/option
	 * @param array		$metaData - the option metadata
	 * @param string	$priorValue - the prior option value
	 * @return string	$function
	 */
	public function form_request_create($function, $fieldName=null, $metaData=null, $priorValue=null)
	{
		$apiParams = [
			'registry_name'			=> $this->plugin->_POST('_registry_name'),
			'registry_email'		=> $this->plugin->_POST('_registry_email'),
			'registry_company'		=> $this->plugin->_POST('_registry_company'),
			'registry_address'		=> sanitize_textarea_field($_POST['_registry_address']),
			'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
			'registry_version'		=> $this->plugin->getVersion(),
			'registry_title'		=> $this->plugin->pluginHeader('Title'),
			'registry_description'	=> $this->plugin->pluginHeader('Description'),
			'registry_timezone'		=> wp_timezone_string(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->registryApiRequest('create',$apiParams);

		if (!$this->is_api_error($response))
		{
			$registry = $response->registration;
			$this->add_option_success($function,"{$function}d Registration {$registry->registry_key}; Status: {$registry->registry_status}");
			$this->plugin->page_reload(true);
		}

		$this->add_option_error($function,"Registry Error {$response->error->code} : {$response->error->message}");
		return $function; // what we're suppossed to do when returning a posted field'
	}


	/**
	 * filter for options_form_post_ _activate_registration OR _revise_registration
	 *
	 * @param string 	$function - the value (button) POSTed
	 * @param string	$fieldName - the name of the field/option
	 * @param array		$metaData - the option metadata
	 * @param string	$priorValue - the prior option value
	 * @return string	$function
	 */
	public function form_request_activate($function, $fieldName=null, $metaData=null, $priorValue=null)
	{
		$apiParams = [
			'registry_key' 			=> $this->plugin->_POST('registry_key'),
			'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
			'registry_version'		=> $this->plugin->getVersion(),
			'registry_timezone'		=> wp_timezone_string(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->registryApiRequest(strtolower($function),$apiParams);

		if (!$this->is_api_error($response))
		{
			$registry = $response->registration;
			$this->add_option_success($function,"{$function}d Registration {$registry->registry_key}; Status: {$registry->registry_status}");
			$this->plugin->page_reload(true);
		}

		$this->add_option_error($function,"Registry Error {$response->error->code} : {$response->error->message}");
		return $function; // what we're suppossed to do when returning a posted field'
	}


	/**
	 * filter for options_form_post_ _delete_registration
	 *
	 * @param string 	$function - the value (button) POSTed
	 * @param string	$fieldName - the name of the field/option
	 * @param array		$metaData - the option metadata
	 * @param string	$priorValue - the prior option value OR registry key
	 * @return string	$function
	 */
	public function form_request_delete($function, $fieldName=null, $metaData=null, $priorValue=null)
	{
		$currentRegistry = $this->getCurrentRegistration();
		if (! isset($currentRegistry->registration)) return $function;

		$apiParams = ['registry_key' => $currentRegistry->registration->registry_key];

		$response = $this->registryApiRequest('deactivate',$apiParams);

		$this->purgeRegistrationCache();

		if (!$this->is_api_error($response))
		{
			$this->add_option_success($function,"Registration {$function}d");
			$this->plugin->page_reload(true);
		}

		$this->add_option_error($function,"Registry Error {$response->error->code} : {$response->error->message}");
		return $function; // what we're suppossed to do when returning a posted field'
	}


	/**
	 * filter for options_form_post_ _refresh_registration
	 *
	 * @param string 	$function - the value (button) POSTed
	 * @param string	$fieldName - the name of the field/option
	 * @param array		$metaData - the option metadata
	 * @param string	$priorValue - the prior option value OR registry key
	 * @return string	$function
	 */
	public function form_request_refresh($function, $fieldName=null, $metaData=null, $priorValue=null)
	{
		// uses refresh (not verify) when passing multiple parameters
		$apiParams = [
			'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
			'registry_version'		=> $this->plugin->getVersion(),
			'registry_timezone'		=> wp_timezone_string(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->refreshRegistration($this->plugin->_POST('registry_key'),$apiParams);

		if (!$this->is_api_error($response))
		{
			$registry = $response->registration;
			$this->add_option_success($function,"{$function}ed Registration {$registry->registry_key}; Status: {$registry->registry_status}");
			$this->plugin->page_reload(true);
		}

		$this->add_option_error($function,"Registry Error {$response->error->code} : {$response->error->message}");
		return $function; // what we're suppossed to do when returning a posted field'
	}


	/**
	 * filter for version_updated
	 *
	 * @param	string|null	$curVersion currently installed version number
	 * @param	string		$newVersion version being installed/updated
	 * @param	bool		$asNetworkAdmin running as network admin
	 * @return	void
	 */
	public function update_request_refresh($curVersion=null, $newVersion=null, $asNetworkAdmin=false)
	{
		if ($registrationKey = $this->getRegistrationKey())
		{
			$apiParams = [
				'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
				'registry_version'		=> $newVersion,
				'registry_timezone'		=> wp_timezone_string(),
			];
			$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());
			$this->refreshRegistration($registrationKey,$apiParams);
		}
	}


	/*
	 *
	 * Get the registration data
	 *
	 */


	/**
	 * get registration custom values
	 *
	 * @return array ['variations','options','domains','sites']
	 */
	protected function getRegistryCustomValues(): array
	{
		$variations = ['environment' => ($this->plugin->is_network_enabled()) ? 'network' : 'domain'];
		$options 	= array_keys($this->plugin->extension_objects);
		$domains	= [$_SERVER['HTTP_HOST']];
		$sites 		= [get_option( 'siteurl' )];

		if ($this->plugin->is_network_enabled() && function_exists('\get_sites'))
		{
			foreach(get_sites() as $site)
			{
				if (!$site->archived && !$site->deleted) {
					$domains[] 	= $site->domain;
					$sites[] 	= $site->siteurl;
				}
			}
		}

		$domains 	= array_values(array_unique($domains));
		$sites 		= array_values(array_unique($sites));

		/**
		 * filter {classname}_registry_custom_values
		 *
		 * @param	array custom values
		 * @return	array custom values
		 */
		return $this->apply_filters('registry_custom_values', [
			'registry_variations'	=> $variations,
			'registry_options'		=> $options,
			'registry_domains'		=> $domains,
			'registry_sites'		=> $sites
		]);
	}


	/**
	 * get registry contextual help
	 *
	 * @param string $registrationKey registry key
	 * @return array help content
	 */
	public function getRegistryHelp(string $registrationKey=null)
	{
		$currentRegistry = $this->getCurrentRegistration($registrationKey);
		$help = [];

		if (empty($currentRegistry))
		{
			return $this->plugin->pluginHeader('Title').' is currently unregistered';
		}
		// if we have 'help', use it
		if (!empty($currentRegistry->registrar->help))
		{
			$help[] = $currentRegistry->registrar->help;
		}
		else
		// otherwise use notices & message
		{
			foreach ((array)$currentRegistry->registrar->notices as $type=>$notice)
			{
				if (!empty($notice)) {
					$help[] = $notice;
				}
			}
			if (!empty($currentRegistry->registrar->message))
			{
				$help[] = $currentRegistry->registrar->message;
			}
		}
		$this->addPluginHelpTab('Registration',$help,null,99);

		return $help;
	}


	/**
	 * get registry information - in html table
	 *
	 * @param string $registrationKey registry key
	 * @return string
	 */
	protected function getRegistryHtml(string $registrationKey=null,$getHelp=false)
	{
		$currentRegistry = $this->getCurrentRegistration($registrationKey);
		$html = "";

		if (empty($currentRegistry))
		{
			return $this->plugin->pluginHeader('Title').' is currently unregistered';
		}
		$html .= "<div class='hidden' style='display:none'>";
		foreach ((array)$currentRegistry->registrar->notices as $type=>$notice)
		{
			if (!empty($notice)) {
				$html .= "<div class='notice notice-{$type}'><p><strong>{$notice}</strong></p></div>";
			}
		}
		$html .= "</div>";
		if (!empty($currentRegistry->registrar->message))
		{
			$html .= "<div class='registration-message'>".$currentRegistry->registrar->message."</div>";
		}
		$html .= $currentRegistry->registryHtml ?: '';
		return wp_kses_post($html);
	}


	/**
	 * get registry information - in html table
	 *
	 * @param string $registrationKey registry key
	 * @return string
	 */
	protected function getRegistrySupplemental(string $registrationKey=null)
	{
		$currentRegistry = $this->getCurrentRegistration($registrationKey);

		if (empty($currentRegistry))
		{
			return '';
		}
		$html = $currentRegistry->supplemental ?? '';
		return wp_kses_post($html);
	}


	/*
	 *
	 * interface implementation through softwareregistry_wordpress trait
	 *
	 */
}
?>

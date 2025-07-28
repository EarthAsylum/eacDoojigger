<?php
namespace EarthAsylumConsulting\Traits;

/**
 * software registration UI for use with {eac}SoftwareRegistry - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	25.0726.1
 */

trait swRegistrationUI
{
	/**
	 * @var string the current registration key
	 */
	private $registrationKey = null;

	/**
	 * @var object the current registration object
	 */
	private $currentRegistry = null;


	/**
	 * add additional actions and filters
	 *
	 * should be called from addActionsAndFilters()
	 *	$this->swRegistrationActionsAndFilters();
	 *
	 */
	private function swRegistrationActionsAndFilters(): void
	{
		if (! is_admin()) return;

		if ($this->registrationKey = $this->getRegistrationKey())
		{
			$this->currentRegistry = $this->getCurrentRegistration($this->registrationKey);
		}

		// when the plugin is updated, refresh the registration
		$this->add_action( 'version_updated', 			array( $this, 'update_request_refresh'),10,3 );

		// Add contextual help
		$this->add_action( 'options_settings_help', 	array( $this, 'getRegistryHelp') );

		// pass registration key in plugin updater request (from plugin_update.trait)
		$this->add_filter( 'plugin_update_parameters', 	function($params)
			{
				if ($this->registrationKey) {
					$params['requestHeaders']['Authorization'] 	= "token ".base64_encode($this->registrationKey);
				}
				return $params;
			}
		);

		// on admin_init to allow for translations
		add_action('admin_init', 						function()
			{
				// get registration link
				$registrationLink = $this->plugin->getSettingsLink(true,'registration','Registration','Registration');

				// add registration link on plugins page
				\add_filter( (is_network_admin() ? 'network_admin_' : '').'plugin_action_links_' . $this->plugin->PLUGIN_SLUG,
					function($pluginLinks, $pluginFile, $pluginData) use ($registrationLink) {
						return array_merge(['registration' => $registrationLink], $pluginLinks);
					},25,3
				);

				// check status and dates for registration validity
				if (! $this->isValidRegistration())
				{
					$this->invalidRegistrationNotice($registrationLink);
				}
			}
		);
	}


	/**
	 * swRegistrationUI - User Interface
	 *
	 * should be called from __construct() in 'options_settings_page' action
	 * 	add_action( 'options_settings_page', [$this,'swRegistrationUI'], PHP_INT_MAX );
	 *
	 */
	public function swRegistrationUI(): void
	{
		// a reserved option is separate WP option, not stored with plugin options array
		$this->plugin->isReservedOption(self::SOFTWARE_REGISTRY_OPTION,true);

		$title = $this->plugin->pluginHeader('Title');
		$this->registerExtension( [$title,'registration'] );

		// hides the submit button
		$pluginOptions = [ '_btnSubmitOptions' => ['type'=>'hidden'] ];

		if (empty($this->registrationKey))
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
			if ($this->isSettingsPage('Registration')) {
				$this->getRegistryNotices();
			}
			if ($refresh = $this->nextRegistryRefreshEvent()) {
				$refresh = wp_date($this->plugin->date_time_format,$refresh->timestamp);
			} else {
				$this->update_request_refresh();
				$this->plugin->page_reload(true);
			}
			$refresh = sprintf(__('Next scheduled refresh: %s'),$refresh);

			$pluginOptions['registry_key']			= array(
									'type'		=> 	'disabled',
									'label'		=> 	'Registration Key',
									'default'	=>	$this->registrationKey,
								);
			$pluginOptions['_registry_info']		= array(
									'type'		=> 	'display',
									'label'		=> 	'Registration Information',
									'default'	=> 	$this->getRegistryHtml(),
									'advanced'	=> 	true,
								);
			$pluginOptions['_refresh_registration']	= array(
									'type'		=> 	'button',
									'label'		=> 	'Refresh Registration',
									'default'	=> 	'Refresh',
									'info'		=> 	'Refresh registration by re-validating the registration key with the registry server.'.
													'<small> -- '.$refresh.'</small>',
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
/*
			$this->plugin->registerPluginOptions([$title,'registration'],
			[
				'_btnSubmitOptions'				=> 	$pluginOptions['_btnSubmitOptions'],
				'registry_key'					=> array(
									'type'		=> 	'disabled',
									'label'		=> 	'Registration Key',
									'default'	=>	$this->registrationKey,
								),
				'_registry_info'				=> 	$pluginOptions['_registry_info'],
			]);
*/
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

		// add supplemental to the settings footer
		$this->add_action('options_settings_page_footer', function()
			{
				if ($supp = $this->getRegistrySupplemental()) {
					echo "<div id='settings-page-footer' class='supplemental-content'>".$supp."</div>\n";
				}
			},
			100
		);
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
			'registry_name'			=> $this->plugin->varPost('_registry_name'),
			'registry_email'		=> $this->plugin->varPost('_registry_email'),
			'registry_company'		=> $this->plugin->varPost('_registry_company'),
			'registry_address'		=> sanitize_textarea_field($_POST['_registry_address']),
			'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
			'registry_version'		=> $this->plugin->getVersion(),
			'registry_title'		=> $this->plugin->pluginHeader('Title'),
			'registry_description'	=> $this->plugin->pluginHeader('Description'),
			'registry_timezone'		=> wp_timezone_string(),
			'registry_locale'		=> get_locale(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->registryApiRequest('create',$apiParams);
		if (!$this->is_api_error($response))
		{
			$this->request_success("{$function}d",$response);
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
			'registry_key' 			=> $this->plugin->varPost('registry_key'),
			'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
			'registry_version'		=> $this->plugin->getVersion(),
			'registry_timezone'		=> wp_timezone_string(),
			'registry_locale'		=> get_locale(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->registryApiRequest(strtolower($function),$apiParams);

		if (!$this->is_api_error($response))
		{
			$this->request_success("{$function}d",$response);
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
			'registry_locale'		=> get_locale(),
		];
		$apiParams = array_merge($apiParams,$this->getRegistryCustomValues());

		$response = $this->refreshRegistration($this->plugin->varPost('registry_key'),$apiParams);

		if (!$this->is_api_error($response))
		{
			$this->request_success("{$function}ed",$response);
			$this->plugin->page_reload(true);
		}

		$this->add_option_error($function,"Registry Error {$response->error->code} : {$response->error->message}");
		return $function; // what we're suppossed to do when returning a posted field'
	}


	/**
	 * output success notice
	 *
	 */
	protected function request_success($action,$response)
	{
		$registry = $response->registration;
		if ($refresh = $this->nextRegistryRefreshEvent()) {
			$refresh = wp_date($this->plugin->date_time_format.' (T)',$refresh->timestamp);
			$refresh = sprintf(__('Next scheduled refresh: %s'),$refresh);
		} else {
			$refresh = '';
		}

		$expires = $registry->registry_expires.' 23:59:59';
		$expires = date_create($expires,timezone_open($response->registrar->timezone ?? 'UTC'));
		$expires = $expires->setTimeZone(wp_timezone())->format($this->plugin->date_time_format.' (T)');

		$action = strtolower($action);
		$this->add_option_success($action,
			sprintf(__("Registration {$action}: %s"),$registry->registry_key),
			'success',
			sprintf(__("Status: %s, Expires: %s<br>%s"),$registry->registry_status,$expires,$refresh)
		);
	//	do_action('eacDoojigger_log_debug',$response,current_action());
	}


	/*
	 *
	 * When the plugin is updated
	 *
	 */


	/**
	 * filter for version_updated
	 *
	 * @param	string|null	$curVersion currently installed version number
	 * @param	string		$newVersion version being installed/updated
	 * @param	bool		$asNetworkAdmin running as network admin
	 */
	public function update_request_refresh($curVersion=null, $newVersion=null, $asNetworkAdmin=false): void
	{
		if ($registrationKey = $this->getRegistrationKey())
		{
			$apiParams = [
				'registry_product'		=> self::SOFTWARE_REGISTRY_PRODUCTID,
				'registry_version'		=> $newVersion,
				'registry_timezone'		=> wp_timezone_string(),
				'registry_locale'		=> get_locale(),
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
	 * Invalid registration notices
	 *
	 * @param string $registrationLink passed from swRegistrationAdminInit
	 */
	private function invalidRegistrationNotice($registrationLink): void
	{
		$registry = $this->currentRegistry;
		$status  = $registry->registration->registry_status ?? 'inactive';

		if (in_array($status,['trial','active'])) {
			$expires = $registry->registration->registry_expires.' 23:59:59';
			$expires = date_create($expires,timezone_open($registry->registrar->timezone ?? 'UTC'));
			$expires = $expires->setTimeZone(wp_timezone())->format($this->plugin->date_time_format.' (T)');
			$expires = ' '.sprintf(__('with an expiration of %s.', $this->plugin->PLUGIN_TEXTDOMAIN),$expires);
		} else {
			$expires = '.';
		}

		/**
		 * filter {pluginName}_unregistered_notice
		 * @param string
		 * @return	string
		 */
		$notice = $this->apply_filters('unregistered_notice',
			"%s is currently %s%s\n".
			"You may check your %s on the settings page."
		);

		// "<plugin title> is currently <status> with an expiration of <expiration date/time>"
		$notice = sprintf(__($notice, $this->plugin->PLUGIN_TEXTDOMAIN),
			'<em>'.$this->plugin->pluginHeader('Title').'</em>', $status, $expires, $registrationLink );

		// display notice on admin pages
		if (! $this->isSettingsPage('registration')) {
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


	/**
	 * get registration custom values
	 *
	 * @return array ['variations','options','domains','sites']
	 */
	protected function getRegistryCustomValues(): array
	{
		// variations : environment network or single site
		$variations = ['environment' => ($this->plugin->is_network_enabled()) ? 'network' : 'site'];
		// options : list of all extensions loaded
		$options 	= array_keys($this->plugin->extension_objects);
		// domains : current host
		$domains	= [$_SERVER['HTTP_HOST']];
		// sites : current site url
		$sites 		= [get_option( 'siteurl' )];

		// add network domains and sites
		if ($this->plugin->is_network_enabled() && function_exists('\get_sites'))
		{
			foreach(get_sites() as $site)
			{
				if (!$site->archived && !$site->deleted) {
					$domains[] 	= $site->domain;
					$sites[] 	= $site->siteurl;
				}
			}
			$domains 	= array_values(array_unique($domains));
			$sites 		= array_values(array_unique($sites));
		}

		/**
		 * filter {pluginName}_registry_custom_values
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
	 * @return string help content
	 */
	public function getRegistryHelp()
	{
		$help = [];

		if (empty($this->currentRegistry))
		{
			return $this->plugin->pluginHeader('Title').' is currently unregistered';
		}
		// if we have 'help', use it
		if (!empty($this->currentRegistry->registrar->help))
		{
			$help[] = $this->currentRegistry->registrar->help;
		}
		else
		// otherwise use notices & message
		{
			foreach ((array)$this->currentRegistry->registrar->notices as $type=>$notice)
			{
				if (!empty($notice)) {
					$help[] = $notice;
				}
			}
			if (!empty($this->currentRegistry->registrar->message))
			{
				$help[] = $this->currentRegistry->registrar->message;
			}
		}
		$this->addPluginHelpTab('Registration',$help,null,99);

		return $help;
	}


	/**
	 * get registry information - in html table
	 *
	 * @return string
	 */
	protected function getRegistryHtml()
	{
		$html = "";

		if (empty($this->currentRegistry))
		{
			return $this->plugin->pluginHeader('Title').' is currently unregistered';
		}

		if (!empty($this->currentRegistry->registrar->message))
		{
			$html .= "<div class='registration-message'>".$this->currentRegistry->registrar->message."</div>";
		}
		$html .= $this->currentRegistry->registryHtml ?? '';
		return wp_kses_post($html);
	}


	/**
	 * get registry notices
	 *
	 * @return string
	 */
	protected function getRegistryNotices()
	{
		if (empty($this->currentRegistry))
		{
			return;
		}

		$html = "<div class='hidden' style='display:none'>";
		foreach ((array)$this->currentRegistry->registrar->notices as $type=>$notice)
		{
			if (!empty($notice)) {
				$html .= "<div class='notice notice-{$type}'><p><strong>{$notice}</strong></p></div>";
			}
		}
		$html .= "</div>";
		echo wp_kses_post($html);
	}


	/**
	 * get registry information - in html table
	 *
	 * @return string
	 */
	protected function getRegistrySupplemental()
	{
		if (empty($this->currentRegistry))
		{
			return '';
		}

		$html = $this->currentRegistry->supplemental ?? '';
		return wp_kses_post($html);
	}


	/*
	 *
	 * interface implementation through softwareregistry_wordpress trait
	 *
	 */
}

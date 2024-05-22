<?php
namespace EarthAsylumConsulting\Traits;

/**
 * standard options trait - {eac}Doojigger for WordPress
 *
 * Add standard options when registering a plugin or extension.
 * siteEnvironment, adminSettingsMenu, UninstallOptions, emailFatalNotice, backupOptions, restoreOptions, backupNetwork, restoreNetwork, clearCache, networkCache, networkActive
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0424.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

/*
 * Usage:
 *
 * Use this trait in your class file...
 *
 *		use \EarthAsylumConsulting\Traits\standard_options;
 *
 * And...
 *
 *		$options = $this->standard_options(['adminSettingsMenu','uninstallOptions','emailFatalNotice','backupOptions','restoreOptions']);
 */

trait standard_options
{
	/**
	 * Get named standard option meta-data
	 *
	 * @param string|array $options list of option names
	 * @return 	array selected option meta-data
	 */
	protected function standard_options($options): array
	{
		if (!is_array($options))
		{
			$options = array_filter(array_map('trim', explode(",", $options)));
		}

		$return = [];

		foreach ($options as $option)
		{
			$method = 'stdOptions_'.trim($option);
			if (method_exists($this,$method))
			{
				if ($result = $this->$method())
				{
					$return = array_merge($return,$result);
				}
			}
		}

		return $return;
	}


	/**
	 * get siteEnvironment meta-data - live/test site
	 *
	 * @return 	array
	 */
	private function stdOptions_siteEnvironment(): array
	{
		if (defined('WP_ENVIRONMENT_TYPE') || class_exists('EarthAsylumConsulting\\eacDoojiggerEnvironment', false))
		{
			return [
				'siteEnvironment'		=> array(
					'type'		=> 	'disabled',
					'label'		=> 	$this->plugin->is_network_admin() ? 'Network Environment' : 'Site Environment',
					'options'	=> 	[self::ENVIRONMENT_LIVE, self::ENVIRONMENT_TEST],
					'info'		=>	'The WordPress environment is: '. (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'not defined')
/*
					'info'		=>	"See the 'WP Environment' option in the WordPress ".
									($this->plugin->is_network_admin()
										? "<a href='".network_admin_url('settings.php')."'>Network settings</a>."
										: "<a href='".admin_url('options-general.php')."'>General settings</a>.")."<br/>".
									'The WordPress environment is: '. (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'not defined')
*/
				),
			];
		}
		else
		{
			return [
				'siteEnvironment'		=> array(
						'type'		=> 	'select',
						'label'		=> 	$this->plugin->is_network_admin() ? 'Network Environment' : 'Site Environment',
						'options'	=> 	[self::ENVIRONMENT_LIVE, self::ENVIRONMENT_TEST],
						'info'		=>	'The WordPress environment is: '. (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'not defined')
				),
			];
		}
	}


	/**
	 * get adminSettingsMenu meta-data - add plugin setting to menu
	 *
	 * @return 	array
	 */
	private function stdOptions_adminSettingsMenu(): array
	{
		return [
			'adminSettingsMenu'		=> array(
				'type'		=> 	'checkbox',
				'label'		=> 	'Settings Admin Menu',
				'options'	=> 	['Main Sidebar','Plugins Menu','Tools Menu','Settings Menu'],
				'default'	=> 	['Main Sidebar'],
				'info'		=> 	'Add "'.$this->plugin->getPluginValue('Title').'" settings menu for easy access'
			),
		];
	}


	/**
	 * get UninstallOptions meta-data - data removed when uninstalled
	 *
	 * @return 	array
	 */
	private function stdOptions_uninstallOptions(): array
	{
		if ($this->plugin->is_network_admin())
		{
			$this->rename_network_option('UninstallOptions','uninstall_options');
			return [
				'uninstall_options'		=> array(
					'type'		=> 	'checkbox',
					'label'		=> 	'Uninstall Options',
					'options'	=> 	[
										['Options &amp; Settings'		=> 'options'],
										['Transient (temporary) Data'	=> 'transients'],
									],
					'default'	=>	['options','transients'],
					'info'		=> 	'Check data that should be removed when this plugin is uninstalled'
				),
			];
		}
		else
		{
			$this->rename_option('UninstallOptions','uninstall_options');
			return [
				'uninstall_options'		=> array(
					'type'		=> 	'checkbox',
					'label'		=> 	'Uninstall Options',
					'options'	=> 	[
										['Options &amp; Settings'		=> 'options'],
										['Transient (temporary) Data'	=> 'transients'],
										['Custom Database Tables'		=> 'tables'],
									],
					'default'	=>	['options','transients'],
					'info'		=> 	'Check data that should be removed when this plugin is uninstalled'
				),
			];
		}
	}


	/**
	 * get emailFatalNotice meta-data - email to receive fatal notifications
	 *
	 * @return 	array
	 */
	private function stdOptions_emailFatalNotice(): array
	{
		return [
			'emailFatalNotice'		=> array(
				'type'		=> 	'email',
				'label'		=> 	'Email Fatal Errors',
				'default'	=> 	get_bloginfo('admin_email'),
				'info'		=> 	'Attempt to send fatal PHP error notifications to this email address.',
				'advanced'	=>	true,
			),
		];
	}


	/**
	 * get backupOptions meta-data - backup button
	 *
	 * @return 	array
	 */
	private function stdOptions_backupOptions(): array
	{
		return [
			'_btnBackupOptions'		=> array(
				'type'		=> 	'button',
				'label'		=> 	'Backup Settings',
				'default'	=> 	'Backup',
				'info'		=> 	"Backup all {$this->pluginName} settings.",
				'validate'	=> 	function($value) {
									$this->plugin->do_option_backup();
									$this->add_option_success('button',"Your settings have been backed up.");
								},
			),
		];
	}


	/**
	 * get restoreOptions meta-data - restore button
	 *
	 * @return 	array
	 */
	private function stdOptions_restoreOptions(): array
	{
		$backupTime = ( $backup = $this->plugin->get_option_backup() )
						? wp_date($this->plugin->date_time_format,$backup['{timestamp}']) : "";
		return [
			'_btnRestoreOptions'.( ($backupTime) ? '' : '_hidden' ) => array(
				'type'		=> 	($backupTime) ? 'button' : 'hidden',
				'label'		=> 	'Restore Settings',
				'default'	=> 	($backupTime) ? 'Restore' : '',
				'after'		=> 	($backupTime) ? '&nbsp;<small>Last backup: '.$backupTime.'</small>' : '',
				'info'		=> 	($backupTime) ? "Restore all {$this->pluginName} settings from the backup created on {$backupTime}." : "No backup to restore from.",
				'validate'	=> 	function($value) {
									if ($value == 'Restore') {
										$this->plugin->do_option_restore();
										$this->add_option_success('button',"Your settings have been restored and are reflected below.");
									}
								},
			),
		];
	}


	/**
	 * get backupOptions meta-data - backup button (all sites)
	 *
	 * @return 	array
	 */
	private function stdOptions_backupNetwork(): array
	{
		if ( ! $this->plugin->is_network_admin() ) return [];

		return [
			'_btnBackupNetwork'		=> array(
				'type'		=> 	'button',
				'label'		=> 	'Backup Network Settings',
				'default'	=> 	'Backup All',
				'info'		=> 	"Backup all {$this->pluginName} settings for all network sites.",
				'validate'	=> 	function($value) {
									$this->plugin->do_network_backup();
									$this->add_option_success('button',"The settings for all sites have been backed up.");
								},
			),
		];
	}


	/**
	 * get restoreOptions meta-data - restore button (all sites)
	 *
	 * @return 	array
	 */
	private function stdOptions_restoreNetwork(): array
	{
		if ( ! $this->plugin->is_network_admin() ) return [];

		$backupTime = ( $backup = $this->plugin->get_network_backup() )
						? wp_date($this->plugin->date_time_format,$backup['{timestamp}']) : "";
		return [
			'_btnRestoreNetwork'.( ($backupTime) ? '' : '_hidden' )	=> array(
				'type'		=> 	($backupTime) ? 'button' : 'hidden',
				'label'		=> 	'Restore Network Settings',
				'default'	=> 	($backupTime) ? 'Restore' : '',
				'after'		=> 	($backupTime) ? '&nbsp;<small>Last backup: '.$backupTime.'</small>' : '',
				'info'		=> 	($backupTime) ? "Restore all {$this->pluginName} settings for all network sites from the backup created on {$backupTime}." : "No backup to restore from.",
				'validate'	=> 	function($value) {
									if ($value == 'Restore') {
										$this->plugin->do_network_restore();
										$this->add_option_success('button',"Your settings for all sites have been restored. Network settings are reflected below.");
									}
								},
			),
		];
	}


	/**
	 * get clearCache meta-data - button to clear caches
	 *
	 * @return 	array
	 */
	private function stdOptions_clearCache(): array
	{
		return [
			'_btnClearCache'		=> array(
				'type'		=> 	'button',
				'label'		=> 	'Clear Caches &amp; Transients',
				'default'	=> 	'Clear Caches',
				'info'		=> 	"Clear WordPress caches and {$this->pluginName} transients.",
				'after' 	=>  wp_using_ext_object_cache()
								? '' : '&nbsp;&nbsp;<input class="input-checkbox" type="checkbox" name="clear_transients" id="clear_transients" value="true" checked="checked">'.
								'<label for="clear_transients">Include Transients</label>',
				'validate'	=> 	function($value) {
									$withTrans = (isset($_POST['clear_transients']) && $_POST['clear_transients'] == 'true');
									$this->plugin->flush_caches( $withTrans );
									$this->page_reload(true);
								},
			),
		];
	}


	/**
	 * get networkCache meta-data - button to clear caches across network sites
	 *
	 * @return 	array
	 */
	private function stdOptions_networkCache(): array
	{
		if ( ! $this->plugin->is_network_admin() ) return [];

		$this->add_filter( "options_form_post__btnNetworkCache", array($this, 'stdOptions_post_networkCache') );

		return [
			'_btnNetworkCache'		=> array(
				'type'		=> 	'button',
				'label'		=> 	'Clear Network Caches &amp; Transients',
				'default'	=> 	'Network Caches',
				'info'		=> 	"Clear WordPress caches and {$this->pluginName} transients for all network sites.",
				'after' 	=>  wp_using_ext_object_cache()
								? '' : '&nbsp;&nbsp;<input class="input-checkbox" type="checkbox" name="site_transients" id="site_transients" value="true">'.
								'<label for="site_transients">Include Transients</label>',
			),
		];
	}

	/**
	 * When _btnNetworkCache button is posted, display notice
	 *
	 * @return	void
	 */
	public function stdOptions_post_networkCache(): void
	{
		$withTrans = (isset($_POST['site_transients']) && $_POST['site_transients'] == 'true');
		$this->plugin->flush_caches( $withTrans );
		$this->plugin->forEachNetworkSite(function() use($withTrans)
			{
				$this->plugin->flush_caches( $withTrans );
			}
		);
		$message = ($withTrans) ? 'cache & transient' : 'cache';
		$this->add_option_success('button',"The {$message} cleanup action has been triggered on all sites.");
		$this->page_reload(true);
	}


	/**
	 * get networkActive meta-data - list all sites with this plugin active
	 *
	 * @return 	array
	 */
	private function stdOptions_networkActive(): array
	{
		if ( ! $this->plugin->is_network_admin() ) return [];

		$active = [];
		$sites = \get_sites();
		foreach($sites as $site)
		{
			\switch_to_blog( $site->blog_id );
			if ( $this->is_network_enabled() || $this->is_plugin_active() )
			{
				$active[\get_option( 'siteurl' )] = \get_option( 'blogname' );
			}
			\restore_current_blog();
		}

		$html = "<table>";
		foreach ($active as $url => $name) {
			$html .= "<tr><td>{$name}</td><td>{$url}</td></tr>";
		}
		$html .= "</table>";

		return [
			'_dspNetworkActive'		=> array(
				'type'		=> 	'display',
				'label'		=> 	'Active Sites/Blogs',
				'default'	=> 	$html,
			),
		];
	}


	/**
	 * hide/override default submit button (no submit)
	 *
	 * @return 	array
	 */
	private function stdOptions_noSubmit(): array
	{
		return [
			'_btnSubmitOptions'		=> array(
				'type'		=> 	'hidden',
				'label'		=> 	'submit',
				'default'	=> 	'',
			),
		];
	}


	/**
	 * option to export settings
	 *
	 * @return 	array
	 */
	private function stdOptions_optionExport(): array
	{
		$nonce 		= wp_create_nonce("{$this->pluginName}_export");
		$basename 	= "{$this->pluginName}_".($this->is_network_admin() ? 'network' : 'site')."_settings.json";
		return [
			'_btnExportOptions'		=> array(
				'type'		=> 	'display',
				'label'		=> 	'Export Settings',
				'default'	=> 	"<a href='".
								admin_url("admin-post.php?action={$this->pluginName}_settings_export&_wpnonce={$nonce}").
								"' class='button button-large' style='text-align:center'>Export</a>",
				'info'		=> 	"Export &amp; download all {$this->pluginName} settings to {$basename}.",
				'help'		=> "[info]",
			),
		];
	}

	/**
	 * Action required for optionExport, must be added in plugin or extension constructor
	 *
	 * @example $this->standard_options('optionExport_action');
	 * @return	void
	 */
	public function stdOptions_optionExport_action(): void
	{
		add_action("admin_post_{$this->pluginName}_settings_export", array($this, 'stdOptions_post_optionExport'));
	}

	/**
	 * When _btnExportOptions button is posted
	 *
	 * @internal
	 * @return	void
	 */
	public function stdOptions_post_optionExport(): void
	{
		$nonce = $_REQUEST['_wpnonce'] ?? null;
		if ( ! current_user_can('manage_options') || ! wp_verify_nonce( $nonce, "{$this->pluginName}_export" ) )
		{
			wp_die( __( 'Security Violation' ), 403 );
		}

		$options = ($this->is_network_admin())
			? $this->plugin->networkOptions
			: $this->plugin->pluginOptions;
		unset(
			$options[$this->plugin::PLUGIN_INSTALLED_VERSION],
			$options[$this->plugin::PLUGIN_INSTALLED_EXTENSIONS],
		);

		$reserved = [];
		foreach ($this->plugin->reservedOptions as $optName => $isReserved)
		{
			if ($isReserved)
			{
				$reserved[$optName] = $this->get_option($optName);
				unset($options[$optName]);
			}
		}
		ksort($options);
		ksort($reserved);

		$data = wp_json_encode(array(
			'header'				=> [
				'home'				=> home_url(),
				'plugin'			=> $this->pluginName,
				'version'			=> $this->plugin->getVersion(),
				'time'				=> gmdate('c'),
				'options'			=> [
					'merge_settings'	=> true, // false=erase current settings
				],
			],
			($this->is_network_admin() ? 'network_settings' : 'plugin_settings')
									=> $options,
			'reserved_settings' 	=> $reserved,
		),JSON_PRETTY_PRINT);

		$basename = "{$this->pluginName}_".($this->is_network_admin() ? 'network' : 'site')."_settings.json";
		$filesize = strlen($data);

		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$basename");
		header("Content-Length: $filesize");
		header("Content-Type: application/json");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: public");
		header("Expires: 0");
		flush();
		echo $data;
		die();
	}


	/**
	 * option to import settings
	 *
	 * @return 	array
	 */
	private function stdOptions_optionImport(): array
	{
		$this->add_filter( "options_form_post__btnImportOptions", array($this, 'stdOptions_post_optionImport'),10,4 );

		return [
			'_btnImportOptions'		=> array(
				'type'		=> 	'file',
				'label'		=> 	'Import Settings',
				'default'	=> 	'Import',
				'info'		=> 	"Upload &amp; import {$this->pluginName} settings.",
				'attributes'=> 	['accept'=>'.json,application/json'],
			),
		];
	}

	/**
	 * When _btnImportOptions button is posted
	 *
	 * @internal
	 * @return	void
	 */
	public function stdOptions_post_optionImport($values, $aOptionKey, $aOptionMeta, $savedOptionValue ): array
	{
		if (! isset($values['error']) && $values['type'] == 'application/json')
		{
			if ($data = wp_json_file_decode($values['file'],['associative'=>true]))
			{
				if ($data && isset($data['header']) && $data['header']['plugin'] == $this->pluginName)
				{
					$count = 0;
					// regular plugin settings (not network admin)
					if (isset($data['plugin_settings']) && !empty($data['plugin_settings']) && !$this->is_network_admin())
					{
						if (isset($data['header']['options']['merge_settings']) && $this->isFalse($data['header']['options']['merge_settings']))
						{
							$this->plugin->pluginOptions = [];
						}
						foreach ($data['plugin_settings'] as $optName => $optValue)
						{
							$count++;
							$this->update_option($optName,$optValue);
						}
					}
					// network plugin settings (only network admin)
					if (isset($data['network_settings']) && !empty($data['network_settings']) && $this->is_network_admin())
					{
						if (isset($data['header']['options']['merge_settings']) && $this->isFalse($data['header']['options']['merge_settings']))
						{
							$this->plugin->networkOptions = [];
						}
						foreach ($data['network_settings'] as $optName => $optValue)
						{
							$count++;
							$this->update_network_option($optName,$optValue);
						}
					}
					// reserved plugin settings
					if (isset($data['reserved_settings']) && !empty($data['reserved_settings']))
					{
						foreach ($data['reserved_settings'] as $optName => $optValue)
						{
							$count++;
							$this->isReservedOption($optName,true);
							$this->update_option($optName,$optValue);
						}
					}
					$this->add_option_success(
						$aOptionKey,
						sprintf('%s : Settings file imported successfully. %d options updated.',$aOptionMeta['label'],$count)
					);
					@unlink($values['file']);
					return $values;
				}
			}
		}

		$this->add_option_error(
			$aOptionKey,
			sprintf('%s : Import file could not be processed.',$aOptionMeta['label'])
		);
		@unlink($values['file']);
		return $values;
	}


	/**
	 * set update source - allows update from default branch or latest release (Github)
	 * if additional tags (channels) are needed, this code can be copied and modified with additional options.
	 *
	 * @return 	array
	 */
	private function stdOptions_updateChannel(): array
	{
		return [
			'selected_update_channel'	=> array(
				'type'		=> 	'select',
				'label'		=> 	'Update Channel',
				'options'	=> 	[
									'Current/Latest-Release'	=> 	'release',			// github 'latest_release'
									'Preview/Release-Candidate'	=>	'branch',			// github 'default_branch' (main)
				//	e.g.			'Beta/test version'			=> 	'branch/beta',		// github 'beta' branch (tag_name=beta)
				//	e.g.			'Previous Version'			=> 	'release/1.0.0',	// github '1.0.0' release (tag_name=1.0.0)
								],
				'info'		=> 	"Select the channel for updates to this plugin.<br>".
								"The <em>Current/Latest-Release</em> is the stable and supported release channel, ".
								"whereas the <em>Preview/Release-Candidate</em> provides an early, pre-release channel ".
								"(not recommended for production sites).",
				'attributes'=>	['onchange'=>'this.form.requestSubmit()'],
			),
		];
	}


	/**
	 * get checkForUpdates - clear update caches and redirect to updates page
	 *
	 * @return 	array
	 */
	private function stdOptions_checkForUpdates(): array
	{
		return [
			'_btnCheckForUpdates'	=> array(
				'type'		=> 	'button',
				'label'		=> 	'Check for Updates',
				'default'	=> 	'Check Now',
				'info'		=> 	"Clear WordPress update caches and check for software updates.",
				'validate'	=> 	function($value) {
									\wp_clean_update_cache();
									$this->page_redirect(network_admin_url('update-core.php'));
									die();
								},
			),
		];
	}
}

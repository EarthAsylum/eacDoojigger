<?php
/**
 * Extension: file_system - expands on the WordPress WP_filesystem - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 *
 * included for admin_options_settings() method
 * @version 23.1028.1
 */

defined( 'ABSPATH' ) or exit;

$credentials = $this->get_option('filesystem_credentials');

$this->registerExtensionOptions( $this->className,
	[
		'_fs_describe'		=> array(
				'type'		=> 	'display',
				'label'		=> 	'<span class="dashicons dashicons-open-folder"></span> WP_Filesystem',
				'default'	=>
					'This system provides access to the web server files using FTP, FTPS, or SSH. '.
					'These protocols create files and folders using your ftp user name (rather than the web '.
					'server user name) to maintain proper and secure file permissions.',
		),
	]
);
if ($this->is_network_admin())
{
	$this->registerNetworkOptions($this->className,
	[
		'filesystem_multisite' => array(
				'type'		=> 	'checkbox',
				'label'		=> 	'Share Network Credentials',
				'options' 	=> 	['Enabled'],
				'default' 	=> 	['Enabled'],
				'info'		=> 	'Share your Filesystem API credentials with all sites in your network.',
		),
	]);
}
else if (is_multisite())
{
	if ($this->is_site_option('filesystem_multisite'))
	{
		// fix: convert 'filesystem_multisite' from array to string
		if (($fix = $this->get_option('filesystem_multisite')) && is_array($fix)) {
			$fix = $fix[0];
			$this->update_option('filesystem_multisite',$fix);
		}

		$this->registerExtensionOptions($this->className,
		[
			'filesystem_multisite' => array(
					'type'		=> 	'radio',
					'label'		=> 	'Use Network Credentials',
					'options' 	=> 	[
										'Yes, use network credentials'=>'yes',
										'No, use this site\'s credentials'=>'no'
									],
					'default' 	=> 	($this->is_option('filesystem_credentials')) ? 'no' : 'yes',
					'info'		=> 	'Use the Network Administrator\'s or this site\'s Filesystem API credentials.',
					'validate'	=> 	function($value) {
										if ($value == 'yes') {
											$this->delete_option('filesystem_credentials');
										}
										return $value;
									},
			),
		]);
	}
	else
	{
		$this->delete_option('filesystem_multisite');
	}
}
$this->registerExtensionOptions( $this->className,
	[
		'_fs_erase'			=> array(
				'type'		=> 	'button',
				'label'		=> 	'Erase Credentials',
				'default' 	=> 	'Erase',
				'info'		=> 	'Erase currently stored Filesystem credentials.',
				'validate'	=> 	function($value) {
									if ($value=='Delete') {
										$this->delete_option('filesystem_credentials');
										$this->add_option_success('_fs_erase','Your stored filesystem credentials have been erased');
									}
								},
				'attributes'=>	(!$credentials) ? ['disabled'=>'disabled'] : ['onmouseup'=>'this.value=\'Delete\';'],
		),
	]
);

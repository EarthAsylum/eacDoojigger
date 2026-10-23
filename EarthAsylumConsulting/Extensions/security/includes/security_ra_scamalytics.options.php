<?php
/**
 * Extension: security - security features - {eac}Doojigger for WordPress
 *
 * included for admin_options_settings() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	26.1009.1
 */

defined( 'ABSPATH' ) or exit;

$this->registerExtensionOptions( $this->className,
	[
		'scamalytics_user' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Username",
				'default'	=>	$this->is_network_option('scamalytics_user'),
				'info'		=> 	$this->security->isPolicyEnabled('scamalytics_user')
					? "Your Scamalytics username enables <em>IP fraud detection</em>."
					: "Enter your <a href='https://scamalytics.com/' target='_blank'>Scamalytics Username</a> to enable <em>IP fraud detection</em>.",
				'attributes'=> ['autocomplete'=>'new-password'],
		),
		'scamalytics_key' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Key",
				'default'	=>	$this->is_network_option('scamalytics_key'),
				'info'		=> 	$this->security->isPolicyEnabled('scamalytics_key')
					? "Your Scamalytics API key enables <em>IP fraud detection</em>."
					: "Enter your <a href='https://scamalytics.com/' target='_blank'>Scamalytics API Key</a> to enable <em>IP fraud detection</em>.",
		),
	]
);

if ($reset = $this->isRateLimit()) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>Scamalytics has been paused until '.
								wp_date($this->plugin->date_time_format,$reset[1]).
								' due to rate limit.</em>',
		),
		]
	);
}

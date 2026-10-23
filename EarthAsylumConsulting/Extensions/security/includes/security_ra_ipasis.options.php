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
		'ipasis_key' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Key",
				'default'	=>	$this->is_network_option('ipasis_key'),
				'info'		=> 	$this->security->isPolicyEnabled('ipasis_key')
					? "Your iPASIS API Key enables <em>IP risk detection</em>."
					: "Enter your <a href='https://ipasis.com/login' target='_blank'>iPASIS API Key</a> to enable <em>IP risk detection</em>.",
		),
	]
);

if ($reset = $this->isRateLimit()) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>iPASIS has been paused until '.
								wp_date($this->plugin->date_time_format,$reset[1]).
								' due to rate limit.</em>',
			),
		]
	);
}

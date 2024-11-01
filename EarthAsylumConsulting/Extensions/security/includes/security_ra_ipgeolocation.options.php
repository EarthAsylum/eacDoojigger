<?php
/**
 * Extension: security - security features - {eac}Doojigger for WordPress
 *
 * included for admin_options_settings() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.1025.1
 */

defined( 'ABSPATH' ) or exit;

$accountTypes = array_combine(
	array_column(self::ACCOUNT_LIMITS, 0),
	array_keys(self::ACCOUNT_LIMITS)
);
$this->registerExtensionOptions( $this->className,
	[
		'ipgeolocation_key' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Key",
				'default'	=>	$this->is_network_option('ipgeolocation_key'),
				'info'		=> 	$this->security->isPolicyEnabled('ipgeolocation_key')
					? "Your IpGeoLocation API Key enables <em>IP threat detection</em>."
					: "Enter your <a href='https://app.ipgeolocation.io/login' target='_blank'>IpGeoLocation API Key</a> to enable <em>IP threat detection</em>.",
		),
		'ipgeolocation_plan'=> array(	// https://ipgeolocation.io/pricing.html
				'type'		=>	'select',
				'label'		=>	'Subscription Plan',
				'options'	=>	$accountTypes,
				'default'	=>	$this->is_network_option('ipgeolocation_plan'),
				'after'		=>	"<br><small>* <a href='https://app.ipgeolocation.io/login' target='_blank'>IpGeoLocation</a> requires a paid plan to obtain the IP threat score.</small>",
				'info'		=>	'Your plan information is used to determine the API features and to guide the rate limiting rules.',
		),
	]
);

if ($reset = $this->isRateLimit()) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>IpGeoLocation has been paused until '.
								wp_date($this->plugin->date_time_format,$reset[1]).
								' due to rate limit.</em>',
		),
		]
	);
}

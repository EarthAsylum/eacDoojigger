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

//$accountTypes = array_combine(
//	array_column(self::ACCOUNT_LIMITS, 0),
//	array_keys(self::ACCOUNT_LIMITS)
//);
$this->registerExtensionOptions( $this->className,
	[
		'abuse_ipdb_key' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"API Key",
				'default'	=>	$this->is_network_option('abuse_ipdb_key'),
				'info'		=> 	$this->security->isPolicyEnabled('abuse_ipdb_key')
					? "Your AbuseIPDB API Key enables <em>IP abuse detection and reporting</em>."
					: "Enter your <a href='https://www.abuseipdb.com/account/api' target='_blank'>AbuseIPDB API Key</a> to enable <em>IP abuse detection and reporting</em>.",
		),
		'abuse_ipdb_reporting' 	=> array(
				'type'		=>	'checkbox',
				'label'		=>	"Abuse Reporting",
				'options'	=>	[
					"Daily CSV File"	=> 'csv',
					"Real-time API" 	=> 'api',
				],
				'info'		=>	"Report abuse attempts. ".
								"File : create a CSV file that can be manually reviewed and uploaded. ".
								"API : automatically transmit abuses through the AbuseIPDB API.",
				'after'		=>	"<br><small><em>* Approval from <a href='https://www.abuseipdb.com' target='_blank'>AbuseIPDB</a> is required.</em></small>",
				'validate'	=>	function($value,$key,$meta,$saved) {
									if ($value && $value != $saved && in_array('csv',$value)) $this->get_abuse_file();
									return $value;
								},
				'advanced'	=> 	true,
		),
		// since the api gives us x-ratelimit-limit, x-ratelimit-remaining, x-ratelimit-reset
		// we don't need to track usage for rate limiting.
		/*
		'abuse_ipdb_plan'=> array(	// https://www.abuseipdb.com/pricing
				'type'		=>	'select',
				'label'		=>	'Subscription Plan',
				'options'	=>	$accountTypes,
				'default'	=>	$this->is_network_option('abuse_ipdb_plan'),
				'info'		=>	'Your plan information is used to determine the API features and to guide the rate limiting rules.',
		),
		*/
	]
);

if ($reset = $this->isRateLimit()) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>AbuseIPDB has been paused until '.
								wp_date($this->plugin->date_time_format,$reset[1]).
								' due to rate limit.</em>',
		),
		]
	);
}

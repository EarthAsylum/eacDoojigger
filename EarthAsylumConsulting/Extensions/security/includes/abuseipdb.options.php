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
 * @version 	24.1003.1
 */

defined( 'ABSPATH' ) or exit;

$this->registerExtensionOptions( $this->className,
	[
		'abuse_ipdb_key' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"API Key",
				'default'	=>	$this->is_network_option('abuse_ipdb_key'),
				'info'		=> 	$this->security->isPolicyEnabled('abuse_ipdb_key')
					? "Your AbuseIPDB API Key enables <em>IP Address checking</em>."
					: "Enter your <a href='https://www.abuseipdb.com/account/api' target='_blank'>AbuseIPDB API Key</a> to enable <em>IP Address checking</em>.",
		),
		'abuse_ipdb_level'	=> array(
				'type'		=>	'range',
				'label'		=>	'Abuse Confidence Level',
				'default'	=>	$this->is_network_option('abuse_ipdb_level',80),
				'after'		=>	'<datalist id="abuse_ipdb_level-ticks">'.
									'<option value="10" label="10"></option>'.
									'<option value="20" label="20"></option>'.
									'<option value="30" label="30"></option>'.
									'<option value="40" label="40"></option>'.
									'<option value="50" label="50"></option>'.
									'<option value="60" label="60"></option>'.
									'<option value="70" label="70"></option>'.
									'<option value="80" label="80"></option>'.
									'<option value="90" label="90"></option>'.
									'<option value="100" label="100"></option>'.
								'</datalist>'.PHP_EOL.
								'Block access on abuse level of <code>'.
								'<output name="abuse_ipdb_level_show" for="abuse_ipdb_level">[value]</output>'.
								'</code> or greater.',
				'attributes'=>	['min="10"', 'max="100"','step="5"','list="abuse_ipdb_level-ticks"',
								'oninput'=>"abuse_ipdb_level_show.value = this.value"],
		),
		'abuse_ipdb_bans' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Block IP Addresses",
				'info'		=>	"Treat additional IP address(es) as banned. Enter 1 address per line.",
				'advanced'	=> 	true,
		),
		'abuse_ipdb_reporting' 	=> array(
				'type'		=>	'checkbox',
				'label'		=>	"Abuse Reporting",
				'options'	=>	[
								"<abbr title='Create a file that can be manually reviewed and uploaded'>Daily CSV File</abbr>"			=> 'csv',
		//						"<abbr title='Automatically transmit abuses through the AbuseIPDB API'>Real-time API</abbr>" 	=> 'api',
								],
				'after'		=>	"<br><small><em>* Approval from AbuseIPDB is required.</em></small>",
				'info'		=>	"Report abuse attempts. ".
								"File : create a CSV file that can be manually reviewed and uploaded. ",
		//						"API : automatically transmit abuses through the AbuseIPDB API.",
				'advanced'	=> 	true,
		),
		'abuse_ipdb_threshold' 	=> array(
				'type'		=>	'range',
				'label'		=>	"Reporting Threshold",
				'default'	=>	$this->is_network_option('abuse_ipdb_threshold',5),
				'info'		=>	"Report abuse after an IP address has been blocked at least [value] times in a 24 hour period.",
				'after'		=>	'<datalist id="abuse_ipdb_threshold-ticks">'.
									'<option value="1"  label="&nbsp;1"></option>'.
									'<option value="5"  label="5"></option>'.
									'<option value="10" label="10"></option>'.
									'<option value="15" label="15"></option>'.
									'<option value="20" label="20"></option>'.
									'<option value="25" label="25"></option>'.
									'<option value="30" label="30"></option>'.
									'<option value="35" label="35"></option>'.
									'<option value="40" label="40"></option>'.
									'<option value="45" label="45"></option>'.
									'<option value="50" label="50"></option>'.
								'</datalist>'.PHP_EOL.
								'Report abuse after <code>'.
								'<output name="abuse_ipdb_threshold_show" for="abuse_ipdb_threshold">[value]</output>'.
								'</code> occurrences.',
				'attributes'=>	['min="1"', 'max="50"','step="1"','list="abuse_ipdb_threshold-ticks"',
								'oninput'=>"abuse_ipdb_threshold_show.value = this.value"],
				'advanced'	=> 	true,
		),
	]
);

$transient_reset_key 	= sprintf('%s_%s','ip_abuse_data','ratelimit_reset');
if ($reset = $this->plugin->get_transient($transient_reset_key)) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>AbuseIPDB has been paused until '.
								wp_date($this->plugin->date_time_format,strtotime($reset)).
								' due to rate limit.</em>',
		),
		]
	);
}

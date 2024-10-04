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
		'fraudguard_user' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Username",
				'default'	=>	$this->is_network_option('fraudguard_user'),
				'info'		=> 	$this->security->isPolicyEnabled('fraudguard_user')
					? "Your FraudGuard username &amp; password enables <em>IP Address checking</em>."
					: "Enter your <a href='https://app.fraudguard.io/register' target='_blank'>FraudGuard username &amp; password</a> to enable <em>IP Address checking</em>.",
				'attributes'=> ['autocomplete'=>'new-password'],
		),
		'fraudguard_pass' 	=> array(
				'type'		=>	'password',
				'label'		=>	"API Password",
				'default'	=>	$this->is_network_option('fraudguard_pass'),
				'info'		=> 	$this->security->isPolicyEnabled('fraudguard_pass')
					? "Your FraudGuard username &amp; password enables <em>IP Address checking</em>."
					: "Enter your <a href='https://app.fraudguard.io/register' target='_blank'>FraudGuard username &amp; password</a> to enable <em>IP Address checking</em>.",
				'attributes'=> ['autocomplete'=>'new-password'],
		),
		'fraudguard_level'	=> array(
				'type'		=>	'range',
				'label'		=>	'Fraud Risk Level',
				'default'	=>	$this->is_network_option('fraudguard_level',4),
				'info'		=>	'Risk level 1: no or low risk, 2: suspicious behavior, 3: potentially harmful '.
								'4: malicious activities, 5:  extremely perilous',
				'after'		=>	'<datalist id="fraudguard_level-ticks">'.
									'<option value=""  label="1"></option>'.
									'<option value="2" label="2"></option>'.
									'<option value="3" label="3"></option>'.
									'<option value="4" label="4"></option>'.
									'<option value="5" label="5"></option>'.
								'</datalist>'.PHP_EOL.
								'Block access on risk level of <code>'.
								'<output name="fraudguard_level_show" for="fraudguard_level">[value]</output>'.
								'</code> or greater.',
				'attributes'=>	['min="1"', 'max="5"','step="1"','list="fraudguard_level-ticks"',
								'oninput'=>"fraudguard_level_show.value = this.value"],
		),
		'fraudguard_version'=> array(
				'type'		=>	'switch',
				'label'		=>	'FraudGuard API Version',
				'options'	=>	['API Version 2'=>'V2'],
				'default'	=>	$this->is_network_option('fraudguard_version'),
				'info'		=>	'FraudGuard IP Reputation v2 is more accurate and more detailed but does not have a free tier.',
		),
	]
);

$transient_reset_key 	= sprintf('%s_%s','ip_fraud_data','ratelimit_reset');
if ($reset = $this->plugin->get_transient($transient_reset_key)) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>FraudGuard has been paused until '.
								wp_date($this->plugin->date_time_format,strtotime($reset)).
								' due to rate limit.</em>',
		),
		]
	);
}

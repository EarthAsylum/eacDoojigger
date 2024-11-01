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
 * @version 	24.1026.1
 */

defined( 'ABSPATH' ) or exit;

$this->delete_option('risk_assesment_level');
$this->delete_option('risk_assesment_convergence');
$this->delete_option('risk_assesment_quality');
$this->delete_option('risk_assesment_limit');
$this->delete_option('risk_assesment_type');
$this->delete_option('risk_assessment_type');
$this->delete_option('risk_assesment_api');
$this->delete_option('risk_assesment_threshold');
$this->delete_option('risk_assesment_banned');

$this->registerExtensionOptions( $this->className,
	[
		'risk_assessment_limit'	=> array(
				'type'		=>	'range',
				'label'		=>	'Risk Assessment Limit',
				'default'	=>	$this->is_network_option('risk_assessment_limit') ?: 80,
				'info'		=>	'Block access to the web site based on a risk assessment level of [value] or higher.',
				'after'		=>	'<datalist id="risk_assessment_limit-ticks">'.
				//					'<option value="10" label="10"></option>'.
									'<option value="20" label="20"></option>'.
									'<option value="25"></option>'.
									'<option value="30" label="30"></option>'.
									'<option value="35"></option>'.
									'<option value="40" label="40"></option>'.
									'<option value="45"></option>'.
									'<option value="50" label="50"></option>'.
									'<option value="55"></option>'.
									'<option value="60" label="60"></option>'.
									'<option value="65"></option>'.
									'<option value="70" label="70"></option>'.
									'<option value="75"></option>'.
									'<option value="80" label="80"></option>'.
									'<option value="85"></option>'.
									'<option value="90" label="90"></option>'.
									'<option value="95"></option>'.
									'<option value="100" label="100"></option>'.
								'</datalist>'.PHP_EOL.
								'Block access on risk assessment level of <code>'.
								'<output name="risk_assessment_limit_show" for="risk_assessment_limit">[value]</output>'.
								'</code> or greater.',
				'attributes'=>	['min="20"', 'max="100"','step="5"','list="risk_assessment_limit-ticks"',
								'oninput'=>"risk_assessment_limit_show.value = this.value"],
		),
		'risk_assessment_threshold' => array(
				'type'		=>	'range',
				'label'		=>	'Risk Assignment Threshold',
				'default'	=>	$this->is_network_option('risk_assessment_threshold') ?: 5,
				'info'		=>	"Block access (and report) after an IP address has been tagged (internally) at least [value] times in a 24 hour period.",
				'after'		=>	'<datalist id="risk_assessment_threshold-ticks">'.
									'<option value="1"  label="&nbsp;1"></option>'.
									'<option value="2"></option>'.
									'<option value="3"></option>'.
									'<option value="4"></option>'.
									'<option value="5"  label="5"></option>'.
									'<option value="6"></option>'.
									'<option value="7"></option>'.
									'<option value="8"></option>'.
									'<option value="9"></option>'.
									'<option value="10" label="10"></option>'.
									'<option value="11"></option>'.
									'<option value="12"></option>'.
									'<option value="13"></option>'.
									'<option value="14"></option>'.
									'<option value="15" label="15"></option>'.
									'<option value="16"></option>'.
									'<option value="17"></option>'.
									'<option value="18"></option>'.
									'<option value="19"></option>'.
									'<option value="20" label="20"></option>'.
									'<option value="21"></option>'.
									'<option value="22"></option>'.
									'<option value="23"></option>'.
									'<option value="24"></option>'.
									'<option value="25" label="25"></option>'.
								'</datalist>'.PHP_EOL.
								'Block access (and report) after <code>'.
								'<output name="risk_assessment_threshold_show" for="risk_assessment_threshold">[value]</output>'.
								'</code> incidents.',
				'attributes'=>	['min="1"', 'max="25"','step="1"','list="risk_assessment_threshold-ticks"',
								'oninput'=>"risk_assessment_threshold_show.value = this.value"],
		),
		'risk_assessment_method' 	=> array(
				'type'		=>	($this->has_filter_count('risk_assessment_provider') > 1) ? 'radio' : 'hidden',
				'label'		=>	"Multiple <abbr title='Risk Assessment'>RA</abbr> Providers",
				'options' 	=> 	[
					"<abbr title='Use first available risk assessment score.'>Divergent</abbr>" 	=> 'divergent',
					"<abbr title='Check each RA for a risk assessment score above the limit.'>Convergent</abbr>" 	=> 'convergent',
					"<abbr title='Average all risk assessment scores.'>Average</abbr>" 		=> 'average',
				],
				'default'	=>	$this->is_network_option('risk_assessment_limit') ?: 'divergent',
				'info'		=>	"<em>Divergent</em>: Check each RA until a <em>risk assessment score</em> is found, regardless of what that score is.<br>".
								"<em>Convergent</em>: Check each RA until and unless the <em>risk assessment score</em> reaches or exceeds the risk assessment limit.<br>".
								"<em>Average</em>: Sum the resulting scores of each RA and calculate the average of the scores.",
		),
		'risk_assessment_api' 	=> array(
				'type'		=>	'switch',
				'label'		=>	'Register Risk API',
				'options' 	=> 	['API Enabled'=>'Enabled'],
				'default'	=>	$this->is_network_option('risk_assessment_api'),
				'info'		=> 	"This API may be used to (externally) redirect high-risk requests to be blocked and reported.".
								"<br>&rarr; /wp-json/".$this->pluginName."/v1/register_fraud?score=nn".
								"<br>&rarr; /wp-json/".$this->pluginName."/v1/register_threat?score=nn".
								"<br>&rarr; /wp-json/".$this->pluginName."/v1/register_abuse?score=nn".
								"<br>&rarr; /wp-json/".$this->pluginName."/v1/register_risk?score=nn",
				'advanced'	=> 	true,
		),
		'risk_assessment_file' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"IP Block List",
				'options'	=>	[
					"Save To File" => 'Enabled'
				],
				'info'		=>	"When an IP address is blocked, save it to a block list file (ip_block_list.conf) in your WordPress root folder. ".
								"This file may be used by your server or router to block requests before reaching WordPress.",
				'validate'	=>	function($value,$key,$meta,$saved) {
									if ($value && $value != $saved) $this->get_ip_file();
									return $value;
								},
				'advanced'	=> 	true,
		),
		'risk_assessment_banned' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Banned IP Addresses",
				'info'		=>	"Treat additional IP addresses as high-risk (banned). Enter 1 IPv4 or IPv6 address or subnet per line.",
				'advanced'	=> 	true,
		),
		'_risk_assessment_reset' 	=> array(
				'type'		=>	'text',
				'label'		=>	"Clear IP Address",
				'info'		=>	"Clear/reset risk tracking for this IP address.",
				'validate'	=> 	function($ip) {
									if ($ip) $this->clear_risk_action($ip);
								},
				'advanced'	=> 	true,
		),
	]
);

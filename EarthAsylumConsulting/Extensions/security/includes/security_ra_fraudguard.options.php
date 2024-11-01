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

$this->delete_option('fraudguard_version');

$accountTypes = array_combine(
	array_column(self::ACCOUNT_LIMITS, 0),
	array_keys(self::ACCOUNT_LIMITS)
);
$this->registerExtensionOptions( $this->className,
	[
		'fraudguard_user' 	=> array(
				'type'		=>	'text',
				'label'		=>	"API Username",
				'default'	=>	$this->is_network_option('fraudguard_user'),
				'info'		=> 	$this->security->isPolicyEnabled('fraudguard_user')
					? "Your FraudGuard username &amp; password enables <em>IP fraud detection</em>."
					: "Enter your <a href='https://app.fraudguard.io/register' target='_blank'>FraudGuard username &amp; password</a> to enable <em>IP fraud detection</em>.",
				'attributes'=> ['autocomplete'=>'new-password'],
		),
		'fraudguard_pass' 	=> array(
				'type'		=>	'password',
				'label'		=>	"API Password",
				'default'	=>	$this->is_network_option('fraudguard_pass'),
				'info'		=> 	$this->security->isPolicyEnabled('fraudguard_pass')
					? "Your FraudGuard username &amp; password enables <em>IP fraud detection</em>."
					: "Enter your <a href='https://app.fraudguard.io/register' target='_blank'>FraudGuard username &amp; password</a> to enable <em>IP fraud detection</em>.",
				'attributes'=> ['autocomplete'=>'new-password'],
		),
		'fraudguard_plan'=> array(	// https://blog.fraudguard.io/misc/2024/08/21/API-rate-limiting-article.html
				'type'		=>	'select',
				'label'		=>	'Subscription Plan',
				'options'	=>	$accountTypes,
				'default'	=>	$this->is_network_option('fraudguard_plan'),
				'info'		=>	'Your plan information is used to determine the API version &amp; features and to guide the rate limiting rules.',
		),
	]
);

if ($reset = $this->isRateLimit()) {
	$this->registerExtensionOptions( $this->className,
		[
			'_paused'		=> array(
				'type'		=>	'display',
				'label'		=>	'<span class="dashicons dashicons-warning"></span> ',
				'default'	=>	'<em>FraudGuard has been paused until '.
								wp_date($this->plugin->date_time_format,$reset[1]).
								' due to rate limit.</em>',
		),
		]
	);
}

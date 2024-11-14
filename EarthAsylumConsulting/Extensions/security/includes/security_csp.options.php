<?php
/**
 * Extension: Content Security Assistant - {eac}Doojigger for WordPress
 *
 * included for admin_options_settings() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.1113.1
 */

defined( 'ABSPATH' ) or exit;

$this->registerExtensionOptions( $this->className,
	[
		'sec_CSP_nonce' => array(
				'type'		=>	'switch',
				'label'		=>	"CSP Nonce",
				'options'	=>	[
					"Add <abbr title='Add \"nonce=xxx\" to script tags.'>JavaScript</abbr> security nonce" => 'script',
					"Add <abbr title='Add \"nonce=xxx\" to style tags.'>Stylesheet</abbr> security nonce" => 'style',
				],
				'default'	=>	$this->is_network_option('sec_CSP_nonce'),
				'info'		=>	"These options assist in building a comprhensive Content Security Polciy ".
								"by adding a common, request-specific security nonce to source and in-line &lt;script&gt; tags and stylesheet  &lt;link&gt; tags (excluding inline styles). ",
				'advanced'	=> 	true,
		),
		'sec_CSP_action' => array(
				'type'		=>	'switch',
				'label'		=>	"CSP Action",
				'options'	=>	[
					"Trigger <abbr title='Trigger the \"".$this->plugin->prefixHookName('content_security_policy')."\" action, passing the nonce value, to apply the \"Content-Security-Policy\" header.'>content_security_policy</abbr> Action" => 'action',
				],
				'default'	=>	$this->is_network_option('sec_CSP_action'),
				'info'		=>	"Use the '".$this->plugin->prefixHookName('content_security_policy')."' action to apply the \"Content-Security-Policy\" header ".
								"or the '".$this->plugin->prefixHookName('security_nonce')."' filter to retreive the security nonce value.",
				'help'		=>	"[info]<br><code>add_action('".$this->plugin->prefixHookName('content_security_policy')."',function(\$nonce) {".
								"<br>&nbsp;&nbsp;&nbsp; header(\"Content-Security-Policy: script-src 'nonce-{\$nonce}';\");</code><br>});",
				'advanced'	=> 	true,
		),
	]
);

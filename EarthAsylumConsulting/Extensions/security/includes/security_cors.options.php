<?php
/**
 * Extension: security - security features - {eac}Doojigger for WordPress
 *
 * included for admin_options_settings() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	25.0421.1
 */

defined( 'ABSPATH' ) or exit;

$this->registerExtensionOptions( $this->className,
	[
		'secCorsOpt' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"<abbr title='Cross-Origin Resource Sharing'>CORS</abbr> Options",
				'options'	=>	array(
					["Apply CORS to <abbr title='REpresentational State Transfer (e.g. /wp-json)'>REST</abbr> requests" =>'rest'],
					["Apply CORS to <abbr title='eXtensible Markup Language - Remote Procedure Call (e.g. /xmlrpc.php)'>XML-RPC</abbr> requests" =>'xml'],
					["Apply CORS to <abbr title='Asynchronous JavaScript and XML (e.g. /admin-ajax.php)'>AJAX</abbr> requests" =>'ajax'],
					["Apply CORS to <abbr title='Form Posts (as well as put or delete) requests'>Other Post</abbr> requests" =>'post'],
					["Use <abbr title='Get origin from the http referer header'>referring URL</abbr> if no origin" =>'referer'],
					["Use <abbr title='Get origin by reverse DNS lookup'>IP address</abbr> if no origin" =>'ip_address'],
					["<abbr title='Verify IP address of incoming requests with origin=".$this->plugin->varServer('host')." to prevent spoofing'>Validate</abbr> this site's origin to its IP address" => 'host_origin'],
				),
				'default'	=>	$this->is_network_option('secCorsOpt'),
				'after'		=>	(!is_network_admin() && $this->security->isNetworkPolicy('secCorsOpt')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"CORS is a security feature implemented by browsers. These options implement basic ".
								"CORS security at the server level helping to prevent malicious activty from browser &amp; non-browser sources.",
				'help'		=> 	'CORS (Cross-Origin Resource Sharing) - [info]',
				'attributes'=>	(!is_network_admin() && $this->security->isNetworkPolicy('secCorsOpt')) ? 'disabled="disabled"' : '',
		),
		'secAllowCors' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"CORS Origin Whitelist",
				'default'	=>	$this->is_network_option('secAllowCors'),
				'info'		=>	"Allow API access from specific origin domains only. ".
								"Enter origin URLs, 1 per line beginning with 'http://' or 'https://, or simply the ending domain name ".
								"(e.g. 'http://api.trusted_domain.com' or '.trusted_domain.com').",
				'after'		=>	(!is_network_admin() && $this->security->isNetworkPolicy('secAllowCors')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'attributes'=>	['placeholder'=>'https://origin.trusted_domain.com'],
		),
		'secAllowCorsIP' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Allowed IP Addresses",
				'info'		=>	"Allow these IP addresses regardless of origin domain. Enter 1 IPv4 or IPv6 address or subnet (CIDR) per line.",
				'advanced'	=> 	true,
		),
		'secExcludeCors' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"CORS Exempt URIs",
				'default'	=>	$this->is_network_option('secExcludeCors'),
				'info'		=>	"Exclude site URIs from CORS security checks, allowing access from any origin. ".
								"Enter URIs, 1 per line, beginning with /.",
				'after'		=>	(!is_network_admin() && $this->security->isNetworkPolicy('secExcludeCors')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'attributes'=>	['placeholder'=>'/wp-json/...'],
		),
	]
);

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
 * @version 	24.0921.1
 */

defined( 'ABSPATH' ) or exit;

$current_login_uri = $this->get_site_option('secLoginUri');

/* register this extension with [group name, tab name], and settings fields */
$this->registerExtensionOptions( $this->className,
	[
		'secLoginUri'		=> array(
				'type'		=>	($this->htaccess) ? 'text' : 'disabled',
				'label'		=> 	"Change Login URI ",
				'before'	=>	'<code>'.site_url().'/</code>',
				'default'	=> 	$current_login_uri,
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secPassPolicy')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	'Security by obscurity: Change the name of the well-known \'wp-login\'. '.
								'Users must login at this url before accessing the WordPress dashboard.'.
								(($this->htaccess) ? '<br/><small>* Updates the Apache .htaccess file using rewrite rules and adds filters/actions for login.</small>' : ''),
				'attributes'=>	['pattern'=>'[a-zA-Z0-9_\.\-]*','placeholder'=>'wp-login.php']
		),
		'secPassPolicy' 	=> array(
				'type'		=>	'checkbox',
				'label'		=>	"Password Policy ",
				'options'	=> 	array(
									['Minimum Length of 10'		=> 'min-len'],
									['Has Alpha Character(s)'	=> 'has-alpha'],
									['Has Numeric Character(s)'	=> 'has-num'],
									['Has Special Character(s)'	=> 'has-spec']
								),
				'default'	=>	$this->is_network_option('secPassPolicy',''),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secPassPolicy')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Password policies for user profiles (enforce strong passwords).",
		),
		'secPassLock'		=> array(
				'type'		=>	'range',
				'label'		=>	'Account Login Attempts ',
				'default'	=>	$this->is_network_option('secPassLock','0'),
				'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secPassLock')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'after'		=>	'<datalist id="secPassLock-ticks">'.
									'<option value="0" label="0"></option>'.
									'<option value="1"></option>'.
									'<option value="2" label="2"></option>'.
									'<option value="3"></option>'.
									'<option value="4" label="4"></option>'.
									'<option value="5"></option>'.
									'<option value="6" label="6"></option>'.
									'<option value="7"></option>'.
									'<option value="8" label="8"></option>'.
									'<option value="9"></option>'.
									'<option value="10" label="10"></option>'.
								'</datalist>'.PHP_EOL.
								'Lock the user account after <code>'.
								'<output name="secPassLockShow" for="secPassLock">[value]</output>'.
								'</code> login attempts (0 = unlimited).',
				'attributes'=>	['min="0"', 'max="10"','step="1"','list="secPassLock-ticks"',
								'oninput'=>"secPassLockShow.value = this.value"],
		),
		'secPassTime'		=> array(
				'type'		=>	'range',
				'label'		=>	'Account Lock Time ',
				'default'	=>	$this->is_network_option('secPassTime','5'),
				'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secPassTime')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'after'		=>	'<datalist id="secPassTime-ticks">'.
									'<option value="5" label="5m"></option>'.
									'<option value="30"></option>'.
									'<option value="60" label="1hr" style="width:4%"></option>'.
									'<option value="120"></option>'.
									'<option value="180"></option>'.
									'<option value="240" label="4hrs" style="width:15%"></option>'.
									'<option value="300"></option>'.
									'<option value="360"></option>'.
									'<option value="420"></option>'.
									'<option value="480" label="8hrs" style="width:20%"></option>'.
									'<option value="540"></option>'.
									'<option value="600"></option>'.
									'<option value="660"></option>'.
									'<option value="720" label="12hrs" style="width:20%"></option>'.
									'<option value="780"></option>'.
									'<option value="840"></option>'.
									'<option value="900"></option>'.
									'<option value="960" label="16hrs" style="width:20%"></option>'.
									'<option value="1020"></option>'.
									'<option value="1080"></option>'.
									'<option value="1140"></option>'.
									'<option value="1200" label="20hrs" style="width:20%"></option>'.
									'<option value="1260"></option>'.
									'<option value="1320"></option>'.
									'<option value="1380"></option>'.
									'<option value="1440" label="24hrs" style="width:20%"></option>'.
								'</datalist>'.PHP_EOL.
								'Lock the user account for <code>'.
								'<output name="secPassTimeShow" for="secPassTime">[value]</output>'.
								'</code> minutes after failed login.',
				'attributes'=>	['min="5"', 'max="1440"','step="5"','list="secPassTime-ticks"',
								'oninput'=>"secPassTimeShow.value = this.value"],
		),
		'secCodeEditor' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable Code Editor",
				'options'	=>	array(['Code Editor Disabled'=>'no-code']),
				'default'	=>	$this->is_network_option('secCodeEditor'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secCodeEditor')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"WordPress supports online editing of theme and plugin code. This option disables the WordPress code editor.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secCodeEditor')) ? 'disabled="disabled"' : '',
		),
		'secFileChanges' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable All File Changes ",
				'options'	=>	array(['File Changes Disabled'=>'no-mods']),
				'default'	=>	$this->is_network_option('secFileChanges'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"This option prevents all file modifications in WordPress via the administration interface - including new/updated themes and plugins. ".
								"Disable file changes for everyday operation and enable when applying updates.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')) ? 'disabled="disabled"' : '',
		),
		'secUnAuthRest' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable <abbr title='REpresentational State Transfer'>REST</abbr> Requests ",
				'options'	=>	array(
					['API Index List'		=>'no-rest-index'],
					['WordPress Core APIs'	=>'no-rest-core'],
					['Un-Authenticated'		=>'no-rest-unauth'],
					['All REST APIs'		=>'no-rest'],
					["Remote Non-API <abbr title='Javascript Object Notation'>JSON</abbr>" =>'no-json']
				),
				'default'	=>	$this->is_network_option('secUnAuthRest'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secUnAuthRest')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"This option hides API index lists and may disable WP Core API URLS, un-authenticated requests, or all REST API URLs. ".
								"Additionally, JSON requests to non-api URLs (often used in attacks) can be blocked.",
				'help'		=> 	'REST (REpresentational State Transfer) API (Application Program Interface) - [info]',
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secUnAuthRest')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
		'secDisableXML' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable <abbr title='eXtensible Markup Language - Remote Procedure Call'>XML-RPC</abbr>",
				'options'	=>	array(['XML-RPC Disabled'=>'no-xml']),
				'default'	=>	$this->is_network_option('secDisableXML'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"XML-RPC may be used to attempt unauthorized access or to overload the site in a DDoS attack. Disable if XML-RPC is not needed.",
				'help'		=> 	'XML (eXtensible Markup Language) RPC (Remote Procedure Call) - [info]',
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
		'secDisablePings' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable Pingbacks",
				'options'	=>	array(['Pingbacks Disabled'=>'no-ping']),
				'default'	=>	$this->is_network_option('secDisablePings'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisablePings')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Pingbacks may be enabled or disabled on individual blog posts. This option disables all pingbacks.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisablePings')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
		'secDisableRSS' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable <abbr title='Really Simple Syndication/Atom Syndication Format '>RSS/ATOM</abbr> Feeds ",
				'options'	=>	array(['RSS Feeds Disabled'=>'no-rss']),
				'default'	=>	$this->is_network_option('secDisableRSS'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableRSS')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"RSS/ATOM URLs may be used to attempt unauthorized access or to overload the site in a DDoS attack. Disable if RSS/ATOM feeds are not needed.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableRSS')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
		'secDisableURIs' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Disable URIs ",
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableURIs')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Certain URIs should be unavailable or may present a security concern. ".
								"This option allows you to block access to those URIs. ".
								"Enter URIs, 1 per line, starting with '/'. For example '/category/name/' or just '/category'".
								(($this->htaccess) ? '<br/><small>* These URIs will be blocked in the Apache .htaccess file using rewrite rules OR through internal code to ensure functionality.</small>' : '')
		),
		'secBlockIP' 		=> array(
				'type'		=>	'textarea',
				'label'		=>	"Block Addresses ",
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secBlockIP')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Block specific IP addresses or host/referrer names. ".
								"Enter addresses 1 per line. For example '192.168.100.1', '2001:0db8:85a3:08d3:1319:8a2e:0370:7334', or 'maliciousdomain.com'".
								(($this->htaccess) ? '<br/><small>* These addresses will be blocked in the Apache .htaccess file using deny from rules OR through internal code to ensure functionality.</small>' : ''),
				'advanced'	=> 	true,
		),
		'secCookies' 		=> array(
				'type'		=>	'checkbox',
				'label'		=>	"Global Cookie Flags ",
				'options'	=>	array(['HTTP Only'=>'httponly'],['Secure (SSL)'=>'secure'],['SameSite (strict)'=>'strict']),
				'default'	=>	$this->is_network_option('secCookies'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secCookies')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"<table>".
								"<tr><td>HTTP&nbsp;Only</td><td>Refuses access to cookies from JavaScript. This setting prevents cookies snatched by a JavaScript injection.</td></tr>".
								"<tr><td>Secure&nbsp;(SSL)</td><td>Allows access to cookies only when using HTTPS. If the website is only accessible via HTTPS, this should be enabled.</td></tr>".
								"<tr><td>SameSite&nbsp;(strict)</td><td>Cookies will only be sent in a first-party context and not be sent along with requests initiated by third party websites.</td></tr>".
								"</table>",
							//	(($this->htaccess) ? '<small>* These options may be set in .htaccess, .user.ini (for php session cookies), OR through internal code to ensure functionality.</small>' : '')
		),
		'secCookiesExc' 	=> array(
								'type'		=>	'textarea',
								'label'		=>	"Cookies to Exclude ",
								'default'	=> 	"woocommerce_items_in_cart\nwoocommerce_cart_hash",
								'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secCookiesExc')
													? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'info'		=>	"Exclude these cookies when applying flags. Cookies may need to be accessable from the browser as well as the server, or with both http and https, or by 3rd parties (often for tracking). Enter cookie names 1 per line."
							),
		'secHeartbeat'		=> array(
				'type'		=>	'range',
				'label'		=>	"WP Heartbeat Time ",
				'default'	=>	$this->is_network_option('secHeartbeat','0'),
				'info'		=>	"Although not a security concern, WordPress pings the server every 15 to 60 seconds. This option can be used to slow it down and lessen resource usage.",
				'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeat')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'after'		=>	'<datalist id="secHeartbeat-ticks">'.
									'<option value="0" label="n/a"></option>'.
									'<option value="15"></option>'.
									'<option value="30" label="30"></option>'.
									'<option value="45"></option>'.
									'<option value="60" label="60"></option>'.
									'<option value="75"></option>'.
									'<option value="90" label="90"></option>'.
									'<option value="105"></option>'.
									'<option value="120" label="120"></option>'.
									'<option value="135"></option>'.
									'<option value="150" label="150"></option>'.
									'<option value="165"></option>'.
									'<option value="180" label="180"></option>'.
									'<option value="195"></option>'.
									'<option value="210" label="210"></option>'.
									'<option value="225"></option>'.
									'<option value="240" label="240"></option>'.
									'<option value="255"></option>'.
									'<option value="270" label="270"></option>'.
									'<option value="285"></option>'.
									'<option value="300" label="300"></option>'.
								'</datalist>'.PHP_EOL.
								'Allow heartbeat every <code>'.
								'<output name="secHeartbeatShow" for="secHeartbeat">[value]</output>'.
								'</code> seconds (0 = unrestricted).',
				'attributes'=>	['min="0"', 'max="300"','step="15"','list="secHeartbeat-ticks"',
								'oninput'=>"secHeartbeatShow.value = this.value"],
				'advanced'	=> 	true,
		),
		'secHeartbeatFE' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable Front-End Heartbeat ",
				'options'	=> 	['Heartbeat Disabled'=>'Enabled'],
				'default'	=>	$this->is_network_option('secHeartbeatFE'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeatFE')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Often the WordPress heartbeat ping is not needed on the site's public front-end. It can be disabled here but may be required by certain plugins or themes.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeatFE')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
	]
);
$this->registerExtensionOptions( 'Server_Side_CORS',
	[
		'secCorsOpt' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"<abbr title='Cross-Origin Resource Sharing'>CORS</abbr> Options",
				'options'	=>	array(
					["Apply CORS to <abbr title='REpresentational State Transfer (e.g. /wp-json)'>REST</abbr> requests"	=>'rest'],
					["Apply CORS to <abbr title='eXtensible Markup Language - Remote Procedure Call (e.g. xmlrpc.php)'>XML-RPC</abbr> requests" =>'xml'],
					["Apply CORS to <abbr title='Asynchronous JavaScript and XML (e.g. admin-ajax.php)'>AJAX</abbr> requests"		=>'ajax'],
					["Use <abbr title='Get origin from the http referer header'>referring URL</abbr> if no origin" =>'referer'],
					["Use <abbr title='Get origin by reverse DNS lookup'>IP address</abbr> if no origin" =>'ip_address'],
				),
				'default'	=>	$this->is_network_option('secCorsOpt'),
//				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secUnAuthRest')
//									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
//									: ''),
				'info'		=>	"CORS is a security feature implemented by browsers. These options implement basic ".
								"CORS security at the server level helping to prevent malicious activty from browser &amp; non-browser sources.",
				'help'		=> 	'CORS (Cross-Origin Resource Sharing) - [info]',
//				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secCorsOpt')) ? 'disabled="disabled"' : '',
		),
		'secAllowCors' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"CORS <abbr title='Application Program Interface'>API</abbr> White List",
				'default'	=>	$this->is_network_option('secAllowCors'),
				'info'		=>	"Allow API access from specific origin domains only. ".
								"Enter origin URLs, 1 per line beginning with 'http://' or 'https://, or simply the ending domain name ".
								"(e.g. 'http://api.trusted_domain.com' or '.trusted_domain.com').",
				'attributes'=>	['placeholder'=>'https://origin.trusted_domain.com'],
		),
		'secExcludeCors' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"CORS Exempt URIs",
				'default'	=>	$this->is_network_option('secExcludeCors'),
				'info'		=>	"Exclude site URIs from CORS security checks, allowing access from any origin. ".
								"Enter URIs, 1 per line, beginning with /.",
				'attributes'=>	['placeholder'=>'/wp-json/...'],
		),
	]
);

$adminOptions = [
];

$siteOptions = [
	$this->enable_option=> array(
			'type'		=>	'hidden',
			'label'		=>	'Enabled',
	//		'options'	=> ['Enabled'],
			'default'	=>	($this->is_network_enabled()) ? 'Enabled' : '',
			'info'		=>	( ($this->is_network_enabled()) ? 'Network Enabled' : 'Network Disabled' ) .
							" <em>(Network policies may override site policies)</em>",
	)
];

if (is_multisite())
{
	if ($this->plugin->is_network_admin())
	{ 	// only allow the cookie options via network admin on multisite
	//	$this->registerExtensionOptions($this->className,$adminOptions);
	}
	else if ( $this->plugin->is_network_enabled() )
	{	// disable 'enabled' option on sites when network activated
		$this->delete_option($this->enable_option);
		$this->registerExtensionOptions($this->className,$siteOptions);
	}
}
else
{
//	$this->registerExtensionOptions($this->className,$adminOptions);
}

$this->add_filter( 'options_form_post_secLoginUri',		array($this, 'options_form_post_secLoginUri'), 10, 4 );
$this->add_filter( 'options_form_post_secPassLock',		array($this, 'options_form_post_secPassLock'), 10, 4 );
$this->add_filter( 'options_form_post_secPassTime',		array($this, 'options_form_post_secPassTime'), 10, 4 );
$this->add_filter( 'options_form_post_secDisableURIs',	array($this, 'options_form_post_secDisableURIs'), 10, 4 );
$this->add_filter( 'options_form_post_secBlockIP',		array($this, 'options_form_post_secBlockIP'), 10, 4 );
$this->add_filter( 'options_form_post_secCookies',		array($this, 'options_form_post_secCookies'), 10, 4 );

$this->add_filter( 'options_form_post_secCodeEditor',	function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change
		if (!$this->wpConfig) return $value;		// no configurator
		if (!empty($value))
		{
			$this->wpConfig->update( 'constant', 'DISALLOW_FILE_EDIT', 'true', array( 'raw' => true ) );
		}
		else
		{
			$this->wpConfig->remove( 'constant', 'DISALLOW_FILE_EDIT' );
		}
		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secFileChanges',	function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change
		if (!$this->wpConfig) return $value;		// no configurator
		if (!empty($value))
		{
			$this->wpConfig->update( 'constant', 'DISALLOW_FILE_MODS', 'true', array( 'raw' => true ) );
		}
		else
		{
			$this->wpConfig->remove( 'constant', 'DISALLOW_FILE_MODS' );
		}
		return $value;
	},
10,4);

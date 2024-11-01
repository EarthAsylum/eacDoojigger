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
 * @version 	24.1029.1
 */

defined( 'ABSPATH' ) or exit;

$current_login_uri = $this->get_site_option('secLoginUri');


/* register this extension with [group name, tab name], and settings fields */
$this->registerExtensionOptions( $this->className,
	[
		'secLoginUri'		=> array(
				'type'		=>	($this->htaccess) ? 'text' : 'disabled',
				'label'		=> 	"Change Login URI ",
				'before'	=>	site_url('/'),
				'default'	=> 	$current_login_uri,
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secPassPolicy')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	'Security by obscurity: Change the name of the well-known \'wp-login\'. '.
								'Users must login at this url before accessing the WordPress dashboard.',
				//				(($this->htaccess) ? '<br/><small>* Updates the Apache .htaccess file using rewrite rules and adds filters/actions for login.</small>' : ''),
				'attributes'=>	['pattern'=>'[a-zA-Z0-9_\.\-]*','placeholder'=>'wp-login.php'],
				'width'		=>	'25',
		),
		'secLoginNonce' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Add Secure Nonce",
				'options'	=>	['Enabled'],
				'default'	=>	$this->is_network_option('secLoginNonce','Enabled'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secLoginNonce')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Add and verify a hidden 'number-used-once' security token to the login/reset forms to block malicious attacks.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secLoginNonce')) ? 'disabled="disabled"' : '',
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
		'secFileChanges' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable File Changes ",
				'options'	=>	[
					"<abbr title='Disables the WordPress code editor only.'>Code Editor</abbr> Disabled" => 'no-code',
					"<abbr title='Disables all file modifications and updates'>File Changes</abbr> Disabled" => 'no-mods'
				],
				'default'	=>	$this->is_network_option('secFileChanges'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"WordPress supports online editing of theme and plugin code as well as automated core, theme, and plugin updates. ".
								"These options disable editiing and file modifications.<br>".
								"<small>Disable file changes for everyday operation and enable when applying updates.</small>",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')) ? 'disabled="disabled"' : '',
		),
		'secUnAuthRest' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable <abbr title='REpresentational State Transfer'>REST</abbr> Requests ",
				'options'	=>	[
					"<abbr title='Do not reveal REST namespaces and routes.'>API Index List</abbr>" =>'no-rest-index',
					"<abbr title='Block /wp/ routes. Only Administrators, Editors, and Contributors may access.'>WordPress Core APIs</abbr>" =>'no-rest-core',
					"<abbr title='Block all un-authenticated routes. Only Logged-In Users may access.'>Un-Authenticated APIs</abbr>" =>'no-rest-unauth',
					"<abbr title='Block all routes. Only Administrators and Editors may access.'>All REST APIs</abbr>" =>'no-rest',
					"<abbr title='Typically invalid unless custom code has been added.'>Non-REST JSON Requests</abbr>" =>'no-json',
				],
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
				'options'	=>	[
					'XML-RPC Disabled'		=> 'no-xml',
				//	"<abbr title='Disable XML Pingbacks. For (newer) REST Pingbacks, go to Settings&rarr;Discussions.'>Pingbacks</abbr> Disabled"	=> 'no-ping',
					"<abbr title='Typically invalid unless custom code has been added.'>Non-RPC XML Requests</abbr>" =>'no-rpc',
				],
				'default'	=>	$this->is_network_option('secDisableXML'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"XML-RPC may be used to attempt unauthorized access or to overload the site in a DDoS attack. Disable if XML-RPC is not needed.",
				'help'		=> 	'XML (eXtensible Markup Language) RPC (Remote Procedure Call) - [info]',
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')) ? 'disabled="disabled"' : '',
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
		'secDisableEmbed' 	=> array(
				'type'		=>	'switch',
				'label'		=>	"Disable oEmbed exchange",
				'options'	=>	array(['oEmbed Disabled'=>'no-embed']),
				'default'	=>	$this->is_network_option('secDisableEmbed'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableEmbed')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"oEmbed is a format for allowing an embedded representation of a URL on third party sites.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableEmbed')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
		'secRequireHttp' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Require HTTP Headers",
				'default'	=>	$this->is_network_option('secRequireHttp'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secRequireHttp')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Require the presence of an HTTP header in all requests. ".
								"If your web site is behind a CDN (e.g. CloudFlare), you may be able to use a CDN-specific (or custom) http header and verify its existance to block any attempt to bypass the CDN. ".
								"You may enter the header name or header:value to validate a specific value.",
		),
		'secBlockHttp' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Block HTTP Headers",
				'default'	=>	$this->is_network_option('secBlockHttp'),
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secBlockHttp')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Many bots or suspicious browsers include detectable http headers. ".
								"Use this list to look for and block requests with any of these headers. ".
								"You may enter the header name or header:value to block a specific value.",
				'advanced'	=> 	true,
		),
		'secDisableURIs' 	=> array(
				'type'		=>	'textarea',
				'label'		=>	"Disable Site URIs ",
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableURIs')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Certain site URIs should be unavailable or may present a security concern. ".
								"This option allows you to block access to those URIs. ".
								"Enter URIs, 1 per line, starting with '/'. For example '/category/name/' or just '/category'",
				'help'		=>	"[info]".
								(($this->htaccess) ? '<br/>* These URIs will be blocked in the Apache .htaccess file using rewrite rules OR through internal code to ensure functionality.' : '')
		),
		'secBlockIP' 		=> array(
				'type'		=>	'textarea',
				'label'		=>	"Block IP Addresses",
				'after'		=>	(!is_network_admin() && $this->isNetworkPolicy('secBlockIP')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'info'		=>	"Block specific IP addresses or host/referrer names. ".
								"Enter addresses or subnets 1 per line. For example: <br>192.168.100.1 or 192.168.100.0/16 <br>2001:0db8:85a3:08d3:1319:8a2e:0370:7334 <br>maliciousdomain.com",
				'help'		=>	"[info]".
								(($this->htaccess) ? '<br/>* These addresses will be blocked in the Apache .htaccess file using RequireAll rules AND through internal code to ensure functionality.' : ''),
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
				'info'		=>	"Exclude these cookies when applying flags. ".
								"Cookies may need to be accessable from the browser as well as the server, or with both http and https, or by 3rd parties (often for tracking)."
			),
		'secHeartbeat'		=> array(
				'type'		=>	'range',
				'label'		=>	"WP Heartbeat Time ",
				'default'	=>	$this->is_network_option('secHeartbeat') ?: 0,
				'info'		=>	"Although not a security concern, WordPress pings the server every 15 to 60 seconds. This option can be used to slow it down and lessen resource usage.",
				'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeat')
									? '<span class="settings-tooltip dashicons dashicons-networking" title="Network policy is set"></span>'
									: ''),
				'after'		=>	'<datalist id="secHeartbeat-ticks">'.
									'<option value="0"   label="n/a"></option>'.
									'<option value="15"  label=""></option>'.
									'<option value="30"  label="30"></option>'.
									'<option value="45"  label=""></option>'.
									'<option value="60"  label="60"></option>'.
									'<option value="75"  label=""></option>'.
									'<option value="90"  label="90"></option>'.
									'<option value="105" label=""></option>'.
									'<option value="120" label="120"></option>'.
									'<option value="135" label=""></option>'.
									'<option value="150" label="150"></option>'.
									'<option value="165" label=""></option>'.
									'<option value="180" label="180"></option>'.
									'<option value="195" label=""></option>'.
									'<option value="210" label="210"></option>'.
									'<option value="225" label=""></option>'.
									'<option value="240" label="240"></option>'.
									'<option value="255" label=""></option>'.
									'<option value="270" label="270"></option>'.
									'<option value="285" label=""></option>'.
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
				'info'		=>	"Often the WordPress heartbeat ping is not needed on the site's public front-end. ".
								"It can be disabled here but may be required by WordPress scheduled tasks or certain plugins and themes.",
				'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeatFE')) ? 'disabled="disabled"' : '',
				'advanced'	=> 	true,
		),
	]
);

/**
 * filter for options_form_post_(*)
 *
 * @param mixed		$value - the value POSTed
 * @param string	$fieldName - the name of the field/option
 * @param array		$metaData - the option metadata
 * @param mixed		$priorValue - the previous value
 * @return mixed $value
 */

$this->add_filter( 'options_form_post_secLoginUri',		function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change

		$this->security_rules[$fieldName] = false;

		$marker	= $this->pluginName.' '.$this->className.' rewrite rule for wp-login';
		$value 	= sanitize_file_name($value);

		if ($this->htaccess)
		{
			$lines = array();
			if (!empty($value))
			{
				$lines = array(
					"RewriteEngine on",
					"RewriteRule ^".preg_quote($value)."$ /wp-login.php [L]",
				);
				$this->login_uri = $value;
			}
			$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#', '', true);
			$this->security_rules[$fieldName] = (!empty($lines));
		}
		$this->wp_login_notice($value);
		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secPassLock',		function($value, $fieldName, $metaData, $priorValue)
	{
		if ($policy = $this->isNetworkPolicy('secPassLock')) {
			if (!is_network_admin()) $value = min($value,$policy);
		}
		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secPassTime',		function($value, $fieldName, $metaData, $priorValue)
	{
		if ($policy = $this->isNetworkPolicy('secPassTime')) {
			if (!is_network_admin())  $value = max($value,$policy);
		}
		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secDisableURIs',	function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change

		$this->security_rules[$fieldName] = false;

		$marker		= $this->pluginName.' '.$this->className.' rewrite rule by uri';
		$uriList 	= $this->plugin->text_to_array($value);

		$value = implode("\n",$uriList);

		if ($this->htaccess)
		{
			$lines = array();
			if (!empty($value))
			{
				$lines = ['RewriteEngine on'];
				foreach ($uriList as $uri)
				{
					$uri = ltrim($uri,'/');
					$lines[] = "RewriteRule ^{$uri}(.*)$ - [F]";
				}
			}
			$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#', '', true);
			$this->security_rules[$fieldName] = (!empty($lines));
		}

		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secBlockIP',		function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change

		$this->security_rules[$fieldName] = false;

		$marker		= $this->pluginName.' '.$this->className.' deny by address';
		$ipList 	= $this->plugin->text_to_array($value);

		$ipSet = array();
		foreach ($ipList as $x => $ip)
		{
			list($ip,$subn) = explode('/',str_replace(' (invalid)','',$ip));
			// valid IP address
			$ipCheck = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
			if (!$ipCheck) {
				// valid host name
				$ipCheck = filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
				if ($ipCheck) {
					// routable host name
					$ipCheck = filter_var(gethostbyname($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
				}
			}
			if (!$ipCheck) {
				$ipList[$x] = $ip.' (invalid)';
			} else {
				$ipSet[] = $ipList[$x];
			}
		}

		$value = implode("\n",$ipList);

		if ($this->htaccess)
		{
			$lines = [];
			if (!empty($ipSet)) {
				$lines[] = "<RequireAll>";
				foreach ($ipSet as $ip)
				{
					$lines[] = "\tRequire not ip {$ip}";
				}
				$lines[] = "</RequireAll>";
			}
			$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#');
			$this->security_rules[$fieldName] = (!empty($lines));
		}

		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secCookies',		function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change

		$this->security_rules[$fieldName] = false;

		$marker		= $this->pluginName.' '.$this->className.' set cookie headers';

		if (!is_array($value)) $value = [];
		$httpOnly 	= in_array('httponly',$value);
		$secure 	= in_array('secure',$value);
		$strict 	= in_array('strict',$value);

		if ($this->userIni)
		{
			$lines = [
				'session.cookie_httponly = ' . ( ($httpOnly) ? 'on' : 'off' ),
				'session.cookie_secure = ' . ( ($secure) ? 'on' : 'off' ),
				'session.cookie_samesite = '. ( ($strict) ? '"Strict"' : '"Lax"' ),
			];
			$this->plugin->insert_with_markers($this->userIni, $marker, $lines, ';');
		}

		return $value;
	},
10,4);

$this->add_filter( 'options_form_post_secFileChanges',	function($value, $fieldName, $metaData, $priorValue)
	{
		if ($value == $priorValue) return $value; 	// no change
		if (!$this->wpConfig) return $value;		// no configurator
		if (is_array($value) && in_array('no-code',$value))
		{
			$this->wpConfig->update( 'constant', 'DISALLOW_FILE_EDIT', 'true', array( 'raw' => true ) );
		}
		else
		{
			$this->wpConfig->remove( 'constant', 'DISALLOW_FILE_EDIT' );
		}
		if (is_array($value) && in_array('no-mods',$value))
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

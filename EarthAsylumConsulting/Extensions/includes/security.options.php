<?php
/**
 * Extension: security - security features - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2021 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 *
 * included for admin_options_settings() method
 * @version 23.1028.1
 */

defined( 'ABSPATH' ) or exit;

$current_login_uri = $this->get_site_option('secLoginUri');

/* register this extension with [group name, tab name], and settings fields */
$this->registerExtensionOptions( $this->className,
	[
		'secLoginUri'		=> array(
								'type'		=>	($this->htaccess) ? 'text' : 'disabled',
								'label'		=> 	"Change Login URI ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secLoginUri')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: '').
												'<code>'.site_url().'/</code>',
								'default'	=> 	$current_login_uri,
								'info'		=>	'Security by obscurity: Change the name of the well-known \'wp-login\'.'.
												'<br/><small>Users must login at this url before accessing the WordPress dashboard.</small>'.
												(($this->htaccess) ? '<br/><small>* Updates the Apache .htaccess file using rewrite rules and adds filters/actions for login.</small>' : ''),
								'attributes'=>	['pattern'=>'[a-zA-Z0-9_\.\-]*','placeholder'=>'wp-login.php']
							),
		'secPassPolicy' 	=> array(
								'type'		=>	'checkbox',
								'label'		=>	"Password Policy ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secPassPolicy')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=> 	array(
													['Minimum Length of 10'		=> 'min-len'],
													['Has Alpha Character(s)'	=> 'has-alpha'],
													['Has Numeric Character(s)'	=> 'has-num'],
													['Has Special Character(s)'	=> 'has-spec']
												),
								'default'	=>	$this->is_network_option('secPassPolicy',''),
								'info'		=>	"Password policies for user profiles (enforce strong passwords).",
							),
		'secPassLock'		=> array(
								'type'		=>	'range',
								'label'		=>	'Account Login Attempts ',
								'default'	=>	$this->is_network_option('secPassLock','0'),
								'info'		=>	'Lock the user account after <code>'.
												'<output name="secPassLockShow" for="secPassLock" style="color:blue;">n</output>'.
												'</code> login attempts (0 = unlimited).'.
												'<script>options_form.secPassLockShow.value = secPassLock.value</script>',
								'attributes'=>	['min="0"', 'max="10"','step="1"','list="secPassLockTicks"',
												'oninput'=>"secPassLockShow.value = this.value"],
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secPassLock')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'after'		=>	'<datalist id="secPassLockTicks">'.
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
												'</datalist>',
							),
		'secPassTime'		=> array(
								'type'		=>	'range',
								'label'		=>	'Account Lock Time ',
								'default'	=>	$this->is_network_option('secPassTime','5'),
								'info'		=>	'Lock the user account for <code>'.
												'<output name="secPassTimeShow" for="secPassTime" style="color:blue;">n</output>'.
												'</code> minutes after failed login.'.
												'<script>options_form.secPassTimeShow.value = secPassTime.value</script>',
								'attributes'=>	['min="5"', 'max="1440"','step="5"','list="secPassTimeTicks"',
												'oninput'=>"secPassTimeShow.value = this.value"],
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secPassTime')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'after'		=>	'<datalist id="secPassTimeTicks">'.
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
												'</datalist>',
							),
		'secDisableRSS' 	=> array(
								'type'		=>	'radio',
								'label'		=>	"Disable RSS/ATOM Feeds ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableRSS')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],['Disable RSS Feeds'=>'no-rss']),
								'default'	=>	$this->is_network_option('secDisableRSS'),
								'info'		=>	"RSS/ATOM URLs may be used to attempt unauthorized access or to overload the site in a DDoS attack. Disable if RSS/ATOM feeds are not needed.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableRSS')) ? 'disabled="disabled"' : '',
							),
		'secUnAuthRest' 	=> array(
								'type'		=>	'radio',
								'label'		=>	"Disable REST API Requests ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secUnAuthRest')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],['Disable Un-Authenticated REST'=>'no-rest-unauth'],['Disable ALL REST'=>'no-rest']),
								'default'	=>	$this->is_network_option('secUnAuthRest'),
								'info'		=>	"Not all WordPress REST APIs require authentication. This option disables un-authenticated requests or all REST API requests.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secUnAuthRest')) ? 'disabled="disabled"' : '',
							),
		'secDisableXML' 	=> array(
								'type'		=>	'radio',
								'label'		=>	"Disable XML-RPC ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],['Disable XML-RPC'=>'no-xml'],['Disable Pingbacks'=>'no-ping']),
								'default'	=>	$this->is_network_option('secDisableXML'),
								'info'		=>	"XML-RPC (Remote Procedure Call) may also be used to attempt unauthorized access or to overload the site in a DDoS attack. Disable if XML-RPC is not needed.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableXML')) ? 'disabled="disabled"' : '',
							),
		'secCodeEditor' 	=> array(
								'type'		=>	'radio',
								'label'		=>	"Disable Code Editor ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secCodeEditor')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],['Disable Code Editor'=>'no-code']),
								'default'	=>	$this->is_network_option('secCodeEditor'),
								'info'		=>	"WordPress supports online editing of theme and plugin code. This option disables the WordPress code editor.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secCodeEditor')) ? 'disabled="disabled"' : '',
							),
		'secFileChanges' 	=> array(
								'type'		=>	'radio',
								'label'		=>	"Disable All File Changes ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],['Disable File Changes'=>'no-mods']),
								'default'	=>	$this->is_network_option('secFileChanges'),
								'info'		=>	"This option prevents all file modifications in WordPress via the administration interface - including new/updated themes and plugins. ".
												"Disable file changes for everyday operation and enable when applying updates.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secFileChanges')) ? 'disabled="disabled"' : '',
							),
		'secDisableURIs' 	=> array(
								'type'		=>	'textarea',
								'label'		=>	"Disable URIs ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secDisableURIs')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'info'		=>	"Certain URIs should be unavailable or may present a security concern. ".
												"This option allows you to block access to those URIs. ".
												"Enter URIs 1 per line starting with '/'. For example '/category/name/' or just '/category'".
												(($this->htaccess) ? '<br/><small>* These URIs will be blocked in the Apache .htaccess file using rewrite rules OR through internal code to ensure functionality.</small>' : '')
							),
		'secBlockIP' 		=> array(
								'type'		=>	'textarea',
								'label'		=>	"Block Addresses ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secBlockIP')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'info'		=>	"Block specific IP addresses or host/referrer names. ".
												"Enter addresses 1 per line. For example '192.168.100.1', '2001:0db8:85a3:08d3:1319:8a2e:0370:7334', or 'maliciousdomain.com'".
												(($this->htaccess) ? '<br/><small>* These addresses will be blocked in the Apache .htaccess file using deny from rules OR through internal code to ensure functionality.</small>' : '')
							),
		'secCookies' 		=> array(
								'type'		=>	'checkbox',
								'label'		=>	"Global Cookie Flags ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secCookies')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['HTTP Only'=>'httponly'],['Secure (SSL)'=>'secure'],['SameSite (strict)'=>'strict']),
								'default'	=>	$this->is_network_option('secCookies'),
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
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secCookiesExc')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'default'	=> 	"woocommerce_items_in_cart\nwoocommerce_cart_hash",
								'info'		=>	"Exclude these cookies when applying flags. Cookies may need to be accessable from the browser as well as the server, or with both http and https, or by 3rd parties (often for tracking). Enter cookie names 1 per line."
							),
/*
		'secHeartbeat' 		=> array(
								'type'		=>	'select',
								'label'		=>	"WP Heartbeat Time ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeat')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=>	array(['WordPress Default'=>''],'15 seconds','30 seconds','45 seconds','60 seconds','90 seconds','120 seconds'),
								'default'	=>	$this->is_network_option('secHeartbeat'),
								'info'		=>	"Although not a security concern, WordPress pings the server every 15 to 60 seconds. This option can be used to slow it down and lessen resource usage.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeat')) ? 'disabled="disabled"' : '',
							),
*/
		'secHeartbeat'		=> array(
								'type'		=>	'range',
								'label'		=>	"WP Heartbeat Time ",
								'default'	=>	$this->is_network_option('secHeartbeat','0'),
								'info'		=>	'Allow heartbeat every <code>'.
												'<output name="secHeartbeatShow" for="secHeartbeat" style="color:blue;">n</output>'.
												'</code> seconds (0 = unrestricted).'.
												" Although not a security concern, WordPress pings the server every 15 to 60 seconds. This option can be used to slow it down and lessen resource usage.".
												'<script>options_form.secHeartbeatShow.value = secHeartbeat.value</script>',
								'attributes'=>	['min="0"', 'max="300"','step="15"','list="secHeartbeatTicks"',
												'oninput'=>"secHeartbeatShow.value = this.value"],
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeat')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'after'		=>	'<datalist id="secHeartbeatTicks">'.
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
												'</datalist>',
							),
		'secHeartbeatFE' 	=> array(
								'type'		=>	'checkbox',
								'label'		=>	"Disable Front-End Heartbeat ",
								'before'	=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeatFE')
													? '<span class="dashicons dashicons-networking" title="Network policy is set"></span>'
													: ''),
								'options'	=> 	[ ['Disabled'=>'Enabled'] ],
								'default'	=>	$this->is_network_option('secHeartbeatFE'),
								'info'		=>	"Often the WordPress heartbeat ping is not needed on the site's public front-end. It can be disabled here but may be required by certain plugins or themes.",
								'attributes'=>	(!is_network_admin() && $this->isNetworkPolicy('secHeartbeatFE')) ? 'disabled="disabled"' : '',
							),
	]
);

$adminOptions = [
];

$siteOptions = [
	$this->enable_option=> array(
							'type'		=>	'hidden',
							'label'		=>	'Enabled',
						//	'before'	=>	'<span class="dashicons dashicons-networking"></span>',
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

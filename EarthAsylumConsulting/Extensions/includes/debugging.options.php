<?php
/**
 * Extension: debugging - file logging & debugging - {eac}Doojigger for WordPress
 *
 * included for admin_options_settings() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.0830.1
 */

defined( 'ABSPATH' ) or exit;
use EarthAsylumConsulting\Helpers\LogLevel;

$fs = false;
if ($this->isSettingsPage('debugging'))
{
	$fs = $this->fs->link_wp_filesystem(true,
		'Debugging extension needs WordPress file access to manage logs and configuration options.'
	);
}

// see if we can get to the wp-config file (only single site or network admin)
$this->wpConfig = $this->wpconfig_handle();

$this->plugin->rename_option('debugLevel',		'debug_log_level');
$this->plugin->rename_option('debugToFile',		'debug_to_file');
$this->plugin->rename_option('debugPHP',		'debug_php_errors');
$this->plugin->rename_option('debugBacktrace',	'debug_backtrace');
$this->plugin->rename_option('debugWP',			'debug_wp_errors');
$this->plugin->rename_option('debugDeprecated',	'debug_depricated');
$this->plugin->rename_option('debugPurge',		'debug_purge_time');
$this->plugin->rename_option('debugOnPage',		'debug_on_page');
$this->plugin->rename_option('debugTestApi',	'debug_test_api');

$debug_wp_debugging = [];
foreach (['WP_DEBUG','WP_DEBUG_DISPLAY','WP_DEBUG_LOG'] as $wpdebug)
{
	if (defined($wpdebug) && constant($wpdebug) !== false) {
		$debug_wp_debugging[] = $wpdebug;
	}
}
$this->update_option('debug_wp_debugging',$debug_wp_debugging);

$this->registerExtensionOptions( $this->className,
	[
		'debug_log_level' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'Logging Levels',
								'options'	=> 	[
													['Information'				=> LogLevel::LOG_INFO],
													['Notices'					=> LogLevel::LOG_NOTICE],
													['Warnings'					=> LogLevel::LOG_WARNING],
													['Errors &amp; Exceptions'	=> LogLevel::LOG_ERROR],
													['Debugging'				=> LogLevel::LOG_DEBUG],
												],
								'default'	=> 	[LogLevel::LOG_WARNING,LogLevel::LOG_ERROR],
								'info'		=> 	'<small>* This does not effect PHP or system logging.</small>',
								'help'		=>	'Sets the information/logging-level to be written to the log file.',
							),
		'debug_to_file' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'File Logging',
								'options'	=> 	[
													['Server Error Log'			=>'server'],
													[$this->pluginName.' Log'	=>'plugin']
												],
								'default'	=> 	'plugin',
								'info'		=> 	'<small>* This does not effect PHP or system logging.</small>',
								'help'		=> 	'The server log is quicker and less detrimental to web site performace but also shows less detail mixed with all other server-wide log entries. '.
												'The '.$this->pluginName.' log provides more detail and structure but may be detrimental to site performance due to memory usage (particularly with debugging information). ',
							),
		'debug_php_errors' 	=> array(
								'type'		=> 	'radio',
								'label'		=> 	'Capture PHP Errors',
								'options'	=> 	[
													['Disable PHP error Logging'			=>'disabled'],
													['Use PHP error reporting setting'		=>'default'],
													['Capture All PHP Error Types (E_ALL)'	=>'all'],
												],
								'default'	=> 	'disabled',
								'info'		=> 	"<small>* This does not effect PHP's error reporting or logging settings.</small>",
								'help'		=> 	'When capturing PHP errors, capture all errors or only those designated by the PHP error reporting setting.',
							),
		'debug_depricated' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'Capture WordPress Coding Messages',
								'options'	=> 	[
													['Backtrace \'deprecated\'  &amp; \'doing it wrong\' Messages'	=>'Enabled']
												],
								'info'		=>	'Messages often occure when invalid or outdated functions are called or invalid parameters are used. '.
												'This option makes it easier to debug these errors.',
								'help'		=>	'[info] Note: \'Notices &amp; Information\' along with \'Capture PHP Errors\' &amp; \'WP_DEBUG\' will also catch these as <em>notices</em>.',
								'advanced'	=> 	true,
							),
		'debug_backtrace' 	=> array(
								'type'		=> 	'range',
								'label'		=> 	'Backtrace Levels',
								'default'	=> 	5,
								'after'		=>	'<datalist id="debug_backtrace_ticks">'.
													'<option value="0" label="0">0</option>'.
													'<option value="1"></option>'.
													'<option value="2" label="2">2</option>'.
													'<option value="3"></option>'.
													'<option value="4" label="4">4</option>'.
													'<option value="5"></option>'.
													'<option value="6" label="6">6</option>'.
													'<option value="7"></option>'.
													'<option value="8" label="8">8</option>'.
													'<option value="9"></option>'.
													'<option value="10" label="10">10</option>'.
													'<option value="11"></option>'.
													'<option value="12" label="12">12</option>'.
												'</datalist>'.
												'When capturing PHP and WP coding errors, show backtracing up to <code>'.
												'<output name="debug_backtrace_show" for="debug_backtrace">[value]</output>'.
												'</code> levels.',
								'attributes'=> 	['list=debug_backtrace_ticks',
												'min=0','max=12','step=1',
												'oninput'=>"debug_backtrace_show.value = this.value"],
								'advanced'	=> 	true,
							),
		'debug_wp_errors' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'Capture WordPress Errors',
								'options'	=> 	[
													['Capture &amp; Log WP Errors'	=>'Enabled']
												],
								'info'		=>	'<small>* Not all WP_Error messages are actually errors but may  be pre-loaded in anticipation of possible error conditions.</small>',
								'help'		=>	'Captures and logs errors when added to a WP_Error object.',
								'advanced'	=> 	true,
							),
		'debug_heartbeat' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'Log WordPress Heartbeat',
								'options'	=> 	[
													['Log Heartbeat Polling' =>'Enabled']
												],
								'info'		=>	'The WordPress Heartbeat API pings the server every 15 to 60 (or more) seconds. '.
												'This option logs those request so you can determine the use, effectivness, and overhead.',
								'advanced'	=> 	true,
							),
/*
		'debug_purge_time' 	=> array(
								'type'		=> 	'select',
								'label'		=> 	'Purge Log Files After',
								'options'	=> 	['No Purging','1 Week','2 Weeks','4 Weeks','6 Weeks','8 Weeks','12 Weeks','16 Weeks','24 Weeks','32 Weeks'],
								'default'	=> 	'No Purging',
								'info'		=>	'Purge '.$this->pluginName.' log files.'
							),
*/
		'debug_purge_time' 	=> array(
								'type'		=> 	'range',
								'label'		=> 	'Purge Log Files After',
								'default'	=> 	0,
								'after'		=>	'<datalist id="debug_purge_time_ticks">'.
													'<option value="0" label="0">0</option>'.
													'<option value="1"></option>'.
													'<option value="2" label="2">2</option>'.
													'<option value="3"></option>'.
													'<option value="4" label="4">4</option>'.
													'<option value="5"></option>'.
													'<option value="6" label="6">6</option>'.
													'<option value="7"></option>'.
													'<option value="8" label="8">8</option>'.
													'<option value="9"></option>'.
													'<option value="10" label="10">10</option>'.
													'<option value="11"></option>'.
													'<option value="12" label="12">12</option>'.
												'</datalist>'.
											 	'Purge '.$this->pluginName.' log files after <code>'.
												'<output name="debug_purge_time_show" for="debug_purge_time">[value]</output>'.
												'</code> week(s).',
								'attributes'=> 	['list=debug_purge_time_ticks',
												'min=0','max=12','step=1',
												'oninput'=>"debug_purge_time_show.value = this.value"],
							),
		'debug_on_page' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'On Page Debugging',
								'options'	=> 	['Enabled'],
								'info'		=>	'Show debugging information in the help tab or in a floating window at the bottom of the page.'
							),
	]
);

if (! is_multisite() || $this->plugin->is_network_admin())
{
	$this->registerExtensionOptions( $this->className,
		[
			'debug_wp_debugging' => array(
								'type'		=> 	'checkbox',
								'label'		=> 	'WordPress Debugging',
								'options'	=> 	[
													['Enable WP Debugging' 		=> 'WP_DEBUG'],
													['Enable Error Display' 	=> 'WP_DEBUG_DISPLAY'],
													['Enable Debug Logging' 	=> 'WP_DEBUG_LOG'],
												],
								'info'		=>	'Enable WordPress built-in debugging options (WP_DEBUG).',
								'attributes'=>	(!$this->wpConfig) ? 'disabled="disabled"' : '',
							),
		]
	);
}

if ( (! is_multisite() || $this->plugin->is_network_admin()) &&
	 (file_exists($this->plugin->pluginHeader('VendorDir').'/Utilities/eacDoojiggerActionTimer.class.php')) )
{
	$default = $this->varPost('_btnActionTimer') ?: ((file_exists(WPMU_PLUGIN_DIR.'/eacDoojiggerActionTimer.php')) ? 'Install' : 'Uninstall');
	$default = ($default=='Install') ? 'Uninstall' : 'Install';
	$this->registerExtensionOptions( $this->className,
		[
			'_btnActionTimer' 	=> array(
								'type'		=> 	'button',
								'label'		=> 	'Plugin Action Timer',
								'default'	=> 	$default,
								'info'		=>	$default.' the Action Timer plugin in the \'mu_plugins\' folder.'.
												'<br/><small>* Requires write access to mu-plugins folder.</small>',
								'help'		=>	'The Plugin Action Timer tracks timing/duration of plugins being loading, specific WordPress actions, '.
												'and has the ability to time custom code execution. '.
												'Results can be seen in debugging logs under \'WordPress Timing\'',
								'validate'	=>	[$this, 'install_actiontimer'],
								'attributes'=>	(!$fs) ? 'disabled="disabled"' : '',
								'advanced'	=> 	true,
							),
		]
	);
}
$this->add_filter( 'options_form_post_debug_wp_debugging', 		array($this, 'options_form_post_wp_debugging'),10,4 );

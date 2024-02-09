<?php
/**
 * Extension: debugging - file logging & debugging - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2021 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 *
 * included for admin_options_help() method
 * @version 23.1028.1
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
	The debugging extensions adds tools to help administrators and developers "see" what's going on behind
	the scenes in their WordPress environment.

	Logging is programmed within the {eac}Doojigger classes and methods.
	Different log levels (notices, warnings, errors, debugging)
	may be used throughout and you can control which of those levels you want to see in the log file.

	You may also use the debugging methods programmatically in your theme, functions, and any other custom code
	using the eacDoojigger() global function:
<pre>
eacDoojigger()->logNotice( {any_data_structure}, 'log notice label' );
eacDoojigger()->logWarning( {any_data_structure}, 'log warning label' );
eacDoojigger()->logError( {any_data_structure}, 'log error label' );
eacDoojigger()->logDebug( {any_data_structure}, 'log debug label' );
</pre>

	When 'On Page Debugging' is enabled you can see (some of) the logging detail either in the 'Help' tab of
	any administration screen or in a floating tab at the bottom of any front-end-page.<br>

	<details><summary>Requirements</summary>
		When initially loaded, this extension creates a log folder, by default, within the 'wp-content' folder.
		On multi-site installations, an additional sub-folder is created for each site.

		To do this, we need...
		<ul>
			<li>Write access to the WordPress 'wp-content' folder <em>(%1$s)</em>.
			<li>Write access to the log folder <em>(%1$s/%2$s)</em>.
		</ul>
		If necessary, you may manually create the log folder <em>('%2$s')</em>
		in either the 'wp-content' or 'wp-content/uploads' folder.

		If you have defined 'WP_DEBUG_LOG' as a file path in your wp-config.php,
		that folder will be used as the root folder for '%2$s'.
	</details>
<?php
$wp_folder = str_replace(ABSPATH,'.../',WP_CONTENT_DIR);
$log_folder = sanitize_key($this->pluginName.'_logs');
$content = sprintf(ob_get_clean(),$wp_folder,$log_folder);

$this->addPluginHelpTab('Debugging',$content,['Debugging Extension','open']);

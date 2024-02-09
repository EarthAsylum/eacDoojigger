<?php
/**
 * Extension: file_system - expands on the WordPress WP_filesystem - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 *
 * included for admin_options_help() method
 * @version 23.1028.1
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
To properly install extensions and create folders &amp; files, {eac}Doojigger uses the WordPress
file access sub-system (WP_Filesystem). This system provides access to the web server files
using FTP (file transfer protocol), FTPS (FTP over ssl), or SSH (secure shell or SFTP).

These protocols create files and folders using <em>your ftp user name</em> rather than the default web
server user name (e.g. "www"", "www-data", "nobody", "apache") which should maintain proper and secure file permissions.

WordPress uses the WP_Filesystem when installing updates, plugins, and themes. You have probably seen
the "Connection Information" form when applying updates. Unlike WordPress, {eac}Doojigger retains your
connection information, in a secure, encrypted state, to be used when needed. Your secure credentials
will be used by WordPress so you never see the "Connection Information" form again.

<details><summary>When does {eac}Doojigger use WP_Filesystem?</summary>
	<ul>
		<li>When installing the auto-loader after {eac}Doojigger has been installed or updated.
		<li>When installing the Environment Switcher from the "Tools" tab.
		<li>When installing the Plugin Timmer from the "Debugging" tab.
		<li>When the debugging extension creates the log file folder(s) and files.
		<li>If updating .htaccess, wp-config.php, or .user.ini due to options selected from the settings page.
	</ul>
</details>

<details><summary>Technical Tips</summary>
	<ul>
		<li>If WordPress incorrectly determines the preferred file access method,<br>
			add this line to your "wp-config.php":<br>
			<code>define( 'FS_METHOD', 'direct' );</code><br>
			<cite>This will force the file access method to "direct" (or "ftpext" or "ssh2" or "ftpsockets").</cite>
		</li>
		<li>If default file permissions cause problems,
			add these lines to your "wp-config.php":<br>
			<code>define( 'FS_CHMOD_DIR', 0775 );</code><br>
			<code>define( 'FS_CHMOD_FILE', 0664 );</code><br>
			<cite>This will give read/write access to both the user/owner and the group.<br>
			<small>* This example is NOT the most secure permissions you may be able to, or should use.
			Use caution when setting these values that you don't expose your system to attack or unauthorized access.</small></cite>
		</li>
		<li>
			Information on manually configuring WordPress file access:
			<ul>
			<li><a href='https://developer.wordpress.org/advanced-administration/server/file-permissions/' target='_blank'>Developer Resources - Changing File Permissions</a>
			<li><a href='https://developer.wordpress.org/apis/wp-config-php/#wordpress-upgrade-constants' target='_blank'>WordPress Upgrade Constants</a><br>
			<li><a href='https://developer.wordpress.org/apis/filesystem/' target='_blank'>Developer Resources - Filesystem</a><br>
			</ul>
		</li>
	</ul>
</details>
<?php
$content = ob_get_clean();
$this->addPluginHelpTab("File Access",$content,['WordPress File Access','open']);

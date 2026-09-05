<?php
/**
 * Extension: security - security features - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		26.0904.1
 *
 * included for admin_options_help() method
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
	<p>The Security Extension of {eac}Doojigger enables general security options,
	Server-Side CORS (Cross-Origin Resource Sharing),
	and Risk Assessment tools providing a general-purpose and easy to use "firewall" to your WordPress site.</P>
<?php
$content = ob_get_clean();

$this->addPluginHelpTab(self::TAB_NAME,$content,['Security','open']);

$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-lock'></span>Security",
	"https://eacdoojigger.earthasylum.com/eacdoojigger-security/",
	"{eac}Doojigger Security"
);

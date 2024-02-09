<?php
/**
 * Extension: encryption - string encryption/decryption - {eac}Doojigger for WordPress
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
	Encryption uses OpenSSL to provide filters to encrypt and decrypt values.
	The encryption key is combined with a salt value assigned by WordPress to create an OpenSSL digest key.
	The key may be changed by double-clicking the field, previous values are retained for decryption use.
<?php
$content = ob_get_clean();

$examples = "<pre>".
			"\$encrypted = apply_filters( '".$this->plugin->prefixHookName('encrypt_string')."', \$plaintext );\n" .
			"\$plaintext = apply_filters( '".$this->plugin->prefixHookName('decrypt_string')."', \$encrypted );".
			"</pre>";
$content .= "<details open><summary>Filter Examples</summary>".$examples."</details>";

$this->addPluginHelpTab('Encryption',$content,['Encryption','open']);

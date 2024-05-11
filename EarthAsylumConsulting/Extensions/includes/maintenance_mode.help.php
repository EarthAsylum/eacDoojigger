<?php
/**
 * Extension: maintenance_mode - put site in scheduled maintenance - {eac}Doojigger for WordPress
 *
 * included for admin_options_help() method
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	24.0314.1
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
	Enabling Maintenance Mode makes your site unavailable to visitors, only logged-in<sup>*</sup>
	editors and administrators will be able to access the site.
	<br><small>* Users must be logged in before enabling Maintenance Mode.</small>

	The "Maintenance Mode Message" field is used to create a page that will be displayed to site visitors.
	The field may make use of several shortcodes to build the page.
	<details><summary>Available Shortcodes</summary>
		<ul>
			<li><code>[BlogName]</code><br>
				Display blog/site name ('%s')
			<li><code>[BlogDescription]</code><br>
				Display the blog/site description (tag line) ('%s')
			<li><code>[PageHeader]</code><br>
				Include the 'scheduled-maintenance' header theme template,
				if not found the default theme header is used.
			<li><code>[PageTemplate]{template-name}[/PageTemplate]</code><br>
				Include the {template-name}.php or 'scheduled-maintenance.php' theme template,
			<li><code>[PageContent]...html content...[/PageContent]</code><br>
				Include the 'scheduled-maintenance' post or template content,
				if not found {html content} is used.
			<li><code>[PageContent id='&lt;post_id or slug_name&gt;']...html content...[/PageContent]</code><br>
				Include the post or template content identified by post_id or slug_name,
				if not found {html content} is used.
			<li><code>[PageFooter]</code><br>
				Include the 'scheduled-maintenance' footer theme template,
				if not found the default theme footer is used.
		</ul>
	</details>
	<details><summary>Default Message</summary>
		<pre>%s</pre>
	</details>

	<small> Maintenance Mode will send a "Status: 503 Service Temporarily Unavailable" header
		letting search engines know to rescan the site later.</small>
<?php
$content = sprintf(ob_get_clean(),
			\get_option('blogname'),
			esc_html(\get_option('blogdescription')),
			esc_html(self::DEFAULT_HTML)
		);
$this->addPluginHelpTab('Maintenance Mode',$content,['Maintenance Mode','open']);

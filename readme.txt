=== EarthAsylum Consulting {eac}Doojigger for WordPress ===
Plugin URI: 		https://eacDoojigger.earthasylum.com/
Author: 			[EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag: 		2.4.2-RC2
Last Updated: 		15-Feb-2024
Requires at least: 	5.5.0
Tested up to: 		6.4
Requires PHP: 		7.4
Contributors:       kevinburkholder
License: 			EarthAsylum Consulting Proprietary License - {eac}PLv1
License URI:		https://eacDoojigger.earthasylum.com/end-user-license-agreement/
Tags: 				plugin development, rapid development, multi-function, security, encryption, debugging, administration, contextual-help, session management, maintenance mode, plugin framework, plugin derivative, plugin extensions, toolkit
GitHub URI:			https://earthasylum.github.io/docs.eacDoojigger/

{eac}Doojigger - A new path to rapid plugin development. A powerful, extensible, multi-function architectural framework and utility plugin for WordPress.

{eac}Doojigger streamlines the plugin development process and allows you to create professional-grade plugins in a fraction of the time. Take your WordPress development to the next level with {eac}Doojigger.

== Description ==

= Summary =

**{eac}Doojigger** by {EarthAsylum Consulting} is a multi functional and highly extensible WordPress plugin that provides existing extensions covering file access, security, debugging, encryption, session management, maintenance mode, administration tools, and more.

_{eac}Doojigger_ is not only a fully functional plugin, but more so, an architectural framework (using shared/abstract code) enabling the easy creation of full featured...

1.	[Custom *Derivative* plugins](#custom-derivative-plugins).
	+	Build your own plugin using a robust, efficient, and clean foundation.
2.	[Custom {eac}Doojigger *Extensions*](#custom-eacdoojigger-extensions).
	+	Add easy-to-code, task-oriented extensions to {eac}Doojigger or your own derivative plugins
3.	[Custom *Extension Plugins*](#custom-extension-plugins).
	+	Load your custom extensions (for {eac}Doojigger or your derivative) as their own WordPress plugins.

Rather than updating or customizing themes and functions, it is often best to isolate your custom code in a plugin or plugin extension so that code is not lost when the theme is changed or updated. Themes should only be used and customized with code pertinent to the look and feel of your site. Any code that should be retained after changing a theme belongs in a plugin or plugin extension. This keeps your code reusable and theme independent.

_{eac}Doojigger makes purpose-driven, task-oriented, theme-independent, reliable, and efficient code easy to create and maintain._


= Table of Contents =

+	[Provided With {eac}Doojigger](#provided-with-eacdoojigger)
+	[Custom Derivative Plugins](#custom-derivative-plugins)
+	[Custom {eac}Doojigger Extensions](#custom-eacdoojigger-extensions)
+	[Custom Extension Plugins](#custom-extension-plugins)
+	[Using {eac}Doojigger](#using-eacdoojigger)
+	[Automatic Updates](#automatic-updates)
+	[Contextual Help](#contextual-help)

= Provided With {eac}Doojigger =

|	Pre-Loaded Extensions				|						|
|	----------------------				|	----------------	|
|	*file system access*				| \*New\* Uses and provides easy access to the WP_Filesystem API for creating or updating files while maintaining permissions, compatibility, and security. See [About the {eac}Doojigger File System Extension](/using-doojigger/#about-the-eacdoojigger-file-system-extension) |
|	* WPMU Installer*					| \*New\* Uses the file system extension to easily install or update programs or files within the WordPress directory structure. See [Using the {eac}Doojigger Installer](/using-doojigger/#using-the-eacdoojigger-installer)
|	*security* 							| Adds a number of security options to your WordPress installation including changing the login url, setting password policies, limiting login attempts, disabling RSS/XML, block IP addresses, set global cookie flags, and more. |
|	*debugging* 						| Adds powerful debugging and detailed logging tools with controls for WordPress debugging options. |
|	*encryption* 						| Adds easy to use data encryption and decryption filters using AES (a NIST FIPS-approved cryptographic algorithm) with authentication tag. |
|	*session* 							| Manages PHP sessions using well-known session managers or through WordPress transients. |
|	*maintenance mode* 					| Enables a custom "Maintenance Mode" when you need to disable front-end access to your site(s). |
|	*admin tools* 						| Adds cache management and plugin option backup/restore, export/import. |
|	*ajax action* 						| Adds an easy to use ajax responder that can be used by any extension. |
|	*material icons* 					| Adds Google's Material Icons to WordPress. |


|	Included Extras		    			|						|
|	------------------------			|	----------------	|
|	*myAwesomePlugin* 					| Example, skeleton plugin derivative. See [Plugin Derivatives](/derivatives/) |
|	*myAwesomeExtension* 				| Example, skeleton plugin extension. See [Custom Extensions](/extensions/) |
|	*myFunctions* 						| A functional skeleton extension plugin intended to replace (or augment) custom theme `functions.php`, including custom stylesheet and javascript. |
|	*myOptionsTest* 					| A functional example plugin that produces a settings screen with all input field types. Includes example input field filters and sanitization. |
|	shared PHP traits					| Several useful, usable PHP traits such as plugin loader, [plugin updater](/automatic-updates/), [plugin help](/contextual-help/), standard (common) dashboard options, date/time methods, version compare methods, and zip archive. |
|	A debugging test api				| Extension that provides testing via url through the debugging extension (not recommended for production sites). |


|	{eac}Doojigger Utilities			|						|
|	----------------------------		|	----------------	|
|	*{eac}DoojiggerAutoloader*			| The required auto-loader to automatically load {eac}Doojigger (and derivative) classes and traits. |
|	*{eac}DoojiggerEnvironment*			| The Environment Switcher to set WP environment from the network (multi-site) settings page. |
|	*{eac}DoojiggerActionTimer*			|The timer/tracking utility to track the load/execution timing of WordPress actions and filters (as well as custom events) when WordPress loads. |


|	Available Derivative Plugins		|						|
|	----------------------------		|	----------------	|
|	[{eac}SoftwareRegistry](https://swregistry.earthasylum.com/) | A full-featured Software Registration/Licensing Server (used by {eac}Doojigger). |


|	Available Extension Plugins			|						|
|	--------------------------			|	----------------	|
|	[{eac}SimpleSMTP](/eacsimplesmtp/)	| Configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email. |
|	[{eac}SimpleAWS](/eacsimpleaws/) 	| Includes and enables use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK). |
|	[{eac}SimpleCDN](/eacsimplecdn/) 	| Enables the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience. |
|	[{eac}ObjectCache](/objectcache/)	|  A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database to cache WordPress objects. |
|	[{eac}Readme](/eacreadme/)			| Translates a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document. |
|	[{eac}MetaPixel](/eacmetapixel/)	| installs the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events. |


= Custom Derivative Plugins =

Once {eac}Doojigger is installed and registered, you, the developer, can create your own plugin using the abstract classes and traits provided.

+	First, create a simple plugin loader using your plugin class name (myAwesomePlugin.php).
	This is the primary plugin file and must contain the required WordPress headers; it will use the plugin_loader trait provided by {eac}Doojigger.
+	Second, create your actual plugin class (myAwesomePlugin.class.php) that gets loaded by your plugin loader.
	This class extends the {eac}Doojigger abstract classes (abstract_context, abstract_frontend, abstract_backend)
	which include all of the management and utility code needed for a full-featured, full-functioning plugin.
+	Third, upload and install your plugin.


Your plugin code need only focus on your particular requirements. The WordPress code and many utility functions have been taken care of for you.

>	See detailed [instructions and examples](/derivatives/) (found in the */Extras/Plugins/readme.txt* file distributed with {eac}Doojigger).


= Custom {eac}Doojigger Extensions =

An extension is a PHP program class that adds functionality to the base plugin. Extensions can be coded for specific needs and can be as simple or complex as needed.

+	First, create an extension class (myAwesomeExtension.extension.php) that extends the extension abstract class (abstract_extension).
+	Second, upload your extension to the plugin's 'Extensions' folder.

*Custom extensions may also be uploaded to your theme folder (preferable a [child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/)), in the ../eacDoojigger/Extensions folder.*

>	See detailed [instructions and examples](/extensions/) (found in the */Extras/Extensions/readme.txt* file distributed with {eac}Doojigger).


= Custom Extension Plugins =

Since uploading extensions to the plugin or theme folder risks overwriting those extensions when upgrading or re-installing the plugin or theme, it is very easy to add extensions as their own WordPress plugin. The plugin simply answers a filter from the base plugin telling it where to load additional extensions. These extensions then exist in their own plugin folder with no risk of being overwritten.


= Using {eac}Doojigger =

{eac}Doojigger provides many useful methods and hooks which can be accessed from your custom plugins or extensions, as well as from your theme functions or any code in WordPress.

>	See:
	+	>	[Using {eac}Doojigger](/using-doojigger) (found in the */Extras/UsingDoojigger/readme.txt* file) for details and examples,
	+	>	[{eac}Doojigger PHP Reference](https://dev.earthasylum.net/phpdoc/) documentation.


= Automatic Updates =

WordPress hosted plugins provide updating functionality automatically. Whenever a new version of a plugin is updated in the WordPress repository, update notifications are seen in your WordPress dashbord on the plugins page.

You can provide the same functionality with your externally or self hosted plugin with a few easy changes.

>	See [Automatic Updates](/automatic-updates/) (found in the */Extras/AutoUpdate/readme.txt* file) for more information.


= Contextual Help =

To complete your plugin and improve support, provide contextual help using the {eac}Doojigger interface to standard WordPress help functions.

Adding contextual help to your plugin and extension is easy using the methods built into {eac}Doojigger... and when using the proper filter, you can ensure that your help content only shows on your plugin page or extension tab.

>	See the [Contextual Help](/contextual-help/) page (found in the */Extras/ContextualHelp/readme.txt* file) for complete details and examples.


== Multi-Site Network ==

>	A multisite network is a collection of sites that all share the same WordPress installation core files. They can also share plugins and themes. The individual sites in the network are virtual sites in the sense that they do not have their own directories on your server, although they do have separate directories for media uploads within the shared installation, and they do have separate tables in the database.

{eac}Doojigger is well aware of multi-site/network environments where only a network administrator may install plugins and plugins may be *network-activated* (enabled for all sites) or *site-activated* (enabled for/by individual sites within the network).

{eac}Doojigger manages installation, activation, deactivation and un-installing properly based on the type of installation and activation. For example, when an {eac}Doojigger derivative plugin is *network-activated*, it is activated on all sites in the network. When un-installed, it is un-installed from all sites. When installed by the network administrator but not *network activated*, each site administrator may properly activate or de-activate the plugin.

{eac}Doojigger also manages options and transients on network installations differently than the WordPress defaults...

{eac}Doojigger makes a distinction between *network installed* (i.e. a plugin *installed* on a multisite network) and *network activated* (i.e. *activated* on all sites in a multisite network).

The WordPress `+_network_option()` (e.g. `get_network_option()`) and `+_site_option()` (e.g. `get_site_option()`) methods are essentially the same and fallback to `+_option()` methods (e.g. single-site `get_option()`) if not installed  on a multisite network. As well, `+_site_transient()` methods fallback to `+_transient()` when not on a multisite network.

WordPress does not check (nor should it) for the type of plugin *activation* (network wide vs. individual site).

{eac}Doojigger methods are different...

+	`$this->+_network_option()` (`$this->get_network_option()`) methods only work on a multi-site installation when the plugin was *network activated* and do nothing (return default value) on a single-site activation.
+	`$this->+_site_option()` methods only use network methods if the plugin was *network activated* on a multi-site installation, otherwise these methods fallback to `+_option()` (single-site) methods.
+	`$this->+_site_transient()` methods only use network methods if the plugin was *network activated* or if invoked by the *network administrator*, otherwise these methods fallback to `+_transient()` (single-site) methods.

These are important differences and help make managing options and transients more effective in a network environment.

To illustrate these differences, if we run this code:

	\add_option('my_test_option','my test');
	\add_network_option(null,'my_test_option','my network test');

	$this->add_option('my_test_option','my test');
	$this->add_network_option('my_test_option','my network test');

We get this...

| 'get' option 					| Single site installation 	| Site activated 		| Network activated |
| --- 							| ---					 	| ---					| --- |
| `get_option()`				| 'my network test'			| 'my test'				| 'my test' |
| `get_network_option()`		| 'my network test'			| 'my network test'		| 'my network test' |
| `get_site_option()`			| 'my network test'			| 'my network test'		| 'my network test' |
| `$this->get_option()`			| 'my test'					| 'my test'				| 'my test' |
| `$this->get_network_option()`	| false						| false					| 'my network test' |
| `$this->get_site_option()`	| 'my test'					| 'my test'				| 'my network test' |

Add this code:

	\add_site_option('my_test_option','my site test');
	$this->add_site_option('my_test_option','my site test');

And we get this...

| 'get' option 					| Single site installation 	| Site activated 		| Network activated |
| --- 							| ---					 	| ---					| --- |
| `get_option()`				| 'my site test'			| 'my test'				| 'my test' |
| `get_network_option()`		| 'my site test'			| 'my site test'		| 'my site test' |
| `get_site_option()`			| 'my site test'			| 'my site test'		| 'my site test' |
| `$this->get_option()`			| 'my site test'			| 'my test'				| 'my test' |
| `$this->get_network_option()`	| false						| false					| 'my site test' |
| `$this->get_site_option()`	| 'my site test'			| 'my site test'		| 'my site test' |

In short,

+	use `$this->add_option()` to add an option *only* used for an individual site.
+	use `$this->add_network_option()` to add an option *only* used when network activated on a multi-site installation.
+	use `$this->add_site_option()` to add an option used either for a single site or network-wide (all sites) when network activated.

= Network Related Methods =

| Method Name 												| Description |
| -----------												| ----------- |
| `$this->is_network_enabled()`								| Returns true if plugin is network-enabled |
| `$this->forEachNetworkSite( $callback, ...$arguments )`	| Execute $callback on each active site in a network |
| `$this->switch_to_blog( $new_blog_id )` 					| Switch the current WordPress blog |
| `$this->restore_current_blog()` 							| Restore the current blog, after calling switch_to_blog() |

\* *use `$this->is_network_enabled()` to determine if the plugin is network activated. Extensions may use `$this->is_network_enabled()` to determine if the extension is enabled at the network level or `$this->plugin->is_network_enabled()` to determine if the plugin is network activated.*

*Using `$this->switch_to_blog()` and `$this->restore_current_blog()` over the corresponding WordPress functions ensures that options are correctly saved and loaded for the switched-from/to blogs.*


== Installation ==

= Automatic Plugin Installation =

Due to the nature of this plugin, it is NOT available from the WordPress Plugin Repository and can not be installed from the WordPress Dashboard » *Plugins* » *Add New* » *Search* feature.

= Upload via WordPress Dashboard =

Installation of this plugin can be managed from the WordPress Dashboard » *Plugins* » *Add New* page. Click the [Upload Plugin] button, then select the eacDoojigger.zip file from your computer.

See [Managing Plugins -> Upload via WordPress Admin](https://wordpress.org/support/article/managing-plugins/#upload-via-wordpress-admin)

= Manual Plugin Installation =

You can install the plugin manually by extracting the eacDoojigger.zip file and uploading the 'eacDoojigger' folder to the 'wp-content/plugins' folder on your WordPress server.

See [Managing Plugins -> Manual Plugin Installation](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation-1)

= Activation =

On activation, custom tables and default settings/options are created. Be sure to visit the 'Settings' page to ensure proper configuration.

_{eac}Doojigger should be Network Activated on multi-site installations._

= Updates =

Updates are managed from the WordPress Dashboard » 'Plugins' » 'Installed Plugins' page. When a new version is available, a notice is presented under this plugin. Clicking on the 'update now' link will install the update; clicking on the 'View details' will provide more information on the update from which you can click on the 'Install Update Now' button.

When updated, any custom tables and/or option changes are applied. Be sure to visit the 'Settings' page.

= Deactivation =

On deactivation, the plugin makes no changes to the system but will not be loaded until reactivated.

= Uninstall =

When uninstalled, the plugin will delete custom tables, settings, and transient data based on the options selected in the general settings. If settings have been backed up, the backup is retained and can be restored if/when re-installed. Tables are not backed up.


== FAQ ==

= Is {eac}Doojigger stable and reliable? =

__Version 2__ has been meticulously updated to provide not only new features and efficiencies, but many other improvements, including stability and reliability. The code base of {eac}Doojigger has been in proprietary use (and in development) over years and on several websites. However, there is a nearly infinte number of website configurations and uses that can't possibly be tested. If you run into any issues, problems, bugs or simply change requests, I'd be more than happy to address them and to work with you.

= Where can I find more information about creating a derivative plugin? =

Please see the [readme.txt](/derivatives/) file in the Extras/Plugins folder.

= Where can I find more information about creating a custom extension? =

Please see the [readme.txt](/extensions/) file in the Extras/Extensions folder.

= How do I define and use options in my plugin or extension? =

Please see the [readme.txt](/options/) file in the Extras/OptionMetaData folder.

= How do I provide automatic updates for my plugin? =

Please see the [readme.txt](/automatic-updates/) file in the Extras/AutoUpdate folder.

= How do I provide contextual help for my plugin or extension? =

Please see the [readme.txt](/contextual-help/) file in the Extras/ContextualHelp folder.

= Who is EarthAsylum Consulting? =

{EarthAsylum Consulting} is a one-person consulting agency in business since 2005.
I have some 30 years experience in technology and software development for a disperse range of businesses.

Currently, and for the last decade or more, my focus has been on internet-based business software & technology management.

In developing {eac}Doojigger, and other plugins based on it, I hope to find a small revenue stream to help keep me going.

To that end, your support is greatly appreciated.
It will enable me to continue developing quality software and provide support to current and future clients (and to enjoy a cup of coffee occasionally).

Learn more here...
+	[EarthAsylum Consulting](https://www.earthasylum.com)
+	[Kevin Burkholder](https://www.kevinBurkholder.com)

Thank you!
_Kevin Burkholder_


== Screenshots ==

1. General settings
![General](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-1.png)

2. General settings - Maintenance Mode
![General](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-2.png)

3. General settings - Material Icons
![General](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-3.png)

4. General settings - Session Extension
![General](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-4.png)

5. Tools settings
![Tools](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-5.png)

6. Debugging settings
![Debugging](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-6.png)

7. Security settings (1)
![Security](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-7.png)

8. Security settings (2)
![Security](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-8.png)

9. My Awesome Plugin with My Awesome Extension
![myAwesomePlugin](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-9.png)

10. My Awesome Plugin Contextual Help
![ContextualHelp](https://d2xk802d4616wu.cloudfront.net/eacDoojigger/assets/screenshot-10.png)


== Other Notes ==

= Additional Information =

{eac}Doojigger should be Network Activated on multi-site installations. Individual extensions and options may be configured on each site.

Some extension may use [wp-config-transformer](https://github.com/wp-cli/wp-config-transformer/contributors) to update wp-config. Copyright (C) 2011-2018 WP-CLI Development Group .


= See Also =

+	[{eac}Doojigger Derivatives](https://eacDoojigger.earthasylum.com/derivatives/)
+	[{eac}Doojigger Extensions](https://eacDoojigger.earthasylum.com/extensions/)
+	[{eac}Doojigger Options & Settings](https://eacDoojigger.earthasylum.com/options/)
+	[{eac}Doojigger Contextual Help](https://eacDoojigger.earthasylum.com/contextual-help/)
+	[{eac}Doojigger Automatic Updates](https://eacDoojigger.earthasylum.com/automatic-updates/)

+	[{eac}SoftwareRegistry](https://swregistry.earthasylum.com/)
A full-featured Software Registration/Licensing Server built on {eac}Doojigger.

+	[{eac}SimpleSMTP](https://eacDoojigger.earthasylum.com/eacsimplesmtp/)
An {eac}Doojigger extension to configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email.

+	[{eac}SimpleAWS](https://eacDoojigger.earthasylum.com/eacsimpleaws/)
An {eac}Doojigger extension to include and enable use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK).

+	[{eac}SimpleCDN](https://eacDoojigger.earthasylum.com/eacsimplecdn/)
An {eac}Doojigger extension to enable the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience.

+	[{eac}ObjectCache](https://eacDoojigger.earthasylum.com/objectcache/)
A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database to cache WordPress objects.

+	[{eac}Readme](https://eacDoojigger.earthasylum.com/eacreadme/)
An {eac}Doojigger extension to translate a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document.

+	[{eac}MetaPixel](https://eacDoojigger.earthasylum.com/eacmetapixel/)
An {eac}Doojigger extension to install the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events.


== Copyright ==

= Copyright © 2019-2024, *EarthAsylum Consulting*, All rights reserved. =

__This is proprietary, copyrighted software.__

+	Title to the Software will remain the exclusive intellectual property of *EarthAsylum Consulting*.

+	You, the customer, are granted a non-exclusive, non-transferable, license to access, install, and use
this software in accordance with the license level purchased.

+	You are not permitted to share, distribute, or make available this software to any third-party.

See: [EarthAsylum Consulting EULA](https://eacDoojigger.earthasylum.com/end-user-license-agreement/)


== Changelog ==

= Version 2.4.2 – February 15, 2024 =

+	Ignore 'onlyPHP' flag when loading to prevent critical errors with 404 redirects.
+	Fixed dynamic properties (no longer supported in PHP 8.2+) in anonymous class in abstract_core.
+	Fixed error in debugging_extension::capture_deprecated()
+	Now hosted on github.

= Version 2.4.1 – December 27, 2023 =

+	Fixed filesystem multisite option.
+	Fixed filesystem call (arguments) to encryption::encode().
+	For single-site installs, removed 'Environment Switcher' mu-plugin in favor of tools option that updates wp-config.php.
	+	Much simpler, much less code - but does not set 'robots_txt' filter headers for non-production sites.
+	Updated wp-config-transformer to v1.3.5 (10-Nov-2023).
	+	Note: something like this:
		`define( 'WP_HOME', 'https://'.$_SERVER['HTTP_HOST'] );`
		can break the string matching algorithm, instead use:
		`define( 'WP_HOME', "https://{$_SERVER['HTTP_HOST']}" );`
+	Fixed potential issue when sanitizing textarea field converted to array.
+	Fixed warning when an empty hidden array option is output on admin screen.
+	Introducing new isAdvancedMode() method to aid in limiting admin complexity.
+	Changed code according to WordPress 'Plugin Check'.
	+	e.g. use wp_json_encode() rather than json_encode().
+	Deprecated getMySqlVersion(), use $this->wpdb->db_server_info().
+	Only show cache flush transient checkbox when not using an object cache (wp_using_ext_object_cache).
+	Confirm cache flush by (known) cache name(s).
+	Updated page_reload() using wp_safe_redirect() or location.replace.

= Version 2.4.0 – November 5, 2023 =

+	Support WordPress 6.4+
	+	Use wp_set_options_autoload on activate/deactivate.
	+ 	Use wp_get_admin_notice for admin notices and settings errors.
+	New filesystem extension - Automated/simplified access to WP_filesystem using ftp/ssh.
	+	Enhances security by preserving owner/group permissions on files.
	+	New eacDoojigger_ftp_credentials utility includes get/set filters for WP 'ftp_credentials'.
	+	Updated auto-loader to include eacDoojigger_ftp_credentials to support core updates.
	+	Updated insert_with_markers to use WP_filesystem.
	+	New admin methods htaccess_handle(), wpconfig_handle(), userini_handle()
		+	Using WP_Filesystem for config file editing.
+	New installer extension - Install/uninstall files to "must use" plugins folder (or other) using WP_filesystem.
	+	Use installer when installing utilities (auto-loader, environment switcher, plugin timer).
+	New helper 'wpconfig_editor' using wp-config-transformer & WP_filesystem when updating wp-config.php.
	+	[wp-config-transformer](https://github.com/wp-cli/wp-config-transformer) (C) 2011-2018 WP-CLI Development Group
+	Added new menu bar option for 'Settings Admin Menu'.
+	Fixed admin menu items using transient to remember tab names before they are loaded.
+	Fixed plugin options save, now occurs when an option changes rather than at shutdown.
+	Added optional checkbox to cache flush tool to (optionally) remove transients.
+	Moved transient flush to abstract_backend (only admin).
+	In attempt to lessen front-end load...
	+	Moved eacDoojigger.plugin.php admin methods to external admin-only trait.
	+	Externalized (to included files) contextual help and admin options in most extensions.
+	Reworked settings_error api
	+	No longer uses WP settings api.
	+	Replace 'admin_notices' action on settings page with 'all_admin_notices' on all admin pages.
	+	Allow adding notices at any time.
	+	Output notices in footer using internal settings api and transient to survive page reload.
	+	Using add_option_error(), add_option_warning(), add_option_info(), add_option_success() methods for options/settings or add_admin_notice() for general notices.
+	New print_admin_notice() to output notice immediately (bypass api).
+	Deprecated _COOKIE(),_GET(),_POST(),_REQUEST(),_SERVER() methods.
	+	Use varCookie(),varGet(),varPost(),varRequest(),varServer()
+	Debugging extension
	+	Use WP_filesystem for folder/file creation (but not log file writes).
	+	Added WP_DEBUG options using wpconfig_editor to update wp-config.php.
	+	Added WP heartbeat logging.
	+	Use range inputs for number fields.
	+	Set log folder to a) WP_DEBUG_LOG folder, b) existing folder in uploads, c) default to wp_content
+	Security extension
	+	Use htaccess_handle(), wpconfig_handle(), userini_handle().
	+	No longer set cookie flags in .htaccess (may cause conflicting flags).
	+	Added option to block pingbacks.
	+	Enhanced WP heartbeat limitation.
+	Encryption Extension
	+	New 'site_encrypt_string()' and 'site_decrypt_string()' using network admin key(s).
	+	Static methods for un-instantiated access.
+	Updated getSemanticVersion(), is now stringable.
+	Removed fatal email notification in autoloader in lue of WordPress recovery mode email.
	+	stdOptions 'emailFatalNotice' may be used to set email recipient address.
+	Fixed potential loss of option update if/when swith_to_blog() is called externally.

= Version 2.3.3 – September 18, 2023 =

+	Several minor fixes/updates for PHP 8.0+
	+	Fixed use of static function variables.
	+	Fixed optional argument before required argument(s).
+	Fixed potential warning with invalid api response in plugin_update.trait.

= Version 2.3.2 – August 30, 2023 =

+	Support for WordPress 6.3.
+	Support for WP_DEVELOPMENT_MODE in Environment Switcher.
+	Changed account lock input in security extension.
+	Fixed security IP blocking when no referrer.

= Version 2.3.1 – July 26, 2023 =

+	Add filters for registry license values in plugin file (unused).

= Version 2.3.0 – June 14, 2023 =

+	Moved html_input_* method to html_input_fields trait.
+	Further changed settings sections (header, fieldset, enabled) with toggle option.
+	Added 'input-*' (type) class to settings field input tags in backend.
+	Updated JavaScript for '.input-readonly'.
+	Updated encryption extension, V3 now uses aes-256-gcm with authentication tag.
+	Updated plugin traits intended for backend to only load code when is_admin().
	+	 Deprecated plugin_loader_environment trait for plugin_environment trait.
+	Cleaned up option names in various extensions.
+	Fixed isRegistryValue in main class.
+	Added "Registration" alias to registration extension.
+	Further improved backend options_settings_page kses/escaping meta fields.
	+	default, before, after, info allow nearly anything.
	+	title, label are wp_kses_post'd.
	+	other attributes are esc_attr'd.
+	New LogLevel.class and updated logging and debugging.extension.
+	Added optional ALIAS constant to extensions.
	+	Extension 'my_awesome_cart_extension' can be referenced as $this->cart.
+	Added getExtension() method, alias to isExtension(), returns extension object.
+	Added 'optionExport_action' to standard_options trait to add required action for optionExport.
+	Improved input type=file styling.
+	Added option 'validate' callback, unlike 'sanitize', may change value without error notice.
	+	Added public html_input_validate.
	+	'validate' and 'filter' occur after 'sanitize' and 'sanitize_option' filter.
+	Added '{classname}_options_settings_form' action to admin options settings.
	+	Before form output, after $_POST fields processed (if post).
+	add_option_error now uses wp_kses_post & nl2br.
+	Fixed style declaration in security extension.
+	Fixed settings_fields group (tab) name.

= Version 2.2.0 – May 11, 2023 =

+	Fixed tab name matching on admin screen (isSettingsPage()).
+	Added required_extensions filter when loading extensions.
+	Fixed fatal() error, no longer triggers error, uses wp_die() only.
+	Added isNewVisitor() method, is_new_visitor and visitor_cookie_name filters.
+	Changes to session extension (expiration input, values from 0.5 to 72).
+	Externalized admin stylesheet and javascript.
+	Correctly load inline css with wp_add_inline_style()
+	Added 'help' field type for settings screens to add help for the screen-section.
+	Reworked admin options screen with section, header, and fieldset tags.
+	Standardize admin class names (tabs/sections).
+	Use $this->defaultTabs to set/pre-populate admin screen tab order.
+	Added 'html' input field to invoke wp_editor on textarea input field.
+	Public utility methods for forms/fields - html_input_section html_input_block  html_input_field html_input_sanitize.
+	Added product registration to myAwesomePlugin using swRegistrationUI trait and extension built with {eac}SoftwareRegisty SDK.
+	Fixed registration api error messaging.
+	Fixed admin display margins outside settings pages.
+	Improved security on admin settings page(s) with nonce validation.
+	Properly set $this->is_admin() & $this->is_network_admin() for admin-post.php request.
+	Support 'help' option on display fields (normally not included in plugin help).
+	Added 'file' input field in abstract_backend with uploads handled by wp_handle_upload().
+	Added export/import settings to admin_tools extension.
+	Fixed security extension error on login_redirect when reseting login attempts.
+	Fixed potential critical error when refreshing updated registration triggered on front-end.

= Version 2.1.0 – February 18, 2023 =

+	ajaxAction now returns the jqXHR object (var jqxhr = atpCustom.AjaxRequest(...)).
+	Fixed bug when extension changed with $this->enable_option = false; defaulting to disabled.
+	Proper registration of admin stylesheet and javascript.
+	New html_input_field() method to add input field in any admin screen with html_input_field_{fieldName} filter.
+	Added {plugin}_plugin_update_parameters filter to filter parameters in plugin_update trait.
+	Added 'plugin_options' array in plugin_update trait to allow additional query parameters to plugin update uri.
+	Changed  extension __call() method  to allow access to any public method in parent plugin.
+	Added extension \_\_get() method to call parent plugin \_\_get().
+	Added \_\_get() method to allow direct access to extension methods - $this->extension->method();
+	Added backtrace of WordPress deprecated and doing_it_wrong errors to debugging extension.
+	Fixed custom queries - reset post data after query
+	Added backtrace level to PHP error debugging option (previously set to 3)

= Version 2.0.0 – November 20, 2022 =

+	Fixed excessive DB reads on unreserved options.
+	Fixed maintenance mode from network admin by clearing caches when enabled.
+	Fixed maintenance mode error when theme header loads WooCommerce cart via 'wp_resource_hints'.
+	Enhanced, simplified, and made more efficient the automatic plugin update methods and traits.
+	Fixed de-spooling of debug log for derivatives loaded before {eac}Doojigger.
+	Added debugging log on help tab (if enabled).
+	Moved backend code related to standard options to standard_options trait.
+	Enhanced Environment Switcher for contextual help and admin current-screen check.
+	Updated extensions using new plugin features.
+	Internal filter parser (for PHP filter_var) now allows additional, optional, arguments passed to callback.
+	Renamed & updated several traits (zip_archive.trait, standard_options.trait, version_compare.trait).
	+	Requires deactivation of derivatives before updating.
+	New help interface via plugin_help trait [addPluginHelpTab(), addPluginSidebarText(), addPluginSidebarLink()]
+	New 'options_settings_help' action for adding contextual help.
+	abstract_backend automatically adds field-level help using option['help'] or option['title']+option['info'].
+	Added options_form_h1_html, options_form_h2_html, and options_form_system_info filters to admin page.
+	More fluid admin screen, settings_info & sticky settings_banner in settings_header (if set by options_form_h1_html).
+	Renamed several admin screen methods.
+	Improved forEachNetworkSite() method.
+	New switch_to_blog(), restore_current_blog(), before_switch_blog(), and after_switch_blog() methods.
+	New front-end get_the_id(), get_the_post(), get_the_field() methods.
+	Removed \_\_get(), \_\_set(), \_\_isset() deprecated methods.
+	Added did_filter() for WP 6.1 and fixed did_action().
+	Updated registration code and SoftwareRegistry distribution kit.
+	Added option['sanitize'] to override internal option sanitization.
+	Updated and formalized option['filter'] to pass option field parameters to callback.
+	New add_admin_notice() method.
+	Added add_option_error(), add_option_warning(), add_option_info(), add_option_success(), shortcuts to add_settings_error() using transient to survive page reload.
+	Added support for 'add_settings_error()' when validating options, and settings_errors() to display error notices.
+	Improved uninstaller trait.
+	Options/settings now stored in options table in a single record.
	+	Option names case-insensitive (lower-cased).
	+	Individual option records take priority and will be converted.
	+	new  'isReservedOption(...)' to mark an option as 'reserved' to retain individual record.
+	Enhanced sanitize() and super-globals filtering with PHP filter and filter parameter parsing.
+	Removed filter_var_callback, uses sanitize_textarea_field directly.
+	Front-end filter and shortcode supports returning object properties.
+	Added check for required parent method calls in abstract_core.
+	Added 'options_settings_page' action for just-in-time option registration.
+	Added 'noSubmit' to standardOptions trait using hidden '_btnSubmitOptions'.
+	'_btnSubmitOptions' option overrides default submit button on settings pages.

= Version 1.2.2 – October 20, 2022 =

+	Backported get_the_id(), get_the_post(), get_the_field(), (to v1.2.0)

= Version 1.2.1 – October 1, 2022 =

+	abstract_extension remembers first registered tab.
+	Built-in extension optimization.
+	Fix expiration setting in session extension.
+	Change (restrict) default file permissions in debugging extension.
+	Hide parent elements of hidden fields in abstract_backend.
+	Added getInstance() to plugin_loader trait.

= Version 1.2.0 – September 28, 2022 =

+	General code restructuring and optimization to abstract_backend.
+	Added 'options_form_post' action when admin form is posted.
+	Added 'before', 'after' attributes to input fields.
+	Added rename login option to security extension.
+	Enhanced security_extension.
+	Fixed CodeMirror loading/formatting.
+	New getFormattedDateTime(), isFormattedDateTime() in datetime trait.
+	Enhanced sanitization and escaping, improved security on admin settings page.
+	Improved plugin_loader_environment and isversion traits.
	+	Added network activation check (require or forbid).
+	Updated plugin_update.trait for WP 5.8+ (using update-plugins-{$hostname} filter).
+	Added upgrade_notice support to abstract_context_wp, abstract_backend, and abstract_extension.
+	Moved plugin upgrade notice to (new) plugin_update_notice.trait.php.
+	Added 'registry_title' to swRegistrationUI for {eac}SoftwareRegistry.
+	Updated Extras and readme docs, new myOptionsTest tests all input types.
+	Added support for extension plugin auto-updates in abstract_extension class.
+	Auto-load extensions from theme directory /eacDoojigger/Extensions.
+	Standardized settings link url with $this->getSettingsLink().
+	Added $this->getSettingsLink(), $this->getDocumentationLink(), $this->getSupportLink().
+	Fixed admin css class name for extensions with unexpected registered name.

= Version 1.1.4 – August 5, 2022 =

+	Allow extensions loaded from plugins or themes directories.
+	Updated documentation and directory structure.

= Version 1.1.3 – July 18, 2022 =

+	Removed code injection extension.

= Version 1.1.2 – July 8, 2022 =

+	Improved sanitization of option input fields.
+	Added $this->_COOKIE() for cookie filtering.
+	Replaced default (depreciated) FILTER_SANITIZE_STRING with WP sanitize_textarea_field() callback.
+	Removed FILTER_PATTERN and FILTER_REPLACE (no longer used).

= Version 1.1.1 – June 17, 2022 =

+	Check headers sent before setting cookie.

= Version 1.1.0 – May 27, 2022 =

+	Completed name change from eacBasePlugin to eacDoojigger.
+	Fixed debug log purging (use modification time).
+	Add ability to override prefix (classname) for options/tables/transients when calling directly.
+	Add ability to override option prefix in getSavedPluginOptions() and getSavedNetworkOptions()

= Version 1.0.9 – May 23, 2022 =

+	Fix bug in update trait.
+	Fixed network enabled check in abstract.extension.
+	Don't register extensions if not in admin settings page.

= Version 1.0.8 – May 17, 2022 =

+	Updated documentation (phpdoc).
+	Changed log file location to wp-content (for proper file permissions).
+	Minor change to debugging log format on request start/end lines.
+	Fixed fatal email notification reset.
+	Added 'safeEcho' to prevent notices being output when running from ajax request.
	Prevents interference with multiple/auto installs.
+	Removed derivative tracking.

= Version 1.0.7 – May 9, 2022 =

+	Fixed issue with isPHP() function with '.' in request uri.

= Version 1.0.6 – April 28, 2022 =

+	Updated external requirements.
+	Updated Software Registry SDK.
+	Fixed plugin_loader_environment notice
+	Prevent auto loader and environment switcher update for each site in multi-site.

= Version 1.0.5 – April 21, 2022 =

+	New 'Material Icons' extension.
+	Several minor "notice" fixes.
+	Fix environment error in WP pre-5.5 versions.
+	Updated for WordPress 5.9.3.
+	Fixed ajaxaction error when no parameters passed.

= Version 1.0.4 – March 13, 2022 =

+	Fix debugging log across derivatives (not load-order dependent)
+	Fix proper capitalization of the word 'WordPress' (as opposed to 'Wordpress').
+	Added {eac}DoojiggerEnvironment utility to set WP_ENVIRONMENT_TYPE from settings page.
+	Added option to install/uninstall {eac}DoojiggerEnvironment utility.
+	Changed siteEnvironment standard option and core code to recognize {eac}DoojiggerEnvironment utility.
+	Cosmetic changes to plugin settings page.
+	Updated for WordPress 5.9.2.

= Version 1.0.3 – March 3, 2022 =

+	Added upgrade notice to admin plugins screen.
+	Added option encryption/decryption.
+	Updates and fixes for WordPress 5.9.1 compatibility.
+	Updated maintenance mode extension.
+	Fixed registration refresh scheduling.
+	Support Admin Color Schemes.
+	Fixed is_network_admin_request() in abstract_context.
+	Registration/scheduling only on main site for network installations.
+	Fixed activation/deactivation, install/upgrade for network installations.
	+	Lessen reliance on activation/deactivation.
	+	Activation/Deactivation run across all active sites in network.
	+	Install/Upgrade run across all active sites in network.
+	Updated internal and external documentation.
+	Fixed bug that allowed disabled extension to be enabled on admin pages.
+	Fixed several PHP Notices.
+	Other miscellaneous fixes and updates.


== Upgrade Notice ==

= 2.0.0 =

To upgrade to version 2.0 of {eac}Doojigger : 1. Disable all derivative plugins; 2. Upgrade {eac}Doojigger; 3. Upgrade all derivative plugins; 4. Re-enable all derivative plugins.


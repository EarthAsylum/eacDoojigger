=== EarthAsylum Consulting {eac}Doojigger for WordPress ===
Plugin URI:             https://eacDoojigger.earthasylum.com/
Author:                 [EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:             2.6.0-RC3+May18
Last Updated:           18-May-2024
Requires at least:      5.8
Tested up to:           6.5
Requires PHP:           7.4
Contributors:           earthasylum@github,kevinburkholder@wordpress
License:                EarthAsylum Consulting Proprietary License - {eac}PLv1
License URI:            https://eacDoojigger.earthasylum.com/end-user-license-agreement/
Tags:                   plugin development, rapid development, multi-function, security, encryption, debugging, administration, contextual-help, session management, maintenance mode, plugin framework, plugin derivative, plugin extensions, toolkit
GitHub URI:             https://github.com/EarthAsylum/docs.eacDoojigger/wiki

{eac}Doojigger - A new path to rapid plugin development. A powerful, extensible, multi-function architectural framework and utility plugin for WordPress. {eac}Doojigger streamlines the plugin development process and allows you to create professional-grade plugins in a fraction of the time.

== Description ==

= Summary =

**{eac}Doojigger** by {EarthAsylum Consulting} is a multi functional and highly extensible WordPress plugin that provides existing extensions covering file access, security, debugging, encryption, session management, maintenance mode, administration tools, and more.

_{eac}Doojigger_ is not only a fully functional plugin, but more so, an architectural development platform (using shared/abstract code) enabling the effortless creation of full featured...

1.  [Custom *Derivative* plugins](#custom-derivative-plugins).
    +   Create your own plugin using a robust, efficient, and clean foundation.
2.  [Custom {eac}Doojigger *Extensions*](#custom-eacdoojigger-extensions).
    +   Add easy-to-code, task-oriented extensions to your plugin or to {eac}Doojigger.
3.  [Custom *Extension Plugins*](#custom-extension-plugins).
    +   Load your custom extensions as their own WordPress plugins.

Rather than updating or customizing themes and functions, it is often best to isolate your custom code in a plugin or plugin extension so that code is not lost when the theme is changed or updated. Themes should only be used and customized with code pertinent to the look and feel of your site. Any code that should be retained after changing a theme belongs in a plugin or plugin extension. This keeps your code reusable and theme independent.

_{eac}Doojigger makes purpose-driven, task-oriented, theme-independent, reliable, and efficient code easy to create and maintain._


= Table of Contents =

+   [Provided With {eac}Doojigger](#provided-with-eacdoojigger)
+   [Custom Derivative Plugins](#custom-derivative-plugins)
+   [Custom {eac}Doojigger Extensions](#custom-eacdoojigger-extensions)
+   [Custom Extension Plugins](#custom-extension-plugins)
+   [Using {eac}Doojigger](#using-eacdoojigger)
+   [Automatic Updates](#automatic-updates)
+   [Contextual Help](#contextual-help)
+   [Advanced Mode](#advanced-mode)

= Provided With {eac}Doojigger =

|   Included Extensions & Traits        |                       |
|   ---------------------------------   |   ----------------    |
|   *file system access*                | Uses and provides easy access to the WP_Filesystem API for creating or updating files while maintaining permissions, compatibility, and security. |
|   *WPMU Installer*                    | Uses the file system extension to easily install or update programs or files within the WordPress directory structure. |
|   *security*                          | Adds a number of security options to your WordPress installation including changing the login url, setting password policies, limiting login attempts, disabling RSS/XML, block IP addresses, set global cookie flags, and more. |
|   *debugging*                         | Adds powerful debugging and detailed logging tools with controls for WordPress debugging options. |
|   *encryption*                        | Adds easy to use data encryption and decryption filters using AES (a NIST FIPS-approved cryptographic algorithm) with authentication tag. |
|   *session*                           | Manages PHP sessions using well-known session managers or through WordPress transients. |
|   *maintenance mode*                  | Enables a custom "Maintenance Mode" when you need to disable front-end access to your site(s). |
|   *admin tools*                       | Adds cache management and plugin option backup/restore, export/import. |
|   *ajax action*                       | Adds an easy to use ajax responder (accessable from any extension) as well as a purely asynchronous device fingerprint action. |
|   *material icons*                    | Adds Google's Material Icons to WordPress. |
|   shared PHP traits                   | Several useful, usable PHP traits such as plugin loader, plugin updater, plugin help, standard (common) dashboard options, date/time methods, version compare methods, and zip archive. |


|   Extras & Examples                   |   { [explore on github](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras) } { [documentation wiki](https://github.com/EarthAsylum/docs.eacDoojigger/wiki/) } { [download zip](https://swregistry.earthasylum.com/software-updates/eacdoojigger-extras.zip) } |
|   ---------------------------------   |   ----------------    |
|   *myAwesomePlugin*                   | Example, skeleton plugin derivative. *Start here with your first plugin.* |
|   *myAwesomeExtension*                | Example, skeleton plugin extension. *Start here with your first extension.* |
|   *myFunctions*                       | A functional skeleton extension plugin intended to replace (or augment) custom theme `functions.php`, including custom stylesheet and javascript. |
|   *myOptionsTest*                     | A functional example plugin that produces a settings screen with all input field types. Includes example input field filters and sanitization. |
|   A debugging test api                | Extension that provides testing via url through the debugging extension (not intended for production sites). |


|   {eac}Doojigger Utilities            |                       |
|   ---------------------------------   |   ----------------    |
|   *{eac}DoojiggerAutoloader*          | The required auto-loader to automatically load {eac}Doojigger (and derivative) classes and traits. |
|   *{eac}DoojiggerEnvironment*         | The Environment Switcher to set WP environment from the network (multi-site) settings page. |
|   *{eac}DoojiggerActionTimer*         |The timer/tracking utility to track the load/execution timing of WordPress actions and filters (as well as custom events) when WordPress loads. |


|   Available Derivative Plugins        |                       |
|   ---------------------------------   |   ----------------    |
|   [{eac}SoftwareRegistry](https://swregistry.earthasylum.com/) | A full-featured Software Registration/Licensing Server (used by {eac}Doojigger). |


|   Available Extension Plugins         |                       |
|   ---------------------------------   |   ----------------    |
|   [{eac}SimpleSMTP](https://eacdoojigger.earthasylum.com/eacsimplesmtp/)  | Configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email. |
|   [{eac}SimpleAWS](https://eacdoojigger.earthasylum.com/eacsimpleaws/)    | Includes and enables use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK). |
|   [{eac}SimpleCDN](https://eacdoojigger.earthasylum.com/eacsimplecdn/)    | Enables the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience. |
|   [{eac}ObjectCache](https://eacdoojigger.earthasylum.com/objectcache/)   |  A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database to cache WordPress objects. |
|   [{eac}Readme](https://eacdoojigger.earthasylum.com/eacreadme/)          | Translates a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document. |
|   [{eac}MetaPixel](https://eacdoojigger.earthasylum.com/eacmetapixel/)    | installs the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events. |


= Custom Derivative Plugins =

Once {eac}Doojigger is installed and registered, you, the developer, can create your own plugin using the abstract classes and traits provided.

+   First, create a simple plugin loader using your plugin class name (myAwesomePlugin.php).
    This is the primary plugin file and must contain the required WordPress headers; it will use the plugin_loader trait provided by {eac}Doojigger.
+   Second, create your actual plugin class (myAwesomePlugin.class.php) that gets loaded by your plugin loader.
    This class extends the {eac}Doojigger abstract classes (abstract_context, abstract_frontend, abstract_backend)
    which include all of the management and utility code needed for a full-featured, full-functioning plugin.
+   Third, upload and install your plugin.


Your plugin code need only focus on your particular requirements. The WordPress code and many utility functions have been taken care of for you.

>   See detailed [instructions and examples](https://eacdoojigger.earthasylum.com/derivatives/) (found in the *[Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)/Plugins/* folder).


= Custom {eac}Doojigger Extensions =

An extension is a PHP program class that adds functionality to the base plugin. Extensions can be coded for specific needs and can be as simple or complex as needed.

+   First, create an extension class (myAwesomeExtension.extension.php) that extends the extension abstract class (abstract_extension).
+   Second, upload your extension to the plugin's 'Extensions' folder.

*Custom extensions may also be uploaded to your theme folder (preferable a [child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/)), in the ../eacDoojigger/Extensions folder.*

>   See detailed [instructions and examples](https://eacdoojigger.earthasylum.com/extensions/) (found in the *[Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)/Extensions/* folder).


= Custom Extension Plugins =

Since uploading extensions to the plugin or theme folder risks overwriting those extensions when upgrading or re-installing the plugin or theme, it is very easy to add extensions as their own WordPress plugin. The plugin simply answers a filter from the base plugin telling it where to load additional extensions. These extensions then exist in their own plugin folder with no risk of being overwritten.


= Using {eac}Doojigger =

{eac}Doojigger provides many useful methods and hooks which can be accessed from your custom plugins or extensions, as well as from your theme functions or any code in WordPress.

>   See [Using {eac}Doojigger](https://eacdoojigger.earthasylum.com/using-doojigger) (found in the *[Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)/UsingDoojigger/* folder) for details and examples,
    +   [{eac}Doojigger PHP Reference](https://earthasylum.github.io/docs.eacDoojigger/) documentation.


= Automatic Updates =

WordPress hosted plugins provide updating functionality automatically. Whenever a new version of a plugin is updated in the WordPress repository, update notifications are seen in your WordPress dashbord on the plugins page.

You can provide the same functionality with your externally or self hosted plugin with a few easy changes.

>   See [Automatic Updates](https://eacdoojigger.earthasylum.com/automatic-updates/) (found in the *[Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)/AutoUpdate/* folder) for more information.


= Contextual Help =

To complete your plugin and improve support, provide contextual help using the {eac}Doojigger interface to standard WordPress help functions.

Adding contextual help to your plugin and extension is easy using the methods built into {eac}Doojigger... and when using the proper filter, you can ensure that your help content only shows on your plugin page or extension tab.

>   See the [Contextual Help](https://eacdoojigger.earthasylum.com/contextual-help/) page (found in the *[Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)/ContextualHelp/* folder) for complete details and examples.


= Advanced Mode =

Advanced Mode gives developers a method to implement options or features based on an advanced mode setting (or combination of settings). {eac}Doojigger uses a menu selection and license level to enable advanced mode, but custom derivatives may use other methods to implement advanced mode.
>   See [Implementing and Using Advanced Mode](https://eacdoojigger.earthasylum.com/how-to/#implementing-and-using-advanced-mode) for details.


== Multi-Site Network ==

>   A multisite network is a collection of sites that all share the same WordPress installation core files. They can also share plugins and themes. The individual sites in the network are virtual sites in the sense that they do not have their own directories on your server, although they do have separate directories for media uploads within the shared installation, and they do have separate tables in the database.

{eac}Doojigger is well aware of multi-site/network environments where only a network administrator may install plugins and plugins may be *network-activated* (enabled for all sites) or *site-activated* (enabled for/by individual sites within the network).

{eac}Doojigger manages installation, activation, deactivation and un-installing properly based on the type of installation and activation. For example, when an {eac}Doojigger derivative plugin is *network-activated*, it is activated on all sites in the network. When un-installed, it is un-installed from all sites. When installed by the network administrator but not *network activated*, each site administrator may properly activate or de-activate the plugin.

{eac}Doojigger also manages options and transients on network installations differently than the WordPress defaults...

{eac}Doojigger makes a distinction between *network installed* (i.e. a plugin *installed* on a multisite network) and *network activated* (i.e. *activated* on all sites in a multisite network).

The WordPress `+_network_option()` (e.g. `get_network_option()`) and `+_site_option()` (e.g. `get_site_option()`) methods are essentially the same and fallback to `+_option()` methods (e.g. single-site `get_option()`) if not installed  on a multisite network. As well, `+_site_transient()` methods fallback to `+_transient()` when not on a multisite network.

WordPress does not check (nor should it) for the type of plugin *activation* (network wide vs. individual site).

{eac}Doojigger methods are different...

+   `$this->+_network_option()` (`$this->get_network_option()`) methods only work on a multi-site installation when the plugin was *network activated* and do nothing (return default value) on a single-site activation.
+   `$this->+_site_option()` methods only use network methods if the plugin was *network activated* on a multi-site installation, otherwise these methods fallback to `+_option()` (single-site) methods.
+   `$this->+_site_transient()` methods only use network methods if the plugin was *network activated* or if invoked by the *network administrator*, otherwise these methods fallback to `+_transient()` (single-site) methods.

These are important differences and help make managing options and transients more effective in a network environment.

To illustrate these differences, if we run this code:

    \add_option('my_test_option','my test');
    \add_network_option(null,'my_test_option','my network test');

    $this->add_option('my_test_option','my test');
    $this->add_network_option('my_test_option','my network test');

We get this...

| 'get' option                  | Single site installation  | Site activated        | Network activated |
| ---                           | ---                       | ---                   | --- |
| `get_option()`                | 'my network test'         | 'my test'             | 'my test' |
| `get_network_option()`        | 'my network test'         | 'my network test'     | 'my network test' |
| `get_site_option()`           | 'my network test'         | 'my network test'     | 'my network test' |
| `$this->get_option()`         | 'my test'                 | 'my test'             | 'my test' |
| `$this->get_network_option()` | false                     | false                 | 'my network test' |
| `$this->get_site_option()`    | 'my test'                 | 'my test'             | 'my network test' |

Add this code:

    \add_site_option('my_test_option','my site test');
    $this->add_site_option('my_test_option','my site test');

And we get this...

| 'get' option                  | Single site installation  | Site activated        | Network activated |
| ---                           | ---                       | ---                   | --- |
| `get_option()`                | 'my site test'            | 'my test'             | 'my test' |
| `get_network_option()`        | 'my site test'            | 'my site test'        | 'my site test' |
| `get_site_option()`           | 'my site test'            | 'my site test'        | 'my site test' |
| `$this->get_option()`         | 'my site test'            | 'my test'             | 'my test' |
| `$this->get_network_option()` | false                     | false                 | 'my site test' |
| `$this->get_site_option()`    | 'my site test'            | 'my site test'        | 'my site test' |

In short,

+   use `$this->add_option()` to add an option *only* used for an individual site.
+   use `$this->add_network_option()` to add an option *only* used when network activated on a multi-site installation.
+   use `$this->add_site_option()` to add an option used either for a single site or network-wide (all sites) when network activated.

= Network Related Methods =

| Method Name                                               | Description |
| -----------                                               | ----------- |
| `$this->is_network_enabled()`                             | Returns true if plugin is network-enabled |
| `$this->forEachNetworkSite( $callback, ...$arguments )`   | Execute $callback on each active site in a network |
| `$this->switch_to_blog( $new_blog_id )`                   | Switch the current WordPress blog |
| `$this->restore_current_blog()`                           | Restore the current blog, after calling switch_to_blog() |

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

= Where can I find more information about ... =

+   creating a derivative plugin
+   creating a custom extension
+   defining and using options in my plugin or extension
+   providing automatic updates for my plugin
+   providing contextual help for my plugin or extension

The *{eac}Doojigger Extras* (now at this [Github Repository](https://github.com/EarthAsylum/docs.eacDoojigger)) includes examples and documentation:

+   [{eac}Doojigger Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)  
+   [{eac}Doojigger Extras Documentation](https://github.com/EarthAsylum/docs.eacDoojigger/wiki/)
+   [{eac}Doojigger Extras Download](https://swregistry.earthasylum.com/software-updates/eacdoojigger-extras.zip)

= Who is EarthAsylum Consulting? =

{EarthAsylum Consulting} is a one-person consulting agency in business since 2005.
I have some 30 years experience in technology and software development for a disperse range of businesses.

Currently, and for the last decade or more, my focus has been on internet-based business software & technology management.

In developing {eac}Doojigger, and other plugins based on it, I hope to find a small revenue stream to help keep me going.

To that end, your support is greatly appreciated.
It will enable me to continue developing quality software and provide support to current and future clients (and to enjoy a cup of coffee occasionally).

Learn more here...
+   [EarthAsylum Consulting](https://www.earthasylum.com)
+   [Kevin Burkholder](https://www.kevinBurkholder.com)

Thank you!
_Kevin Burkholder_


== Screenshots ==

1. General settings
![General](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-1.png)

2. Tools settings
![Tools](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-2.png)

3. Debugging settings
![Debugging](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-3.png)

4. Security settings
![Security](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-4.png)

5. Advanced Mode Menu
![Advanced Mode](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/advanced-menu.png)

9. My Awesome Plugin with My Awesome Extension
![myAwesomePlugin](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-9.png)

10. My Awesome Plugin Contextual Help
![ContextualHelp](https://swregistry.earthasylum.com/software-updates/eacdoojigger/assets/screenshot-10.png)


== Other Notes ==

= Additional Information =

{eac}Doojigger should be Network Activated on multi-site installations. Individual extensions and options may be configured on each site.

Some extension may use [wp-config-transformer](https://github.com/wp-cli/wp-config-transformer/contributors) to update wp-config. Copyright 2011-2018 WP-CLI Development Group .


= See Also =

*Information on building with and using {eac}Doojigger*

+   [{eac}Doojigger Derivatives](https://eacDoojigger.earthasylum.com/derivatives/)
+   [{eac}Doojigger Extensions](https://eacDoojigger.earthasylum.com/extensions/)
+   [{eac}Doojigger Options & Settings](https://eacDoojigger.earthasylum.com/options/)
+   [{eac}Doojigger Contextual Help](https://eacDoojigger.earthasylum.com/contextual-help/)
+   [{eac}Doojigger Automatic Updates](https://eacDoojigger.earthasylum.com/automatic-updates/)

*Plugins and Extensions built with {eac}Doojigger*

+   [{eac}SoftwareRegistry](https://swregistry.earthasylum.com/)
A full-featured Software Registration/Licensing Server built on {eac}Doojigger.

+   [{eac}SimpleSMTP](https://eacDoojigger.earthasylum.com/eacsimplesmtp/)
An {eac}Doojigger extension to configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email.

+   [{eac}SimpleAWS](https://eacDoojigger.earthasylum.com/eacsimpleaws/)
An {eac}Doojigger extension to include and enable use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK).

+   [{eac}SimpleCDN](https://eacDoojigger.earthasylum.com/eacsimplecdn/)
An {eac}Doojigger extension to enable the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience.

+   [{eac}ObjectCache](https://eacDoojigger.earthasylum.com/objectcache/)
A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database to cache WordPress objects.

+   [{eac}Readme](https://eacDoojigger.earthasylum.com/eacreadme/)
An {eac}Doojigger extension to translate a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document.

+   [{eac}MetaPixel](https://eacDoojigger.earthasylum.com/eacmetapixel/)
An {eac}Doojigger extension to install the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events.


== Copyright ==

= Copyright © 2019-2024, *EarthAsylum Consulting*, All rights reserved. =

__This is proprietary, copyrighted software.__

+   Title to the Software will remain the exclusive intellectual property of *EarthAsylum Consulting*.

+   You, the customer, are granted a non-exclusive, non-transferable, license to access, install, and use
this software in accordance with the license level purchased.

+   You are not permitted to share, distribute, or make available this software to any third-party.

See: [EarthAsylum Consulting EULA](https://eacDoojigger.earthasylum.com/end-user-license-agreement/)


== Upgrade Notice ==

= 2.0.0 =

To upgrade to version 2.0 of {eac}Doojigger : 1. Disable all derivative plugins; 2. Upgrade {eac}Doojigger; 3. Upgrade all derivative plugins; 4. Re-enable all derivative plugins.


== Changelog ==

= Version 2.6.0 – May 18, 2024 =

+   Improved "Advance Mode" with isAdvancedMode(), setAdvancedMode(), and allowAdvancedMode().
    +   derivative plugins must call allowAdvancedMode(true) to enable, and may overload functions or use 'allow_advanced_mode' filter to implement.
+   Added 'advanced' attribute to settings fields to suppress field when not isAdvancedMode().
+   Changed plugin update to load on 'pre_site_transient_update_plugins' instead of 'pre_set_site_transient_update_plugins'.
+   Made (most) options_settings_page_* methods public so html_input trait can access them.
+   New code-editor trait, loads code-mirror and wp_editor with consistant options/styling.
+   Change to tiny-mce parameters and toolbars for html fields.
+   Improved ajaxAction extension.
    +   Added fingerprint option (using https://github.com/thumbmarkjs/thumbmarkjs).
    +   Added `{pluginName}_{className}_{methodName}` filter in dispatcher.
        +   e.g. `eacDoojigger_ajaxAction_deviceFingerprint`
+   Added 'settings-grid-item-label' and 'settings-grid-item-input-{type}' class to settings divs.
+   Change 'Requires at least' to WordPress 5.8.
+   Several improvements to admin screen styling/layout.
    +   Improved admin theme support using admin colors (from $_wp_admin_css_colors).
+   Changes to some javascript loading code (inc. defer admin script).
+   Support replaceable meta in options attributes, Ex. 'Title' => '[label] [info]'.
    +   label, default, title, before, after, info, tooltip, help
+   Added $this->wp_kses() custom wp_kses method with extended tags.
    +   Now processes all (string) admin field option attributes through $this->wp_kses().
    +   New 'script' field attribute since <script> tags no longer allowed in other attributes.
    +   $this->minifyString() (used for inline scripts/css) now uses $this->wp_kses().
+   Added 'tooltip' attribute to settings fields with jQuery hover tooltip.
    +    Automatically populated with field 'info' when not set or set to true.
+   added tooltip filter to disable auto-populate.
    +   `$this->add_filter("automatic_tooltips", function($bool, $groupName, $groupMeta){...});`
+   Added input field filters before rendering fields.
    +   `$this->add_filter("options_group_meta_{$groupName}", function($groupMeta){...});`
    +   `$this->add_filter("options_field_meta_{$fieldName}", function($fieldMeta, $fieldValue){...});`
+   Added actions to wpmu_installer extension.
    +   `do_action('eacDoojigger_installer_invoke', $installAction, $installMethod, $installOptions, $onSuccess)`
    +   `do_action('eacDoojigger_installer_install', $installOptions)`
    +   `do_action('eacDoojigger_installer_update', $installOptions)`
    +   `do_action('eacDoojigger_installer_uninstall', $installOptions)`
    +   `do_action('eacDoojigger_installer_delete', $installOptions)`
+   Added filters to file_system extension.
    +   `$fs = apply_filters('eacDoojigger_load_filesystem',$wp_filesystem,true,'file system required',[]);`
    +   `$fs = apply_filters('eacDoojigger_link_filesystem',$wp_filesystem,true,'file system required',[]);`
+   Fix call/use of WC() in session_manager to prevent erros if woocommerce has been disabled.

= Version 2.5.0 – April 4, 2024 =

+   Update trait - check 'compatible up to' without '-RCx' in WordPress version.
+   Compatible with WordPress 6.5.
+   Remove autoloader when deactivated.
+   Only flush transients (flush_caches()) if not using an object cache.
+   Reworked get_option(),get_network_option() for faster access to non-reserved options.
+   Added support for `$_SERVER['GEOIP_COUNTRY_CODE']` IP Geolocation.
+   Use `this.form.requestSubmit()` instead of `options_form.submit()`.
+   Fixed switch_to_blog() and loading/saving options on switch.
+   Updated maintenance mode, enable using transient with expiration.
+   Recognize 'Network Enabled' as extension enabled option.
+   New get_page_template() to buffer a template part.
+   Added action `after_flush_caches` when flushing caches.
+   Added option `{classname}_selected_update_channel` and stdOptions_updateChannel() in standard_options trait.
    +   Like {classname}_PLUGIN_UPDATE_CHANNEL.
+   Added constant `{classname}_PLUGIN_UPDATE_CHANNEL` to override plugin update source.
    +   For github hosting, specify branch|release and the tag or id.
    +   `define( 'EACDOOJIGGER_PLUGIN_UPDATE_CHANNEL', 'branch' );`
    +   `define( 'EACDOOJIGGER_PLUGIN_UPDATE_CHANNEL', 'branch/default' );`
+   Updated documentation and examples.
    +   Removed Extras from distribution package, now available on Github:
        +   [Download](https://swregistry.earthasylum.com/software-updates/eacdoojigger-extras.zip)
        +   [Documentation](https://github.com/EarthAsylum/docs.eacDoojigger/wiki/)
+   Streamline standard_options trait, added checkForUpdates (tools).
+   Streamline plugin_update trait including
    +   Support for new software taxonomy Github hosting plugin.
    +   Renamed methods in plugin_update trait.
    +   Delete internal transient when update_plugins transient is deleted.
    +   Changed default transient_time to 1 hour (was 12), letting WP manage update checks.
+   Use wp_clean_update_cache() when clearing caches.
+   Now loads for all (php/non-php) requests.
    +   Extensions are not loaded unless self::ALLOW_NON_PHP is set on construct.
    +   This allows handling of non-php files and redirects passed through WordPress.
    +   Limits loading of unneeded extensions.
+   Pass authentication header on plugin_update_parameters filter in swRegistrationUI.
+   Added environment to plugin updater uri - ?environment=wp_get_environment_type().
+   Fixed dynamic properties (no longer supported in PHP 8.2+) in anonymous class in abstract_core.
+   Fixed error in debugging_extension::capture_deprecated()
+   Now hosted on and updated from github (private repository).
    +   Using [{eac}SoftwareRegistry](https://swregistry.earthasylum.com/) plugin.
    +   With [Software Product Taxonomy](https://swregistry.earthasylum.com/software-taxonomy/) extension for github hosting.

= See changelog.txt for more =

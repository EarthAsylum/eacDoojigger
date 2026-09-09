=== EarthAsylum Consulting {eac}Doojigger for WordPress ===
Plugin URI:             https://eacDoojigger.earthasylum.com/
Author:             	[EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:             3.3.0-RC3
Last Updated:           09-Sep-2026
Requires at least:      5.8
Tested up to:           7.1
Requires PHP:           8.1
Contributors:       	EarthAsylum@github,KevinBurkholder@wordpress
Donate link:            https://github.com/sponsors/EarthAsylum
Support link:           https://github.com/EarthAsylum/eacDoojigger/issues
License:                EarthAsylum Consulting Proprietary License - {eac}PLv1
License URI:            https://eacDoojigger.earthasylum.com/end-user-license-agreement/
Tags:                   plugin development, rapid development, multi-function, security, encryption, debugging, administration, contextual-help, session management, maintenance mode, plugin framework, plugin derivative, plugin extensions, toolkit
GitHub URI:             https://github.com/EarthAsylum/docs.eacDoojigger/wiki

{eac}Doojigger is a powerful, extensible WordPress framework: a ready-to-use utility plugin combined with an architecture for building your own plugins, so you can ship professional-grade results in a fraction of the usual development time.

== Description ==

= Important Update =

**As of August 2025, a free "basic edition" is available** on [GitHub][GitHub Repository], optionally supported via [GitHub Sponsorship][sponsorship].

**Paid subscription plans are still sold** on the [product site][website] for those who want the full commercial offering.

*The [Copyright](#copyright) and [End User License Agreement][EULA] still apply.*

📦 [Download eacDoojigger.zip][Download] - latest release, ready to install

[website]:			https://eacdoojigger.earthasylum.com/shop/
[sponsorship]:		https://github.com/sponsors/EarthAsylum
[download]:			https://swregistry.earthasylum.com/software-updates/eacdoojigger.zip
[GitHub Repository]:https://github.com/EarthAsylum/eacDoojigger
[EULA]:				https://eacdoojigger.earthasylum.com/end-user-license-agreement/


= Summary =

{eac}Doojigger is a WordPress plugin framework: a base plugin that ships with a working set of security, debugging, encryption, session, and administration features, plus an architecture for building your own plugins and extensions on top of it without rewriting WordPress boilerplate each time.

If you build or maintain multiple WordPress plugins — internal tools, client work, or products — {eac}Doojigger's abstract classes and traits handle the repetitive plumbing (activation/deactivation, multi-site awareness, options storage, updates, settings UI, logging) so your code only has to handle what's actually specific to your plugin.

__Three ways to build with {eac}Doojigger__

1. 	**Derivative plugins ("Doojiggers")**
— build your own plugin by extending {eac}Doojigger's abstract classes (`abstract_context`, `abstract_frontend`, `abstract_backend`). You write a small loader file plus a class file; the framework handles the rest.

2. 	**Extensions ("Doolollys")**
— a PHP class dropped into the `Extensions` folder (of the plugin or a child theme) that adds functionality to an existing Doojigger. Lowest-effort option for small, task-specific additions.

3. 	**Extension plugins ("Doohickeys")**
— an extension packaged as its own plugin, so it isn't at risk of being overwritten on the parent plugin's next update or reinstall. Can ship its own automatic updates via the included `plugin_update` trait.

If you're customizing a WordPress site, code that needs to survive a theme change belongs in a plugin, not a theme. Themes should hold presentation code only — anything functional should live in a plugin or plugin extension so it isn't lost the next time the theme is updated or swapped.


= Table of Contents =

+   [Provided With {eac}Doojigger](#provided-with-eacdoojigger)
+   [Doojiggers - Custom Derivative Plugins](#custom-derivative-plugins)
+   [Doolollys - Custom Doojigger Extensions](#custom-eacdoojigger-extensions)
+   [Doohickeys - Custom Doololly Plugins](#custom-extension-plugins)
+   [Using {eac}Doojigger](#using-eacdoojigger)
+   [Automatic Updates](#automatic-updates)
+   [Contextual Help](#contextual-help)
+   [Advanced Mode](#advanced-mode)

= Provided With {eac}Doojigger =

|   'Doolollys' & 'Doodads'             | Included extensions, helpers & Traits |
|   :--------------------------------   |   :---------------    |
|   *[File System Access]*              | Uses and provides easy access to the WP_Filesystem API for creating or updating files while maintaining permissions, compatibility, and security. |
|   *[WPMU Installer]*                  | Uses the file system extension to easily install or update programs or files within the WordPress directory structure.|
|   *[Security]*                        | Adds a number of security/firewall options to your WordPress installation including obfuscating the login url and adding a custom security nonce, enforcing password policies, limiting login attempts, disabling RSS/XML, limiting REST access, checking for required http headers, setting global cookie flags, and more. |
|   *[Content Security Assistant][Security]* | Adds security nonce to `script` and style `link` tags to facilitate creation of comprehensive *Content Security Policy* (CSP) |
|   *[Server-Side CORS][Security]*      | Implements the Cross-Origin Resource Sharing protocol to allow or deny access to resources when requested from non-browser origins using the referring address or reverse DNS lookup to identify the origin. |
|   *[Threat Detection][Security]*      | Block access by IP address based on *security* and *CORS* violations and third-party threat-score APIs (AbuseIPDB, FraudGuard, IPGeoLocation). Version 3.3 adds request rate limiting. |
|   *[Event Scheduler]*                 | Easily set and enable WordPress and custom CRON schedules (intervals), events, and tasks (actions). |
|   *[Key/Value Storage]*               | Lightweight key-value storage that takes full advatage of the WP Object Cache. |
|   *[Debugging]*                       | Provides powerful debugging and detailed logging tools with controls for WordPress debugging options. |
|   *[PSR-3 Logging][Debugging]*        | Standard logging methods with ability to `subscribe` to log events. |
|   *Encryption*                        | Easy to use data encryption and decryption filters using AES (a NIST FIPS-approved cryptographic algorithm) with authentication tag. |
|   *[Cookie Compliance]*               | Set cookies with [WP Consent API] compatible consent parameters for GDPR/CCPA Compliance. |
|   *Session Support*                   | Manages PHP sessions using well-known session managers or through WordPress transients, with built-in support for reading/writing session variables. |
|   *Maintenance Mode*                  | Custom "Maintenance Mode" when you need to disable front-end access to your site(s). |
|   *Administrator Tools*               | Adds cache management and plugin settings backup/restore, export/import. |
|   *[Plugin Reinstall]*                | Provides the ability to re-install eacDoojigger from the `Tools` tab or any/all plugins from the `Plugins` page. |
|   *Ajax Action*                       | Adds an easy to use ajax responder (accessable from any extension). |

Plus shared PHP traits for plugin loading, updates, contextual help, HTML input fields, common dashboard options, date/time handling, version comparisons, and zip archives.

[File System Access]:	https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(file-system-extension)
[WPMU Installer]:		https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(eacdoojigger-installer)
[Security]:				https://github.com/EarthAsylum/docs.eacDoojigger/wiki/Security
[Event Scheduler]:		https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(recurring-events)
[Key/Value Storage]:	https://github.com/EarthAsylum/eacKeyValue/blob/main/readme.md
[Debugging]:			https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(debugging-logger-methods)
[Cookie Compliance]:	https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(wp-consent-api-and-cookies)
[Plugin Reinstall]:		https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To-(plugin-reinstall)
[WP Consent API]:		https://wordpress.org/plugins/wp-consent-api/
[AbuseIPDB]:			https://www.abuseipdb.com/user/165095
[FraudGuard]:			https://www.fraudguard.io
[IpGeoLocation]:		https://www.ipgeolocation.io

|   {eac}Doojigger Utilities            |                       |
|   :--------------------------------   |   :---------------    |
|   *{eac}DoojiggerAutoloader*          | The required auto-loader to automatically load {eac}Doojigger (and derivative) classes and traits. |
|   *{eac}DoojiggerEnvironment*         | The Environment Switcher to set WP environment from the network (multi-site) settings page. |
|   *{eac}DoojiggerActionTimer*         | A timer/tracking utility to track the load/execution timing of WordPress actions and filters (as well as custom events) when WordPress loads. |


|   Available 'Doojiggers'              |   Derivative Plugins  |
|   :--------------------------------   |   :---------------    |
|   [{eac}SoftwareRegistry]             | A full-featured Software Registration/Licensing Server (used by {eac}Doojigger). |

[{eac}SoftwareRegistry]:	https://swregistry.earthasylum.com/


|   Available 'Doohickies'              |   Extension Plugins { [WordPress Repository] } |
|   :--------------------------------   |   :---------------    |
|   [{eac}ObjectCache]                  | A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database and even faster APCu shared memory to cache WordPress objects. |
|   [{eac}SimpleCDN]                    | Enables the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience. |
|   [{eac}SimpleSMTP]                   | Configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email. |
|   [{eac}SimpleAWS]                    | Includes and enables use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK). |
|   [{eac}Readme]                       | Translates a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document. |
|   [{eac}SimpleGTM]                    | Installs the Google Tag Manager (gtm) or Google Analytics (gtag) script, sets default consent options, and enables tracking of views, searches, and, with WooCommerce, e-commerce actions. |
|   [{eac}MetaPixel]                    | installs the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events. |

[WordPress Repository]:		https://wordpress.org/plugins/search/earthasylum/

[{eac}ObjectCache]:			https://eacdoojigger.earthasylum.com/objectcache/
[{eac}SimpleCDN]:			https://eacdoojigger.earthasylum.com/eacsimplecdn/
[{eac}SimpleSMTP]:			https://eacdoojigger.earthasylum.com/eacsimplesmtp/
[{eac}SimpleAWS]:			https://eacdoojigger.earthasylum.com/eacsimpleaws/
[{eac}Readme]:				https://eacdoojigger.earthasylum.com/eacreadme/
[{eac}SimpleGTM]:			https://eacdoojigger.earthasylum.com/eacsimplegtm/
[{eac}MetaPixel]:			https://eacdoojigger.earthasylum.com/eacmetapixel/

__Extras & Examples__

*Skeleton/example code* (`myAwesomePlugin`, `myAwesomeExtension`, `myFunctions`, `myOptionsTest`) ships separately as working starting points, available on [GitHub][explore on github].

[explore on github] | [documentation wiki] | [download extras zip]

[explore on github]:		https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras
[documentation wiki]:		https://github.com/EarthAsylum/docs.eacDoojigger/wiki/
[download extras zip]:		https://swregistry.earthasylum.com/software-updates/eacdoojigger-extras.zip


= 'Doojiggers' - Custom Derivative Plugins =

Once {eac}Doojigger is installed and registered, you can build your own plugin on its abstract classes and traits:

1. **Create a plugin loader** (`myAwesomePlugin.php`) — this is your main plugin file. It needs the standard WordPress plugin headers and uses the `plugin_loader` trait provided by {eac}Doojigger.

2. **Create your plugin class** (`myAwesomePlugin.class.php`) — loaded by the file above. Extend {eac}Doojigger's abstract classes (`abstract_context`, `abstract_frontend`, `abstract_backend`) to inherit the management and utility code a full-featured plugin needs.

3. **Install it** — upload the plugin as usual.

From there, your code only needs to handle your actual requirements — the WordPress plumbing is already done.

>   See [instructions and examples](https://eacdoojigger.earthasylum.com/derivatives/) (found in the *[Extras]/Plugins/* folder).


= 'Doolollys' - Custom Doojigger Extensions =

An extension is a PHP class that adds functionality to the base plugin — as simple or complex as you need.

1. Create an extension class (`myAwesomeExtension.extension.php`) extending `abstract_extension`.

2. Drop it into the plugin's `Extensions` folder.

*Custom extensions may also be uploaded to your theme folder (preferable a [child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/)), in the ../eacDoojigger/Extensions or ../eacDoojigger/doolollys folder.*

>   See [instructions and examples](https://eacdoojigger.earthasylum.com/extensions/) (found in the *[Extras]/Extensions/* folder).


= 'Doohickeys' - Custom Extension Plugins =

Adding extensions directly into {eac}Doojigger's `Extensions` folder works, but an upgrade or re-install can overwrite them. To avoid that, package extensions as their own plugin instead — it responds to a filter from the base plugin telling it where to load additional extensions, and lives in its own plugin folder, safe from being overwritten.

Extension plugins built this way can also provide automatic updates, by using the `plugin_update` trait.


= Using {eac}Doojigger =

{eac}Doojigger exposes methods and hooks you can call from your own plugins, extensions, template functions, or anywhere else in WordPress.

>   See:
>	+ [Using {eac}Doojigger](https://eacdoojigger.earthasylum.com/using-doojigger) (found in the *[Extras]/UsingDoojigger/* folder) for details and examples,
>   + [{eac}Doojigger PHP Reference](https://earthasylum.github.io/docs.eacDoojigger/) documentation.


= Automatic Updates =

WordPress hosted plugins provide updating functionality automatically. Whenever a new version of a plugin is updated in the WordPress repository, update notifications are seen in your WordPress dashbord on the plugins page.

You can provide the same functionality with your externally or self hosted plugin with a few easy changes.

>   See [Automatic Updates](https://eacdoojigger.earthasylum.com/automatic-updates/) (found in the *[Extras]/AutoUpdate/* folder) for more information.

= Contextual Help =

To complete your plugin and improve support, provide contextual help using the {eac}Doojigger interface to standard WordPress help functions.

Adding contextual help to your plugin and extension is easy using the methods built into {eac}Doojigger... and when using the proper filter, you can ensure that your help content only shows on your plugin page or extension tab.

>   See the [Contextual Help](https://eacdoojigger.earthasylum.com/contextual-help/) page (found in the *[Extras]/ContextualHelp/* folder) for complete details and examples.

[Extras]:	https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras


= Advanced Mode =

Advanced Mode gives developers a method to implement options or features based on an advanced mode setting (or combination of settings). {eac}Doojigger uses a menu selection and license level to enable advanced mode, but custom derivatives may use other methods to implement advanced mode.

>   See [Implementing and Using Advanced Mode](https://eacdoojigger.earthasylum.com/how-to/#implementing-and-using-advanced-mode) for details.


== Multi-Site Network ==

>   A multisite network is a collection of sites that all share the same WordPress installation core files. They can also share plugins and themes. The individual sites in the network are virtual sites in the sense that they do not have their own directories on your server, although they do have separate directories for media uploads within the shared installation, and they do have separate tables in the database.

{eac}Doojigger is well aware of multi-site/network environments where only a network administrator may install plugins and plugins may be *network-activated* (enabled for all sites) or *site-activated* (enabled for/by individual sites within the network).

{eac}Doojigger manages installation, activation, deactivation and un-installing properly based on the type of installation and activation. For example, when an Doojigger plugin is *network-activated*, it is activated on all sites in the network. When un-installed, it is un-installed from all sites. When installed by the network administrator but not *network activated*, each site administrator may properly activate or de-activate the plugin.

{eac}Doojigger distinguishes between a plugin being *network-installed* versus *network-activated*, and its option/transient methods behave accordingly — deliberately diverging from WordPress's own defaults, where `*_network_option()` and `*_site_option()` fall back to single-site behavior without checking activation type.

In short:

- `$this->add_option()` — site-only, always.
- `$this->add_network_option()` — only takes effect when network-activated; otherwise a no-op.
- `$this->add_site_option()` — site-scoped normally, but automatically becomes network-wide when the plugin is network-activated.

WordPress does not check (nor should it) for the type of plugin *activation* (network wide vs. individual site).

If you manage plugins across a multi-site network — some sites opting in, others not — this gives you option and transient behavior that matches actual activation state without extra code on your part.

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

= Network Related Methods =

| Method Name                                               | Description |
| -----------                                               | ----------- |
| `$this->is_network_enabled()`                             | Returns true if plugin is network-enabled |
| `$this->forEachNetworkSite( $callback, ...$arguments )`   | Execute $callback on each active site in a network |
| `$this->switch_to_blog( $new_blog_id )`                   | Switch the current WordPress blog |
| `$this->restore_current_blog()`                           | Restore the current blog, after calling switch_to_blog() |

\* *use `$this->is_network_enabled()` to determine if the plugin is network activated. Extensions may use `$this->is_network_enabled()` to determine if the extension is enabled at the network level or `$this->plugin->is_network_enabled()` to determine if the plugin is network activated.*

*Using `$this->switch_to_blog()` and `$this->restore_current_blog()` over the corresponding WordPress functions ensures that options are correctly saved and loaded for the switched-from/to blogs.*


== More Information ==

{eac}Doojigger should be Network Activated on multi-site installations. Individual extensions and options may be configured on each site.

= Definitions =

_doojigger_ (n)
1. Something unspecified whose name is either forgotten or not known.
2. *A plugin built on {eac}Doojigger (including {eac}Doojigger itself)*

_doololly_ (n)
1. Any nameless small object, typically some form of gadget.
2. *An extension added to a Doojigger*

_doohickey_ (n)
1. A thing (used in a vague way to refer to something whose name one does not know or cannot recall).
2. *A Doololly packaged as its own standalone plugin*

_doodad_ (n)
1. Something, especially a small device or part, whose name is unknown or forgotten.
2. *A shared helper or trait included with a Doojigger*

---

>   'Doojiggers' and 'Doohickeys' (plugins) have their own activation and deactivation processes whereas 'Doolollys' (extensions) are activated or deactivated along with their parent 'Doojigger'. 'Doohickeys' remain active but perform no function if their parent 'Doojigger' is deactivated.

>   {eac}Doojigger is the ancestrial parent of all 'Doojiggers', 'Doolollys', and 'Doohickeys'.

= See Also =

*Information on building with and using {eac}Doojigger*

+   [{eac}Doojigger Derivatives](https://eacDoojigger.earthasylum.com/derivatives/)
+   [{eac}Doojigger Extensions](https://eacDoojigger.earthasylum.com/extensions/)
+   [{eac}Doojigger Options & Settings](https://eacDoojigger.earthasylum.com/options/)
+   [{eac}Doojigger Automatic Updates](https://eacDoojigger.earthasylum.com/automatic-updates/)
+   [{eac}Doojigger Contextual Help](https://eacDoojigger.earthasylum.com/contextual-help/)


*{eac}Doojigger Information and Examples*

+   [{eac}Doojigger How-To...](https://eacDoojigger.earthasylum.com/how-to/)

*'Doohickeys' (plugins) and 'Doolollys' (extensions) built with {eac}Doojigger*

+   [{eac}SoftwareRegistry]
A full-featured Software Registration/Licensing Server built on {eac}Doojigger.

+   [{eac}ObjectCache]
A light-weight and very efficient drop-in persistent object cache that uses a fast SQLite database and even faster APCu shared memory to cache WordPress objects.

+   [{eac}SimpleCDN]
An {eac}Doojigger extension to enable the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience.

+   [{eac}SimpleSMTP]
An {eac}Doojigger extension to configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email.

+   [{eac}SimpleAWS]
An {eac}Doojigger extension to include and enable use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK).

+   [{eac}Readme]
An {eac}Doojigger extension to translate a WordPress style markdown 'readme.txt' file and provides _shortcodes_ to access header lines, section blocks, or the entire document.

+   [{eac}SimpleGTM]
Installs and configures the Google Tag Manager (GTM) or Google Analytics (GA4) script with optional tracking events.

+   [{eac}MetaPixel]
An {eac}Doojigger extension to install the Facebook/Meta Pixel to enable tracking of PageView, ViewContent, AddToCart, InitiateCheckout and Purchase events.

+	[{eac}KeyValue]
An easy to use, efficient, key-value pair storage mechanism for WordPress that takes advatage of the WP Object Cache. Similar to WP options/transients with less overhead and greater efficiency (and fewer hooks).

[{eac}SoftwareRegistry]:	https://swregistry.earthasylum.com/
[{eac}ObjectCache]:			https://eacdoojigger.earthasylum.com/objectcache/
[{eac}SimpleCDN]:			https://eacdoojigger.earthasylum.com/eacsimplecdn/
[{eac}SimpleSMTP]:			https://eacdoojigger.earthasylum.com/eacsimplesmtp/
[{eac}SimpleAWS]:			https://eacdoojigger.earthasylum.com/eacsimpleaws/
[{eac}Readme]:				https://eacdoojigger.earthasylum.com/eacreadme/
[{eac}SimpleGTM]:			https://eacdoojigger.earthasylum.com/eacsimplegtm/
[{eac}MetaPixel]:			https://eacdoojigger.earthasylum.com/eacmetapixel/
[{eac}KeyValue]:			https://eacdoojigger.earthasylum.com/eackeyvalue/


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

{eac}Doojigger has been meticulously updated to provide not only new features and efficiencies, but many other improvements, including stability and reliability. The code base of {eac}Doojigger has been in proprietary use (and in development) over many years and on several websites. However, there is a nearly infinte variety of configurations and uses that can't possibly be tested. If you run into any issues, problems, bugs or simply change requests, please share any details on the [issues](https://github.com/EarthAsylum/eacDoojigger/issues) or [discussions](https://github.com/EarthAsylum/eacDoojigger/discussions) pages.

= Where can I find more information about ... =

+   creating a [derivative plugin](https://eacDoojigger.earthasylum.com/derivatives/)
+   creating a [custom extension](https://eacDoojigger.earthasylum.com/extensions/)
+   defining and using [options & settings](https://eacDoojigger.earthasylum.com/options/) in my plugin or extension
+   providing [automatic updates](https://eacDoojigger.earthasylum.com/automatic-updates/) for my plugin
+   providing [contextual help](https://eacDoojigger.earthasylum.com/contextual-help/) for my plugin or extension
+   [using features](https://eacDoojigger.earthasylum.com/how-to/) of {eac}Doojigger

The *{eac}Doojigger Extras* (now at this [Github Repository](https://github.com/EarthAsylum/docs.eacDoojigger)) includes documentation and several functional examples:

+   [Extras](https://github.com/EarthAsylum/docs.eacDoojigger/tree/main/Extras)
+   [Extras Documentation](https://github.com/EarthAsylum/docs.eacDoojigger/wiki/)
+   [Extras Download](https://swregistry.earthasylum.com/software-updates/eacdoojigger-extras.zip)

= Who is EarthAsylum Consulting? =

{EarthAsylum Consulting} is a one-person consulting agency, active since 2005, with decades of experience in technology and software development.

The focus for the last decade, or more, has been internet-based business software and technology management.

{eac}Doojigger (and the plugins built on it) exist to fund continued development and support. Your support and [sponsorship](https://github.com/sponsors/EarthAsylum) are greatly appreciated.

*It's not just a job, it's a hobby, a craft, a passion, and an art.*

Thank you!
[Kevin Burkholder](https://www.kevinBurkholder.com) @ [EarthAsylum Consulting](https://www.earthasylum.com)


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


== Upgrade Notice ==

= 3.0 =

As of version 3.0, PHP 7 is no longer supported; {eac}Doojigger requires PHP 8.1+


== Copyright ==

= Copyright © 2019-2026, *EarthAsylum Consulting*, All rights reserved. =

__This is proprietary, copyrighted software.__

+   Title to the Software will remain the exclusive intellectual property of *EarthAsylum Consulting*.

+   You, the customer, are granted a non-exclusive, non-transferable, license to access, install, and use
this software in accordance with the license level affirmed.

+   You are not permitted to share, distribute, or make available this software to any third-party.

See: [EarthAsylum Consulting EULA](https://eacDoojigger.earthasylum.com/end-user-license-agreement/)


== Changelog ==

= Version 3.3.0 – September 9, 2026 =

+	Compatible with WordPress 7.1.
+	New `Request Rate Limit` setting in Risk Assessment extension.
	+	Limits the number of request (by IP address) within 10 minutes.
	+	Added `Retry-After` header on status = 429.
+	New `Whitelist IP Addresses` setting in Risk Assessment extension.
	+	To bypass risk assessment for given IP addresses.
+	New `is_non_code_request()` function to determine non-code request from url using `wp_get_ext_types()`.
	+	Extensions, by default, do not load for non-code requests.
	+	If needed, extension must specify `self::ALLOW_NON_CODE` on `parent::__construct()`.
+	New 'plugin_reinstall' extension enables the re-installation of current plugins.
	+	`getReinstallLink()` method provides html link to trigger a plugin reinstall.
	+	`reinstall_plugin_action()` method used by `eacDoojigger_reinstall_plugin` action.
	+	Adds 'Trigger Reinstall' link to tools page.
	+	Adds 'Trigger Reinstall' links to plugins page using `plugin_auto_update_setting_html` filter.
+	Plugin loader - uses `is_non_code_request()` rather than `isPHP()` on load option (load only for code files).
+	Moved admin-only extensions to admin folder.
+	Use case-insensitive search for readme.txt/md file.
+	Admin page tool-tips now converts new-line to break.
+	Fix: Pass full/un-truncated blog version in plugin_update.trait.
+	Fix: decryption in `eacKeyValue::get()`
+	Fix/improve maintenance mode with accurate retry-after and [UntilTime] shortcode.
+	Updated `IpGeoLocation` API to v3.
+	Updated `IpGeoLocation` plans for rate limits and credits.
+	Always respect `Retry-After` header in risk assessment APIs.
+	Aesthetic/Nonstructural changes...
	+	Enhanced `getDocumentationLink()`.
	+	Enhanced `getSupportLink()` with use of `Support Link` in readme.txt.
	+	Removed 'Registration' link added 'Support' and 'Sponsor' to plugins page listing.
	+	Updated readme and other documentation.
+	Allow single option passed to `add_admin_action_link` then passed through url to triggered action.
+	Allow passing alternate url to `add_admin_action_link` (override current uri);

= Version 3.2.5 – August 6, 2026 =

+   Fix: dashicons info icon `left: unset`.
+   In `cookie_consent.trait.php`...
    +   Validate `plugin_or_service` using `wp_validate_consent_service()`.
    +   Enhanced `has_cookie_consent()` to check category and/or service name for allowed/denied.
    +   Add filter `wp_get_consent_type` earlier to prevent defaulting to 'allow' consent.
    +   New `wp_setcookie_service` filter.

= Version 3.2.4 – July 31, 2026 =

+   Don't wait for `rest_pre_serve_request` in CORS origin check.
    +   Prevents rest execution when forbidden.
+   Use `send_headers` action  for CORS headers.
+   Fix: check return of parse_url for array.
+   Fix: `currentURL()` for WP-CLI.
+   Updated (finally) for PHP 8.4+
+   Removed reference to `E_STRICT` constant.
+   Implement explicit nullable types (?string).

= Version 3.2.3 – June 5, 2026 =

+   Compatible with WordPress 7.0.
+   Fixed `getVariable()` when using derivative plugin(s).
    +   Get default from eacDoojigger filter before derivative filter.
+   Do not start a new session from an ajax call.
+   Fixed error with `allowed_http_origin` filter when $origin_arg is an array.
+   Fixed error when enabling output file for AbuseIPDB.
+   Support `wp_has_service_consent()` from WP Consent API 2.0.

= Version 3.2.2 – October 1, 2025 =

+   Fixed SQL select for sitewide transient (meta_key) in eacKeyValue.
+   Ignore (return default) options with ['-','\_','.'] prefix in `get_option()`.
+   Pass `$context` array in logging methods.
+   Force string return in getRequestParts(...) when null.
+   Don't repeatedly set visitor cookie, only set if not found.
+   Fix  error in `upgrader_process_complete` when `$hook_extra['plugins']` is null.
+   Check `headers_sent()` in `set_cookie()` to prevent error.
+   Option to bypass kses in `minifyString()` since typically is not html.
+   Automatically strip invalid characters in `minifyString()`.

= Version 3.2.1 – August 1, 2025 =

+   Fixed issues with uninstall and added support for keyvalue table(s).
+   Fix for plugin update, not network activated, on multisite.
+   Admin notice/settings errors recognize and eliminate duplicate notices.
+   Risk assessment looks for "ip_allow_list.conf" file to reset assessment by IP address.
+   `datetime` trait now uses DateTimeImmutable instead of DateTime, still returns \DateTime.
+   Moved extension loading code to `load_extensions` trait.
+   Fixed `getRequestParts()` using PHP_URL_* component.
+   Add (and use) `getRequestScheme()` method.
+   Add `allow_request_origin()` method, sets `http_origin` and `allowed_http_origins` filters.
+   Add error_log on access_denied().
+   Use Anonymous function to send headers in access_denied().
+   Load theme extensions/doolollys after plugin extensions/doohickies.
+   New `isDeveloperLicense()` and `isUnlimitedLicense()` methods.
+   Add 'developer' and 'unlimited' to advanced mode settings array.
+   Updated `swRegistrationUI` trait.
+   Updated registration SDK.
+   Updated license (for github distribution).

= Version 3.2 – July 1, 2025 =

+   Added `eacDoojigger_risk_assessment_data` filter allowing actors to filter risk assessment result.
+   Fixed potential 1-second error in rate limit check of Risk Assessment.
+   New `eacKeyValue` helper class for key/value pair storage.
    +   see: https://github.com/EarthAsylum/eacKeyValue
+   Internal transient methods use key/value helper instead of WP transient API.
+   Internal options methods use key/value helper instead of WP options API.
+   Reworked internal option backup/restore.
+   Added 'Key/Value storage' as session manager option.
+   Session 'transient' option uses WP transient functions (not internal).
+   Make sure we have FS_CHMOD_FILE/FS_CHMOD_DIR set in autoload.php.
+   Added `doTask()` method and `do_cron_task` action to event_scheduler extension.
+   Delay scheduling events until `init` action, allows routing to Action Scheduler.
+   Do Risk Assessment a bit earlier on `wp_headers` not `wp`.
+   `access_denied()` checks for `send_headers` action.
+   Strip tags when logging admin_notice warnings/error.
+   Debug log entry for Action Scheduler tasks.

= [See changelog.md for more](https://raw.githubusercontent.com/EarthAsylum/eacDoojigger/main/changelog.md) =

== Changelog ==

= Version 2.6.2 – September 7, 2024 =

+   Purge expired transients on cache clearing and automatically (daily).
    +   Force minimum transient expiration with transient sessions.
+   New `text_to_array()` function to split textarea to array of lines.
+   New AbuseIPDB api (security) extension to block by IP address based on abuse score.
    +   See : https://www.abuseipdb.com 
+   Enhanced security extension...
    +   Block REST index list, WP core REST routes, non-rest json requests.
    +   CORS headers w/white-list domains.
+   Updated wpconfig-transformer to v1.3.6
+   Reworked/simplified installed mu autoloader and autoloader class with new 'autoload.php'.
+   Removed `setEmailNotification()` from autoloader and emailFatalNotice standard option.
+   Changed advanced mode link on settings page (essentials|advanced).
+   Reworked debugging extension and logging with new logger helper compatible with PSR-3 logging.
    +   See : https://eacdoojigger.earthasylum.com/how-to/#use-debugging-logger-methods
    +   New PSR-3 logging method : `$this->log( $level, $message, $context )`
    +   Or e.g. : `eacDoojigger->log('error', $message, $context )`
+   Support/compliance with WP Consent API.
+   New cookie methods supporting WP Consent API (if active).
    +   See: https://eacdoojigger.earthasylum.com/how-to/#wp-consent-api-and-cookies
    +   `set_cookie(string $name, string $value, $expires=0, array $options=[], $consent=[])`
+   Allow/default session access from derivative plugins when using `setVariable()` and `getVariable()`.
+   Added action `{pluginname}_startup` after `plugins_loaded`, before loading extensions.
+   Session debugging filter for `eacDoojigger_debugging`.

= Version 2.6.1 – July 6, 2024 =

+   Session manager extension:
    +   Use session_set_cookie_params if session_start().
    +   uses WC->session getters and setters.
    +   Start session on demand not on 'init'.
    +   Adjust session_save_data (shutdown) priority (8).
    +   Added generic session manager using external plugin (or not).
    +   Removed (outdated) 'WP Session Manager' support.
+   Option input field type allow 'toggle' as alias for 'switch'.
+   For option validation ('validate'=>...), false value triggers generic error notice.
+   doing_ajax() checks wp_doing_ajax (admin-ajax.php) and 'XMLHttpRequest' (other).
+   $this->isAjaxRequest() method deprecated for $this->doing_ajax().
+   Removed user id from visitorId().
+   Save visitorId using setVariable() (maybe session).
    +   isNewVisitor() checks variable.

= Version 2.6.0 – June 4, 2024 =

+   EAC_DOOJIGGER_VERSION constant deprecated in favor of EACDOOJIGGER_VERSION.
+   Fixed upgrade notice in plugin update notice trait.
+   New getRelease() method returns 'Stable Tag' and 'Last Updated' from readme.
+   Include header values from readme.txt in pluginData.
+   Add stable release on plugins page when different than version.
+   Moved plugin updater actions from plugin loader to new `eacDoojiggerPluginUpdater` class.
    +   Loaded once in eacDoojiggerAutoloader.
    +   Handles all derivative and extension plugins.
    +   Reduces individual plugin footprint and redundancy.
    +   Allows updating even when plugin is disabled or not network enabled on multi-site.
+   Improved "Advanced Mode" with isAdvancedMode(), setAdvancedMode(), and allowAdvancedMode().
    +   derivative plugins must call allowAdvancedMode(true) to enable, and may overload functions or use 'allow_advanced_mode' filter to implement.
    +   filter `$this->apply_filters('is_advanced_mode',false,'settings');`
+   Added 'advanced' attribute to settings fields to suppress field when not isAdvancedMode().
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
    +   New 'script' field attribute since script tags no longer allowed in other attributes.
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

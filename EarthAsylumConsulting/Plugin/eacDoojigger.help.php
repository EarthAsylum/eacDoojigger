<?php
defined( 'ABSPATH' ) or exit;

ob_start();
?>
	{eac}Doojigger is a multi functional and highly extensible WordPress plugin and architectural framework
	enabling the easy creation of full featured derivative plugins and custom extensions.

	{eac}Doojigger also provides utilities and extensions covering
	security, debugging, encryption, session management, maintenance mode, administration tools, and much more.

	And you can create your own
	<a href='https://eacdoojigger.earthasylum.com/extensions' target='_blank'>{eac}Doojigger extensions</a>
	or install additional extensions from the <a href='%s'>WordPress Plugin Repository</a>.
<?php
$content = sprintf(ob_get_clean(), network_admin_url('/plugin-install.php?s=earthasylum&tab=search&type=term'));

$this->addPluginHelpTab("General",$content,'About');

ob_start();
?>
	Should you need help using or extending {eac}Doojigger, please review this help content and read our online
	<a href='https://eacdoojigger.earthasylum.com/eacdoojigger' target='_blank'>documentation</a>. If necessary,
	email us with your questions, problems, or bug reports at <a href='mailto:support@earthasylum.com'>support@earthasylum.com</a>.

	We recommend checking your <a href='site-health.php'>Site Health</a> report occasionally, especially when problems arise.
<?php
$content = ob_get_clean();

$this->addPluginHelpTab("General",$content,['Getting Help','open']);

if ($this->isSettingsPage('Tools'))
{
	ob_start();
	?>
		The %s Environment tool enables the installation (or removal) of the  {eac}Doojigger
		Environment Switcher. The Environment Switcher adds a 'WP Environment' option
		to the %s page allowing the administrator to set the environment to either
		'Production', 'Staging', 'Development', or 'Local'.

		<details><summary>Requirements</summary>
		<ul>
			<li>Write access to	 the 'mu-plugins' folder.
			<li>Because of core functionality in WordPress, this feature only works if:
				<ul>
					<li>WP_DEBUG constant IS defined.
					<li>WP_ENVIRONMENT_TYPE constant is NOT defined.
				</ul>
			<li>If necessary, change the 'wp-config.php' file on your server:
				<ul>
					<li>Add: <code>define( 'WP_DEBUG', true|false );</code>
					<li>Remove: <code>define( 'WP_ENVIRONMENT_TYPE', true|false );</code>
				</ul>
		</ul>
		</details>
	<?php
	$content = ob_get_clean();
/*
	if ($this->is_network_admin()) {
		$content = sprintf($content, 'Network','<a href="'.network_admin_url('settings.php').'">Network Settings</a>');
		$this->addPluginHelpTab("Tools",$content,['Network Environment','open'],99);
	} else {
		$content = sprintf($content, 'Site','<a href="'.admin_url('options-general.php').'">General Settings</a>');
		$this->addPluginHelpTab("Tools",$content,['Site Environment','open'],99);
	}
*/
}

$this->addPluginSidebarText('<h4>For more information:</h4>');

$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-info-outline eac-logo-orange'></span>About This Plugin",
	( is_multisite()
		? network_admin_url('plugin-install.php')
		: admin_url('plugin-install.php')
	).
	"?tab=plugin-information&plugin=eacDoojigger",
	$this->pluginHeader('Title')." Plugin Information Page"
);
$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-plugins-checked eac-logo-orange'></span>Derivative Plugins",
	$this->getDocumentationURL(true,'/derivatives'),
	"Custom Plugin Derivatives"
);
$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-admin-generic eac-logo-orange'></span>Custom Extensions",
	$this->getDocumentationURL(true,'/extensions'),
	"Custom Plugin Extensions"
);
$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-admin-settings eac-logo-orange'></span>Admin Options",
	$this->getDocumentationURL(true,'/options'),
	"Custom Administrator Options"
);

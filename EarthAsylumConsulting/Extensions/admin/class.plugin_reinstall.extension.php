<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\plugin_reinstall', false) )
{
	/**
	 * Extension: plugin_reinstall - enable re-install of plugins - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class plugin_reinstall extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '26.0909.1';

		/**
		 * @var string extension tab name
		 */
		const TAB_NAME	= 'Tools';


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			$this->enable_option = false;
			parent::__construct($plugin, self::ALLOW_ADMIN|self::ALLOW_NETWORK|self::ONLY_ADMIN);

			$this->registerExtension( 'software_updates' );

			add_action('admin_init', function()
			{
				if ($this->isAdvancedMode('settings'))
				{
					// Register plugin options when needed
					$this->add_action( "options_settings_page", 	array($this, 'admin_options_settings') );
				}
			});
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' )
			&& (!is_multisite() || $this->plugin->is_network_admin()) )
			{
				// add reinstall plugin/all plugins
				$option 		= $this->is_option('reinstall_all_plugins') ? 'Disable' : 'Enable';
				$name 			= "{$option} for All Plugins";
				$title 			= "{$option} Reinstall for all eligible plugins from the Plugins page";

				$linkURL = $this->add_admin_action_link( "reinstall_all_plugins", "{$option}d", network_admin_url('plugins.php') );
				$enableAllLink = sprintf('<a href="%1$s" data-tooltip title="%2$s">%3$s</a>',$linkURL,$title,$name);
				$enableAllLink = str_replace('data-tooltip','class="button button-large" style="width:100%;" data-tooltip',$enableAllLink);

				$reinstallLink = $this->getReinstallLink(goto: network_admin_url('update-core.php'));
				$reinstallLink = str_replace('data-tooltip','class="button button-large" style="width:100%;" data-tooltip',$reinstallLink);

				$reinstall = ['_btnReinstallPlugin' =>
					[
						'type'		=> 	'display',
						'label'		=> 	'Reinstall Plugin(s)',
						'default'	=> 	"<div style='width:14em;'>".
										"<div style='margin-top:0em;'>".$reinstallLink."</div>".
										"<div style='margin-top:1em;'>".$enableAllLink."</div>".
										"</div>",
						'info'		=> 	"Force the plugin update option on the Plugins or Updates page.",
					]
				];
				$this->registerExtensionOptions('software_updates',$reinstall);
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			// on plugin_auto_update_setting_html filter, add 'Reinstall' link
			if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) )
			{
				if ($this->is_option('reinstall_all_plugins'))
				{
					// for all plugins
					add_filter( 'plugin_auto_update_setting_html', function($pluginHtml, $pluginSlug, $pluginData)
						{
							$updates = \get_site_transient('update_plugins');
							if ($updates && isset($updates->no_update[$pluginSlug])
							&&  str_contains($pluginHtml,'able auto-updates'))
							{
								$pluginHtml .= '<br/>'.$this->getReinstallLink($pluginData);
							}
							return $pluginHtml;
						},20,3
					);
				}
				else
				{
				/*
					// for this plugin
					add_filter( 'plugin_auto_update_setting_html', function($pluginHtml, $pluginSlug, $pluginData)
						{
							if ($pluginSlug == $this->plugin->PLUGIN_SLUG)
							{
								$pluginHtml .= '<br/>'.$this->getReinstallLink();
							}
							return $pluginHtml;
						},20,3
					);
				*/
				}

				// on reinstall_all_plugins, set option Enabled/Disabled
				$this->add_action( 'reinstall_all_plugins', function($option)
					{
						$this->set_option('reinstall_all_plugins',$option);
					}
				);

				// on reinstall_plugin, trigger plugin reinstall
				$this->add_action( 'reinstall_plugin', function($pluginSlug)
					{
						if ($this->is_option('reinstall_all_plugins') || $pluginSlug == $this->plugin->PLUGIN_SLUG)
						{
							$this->reinstall_plugin_action($pluginSlug);
						}
					}
				);
			}
		}


		/**
		 * get Trigger Reinstall link for this plugin
		 *
		 * @param mixed $plugin true=use this plugin title, array=get_plugin_data array, string = title
		 * @param string $slug WP plugin slug
		 * @param string $name link name
		 * @param string $title title
		 * @return	string	the Support link
		 */
		public function getReinstallLink($plugin=true,$slug=null,$name='',$title='',?string $goto=null): string
		{
			if ($plugin === true)							// this plugin
			{
				$pluginId = $this->pluginHeader('Title');
				if (empty($slug)) $slug = $this->pluginHeader('PluginSlug');
			}
			else if (is_array($plugin))						// get_plugin_data() array
			{
				if (isset($plugin['Name'])) $pluginId = $plugin['Name'];
				if (empty($slug) && isset($plugin['plugin'])) $slug = $plugin['plugin'];
			}
			else if (is_scalar($plugin))					// named plugin
			{
				$pluginId = $plugin;						// $slug required
			}
			else $pluginId = '';

			if ($pluginId && $slug)
			{
				$isWP = true;
				$updates = \get_site_transient('update_plugins');
				if (is_object($updates))
				{
					if (isset($updates->response[$slug])) return '';
					if (isset($updates->no_update) && isset($updates->no_update[$slug])) {
						$isWP = (str_starts_with($updates->no_update[$slug]->package,'https://downloads.wordpress.org'));
					} else {
						return '';
					}
				}
				$color 	= ($isWP) ? '#0073aa' : '#00a0d2';
				$source = ($isWP) ? 'From WP Repository' : 'From External Source';
				$version= $updates->no_update[$slug]->new_version ?? $updates->no_update[$slug]->version;

				if (empty($name))  $name = 'Trigger Reinstall';
				if (empty($title)) $title = 'Reinstall %1$s (%2$s)'."\n(activate \"new version\" update for the current version)";
				$linkURL = $this->add_admin_action_link( 'reinstall_plugin', $slug, $goto );
				return sprintf(
						'<a href="%1$s" data-tooltip title="%2$s">%3$s</a>',
						$linkURL,
						esc_attr( sprintf( __( "{$title}", $this->plugin->PLUGIN_TEXTDOMAIN ), $pluginId,$version ) ),
						'<span title="'.$source.'" class="dashicons dashicons-controls-repeat" style="color:'.$color.';"></span>'.
						sprintf( __( "{$name}", $this->plugin->PLUGIN_TEXTDOMAIN ), $pluginId,$slug ),

				);
			}
			return '';
		}


		/**
		 * reinstall_plugin action
		 *
		 * @param mixed $plugin plugin slug
		 * @return void
		 */
		public function reinstall_plugin_action($plugin)
		{
			$updates = \get_site_transient('update_plugins');

			if ($updates && isset($updates->no_update[$plugin]))
			{
				$item = $updates->no_update[$plugin];
				unset($updates->no_update[$plugin]);
				$item->version = '0.0.0';
				$updates->response[$plugin] = $item;
				$updates->last_checked = time();
				\set_site_transient('update_plugins', $updates);
			}
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new plugin_reinstall($this);
?>

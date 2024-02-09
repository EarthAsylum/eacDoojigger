<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\maintenance_mode', false) )
{
	/**
	 * Extension: maintenance_mode - put site in scheduled maintenance - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2021 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class maintenance_mode extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '23.1022.1';

		const DEFAULT_HTML = 	"[PageHeader]\n[PageContent]\n".
								"<div style='text-align:center;padding:5em 0;'>\n".
								"\t<h1>[BlogName]<br>[BlogDescription]</h1>\n".
								"\t<h2>This site is currently undergoing scheduled maintenance.</h2>\n".
								"\t<h3>We're sorry for the inconvenience. Please check back soon.</h3>\n".
								"</div>\n".
								"[/PageContent]\n[PageFooter]";

		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL | self::DEFAULT_DISABLED);

			$this->registerExtension( $this->className );

			if ($this->is_admin())
			{
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			$this->plugin->rename_option('adminMaintenanceHtml','maintenance_mode_html');
			$this->registerExtensionOptions( $this->className,
				[
						'maintenance_mode_html' 	=> array(
										'type'		=> 	'codeedit-html',
										'label'		=> 	"Maintenance Mode Message",
										'default'	=> 	self::DEFAULT_HTML,
										'info'		=> 	"Available shortcodes: [BlogName], [BlogDescription], [PageHeader], [PageContent], [PageFooter]",
										'sanitize'	=>	false,
										'validate'	=>	'wp_kses_post'
									),
				]
			);

			if (is_multisite() && $this->plugin->is_network_enabled())
			{
				if ( !is_network_admin() && $this->is_network_enabled() )
				{	// disable 'enabled' option on sites when network activated
					$this->registerExtensionOptions($this->className,[
						$this->enable_option		=> array(
										'type'		=>	'hidden',
										'label'		=>	'Enabled',
										'default'	=>	'Enabled (admin)',
										'info'		=>	'Network Enabled'
									)
						]
					);
				}
				if ( is_network_admin() )
				{
					$this->add_filter( 'options_form_post_'.$this->enable_option, array($this, 'form_network_check_status'), 10, 4 );
				}
			}
		}


		/**
		 * Add help tab on admin page
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
			if (!$this->plugin->isSettingsPage('General')) return;
			include 'includes/maintenance_mode.help.php';
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			$this->plugin->logDebug($this->get_option($this->enable_option),$this->enable_option);
			if ( ! parent::initialize() ) return; // disabled
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			/**
			 * check maintenance mode
			 */
			if ( ! is_user_logged_in() || ! current_user_can('edit_themes') )
			{
				remove_action( 'wp_head', 'wp_resource_hints', 2 ); // removes dns-prefetch which causes WC to call get_cart()
				add_action('wp', function() // get_header
					{
						if (defined('REST_REQUEST') || defined('XMLRPC_REQUEST') || stripos($_SERVER['REQUEST_URI'],'wp-json/') !== false)
						{
							wp_die(
								new \WP_Error('scheduled_maintenance','This site is currently undergoing scheduled maintenance','data'),
								get_bloginfo('name').' Scheduled Maintenance',503
							);
						}
						$content = $this->get_option('maintenance_mode_html') ?: $this->get_network_option('maintenance_mode_html');
						$content = do_shortcode(stripslashes($content));
						header("Cache-Control: no-cache, must-revalidate",true);
						header("Pragma: no-cache",true);
						header("Expires: Thu, 1 Jan 1970 00:00:00 GMT",true);
						status_header( 503 );
						die($content);
						//	wp_die(
						//		$content,
						//		get_bloginfo('name').' Scheduled Maintenance',503
						//	);
					}
				);
			}
		}


		/**
		 * Add shortcodes- called from main plugin
		 *
		 * @return	void
		 */
		public function addShortcodes(): void
		{
			add_shortcode( 'BlogName', 			function() {return \get_option('blogname');} );
			add_shortcode( 'BlogDescription', 	function() {return \get_option('blogdescription');} );
			add_shortcode( 'PageHeader', 		function() {return $this->plugin->get_page_header( 'scheduled-maintenance' );} );
			add_shortcode( 'PageFooter', 		function() {return $this->plugin->get_page_footer( 'scheduled-maintenance' );} );
			add_shortcode( 'PageContent',		function($atts = null, string $defaultContent = '', string $tag = '')
				{
					$a = shortcode_atts(['id' => false], $atts);
					if ($a['id'])
					{
						if (intval( $a['id'] ))
						{
							if ( class_exists( '\Elementor\Plugin' ) ) {
								if ($content = \Elementor\Plugin::$instance->frontend->get_builder_content( $a['id'] )) {
									return apply_filters('the_content', $post->post_content);
								}
							}
							if ($post = get_post( $a['id'] )) {
								return apply_filters('the_content', $post->post_content);
							}
						}
						else
						{
							if ($post = $this->plugin->get_post_by_slug( $a['id'] )) {
								return apply_filters('the_content', $post->post_content);
							}
						}
					}
					if ($post = $this->plugin->get_post_by_slug( 'scheduled-maintenance' ))
					{
						return apply_filters('the_content', $post->post_content);
					}
					return apply_filters('the_content', $defaultContent);
				}
			);
		}


		/**
		 * filter for options_form_post_
		 *
		 * @param $value - the value POSTed
		 * @param $fieldName - the name of the field/option
		 * @param $metaData - the option metadata
		 * @return mixed
		 */
		public function form_network_check_status($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return;

			$this->plugin->forEachNetworkSite(function() use ($value)
				{
					if ($value == 'Enabled') {
						// was not enabled by site, enable
						if ($this->get_option($this->enable_option) != 'Enabled') {
							$this->update_option($this->enable_option,'Enabled (admin)');
							$this->delete_transient( $this->plugin::PLUGIN_EXTENSION_TRANSIENT );
						}
					} else {
						// was enabled by administrator, disable
						if ($this->get_option($this->enable_option) == 'Enabled (admin)') {
							$this->update_option($this->enable_option,'');
							$this->delete_transient( $this->plugin::PLUGIN_EXTENSION_TRANSIENT );
						}
					}
					$this->plugin->flush_caches();
				}
			);
			return $value;
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new maintenance_mode($this);
?>

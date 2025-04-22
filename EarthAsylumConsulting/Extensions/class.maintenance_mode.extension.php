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
	 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class maintenance_mode extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '25.0422.1';

		/**
		 * @var string default maintenance_mode html
		 */
		const DEFAULT_HTML = 	"[PageHeader]\n[PageContent]\n".
								"<div style='background:#fff;color:#000;text-align:center;padding:3em;'>\n".
								"\t<div class='scheduled-maintenance'>\n".
								"\t\t<h1>[BlogName]<br>[BlogDescription]</h1>\n".
								"\t\t<h2>This site is currently undergoing scheduled maintenance.</h2>\n".
								"\t\t<h3>We're sorry for the inconvenience. Please check back soon.</h3>\n".
								"\t</div>\n</div>\n".
								"[/PageContent]\n[PageFooter]";

		/**
		 * @var string active until
		 */
		private $active = 'Inactive';


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
			if (is_admin())
			{
				add_action('admin_init', function()
				{
					// Register plugin options when needed
					$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
					// Add contextual help
					$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
					// check maintenance mode
					$this->check_maintenance_mode();
				});
			} else {
				// check maintenance mode
				add_action('init', array($this,'check_maintenance_mode'));
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
								'info'		=> 	"Available shortcodes: [BlogName], [BlogDescription], [PageHeader], [PageTemplate], [PageContent], [PageFooter]",
								'sanitize'	=>	false,
								'validate'	=>	'wp_kses_post'
						),
						'maintenance_mode_time' 	=> array(
								'type'		=> 	'number',
								'label'		=> 	"Maintenance Mode Time",
								'default'	=> 	0,
								'info'		=> 	"Number of minutes to remain in maintenance mode.<br>" .
												"<small>(will reset to 0 once activated) ".
												"1 Hour = ".(HOUR_IN_SECONDS/60).", " .
												"1 Day = ". (DAY_IN_SECONDS/60).", " .
												"1 Week = ".(WEEK_IN_SECONDS/60).".</small>",
								'validate'	=>	function($expire) {
									if (is_numeric($expire) && $expire > 0) {
										$expire = $expire * MINUTE_IN_SECONDS;
										$until = wp_date($this->plugin->date_time_format,time() + $expire);
										$this->plugin->set_transient('maintenance_mode',$until,$expire);
										$this->do_action('flush_caches');
										$this->page_reload();
									} else {
										$this->plugin->delete_transient('maintenance_mode');
									}
									return 0;	// reset to 0
								},
						),
						'_maintenance_mode_status' 	=> array(
								'type'		=> 	'display',
								'label'		=> 	"Maintenance Mode Status",
								'default'	=>	($this->active) ? $this->active : 'Inactive',
						),
				]
			);

			if (is_multisite() && $this->plugin->is_network_enabled())
			{
				if ( is_network_admin() )
				{
					$this->add_filter( 'options_form_post_'.$this->enable_option, array($this, 'network_check_enabled'), 10, 4 );
				}
				else if ( $this->is_network_enabled() )
				{	// disable 'enabled' option on sites when network activated
					$this->isEnabled(true,true);
					$this->registerExtensionOptions($this->className,[
						$this->enable_option		=> array(
										'type'		=>	'hidden',
										'label'		=>	'Enabled',
										'default'	=>	'Network Enabled',
										'info'		=>	'Network Enabled'
									)
						]
					);
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
			if ( ! parent::initialize() ) return; // disabled
		}


		/**
		 * check maintenance mode
		 *
		 * @return	void
		 */
		public function check_maintenance_mode()
		{
			if ($this->active = $this->getActiveUntil()) {
				$this->active = 'Active Until '.$this->active;
				$this->add_admin_notice('Maintenance Mode - '.$this->active,'success');
			} else {
				$this->active = false;
				$this->isEnabled(false,true);
				if ($this->enable_option) {
					if ($enabled = $this->is_option($this->enable_option)) {
						$this->network_check_enabled('',$this->enable_option,null,$enabled);
					}
				}
				return;
			}

			if ( is_user_logged_in() && current_user_can('edit_themes') ) {
				return;
			}

			/**
			 * action {classname}_scheduled_maintenance
			 */
			$this->do_action('scheduled_maintenance');

			// removes dns-prefetch which causes WC to call get_cart()
			remove_action( 'wp_head', 'wp_resource_hints', 2 );

			add_action('wp', function() // get_header
				{
					nocache_headers();
					header( 'Retry-After: 600' );
					if ( defined('REST_REQUEST') || defined('XMLRPC_REQUEST') || wp_is_json_request() || wp_is_xml_request() )
					{
						wp_die(
							new \WP_Error('scheduled_maintenance','This site is currently undergoing scheduled maintenance'),
							get_bloginfo('name').' Scheduled Maintenance',503
						);
					}
					$content = $this->getMaintenanceMessage();
					status_header( 503 );
					die($content);
				}
			);
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
			// get header-scheduled-maintenance.php or default header
			add_shortcode( 'PageHeader', 		function() {return $this->plugin->get_page_header( 'scheduled-maintenance' );} );
			// get footer-scheduled-maintenance.php or default footer
			add_shortcode( 'PageFooter', 		function() {return $this->plugin->get_page_footer( 'scheduled-maintenance' );} );
			// get scheduled-maintenance.php or named template
			add_shortcode( 'PageTemplate',		function($atts = null, string $template = '', string $tag = '')
				{
					if (empty($template)) $template = 'scheduled-maintenance';
					return $this->plugin->get_page_template( $template );
				}
			);
			add_shortcode( 'PageContent',		function($atts = null, string $defaultContent = '', string $tag = '')
				{
					$a = shortcode_atts(['id' => false], $atts);
					if ($a['id'])
					{
						// [PageContent id=n]
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
						// [PageContent id=slug]
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
					//return apply_filters('the_content', $defaultContent);
					return do_shortcode($defaultContent);
				}
			);
		}


		/**
		 * get maintenance mode html
		 *
		 * @return	string
		 */
		public function getMaintenanceMessage()
		{
			$content = $this->get_option('maintenance_mode_html') ?: $this->get_network_option('maintenance_mode_html');
			return do_shortcode(stripslashes($content));
		}


		/**
		 * get active (or not) until
		 *
		 * @return	string
		 */
		public function getActiveUntil()
		{
			if ($active = $this->plugin->get_transient('maintenance_mode'))
			{
				return $active;
			}
			if ($active = $this->plugin->get_site_transient('maintenance_mode'))
			{
				return $active;
			}
			return false;
		}


		/**
		 * filter for options_form_post_
		 *
		 * @param $value - the value POSTed
		 * @param $fieldName - the name of the field/option
		 * @param $metaData - the option metadata
		 * @return mixed
		 */
		public function network_check_enabled($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return;

			$this->plugin->forEachNetworkSite(function() use ($value,$fieldName)
				{
					if ($value == 'Enabled') {
						// was not enabled by site administrator, enable
						if ($this->get_option($fieldName) != 'Enabled') {
							$this->update_option($fieldName,'Network Enabled');
							$this->plugin->delete_transient( $this->plugin::PLUGIN_EXTENSION_TRANSIENT );
						}
					} else {
						// was enabled by network administrator, disable
						if ($this->get_option($fieldName) == 'Network Enabled') {
							$this->update_option($fieldName,'');
							$this->plugin->delete_transient( $this->plugin::PLUGIN_EXTENSION_TRANSIENT );
						}
					}
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

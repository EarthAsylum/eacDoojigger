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
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 */

	class maintenance_mode extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '26.0908.1';

		/**
		 * @var string default maintenance_mode html
		 */
		const DEFAULT_HTML = 	"[PageHeader]\n[PageContent]\n".
								"<div style='background:#fff;color:#000;text-align:center;padding:3em;'>\n".
								"\t<div class='scheduled-maintenance'>\n".
								"\t\t<h1>[BlogName]<br>[BlogDescription]</h1>\n".
								"\t\t<h2>This site is currently undergoing scheduled maintenance.</h2>\n".
								"\t\t<h3>\n".
								"\t\t\tWe're sorry for the inconvenience.\n".
								"\t\t\tPlease check back after [UntilTime]g:i a[/UntilTime].\n".
								"\t\t</h3>\n".
								"\t</div>\n</div>\n".
								"[/PageContent]\n[PageFooter]";


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL | self::ALLOW_NON_PHP | self::DEFAULT_DISABLED);

			$this->registerExtension( $this->className );
			if ($this->is_admin())
			{
				add_action('admin_init', function()
				{
					// Register plugin options when needed
					$this->add_action( "options_settings_page", 		array($this, 'admin_options_settings') );
					// Add contextual help
					$this->add_action( 'options_settings_help', 		array($this, 'admin_options_help') );
					// check maintenance mode (after processing options)
					$this->add_action( 'options_settings_page_footer', 	array($this, 'check_maintenance_mode') );
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
			$isActive = $this->getActiveUntil(true);
			$isActive = ($isActive) ? 'Active Until '.$isActive : 'Inactive';

			$this->registerExtensionOptions( $this->className,
				[
						'maintenance_mode_html' 	=> array(
								'type'		=> 	'codeedit-html',
								'label'		=> 	"Maintenance Mode Message",
								'default'	=> 	self::DEFAULT_HTML,
								'info'		=> 	"Available shortcodes: [BlogName], [BlogDescription], [UntilTime], ".
												"[PageHeader], [PageTemplate], [PageContent], [PageFooter]",
								'sanitize'	=>	false,
								'validate'	=>	'wp_kses_post'
						),
						'maintenance_mode_time' 	=> array(
								'type'		=> 	'number',
								'label'		=> 	"Maintenance Mode Time",
								'default'	=> 	0,
								'info'		=> 	"Number of minutes to remain in maintenance mode.<br>" .
												"<small>(will reset to 0 once expired) ".
												"1 Hour = ".(HOUR_IN_SECONDS/60).", " .
												"1 Day = ". (DAY_IN_SECONDS/60).", " .
												"1 Week = ".(WEEK_IN_SECONDS/60).".</small>",
								'attributes'=>	['min="0"', 'max="99999"','step="1"'],
								'validate'	=>	[$this,'maintenance_mode_time'],
						),
						'_maintenance_mode_status' 	=> array(
								'type'		=> 	'display',
								'label'		=> 	"Maintenance Mode Status",
								'default'	=>	$isActive,
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
		 * When maintenance_mode_time is submitted
		 *
		 * @return	void
		 */
		public function maintenance_mode_time($minutes)
		{
			if (is_numeric($minutes) && $minutes > 0) {
				$this->do_action('flush_caches');
				$expires = time() + ($minutes * MINUTE_IN_SECONDS);
				$this->plugin->set_transient('maintenance_mode',$expires,$expires);
				$this->page_reload();
			} else {
				$this->plugin->delete_transient('maintenance_mode');
				return 0;
			}
			return $minutes;
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
			if ($isActive 	= $this->getActiveUntil(true)) {
				$isActive 	= 'Active Until '.$isActive;
				$this->isEnabled(true,true);
				$this->add_admin_notice('Maintenance Mode - '.$isActive,'success');
			} else {
				$isActive 	= false;
				if (!$this->is_admin()) {
					$this->isEnabled(false,true);
				}
				if ($this->enable_option) {
					if ($enabled = $this->is_option($this->enable_option)) {
						$this->network_check_enabled('',$this->enable_option,null,$enabled);
					}
				}
				$this->delete_option('maintenance_mode_time');
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

			add_action('wp',						array($this, 'force_maintenance_mode'));
			add_action('xmlrpc_enabled',			array($this, 'force_maintenance_mode'));
			add_action('rest_pre_serve_request',	array($this, 'force_maintenance_mode'));
		}


		/**
		 * force maintenance mode
		 *
		 * @return	void
		 */
		public function force_maintenance_mode()
		{
			status_header( 503 );
			nocache_headers();
			$this->setRetryHeader();

			if ( wp_is_json_request() || (defined( 'REST_REQUEST' ) && REST_REQUEST))
			{
				$_SERVER['CONTENT_TYPE'] = 'application/json';
			}

			if ( wp_is_json_request() || (defined( 'REST_REQUEST' ) && REST_REQUEST)
			||   wp_is_xml_request() || (defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) )
			{
				wp_die(
					new \WP_Error('scheduled_maintenance','This site is currently undergoing scheduled maintenance'),
					get_bloginfo('name').' Scheduled Maintenance',['response'=>503,'exit'=>true]
				);
			}

			if (\EarthAsylumConsulting\is_non_code_request()) die();

			$content = $this->getMaintenanceMessage();
			die($content);
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
			// get the formatted expiration date/time
			add_shortcode( 'UntilTime',			function($atts = null, string $format = '', string $tag = '')
				{
					return $this->getActiveUntil(true,$format);
				}
			);
			// get header-scheduled-maintenance.php or default header
			add_shortcode( 'PageHeader', 		function()
				{
					return $this->plugin->get_page_header( 'scheduled-maintenance' );
				}
			);
			// get footer-scheduled-maintenance.php or default footer
			add_shortcode( 'PageFooter', 		function()
				{
					return $this->plugin->get_page_footer( 'scheduled-maintenance' );
				}
			);
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
		 * @param bool $asString - return wp_date() string
		 * @param string $format - wp_date format string
		 * @return int|string
		 */
		public function getActiveUntil($asString = false, $format = '')
		{
			if ($asString && empty($format))
			{
				$format = $this->plugin->date_time_format;
			}
			if ($active = $this->plugin->get_transient('maintenance_mode'))
			{
				return ($asString) ? wp_date($format,$active) : $active;
			}
			if ($active = $this->plugin->get_site_transient('maintenance_mode'))
			{
				return ($asString) ? wp_date($format,$active) : $active;
			}
			return '';
		}


		/**
		 * set Retry-After header
		 *
		 */
		private function setRetryHeader()
		{
			if ($httpDate = $this->getActiveUntil())
			{
				$httpDate = gmdate('D, d M Y H:i:s', $httpDate) . ' GMT';
				header('Retry-After: '.$httpDate);
			}
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

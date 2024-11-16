<?php
namespace EarthAsylumConsulting\Extensions;
/*
 * Custom APIs may add code similar to this (after authentication)
 *		// allow origin in CORS
 *		add_filter( 'allowed_http_origins', function ($allowed) {
 *			$origin  = (is_ssl()) ? 'https://' : 'http://';
 *			$origin .= gethostbyaddr($this->plugin->getVisitorIP());
 *			$allowed[] = $origin;
 *			return $allowed;
 *		});
 */

if (! class_exists(__NAMESPACE__.'\security_cors', false) )
{
	/**
	 * Extension: fraudguard - FraudGuard API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_cors extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1112.1';

		/**
		 * @var string extension tab name
		 */
		const TAB_NAME 			= 'Security';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		=
			"<abbr title='Cross-origin resource sharing (CORS) is a security mechanism that allows a web page to access ".
			"resources from a different domain than the one that served the page.'>Server Side CORS</abbr>";

		/**
		 * @var array host dns IP addresses
		 */
		public $host_ips 		= [];


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ADMIN | self::ALLOW_NETWORK | self::ALLOW_NON_PHP );

			// must have security extension enabled
			if (! $this->isEnabled('security')) return false;

			if ($this->is_admin())
			{
				$this->registerExtension( $this->className );
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
			require 'includes/security_cors.options.php';
		}


		/**
		 * Add help tab on admin page
		 *
		 * @todo - add contextual help
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
		//	if (!$this->plugin->isSettingsPage(self::TAB_NAME)) return;
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled
		$this->delete_option('security_server_cors_extension_enabled');

			if ( $this->plugin->isSettingsPage(self::TAB_NAME))
			{
				if ( is_multisite() && !is_network_admin() &&
					(!defined( 'WP_CLI' ) && !defined( 'DOING_AJAX' ) && !defined( 'DOING_CRON' )) )
				{
					if ($this->security->isNetworkPolicy('secCorsOpt')) 	$this->delete_option('secCorsOpt');
				}
			}

			if ($this->security->isPolicyEnabled('secCorsOpt','host_origin'))
			{
				$this->host_ips = $this->get_transient('host_ips',[]);
				if (! ($this->host_ips && is_array($this->host_ips)) )
				{
					$this->host_ips = [];
					$host = dns_get_record($this->plugin->varServer('host'),DNS_A+DNS_AAAA);
					foreach ($host as $ip) {
						$this->host_ips[] = $ip['ipv6'] ?? $ip['ip'];
					}
					if ($host = file_get_contents('https://ipv4.icanhazip.com/')) {
						$this->host_ips[] = trim($host);
					}
					if ($host = file_get_contents('https://ipv6.icanhazip.com/')) {
						$this->host_ips[] = trim($host);
					}
					$this->host_ips = array_unique($this->host_ips);
					$this->set_transient('host_ips',$this->host_ips,DAY_IN_SECONDS);
					$this->logDebug($this->host_ips,$this->plugin->varServer('Host').' Host IP Address');
				}
			}
			else
			{
				$this->delete_transient('host_ips');
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if ($this->security->isPolicyEnabled('secCorsOpt','rest')) {
				add_action('rest_api_init', 					array($this,'validate_cors_origin'), 50);
			}
			if ($this->security->isPolicyEnabled('secCorsOpt','xml')) {
				add_action('xmlrpc_enabled', 					array($this,'validate_cors_origin'), 50);
			}

			if ($this->plugin->doing_ajax()) {
				if ($this->security->isPolicyEnabled('secCorsOpt','ajax')) {
					if (!$this->plugin->varServer('X-Requested-With')) {
						$this->do_action('report_threat','Invalid XMLHttp Request',100);
						wp_die( $this->plugin->access_denied('Invalid XMLHttp Request') );
					}
					add_action('init', 							array($this,'validate_cors_origin'), 50);
				}
			}
			else
			if ($this->security->isPolicyEnabled('secCorsOpt','post')) {
				if (in_array($_SERVER['REQUEST_METHOD'],['POST','PUT','PATCH','DELETE'])) {
					add_action('wp', 							function() {
						if (!defined('REST_REQUEST') && !defined('XMLRPC_REQUEST')) {
							$this->validate_cors_origin();
						}
					}, 50);
				}
			}
		}


		/**
		 * Validate origin IP when origin is this host
		 *
		 * @param	string $origin origin doman (sans protocol)
		 * @return	bool
		 */
		private function validate_local_origin($origin)
		{
			static $host = null;
			if (!$host) $host = $this->plugin->varServer('Host');

			if ($origin == $host && !empty($this->host_ips))
			{
				$ipAddress 	= $this->plugin->getVisitorIP();
				$ipAsHost	= (is_ssl()) ? 'https://' : 'http://';
				$ipAsHost	.= $ipAddress;
				if (!in_array($ipAddress,$this->host_ips) && !is_allowed_http_origin($ipAsHost)) {
					$this->do_action('report_threat','cross-origin access denied from '.$origin." ({$ipAddress})",100,400);
				}
			}
		}


		/**
		 * Set origin-specific CORS headers
		 *
		 */
		public function validate_cors_origin(): void
		{
			static $once = false;
			if ($once) return;
			$once = true;


			// should we trust browser sec headers?
		//	if ( ($this->plugin->varServer('Sec-Fetch-Mode') == 'cors') &&
		//	     ($this->plugin->varServer('Sec-Fetch-Site') == 'same-origin') ) return;

			$allowed_origins = $this->security->mergePolicies('secAllowCors');
			// add server IP addresses as origins
			foreach ($this->host_ips as $host) {
				$allowed_origins[] = 'http://'.$host;
				$allowed_origins[] = 'https://'.$host;
			}
			$allowed_origins = array_unique($allowed_origins);

			// what origins are allowed
			if (!empty($allowed_origins)) {
				add_filter( 'allowed_http_origins', function ($allowed) use($allowed_origins) {
					$allowed_origins = array_merge($allowed,$allowed_origins);
					return $allowed_origins;
				});
			}

			// should we trust browser referer header?
			if ($this->security->isPolicyEnabled('secCorsOpt','referer')) {
				add_filter( 'http_origin', function ($origin) {
					if (empty($origin)) {
						if ($origin = $this->plugin->varServer('HTTP_REFERER')) {
							$origin = parse_url($origin);
							$this->validate_local_origin($origin['host']);
							$origin = $origin['scheme'].'://'.$origin['host'];
						}
					}
					return $origin;
				},101);
			}

			// should we trust IP address headers?
			if ($this->security->isPolicyEnabled('secCorsOpt','ip_address')) {
				add_filter( 'http_origin', function ($origin) {
					if (empty($origin)) {
						$origin = (is_ssl()) ? 'https://' : 'http://';
						$origin .= gethostbyaddr($this->plugin->getVisitorIP());
					}
					return $origin;
				},102);
			}

			// is this origin allowed? Match origin ends with allowed
			add_filter( 'allowed_http_origin', function($origin, $origin_arg) {
				if (empty($origin) && !empty($origin_arg)) {	// $origin not allowed (so far)
					foreach(get_allowed_http_origins() as $allowed) {
						if (str_ends_with($origin_arg,$allowed)) {
							return $origin_arg;
						}
					};
				}
				return $origin;
			},10,2);

			// validate origin = this host [api spoofing origin] (but not for ajax)
			if (!$this->plugin->varServer('X-Requested-With')) {
				$this->validate_local_origin($this->plugin->varServer('Origin'));
			}

			$action = (current_action() == 'rest_api_init') ? 'rest_pre_serve_request' : current_action();
			remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		//	add_filter( $action, function($value) {
				$origin = get_http_origin();
				if ($this->security->match_disabled_uris('secExcludeCors') || is_allowed_http_origin($origin)) {
					header( 'Access-Control-Allow-Origin: ' . $origin );
					header( 'Access-Control-Allow-Methods: ' .
						$this->plugin->varServer('REQUEST_METHOD') ?: 'OPTIONS, GET, POST, PUT, PATCH, DELETE' );
					header( 'Access-Control-Allow-Credentials: true' );
					header( 'Vary: Origin', false );
					$this->logDebug($origin,'cross-origin access allowed');
				} else {
					header( 'Access-Control-Allow-Origin: ' . site_url() );
					$this->do_action('report_threat','cross-origin access denied from '.$origin,100);
					wp_die( $this->plugin->access_denied('cross-origin access denied from '.$origin) );
				}
		//		return $value;
		//	},1000);

			return;
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_cors($this);
?>

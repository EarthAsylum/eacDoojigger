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

if (! class_exists(__NAMESPACE__.'\security_server_cors', false) )
{
	/**
	 * Extension: fraudguard - FraudGuard API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_server_cors extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1005.1';

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
			parent::__construct($plugin, self::ALLOW_ADMIN | self::ALLOW_NETWORK | self::ALLOW_NON_PHP | self::DEFAULT_DISABLED);

			// must have security extension enabled
			if (! $this->isEnabled('security')) return false;

			if ($this->is_admin())
			{
				$this->registerExtension( [ $this->className, basename(__DIR__) ] );
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
			require 'includes/server_cors.options.php';
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
		//	if (!$this->plugin->isSettingsPage('Security')) return;
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled

			if ( $this->plugin->isSettingsPage('Security'))
			{
				if ( is_multisite() && !is_network_admin() &&
					(!defined( 'WP_CLI' ) && !defined( 'DOING_AJAX' ) && !defined( 'DOING_CRON' )) )
				{
					if ($this->security->isNetworkPolicy('secCorsOpt')) 	$this->delete_option('secCorsOpt');
				}
			}

			if ($this->security->isPolicyEnabled('secCorsOpt','host_origin'))
			{
				if (! $this->host_ips = $this->get_site_transient('host_ips'))
				{
					$this->host_ips = [];
					$host = dns_get_record($this->plugin->varServer('host'),DNS_A+DNS_AAAA);
					foreach ($host as $ip) {
						$this->host_ips[] = $ip['ipv6'] ?? $ip['ip'];
					}
					$this->set_site_transient('host_ips',DAY_IN_SECONDS);
					$this->logDebug($this->host_ips,__METHOD__);
				}
			}
			else
			{
				$this->delete_site_transient('host_ips');
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if ($this->security->isPolicyEnabled('secCorsOpt','rest')) {
				add_action('rest_api_init', 					array($this,'rest_api_cors'), 999);
			}
			if ($this->security->isPolicyEnabled('secCorsOpt','xml')) {
				if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
					add_action('init', 							array($this,'rest_api_cors'), 999);
				}
			}
			if ($this->security->isPolicyEnabled('secCorsOpt','ajax')) {
				if ($this->plugin->doing_ajax()) {
					if (!$this->plugin->varServer('X-Requested-With')) {
						wp_die( $this->plugin->request_forbidden('Invalid XMLHttp Request') );
					}
					add_action('init', 							array($this,'rest_api_cors'), 999);
				}
			}
		}


		/**
		 * Set origin-specific CORS headers (rest_api_init -> rest_pre_serve_request)
		 *
		 * @param	$user user data
		 * @return	WP_Error or $user
		 */
		public function rest_api_cors()
		{
			// should we trust browser sec headers?
		//	if ( ($this->plugin->varServer('Sec-Fetch-Mode') == 'cors') &&
		//	     ($this->plugin->varServer('Sec-Fetch-Site') == 'same-origin') ) return;

			// validate origin = this host [api spoofing origin] (but not for ajax)
			if ($this->security->isPolicyEnabled('secCorsOpt','host_origin')
			&& !$this->plugin->varServer('X-Requested-With')
			&& !empty($this->host_ips)
			){
				if ($this->plugin->varServer('Origin') == $this->plugin->varServer('Host')) {
					if (!in_array($this->plugin->getVisitorIP(),$this->host_ips)) {
						$this->do_action('report_abuse','invalid origin to ip address');
						wp_die( $this->plugin->request_forbidden('Invalid origin/address ('.$origin.', '.$this->plugin->getVisitorIP().')') );
					}
				}
			}

			$allowed_origins = $this->security->mergePolicies('secAllowCors','');

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
							$origin = $origin['scheme'].'://'.$origin['host'];
						}
					}
					return $origin;
				});
			}

			// should we trust IP address headers?
			if ($this->security->isPolicyEnabled('secCorsOpt','ip_address')) {
				add_filter( 'http_origin', function ($origin) {
					if (empty($origin)) {
						$origin = (is_ssl()) ? 'https://' : 'http://';
						$origin .= gethostbyaddr($this->plugin->getVisitorIP());
					}
					return $origin;
				});
			}

			// is this origin allowed? Match origin ends with allowed
			add_filter( 'allowed_http_origin', function($origin, $origin_arg) {
				if (empty($origin) && !empty($origin_arg)) {	// $origin not allowed (so far)
					foreach(get_allowed_http_origins() as $allowed) {
						if (substr_compare($origin_arg, $allowed, - strlen($allowed)) === 0) {
							return $origin_arg;
						}
					};
				}
				return $origin;
			},10,2);

			$action = (current_action() == 'rest_api_init') ? 'rest_pre_serve_request' : 'wp_loaded';
			remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
			add_filter( $action, function($value) {
				$origin = get_http_origin();
				if ($this->security->match_disabled_uris('secExcludeCors') || is_allowed_http_origin($origin)) {
					$this->logDebug($origin,'CORS: allowed origin');
					header( 'Access-Control-Allow-Origin: ' . $origin );
					header( 'Access-Control-Allow-Methods: ' .
						$this->plugin->varServer('REQUEST_METHOD') ?: 'OPTIONS, GET, POST, PUT, PATCH, DELETE' );
					header( 'Access-Control-Allow-Credentials: true' );
					header( 'Vary: Origin', false );
				} else {
					$this->logError($origin,'CORS: denied origin');
					header( 'Access-Control-Allow-Origin: ' . site_url() );
					$this->do_action('report_abuse','prohibited cross-origin request');
					wp_die( $this->plugin->request_forbidden('Cross-Origin access denied from '.$origin) );
				}
				return $value;
			},20);
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_server_cors($this);
?>

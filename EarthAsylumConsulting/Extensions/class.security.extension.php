<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_extension', false) )
{
	/**
	 * Extension: security - security features - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.0921.1';

		/**
		 * @var string extension alias
		 */
		const ALIAS 			= 'security';

		/**
		 * @var string path to .htaccess (allow access)
		 */
		private $htaccess 		= false;

		/**
		 * @var object wp-config-transformer
		 */
		private $wpConfig 		= false;

		/**
		 * @var string path to .user.ini (allow access)
		 */
		private $userIni 		= false;

		/**
		 * @var array what .htaccess rules are set
		 */
		private $security_rules = [];

		/**
		 * @var string replacement login uri
		 */
		private $login_uri 		= null;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL | self::ALLOW_NON_PHP);

			if ($this->is_admin())
			{
				$this->registerExtension( [$this->className, 'Security'] );
				$this->registerExtension( ['Server_Side_CORS', 'Security'] );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}

			// add additional css when our settings stylesheet loads.
			if ($this->plugin->isSettingsPage('Security'))
			{
				$this->add_action('admin_enqueue_styles', function($styleId)
				{
					$style =
						'.dashicons-networking {position: absolute; top: 1px; right: 2px; font-size: 16px; opacity: .5;}'.
						'#secPassLock {width: 85%; max-width: 30em;}'.
						'#secPassLock-ticks {display: flex; width: 86%; max-width: 38.5em;}'.

						'#secPassTime {width: 85%;}'.
						'#secPassTime-ticks {display: flex; width: 86%;}'.

						'#secHeartbeat {width: 85%;}'.
						'#secHeartbeat-ticks {display: flex; width: 86%;}';
					wp_add_inline_style( $styleId, $style );
				});
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
			include 'includes/security.options.php';
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
		//	include 'includes/security.help.php';
		}


		/**
		 * destructor method
		 *
		 * @return 	void
		 */
		public function __destruct()
		{
			if ($this->plugin->isSettingsPage('Security') && !empty($this->security_rules))
			{
				if (!is_multisite() || is_network_admin())
				{
					$this->update_site_option('security_extension_rules',$this->security_rules);
				}
			}
			parent::__destruct();
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
				// see if we can get to the config files (only single site or network admin)
				$this->htaccess = $this->plugin->htaccess_handle();
				$this->wpConfig = $this->plugin->wpconfig_handle();
				$this->userIni 	= $this->plugin->userini_handle();

				if ( is_multisite() && !is_network_admin() &&
					(!defined( 'WP_CLI' ) && !defined( 'DOING_AJAX' ) && !defined( 'DOING_CRON' )) )
				{
					if ($this->isNetworkPolicy('secDisableRSS')) 	$this->delete_option('secDisableRSS');
					if ($this->isNetworkPolicy('secUnAuthRest')) 	$this->delete_option('secUnAuthRest');
					if ($this->isNetworkPolicy('secDisableXML')) 	$this->delete_option('secDisableXML');
					if ($this->isNetworkPolicy('secDisablePings')) 	$this->delete_option('secDisablePings');
					if ($this->isNetworkPolicy('secCodeEditor')) 	$this->delete_option('secCodeEditor');
					if ($this->isNetworkPolicy('secFileChanges')) 	$this->delete_option('secFileChanges');
					if ($this->isNetworkPolicy('secHeartbeat')) 	$this->delete_option('secHeartbeat');
					if ($this->isNetworkPolicy('secHeartbeatFE')) 	$this->delete_option('secHeartbeatFE');

					if ($this->isNetworkPolicy('secPassPolicy')) {
						$this->update_option('secPassPolicy', 		$this->mergePolicies('secPassPolicy',[],true));
					}
					if ($this->isNetworkPolicy('secCookies')) {
						$this->update_option('secCookies', 			$this->mergePolicies('secCookies',[],true));
					}
					// only use site_option
					$this->delete_option('secLoginUri');
				}
				// removed from this extension
				$this->delete_option('secAbuseIPDB_key');
				$this->delete_option('secAbuseIPDB_level');
			}

			// so we know if/what .htaccess rules have been set
			$this->security_rules = wp_parse_args(
				$this->get_site_option('security_extension_rules',[]),
				array(
					'secLoginUri'		=> false,
					'secDisableURIs'	=> false,
					'secBlockIP'		=> false,
					'secCookies'		=> false,
				)
			);
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if ($this->login_uri = $this->get_site_option('secLoginUri'))
			{
				//$this->login_uri = $this->mergePolicies('secLoginUri','')[0];
				add_filter( 'site_url', 					array($this, 'wp_login_filter'), 10, 4 );
				add_filter( 'network_site_url', 			array($this, 'wp_login_filter'), 10, 4 );
				add_action( 'login_init', 					array($this, 'wp_login_init') );
				add_filter( 'wp_redirect', 					array($this, 'wp_login_redirect' ), 10, 2 );
				add_filter( 'site_option_welcome_email', 	array($this, 'welcome_email_filter') );
			}

			if ($this->isPolicyEnabled('secPassPolicy'))
			{
				add_action( 'user_profile_update_errors', 	array($this, 'validate_password_policy'), 10, 3 );
				add_filter( 'registration_errors', 			array($this, 'validate_password_policy'), 10, 3 );
				add_action( 'validate_password_reset', 		array($this, 'validate_password_policy'), 10, 2 );

				add_action( 'woocommerce_save_account_details_errors', 	array($this, 'validate_password_policy'), 10, 2 );
				add_action( 'woocommerce_password_reset', 				array($this, 'validate_password_policy'), 10, 2 );
			}

			if ($this->isPolicyEnabled('secPassLock'))
			{
				add_action( 'wp_authenticate_user', 		array($this, 'validate_authentication_attempts'), 10, 2 );
				add_filter( 'login_redirect', 				function( $url, $query, $user ) {
					if (! is_wp_error( $user )) {
						$this->delete_transient('login_attempt_'.$user->ID);
					}
					return $url;
				}, 10, 3 );
			}

			if ($this->isPolicyEnabled('server_side_cors_extension_enabled'))
			{
				if ($this->isPolicyEnabled('secCorsOpt','rest')) {
					add_action('rest_api_init', 					array($this,'rest_api_cors'), 999);
				}
				if ($this->isPolicyEnabled('secCorsOpt','xml')) {
					if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
						add_action('init', 							array($this,'rest_api_cors'), 999);
					}
				}
				if ($this->isPolicyEnabled('secCorsOpt','ajax')) {
					if ( $this->plugin->doing_ajax() ) {
						add_action('init', 							array($this,'rest_api_cors'), 999);
					}
				}
			}

			if ($this->isPolicyEnabled('secDisableRSS'))
			{
				$this->disable_rss_feeds();
			}

			if ($this->isPolicyEnabled('secUnAuthRest'))
			{
				if ($this->isPolicyEnabled('secUnAuthRest','no-rest')) {
				//	add_filter( 'json_jsonp_enabled', 		'__return_false');
				//	add_filter( 'rest_enabled', 			'__return_false');	// deprecated
					add_filter( 'rest_jsonp_enabled', 		'__return_false' );
				}
				if ($this->isPolicyEnabled('secUnAuthRest','no-rest-index')) {
					add_filter( 'rest_index', 				array($this, 'disable_rest_list'), 999 );
					add_filter( 'rest_namespace_index', 	array($this, 'disable_rest_list'), 999 );
					add_filter( 'rest_route_data', function($available, $routes){return [];}, 999, 2 );
     			}
				if ($this->isPolicyEnabled('secUnAuthRest','no-rest-core')) {
					$this->disable_rest_core();
				}
				add_filter( 'rest_authentication_errors', 	array($this, "disable_rest"), 999, 1 );
				remove_action('wp_head', 'rest_output_link_wp_head');

				if ($this->isPolicyEnabled('secUnAuthRest','no-json')) {
					add_action('wp', 						array($this, 'disable_invalid_json'));
				}
			}

			if ($this->isPolicyEnabled('secDisableXML','no-xml'))
			{
				add_filter(	'xmlrpc_enabled', 				'__return_false');
				add_filter( 'xmlrpc_methods', 				array($this, "disable_xml"), 998 );
				remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
			}

			if ($this->isPolicyEnabled('secDisablePings','no-ping'))
			{
				// remove x-pingback HTTP header
				add_filter('wp_headers', 					function($headers) {
					unset($headers['X-pingback']);
					return $headers;
				});
				// disable pingbacks
				add_filter( 'xmlrpc_methods', 				array($this, "disable_pings"), 999 );
			}

			if ($this->isPolicyEnabled('secCodeEditor'))
			{
				$this->disable_code_edit();
			}

			if ($this->isPolicyEnabled('secFileChanges'))
			{
				$this->disable_file_mods();
			}

			if ($this->isPolicyEnabled('secDisableURIs'))
			{
				if ( ! $this->security_rules['secDisableURIs'] ) {
					$this->disable_uris();
				}
			}

			if ($this->isPolicyEnabled('secBlockIP'))
			{
				if ( ! $this->security_rules['secBlockIP'] ) {
					$this->block_ip_address();
				}
			}

			if ($this->isPolicyEnabled('secCookies'))
			{
				$this->security_rules['secCookies'] = false; // no longer using .htacess
			//	if ( ! $this->security_rules['secCookies'] ) {
					$this->add_action('http_headers_ready',	array($this, "checkCookieFlags"), 999  );
			//	}
			}

			if ($this->isPolicyEnabled('secHeartbeat'))
			{
				add_filter( 'heartbeat_settings', 			array($this, "set_hearttbeat")  );
			}

			if ($this->isPolicyEnabled('secHeartbeatFE'))
			{
				add_action( 'wp_enqueue_scripts', 			function() {wp_deregister_script( 'heartbeat' );} );
			}
		}


		/*
		 * Form post filters
		 */


		/**
		 * filter for options_form_post_secLoginUri
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secLoginUri($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return $value; 	// no change

			$this->security_rules[$fieldName] = false;

			$marker	= $this->pluginName.' '.$this->className.' rewrite rule for wp-login';
			$value 	= sanitize_file_name($value);

			if ($this->htaccess)
			{
				$lines = array();
				if (!empty($value))
				{
					$lines = array(
						"RewriteEngine on",
						"RewriteRule ^".preg_quote($value)."$ /wp-login.php [L]",
					);
					$this->login_uri = $value;
				}
				$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#', '', true);
				$this->security_rules[$fieldName] = (!empty($lines));
			}
			$this->wp_login_notice($value);
			return $value;
		}


		/**
		 * filter for options_form_post_secPassLock
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secPassLock($value, $fieldName, $metaData, $priorValue)
		{
			if ($policy = $this->isNetworkPolicy('secPassLock')) {
				if (!is_network_admin()) $value = min($value,$policy);
			}
			return $value;
		}


		/**
		 * filter for options_form_post_secPassTime
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secPassTime($value, $fieldName, $metaData, $priorValue)
		{
			if ($policy = $this->isNetworkPolicy('secPassTime')) {
				if (!is_network_admin())  $value = max($value,$policy);
			}
			return $value;
		}


		/**
		 * filter for options_form_post_secDisableURIs
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secDisableURIs($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return $value; 	// no change

			$this->security_rules[$fieldName] = false;

			$marker		= $this->pluginName.' '.$this->className.' rewrite rule by uri';
			$uriList 	= $this->plugin->text_to_array($value);

			$value = implode("\n",$uriList);

			if ($this->htaccess)
			{
				$lines = array();
				if (!empty($value))
				{
					$lines = ['RewriteEngine on'];
					foreach ($uriList as $uri)
					{
						$uri = ltrim($uri,'/');
						$lines[] = "RewriteRule ^{$uri}(.*)$ - [F]";
					}
				}
				$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#', '', true);
				$this->security_rules[$fieldName] = (!empty($lines));
			}

			return $value;
		}


		/**
		 * filter for options_form_post_secBlockIP
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secBlockIP($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return $value; 	// no change

			$this->security_rules[$fieldName] = false;

			$marker		= $this->pluginName.' '.$this->className.' deny by address';
			$ipList 	= $this->plugin->text_to_array($value);

			$ipSet = array();
			foreach ($ipList as $x => $ip)
			{
				// valid IP address
				$ipCheck = \filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
				if (!$ipCheck) {
					// valid host name
					$ipCheck = filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
					if ($ipCheck) {
						// routable host name
						$ipCheck = filter_var(gethostbyname($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);
					}
				}
				if (!$ipCheck) {
					if (strpos($ip, '(invalid)') === false) $ipList[$x] = $ip.' (invalid)';
				} else {
					$ipSet[] = $ip;
				}
			}

			$value = implode("\n",$ipList);

			if ($this->htaccess)
			{
				$lines = array();
				foreach ($ipSet as $ip)
				{
					$lines[] = "deny from {$ip}";
				}
				$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#');
				$this->security_rules[$fieldName] = (!empty($lines));
			}

			return $value;
		}


		/**
		 * filter for options_form_post_secCookies
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_secCookies($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return $value; 	// no change

			$this->security_rules[$fieldName] = false;

			$marker		= $this->pluginName.' '.$this->className.' set cookie headers';

			if (!is_array($value)) $value = [];
			$httpOnly 	= in_array('httponly',$value);
			$secure 	= in_array('secure',$value);
			$strict 	= in_array('strict',$value);

			if ($this->userIni)
			{
				$lines = [
					'session.cookie_httponly = ' . ( ($httpOnly) ? 'on' : 'off' ),
					'session.cookie_secure = ' . ( ($secure) ? 'on' : 'off' ),
					'session.cookie_samesite = '. ( ($strict) ? '"Strict"' : '"Lax"' ),
				];
				$this->plugin->insert_with_markers($this->userIni, $marker, $lines, ';');
			}


			/*
			 * no longer doing this in .htaccess - may cause duplicate/conflicting flags
			 * instead, filter set-cookie headers in checkCookieFlags()
			 */

			/*
			$exclude = $this->mergePolicies('secCookiesExc','',true); // w/$_POST values
			// can't do this with woocommerce_items_in_cart & woocommerce_cart_hash and other excluded cookies
			if (empty($exclude) && $this->htaccess)
			{
				$string = 	( ($httpOnly) ? '; HttpOnly' : '' ) .
							( ($secure) ? '; Secure' : '' ) .
							( ($strict) ? '; SameSite=Strict' : '' );
				$lines = array();
				if (!empty($string)) {
					$lines = [
						"<IfModule mod_headers.c>",
						"  Header always edit Set-Cookie (.*) \"\$1{$string}\"",
						"  Header onsuccess edit Set-Cookie (.*) \"\$1{$string}\"",
						"</IfModule>",
					];
				}
				$this->plugin->insert_with_markers($this->htaccess, $marker, $lines, '#');
				$this->security_rules[$fieldName] = (!empty($lines));
			}
			*/
			return $value;
		}


		/*
		 * Filters/actions
		 */


		/**
		 * wp_login notice
		 *
		 * @param $newLogin new login url or ''
		 * @return void
		 */
		public function wp_login_notice($newLogin=null)
		{
		//	$newLogin = site_url($newLogin ?: 'wp-login.php');
		//	echo '<div class="updated notice"><h4>' .
		//			__( "Your login url is now") . ': ' . sprintf('<a href="%s">%s</a>',$newLogin,$newLogin).
		//		 '</h4></div>';
		}


		/**
		 * wp_login filter
		 *
		 * @param string	$url complete url
		 * @param string  	$path path of url
		 * @param string  	$scheme http|https
		 * @param int|null  $blodId site id or null (current)
		 * @return	string url
		 */
		public function wp_login_filter( $url, $path, $scheme, $blogId=null )
		{
			if ($this->login_uri)
			{
				if ( strpos( $path, 'wp-login.php' ) !== false ) {
					$url = str_replace('wp-login.php', $this->login_uri, $url);
				}
			}
			return $url;
		}


		/**
		 * wp_login init action
		 *
		 * @return void
		 */
		public function wp_login_init()
		{
		    global $wp_query;

			if ($this->login_uri)
			{
				if (strpos( $_SERVER["REQUEST_URI"], $this->login_uri ) === false)
				{
				    if ($wp_query) $wp_query->set_404();
    				status_header( 404 );
    				if (get_template_part( 404 ) === false) {
						wp_die('<h4>404 Page Not Found</h4>','Page Not Found',404);
					}
    				exit();
				}
			}
		}


		/**
		 * wp_login redirect action
		 *
		 * @return void
		 */
		public function wp_login_redirect($location)
		{
			if ($this->login_uri)
			{
				if ( (is_admin() && !is_user_logged_in()) &&
				     (!defined( 'WP_CLI' ) && !defined( 'DOING_AJAX' ) && !defined( 'DOING_CRON' )) ) {
					wp_die('<h4>401 Unauthorized</h4>'.
							__('You do not have permission to access the requested resource.'),'Unauthorized',401);
				}
				if (substr($location,0,4) == 'http' && strpos($location, site_url()) === false) {
					return $location;
				}
				$location = str_replace('wp-login.php', $this->login_uri, $location);
			}
			return $location;
		}


		/**
		 * welcome email filter
		 *
		 * @param string	$content email message content
		 * @return	string
		 */
		public function welcome_email_filter( $content )
		{
			if ($this->login_uri)
			{
				$content = str_replace('wp-login.php', $this->login_uri, $content);
			}
			return $content;
		}


		/**
		 * validate password policy
		 *
		 * @param	WP_Error $wpErrors
		 * @param 	$userData
		 * @return	WP_Error
		 */
		public function validate_password_policy( $wpErrors, ...$args )
		{
			if ( isset( $_POST['pass1'] ) && trim( $_POST['pass1'] ) )
			{
				$password = sanitize_text_field( $_POST['pass1'] );
			}
			elseif ( isset( $_POST['password_1'] ) && trim( $_POST['password_1'] ) )
			{
				$password = sanitize_text_field($_POST["password_1"]);
			}

			if ( empty($password) || $wpErrors->get_error_data('pass') ) return $wpErrors;

			$wpErrors = $this->validatePassword( $password, $wpErrors );
			return $wpErrors;
		}

		/**
		 * validate_password
		 *
		 * @param	string $password
		 * @param	WP_Error $wpErrors
		 * @return	WP_Error $wpErrors
		 */
		private function validatePassword($password, $wpErrors)
		{
			$policy = $this->mergePolicies('secPassPolicy',[]);

			if (in_array('min-len', $policy) && strlen($password) < 10)
			{
				$wpErrors->add("pass_length",__("<strong>Security Policy</strong>: Password must conain at least 10 characters"), array( 'form-field' => 'pass1' ));
			}

			if (in_array('has-alpha', $policy) && (!preg_match("/[A-Z]/", $password) ))
			{
				$wpErrors->add("pass_uppercase",__("<strong>Security Policy</strong>: Password must contain upper case letters"), array( 'form-field' => 'pass1' ));
			}

			if (in_array('has-alpha', $policy) && (!preg_match("/[a-z]/", $password) ))
			{
				$wpErrors->add("pass_lowercase",__("<strong>Security Policy</strong>: Password must contain lower case letters"), array( 'form-field' => 'pass1' ));
			}

			if (in_array('has-num', $policy) && (!preg_match("#[0-9]+#", $password)))
			{
				$wpErrors->add("pass_numeric",__("<strong>Security Policy</strong>: Password must contain numeric values"), array( 'form-field' => 'pass1' ));
			}

			if (in_array('has-spec', $policy) && (!preg_match("/[@#$\%&\*()_+{:;'\><,.}\/]/", $password) ))
			{
				$wpErrors->add("pass_special",__("<strong>Security Policy</strong>: Password must contain special characters"), array( 'form-field' => 'pass1' ));
			}

			return $wpErrors;
		}


		/**
		 * validate authentication attempts
		 *
		 * @param	$user user data
		 * @return	WP_Error or $user
		 */
		public function validate_authentication_attempts( $user, ...$args )
		{
			if (empty($user) || empty($user->ID)) return $user;

			$maxAttempts = $this->mergePolicies('secPassLock',0);
			if ($maxAttempts < 1) return $user;

			$curAttempts = $this->get_transient('login_attempt_'.$user->ID, 0) + 1;

			if ($curAttempts > $maxAttempts)
			{
				return new \WP_Error( 'account_lockout',__( 'This account has been temporarily locked.' ) );
			}

			if ($maxTime = $this->mergePolicies('secPassTime',0))
			{
				$this->set_transient('login_attempt_'.$user->ID,$curAttempts,(MINUTE_IN_SECONDS * $maxTime));
			}

			return $user;
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

			$allowed_origins = $this->mergePolicies('secAllowCors','');
			if (empty($allowed_origins)) $allowed_origins = [];

			add_filter( 'allowed_http_origins', function ($allowed) use($allowed_origins) {
				$allowed_origins = array_merge($allowed,$allowed_origins);
				return $allowed_origins;
			});
			// should we trust browser referer header?
			if ($this->isPolicyEnabled('secCorsOpt','referer')) {
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
			if ($this->isPolicyEnabled('secCorsOpt','ip_address')) {
				add_filter( 'http_origin', function ($origin) {
					if (empty($origin)) {
						$origin = (is_ssl()) ? 'https://' : 'http://';
						$origin .= gethostbyaddr($this->plugin->getVisitorIP());
					}
					return $origin;
				});
			}
			$action = (current_action() == 'rest_api_init') ? 'rest_pre_serve_request' : 'wp_loaded';
			remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
			add_filter( $action, function($value) use($allowed_origins) {
				$origin = get_http_origin();
				if ($this->match_disabled_uris('secExcludeCors') || is_allowed_http_origin($origin)) {
					$this->logDebug($origin,'CORS: allowed origin');
					header( 'Access-Control-Allow-Origin: ' . $origin );
					header( 'Access-Control-Allow-Methods: ' .
						$this->plugin->varServer('REQUEST_METHOD') ?: 'OPTIONS, GET, POST, PUT, PATCH, DELETE' );
					header( 'Access-Control-Allow-Credentials: true' );
					header( 'Vary: Origin', false );
				} else {
					$this->logError($origin,'CORS: denied origin');
					header( 'Access-Control-Allow-Origin: ' . site_url() );
					$this->respondForbidden('CORS access denied');
				}
				return $value;
			},20);
		}

		/**
		 * disable rss feeds
		 *
		 * @return void
		 */
		public function disable_rss_feeds()
		{
			add_action( 'init', 						function()
			{
				remove_action( 'do_feed_rdf', 			'do_feed_rdf', 10, 0 );
				remove_action( 'do_feed_rss', 			'do_feed_rss', 10, 0 );
				remove_action( 'do_feed_rss2', 			'do_feed_rss2', 10, 1 );
				remove_action( 'do_feed_atom', 			'do_feed_atom', 10, 1 );

				remove_action( 'wp_head', 				'rsd_link' );
				remove_action( 'wp_head', 				'feed_links', 2 );
				remove_action( 'wp_head', 				'feed_links_extra', 3 );
			});

			add_action( 'do_feed', 						array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_rdf', 					array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_rss', 					array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_rss2',					array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_atom',					array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_rss2_comments',		array($this, "disable_rss_response"), 1 );
			add_action( 'do_feed_atom_comments',		array($this, "disable_rss_response"), 1 );

			add_action( 'current_theme_supports-automatic-feed-links', '__return_false', 999 );
		}


		/**
		 * disable rss feeds
		 *
		 * @return void
		 */
		public function disable_rss_response()
		{
			$this->respondForbidden('RSS access denied');
		}


		/**
		 * disable xml-rpc
		 *
		 * @return void|array - empty array of xmlrpc methods
		 */
		public function disable_xml($methods)
		{
		//	$this->logDebug($methods,__METHOD__);
			// remove all but pingbacks
			return array_filter($methods, function($method)
				{
					return stripos($method,'ping') !== false;
				},
			ARRAY_FILTER_USE_KEY);
		}


		/**
		 * disable xml-rpc pings
		 *
		 * @return void|array - empty array of xmlrpc methods
		 */
		public function disable_pings($methods)
		{
		//	$this->logDebug($methods,__METHOD__);
			// remove all pingbacks
			return array_filter($methods, function($method)
				{
					return stripos($method,'ping') === false;
				},
			ARRAY_FILTER_USE_KEY);
		}


		/**
		 * disable code editor
		 *
		 * @return void
		 */
		public function disable_code_edit()
		{
			if (!defined('DISALLOW_FILE_EDIT'))
			{
				define( 'DISALLOW_FILE_EDIT', true );
			}
			else if (is_admin())
			{
			//	\add_action( 'all_admin_notices', array($this,'disable_code_edit_error') );
			}
		}


		/**
		 * DISALLOW_FILE_EDIT was already set
		 *
		 * @return void
		 */
		public function disable_code_edit_error()
		{
			echo '<div class="notice notice-error"><h4>' .
					__( "Error setting Code Editor option, Constant 'DISALLOW_FILE_EDIT' is already defined as ".(DISALLOW_FILE_EDIT ? 'true' : 'false')) .
				 '</h4>'.( (DISALLOW_FILE_EDIT) ? '' : '<p>You can set DISALLOW_FILE_EDIT in wp-config.php with : <code>define(\'DISALLOW_FILE_EDIT\', true);</code></p>' ) .'</div>';
		}


		/**
		 * disable file mods
		 *
		 * @return void
		 */
		public function disable_file_mods()
		{
			if (!defined('DISALLOW_FILE_MODS'))
			{
				define( 'DISALLOW_FILE_MODS', true );
			}
			else if (is_admin())
			{
			//	\add_action( 'all_admin_notices', array($this,'disable_file_mods_error') );
			}
		}


		/**
		 * DISALLOW_FILE_MODS was already set
		 *
		 * @return void
		 */
		public function disable_file_mods_error()
		{
			echo '<div class="notice notice-error"><h4>' .
					__( "Error setting File Changes option, Constant 'DISALLOW_FILE_MODS' is already defined as ".(DISALLOW_FILE_MODS ? 'true' : 'false') ) .
				 '</h4>'.( (DISALLOW_FILE_MODS) ? '' : '<p>You can set DISALLOW_FILE_MODS in wp-config.php with : <code>define(\'DISALLOW_FILE_MODS\', true);</code></p>' ) .'</div>';
		}


		/**
		 * disable REST API index/list
		 *
		 * @return void
		 */
		public function disable_rest_list($response)
		{
			if (! is_user_logged_in())
			{
				$this->plugin->error('access_denied','REST API List denied',
					[$this->plugin->getVisitorIP(),$_SERVER['REQUEST_URI']]
				);
				$data = $response->get_data();
				$data['namespaces'] = [];
				$data['routes'] = [];
				$response->set_data( $data );
			}
			return $response;
		}


		/**
		 * disable WP Core REST API
		 *
		 * @return void
		 */
		public function disable_rest_core()
		{
			if (! is_user_logged_in())
			{
				add_filter('rest_endpoints', function( $endpoints )
					{
						foreach( $endpoints as $route => $endpoint ) {
							if( 0 === stripos( $route, '/wp/' ) || '/' === $route ) {
								unset( $endpoints[ $route ] );
							}
						}
						return $endpoints;
					}
				);
			}
		}


		/**
		 * disable un-authenticated REST API
		 *
		 * @param WP_Error
		 * @return WP_Error
		 */
		public function disable_rest($authError)
		{
			if ($this->isPolicyEnabled('secUnAuthRest','no-rest'))
			{
				$this->respondForbidden('REST API access denied (disabled)');
			}

			if (!empty($authError)) return $authError;

			if ($this->isPolicyEnabled('secUnAuthRest','no-rest-unauth'))
			{
				if (!is_user_logged_in())
				{
					$this->respondForbidden('REST API access denied (unauthorized)');
				}
			}
			return $authError;
		}


		/**
		 * disable invalid json request
		 *
		 */
		public function disable_invalid_json()
		{
			if (wp_is_json_request())
			{
				if (defined('REST_REQUEST')) return;
				if ($this->plugin->varServer('Sec-Fetch-Site') == 'same-origin') return;

				$this->respondForbidden('Invalid JSON Request');
			}
		}


		/**
		 * disable uri
		 *
		 * @return void
		 */
		public function disable_uris()
		{
			$found = $this->match_disabled_uris('secDisableURIs');

			if (!$found) return;

			$this->respondForbidden('URI access denied');
		}


		/**
		 * disable uri
		 *
		 * @return void
		 */
		private function match_disabled_uris($optionName)
		{
			$request 	= explode('?',$_SERVER['REQUEST_URI']);
			$request 	= trim($request[0],'/');

			if (empty($request)) return;

			$request 	= '/'.$request;

			$uriList 	= $this->mergePolicies($optionName,'');

			if (empty($uriList)) return;

			$found = array_filter($uriList,function($uri) use($request) {
				return (stripos($request, $uri) === 0);
			});
			return !empty($found);
		}


		/**
		 * block ip/host address
		 *
		 * @return void
		 */
		public function block_ip_address()
		{
			if (is_user_logged_in()) return;
			$request 	= $this->plugin->getVisitorIP();
			$ipList 	= $this->mergePolicies('secBlockIP','');
			if (empty($ipList)) return;

			if (!in_array($request, $ipList))
			{
				if ($request = $_SERVER['HTTP_REFERER'] ?? null)
				{
					$found = false;
					foreach ($ipList as $ip)
					{
						if (stripos($ip, $request) !== false) {
							$found = true;
							break;
						}
					}
					if (!$found) return;
				}
				else return;
			}

			$this->respondForbidden('IP access denied');
		}


		/**
		 * output forbidden response
		 *
		 * @return void
		 */
		public function respondForbidden($logMsg='',$message=null)
		{
			if ($logMsg) {
				$this->plugin->error('access_denied',$logMsg,
					array_filter([$this->plugin->getVisitorIP(),$_SERVER['REQUEST_URI'],file_get_contents('php://input')])
				);
			}

			http_response_code(403);
			wp_die( new \WP_Error(
				'access_denied',
				__($message ?? "Sorry, you do not have permission to access the requested resource"),
				['status' => 403]
			) );
		}


		/**
		 * set flags in Set-Cookie headers before output (used by header_register_callback)
		 *
		 * @return void
		 */
		public function checkCookieFlags()
		{
			$policy 	= $this->mergePolicies('secCookies',[]);

			$httpOnly 	= in_array('httponly',$policy);
			$secure 	= in_array('secure',$policy) && is_ssl();
			$strict 	= in_array('strict',$policy);

			$exclude 	= $this->mergePolicies('secCookiesExc','');

			$newHeaders = [];
			foreach (headers_list() as $header)
			{
				if ( preg_match('/Set-Cookie:\s(.*?)=/', $header, $cookie) )
				{
					$header = trim($header,';');
					if (!in_array($cookie[1], $exclude))
					{
						if ($httpOnly && stripos($header, 'httponly') === false) {
							$header .= '; HttpOnly';
						}
						if ($secure && stripos($header, 'secure') === false) {
							$header .= '; Secure';
						}
						if ($strict && stripos($header, 'samesite') === false) {
							$header .= '; SameSite=Strict';
						}
					}
					$newHeaders[] = $header;
				}
			}

			if (!empty($newHeaders))
			{
				header_remove('Set-Cookie');
				foreach ($newHeaders as $header) {
					header($header,false);
				}
			}
		}


		/**
		 * heartbeat_settings
		 *
		 * @param array heartbeat parameters
		 * @return array heartbeat parameters (interval set)
		 */
		public function set_hearttbeat($options)
		{
		//	if (is_admin() && function_exists('get_current_screen')) {
		//		// don't modify heartbeat when editing
		//		$screen = get_current_screen();
		//		if ($screen && $screen->parent_base == 'edit' ) return $options;
		//	}

			$sec = $this->mergePolicies('secHeartbeat',0);

			if (!empty($sec))
			{
				$options['interval'] = $sec;
			}
			return $options;
		}


		/**
		 * is network option set
		 *
		 * @param string $optionName - policy/option name
		 * @return bool
		 */
		public function isNetworkPolicy($optionName)
		{
			return $this->is_network_option($optionName);
		}


		/**
		 * is site or network option set
		 *
		 * @param string $optionName - policy/option name
		 * @param string $value - check for specific value
		 * @return bool
		 */
		public function isPolicyEnabled($optionName,$value=null)
		{
			if (! is_null($value))
			{
				return ($this->is_option($optionName,$value) || $this->is_network_option($optionName,$value));
			}
			return ($this->is_option($optionName) ?: $this->is_network_option($optionName));
		}


		/**
		 * merge site and network policies
		 *
		 * @param string $optionName - policy/option name
		 * @param mixed $default - default value & type returned
		 * @param bool $getPost - POSTed values
		 * @return mixed
		 */
		public function mergePolicies($optionName, $default = [], $getPost = false)
		{
			if (is_array($default))
			{
				if ($getPost && isset($_POST[$optionName])) {
					$value1 = sanitize_textarea_field($_POST[$optionName]);
					$value1 = array_unique( $this->plugin->text_to_array($value1) );
				} else {
					$value1 = $this->get_option($optionName,$default);
					if (!is_array($value1)) $value1 = (empty($value1)) ? [] : array($value1);
				}

				$value2 = $this->is_network_option($optionName);
				if (!is_array($value2)) $value2 = (empty($value2)) ? [] : array($value2);

				return array_unique( array_merge($value1,$value2) );
			}
			else if (is_int($default))
			{
				if ($getPost && isset($_POST[$optionName])) {
					$value1 = intval($_POST[$optionName]);
				} else {
					$value1 = intval( $this->get_option($optionName,$default) );
				}

				$value2 = intval( $this->is_network_option($optionName) );

				return max($value1,$value2);
			}
			else if (is_bool($default))
			{
				if ($getPost && isset($_POST[$optionName])) {
					$value1 = $this->plugin->isTrue($_POST[$optionName]);
				} else {
					$value1 = $this->get_option($optionName,$default);
				}

				$value2 = $this->is_network_option($optionName);

				return ($value1 || $value2) ? true : false;
			}
			else // expect string, return array of strings
			{
				if ($getPost && isset($_POST[$optionName])) {
					$values = sanitize_textarea_field($_POST[$optionName]);
				} else {
					$values	= $this->get_option($optionName,$default);
				}

				if ($value2 = $this->is_network_option($optionName)) {
					$values	.= "\n".$value2;
				}

				return array_unique( $this->plugin->text_to_array($values) );
			}
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_extension($this);
?>

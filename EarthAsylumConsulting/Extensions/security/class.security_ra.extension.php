<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_extension', false) )
{
	/**
	 * Extension: Risk Assessment - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.1112.1';

		/**
		 * @var string alias
		 */
		const ALIAS 			= 'risk_assessment';

		/**
		 * @var string extension tab name
		 */
		const TAB_NAME 			= 'Security';

		/**
		 * @var int risk assessment time-to-live
		 * hold for n hours after last request
		 */
		const RA_TTL 			= HOUR_IN_SECONDS * 12;

		/**
		 * @var int rate limit time span
		 * limit request to n in n minutes
		 */
		const RL_SPAN 			= MINUTE_IN_SECONDS * 10;

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"<abbr title='Risk Assessment uses internal risk filters and optional RA provider extensions to obtain a risk level (0-100) based on IP address.'>Risk Assessment</abbr>",
			'help'	=> 	"If more than one <abbr title='Risk Assessment'>RA</abbr> extension is enabled, each will be used, according to the 'Multiple RA Providers' setting, until a risk level is obtained.",
		];

		/**
		 * @var array risk type priority/weight
		 * when reported within the app without giving a score, the score is calculated and multiplied by weight.
		 */
		const RISK_TYPES 		= [
			'fraud' => 1.5, 'threat'=> 1.25, 'abuse' => 1.0, 'risk' => 0.75,
			'concern' => 0.5, 'none' => 0 // <- non-risk-event
		];

		/**
		 * @var string IP block list file name (write)
		 */
		const IP_BLACKLIST_FILE	= ABSPATH."ip_block_list.conf";

		/**
		 * @var string IP allow list file name (read)
		 */
		const IP_WHITELIST_FILE = ABSPATH."ip_allow_list.conf";

		/**
		 * @var int http status on die
		 */
		private $http_status 	= 403;

		/**
		 * @var string remote IP address
		 */
		private $ipAddress 		= null;


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
			if (! $this->isEnabled('security')) return $this->isEnabled(false);

			$this->registerExtension( $this->className );
			add_action('admin_init', function()
			{
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			});

			if ($this->plugin->isSettingsPage(self::TAB_NAME))
			{
				$this->add_action('admin_enqueue_styles', function($styleId)
				{
					// style for range fields (needed even if disabled)
					$style =
						'#risk_assessment_limit, #risk_assessment_threshold {width: 85%; max-width: 30em;}'.
						'#risk_assessment_limit-ticks, #risk_assessment_threshold-ticks {display: flex; width: 86%; max-width: 38.5em;}';
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
			require 'includes/security_ra.options.php';
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
			if (!$this->plugin->isSettingsPage(self::TAB_NAME)) return;
			require 'includes/security_ra.help.php';
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled

			$this->ipAddress = $this->getVisitorIP();

			/*
			 * setup rest api - perform risk assessment for IP address
			 */
			if ($this->is_option('risk_assessment_api','async'))
			{
				add_action( 'rest_api_init', 	array($this,'risk_assessment_api'), 25 );
			}
			/*
			 * setup rest api - allow external risk reporting
			 */
			if ($this->is_option('risk_assessment_api','remote'))
			{
				add_action( 'rest_api_init', 	array($this,'register_risk_api'), 25 );
			}

			/**
			 * action {plugin}_register_[threat|fraud|abuse|risk]
			 * @param string $message additional comment text
			 * @param int $score risk score (0-100)
			 * @param int $http_status optional, set http status on die
			 */
			foreach (array_keys(self::RISK_TYPES) as $type)
			{
				$this->add_action( "register_{$type}", array($this, 'register_risk_action'),10,3 );
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if ($this->isEnabled() && ! current_user_can('edit_pages') ) // not editor or better
			{
				if (! str_ends_with($_SERVER['REQUEST_URI'],'/eacDoojigger/v1/risk_assessment'))
				{
					add_action('init',						array($this, 'addAssessmentActions'));
				}
			}

			/**
			 * action {pluginName}_risk_assessment - used to force the assessment
			 */
			$this->add_action('risk_assessment',			array($this, 'get_risk_assessment_result'));

			// capture IP addresses to block file
			if ($this->get_option('risk_assessment_file'))
			{
				$this->add_action('risk_assessment_report', array($this, 'output_ip_blacklist_file'),10,4 );
			}

			/**
			 * action {pluginName}_clear_risk_assessment - forget any previously registered risk
			 */
			$this->add_action( 'clear_risk_assessment', 	array($this,'clear_risk_action') );
		}


		/**
		 * Add assessment actions on init
		 *
		 */
		public function addAssessmentActions()
		{
			if ($this->isWhitelisted()) return;

			// blacklist will die with 403 status
			if ($this->isBlacklisted()) return;

			// external risk may die with passed status
			if ($this->isExternalRisk()) return;

			// do this asynchronously?
			if ($this->is_option('risk_assessment_api','async'))
			{
				if ($this->do_async_assessment()) return;
			}

			/*
			 * We want to do this always
			 * We want to do this early 	(to block nefarious actors)
			 *        but
			 * We want to do this late 		(to allow actions to identify risk(s))
			 */
			add_action('wp_login',					array($this, 'get_risk_assessment_result'),99);
			add_action('wp_login_failed',			array($this, 'get_risk_assessment_result'),99);

			add_action('rest_pre_serve_request',	array($this, 'get_risk_assessment_result'),99);
			add_action('xmlrpc_call',				array($this, 'get_risk_assessment_result'),99);

			add_filter('wp_headers',				array($this, 'get_risk_assessment_result'),99);
		}


		/**
		 * Check an IP address is in ignored list
		 *
		 * @return bool
		 */
		public function isWhitelisted(): bool
		{
			static $ip_whitelist = null;
			static $ip_checked = null;

			if (is_null($ip_whitelist))
			{
				$ip_whitelist = $this->security->mergePolicies('risk_assessment_allowed') ?: [];
				/**
				 * filter {pluginName}_risk_assessment_whitelist - IP addresses to allow
				 * @param array IP addresses
				 * @return array IP addresses
				 */
				$ip_whitelist = $this->apply_filters('risk_assessment_whitelist',$ip_whitelist);
				// allow list can be used to reset allowed IP addresses
				$ip_whitelist = $this->input_ip_whitelist_file($ip_whitelist);
			}

			if (is_null($ip_checked))
			{
				$ip_checked = $this->plugin->isIpInList($this->ipAddress, $ip_whitelist);
			}
			return $ip_checked;
		}


		/**
		 * Check blocked IP addresses
		 *
		 */
		public function isBlacklisted(): bool
		{
			/**
			 * filter {pluginName}_risk_assessment_blacklist - IP addresses to ban
			 * @param array IP addresses
			 * @return array IP addresses
			 */
			$ip_blacklist = $this->security->mergePolicies('risk_assessment_banned') ?: [];
			if ( $ip_blacklist = $this->apply_filters('risk_assessment_blacklist',$ip_blacklist) )
			{
				if ($this->plugin->isIpInList($this->ipAddress,$ip_blacklist)) {
					$reason = 'blocked by IP blacklist rule';
					$this->http_status = 403;
					$this->register_risk($reason, 'threat', 100);
					$this->get_risk_assessment_result(); 	// will die here
					return true;
				}
			}
			return false;
		}


		/**
		 * Check http header and URL parameter
		 *
		 * URL: 	...?register_{$type}=auto;reason_message
		 * Header: 	X-{$type}-Assessment-Score: 0|n|auto; reason_message
		 * 			X-Register-{$type}: 0|n|auto; reason_message
		 */
		public function isExternalRisk(): bool
		{
			// get score passed in custom header
			foreach (array_keys(self::RISK_TYPES) as $type)
			{
				if ($score =
						$_REQUEST["register_{$type}"]
					?? 	$this->plugin->varServer("X-Register-{$type}")
					?? 	$this->plugin->varServer("X-{$type}-Assessment-Score")
				) {
					$score 	= explode(';',$score);

					$reason = str_replace('_',' ',
						sanitize_text_field($score[1] ?? "identified {$type} source")
					);

					if ($status = intval($score[2] ?? 400)) {
						$this->http_status = $status;
					}

					$this->register_risk($reason, $type, intval($score[0]));
					$this->get_risk_assessment_result();	// may die here
					if (self::RISK_TYPES[$type] > 1.0) {
						wp_die( $this->plugin->access_denied($reason, $this->http_status) );
					}
					return true;
				}
			}
			return false;
		}


		/**
		 * Do risk assessment asynchronously
		 *
		 */
		public function do_async_assessment(): bool
		{
			if (! ($this->transient_provider()) )
			{
				$origin = parse_url(site_url());
				$origin = $origin['scheme'].'://'.$origin['host'];
				$result = wp_remote_head(
					home_url('wp-json/eacDoojigger/v1/risk_assessment'),
					[
						'timeout'   => 0.01,
						'blocking'  => false,
						'headers'	=> [
							'Authorization' 	=> 'token '.base64_encode(wp_salt('auth')),
							'X-Doojigger-Ip' 	=> $this->ipAddress,
							'X-Doojigger-Uri'	=> $this->varServer('REQUEST_URI'),
							'Referer' 			=> $this->varServer('REFERER') ?: '',
							'User-Agent'		=> $this->varServer('USER-AGENT'),
							'Origin'			=> $origin,
						]
					]
				);
				return true;
			}
			return false;
		}


		/**
		 * get the risk assessment result
		 *
		 */
		public function get_risk_assessment_result($filterArg=null)
		{
			static $once = 0;
			if (! $once++)
			{
				/**
				 * action {pluginName}_risk_assessment_result
				 * @param array risk assessment data
				 */
				$this->do_action('risk_assessment_result', $this->do_risk_assessment());
			}
			return $filterArg;
		}


		/**
		 * Use the risk assessment APIs to validate/block IP address
		 *
		 * @return array risk assessment data
		 */
		private function do_risk_assessment(): array
		{
			$method 	= $this->get_option('risk_assessment_method');
			$limit 		= intval($this->get_option('risk_assessment_limit'));

			if (! ($data = $this->transient_provider()) )
			{
				$data = [
					'ipAddress'				=> $this->ipAddress,// remote address
					'RequestCount'			=> 0,				// track number of requests
					'RequestTime'			=> 0,				// track time of last requests
					'RateLimitCount'		=> 0,				// for rate limiting - request count
					'RateLimitTime'			=> time(),			// for rate limiting - 1st request time
					'RiskAssessmentMethod'	=> $method,			// divergent, convergent, average
					'RiskAssessmentLimit'	=> $limit,			// 1 - 100
					'RiskAssessmentType'	=> 'none',			// fraud, threat, abuse, risk
					'RiskAssessmentScore'	=> -1,				// 0 - 100
					'RiskAssessmentScores'	=> [],				// [ provider => [ type => score ]
					'RiskAssessmentData'	=> [],				// [ provider => [ data ] ]
					// assigned by ra provider (if available)
					'CountryCode'			=> '',
					'CountryName'			=> '',
					'PostalCode'			=> '',
					'Region'				=> '',
					'City'					=> '',
					'MetroCode'				=> '',
					'Latitude'				=> '',
					'Longitude'				=> '',
					'TimeZone'				=> '',
					'Currency'				=> '',
				];
				// save the transient early to prevent dual processing (give providers time)
				$this->transient_provider($data,self::RA_TTL);
			}
			else 	// temporarily account for pre-existing transients
			{
				if (!isset($data['RequestCount'])) $data['RequestCount'] = 1;
				if (!isset($data['RequestTime'])) $data['RequestTime'] = time();
				if (!isset($data['RateLimitCount'])) $data['RateLimitCount'] = 1;
				if (!isset($data['RateLimitTime'])) $data['RateLimitTime'] = time();
			}

			// get registered score (from register_risk)
			$data = $this->risk_assessment_registered($data);

			// get api scores (from external providers)
			$data = $this->risk_assessment_providers($data);

			// get the final score
			$data = $this->risk_assessment_scores($data);

			/**
			 * filter {pluginName}_risk_assessment_data
			 * @param array risk assessment data
			 */
			$data = $this->apply_filters('risk_assessment_data', $data);

			// save data in transient
			$this->transient_provider($data,self::RA_TTL);

			$this->logNotice(
				sprintf("%d/%d", $data['RiskAssessmentScore'], $limit),
				'Risk assessment score'
			);

			// save in current session (use getVariable('RiskAssessment') to get data)
			$this->plugin->setVariable('RiskAssessment',$data);
			$this->plugin->setVariable('RiskAssessmentScore',$data['RiskAssessmentScore']);

			// block request if we've reached the limit
			if ($limit && $data['RiskAssessmentScore'] >= $limit)
			{
			//	$this->plugin->logDebug($data['RiskAssessmentScores'],__FUNCTION__);
				$this->risk_assessment_abort($data);
			}

			return $data;
		}


		/**
		 * Check for internally registered risk
		 *
		 * @param array $data risk assessment data (empty)
		 * @return array results
		 */
		private function risk_assessment_registered(array $data): array
		{
			static $once = 0;
			if (! $once++ )
			{
				// count the number of requests
				$data['RequestCount']++;
				$data['RequestTime'] 	= time();

				// check rate limiting
				if ($data['RateLimitTime'] < (time() - self::RL_SPAN)) {
					$data['RateLimitCount'] = 0;
					$data['RateLimitTime'] = time();
				}

				$data['RateLimitCount']++;
				if ($rateLimit = $this->get_option('risk_assessment_rate_limit'))
				{
					if ($data['RateLimitCount'] > $rateLimit) {
						$this->http_status = 429;
						$this->register_risk_internal('rate limit maximum exceeded','abuse',100);
					}
				}
			}

			if ($result = $this->transient_register())
			{
				// save registered type/score
				$data['RiskAssessmentScores']['registered'] = [ $result['type'] => $result['score'] ];
				$data['RiskAssessmentData']['registered'] 	= $result;
			}

			return $data;
		}


		/**
		 * Use the risk assessment APIs to validate/block IP address
		 *
		 * @param array $data risk assessment data
		 * @return array results
		 */
		private function risk_assessment_providers(array $data): array
		{
			// if we've already hit the limit internally...
			$score = isset($data['RiskAssessmentScores']['registered'])
				? reset($data['RiskAssessmentScores']['registered']) : -1;
			if ($score >= $data['RiskAssessmentLimit']) return $data;

			/**
			 * filter {pluginName}_risk_assessment - check ra extension APIs
			 * @param array $data risk assessment data
			 * @return array results from RA extension(s)
			 */
			$data = $this->apply_filters('risk_assessment_provider',$data);

			if (!empty($data['CountryCode']))
			{
				if (! $this->plugin->getVariable('remote_country')) {
					$this->plugin->setVariable('remote_country',$data['CountryCode']);
				}
			}

			return $data;
		}


		/**
		 * Get the risk assessment score
		 *
		 * @param array $data risk assessment data
		 * @return array results
		 */
		private function risk_assessment_scores(array $data): array
		{
			$currentType  		= array_keys(self::RISK_TYPES);
			$currentType  		= end($currentType);
			$currentScore 		= $data['RiskAssessmentScore'] = 0;
			$averageScore 		= [];
			$registerScore		= 0;

			// get the highest score
			foreach($data['RiskAssessmentScores'] as $provider => $scores)
			{
				foreach ($scores as $type => $score) {
					$score = intval($score);
					if (
						($currentScore < $score) ||
						($currentScore == $score && self::RISK_TYPES[$currentType] < self::RISK_TYPES[$type])
					) {
						$data['RiskAssessmentType']		= $currentType	= $type;
						$data['RiskAssessmentScore']	= $currentScore	= $score;
					}
					if ($provider == 'registered') {
						$registerScore					= $score;
					} else {
						if ($score > 0) $averageScore[]	= $score;
					}
				}
			}

			// average non-registered, non-zero scores
			if ($data['RiskAssessmentMethod'] == 'average' && !empty($averageScore))
			{
				if ($score = array_sum($averageScore)) {
					$score = round( $score / count($averageScore), 0);
					$data['RiskAssessmentScore'] 		= max($registerScore,$score);
				}
			}

			return $data;
		}


		/**
		 * die with response and logging
		 *
		 * @param array $data risk assessment data
		 */
		private function risk_assessment_abort(array $data): void
		{
			if ($this->isWhitelisted($this->ipAddress))
			{
				$this->clear_risk_action($this->ipAddress);
			}
			else
			{
				$message = sprintf(
					__("Request denied; Risk assessment score: %d/%d",$this->plugin->PLUGIN_TEXTDOMAIN),
					$data['RiskAssessmentScore'], $data['RiskAssessmentLimit']
				);
				header_remove();
				if ($this->http_status == 429) {
					$httpDate = gmdate('D, d M Y H:i:s', time()+self::RA_TTL) . ' GMT';
					header('Retry-After: '.$httpDate);
				} else /*if (self::RISK_TYPES[$data['RiskAssessmentType']] > .5)*/ {
					/**
					 * action {pluginName}_risk_assessment_blocked
					 * @param string 	$ipAddress
					 * @param array 	$data
					 */
					$this->do_action('risk_assessment_blocked',$this->ipAddress,$data);
				}
				wp_die( $this->plugin->access_denied($message, $this->http_status) );
			}
		}


		/*
		 * Do risk assessment API
		 * used by REST api: /wp-json/eacDoojigger/v1/risk_assessment
		 */


		/**
		 * Register a WP REST api
		 *
		 * @return void
		 */
		public function risk_assessment_api()
		{
			register_rest_route( $this->pluginName, "/v1/risk_assessment", array(
				array(
					'methods'             	=> 'HEAD',
					'callback'            	=> [$this, 'risk_assessment_api_request'],
					'permission_callback' 	=> [$this, 'risk_assessment_api_auth'],
				),
			));
		}


		/**
		 * Register a WP REST api
		 *
		 * @return void
		 */
		public function risk_assessment_api_auth($request)
		{
			list ($authType,$authKey) = explode(' ',$request->get_header( 'Authorization' ));
			if ($authKey) {
				$authKey = base64_decode( trim($authKey) );
				$authKey = ($authKey == wp_salt('auth'));
			}
			if (! $authKey)
			{
				wp_die( $this->plugin->access_denied("Asynchronous Risk authentication failed",401) );
			}
			return true;
		}


		/**
		 * Do the risk assessment
		 *
		 * @return void
		 */
		public function risk_assessment_api_request($request)
		{
			define('RISK_ASSESSMENT_API',true);
			if ($header = $request->get_header( 'X-Doojigger-Ip' )) {
				$this->ipAddress = $header;
			}
			if ($header = $request->get_header( 'X-Doojigger-Uri' )) {
				$_SERVER['REQUEST_URI'] = $header;
			}
			$this->get_risk_assessment_result();
		}


		/*
		 * Register risk activity
		 * used by REST api: /wp-json/eacDoojigger/v1/register_[fraud|threat|abuse|risk]
		 * - or - actions: do_action( 'eacDoojigger_register_[fraud|threat|abuse|risk]' )
		 */


		/**
		 * Register a WP REST api
		 *
		 * @return void
		 */
		public function register_risk_api()
		{
			$types = implode('|',array_keys(self::RISK_TYPES));
			register_rest_route( $this->pluginName, "/v1/register_(?P<type>{$types})", array(
				array(
					'methods'             	=> \WP_REST_Server::ALLMETHODS,
					'callback'            	=> [$this, 'register_risk_api_request'],
					'permission_callback' 	=> [$this, 'register_risk_api_auth'],
					'args'					=> array(
						'ip'				=> ['default' => ''],
						'url'				=> ['default' => ''],
						'reason'			=> ['default' => ''],
						'score'				=> ['default' => '100'],
						'status'			=> ['default' => $this->http_status],
					),
				),
			));
		}


		/**
		 * Authenticate a WP REST api
		 *
		 * @return void
		 */
		public function register_risk_api_auth($request)
		{
			/**
			 * filter - {pluginName}_register_risk_api_auth - allow actors to authenticate and/or process request
			 * @param bool is authenticated
			 * @param object API request object
			 * @return bool
			 */
			if (! $this->apply_filters('register_risk_api_auth',true,$request))
			{
				wp_die( $this->plugin->access_denied("Register Risk authentication failed",401) );
			}

			// allow origin in CORS
			$this->plugin->allow_request_origin();

			return true;
		}


		/**
		 * Log the risk request via api
		 *
		 * @param object $request REST request
		 */
		public function register_risk_api_request($request)
		{
			$type 		= sanitize_text_field($request->get_param('type'));		// fraud|threat|abuse|risk
			$score 		= intval($request->get_param('score'));
			$reason 	= str_replace('_',' ',
							sanitize_text_field($request->get_param('reason'))
						) ?: "remote request registered {$type} ($score)";
			$url 		= $request->get_param('url');
			$this->ipAddress = $request->get_param('ip') ?: $this->getVisitorIP();

			$this->http_status = intval($request->get_param('status'));

			if ($url)
			{
				$url = sanitize_text_field(urldecode($url));
				$url = parse_url($url,PHP_URL_PATH);
				if ($url) $reason .= " [uri: {$url}]";
			}

			$this->register_risk($reason,$type,$score);
		}


		/**
		 * Log the risk request via action (used in security extension)
		 * action {pluginName}_register_[fraud|threat|abuse|risk]
		 *
		 * @param string $message additional comment text
		 * @param int $score risk score (0-100)
		 * @param int $http_status optional, set http status on die
		 */
		public function register_risk_action($message=null,int $score=0,int $http_status=0)
		{
			$type 		= explode('_',current_action());
			$type 		= end($type);
			$score 		= intval($score);

			$this->http_status = intval($http_status) ?: 403;

			$this->register_risk($message,$type,$score);
		}


		/**
		 * register the risk request
		 *
		 * @param string $message additional comment text
		 * @param string $type fraud|threat|abuse|risk
		 * @param int $score risk score (0-100)
		 */
		private function register_risk(string $message, string $type, int $score=0): void
		{
			if ($this->register_risk_internal($message, $type, $score))
			{
				$this->do_risk_assessment();
			}
		}


		/**
		 * register the risk request (without recursive call to do_risk_assessment())
		 *
		 * @param string $message additional comment text
		 * @param string $type fraud|threat|abuse|risk
		 * @param int $score risk score (0-100)
		 *
		 * @return bool risk > limit
		 */
		private function register_risk_internal(string $message, string $type, int $score=0): bool
		{
			static $limit 			= 0;
			static $threshold 		= 0;

			if (! $limit) {
				$limit 				= $this->get_option('risk_assessment_limit') ?: 80;
			}
			if (! $threshold){
				$threshold 			= $this->get_option('risk_assessment_threshold') ?: 5;
			}

			$score 					= $score ?: round(($limit / $threshold) * self::RISK_TYPES[$type],0);

			$counter 				= (self::RISK_TYPES[$type] > .5) ? 1 : 0; // don't count 'concern'

			// get previously registered
			if ($registered = $this->transient_register())
			{
				$registered['count'] 	+= $counter;
				$registered['type']		= (self::RISK_TYPES[$type] >= self::RISK_TYPES[$registered['type']])
					? $type : $registered['type'];
				$registered['score']	= min(100, ($registered['score'] + $score));
				$registered['message'][]= $message;
			}
			// or first report
			else
			{
				$registered['count']	= $counter;
				$registered['type']		= $type;
				$registered['score']	= min(100, $score);
				$registered['message']	= [$message];
				$registered['expires'] 	= self::RA_TTL;
			}
			$registered['message']		= array_slice($registered['message'],- $threshold);

			$stats = sprintf("(%d/%d),(%d/%d)",$registered['count'],$threshold,$registered['score'],$limit);
			$this->logWarning($message,"Registered {$type} report {$stats}");

			// force score to max when limit is reached
			if ($registered['count'] >= $threshold) $registered['score'] = max($limit,$registered['score']);

			// report risk to providers...
			if ($registered['count'] == $threshold && self::RISK_TYPES[$type] > .5 && !$this->isWhitelisted())
			{
				if ($this->has_action('risk_assessment_report') && $this->http_status != 429)
				{
					$this->logNotice(
						sprintf("%d/%d",$registered['count'], $threshold),
						'Triggered risk assessment reporting on theshold '
					);
					/**
					 * action {pluginName}_risk_assessment_report
					 * @param string 	$ipAddress
					 * @param array 	$registered
					 * @param int 		$threshold risk reporting threshold (1-50)
					 * @param int 		$limit risk reporting limit (1-100)
					 */
					$this->do_action('risk_assessment_report',$this->ipAddress,$registered,$threshold,$limit);
				}
				// update expiration time (report once in 12hrs)
				$registered['expires'] = self::RA_TTL;
			}

			// save current status (for at least a minute, we're obviously still active)
			$seconds = ($registered['expires'] > time()) ? $registered['expires'] - time() : MINUTE_IN_SECONDS;
			$this->transient_register($registered,$seconds);
			return ($registered['score'] >= $limit);
		}


		/**
		 * clear/delete the risk report transient
		 *
		 * @param string $ipAddress IP address (optional)
		 */
		public function clear_risk_action($ipAddress = null)
		{
			$ipAddress = $ipAddress ?: $this->ipAddress;
			if (! \filter_var($ipAddress,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
				return;
			}
			$this->logDebug($ipAddress,'IP address risk tracking cleared');

			/**
			 * action {pluginName}_risk_assessment_unblocked
			 * @param string 	$ipAddress
			 */
			$this->do_action('risk_assessment_unblocked',$ipAddress);

			if ($ipAddress == $this->ipAddress) {
				$this->plugin->setVariable('RiskAssessment',null);
				$this->plugin->setVariable('RiskAssessmentScore',null);
			}

			$this->transient_provider(false);
			$this->transient_register(false);
		}


		/**
		 * get/set/delete provider transient
		 *
		 * @param mixed $data write array or null (read) or false (delete)
		 * @param int $seconds transient time (write)
		 */
		private function transient_provider($data=null,$seconds=0)
		{
			static $key = null;
			if (empty($key)) {
				$key = sprintf('%s-%s','ip_risk_provider',str_replace(['.',':'],'-',$this->ipAddress));
			}
			return match ($data) {
				null 		=> $this->plugin->get_site_transient($key,[]),
				false 		=> $this->plugin->delete_site_transient($key),
				default  	=> $this->plugin->set_site_transient($key,$data,$seconds),
			};
		}


		/**
		 * get/set/delete registered transient
		 *
		 * @param mixed $data write array or null (read) or false (delete)
		 * @param int $seconds transient time (write)
		 */
		private function transient_register($data=null,$seconds=0)
		{
			static $key = null;
			if (empty($key)) {
				$key = sprintf('%s-%s','ip_risk_register',str_replace(['.',':'],'-',$this->ipAddress));
			}
			return match ($data) {
				null 		=> $this->plugin->get_site_transient($key,[]),
				false 		=> $this->plugin->delete_site_transient($key),
				default  	=> $this->plugin->set_site_transient($key,$data,$seconds),
			};
		}


		/**
		 * Input ip allow file
		 *
		 */
		public function input_ip_whitelist_file($ip_whitelist)
		{
			if (is_file(self::IP_WHITELIST_FILE))
			{
				if ($ip_allow = file(self::IP_WHITELIST_FILE,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)) {
					$ip_whitelist = array_merge(
						$ip_whitelist,
						array_filter($ip_allow, function($line){return $line[0] != '#';})
					);
				}
			}
			return $ip_whitelist;
		}


		/**
		 * Output to ip block file
		 *
		 * @param string 	$ipAddress
		 * @param array 	$report
		 *		count
		 *		type
		 *		score
		 *		message[]
		 *		expires
		 *		status
		 * @param int 		$threshold risk reporting threshold (1-50)
		 * @param int 		$limit risk reporting limit (1-100)
		 */
		public function output_ip_blacklist_file(string $ipAddress,array $report, int $threshold, int $limit)
		{
			if ($file = $this->get_ip_blacklist_file()) {
				file_put_contents($file, $ipAddress."\n", FILE_APPEND | LOCK_EX);
			}
		}


		/**
		 * set (create) file path
		 *
		 * @return string file path
		 */
		private function get_ip_blacklist_file()
		{
			if	(! ($fs = $this->fs->load_wp_filesystem()) ) return '';

			if (!$fs->exists(self::IP_BLACKLIST_FILE)) {
				if (($fsLogPath = $fs->find_folder(dirname(self::IP_BLACKLIST_FILE))) && $fs->is_writable($fsLogPath)) {
					$fsLogPath .= basename(self::IP_BLACKLIST_FILE);
					// since we write to this not using $fs, we need onwner & group write access
					$fs->put_contents($fsLogPath,"# Risk Assessment IP Block File\n",FS_CHMOD_FILE|0660);
				}
			}
			if (!$fs->exists(self::IP_BLACKLIST_FILE)) {
				$this->add_admin_notice('Unable to create '.basename(self::IP_BLACKLIST_FILE),'error','Write acces denied.');
				return '';
			}
			return self::IP_BLACKLIST_FILE;
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_extension($this);
?>

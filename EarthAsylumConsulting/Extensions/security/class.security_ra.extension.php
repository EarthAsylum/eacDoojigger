<?php
namespace EarthAsylumConsulting\Extensions;

require_once 'includes/security_ra.abstract.php';

if (! class_exists(__NAMESPACE__.'\security_ra_extension', false) )
{
	/**
	 * Extension: Risk Assessment - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1112.1';

		/**
		 * @var string alias
		 */
		const ALIAS 			= 'risk_assessment';

		/**
		 * @var string extension version
		 */
		const TAB_NAME 			= 'Security';

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
		const RISK_TYPES 		= [ 'fraud' => 1.5, 'threat' => 1.25, 'abuse' => 1.0, 'risk' => 0.75, 'none' => 0 ];

		/**
		 * @var string IP block list file name
		 */
		const IP_BLOCK_LIST 	= ABSPATH."ip_block_list.conf";

		/**
		 * @var array ignore these addresses
		 */
		private $ip_ignored 	= [];

		/**
		 * @var int http status on die
		 */
		private $http_status 	= 403;


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

			if ($this->is_admin())
			{
				$this->registerExtension( $this->className );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}

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

			// open api to allow external risk reporting
			if ($this->isEnabled())
			{
				if ($this->security->isPolicyEnabled('risk_assessment_api'))
				{
					/*
					 * setup rest api
					 */
					add_action( 'rest_api_init', 	array($this,'register_risk_api') );
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

			/**
			 * filter {pluginName}_risk_assessment_ignore - IP addresses to ignore
			 * 'ignore' is a misnomer, these IPs are processed but reset (i.e. for testing)
			 * @param array IP addresses
			 * @return array IP addresses
			 */
			$this->ip_ignored = $this->apply_filters('risk_assessment_ignore',$this->ip_ignored);
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if (! current_user_can('edit_pages') ) // not editor or better
			{
				add_action('init',						array($this, 'check_for_blocks'));
				// do this late, but before output, so other rules may process
				add_action('wp',						array($this, 'risk_assessment_result'),99);
				add_action('login_init',				array($this, 'risk_assessment_result'),99);
			}
			add_action('xmlrpc_enabled',				array($this, 'risk_assessment_result'),99);
			add_action('rest_pre_serve_request',		array($this, 'risk_assessment_result'),99);

			/**
			 * action {pluginName}_risk_assessment - used to force the assessment
			 */
			$this->add_action('risk_assessment',		array($this, 'risk_assessment_result'));

			// capture IP addresses to block file
			if ($this->security->isPolicyEnabled('risk_assessment_file'))
			{
				$this->add_action('risk_assessment_report', array($this, 'output_ip_file'),10,4 );
			}

			/**
			 * action {* action }_clear_risk - forget any previously registered risk
			 */
			$this->add_action( 'clear_risk', 			array($this,'clear_risk_action') );
		}


		/**
		 * Check http header and blocked IP addresses
		 *
		 */
		public function check_for_blocks(): void
		{
			// get score passed in custom header
			foreach (array_keys(self::RISK_TYPES) as $type)
			{
				// X-{$type}-Assessment-Score: 0|n|auto; reason message text
				// X-Threat-Assessment-Score: auto; known threat source tagged
				if ($score = $this->plugin->varServer("X-{$type}-Assessment-Score")) {
					$score 	= explode(';',$score);
					$reason = __(sanitize_text_field($score[1] ?? "indentified {$type} source"),$this->plugin->PLUGIN_TEXTDOMAIN);
					$this->register_risk($reason, $type, intval($score[0]));
				}
			}

			/**
			 * filter {pluginName}_risk_assessment_banned - IP addresses to ban
			 * @param array IP addresses
			 * @return array IP addresses
			 */
			$ip_blocked = $this->security->mergePolicies('risk_assessment_banned') ?: [];
			if ( $ip_blocked = $this->apply_filters('risk_assessment_banned',$ip_blocked) )
			{
				if ($this->plugin->isIpInList($this->getVisitorIP(),$ip_blocked)) {
				//if (in_array($this->getVisitorIP(),$ip_blocked)) {
					$this->register_risk('blocked by IP rule', 'threat', 100);
				}
			}
		}


		/**
		 * get the risk assessment result
		 *
		 */
		public function risk_assessment_result($arg=null)
		{
			static $once = false;
			if (!$once)
			{
				$once = true;
				/**
				 * action {pluginName}_risk_assessment_result
				 * @param array risk assessment data
				 */
				$this->do_action('risk_assessment_result', $this->risk_assessment());
			}
			return $arg;
		}


		/**
		 * Use the risk assessment APIs to validate/block IP address
		 *
		 * @return array risk assessment data
		 */
		private function risk_assessment($arg=null): array
		{
			$ipAddress 	= $this->getVisitorIP();
			$method 	= $this->security->isPolicyEnabled('risk_assessment_method');
			$limit 		= $this->security->isPolicyEnabled('risk_assessment_limit');

			if (! ($data = $this->transient_provider($ipAddress,true)) )
			{
				$data = [
					'ipAddress'				=> $ipAddress,		// remote address
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
					'TimeZone'				=> '',
					'Currency'				=> '',
				];
			}

			// get registered score (from register_risk)
			$data = $this->risk_assessment_registered($data);

			// get api scores (from external providers)
			$data = $this->risk_assessment_providers($data);

			// get the final score
			$data = $this->risk_assessment_scores($data);

			// save data in transient
			$transient_provider_key = $this->transient_provider($ipAddress,false);
			$transient_time 		= HOUR_IN_SECONDS * 12;
			$this->plugin->set_site_transient($transient_provider_key,$data,$transient_time);

			$this->logNotice(
				sprintf("%d/%d", $data['RiskAssessmentScore'], $limit),
				'Risk assessment score'
			);

			// block request if we've reached the limit
			if ($data['RiskAssessmentScore'] >= $limit)
			{
				$this->plugin->logDebug($data['RiskAssessmentScores'],__FUNCTION__);
				$this->risk_assessment_abort($ipAddress, $data['RiskAssessmentScore'], $limit);
			}

			// save in current session (use getVariable('RiskAssessment') to get data)
			$this->plugin->setVariable('RiskAssessment',$data);
			$this->plugin->setVariable('RiskAssessmentScore',$data['RiskAssessmentScore']);

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
			if ($result = $this->transient_register($data['ipAddress'],true))
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
			$currentType  		= 'risk';
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
		 * @param string $ipAddress IP address
		 * @param int $score risk score (0-100)
		 */
		private function risk_assessment_abort(string $ipAddress, int $score, int $limit): void
		{
			$message = sprintf(
				__("Request from %s denied; Risk assessment score: %d/%d",$this->plugin->PLUGIN_TEXTDOMAIN),
				$ipAddress, $score, $limit
			);

			if ($this->isIpIgnored($ipAddress))
			{
				$this->clear_risk_action($ipAddress);
			}

			wp_die( $this->plugin->access_denied($message, $this->http_status) );
		}


		/*
		 * Register risk activity
		 * used by actions: do_action( 'register_[fraud|threat|abuse|risk]' )
		 * - or-  REST api: /wp-json/.../register_[fraud|threat|abuse|risk]
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
					'callback'            	=> [$this, 'register_risk_request'],
					'permission_callback' 	=> [$this, 'register_risk_permission'],
					'args'					=> array(
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
		public function register_risk_permission($request)
		{
			/**
			 * filter - {pluginName}_register_risk_request - allow actors to authenticate and/or process request
			 * @param bool is authenticated
			 * @param object API request object
			 * @return bool
			 */
			if (! $this->apply_filters('register_risk_request',true,$request))
			{
				return $this->plugin->access_denied("Register Risk authentication failed",401);
			}
			/*
			// allow origin in CORS
			add_filter( 'allowed_http_origins', function ($allowed) {
				$origin  = (is_ssl()) ? 'https://' : 'http://';
				$origin .= gethostbyaddr($this->plugin->getVisitorIP());
				$allowed[] = $origin;
				return $allowed;
			});
			*/
			return true;
		}


		/**
		 * Log the risk request via api
		 *
		 * @param object $request REST request
		 */
		public function register_risk_request($request)
		{
			$type 		= sanitize_text_field($request->get_param('type'));		// fraud|threat|abuse|risk
			$reason 	= sanitize_text_field($request->get_param('reason'))
							?: 'disallowed remote request blocked';
			$score 		= intval($request->get_param('score')) ?: 100;
			$url 		= $request->get_param('url');

			$this->http_status = intval($request->get_param('status'));

			if ($url) {
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

			$this->http_status = intval($http_status) ?: $this->http_status;

			$this->register_risk($message,$type,$score);
		}


		/**
		 * register the risk request
		 *
		 * @param string $message additional comment text
		 * @param string $type fraud|threat|abuse|risk
		 * @param int $score risk score (0-100)
		 */
		private function register_risk(string $message, string $type, int $score=0)
		{
			static $limit 			= 0;
			static $threshold 		= 0;

			if (! $limit) {
				$limit 				= $this->security->isPolicyEnabled('risk_assessment_limit') ?: 80;
			}
			if (! $threshold){
				$threshold 			= $this->security->isPolicyEnabled('risk_assessment_threshold') ?: 5;
			}

			$ipAddress 				= $this->getVisitorIP();
			$score 					= $score ?: round(($limit / $threshold) * self::RISK_TYPES[$type],0);
			$transient_time 		= time() + DAY_IN_SECONDS;

			// get previously registered
			if ($registered = $this->transient_register($ipAddress,true))
			{
				$registered['count'] 	+= 1;
				$registered['type']		= (self::RISK_TYPES[$type] >= self::RISK_TYPES[$registered['type']])
					? $type : $registered['type'];
				$registered['score']	= min(100, ($registered['score'] + $score));
				$registered['message'][]= $message;
			}
			// or first report
			else
			{
				$registered['count']	= 1;
				$registered['type']		= $type;
				$registered['score']	= min(100, $score);
				$registered['message']	= [$message];
				$registered['expires'] 	= $transient_time;
				$registered['status']	= 200;
			}
			$registered['message']		= array_slice($registered['message'],- $threshold);

			$stats = sprintf("(%d/%d),(%d/%d)",$registered['count'],$threshold,$registered['score'],$limit);
			$this->logWarning(
				$message,
				"Registered {$type} report {$stats}"
			);

			// force score to max when limit is reached
			if ($registered['count'] >= $threshold) $registered['score'] = max($limit,$registered['score']);

			// report risk to providers...
			if ($registered['count'] == $threshold && $type != 'risk' && !$this->isIpIgnored($ipAddress))
			{
				if ($this->has_action('risk_assessment_report'))
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
					$this->do_action('risk_assessment_report',$ipAddress,$registered,$threshold,$limit);
				}
				// update expiration time (report once in 24hrs)
				$registered['expires'] = $transient_time;
			}

			$transient_register_key = $this->transient_register($ipAddress,false);

			// save current status (for at least a minute, we're obviously still active)
			$seconds = ($registered['expires'] > time()) ? $registered['expires'] - time() : MINUTE_IN_SECONDS;
			$this->plugin->set_site_transient($transient_register_key,$registered,$seconds);

			if ($registered['score'] >= $limit) $this->risk_assessment();
		}


		/**
		 * clear/delete the risk report transient
		 *
		 * @param string $ipAddress IP address (optional)
		 */
		public function clear_risk_action($ipAddress = null)
		{
			$ipAddress 				= $ipAddress ?: $this->getVisitorIP();
			$this->logDebug($ipAddress,'IP address risk tracking cleared');

			$this->plugin->setVariable('RiskAssessment',null);
			$this->plugin->setVariable('RiskAssessmentScore',null);

			$ipAddress 				= sanitize_key(str_replace(['.',':'],'-',$ipAddress));

			$transient_provider_key = $this->transient_provider($ipAddress,false);
			$this->plugin->delete_site_transient($transient_provider_key);

			$transient_register_key = $this->transient_register($ipAddress,false);
			$this->plugin->delete_site_transient($transient_register_key);
		}


		/**
		 * get transient provider key or data
		 *
		 * @param string $ipAddress IP address
		 * @param bool $getIt get the transient data
		 */
		private function transient_provider($ipAddress,$getIt=true)
		{
			$key = sprintf('%s-%s','ip_risk_provider',sanitize_key(str_replace(['.',':'],'-',$ipAddress)));
			return ($getIt) ? $this->plugin->get_site_transient($key,[]) : $key;
		}


		/**
		 * get transient registered key or data
		 *
		 * @param string $ipAddress IP address
		 * @param bool $getIt get the transient data
		 */
		private function transient_register($ipAddress,$getIt=true)
		{
			$key = sprintf('%s-%s','ip_risk_register',sanitize_key(str_replace(['.',':'],'-',$ipAddress)));
			return ($getIt) ?  $this->plugin->get_site_transient($key,[]) : $key;
		}


		/**
		 * Check an IP address is in ignored list
		 *
		 * @param string $ipAddress IPv4 or IPv6 address
		 * @return bool
		 */
		public function isIpIgnored(string $ipAddress): bool
		{
			static $checked = null;
			if (is_null($checked))
			{
				$checked = $this->plugin->isIpInList($ipAddress, $this->ip_ignored);
			}
			return $checked;
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
		public function output_ip_file(string $ipAddress,array $report, int $threshold, int $limit)
		{
			if ($file = $this->get_ip_file()) {
				file_put_contents($file, $ipAddress."\n", FILE_APPEND | LOCK_EX);
			}
		}


		/**
		 * set (create) file path
		 *
		 * @return string file path
		 */
		private function get_ip_file()
		{
			if	(! ($fs = $this->fs->load_wp_filesystem()) ) return '';

			if (!$fs->exists(self::IP_BLOCK_LIST)) {
				if (($fsLogPath = $fs->find_folder(dirname(self::IP_BLOCK_LIST))) && $fs->is_writable($fsLogPath)) {
					$fsLogPath .= basename(self::IP_BLOCK_LIST);
					// since we write to this not using $fs, we need onwner & group write access
					$fs->put_contents($fsLogPath,"# Risk Assessment IP Block File\n",FS_CHMOD_FILE|0660);
				}
			}
			if (!$fs->exists(self::IP_BLOCK_LIST)) {
				$this->add_admin_notice('Unable to create '.basename(self::IP_BLOCK_LIST),'error','Write acces denied.');
				return '';
			}
			return self::IP_BLOCK_LIST;
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_extension($this);
?>

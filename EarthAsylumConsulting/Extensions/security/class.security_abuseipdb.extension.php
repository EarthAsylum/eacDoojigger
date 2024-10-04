<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_abuseipdb_extension', false) )
{
	/**
	 * Extension: abuseipdb - AbuseIPDB API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_abuseipdb_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1004.1';

		/**
		 * @var string alias
		 */
		const ALIAS 			= 'abuse';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"<abbr title='A project dedicated to helping combat abusive activity on the internet. ".
						"This API utilizes the database to block access based on the <em>abuse confidence level</em>.'>AbuseIPDB</abbr> Extension",
			'help'	=> 	"<small>Visit the <a href='https://www.abuseipdb.com' target='_blank'>AbuseIPDB</a> web site.</small>",
		];

		/**
		 * @var array additional IP addresses to block
		 */
		private $ip_blocked 	= [];


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

			if ($this->plugin->isSettingsPage('Security'))
			{
				$this->add_action('admin_enqueue_styles', function($styleId)
				{
					$style =
						'#abuse_ipdb_level, #abuse_ipdb_threshold {width: 85%; max-width: 30em;}'.
						'#abuse_ipdb_level-ticks, #abuse_ipdb_threshold-ticks {display: flex; width: 86%; max-width: 38.5em;}';
					wp_add_inline_style( $styleId, $style );
				});
			}

			if ($this->isEnabled() && $this->security->isPolicyEnabled('abuse_ipdb_reporting'))
			{
				/*
				 * setup rest api
				 */
				\add_action( 'rest_api_init', array($this,'register_abuse_api') );

				/**
				 * action {plugin}_report_abuse
				 */
				$this->add_action( 'report_abuse', array($this,'report_abuse'),10,2 );
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
			require 'includes/abuseipdb.options.php';
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

			if ($ip_blocked = $this->security->isPolicyEnabled('abuse_ipdb_bans'))
			{
				$ip_blocked = $this->plugin->text_to_array($ip_blocked);
				foreach($ip_blocked as $ip) {
					$this->ip_blocked[$ip] = [
						'ipAddress'				=> $ip,
						'abuseConfidenceScore'	=> 100,
					];
				}
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			if (! is_user_logged_in())
			{
				// do this late so other rules may process
				add_action('wp', 	array($this, 'check_for_abuse'), 100);
			}
		}


		/**
		 * Register a WP REST api
		 *
		 * @return void
		 */
		public function register_abuse_api()
		{
			register_rest_route( $this->pluginName, '/report_abuse', array(
				array(
					'methods'             	=> \WP_REST_Server::READABLE,
					'callback'            	=> array( $this, 'report_abuse_api' ),
					'permission_callback' 	=> [$this,'register_abuse_auth'],
					'args'					=> array(
						'reason'			=> ['default' => ''],
						'category'			=> ['default' => ''],
						'threshold'			=> ['default' => '0'],
					),
				),
			));
		}


		/**
		 * Authenticate a WP REST api
		 *
		 * @return void
		 */
		public function register_abuse_auth($request)
		{
			/**
			 * filter - allow actors to authenticate
			 */
			if (! $auth = $this->apply_filters('report_abuse_api',true,$request))
			{
				return $this->plugin->request_forbidden("report_abuse_auth",'',401);
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
		 * Log the abuse request
		 *
		 * @param object $request REST request
		 * @return array abuse report array
		 */
		public function report_abuse_api($request)
		{
			$reason 	= sanitize_text_field($request->get_param('reason'))
							?: 'unauthorized origin blocked';
			$category 	= sanitize_text_field($request->get_param('category'));
			$threshold 	= intval($request->get_param('threshold'));

			$this->report_abuse($reason,$category,$threshold);

			wp_die( $this->plugin->request_forbidden("report abuse api ".$reason) );
		}


		/**
		 * Log the abuse request
		 *
		 * @param string $message additional comment text
		 * @param string $category reporting categories ("18,21")
		 */
		public function report_abuse($message=null,$category=null,$threshold=0)
		{
			$threshold 				= $threshold ?: $this->security->isPolicyEnabled('abuse_ipdb_threshold') ?: 5;
			$ipAddress 				= $this->getVisitorIP();
			$transient_data_key 	= sprintf('%s_%s','ip_abuse_report',sanitize_key(str_replace(['.',':'],'-',$ipAddress)));
			$transient_time 		= time()+DAY_IN_SECONDS;

			// get previously reported
			if ($abuse = $this->plugin->get_site_transient($transient_data_key,[])) {
				$abuse['count']++;
			//	$abuse['expires'] 	= $transient_time;
			} else {
			// or first report
				$abuse['count']		= 1;
				$abuse['expires'] 	= $transient_time;
			}

			if ($abuse['count'] == $threshold) {
				// report abuse...
				$comment = ($abuse['count'] == 1)
					? 'Exploit attempt on WordPress entry point'
					: 'Multiple exploit attempts on WordPress entry points';
				if (!empty($message)) {
					$comment .= ' ('.str_replace('"',"'",$message).')';
				}
				$report = [
					'IP'			=> $ipAddress,
					'Categories'	=> $category ?: '18,21',
					'ReportDate'	=> date('c'),
					'Comment'		=> $comment,
				];
				if ($this->security->isPolicyEnabled('abuse_ipdb_reporting','csv')) {
					if ($file = $this->get_abuse_file()) {
						$fp = fopen($file, 'a');
						fputcsv($fp, $report);
						fclose($fp);
					}
				}
				if ($this->security->isPolicyEnabled('abuse_ipdb_reporting','api')) {
					if ($key = $this->security->isPolicyEnabled('abuse_ipdb_key')) {
					}
				}
				$this->logDebug($report,__METHOD__);
				// update expiration time (report once in 24hrs)
				$abuse['expires'] 	= $transient_time;
			}

			if ($abuse['expires'] > time()) {
				$seconds = $abuse['expires']-time();
				$this->plugin->set_site_transient($transient_data_key,$abuse,$seconds);
			} else {
				$this->plugin->delete_site_transient($transient_data_key);
			}
		}


		/**
		 * set (create) log file path
		 *
		 * @return	bool
		 */
		private function get_abuse_file()
		{
			if	(! ($fs = $this->fs->load_wp_filesystem()) ) return false;

			$logPath = false;

			// check for writeable folder defined by WP_DEBUG_LOG
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
				$logPath = $fs->is_writable(realpath(dirname(WP_DEBUG_LOG)))
					? realpath(dirname(WP_DEBUG_LOG)) : null;
			}
			// check for existing, writeable folder at wp-content/uploads/
			if (!$logPath) {
				$logPath = $fs->is_writable(wp_get_upload_dir()['basedir'])
					? wp_get_upload_dir()['basedir'] : null;
			}
			// set default folder at wp-content
			if (!$logPath) $logPath = WP_CONTENT_DIR;

			if (!is_writable($logPath)) return false;

			$file = $logPath."/AbuseIPDB_".date('Ymd').".csv";

			if (!$fs->exists($file)) {
				if (($fsLogPath = $fs->find_folder(dirname($file))) && $fs->is_writable($fsLogPath)) {
					$fsLogPath .= basename($file);
					// since we write to this not using $fs, we need onwner & group write access
					$fs->put_contents($fsLogPath,"IP,Categories,ReportDate,Comment\n",FS_CHMOD_FILE|0660);
				}
			}

			return $file;
		}


		/**
		 * increment the abuse score
		 *
		 * @param int $increment increment abuse score
		 */
		public function increment(int $increment=1): void
		{
			if ($key = $this->security->isPolicyEnabled('abuse_ipdb_key'))
			{
				$this->get_AbuseIPDB($key,$increment);
			}
		}


		/**
		 * Use the AbuseIPDB API to validate/block IP address
		 *
		 * @return void
		 */
		public function check_for_abuse()
		{
			// get site or network values
			if ( ($key = $this->security->isPolicyEnabled('abuse_ipdb_key'))
			&&   ($level = $this->security->isPolicyEnabled('abuse_ipdb_level'))
			){
				$data = $this->get_AbuseIPDB($key);
				/**
				 * filter {pluginName}_abuse_check_result
				 * results from AbuseIPDB
				 */
				$data = $this->apply_filters('abuse_check_result',$data);
				if ($data['abuseConfidenceScore'] >= $level) {
					$this->plugin->logDebug($data,__METHOD__);
					wp_die( $this->plugin->request_forbidden("Request from {$data['ipAddress']} denied, Confidence score {$data['abuseConfidenceScore']}") );
				}
			}
		}


		/**
		 * Use the AbuseIPDB API to validate/block IP address
		 *
		 * @param string	$key API key
		 * @param int $increment increment abuse score
		 * @return array 	results
		 */
		public function get_AbuseIPDB(string $key,int $increment=0): array
		{
			$ipAddress 				= $this->getVisitorIP();
			if (isset($this->ip_blocked[$ipAddress])) return $this->ip_blocked[$ipAddress];

			$transient_data_key 	= sprintf('%s_%s','ip_abuse_data',sanitize_key(str_replace(['.',':'],'-',$ipAddress)));
			$transient_reset_key 	= sprintf('%s_%s','ip_abuse_data','ratelimit_reset');
			$transient_time 		= HOUR_IN_SECONDS * 8;

			// check transient (previously checked)
			if ($data = $this->plugin->get_site_transient($transient_data_key)) {
				if ($increment) {
					$data['abuseConfidenceScore'] += $increment;
					$this->plugin->set_site_transient($transient_data_key,$data,$transient_time);
				}
				return $data;
			}

			$data = [
				'ipAddress'				=> $ipAddress,
				'abuseConfidenceScore'	=> -1,
				'risk_level'			=> -1,
			];

			// check transient (previously exceeded limit)
			if ($this->plugin->get_transient($transient_reset_key)) {
				return $data;
			}

			$result = wp_remote_get(
				add_query_arg(['ipAddress'=>urlencode($ipAddress)],'https://api.abuseipdb.com/api/v2/check'),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
						'Key' 		=> $key,
					]
				]
			);

			$status = wp_remote_retrieve_response_code($result);
			if ($status == '429') {			// rate limit reached
				$reset = wp_remote_retrieve_header($result, 'X-RateLimit-Reset') ?: time()+$transient_time;
				if ($reset) {
					$log = [
						'limit'		=> wp_remote_retrieve_header($result, 'X-RateLimit-Limit'),
						'reset' 	=> wp_date('c',$reset),
					];
					$this->logError($log,'AbuseIPDB daily rate limit exceeded');
					$this->plugin->set_transient($transient_reset_key,$log,$reset - time());
				}
				return $data;
			}

			if ($status != '200') {
				$this->logError('Status '.$status.': '.get_status_header_desc($status),__METHOD__);
			} else {
				$result = json_decode( wp_remote_retrieve_body($result), true );
				if (!empty($result) && isset($result['data'])) {
					$data = $result['data'];
					$risk = intval($data['abuseConfidenceScore']);
					$data['risk_level'] 	= ($risk > 20) ? round($risk / 20,0) : 1; 	// (0-100 = 1-5)
					$data['isocode'] 		= $data['countryCode'];
					$data['api'] 			= 'AbuseIPDB';
					unset($data['reports']);
				}
			}
			if ($increment) {
				$data['abuseConfidenceScore'] += $increment;
			}

			$this->plugin->set_site_transient($transient_data_key,$data,$transient_time);

			if (isset($data['countryCode'])) {
				if (! $this->plugin->getVariable('remote_country')) {
					$this->plugin->setVariable('remote_country',$data['countryCode']);
				}
			}

			$this->logDebug("{$data['ipAddress']} AbuseIPDB Confidence Score = {$data['abuseConfidenceScore']}",__METHOD__);

			return $data;
			/*
			  {
				"data": {
				  "ipAddress": "118.25.6.39",
				  "isPublic": true,
				  "ipVersion": 4,
				  "isWhitelisted": false,
				  "abuseConfidenceScore": 100,
				  "countryCode": "CN",
				  "countryName": "China",
				  "usageType": "Data Center/Web Hosting/Transit",
				  "isp": "Tencent Cloud Computing (Beijing) Co. Ltd",
				  "domain": "tencent.com",
				  "hostnames": [],
				  "isTor": false,
				  "totalReports": 1,
				  "numDistinctUsers": 1,
				  "lastReportedAt": "2018-12-20T20:55:14+00:00",
				  "reports": [
					{
					  "reportedAt": "2018-12-20T20:55:14+00:00",
					  "comment": "Dec 20 20:55:14 srv206 sshd[13937]: Invalid user oracle from 118.25.6.39",
					  "categories": [
						18,
						22
					  ],
					  "reporterId": 1,
					  "reporterCountryCode": "US",
					  "reporterCountryName": "United States"
					}
				  ]
				}
			  }
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_abuseipdb_extension($this);
?>

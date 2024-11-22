<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_abuseipdb', false) )
{
	/**
	 * Extension: Risk Assessment - AbuseIPDB API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_abuseipdb extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1122.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'AbuseIPDB';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='A project dedicated to helping combat abusive activity on the internet. ".
						"This API utilizes the  AbuseIPDB database to block access based on the <em>abuse confidence level</em>.'>AbuseIPDB</abbr>)",
			'help'	=> 	"<small>Visit the <a href='https://www.abuseipdb.com' target='_blank'>AbuseIPDB</a> web site.</small>",
		];

		/**
		 * @var array account types and rate limits
		 * since the AbuseIPDB api gives us x-ratelimit-limit, x-ratelimit-remaining, x-ratelimit-reset
		 * we don't need to track usage for rate limiting.
		 */
		const ACCOUNT_LIMITS = [
		//	id						name
		//	"standard"		=> [ 	"Standard (free)",		'month'=> 1000	],
		//	"webmaster"		=> [ 	"Web Master (free)",	'month'=> 3000	],
		//	"supporter"		=> [ 	"Supporter (free)",		'month'=> 5000	],
		//	"basic"			=> [ 	"Basic",				'month'=>10000	],
		//	"premium"		=> [ 	"Premium",				'month'=>50000	],
		//	"enterprise"	=> [ 	"Enterprise",			'month'=>0		],
		];


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_abuseipdb.options.php';
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

			// get the api key to set our account id
			if (! ($key = $this->security->isPolicyEnabled('abuse_ipdb_key')) )
			{
				return $this->isEnabled(false);
			}
			$this->account_id = $key;

			// set the account plan and rate limit - AbuseIPDB passes rate limit in the api
			//	if ($account = $this->security->isPolicyEnabled('abuse_ipdb_plan')) {
			//		$this->account_plan 		= self::ACCOUNT_LIMITS[$account];
			//		$this->rate_limit['limit'] 	= $this->account_plan['month'];
			//		$this->rate_limit['retry'] 	= strtotime('tomorrow');
			//	}

		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			// add custom filter for reporting to provider
			if ( parent::addActionsAndFilters() )
			{
				$this->add_action('risk_assessment_report', array($this, 'report_to_provider'),10,4 );
			}
		}


		/**
		 * Use the AbuseIPDB API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	result data array
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = $data['ipAddress'];

			$result = wp_remote_get(
				add_query_arg(['ipAddress'=>urlencode($ipAddress)],'https://api.abuseipdb.com/api/v2/check'),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
						'Key' 		=> $this->account_id,
					]
				]
			);

			$status = intval(wp_remote_retrieve_response_code($result));
			$data['RiskAssessmentData'][self::PROVIDER] = ['status'=>$status];
			// only use on status 429, otherwise informational only
			$this->rate_limit = [
				'limit' 	=> intval(wp_remote_retrieve_header($result,'X-RateLimit-Limit')),
				'retry' 	=> intval(wp_remote_retrieve_header($result,'X-RateLimit-Reset')),
			];

			if ($status != 200)
			{
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$this->logError($result['errors'][0]['detail'],'AbuseIPDB API Error');
				}
			}
			else
			{
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$result = $result['data'];
					unset($result['reports']);
					$score = intval($result['abuseConfidenceScore']);
					$data = array_replace($data,[
						'CountryCode'		=> $result['countryCode'],
						'CountryName'		=> $result['countryName'] ?? $data['CountryName'],
					]);
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ 'abuse' => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				}
			}

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


		/**
		 * Report the abuse request
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
		public function report_to_provider(string $ipAddress,array $report, int $threshold, int $limit)
		{
			$message 				= implode('; ',array_unique($report['message']));

			$comment = ($threshold == 1)
				? 'Exploit attempt on WordPress entry point'
				: 'Repeated exploit attempts on WordPress entry points';
			if (!empty($message)) {
				$comment .= ' ('.str_replace('"',"'",$message).')';
			}
			$report = [ // in column order with api names
				'ip'			=> $ipAddress,
				'categories'	=> '18,21',
				'timestamp'		=> date('c'),
				'comment'		=> $comment,
			];
			$this->logDebug($report,__FUNCTION__);
			// abuse report file
			if ($this->security->isPolicyEnabled('abuse_ipdb_reporting','csv')) {
				$this->report_to_provider_file($report);
			}
			// abuse repoort api
			if ($this->security->isPolicyEnabled('abuse_ipdb_reporting','api')) {
				$this->report_to_provider_api($report);
			}
		}


		/**
		 * report abuse to file
		 *
		 * @param array $report report array
		 * @return void
		 */
		private function report_to_provider_file(array $report)
		{
			$file = $this->plugin->get_output_file(
				static::PROVIDER."/ip_report.".date('Y-m-d').".csv",
				true,
				"IP,Categories,ReportDate,Comment\n"
			);
			if (! is_wp_error($file)) {
				$fp = fopen($file, 'a');
				fputcsv($fp, $report);
				fclose($fp);
			}
		}


		/**
		 * report abuse via api
		 *
		 * @param array $report report array
		 * @return void
		 */
		private function report_to_provider_api(array $report): void
		{
			if ($this->isRateLimit(0,'report')) return;

			$report['ip'] = urlencode($report['ip']);
			$result = wp_remote_post('https://api.abuseipdb.com/api/v2/report',
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
						'Key' 		=> $this->account_id,
					],
					'body'		=> $report,
				]
			);
			$status = intval(wp_remote_retrieve_response_code($result));
			if ($status != 200)
			{
				$this->logError('Status '.$status.': '.get_status_header_desc($status),__FUNCTION__);
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$this->logError($result['errors'][0]['detail'],'AbuseIPDB Reporting Error');
				}
				if ($status == 429) {			// rate limit reached
					$retry = wp_remote_retrieve_header($result, 'X-RateLimit-Reset') ?: strtotime('tomorrow');
					$this->isRateLimit($retry,'report');
				}
			}
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_abuseipdb($this);
?>

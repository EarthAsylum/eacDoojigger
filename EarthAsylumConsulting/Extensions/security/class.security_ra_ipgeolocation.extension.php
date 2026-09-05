<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_ipgeolocation', false) )
{
	require_once 'includes/security_ra.abstract.php';

	/**
	 * Extension: ipgeolocation - ipgeolocation API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_ipgeolocation extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.0904.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'IpGeoLocation';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='IpGeoLocation provides geographical information about website visitors with any IPv4 or IPv6 address as well as a threat score with all paid plans. ".
						"This API utilizes the IpGeoLocation database to block access based on the <em>threat score</em>.'>IpGeoLocation</abbr>)",
			'help'	=>	"<small>Visit the <a href='https://www.ipgeolocation.io' target='_blank'>ipgeolocation</a> web site.</small>",
		];

		/**
		 * @var array account types and rate limits
		 * We rely on the api returning a 429 status if we hit the daily or monthly rate limit
		 * and pause until the following midnight (UTC). This allows for surcharged overages.
		 */
		const ACCOUNT_LIMITS = [
		//	id						name
			"developer"		=> [ 	"Developer (free)",		'day'=>1000,	'month'=>       0	],

			"starter"		=> [ 	"Starter",				'day'=>0,		'month'=>  150000	],
			"plus"			=> [ 	"Plus",					'day'=>0,		'month'=>  500000	],
			"pro"			=> [ 	"Pro",					'day'=>0,		'month'=> 1000000	],
			"business"		=> [ 	"Business",				'day'=>0,		'month'=> 2000000	],
			"premium"		=> [ 	"Premium",				'day'=>0,		'month'=> 5000000	],
			"enterprise"	=> [ 	"Enterprise",			'day'=>0,		'month'=>       0	],

		//	"standard"		=> [ 	"Standard",				'day'=>0,		'month'=> 1000000	],
		//	"security"		=> [ 	"Security",				'day'=>0,		'month'=> 1000000	],
		//	"advance"		=> [ 	"Advance",				'day'=>0,		'month'=> 1000000	],
		//	"custom"		=> [ 	"Custom",				'day'=>0,		'month'=>       0	],
		];


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_ipgeolocation.options.php';
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
			if (! ($key = $this->security->isPolicyEnabled('ipgeolocation_key')) )
			{
				return $this->isEnabled(false);
			}
			$this->account_id = $key;

			// set the account plan and rate limit
			if ($account = $this->security->isPolicyEnabled('ipgeolocation_plan'))
			{
				// old plans to standard
				if (!isset(self::ACCOUNT_LIMITS[$account])) {
					$link = $this->plugin->getSettingsLink(true,'security','IpGeoLocation plan');
					self::add_admin_notice("Please reset your {$link}",'warning');
					$account = 'starter';
				}
				$this->account_plan 		= self::ACCOUNT_LIMITS[$account];
				$this->rate_limit['limit'] 	= $this->account_plan['month'];
				if ($this->rate_limit['limit']) {
					$this->rate_limit['limit'] = $this->rate_limit['limit'] / 3; // 3 credits per request
				}
				$this->rate_limit['retry'] 	= strtotime('tomorrow');
			}
		}


		/**
		 * Use the ipgeolocation API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	result data array
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = $data['ipAddress'];

			$api_url = add_query_arg([
					'apiKey'	=> $this->account_id,
					'ip'		=> $ipAddress,
					'include'	=> ($this->security->isPolicyEnabled('ipgeolocation_plan') != 'developer')
						? 'security' : '',
				],'https://api.ipgeolocation.io/v3/ipgeo');

			$result = wp_remote_get($api_url,
				[
					'headers' 	=> [
						'Accept' 		=> 'application/json',
					]
				]
			);

			$status = intval(wp_remote_retrieve_response_code($result));
			$data['RiskAssessmentData'][self::PROVIDER] = ['status'=>$status];

			if ($status != 200)
			{
				if ($status == 429) {	// rate limit exceeded (day or month)
					if ($rateLimit = wp_remote_retrieve_header($result,'Retry-After')) {
						$this->rate_limit['retry'] = $rateLimit;
					}
				}
			//	$this->logDebug(wp_remote_retrieve_headers($result),self::PROVIDER.' API Error Response');
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$this->logError($result['message'],self::PROVIDER.' API Error');
				}
			}
			else
			{
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$score = (isset($result['security']))
						? intval($result['security']['threat_score'])
						: 0;
					$data = array_replace($data,[
						'CountryCode'		=> $result['location']['country_code2'],
						'CountryName'		=> $result['location']['country_name'],
						'PostalCode'		=> $result['location']['zipcode'],
						'Region'			=> (isset($result['location']['state_code']))
												? substr($result['location']['state_code'],-2)
												: $result['location']['state_prov'],
						'City'				=> $result['location']['city'],
						'TimeZone'			=> $result['time_zone']['name'] ?? $data['TimeZone'],
						'Currency'			=> $result['currency']['code'] ?? $data['Currency'],
					]);
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ 'threat' => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				}
			}

			return $data;
			/*
			{
				"ip": "8.8.8.8",
				"hostname": "dns.google",
				"continent_code": "NA",
				"continent_name": "North America",
				"country_code2": "US",
				"country_code3": "USA",
				"country_name": "United States",
				"country_capital": "Washington, D.C.",
				"state_prov": "California",
				"district": "Santa Clara",
				"city": "Mountain View",
				"zipcode": "94043-1351",
				"latitude": "37.42240",
				"longitude": "-122.08421",
				"is_eu": false,
				"calling_code": "+1",
				"country_tld": ".us",
				"languages": "en-US,es-US,haw,fr",
				"country_flag": "https://ipgeolocation.io/static/flags/us_64.png",
				"geoname_id": "6301403",
				"isp": "Google LLC",
				"connection_type": "",
				"organization": "Google LLC",
				"asn": "AS15169",
				"currency": {
					"code": "USD",
					"name": "US Dollar",
					"symbol": "$"
				},
				"time_zone": {
					"name": "America/Los_Angeles",
					"offset": -8,
					"current_time": "2020-12-17 07:49:45.872-0800",
					"current_time_unix": 1608220185.872,
					"is_dst": false,
					"dst_savings": 1
				}
				"security": {
					"threat_score": 7,
					"is_tor": false,
					"is_proxy": true,
					"proxy_type": "VPN",
					"is_anonymous": true,
					"is_known_attacker": false,
					"is_cloud_provider": false
				}
			}
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_ipgeolocation($this);
?>

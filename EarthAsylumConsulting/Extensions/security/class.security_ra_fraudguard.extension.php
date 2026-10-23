<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_fraudguard', false) )
{
	require_once 'includes/security_ra.abstract.php';

	/**
	 * Extension: fraudguard - FraudGuard API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_fraudguard extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.1013.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'FraudGuard';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='A service designed to provide an easy way to validate usage by continuously collecting and analyzing real-time internet traffic. ".
						"This API utilizes the FraudGuard database to block access based on the <em>fraud risk level</em>.'>FraudGuard</abbr>)",
			'help'	=>	"<small>Visit the <a href='https://www.fraudguard.io' target='_blank'>FraudGuard</a> web site.</small>",
		];

		/**
		 * @var array account types and rate limits
		 */
		const ACCOUNT_LIMITS = [
		//	id						name
			"hacker"		=> [ 	"Hacker (free)",	'second'=> 1,	'month'=>     1000	],
			"starter"		=> [ 	"Starter",			'second'=> 2,	'month'=>  1000000	],
			"professional"	=> [ 	"Professional",		'second'=> 5,	'month'=>  5000000	],
			"business"		=> [ 	"Business",			'second'=>10,	'month'=> 25000000	],
			"enterprise"	=> [ 	"Enterprise",		'second'=>20,	'month'=>100000000	],
		];


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_fraudguard.options.php';
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

			// get the api user/pass to set our account id
			if (! ($user = $this->get_option('fraudguard_user'))
			||  ! ($pass = $this->get_option('fraudguard_pass'))
			) {
				return $this->isEnabled(false);
			}
			$this->account_id = $user.':'.$pass;

			// set the account plan and rate limit
			if ($account = $this->get_option('fraudguard_plan'))
			{
				$this->account_plan 		= self::ACCOUNT_LIMITS[$account];
				$this->rate_limit['limit'] 	= $this->account_plan['month'];
				$this->rate_limit['retry'] 	= strtotime('tomorrow');
			}
		}


		/**
		 * Use the FraudGuard API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	results
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = urlencode( $data['ipAddress'] );

			$api_url = (in_array($this->get_option('fraudguard_plan'),['hacker','starter']))
					? 'https://api.fraudguard.io/ip/%s'
					: 'https://api.fraudguard.io/v2/ip/%s';

			$responce = wp_remote_get(sprintf($api_url,$ipAddress),
				[
					'headers' 	=> [
						'Accept' 		=> 'application/json',
						'Authorization' => 'Basic '.base64_encode($this->account_id),
					]
				]
			);

			$status = intval(wp_remote_retrieve_response_code($responce));
			$data['RiskAssessmentData'][self::PROVIDER] = ['status'=>$status];

			if ($status != 200)
			{
				$this->assessment_result_error($status,$responce);
			}
			else
			{
				if ($result = json_decode( wp_remote_retrieve_body($responce), true )) {
					array_walk($result, function(&$value,$key) {
						if ($value == 'unknown') $value = '';
					});
					if (!isset($result['timezone']))		// v1
					{
						$data = array_replace($data,array_filter([
							'CountryCode'		=> $result['isocode'],
							'CountryName'		=> $result['country'],
							'Region'			=> $result['state'],
							'City'				=> $result['city'],
						]));
					}
					else									// v2
					{
						$data = array_replace($data,array_filter([
							'CountryCode'		=> $result['isocode'],
							'CountryName'		=> $result['country'],
							'Region'			=> $result['state_code'],
							'City'				=> $result['city'],
							'PostalCode'		=> $result['postal_code'],
							'Latitude'			=> $result['latitude'],
							'Longitude'			=> $result['longitude'],
							'TimeZone'			=> $result['timezone'],
						]));
					}
					$type = [1=>'risk',2=>'abuse',3=>'threat',4=>'threat',5=>'fraud'];
					$type = $type[ intval($result['risk_level']) ];
					$score = [1=>10,2=>30,3=>60,4=>80,5=>100];
					$score = $score[ intval($result['risk_level']) ];
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ $type => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				}
			}

			return $data;
			/*
				{
				    "isocode": "KR",
					"country": "Republic of Korea",
					"state": "Seoul",
					"city": "Seoul",
					"discover_date": "2018-12-11 07:00:45",
					"threat": "honeypot_tracker",
					"risk_level": "5"
				}
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_fraudguard($this);
?>

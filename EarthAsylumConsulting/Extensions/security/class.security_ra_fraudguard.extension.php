<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_fraudguard', false) )
{
	/**
	 * Extension: fraudguard - FraudGuard API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_fraudguard extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1107.1';

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
			if (! ($user = $this->security->isPolicyEnabled('fraudguard_user'))
			||  ! ($pass = $this->security->isPolicyEnabled('fraudguard_pass'))
			) {
				return $this->isEnabled(false);
			}
			$this->account_id = $user.':'.$pass;

			// set the account plan and rate limit
			if ($account = $this->security->isPolicyEnabled('fraudguard_plan'))
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
			$ipAddress = $data['ipAddress'];

			$api_url = ($this->security->isPolicyEnabled('fraudguard_plan') != 'hacker')
					? 'https://api.fraudguard.io/v2/ip/'
					: 'https://api.fraudguard.io/ip/';

			$result = wp_remote_get($api_url.$ipAddress,
				[
					'headers' 	=> [
						'Accept' 		=> 'application/json',
						'Authorization' => 'Basic '.base64_encode($this->account_id),
					]
				]
			);

			$status = intval(wp_remote_retrieve_response_code($result));
			$data['RiskAssessmentData'][self::PROVIDER] = ['status'=>$status];

			if ($status != 200)
			{
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					$this->logError($result,'FraudGuard API Error');
				}
			}
			else
			{
				if ($result = json_decode( wp_remote_retrieve_body($result), true )) {
					array_walk($result, function(&$value,$key) {
						if ($value == 'unknown') $value = '';
					});
					$score = [1=>0,2=>30,3=>60,4=>80,5=>100];
					$score = $score[ intval($result['risk_level']) ];
					$data = array_replace($data,[
						'CountryCode'		=> $result['isocode'],
						'CountryName'		=> $result['country'],
						'Region'			=> $result['state'],
						'City'				=> $result['city'],
					]);
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ 'threat' => $score ];
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

<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_ipqualityscore', false) )
{
	require_once 'includes/security_ra.abstract.php';

	/**
	 * Extension: Risk Assessment - IPQualityScore API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_ipqualityscore extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.1013.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'IPQualityScore';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='Check IP Fraud Scores Instantly with Real-Time Risk Analysis. ".
						"Lookup IP scores for any IPv4 or IPv6 address. Detect high risk IP addresses ".
						"and check IP fraud scores with accurate results worldwide.'>IPQualityScore</abbr>)",
			'help'	=> 	"<small>Visit the <a href='https://ipqualityscore.com/?r=11946441' target='_blank'>IPQualityScore</a> web site.</small>",
		];

		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_ipqualityscore.options.php';
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
			if (! ($key = $this->get_option('ipqs_key')) )
			{
				return $this->isEnabled(false);
			}
			$this->account_id = $key;
		}


		/**
		 * Use the IPQS API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	result data array
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = urlencode( $data['ipAddress'] );

			$responce = wp_remote_get(
				sprintf("https://ipqualityscore.com/api/json/ip/%s/%s",$this->account_id,$ipAddress),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
					]
				]
			);

			$status = intval(wp_remote_retrieve_response_code($responce));
			$data['RiskAssessmentData'][self::PROVIDER] = ['status'=>$status];
			// only use on status 429, otherwise informational only
			$this->rate_limit = [
				'limit' 	=> intval(wp_remote_retrieve_header($responce,'X-RateLimit-Limit')),
				'retry' 	=> intval(wp_remote_retrieve_header($responce,'X-RateLimit-Reset') ?: strtotime('tomorrow')),
			];

			if ($status != 200)
			{
				$this->assessment_result_error($status,$responce);
			}
			else
			{
				if (($result = json_decode( wp_remote_retrieve_body($responce), true )) && $result['success']) {
					$score = intval($result['fraud_score']);
					$type = match(true) {
						$score <= 20 => 'risk',
						$score <= 50 => 'abuse',
						$score <= 80 => 'threat',
						default => 'fraud',
					};
					$data = array_replace($data,array_filter([
						'CountryCode'		=> $result['country_code'],
						'City'				=> $result['city'],
						'Region'			=> $result['region'],
						'PostalCode'		=> $result['zip_code'],
						'Latitude'			=> $result['latitude'],
						'Longitude'			=> $result['longitude'],
						'TimeZone'			=> $result['timezone']
					]));
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ $type => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				} else {
					$data['RiskAssessmentData'][self::PROVIDER]['status'] = 500;
				}
			}

			return $data;
			/*
				[success] => 1
				[message] => Success
				[fraud_score] => 75
				[country_code] => US
				[region] => Pennsylvania
				[city] => Reading
				[ISP] => Cloudflare Warp
				[ASN] => 13335
				[organization] => Cloudflare Warp
				[is_crawler] =>
				[timezone] => America/New_York
				[mobile] =>
				[host] => 2a09:bac5:b294:175f::254:1a
				[proxy] => 1
				[vpn] => 1
				[tor] =>
				[active_vpn] => 1
				[active_tor] =>
				[recent_abuse] =>
				[bot_status] =>
				[connection_type] => Premium required.
				[abuse_velocity] => Premium required.
				[zip_code] => N/A
				[latitude] => 40.34000015
				[longitude] => -75.98000336
				[abuse_events] => Array
					(
						[0] => Enterprise plan required to view abuse events and active proxy networks
					)
				[request_id] => lUgG7yadbP
				[api_version] => 1
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_ipqualityscore($this);
?>

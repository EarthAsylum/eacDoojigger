<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_ipasis', false) )
{
	require_once 'includes/security_ra.abstract.php';

	/**
	 * Extension: Risk Assessment - iPASIS API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_ipasis extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.1013.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'iPASIS';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='IPASIS detects bots, fake users, and fraudulent interactions using advanced IP and email intelligence. ".
						"This API uses IP lookup and calculates the risk score based on tor, proxy, vpn, abuse and/or hosting signals.'>iPASIS</abbr>)",
			'help'	=> 	"<small>Visit the <a href='https://www.ipasis.com' target='_blank'>iPASIS</a> web site.</small>",
		];

		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_ipasis.options.php';
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
			if (! ($key = $this->get_option('ipasis_key')) )
			{
				return $this->isEnabled(false);
			}
			$this->account_id = $key;
		}


		/**
		 * Use the iPASIS API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	result data array
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = urlencode( $data['ipAddress'] );

			$responce = wp_remote_get(
				add_query_arg(['ip'=>$ipAddress],'https://api.ipasis.com/v1/lookup'),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
						'X-API-Key' => $this->account_id,
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
				if ($result = json_decode( wp_remote_retrieve_body($responce), true )) {
					if (isset($result['risk'])) {
						$score = intval($result['risk']['score']);
					} else {
						$score = 0;
						if (!empty($result['privacy']['Tor'])) 			$score += 50;
						else if (!empty($result['privacy']['Proxy'])) 	$score += 40;
						else if (!empty($result['privacy']['VPN'])) 	$score += 30;
						if (!empty($result['privacy']['Abuse'])) 		$score += 25;
						if (!empty($result['privacy']['Hosting'])) 		$score += 20;
					}
					$score = min($score,100);
					$type = match(true) {
						$score <= 20 => 'risk',
						$score <= 50 => 'abuse',
						$score <= 80 => 'threat',
						default => 'fraud',
					};
					$geo = explode(',',$result['loc']);
					$data = array_replace($data,array_filter([
						'CountryCode'		=> $result['country'],
						'City'				=> $result['city'],
						'Region'			=> $result['region'],
						'PostalCode'		=> $result['postal'],
						'Latitude'			=> $geo[0],
						'Longitude'			=> $geo[1],
						'TimeZone'			=> $result['timezone']
					]));
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ $type => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				}
			}

			return $data;
			/*
				[ip] => 45.78.81.176
				[city] =>
				[region] =>
				[country] => US
				[loc] => 37.751,-97.822
				[postal] =>
				[timezone] => America/Chicago
				[asn] => Array
					(
						[ASN] => AS203020
						[Name] => HostRoyale Technologies Pvt Ltd
						[Domain] =>
						[Route] => 45.78.64.0/19
						[Type] => hosting
					)
				[company] => Array
					(
						[Name] => UnknownVPN
						[Domain] =>
						[Type] => vpn
					)
				[privacy] => Array
					(
						[VPN] => 1
						[Proxy] =>
						[Tor] =>
						[Relay] =>
						[Hosting] =>
						[Abuse] =>
						[AI] =>
						[Crawler] =>
						[Service] => UnknownVPN
						[Type] => Residential
					)
				[abuse] => Array
					(
						[Address] =>
						[Country] =>
						[Email] =>
						[Name] =>
						[Network] => 45.78.64.0/19
						[Phone] =>
					)
				[domains] => Array
					(
						[Page] => 0
						[Total] => 0
						[Domains] =>
					)
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_ipasis($this);
?>

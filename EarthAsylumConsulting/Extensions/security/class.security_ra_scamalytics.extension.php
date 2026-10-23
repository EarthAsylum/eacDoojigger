<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_ra_scamalytics', false) )
{
	require_once 'includes/security_ra.abstract.php';

	/**
	 * Extension: Risk Assessment - Scamalytics API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2026 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_ra_scamalytics extends security_ra_abstract
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '26.1013.1';

		/**
		 * @var string risk assessment provider name (display name, array key, transient id)
		 */
		const PROVIDER 			= 'Scamalytics';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"RA (<abbr title='The Scamalytics IP Fraud Risk API provides real-time risk assessments ".
						"for any IP address, combining Scamalytics proprietary fraud intelligence with data from 10+ trusted external sources.'>Scamalytics</abbr>)",
			'help'	=> 	"<small>Visit the <a href='https://www.scamalytics.com' target='_blank'>Scamalytics</a> web site.</small>",
		];

		/**
		 * @var mixed account id (api key/user)
		 */
		protected $account_user 	= '';

		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_ra_scamalytics.options.php';
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

			// get the api user/key to set our account id
			if (! ($user = $this->get_option('scamalytics_user'))
			||  ! ($key  = $this->get_option('scamalytics_key'))
			) {
				return $this->isEnabled(false);
			}
			$this->account_user	= $user;
			$this->account_id	= $key;
		}


		/**
		 * Use the Scamalytics API to validate/block IP address
		 *
		 * @param array		$data initialized data array
		 * @return array 	result data array
		 */
		public function get_assessment_result(array $data): array
		{
			$ipAddress = urlencode( $data['ipAddress'] );

			$responce = wp_remote_get(
				add_query_arg(['key'=>$this->account_id,'ip'=>$ipAddress],'https://api11.scamalytics.com/v3/'.$this->account_user),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
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
					$scamalytics = $result['scamalytics'];
					if ($scamalytics['status'] == 'error') {
						if ($scamalytics['credits']) {
							$this->rate_limit['limit'] = $scamalytics['credits']['used']+$scamalytics['credits']['remaining'];
							if (! $scamalytics['credits']['remaining']) {
								$status = $data['RiskAssessmentData'][self::PROVIDER]['status'] = 429;
							}
							$this->assessment_result_error($status,$responce);
						}
						return $data;
					}
					$score = intval($scamalytics['scamalytics_score']);
					$type  = ['low'=>'risk', 'medium'=>'abuse', 'high'=>'threat', 'very high'=>'fraud'];
					$type  = $type[$scamalytics['scamalytics_risk']] ?? 'threat';
					$location = $result['external_datasources']['maxmind_geolite2'];
					$geo = explode(',',$location['ip_geolocation']);
					$data = array_replace($data,array_filter([
						'CountryCode'		=> $location['ip_country_code'],
						'CountryName'		=> $location['ip_country_name'],
						'City'				=> $location['ip_city'],
						'Region'			=> $location['ip_state_name'] ?: $location['ip_district_name'],
						'PostalCode'		=> $location['ip_postcode'],
						'MetroCode'			=> $location['ip_metro_code'],
						'Latitude'			=> $geo[0],
						'Longitude'			=> $geo[1],
						'TimeZone'			=> $location['ip_time_zone'],
					]));
					$data['RiskAssessmentScores'][self::PROVIDER]	= [ $type => $score ];
					$data['RiskAssessmentData']  [self::PROVIDER]	+= $result;
				}
			}

			return $data;
			/*
			[scamalytics] => Array
				(
					[status] => ok
					[mode] => live
					[ip] => 2a09:bac5:b294:175f::254:14
					[scamalytics_score] => 25
					[scamalytics_risk] => medium
					[scamalytics_url] => https://scamalytics.com/ip/2a09:bac5:b294:175f::254:14
					[scamalytics_isp] => Cloudflare, Inc.
					[scamalytics_org] => Cloudflare
					[scamalytics_isp_score] => 5
					[scamalytics_isp_risk] => low
					[scamalytics_proxy] => Array
						(
							[is_datacenter] => 1
							[is_vpn] =>
							[is_apple_icloud_private_relay] =>
							[is_amazon_aws] =>
							[is_google] =>
							[is_resproxy] =>
						)
					[is_blacklisted_external] =>
					[credits] => Array
						(
							[used] => 1
							[remaining] => 4999
							[last_sync_timestamp_utc] => 2026-10-10 14:31:25
							[seconds_elapsed_since_last_sync] => 183
							[note] => Credits used and remaining are approximate values.
						)
					[exec] => 20.73 ms
				)
				[external_datasources] => Array
					(
						[maxmind_geolite2] => Array
							(
								[asn] => 13335
								[as_name] => Cloudflare, Inc.
								[ip_geoname_id] => 5207728
								[ip_location_accuracy_km] => 100
								[ip_country_code] => US
								[ip_state_name] => Pennsylvania
								[ip_district_name] =>
								[ip_city] => Reading
								[ip_metro_code] => 504
								[ip_postcode] => 19602
								[ip_geolocation] => 40.3362,-75.9153
								[ip_country_name] => United States
								[ip_time_zone] => America/New_York
								[datasource_name] => maxmind.com and geonames.org
								[license_info] => https://creativecommons.org/licenses/by-sa/4.0
								[last_updated_timestamp_utc] => 2026-10-10 06:20:18
							)
					)
			*/
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_ra_scamalytics($this);
?>

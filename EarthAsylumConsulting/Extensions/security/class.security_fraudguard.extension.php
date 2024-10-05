<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_fraudguard_extension', false) )
{
	/**
	 * Extension: fraudguard - FraudGuard API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_fraudguard_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1004.1';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= [
			'label' => 	"<abbr title='A service designed to provide an easy way to validate usage by continuously collecting and analyzing real-time internet traffic. ".
						"This API utilizes the database to block access based on the <em>fraud risk level</em>.'>FraudGuard</abbr> Extension",
			'help'	=>	"<small>Visit the <a href='https://www.fraudguard.io' target='_blank'>FraudGuard</a> web site.</small><br>".
						"If the AbuseIPDB API is enabled, FraudGuard is used only as a fallback service. ".
						"Otherwise, FraudGuard is the primary detection service.",
		];


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
						'#fraudguard_level {width: 85%; max-width: 30em;}'.
						'#fraudguard_level-ticks {display: flex; width: 86%; max-width: 38.5em;}';
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
			require 'includes/fraudguard.options.php';
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
				if ($this->isExtension('abuse') && $this->abuse->isEnabled()) {
					$this->add_filter('abuse_check_result', array($this, 'check_for_fraud'));
				} else {
					add_action('wp', 						array($this, 'check_for_fraud'), 100);
				}
			}
		}


		/**
		 * Use the FraudGuard API to validate/block IP address
		 *
		 * @param array|object 	$data from abuse check or wp object
		 * @return array 	$data
		 */
		public function check_for_fraud($data=null)
		{
			// if we already have an abuse score, don't check fraud risk
			if (is_array($data) && $data['abuseConfidenceScore'] >= 0) return $data;

			// get site or network values
			if ( ($user = $this->security->isPolicyEnabled('fraudguard_user'))
			&&   ($pass = $this->security->isPolicyEnabled('fraudguard_pass'))
			&&   ($level = $this->security->isPolicyEnabled('fraudguard_level'))
			){
				$data = $this->get_FraudGuard($user,$pass);
				/**
				 * filter {pluginName}_fraud_check_result
				 * results from FraudGuard
				 */
				$data = $this->apply_filters('fraud_check_result',$data);
				if ($data['risk_level'] >= $level) {
					$this->plugin->logDebug($data,__METHOD__);
					wp_die( $this->plugin->request_forbidden("Request from {$data['ipAddress']} denied, Fraud risk level {$data['risk_level']}") );
				}
			}
			return $data;
		}


		/**
		 * Use the FraudGuard API to validate/block IP address
		 *
		 * @param string	$user FraudGuard username
		 * @param string	$pass FraudGuard password
		 * @return array 	results
		 */
		public function get_FraudGuard(string $user,string $pass): array
		{
			$ipAddress 				= $this->getVisitorIP();

			$transient_data_key 	= sprintf('%s_%s','ip_abuse_data',sanitize_key(str_replace(['.',':'],'-',$ipAddress)));
			$transient_reset_key 	= sprintf('%s_%s','ip_fraud_data','ratelimit_reset');
			$transient_time 		= HOUR_IN_SECONDS * 8;

			// check transient (previously checked)
			if (($data = $this->plugin->get_site_transient($transient_data_key,[]))
			&&  ($data['abuseConfidenceScore'] >= 0)
			){
				return $data;
			}

			$data = array_merge([
				'ipAddress'				=> $ipAddress,
				'abuseConfidenceScore'	=> -1,
				'risk_level'			=> -1,
			],$data);

			// check transient (previously exceeded limit)
			if ($this->plugin->get_transient($transient_reset_key)) {
				return $data;
			}

			$api_url = ($this->security->isPolicyEnabled('fraudguard_version') == 'V2')
					? 'https://api.fraudguard.io/v2/ip/'
					: 'https://api.fraudguard.io/ip/';

			$result = wp_remote_get($api_url.$ipAddress,
				[
					'headers' 	=> [
						'Accept' 		=> 'application/json',
						'Authorization' => 'Basic '.base64_encode($user.':'.$pass),
					]
				]
			);

			$status = wp_remote_retrieve_response_code($result);
			if ($status == '429') {			// rate limit reached
				if ($reset = time() + DAY_IN_SECONDS) {
					$log = [
						'reset' 	=> wp_date('c',$reset),
					];
					$this->logError($log,'FraudGuard rate limit exceeded');
					$this->plugin->set_transient($transient_reset_key,$log,$reset - time());
				}
				return $data;
			}

			if ($status != '200') {
				$this->logError('Status '.$status.': '.get_status_header_desc($status),__METHOD__);
			} else {
				$result = json_decode( wp_remote_retrieve_body($result), true );
				if (!empty($result)) {
					$data = array_merge($data,$result);
					$risks = [1=>10,2=>30,3=>60,4=>80,5=>100];
					$data['abuseConfidenceScore'] = $risks[ $data['risk_level'] ]; 	// (1-5 = 10-100)
					$data['countryCode'] 	= $data['isocode'];
					$data['api'] 			= 'FraudGuard';
				}
			}

			$this->plugin->set_site_transient($transient_data_key,$data,$transient_time);

			if (isset($data['countryCode'])) {
				if (! $this->plugin->getVariable('remote_country')) {
					$this->plugin->setVariable('remote_country',$data['countryCode']);
				}
			}

			$this->logDebug("{$data['ipAddress']} FraudGuard Risk Level = {$data['risk_level']}",__METHOD__);

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
if (isset($this)) return new security_fraudguard_extension($this);
?>

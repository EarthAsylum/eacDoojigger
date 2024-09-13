<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\abuse_extension', false) )
{
	/**
	 * Extension: abuseipdb - AbuseIPDB API - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class abuse_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.0913.1';

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

			if ($this->is_admin())
			{
				$this->registerExtension( [$this->className, 'Security'] );
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
						'#abuse_ipdb_level {width: 85%; max-width: 30em;}'.
						'#abuse_ipdb_level-ticks {display: flex; width: 86%; max-width: 38.5em;}';
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
			$this->registerExtensionOptions( $this->className,
				[
					'_abuse_ipdb' 	=> array(
							'type'		=>	'display',
							'label'		=>	"About",
							'default'	=>	"<a href='https://www.abuseipdb.com'>AbuseIPDB</a> ".
											"is a project dedicated to helping combat abusive activity on the internet. ".
											"This <abbr title='Application Program Interface'>API</abbr> utilizes the database to block access based on the <em>abuse confidence level</em>.",
					),
					'abuse_ipdb_key' 	=> array(
							'type'		=>	'textarea',
							'label'		=>	"API Key",
							'default'	=>	$this->isPolicyEnabled('abuse_ipdb_key'),
							'info'		=> 	$this->isPolicyEnabled('abuse_ipdb_key')
								? "Your AbuseIPDB API Key enables <em>IP Address checking</em>."
								: "Enter your <a href='https://www.abuseipdb.com/account/api' target='_blank'>AbuseIPDB API Key</a> to enable <em>IP Address checking</em>.",
					),
					'abuse_ipdb_level'	=> array(
							'type'		=>	'range',
							'label'		=>	'Abuse Confidence Level',
							'default'	=>	$this->isPolicyEnabled('abuse_ipdb_level',80),
							'after'		=>	'<datalist id="abuse_ipdb_level-ticks">'.
												'<option value="10" label="10"></option>'.
												'<option value="20" label="20"></option>'.
												'<option value="30" label="30"></option>'.
												'<option value="40" label="40"></option>'.
												'<option value="50" label="50"></option>'.
												'<option value="60" label="60"></option>'.
												'<option value="70" label="70"></option>'.
												'<option value="80" label="80"></option>'.
												'<option value="90" label="90"></option>'.
												'<option value="100" label="100"></option>'.
											'</datalist>'.PHP_EOL.
											'Block access on abuse level of <code>'.
											'<output name="abuse_ipdb_level_show" for="abuse_ipdb_level">[value]</output>'.
											'</code> or greater.',
							'attributes'=>	['min="10"', 'max="100"','step="1"','list="abuse_ipdb_level-ticks"',
											'oninput'=>"abuse_ipdb_level_show.value = this.value"],
					),
					'abuse_ipdb_bans' 	=> array(
							'type'		=>	'textarea',
							'label'		=>	"Additional Addresses ",
							'info'		=>	"Treat additional IP address(es) as banned. Enter 1 address per line.",
							'advanced'	=> 	true,
					),
				]
			);
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

			if ($ip_blocked = $this->isPolicyEnabled('abuse_ipdb_bans'))
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
				add_action('init', 			array($this, 'check_for_abuse'), 1);
			}
		}


		/*
		 * Filters/actions
		 */


		/**
		 * Use the AbuseIPDB API to validate/block IP address
		 *
		 * @return bool
		 */
		public function check_for_abuse($arg=null)
		{
			// get site or network values
			if ( ($key = $this->isPolicyEnabled('abuse_ipdb_key')) && ($level = $this->isPolicyEnabled('abuse_ipdb_level')) )
			{
				$data = $this->get_AbuseIPDB($key,$level);
				if ($data['abuseConfidenceScore'] >= $level) {
					$this->error('access_denied',"Request from {$data['ipAddress']} denied (Abuse IP DB)");
					$this->plugin->logDebug($data,__METHOD__);
					wp_die(
						__("Sorry, you do not have permission to access the requested resource"),
						get_bloginfo('name').__(' - Permission Denied'),403
					);
				}
			}
		}


		/**
		 * Use the AbuseIPDB API to validate/block IP address
		 *
		 */
		public function get_AbuseIPDB($key,$level=100): array
		{
			$ipAddress 		= $this->getVisitorIP();
			if (isset($this->ip_blocked[$ipAddress])) return $this->ip_blocked[$ipAddress];

			$sessionKey 	= 'ip_data';
			$transientKey 	= sprintf('%s_%s',$sessionKey,str_replace(['.',':'],'-',$ipAddress));

			// check current session
		//	if ($result = $this->getVariable($sessionKey)) {
		//		return $result;
		//	}

			// check transient (previously blocked)
			if ($result = $this->get_site_transient($transientKey)) {
				return $result;
			}

			$data = [
				'ipAddress'				=> $ipAddress,
				'abuseConfidenceScore'	=> 0,
			];

			$ipEncode = urlencode($ipAddress);
			$result = wp_remote_get(
				add_query_arg(['ipAddress'=>$ipEncode],'https://api.abuseipdb.com/api/v2/check'),
				[
					'headers' 	=> [
						'Accept' 	=> 'application/json',
						'Key' 		=> $key,
					]
				]
			);
			$result = (wp_remote_retrieve_response_code($result) == '200')
				? json_decode( wp_remote_retrieve_body($result), true ) : null;

			if (empty($result)) return $data;

			$data = $result['data'];

			$this->setVariable($sessionKey,$data);
			$this->set_site_transient($transientKey,$data,HOUR_IN_SECONDS/2);

			if (! $this->getVariable('remote_country')) {
				$this->setVariable('remote_country',$data['countryCode']);
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
		 * is site or network option set
		 *
		 * @param string $optionName - policy/option name
		 * @param string $value - check for specific value
		 * @return mixed
		 */
		public function isPolicyEnabled($optionName,$value=null)
		{
			if (! is_null($value))
			{
				return $this->is_option($optionName) ?: $this->is_network_option($optionName) ?: $value;
			}
			return $this->is_option($optionName) ?: $this->is_network_option($optionName);
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new abuse_extension($this);
?>

<?php
namespace EarthAsylumConsulting\Extensions;

/**
* Extension: security_ra - abstract class for risk assessment providers
*
* @category		WordPress Plugin
* @package		{eac}Doojigger\Extensions
* @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
* @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
*/

abstract class security_ra_abstract extends \EarthAsylumConsulting\abstract_extension
{
	/**
	 * @var string extension version
	 */
	const VERSION 			= '25.0314.1';

	/**
	 * @var string extension tab name
	 */
	const TAB_NAME 			= 'Security';

	/**
	 * @var string risk assessment provider name (display name, array key, transient id)
	 */
	const PROVIDER 			= '';	// required

	/**
	 * @var array account types and rate limits
	 * We rely on the api returning a 429 status if we hit a rate limit and pause
	 * until the following midnight (UTC). This allows for surcharged overages.
	 * We can set limits here by second, minute, hour, day, or month (each is optional).
	 */
	const ACCOUNT_LIMITS 	= [
	//		"id"	=> [ 	"Description", 'second'=>n, 'minute'=>n, 'hour'=>n, 'day'=>n, 'month'=>n	],
	];

	/**
	 * @var array account plan (single selected from ACCOUNT_LIMITS)
	 */
	protected $account_plan = [];

	/**
	 * @var mixed account id (api key/user)
	 */
	protected $account_id 	= '';	// required

	/**
	 * @var array rate limit
	 */
	protected $rate_limit 	= [
		'limit' => 0,				// $this->account_plan['month'];
		'retry'	=> 0,				// strtotime('tomorrow');
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

		// must have risk_assessment enabled
		if (! $this->isEnabled('risk_assessment')) return false;

		if ($this->is_admin())
		{
			$this->registerExtension( $this->className );
			// Register plugin options when needed
			$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
			// Add contextual help
			$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
		}
	}


	/**
	 * register options on options_settings_page
	 *
	 * @access public
	 * @return void
	 */
	abstract function admin_options_settings();


	/**
	 * Add help tab on admin page
	 *
	 * @todo - add contextual help
	 *
	 * @return	void
	 */
	abstract function admin_options_help();


	/**
	 * initialize method - called from main plugin
	 *
	 * @return 	void
	 */
	public function initialize()
	{
		return parent::initialize();
	}


	/**
	 * Add filters and actions
	 * @example if ( parent::addActionsAndFilters() ) {...}
	 *
	 * @return bool filter(s) added
	 */
	public function addActionsAndFilters()
	{
		if (! current_user_can('edit_pages') ) 	// not editor or better
		{
			$this->add_filter('risk_assessment_provider', 	array($this, 'check_for_risk'));
			return true; 						// let child know it's ok to add additional filters
		}
		else									// dummy filter required for risk_assessment_method
		{
			$this->add_filter('risk_assessment_provider', function($data) {return $data;});
			return false;
		}
	}


	/**
	 * Use the provider APIs to validate/block IP address
	 *
	 * @param array $data from security_ra 'risk_assessment'
	 * @return array $data
	 */
	public function check_for_risk(array $data): array
	{
		// if we already got our score...
		if (isset($data['RiskAssessmentData'][static::PROVIDER])) return $data;

		$limit = match($data['RiskAssessmentMethod']) {
			'divergent' 	=> 0,								// first score >= 0 is used
			'convergent' 	=> $data['RiskAssessmentLimit'],	// first score >= limit is used
			'average' 		=> PHP_INT_MAX,						// average all scores
		};

		// if we already have a risk score over the limit...
		if ($data['RiskAssessmentScore'] >= $limit) return $data;

		// check for previous or plan rate limit exceeded
		if ($this->checkRateLimits()) return $data;

		// get the provider risk assessment
		$data 	= $this->get_assessment_result($data);
		$status = $data['RiskAssessmentData'][static::PROVIDER]['status'];

		if ($status !== 200)
		{
			$this->logError('Status '.$status.': '.get_status_header_desc($status),static::PROVIDER.' API Error');
			if ($status == 429) {	// rate limit exceeded (day or month)
				$this->isRateLimit($this->rate_limit['retry']);
			}
		}
		else
		{
			$score = reset($data['RiskAssessmentScores'][static::PROVIDER]);
			$data['RiskAssessmentScore'] = max($data['RiskAssessmentScore'],$score);
			$this->logNotice("Status: {$status}, Score: {$score}",static::PROVIDER.' API Result');
		}

		return $data;
	}


	/**
	 * Use the provider API to validate/block IP address
	 *
	 * @param array		$data initialized data array
	 * @return array 	result data array
	 */
	abstract function get_assessment_result(array $data): array;


	/*
	 * Rate limiting by provider or account plan
	 *
	 */


	/**
	 * Helper to check rate limit from account plam
	 *
	 * @return bool true = limit exceeded
	 */
	protected function checkRateLimits(): bool
	{
		// check provider rate limit first
		if ($this->isRateLimit()) return true;

		// do we have an account plan set
		if (empty($this->account_plan)) return false;

		$provider 				= static::PROVIDER;
		$hash 					= md5($this->account_id);
		$plan 					= $this->account_plan;

		$transient_limit_key 	= $this->transient_ratelimit($provider,"%s-{$hash}",false);
		// not month, let provider tell us if we hit the monthly limit via isRateLimit()
		foreach (['second','minute','hour','day' /*,'month' */] as $idx=>$type)
		{
			if (isset($plan[$type]) && $plan[$type] > 0)
			{
				$key = sprintf($transient_limit_key,$type);
				if ($reset = $this->plugin->get_site_transient($key)) {
					if ($reset[0] >= $plan[$type]) {
						$this->logError(wp_date('c',$reset[1]), $provider." per-{$type} rate limit ({$plan[$type]}) exceeded");
						return true;
					}
					$reset[0]++;
					$setTime = $reset[1];
				} else {
					switch ($type) {
						case 'second'	: $setTime = round(microtime(true) + 1.0,0); break;
					//	case 'minute'	: $setTime = strtotime(date('h:i:59'))+1; break;
					//	case 'hour'		: $setTime = strtotime(date('h:59:59'))+1; break;
						case 'day'		: $setTime = strtotime('tomorrow'); break;
					//	case 'month'	: $setTime = strtotime('midnight first day of next month'); break;
						default			: $setTime = strtotime("+1 {$type}");
					}
					$reset 		= [0,$setTime];
					if ($idx > 1) { // don't log second or minute
						$this->logDebug(wp_date('c',$reset[1]), $provider." per-{$type} rate limit ({$plan[$type]}) set");
					}
				}
				$this->plugin->set_site_transient($key,$reset,$setTime - time());
			}
		}
		return false;
	}


	/**
	 * Helper to check or set rate limit - set on a 429 status from the provider
	 *
	 * @param int $time - epoch time to expire
	 * @param string $name - optional id (non-default rate limit)
	 * @return bool|array - false or transient array
	 */
	protected function isRateLimit(int $time=0,string $name='')
	{
		$provider 				= static::PROVIDER . (($name) ? "-{$name}" : '');
		$hash 					= md5($this->account_id);
		$transient_rate_key 	= $this->transient_ratelimit($provider,"retry-{$hash}",false);

		if ($time)
		{
			$this->plugin->set_site_transient($transient_rate_key,[0,$time],$time - time());
			$this->logError(wp_date('c',$time),$provider.' provider rate limit exceeded');
			return [0,$time];
		}
		else
		{
			return $this->plugin->get_site_transient($transient_rate_key,false);
		}
	}


	/**
	 * get transient ratelimit key or data
	 *
	 * @param string $provider provider name
	 * @param string $type ratelimit type/hash
	 * @param bool $getIt get the transient data
	 */
	private function transient_ratelimit($provider,$type,$getIt=true)
	{
		$key = sprintf('%s-%s-%s','ip_risk_ratelimit',sanitize_key($provider),$type);
		return ($getIt) ?  $this->plugin->get_site_transient($key) : $key;
	}
}

<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * Extension: software registration - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @uses 		\EarthAsylumConsulting\Traits\swRegistrationUI;
 */

include "eacDoojigger_registration/eacDoojigger_registration.includes.php";

class eacDoojigger_registration extends \EarthAsylumConsulting\abstract_extension
	implements \EarthAsylumConsulting\Interfaces\eacDoojigger_registration
{
	use \EarthAsylumConsulting\Traits\swRegistrationUI;
	use \EarthAsylumConsulting\Traits\eacDoojigger_registration_wordpress;

	/**
	 * @var string extension version
	 */
	const VERSION	= '25.0419.1';

	/**
	 * @var ALIAS constant ($this->Registration->...)
	 */
	const ALIAS		= 'Registration';

	/**
	 * @var string|array|bool to set (or disable) default group display/switch
	 * 		false 		disable the 'Enabled'' option for this group
	 * 		string 		the label for the 'Enabled' option
	 * 		array 		override options for the 'Enabled' option (label,help,title,info, etc.)
	 */
	const ENABLE_OPTION	= false;


	/**
	 * constructor method
	 *
	 * @param 	object	$plugin main plugin object
	 * @return 	void
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin, self::ALLOW_ALL|self::ALLOW_NON_PHP);

		if ($this->is_admin())
		{
			// load UI (last) from swRegistrationUI trait (includes registration)
			$this->add_action( 'options_settings_page', [$this,'swRegistrationUI'], PHP_INT_MAX );
			// safety check for registration refresh
			//add_action( 'shutdown', 	[$this,'checkRegistryRefreshEvent'] );
		}

		// allow internal extensions if license is L3 (standard) or better
		$this->add_filter( 'allow_internal_extensions', function()
			{
				return $this->isRegistryvalue('license', 'L3', 'ge');
			}, PHP_INT_MAX
		);

		// allow external extensions if license is L4 (professional) or better
		$this->add_filter( 'allow_external_extensions', function()
			{
				return $this->isRegistryvalue('license', 'L4', 'ge');
			}, PHP_INT_MAX
		);
	}


	/**
	 * Called after instantiating, loading extensions and initializing
	 *
	 * @see https://codex.wordpress.org/Plugin_API
	 *
	 * @return	void
	 */
	public function addActionsAndFilters(): void
	{
		parent::addActionsAndFilters();

		// from registration_wordpress trait
		if (method_exists($this, 'addSoftwareRegistryHooks'))
		{
			$this->addSoftwareRegistryHooks();
		}
		// from swRegistrationUI trait (backend)
		if (method_exists($this, 'swRegistrationActionsAndFilters'))
		{
			$this->swRegistrationActionsAndFilters();
		}

		if ($this->is_admin())
		{
			// add to unregistered notice
			$this->add_filter('unregistered_notice', 	function($notice)
				{
					$notice .= "<br/>Note: derivative plugins and other features may be non-functional until registered.";
					return $notice;
				}
			);
		}
	}


	/*
	 *
	 * interface implementation through swRegistrationUI & eacDoojigger_registration_wordpress traits
	 *
	 */
}
/**
* return a new instance of this class
*/
return new eacDoojigger_registration($this);
?>

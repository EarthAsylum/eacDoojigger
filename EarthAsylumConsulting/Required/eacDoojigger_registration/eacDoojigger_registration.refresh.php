<?php
/**
 * EarthAsylum Consulting {eac} Software Registration - software registration command line refresh
 *
 * scheduled to run via crontab :
 * /path/to/php/php /path/to/html/classfolder/eacDoojigger_registration.refresh.php {registration_key}
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2021 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 */

include "eacDoojigger_registration.includes.php";

class eacDoojiggerRegistrationRefresh implements \EarthAsylumConsulting\Interfaces\eacDoojigger_registration
{
	use \EarthAsylumConsulting\Traits\eacDoojigger_registration_wordpress;

	/**
	 * constructor method
	 *
	 * @param string current registration key
	 * @return 	void
	 */
	public function __construct(string $registrationKey = null)
	{
		return $this->refreshRegistration($registrationKey);
	}
}
return new eacDoojiggerRegistrationRefresh($argv[0] ?: null);

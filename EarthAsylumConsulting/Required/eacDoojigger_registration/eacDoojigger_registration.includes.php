<?php
/**
 * EarthAsylum Consulting {eac} Software Registration - software registration includes
 *
 * includes the interfaces and traits used by the software registration API
 *
 * @category	WordPress Plugin
 * @package		{eac}SoftwareRegistry
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		25.0725.1
 */

/*
 *	class <yourclassname> [extends something] implements \EarthAsylumConsulting\eacDoojigger_registration_interface
 *	{
 *		use \EarthAsylumConsulting\Traits\eacDoojigger_registration_wordpress;
 *				- OR -
 *		use \EarthAsylumConsulting\Traits\eacDoojigger_registration_filebased;
 *		...
 *	}
 */

/*
 * include interface...
 */
	require "eacDoojigger_registration.interface.php";

/*
 * include traits ...
 */
	require "eacDoojigger_registration.interface.trait.php";

/*
 *	require "eacDoojigger_registration.wordpress.trait.php";
 *				- OR -
 *	require "eacDoojigger_registration.filebased.trait.php";
 */
 	require "eacDoojigger_registration.wordpress.trait.php";

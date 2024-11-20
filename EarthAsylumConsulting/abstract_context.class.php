<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger for WordPress - Plugin context switch front-end (public) vs back-end (administration).
 *
 * Plugin derivatives extend abstract_context which in turn extends abstract_frontend or abstract_backend.
 *
 * @example class myAwesomePlugin extends \EarthAsylumConsulting\abstract_context
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 * @version		24.1120.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @uses		\EarthAsylumConsulting\abstract_backend
 * @uses		\EarthAsylumConsulting\abstract_frontend
 */

/*
 * @since Ver 3.0 - moved is_admin_request() to autoload.php
 */
//if (!function_exists(__NAMESPACE__.'\is_admin_request')) {}

/*
 * @since Ver 3.0 - moved is_network_admin_request() to autoload.php
 */
//if (!function_exists(__NAMESPACE__.'\is_network_admin_request')) {}


/*
 * Include and extend the appropriate network/back-end/front-end base class
 */


if ( is_network_admin_request() )
{
	/*
	 * Back-End Network Administration context
     * @ignore
	 */
	abstract class abstract_context extends \EarthAsylumConsulting\abstract_backend
	{
		/**
		 * @var bool context flags
		 */
		const 	CONTEXT_IS_NETWORK 		= true,
			 	CONTEXT_IS_BACKEND 		= true,
				CONTEXT_IS_FRONTEND 	= false;
	}
}
else if ( is_admin_request() )
{
	/*
	 * Back-End Administration context
     * @ignore
	 */
	abstract class abstract_context extends \EarthAsylumConsulting\abstract_backend
	{
		/**
		 * @var bool context flags
		 */
		const 	CONTEXT_IS_NETWORK 		= false,
				CONTEXT_IS_BACKEND 		= true,
				CONTEXT_IS_FRONTEND 	= false;
	}
}
else
{
	/**
	 * Plugin context switch front-end (public) vs back-end (administration)
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2022 EarthAsylum Consulting <www.earthasylum.com>
	 * @version		2.x
	 * @uses		EarthAsylumConsulting\abstract_backend
	 * @uses		EarthAsylumConsulting\abstract_frontend
	 */

	/*
	 * Front-End Public context
	 */
 	abstract class abstract_context extends \EarthAsylumConsulting\abstract_frontend
 	{
		/**
		 * @var bool context flags
		 */
		const 	CONTEXT_IS_NETWORK 		= false,
			 	CONTEXT_IS_BACKEND 		= false,
				CONTEXT_IS_FRONTEND 	= true;
	}
}

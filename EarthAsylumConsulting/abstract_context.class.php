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
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @uses		\EarthAsylumConsulting\abstract_backend
 * @uses		\EarthAsylumConsulting\abstract_frontend
 */

if (!function_exists(__NAMESPACE__.'\is_admin_request'))
{
	/**
	 * admin-ajax & admin-post request always return is_admin() == true
	 * are we calling for the frontend or backend?
	 */
	function is_admin_request(): bool
	{
		static $is_admin = null;

		if (!is_bool($is_admin))
		{
			if (wp_doing_ajax() || (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/admin-post.php') !== false))
			{
				$is_admin = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], admin_url()) !== false);
			}
			else
			{
				$is_admin = is_admin();
			}
		}
		return $is_admin;
	}
}

if (!function_exists(__NAMESPACE__.'\is_network_admin_request'))
{
	/**
	 * admin-ajax & admin-post request always return is_network_admin() == false
	 * are we calling for network admin?
	 */
	function is_network_admin_request(): bool
	{
		static $is_network_admin = null;

		if (!is_bool($is_network_admin))
		{
			if (! is_multisite())
			{
				$is_network_admin = false;
			}
			else if (wp_doing_ajax() || (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/admin-post.php') !== false))
			{
				$is_network_admin = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], network_admin_url()) !== false);
			}
			else
			{
				$is_network_admin = (is_multisite() && is_network_admin());
			}
		}
		return $is_network_admin;
	}
}


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

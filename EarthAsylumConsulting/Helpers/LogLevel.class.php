<?php
namespace EarthAsylumConsulting\Helpers;

use Psr\Log\LogLevel as psr;

/**
 * LogLevel class - define PHP/PSR-3 log levels
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Helpers
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0830.1
 */
class LogLevel
{
	/**
	 * @var int logging levels (selectable debugging levels)
	 */
	const LOG_ERROR				= E_USER_ERROR;			// Errors & Exceptions
	const LOG_WARNING			= E_USER_WARNING;		// Warnings
	const LOG_NOTICE			= E_USER_NOTICE;		// Notices
	const LOG_DEBUG				= 2*self::LOG_NOTICE;	// Debugging
	const LOG_INFO				= 2*self::LOG_DEBUG;	// Information
	const LOG_ALWAYS			= E_ALL;				// log under any selected level

	/**
	 * @var array map PSR LogLevel to logging levels
	 */
	const PSR_TO_LOGGING		=
	[
		psr::EMERGENCY			=> self::LOG_ERROR,
		psr::ALERT				=> self::LOG_ERROR,
		psr::CRITICAL 			=> self::LOG_ERROR,
		psr::ERROR 				=> self::LOG_ERROR,
		psr::WARNING			=> self::LOG_WARNING,
		psr::NOTICE				=> self::LOG_NOTICE,
		psr::INFO				=> self::LOG_INFO,
		psr::DEBUG				=> self::LOG_DEBUG,
	];

	/**
	 * @var array map PHP ErrorLevel to logging levels
	 */
	const PHP_TO_LOGGING		=
	[
		E_ERROR 				=> self::LOG_ERROR,
		E_WARNING 				=> self::LOG_WARNING,
		E_PARSE 				=> self::LOG_ERROR,
		E_NOTICE 				=> self::LOG_NOTICE,
		E_CORE_ERROR 			=> self::LOG_ERROR,
		E_CORE_WARNING 			=> self::LOG_WARNING,
		E_COMPILE_ERROR 		=> self::LOG_ERROR,
		E_COMPILE_WARNING 		=> self::LOG_WARNING,
		E_USER_ERROR 			=> self::LOG_ERROR,
		E_USER_WARNING 			=> self::LOG_WARNING,
		E_USER_NOTICE 			=> self::LOG_NOTICE,
		E_STRICT 				=> self::LOG_NOTICE,
		E_RECOVERABLE_ERROR 	=> self::LOG_ERROR,
		E_DEPRECATED 			=> self::LOG_WARNING,
		E_USER_DEPRECATED 		=> self::LOG_NOTICE,
		E_ALL 					=> self::LOG_ALWAYS,
	];

	/**
	 * @var array map PHP ErrorLevel to PSR LogLevel
	 */
	const PHP_TO_PRINT			=
	[
		E_ERROR 				=> psr::CRITICAL,
		E_WARNING 				=> psr::WARNING,
		E_PARSE 				=> psr::EMERGENCY,
		E_NOTICE 				=> psr::NOTICE,
		E_CORE_ERROR 			=> psr::ALERT,
		E_CORE_WARNING 			=> psr::WARNING,
		E_COMPILE_ERROR 		=> psr::CRITICAL,
		E_COMPILE_WARNING 		=> psr::WARNING,
		E_USER_ERROR 			=> psr::ERROR,
		E_USER_WARNING 			=> psr::WARNING,
		E_USER_NOTICE 			=> psr::NOTICE,
		E_STRICT 				=> psr::NOTICE,
		E_RECOVERABLE_ERROR 	=> psr::ERROR,
		E_DEPRECATED 			=> psr::WARNING,
		E_USER_DEPRECATED 		=> psr::NOTICE,
		// used to log under any selected level (self::LOG_ALWAYS)
		E_ALL					=> '-----',
	];

	/**
	 * @var array map PHP ErrorLevel to string
	 */
	const PHP_TO_STRING			=
	[
		E_ERROR 				=> 'E_ERROR',
		E_WARNING 				=> 'E_WARNING',
		E_PARSE 				=> 'E_PARSE',
		E_NOTICE 				=> 'E_NOTICE',
		E_CORE_ERROR 			=> 'E_CORE_ERROR',
		E_CORE_WARNING 			=> 'E_CORE_WARNING',
		E_COMPILE_ERROR 		=> 'E_COMPILE_ERROR',
		E_COMPILE_WARNING 		=> 'E_COMPILE_WARNING',
		E_USER_ERROR 			=> 'E_USER_ERROR',
		E_USER_WARNING 			=> 'E_USER_WARNING',
		E_USER_NOTICE 			=> 'E_USER_NOTICE',
		E_STRICT 				=> 'E_STRICT',
		E_RECOVERABLE_ERROR 	=> 'E_RECOVERABLE_ERROR',
		E_DEPRECATED 			=> 'E_DEPRECATED',
		E_USER_DEPRECATED 		=> 'E_USER_DEPRECATED',
		E_ALL 					=> 'E_ALL',
	];
}

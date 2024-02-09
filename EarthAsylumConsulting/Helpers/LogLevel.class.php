<?php
namespace EarthAsylumConsulting\Helpers;

/**
 * LogLevel class - define PHP/PSR log levels
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Helpers
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 */
class LogLevel
{
	/**
	 * @var string version
	 */
	const VERSION				= '23.0930.1';

	/**
	 * @var int PHP logging levels
	 */
	const PHP_ERROR				= E_USER_ERROR;
	const PHP_WARNING			= E_USER_WARNING;
	const PHP_NOTICE			= E_USER_NOTICE;
	const PHP_DEBUG				= E_STRICT;
	const PHP_ALWAYS			= E_ALL;

	/**
	 * @var string PSR logging levels
	 */
	const PSR_EMERGENCY 		= 'emergency';
	const PSR_ALERT				= 'alert';
	const PSR_CRITICAL			= 'critical';
	const PSR_ERROR				= 'error';
	const PSR_WARNING			= 'warning';
	const PSR_NOTICE			= 'notice';
	const PSR_INFO				= 'info';
	const PSR_DEBUG				= 'debug';

	/**
	 * @var array map PSR to PHP Logging
	 */
	const PSR_TO_LOGGING		=
	[
		self::PSR_EMERGENCY		=> self::PHP_ERROR,
		self::PSR_ALERT			=> self::PHP_ERROR,
		self::PSR_CRITICAL 		=> self::PHP_ERROR,
		self::PSR_ERROR 		=> self::PHP_ERROR,
		self::PSR_WARNING		=> self::PHP_WARNING,
		self::PSR_NOTICE		=> self::PHP_NOTICE,
		self::PSR_INFO			=> self::PHP_NOTICE,
		self::PSR_DEBUG			=> self::PHP_DEBUG,
	];

	/**
	 * @var array PHP error to PHP Logging
	 */
	const PHP_TO_LOGGING		=
	[
		E_ERROR 				=> self::PHP_ERROR,
		E_WARNING 				=> self::PHP_WARNING,
		E_PARSE 				=> self::PHP_ERROR,
		E_NOTICE 				=> self::PHP_NOTICE,
		E_CORE_ERROR 			=> self::PHP_ERROR,
		E_CORE_WARNING 			=> self::PHP_WARNING,
		E_COMPILE_ERROR 		=> self::PHP_ERROR,
		E_COMPILE_WARNING 		=> self::PHP_WARNING,
		E_USER_ERROR 			=> self::PHP_ERROR,
		E_USER_WARNING 			=> self::PHP_WARNING,
		E_USER_NOTICE 			=> self::PHP_NOTICE,
		E_STRICT 				=> self::PHP_DEBUG,
		E_RECOVERABLE_ERROR 	=> self::PHP_ERROR,
		E_DEPRECATED 			=> self::PHP_WARNING,
		E_USER_DEPRECATED 		=> self::PHP_NOTICE,
	];

	/**
	 * @var PHP error levels to name string
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
	];


	/**
	 * convert PSR-3 code or PHP error level to LogLevel::ERROR/WARNING/NOTICE/DEBUG
	 *
	 * @param string|int $level PSR-3 error code or PHP error level
	 * @return int - log level
	 */
	public static function setLoggingLevel($level): int
	{
		if (is_int($level) && isset(self::PHP_TO_LOGGING[$level]))
		{
			return self::PHP_TO_LOGGING[$level];
		}
		if (is_string($level) && isset(self::PSR_TO_LOGGING[$level]))
		{
			return self::PSR_TO_LOGGING[$level];
		}
		return intval($level);
	}
}

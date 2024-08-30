<?php
namespace EarthAsylumConsulting\Helpers;

use EarthAsylumConsulting\Helpers\LogLevel;
use Psr\Log\InvalidArgumentException;

/*
 * Example log() method :

	/ **
	 * PSR-3 Log
	 *
	 * @param 	int|string 	$level PHP or PSR error level
     * @param 	string|Stringable $message
     * @param 	array 		$context [key=>value] array for interpolation of $message
     *
     * @return object|bool|void logger object or false or void on log()
	 * /
    public function log($level = '', $message = '', array $context = [])
	{
		static $logger = null;

		if (is_null($logger))
		{
			$logger = new Logger();
		}
		if (func_num_args())
		{
			return $logger->log($level, (string)$message, $context);
		}
		return $logger;
	}
  */


/**
 * PSR-3 compatible Logger called from eacDoojigger logging functions
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Helpers
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0830.1
 */
class Logger extends \Psr\Log\AbstractLogger
{
	/**
	 * @var string logging action names
	 */
	const LOG_ACTION 			= 'eac_logger_output';	// actors subscribe to this action
	const LOG_READY 			= 'eac_logger_ready';	// fired when ready to send data to actors

	/**
	 * @var mixed 	logging queue
	 *		array	queue logging data
	 *		bool 	false = no logging
	 * 		null 	queueing disabled
	 */
	private static $log_queue	= [];


	/**
	 * set filter to trigger despooling at end of 'plugins_loaded'
	 *
	 */
	public function __construct()
	{
		\add_action( 'plugins_loaded', array( self::class, '_logger_ready'), PHP_INT_MAX );
	}


	/**
	 * outside actors may subscribe and provide the function to actually log the data
	 *
	 * @param callable - action callback
	 */
	public static function subscribe(callable $action): void
	{
		/**
		 * add action 'eac_logger_output'
		 * @param array [logging_level, message, context, original_level]
		 */
		\add_action( self::LOG_ACTION, $action );
	}


	/**
	 * Logs with an arbitrary level
	 *
	 * @param 	int|string 	$level
     * @param 	string|Stringable $message
     * @param 	array 		$context [key=>value] array for interpolation of $message
	 */
	public function log($level, $message, array $context = []): void
	{
		if (self::$log_queue === false)
		{
			return;		// no logging
		}

		if (!empty($context))
		{
			$message = self::interpolate($message,$context);
		}

		if (!isset($context['@time']))
		{
			$context['@time'] = microtime(true);
		}

		if (is_array(self::$log_queue))
		{
			// not logging yet
			self::$log_queue[] = [$level, (string)$message, $context];
		}
		else
		{
			// logging
			self::log_write($level, (string)$message, $context);
		}
	}


	/**
	 * output the log data as an array passes through a WP action
	 *
	 * @param 	int|string 	$level
     * @param 	string 		$message
     * @param 	array 		$context [key=>value] array for interpolation of $message
	 */
	private static function log_write($level, string $message, array $context = []): void
	{
		if (isset($context['@variable']))
		{
			$var = &$context['@variable'];
			if (is_wp_error($var))
			{
				$context['wp_error'] = $var;
				$var = array( \get_class($var) => [
					'code'		=> $var->get_error_code(),
					'message'	=> $var->get_error_message(),
					'data'		=> $var->get_error_data(),
				]);
			}
			else if (is_a($var,'\Throwable'))
			{
				$context['exception'] = $var;
				$var = array( \get_class($var) => [
					'code'		=> $var->getCode(),
					'message'	=> $var->getMessage(),
					'data'		=> ['file'=>$var->getFile(),'line'=>$var->getLine()],
				]);
			}
		}

		$logLevel = self::setLogLevel($level);
		/**
		 * do action 'eac_logger_output'
		 * actors subscribe to this action
		 * @param array [level, message, context, code]
		 */
		\do_action( self::LOG_ACTION,
			[
				'level' 		=> $logLevel,
				'message'		=> $message,
				'context'		=> $context,
				'error_code'	=> $level,						// PSR LogLevel or PHP ErrorLevel
				'print_code'	=> (is_string($level))? $level : LogLevel::PHP_TO_PRINT[$level],
			]
		);
	}


	/**
	 * convert PSR-3 code or PHP error level to LogLevel::ERROR/WARNING/NOTICE/DEBUG
	 *
	 * @param string|int $level PSR-3 error code or PHP error level
	 * @return int - log level
	 */
	private static function setLogLevel($level): int
	{
		if (is_int($level) && isset(LogLevel::PHP_TO_LOGGING[$level]))
		{
			return LogLevel::PHP_TO_LOGGING[$level];
		}
		if (is_string($level) && isset(LogLevel::PSR_TO_LOGGING[$level]))
		{
			return LogLevel::PSR_TO_LOGGING[$level];
		}
		throw new \Psr\Log\InvalidArgumentException(
			sprintf('The log level "%s" is invalid.', $level)
		);
	}


	/**
	 * Interpolates context values into the message placeholders.
	 *
     * @param 	string|Stringable $message
     * @param 	array 		$context [key=>value] array for interpolation of $message
	 * @return 	string
	 */
	public static function interpolate($message, array $context = []): string
	{
		if (is_array($message) || (is_object($message) && !method_exists($message, '__toString')))
		{
			return $message;
		}
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val)
		{
			// check that the value can be cast to string
			if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString')))
			{
				$replace['{' . $key . '}'] = $val;
			}
		}
		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}


	/**
	 * despool or clear logging queue.
	 * log entries are queued until outside actors have a chance to subscribe.
	 * if no subscribers, disable logging, else despool and disable queueing.
	 */
	public static function _logger_ready(): void
	{
		if (! has_action(self::LOG_ACTION))
		{
			self::$log_queue = false;				// disable logging
		}
		else if (is_array(self::$log_queue))
		{
			/**
			 * do action 'eac_logger_ready'
			 * fired when ready to send data to actors
			 */
			\do_action( self::LOG_READY );
			while (!empty(self::$log_queue))		// despool queue
			{
				$log = array_shift(self::$log_queue);
				self::log_write(...$log);
			}
			self::$log_queue = null; 				// disable queueing
		}
	}
}


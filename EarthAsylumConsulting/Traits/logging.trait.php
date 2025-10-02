<?php
namespace EarthAsylumConsulting\Traits;

use EarthAsylumConsulting\Helpers\Logger;
use Psr\Log\LogLevel;

/**
 * logger trait - logging functions using Logger healper
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		25.09266.1
 */
trait logging
{
	/**
	 * wp_error with logging
	 *
	 * @param	string|int|object	$code Error code or wp_error or throwable instance
	 * @param	string		$message Error message
	 * @param	mixed		$data optional, Error data
	 * @param	string		$id optional, source id
	 * @return	object		WP_Error object
	 */
	public function error($code, $message = '', $data = '', $id = '', $isCritical = false): object
	{
		if (is_wp_error($code))
		{
			$error = $code;
		}
		else if (is_a($code,'\Throwable'))
		{
			$error = new \WP_Error( $code->getCode(), $code->getMessage(), ['file'=>$code->getFile(),'line'=>$code->getLine()] );
		}
		else
		{
			$error = new \WP_Error( $code, $message, $data );
		}

		if (!empty($data))
		{
			$error->add_data($data);
		}

		if (empty($id))
		{
			$id = 'error '.$error->get_error_code();
		}

		$context = ['@variable' => $error];
		$this->log(($isCritical ? 'critical' : 'error'), $id, $context);
		return $error;
	}


	/**
	 * fatal error with logging
	 *
	 * @param	string|int	$code Error code
	 * @param	string		$message Error message
	 * @param	mixed		$data optional, Error data
	 * @param	string		$id optional, source id
	 * @return	void
	 */
	public function fatal($code, $message = '', $data = '', $id = null): void
	{
		$error = $this->error($code, $message, $data, $id, true);
		wp_die($error);
	}


	/**
	 * PSR-3 Log
	 *
	 * @param 	int|string 	$level PHP or PSR error level
     * @param 	string 		$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 		$context [key=>value] array for interpolation of $message
     *
     * @return object|void logger object or void on log()
	 */
    public function log($level = '', $message = '', array $context = [])
	{
		static $logger = null;

		if (is_null($logger))
		{
			$logger = new Logger();
		}
		if (func_num_args())
		{
			if (!isset($context['@source'])) {
				$context['@source']	= basename(str_replace('\\', '/', get_class($this)));
			}
			return $logger->log($level, (string)$message, $context);
		}
		return $logger;
	}


	/**
	 * console/file logging
	 *
	 * @param	int		$level the debugging log level
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logWrite($level, $variable, $message = '', array $context = []): void
	{
		$context['@variable'] = $variable;
		$this->log($level, (string)$message, $context);
	}


	/**
	 * console/file logging - info (notice)
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logInfo($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::INFO, $variable, $message, $context);
	}


	/**
	 * console/file logging - notice
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logNotice($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::NOTICE, $variable, $message, $context);
	}


	/**
	 * console/file logging - data (warning)
	 *
	 * @deprecated - use logDebug()
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logData($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::DEBUG, $variable, $message, $context);
	}


	/**
	 * console/file logging - warning
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logWarning($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::WARNING, $variable, $message, $context);
	}


	/**
	 * console/file logging - error
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logError($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::ERROR, $variable, $message, $context);
	}


	/**
	 * console/file logging - debug
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logDebug($variable, $message = '', array $context = []): void
	{
		$this->logWrite(LogLevel::DEBUG, $variable, $message, $context);
	}


	/**
	 * console/file logging - always
	 *
	 * @param	mixed	$variable variable to log, WP_Error object, throwable object
	 * @param	string	$message, optional (may identify source, i.e. __METHOD__)
     * @param 	array 	$context [key=>value] array for interpolation of $message
     * @return void
	 */
	public function logAlways($variable, $message = '', array $context = []): void
	{
		$this->logWrite(E_ALL, $variable, $message, $context);
	}
}

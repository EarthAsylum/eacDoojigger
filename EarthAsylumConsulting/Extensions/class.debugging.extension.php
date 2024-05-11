<?php
namespace EarthAsylumConsulting\Extensions;

use EarthAsylumConsulting\Helpers\LogLevel;

if (! class_exists(__NAMESPACE__.'\debugging_extension', false) )
{
	/**
	 * Extension: debugging - file logging & debugging - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class debugging_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '24.0422.1';

		/**
		 * @var array PHP to print string
		 */
		const PHP_TO_PRINT			=
		[
			LogLevel::PHP_ERROR		=> 'error',
			LogLevel::PHP_WARNING	=> 'warning',
			LogLevel::PHP_NOTICE	=> 'info',
			LogLevel::PHP_DEBUG		=> 'debug',
			LogLevel::PHP_ALWAYS	=> '-----', // used to log under any level

			E_ERROR 				=> 'critical',
			E_WARNING 				=> 'warning',
			E_PARSE 				=> 'critical',
			E_NOTICE 				=> 'notice',
			E_CORE_ERROR 			=> 'critical',
			E_CORE_WARNING 			=> 'warning',
			E_COMPILE_ERROR 		=> 'critical',
			E_COMPILE_WARNING 		=> 'warning',
			E_RECOVERABLE_ERROR 	=> 'error',
			E_DEPRECATED 			=> 'warning',
			E_USER_DEPRECATED 		=> 'notice',
		];

		/**
		 * @var internal variables
		 */
		private $logLevel 	= 0;
		private $logPath 	= null;
		private $logFile 	= null;
		private $logLength	= 8192;

		private $reqType 	= 'page';
		private $reqBreak 	= '+++';

		private $logData 	= null; // log data on page output
		private $logText 	= null; // log data on file output
		private $phpData 	= null; // log data on PHP errors
		private $wpData 	= null; // log data on WP errors

		private $current_user 	= null;

		/**
		 * @var object wp-config-transformer
		 */
		private $wpConfig = false;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL | self::DEFAULT_DISABLED);

			if ($this->is_admin())
			{
				$this->registerExtension( [ $this->className, 'debugging' ] );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}

			if ($this->isEnabled())
			{
				$this->logLength = min(ini_get('log_errors_max_len'), $this->logLength);

				if ( ($logLevel = $this->get_option('debug_log_level')) )
				{
					$this->logLevel = array_sum( array_map('intval', $logLevel) );
				}

				if ($this->is_option('debug_php_errors'))
				{
					$this->setErrorhandler();
				}
			}
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			include 'includes/debugging.options.php';
		}


		/**
		 * Add help tab on admin page
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
			if (!$this->plugin->isSettingsPage('Debugging')) return;
			include 'includes/debugging.help.php';
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled
			$this->current_user = wp_get_current_user();
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			if ($this->plugin->isSettingsPage('Debugging'))
			{
				// add additional css when our settings stylesheet loads.
				$this->add_action('admin_enqueue_styles', function($styleId)
				{
					$style =
						'#debug_backtrace,#debug_purge_time {width: 85%; max-width: 25em;}'.
						'#debug_backtrace_ticks,#debug_purge_time_ticks {'.
							'display: flex; width: 86%; max-width: 32em;'.
							'justify-content: space-between;'.
							'font-size: 0.85em;'.
							'padding: 0 0 0 0.2em;'.
						'}';
					wp_add_inline_style( $styleId, $style );
				});
			}

			/**
			 * action {pluginname}_log_write to log debug data
			 * @param	array	$args variable to log (level, data, context)
			 * @return	void
			 */
			if ( ($this->is_option('debug_to_file','plugin') && $this->setLoggingPathname(true)) || $this->is_option('debug_to_file','server') )
			{
				$this->add_action( 'log_write', 		array($this, 'write_log_array'), 10, 1 );
			}

			if ($this->is_option('debug_wp_errors'))
			{
				add_action( 'wp_error_added', 			array($this, 'capture_wp_error'), 10, 4);
			}

			if ($this->is_option('debug_depricated'))
			{
				add_action( 'doing_it_wrong_run', 		array($this, 'capture_doing_it_wrong'), 10, 3);
				add_action( 'deprecated_function_run', 	array($this, 'capture_deprecated'), 10, 3);
				add_action( 'deprecated_constructor_run',array($this, 'capture_deprecated'), 10, 3);
				add_action( 'deprecated_file_included', array($this, 'capture_deprecated'), 10, 3);
				add_action( 'deprecated_argument_run', 	array($this, 'capture_deprecated'), 10, 3);
				add_action( 'deprecated_hook_run', 		array($this, 'capture_deprecated'), 10, 4);
			}

			if ($this->is_option('debug_heartbeat'))
			{
				add_filter( 'heartbeat_received', 		array($this, 'capture_heartbeat'), PHP_INT_MAX, 3);
			}

			if ($this->is_option('debug_on_page') && user_can($this->current_user, 'manage_options'))
			{
				$this->add_action( 'log_write', 		array($this, 'save_data_array'), 10, 1 );
				if ($this->plugin->pluginHelpEnabled())
				{
					add_action( 'current_screen', 		array( $this, 'page_debugging_help'), 999);
				}
				else
				{
					add_action( 'wp_footer', 			array($this, 'page_debugging_output'), 999);	// the end of the <body>
					add_action( 'admin_footer', 		array($this, 'page_debugging_output'), 999);	// the end of the <body>
				}
			}

			/**
			 * action {pluginname}_daily_event to run daily  - (never runs for network_admin)
			 * @return	void
			 */
			$this->add_action( 'daily_event', 			array($this, 'purge_logs'), 10, 1 );

			/**
			 * filter {classname}_debugging add to the debugging arrays
			 * @return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$this->add_filter( 'debugging', 			array($this, 'debug_debugging'));

			/**
			 * filter {classname}_debuglog get the debugging log
			 * @return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$this->add_filter( 'debuglog', 				array($this, 'debug_logdata'));
		}


		/**
		 * install/uninstall actiontimer
		 *
		 * @param $action button value ('Install' | 'Uninstall')
		 * @return string $action
		 */
		public function install_actiontimer($action)
		{
			$this->installer->invoke($action,false,
				[
					'title'			=> 'The Plugin Action Timer',
					'sourcePath'	=> $this->pluginHeader('VendorDir').'/Utilities',
					'sourceFile'	=> 'eacDoojiggerActionTimer.class.php',
					'targetFile'	=> 'eacDoojiggerActionTimer.php',
				]
			);
			return $action;
		}


		/**
		 * filter for options_form_post_wp_debugging
		 *
		 * @param mixed		$value - the value POSTed
		 * @param string	$fieldName - the name of the field/option
		 * @param array		$metaData - the option metadata
		 * @param mixed		$priorValue - the previous value
		 * @return mixed $value
		 */
		public function options_form_post_wp_debugging($value, $fieldName, $metaData, $priorValue)
		{
			if ($value == $priorValue) return $value; 	// no change
			if (!$this->wpConfig) return $value;		// no configurator

			foreach (['WP_DEBUG','WP_DEBUG_DISPLAY','WP_DEBUG_LOG'] as $wpdebug)
			{
				switch ($wpdebug)
				{
					case 'WP_DEBUG_LOG':	// retain debug.log path if set
						$wp_debug_log = (defined('WP_DEBUG_LOG')) ? WP_DEBUG_LOG : false;
						$path = (!is_bool($wp_debug_log))
							? WP_DEBUG_LOG
							: $this->get_option('wp_debug_log',null);
						if (in_array($wpdebug,$value)) { 	// true
							if ($path) {
								$this->wpConfig->update( 'constant', $wpdebug, $path );
							} else {
								$this->wpConfig->update( 'constant', $wpdebug, 'TRUE', ['raw'=>true] );
							}
						} else {							// false
							$this->wpConfig->update( 'constant', $wpdebug, 'FALSE', ['raw'=>true] );
						}
						if ($path && $wp_debug_log !== true) {
							$this->update_option('wp_debug_log',$path);
						} else {
							$this->delete_option('wp_debug_log');
						}
						break;
					default:
						$bool = (is_array($value) && in_array($wpdebug,$value)) ? 'TRUE' : 'FALSE';
						$this->wpConfig->update( 'constant', $wpdebug, $bool, ['raw'=>true] );
				}
			}
			return $value;
		}


		/**
		 * capture wp errors when a new error is added - wp_error_added hook
		 *
		 * @param string $code wp_error code
		 * @param string $message wp_error message
		 * @param mixed $data wp_error data
		 * @param object $wp_error wp_error object
		 * @return void
		 */
		public function capture_wp_error($code, $message, $data, $wp_error)
		{
			$error_message = sprintf('WP_Error::%1$s %2$s',$code,$message);
			$this->wpData[] = $error_message;
			$this->plugin->logError($message,"WP_Error::{$code}");
		}


		/**
		 * capture wp 'doing it wrong' errors - doing_it_wrong_run hook
		 *
		 * @param string $function the function name
		 * @param string $message the message
		 * @param string $version  the WP version
		 * @return void
		 */
		public function capture_doing_it_wrong($function, $message, $version)
		{
			$error_message 	= sprintf('doing_it_wrong::%1$s %2$s %3$s',$function,$message,$version);
			$this->wpData[] = $error_message;
			$backtraceTo = $this->get_option('debug_backtrace');
			$error_trace 	= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$backtraceTo);
			$this->plugin->logError($error_trace,$error_message);
		}


		/**
		 * capture wp 'deprecated' errors - several deprecated_*_run hooks
		 *
		 * @param $args arguments vary by hook being captured
		 * @return void
		 */
		public function capture_deprecated(...$args)
		{
			$error_message 	= basename(current_action(),'_run').'::'.rtrim(implode(' ',func_get_args()));
			$this->wpData[] = $error_message;
			$backtraceTo = $this->get_option('debug_backtrace');
			$error_trace 	= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$backtraceTo);
			$this->plugin->logError($error_trace,$error_message);
		}


		/**
		 * capture wp 'heartbeat'
		 *
		 * @param array $response response data set by filters
		 * @param array $data request data set by browser
		 * @param string $screen_id wp screen id
		 * @return array $response
		 */
		public function capture_heartbeat($response, $data, $screen_id)
		{
			global $wp_filter;
			$filters = [];
			$hooks = $wp_filter['heartbeat_received']->callbacks;
			foreach ($hooks as &$hook)
			{
				foreach ($hook as $fn)
				{
					switch (true) {
						case is_string($fn['function']) && strpos($fn['function'], '::'):
							$fn['function'] = explode('::',$fn['function']);
							//break;
						case is_string($fn['function']):
							$ref = new \ReflectionFunction($fn['function']);
							$filters[] = [
								'[function]'=> $fn['function'] . '()',
								'[file]' 	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_array($fn['function']) && is_object($fn['function'][0]):
							$ref = new \ReflectionMethod($fn['function'][0],$fn['function'][1]);
							$filters[] = [
								'[method] ' => get_class($fn['function'][0])  . '->' . $fn['function'][1] . '()',
								'[file]' 	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_array($fn['function']):
							$ref = new \ReflectionMethod($fn['function'][0],$fn['function'][1]);
							$filters[] = [
								'[static] ' => $fn['function'][0]  . '::' . $fn['function'][1] . '()',
								'[file]' 	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case $fn['function'] instanceof \Closure:
							$ref = new \ReflectionFunction($fn['function']);
							$filters[] = [
								'[closure]' => 'function()',
								'[file]' 	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_object($fn['function']):
							$ref = new \ReflectionMethod($fn['function'],'__invoke');
							$filters[] = [
								'[invokable]' => get_class($fn['function']) . '()',
								'[file]' 	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						default:
							$filters[] = [
								'[unknown] ' => gettype($fn['function'])
							];
					}
				}
			}
			$this->plugin->logData(
				[
				//	'Hooks'	=>	max(0, (count($wp_filter['heartbeat_received']->callbacks)-1) ),
					'Callbacks'		=>	$filters,
					'Input Data'	=>	$data,
					'Response Keys'	=>	array_keys($response),
					'Screen Id'		=>	$screen_id,
				],'WP_Heartbeat API');
			return $response;
		}


		/**
		 * {pluginName}_log_write called from plugin->log...
		 *
		 * @param array argument array
		 *  	int		$level - log level (LogLevel::PHP_*)
		 *  	mixed	$var - variable being logged
		 *  	string	$context - context string
		 *  	float	$mtime - mico-time (optional)
		 * @return void
		 */
		public function save_data_array($args)
		{
			$this->saveData(...$args);
		}


		/**
		 * {pluginName}_log_write called from plugin->log...
		 *
		 * @param array argument array
		 *  	int		$level - log level (LogLevel::PHP_*)
		 *  	mixed	$var - variable being logged
		 *  	string	$context - context string
		 *  	float	$mtime - mico-time (optional)
		 * @return void
		 */
		public function write_log_array($args)
		{
			$this->logWrite(...$args);
		}


		/**
		 * filter for current_screen, help screen
		 *
		 * @param object $screen current_screen
		 * @return string
		 */
		public function page_debugging_help($screen=null)
		{
			$this->plugin->addPluginHelpTab('[debugging_log]',
				"<p>".$this->plugin->pluginHeader('Title')." Debugging output</p>", null, PHP_INT_MAX
			);

			if (!$this->plugin->isSettingsPage())
			{
				$this->plugin->plugin_help_render($screen);
			}

			add_action( 'admin_footer', function()
			{
				/**
				 * 	filter {pluginname}_debugging get debugging information
				 * 	param	array 	current array
				 * 	return	array	extended array with [ extension_name => [key=>value array] ]
				 */
				$response = $this->apply_filters( 'debugging', [] );
				foreach ($response as $name => $value)
				{
					if (!$this->plugin->isSettingsPage() && $name != 'Log Data') continue;

					$this->plugin->addPluginHelpField('[debugging_log]','',
						"<details><summary>{$name}</summary><pre>".var_export($value,true)."</pre></details>"
					);
				}
			});
		}


		/**
		 * filter for wp_footer, admin_footer
		 *
		 * @return string
		 */
		public function page_debugging_output()
		{
			echo "<details style='position: relative; float: right; font-family:monospace; font-size:12px; ".
				 "border: 1px solid red; margin: 1em; padding: .5em; background: #f3fbdd; max-width: 45%; overflow: scroll;'>\n";

			echo "<summary style='font-weight: bold'>Debugging...</summary>\n";
			echo "<pre style='font-size:1em; line-height:1.1em; background:#f3fbdd;'>\n";

			/**
			 * 	filter {pluginname}_debugging get debugging information
			 * 	param	array 	current array
			 * 	return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$response = $this->apply_filters( 'debugging', [] );
			foreach ($response as $name => $value)
			{
				echo "<details><summary>+{$name}</summary>".@var_export($value,true)."</details>\n";
			}
			echo "</pre></details>\n";
		}


		/**
		 * purge old log files - (never runs for network_admin)
		 *
		 * @return 	void
		 */
		public function purge_logs($asNetAdmin=false)
		{
			if (!$this->setLoggingPathname(false,$asNetAdmin) || !is_dir($this->logPath)) return;

			$weeks = ($asNetAdmin)
					? intval($this->is_network_option('debug_purge_time'))
					: intval($this->is_option('debug_purge_time'));
			if ($weeks < 1) return;
			$timeNow = time();
			$timeOld = WEEK_IN_SECONDS * $weeks;

			if ($fs = $this->fs->load_wp_filesystem())
			{
				$fsLogPath = $fs->find_folder($this->logPath);
				$fsIterator = $fs->dirlist($fsLogPath,false);
				$this->logDebug($fsIterator,__METHOD__);
				foreach ($fsIterator as $file)
				{
					if ( $file['type'] == 'f' && ($timeNow - $file['lastmodunix']) > $timeOld )
					{
						$fs->delete($fsLogPath.'/'.$file['name']);
					}
				}
			}
			else
			{
				$fsIterator = new \FilesystemIterator($this->logPath, \FilesystemIterator::SKIP_DOTS);
				foreach ($fsIterator as $file)
				{
					if ( ($timeNow - $file->getMTime()) > $timeOld )
					{
						unlink($file->getPathname());
					}
				}
			}

			if (!$asNetAdmin && is_multisite() && is_main_site())
			{
				$this->purge_logs(true);
			}
		}


		/**
		 * Get the debugging array for our own filter
		 *
		 * @param	array 	current debugging array
		 * @return	array	extended array with [ extension_name => [key=>value array] ]
		 */
		public function debug_debugging($debugging_array)
		{
			$debugging_array['Plugin Detail'] = array_merge($this->plugin->pluginHeaders(),array(
				"Plugin Updated"	=> wp_date($this->plugin->date_time_format,filemtime($this->plugin->pluginHeader('PluginDir'))),
				"Plugin Environment"=> $this->get_option('siteEnvironment'),
				"WP Admin"			=> (is_admin()) ? 'True' : 'false',
				"WP Multi Site"		=> (is_multisite()) ? 'True' : 'false',
				"WP Network Admin"	=> (is_network_admin()) ? 'True' : 'false',
				"WP Current User"	=> ($this->current_user) ? $this->current_user->data : 'none',
				"WP Page Parent"	=> (function_exists('get_admin_page_parent')) ? get_admin_page_parent() : 'undefined',
			));

			/*
			$debugging_array['Plugin Options'] 	= (is_network_admin()) ? $this->plugin->networkOptions : $this->plugin->options;

			if (method_exists($this->plugin, 'getOptionMetaData')) {
				$debugging_array['Option Metadata'] = (is_network_admin()) ? $this->plugin->getNetworkMetaData() : $this->plugin->getOptionMetaData();
			}
			*/

			$extensions = array();
			foreach ($this->plugin->extension_objects as $name => $object) {
				$extensions[$name] = array(
					'Version' => $object->getVersion(),
					'Enabled' => ($object->isEnabled()) ? 'true' : 'false',
				);
			}
			$debugging_array['Plugin Extensions'] = $extensions;

			if (!empty($this->phpData)) {
				$debugging_array['PHP Errors'] = $this->phpData;
			}

			if (!empty($this->wpData)) {
				$debugging_array['WP Errors'] = $this->wpData;
			}

			if (!empty($this->logData)) {
				$debugging_array['Log Data'] = $this->logData;
				$debugging_array['Log Data'][] = 'PHP Memory Used: '.round((memory_get_peak_usage(false) / 1024) / 1024).'M of '.ini_get('memory_limit');
			}

		//	$debugging_array['Hooks'] = $this->getRegisteredHooks('the_content');
			if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false)) {
				$debugging_array['WordPress Timing'] = \EarthAsylumConsulting\eacDoojiggerActionTimer::getArray();
			}

			return $debugging_array;
		}


		/**
		 * Get the debugging log array
		 *
		 * @return	array	array with [ 'LogData' => log entries ]
		 */
		public function debug_logdata()
		{
			return ['LogData' => explode("\n",$this->logText)];
		}


		/**
		 * set PHP error handler
		 *
		 * @return void
		 */
		private function setErrorhandler()
		{
			set_error_handler(array($this, 'phpErrorHandler'), ($this->is_option('debug_php_errors','All')) ? E_ALL : error_reporting() );
			//set_exception_handler(array($this, 'phpErrorHandler'));				// Uncaught exceptions (not recommended)
		}


		/**
		 * Handle PHP errors
		 *
		 * @return void
		 */
		public function phpErrorHandler()
		{
			$backtraceTo = $this->get_option('debug_backtrace');

			if (error_reporting() == 0) return true;	// ignore '@' functions
			$errReorting = error_reporting(0);

			try {
				$error = array();
				if (func_num_args() > 3) 					// PHP error
				{
					list($error['type'], $error['message'], $error['file'], $error['line']) = func_get_args();
					if (($this->logLevel & LogLevel::PHP_DEBUG)) {
						$error['trace']	 = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$backtraceTo);
					}
				}
				else 										// exception object
				{
					$exc = func_get_arg(0);
					$error['type']		= E_UNCAUGHT_EXCEPTION;
					$error['code']		= $exc->getCode();
					$error['message']	= $exc->getMessage();
					$error['file']		= $exc->getFile();
					$error['line']		= $exc->getLine();
					if (($this->logLevel & LogLevel::PHP_DEBUG)) {
						$error['trace']	= array_slice($exc->getTrace(),0,$backtraceTo);
					}
				}
				$level = $error['type'];
				$error['type'] = $this->phpErrorLevel($error['type']);
				$this->plugin->log_write($level,$error,'php_error_handler');
			} catch (\Throwable $e) {
				restore_error_handler();
				trigger_error($e->getMessage()." (".$e->getLine()." of ".$e->getFile().")",LogLevel::PHP_WARNING);
				$this->setErrorhandler();
			}

			$errType = ucwords( str_replace('_',' ', strtolower( substr($error['type'],2) ) ) );
			$this->phpData[] = "PHP {$errType}: {$error['message']} in {$error['file']} on line {$error['line']}";

			error_reporting($errReorting);
			return false; 									// allow PHP to handle the error
		}


		/**
		 * convert PHP error level to string constant name
		 *
		 * @param int $level PHP error level
		 * @return string - friendly error level name
		 */
		private function phpErrorLevel($level): string
		{
			return LogLevel::PHP_TO_STRING[$level] ?? 'E_UNKNOWN_'.(string)$level;
		}


		/**
		 * PHP error level to print string
		 *
		 * @return int - log level
		 */
		private function phpLogString($level): string
		{
			return self::PHP_TO_PRINT[$level] ?? (string)$level;
		}


		/**
		 * save data to output on page footer
		 *
		 * @param int		$level - log level (LogLevel::PHP_*)
		 * @param mixed		$var - variable being logged
		 * @param string	$context - context string
		 * @param float		$mtime - mico-time (optional)
		 * @param string	$source - used internally
		 * @return bool
		 */
		private function saveData($level, $var, $context='', $mtime=null, $source='')
		{
			if ( !($this->logLevel & LogLevel::setLoggingLevel($level)) || $context == 'php_error_handler')
			{
				return;
			}

			$this->logData[] = $this->logMessage($var, $context, false);
		}


		/**
		 * Write to the log file.
		 * we don't actually "write" anything but prep the string and add it to the text string.
		 *
		 * @param int		$level - log level (LogLevel::PHP_*)
		 * @param mixed		$var - variable being logged
		 * @param string	$context - context string
		 * @param float		$mtime - mico-time (optional)
		 * @param string	$source - used internally
		 * @return bool
		 */
		private function logWrite($level, $var, $context='', $mtime=null, $source='')
		{
			if ( !($this->logLevel & LogLevel::setLoggingLevel($level)) )
			{
				return;
			}

			// write to system log file
			if ($this->is_option('debug_to_file','server') && $context != 'php_error_handler')
			{
				$message = $this->logMessage($var, $context, false);
				$message = substr($message, 0, $this->logLength);
				error_log( $message );
			}

			// write to plugin log file
			if ($this->is_option('debug_to_file','plugin') && $this->logPath)
			{
				if (!$this->logFile && !$this->logOpen())
				{
					return ($this->logPath = false);
				}

				if (!$mtime) $mtime = microtime(true);
				list($sec,$usec) = explode('.',sprintf('%01.4f',$mtime));

				$this->logText .= $this->reqBreak." ".sprintf('%-20s %-10s [%s] - ', $source, $this->phpLogString($level), wp_date("Y-m-d H:i:s.",$sec).$usec);
				$this->logText .= $this->logMessage($var, $context, true) . "\n";
			}
		}


		/**
		 * get log data message
		 *
		 * @param mixed	$var - variable being logged
		 * @param string|array $context - context string or key=>value array
		 * @param bool $detailed - output detail object data
		 * @return bool
		 */
		private function logMessage($var, $context='', $detailed=true)
		{
			if (is_array($context))
			{
				$var = $this->psrInterpolate($var,$context);
				$context = $context['log_context'] ?? '';
			}

			return trim( (string) $context.": ".$this->logVariable($var,$detailed) );
		}


		/**
		 * Interpolates context values into the message placeholders.
		 *
		 * @param mixed	$var - variable being logged
		 * @param array $context - key=>value array
		 * @return string
		 */
		private function psrInterpolate($var, array $context = array())
		{
			if (is_array($var) || (is_object($var) && !method_exists($var, '__toString')))
			{
				return $var;
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
			return strtr($var, $replace);
		}


		/**
		 * check data being logged
		 *
		 * @return bool
		 */
		private function logVariable($var, $detailed=true)
		{
			switch (true)
			{
				case is_scalar($var):
					return $var;
					break;
				case is_bool($var):
					return ($var) ? 'TRUE' : 'FALSE';
					break;
				case is_null($var):
					return 'NULL';
					break;
				case is_object($var):
					if (method_exists($var, '__toString')) {
						return $var->__toString();
					}
					if (!$detailed || !($this->logLevel >= LogLevel::PHP_ERROR)) {
						return ucfirst(\gettype($var).' '.\get_class($var));
					}
					$reflect = new \ReflectionObject($var);
					$return = [
						$reflect->getShortName() => $reflect->getName(),
					];
					//if ($prop = $reflect->getConstants()) {
					//	$return['Constants'] = $prop;
					//}
					if ($prop = $reflect->getProperties()) {
						$return['Properties'] = [];
						foreach($prop as $p) {
							$value = ($p->IsPublic()) ? $p->getValue($var) : ( ($p->IsPrivate()) ? '(private)' : '(protected)');
							$return['Properties'][$p->getName()] = $this->logVariable($value,$detailed);
						}
					}
					$var = $return;
					// no break;
				default:
					if (!$detailed) {
						return ucfirst(\gettype($var));
					}
					try {
						return @var_export($var,true);
					} catch (\Throwable $e) {
						return @var_export($e,true);
					}
					break;
			}
		}


		/**
		 * Open the log file when needed.
		 * we don't actually "open" anything but verify access and touch the file.
		 *
		 * @return bool
		 */
		private function logOpen()
		{
			if (!$this->isEnabled() || empty($this->logPath)) return false;

			$file = $this->logPath."/".$this->pluginName."_".wp_date('Ymd').".log";

			if ($fs = $this->fs->load_wp_filesystem())
			{
				if (!$fs->exists($file)) {
					if (($fsLogPath = $fs->find_folder(dirname($file))) && $fs->is_writable($fsLogPath)) {
						$fsLogPath .= basename($file);
						// since we write to this not using $fs, we need onwner & group write access
						$fs->put_contents($fsLogPath,'',FS_CHMOD_FILE|0660);
					}
				}
			}
			else
			{
				$this->logFile = touch($file);
			}

			if (!file_exists($file)) {
				$this->add_admin_notice($this->pluginName.' Debugging: Unable to access log file','error');
				trigger_error('unable to access log file '.$file,E_USER_WARNING);
				return false;
			}

			$this->logFile = $file;

			if ($this->plugin->isAjaxRequest()) {
				$this->reqType = 'Ajax';
				$this->reqBreak = '---';
			} else {
				$this->reqType = 'Page';
				$this->reqBreak = '+++';
			}

			$this->logText .= "\n".str_repeat($this->reqBreak,30)."\n";

			$startTime = $this->plugin->pluginHeader('RequestTime');

			$headers = ($this->logLevel & LogLevel::PHP_DEBUG) ? $this->getHeaders() : $this->reqBreak;

			$this->logWrite(LogLevel::PHP_ALWAYS, $headers, $this->requestURL(), $startTime, $this->reqType." Request");

			if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
				$this->logWrite(LogLevel::PHP_DEBUG, $_POST, 'Post values', $startTime);
			}

			\add_action( 'shutdown', array($this,'logClose'),PHP_INT_MAX );
			return true;
		}


		/**
		 * Finish and close the log.
		 * now we write the data to the file and clean-up.
		 *
		 * @return void
		 */
		public function logClose()
		{
			$mtime = microtime(true);
			list($sec,$usec) = explode('.',sprintf('%01.4f',$mtime));

			if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false)) {
				$this->logWrite(LogLevel::PHP_DEBUG,\EarthAsylumConsulting\eacDoojiggerActionTimer::getArray(),'WordPress Timing');
			}

			$this->logWrite(LogLevel::PHP_DEBUG,round((memory_get_peak_usage(false) / 1024) / 1024).'M of '.ini_get('memory_limit'),'PHP Memory Used');

			$headers = ($this->logLevel & LogLevel::PHP_DEBUG) ? headers_list() : $this->reqBreak;

			$this->logWrite(LogLevel::PHP_ALWAYS, $headers, $this->requestURL(), null, $this->reqType." Exit");

			$this->logText .= str_repeat($this->reqBreak,30)."\n";

			// wp_filesystem has no way to append to a file
			file_put_contents($this->logFile, $this->logText, FILE_APPEND|LOCK_EX);
			$this->logFile = $this->logPath = $this->logText = false;
		}


		/**
		 * Log request server values
		 *
		 * @return void
		 */
		public function requestURL()
		{
			global $argv;
			$http = (is_ssl()) ? "https://" : "http://";

			if ( (PHP_SAPI === 'cli') || (defined('WP_CLI') && WP_CLI) ) {
				$_SERVER['REQUEST_METHOD'] 	= 'CLI';
				$_SERVER['HTTP_HOST'] 		= parse_url(home_url(),PHP_URL_HOST);
				$_SERVER['SERVER_NAME'] 	= gethostname();
				$_SERVER['REQUEST_URI'] 	= (!empty($argv)) ? implode(' ',$argv) : '';
			}

			return sanitize_text_field( $_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'].' '.$http.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
		}


		/**
		 * get all request headers (maybe)
		 *
		 * @return array
		 */
		private function getHeaders()
		{
			if (function_exists('getallheaders')) {
				$headers = getallheaders();
			} else {
				$headers = [];
				foreach ($_SERVER as $name => $value) {
					$name = explode('_' ,$name);
					if (array_shift($name) == 'HTTP') {
						array_walk($name,function(&$v){$v=ucfirst(strtolower($v));});
						$headers[implode('-',$name)] = $value;
					}
				}
			}

			$result = [];
			foreach ($headers as $name => $value) {
				$result[] = "{$name}: {$value}";
			}
			return $result;
		}


		/**
		 * check for writeable folder using wp_filesystem
		 *
		 * @param object $fs WP_Filesystem
		 * @param string $logPath full path to log folder
		 * @param bool $exists log path must already exists
		 * @return 	bool
		 */
		private function isWriteablePath($fs,$logPath,$exists=false)
		{
			if ($exists && !is_dir($logPath)) return false;
			if  ($fs)
			{
				return (($fsLogPath = $fs->find_folder($logPath)) && $fs->is_writable($fsLogPath))
					? $logPath
					: false;
			}
			else
			{
				return (is_writable($fsLogPath))
					? $logPath
					: false;
			}
		}


		/**
		 * set (create) log file path
		 *
		 * @return 	bool
		 */
		private function setLoggingPathname($create=false,$asNetAdmin=false)
		{
			if (! $this->is_option('debug_to_file_allowed')) return false;

			if  (! ($fs = $this->fs->load_wp_filesystem()) ) return false;
			$create = $create && $fs;

			$logPath = false;
			$logFolder = sanitize_key($this->pluginName.'_logs');

			// check for writeable folder defined by WP_DEBUG_LOG
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
				if ($logPath = $this->isWriteablePath($fs, realpath(dirname(WP_DEBUG_LOG)))) {
					$logPath = trailingslashit($logPath).$logFolder;
				}
			}
			// check for existing, writeable folder at wp-content/uploads/{$logFolder}
			if (!$logPath) {
				$uploads = trailingslashit(wp_get_upload_dir()['basedir']);
				$logPath = $this->isWriteablePath($fs, $uploads.$logFolder, true);
			}
			// set default folder at wp-content
			if (!$logPath) {
				$logPath = WP_CONTENT_DIR.'/'.$logFolder;
			}

			if (!is_dir($logPath) && $create)
			{
				if (($fsLogPath = $fs->find_folder(dirname($logPath))) && $fs->is_writable($fsLogPath)) {
					$fsLogPath .= basename($logPath);
					// since we write to this not using $fs, we need onwner & group write access
					$fs->mkdir($fsLogPath,FS_CHMOD_DIR|0660);
				}
			}

			if (is_multisite())
			{
				$logPath 	 = trailingslashit($logPath);
				$logPath 	.= (is_network_admin() || $asNetAdmin)
								? sanitize_text_field(\get_network_option(null,'site_name'))
								: sanitize_text_field(\get_option('blogname'));
				if (!is_dir($logPath) && $create)
				{
					if (($fsLogPath = $fs->find_folder(dirname($logPath))) && $fs->is_writable($fsLogPath)) {
						$fsLogPath .= basename($logPath);
						// since we write to this not using $fs, we need onwner & group write access
						$fs->mkdir($fsLogPath,FS_CHMOD_DIR|0660);
					}
				}
			}

			if (!is_writable($logPath))
			{
				$this->add_admin_notice($this->pluginName.' Debugging: Unable to access log path','error',
					'File logging has been disabled');
				trigger_error($this->pluginName.': unable to access log path '.$logPath,E_USER_WARNING);
				$this->update_option('debug_to_file_allowed','no');
				return false;
			}

			$this->logPath = $logPath;
			return true;
		}
	}
}
/*
 * return a new instance of this class
 */
if (isset($this)) return new debugging_extension($this);
?>

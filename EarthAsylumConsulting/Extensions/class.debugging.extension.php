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
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see			https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class debugging_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '24.0830.1';

		/**
		 * @var internal variables
		 */
		private $logLevel		= 0;		// sets the combined log levels (like php error reporting)
		private $logPath		= null;		// directory path to logs
		private $logFile		= null;		// log file resource
		private $logLength		= 8192;		// error_log max length

		private $reqType		= '';		// ajax, cron, rest, xml, http(s)

		private $logData		= null;		// log data on page output
		private $logText		= null;		// log data on file output
		private $phpData		= null;		// log data on PHP errors
		private $wpData			= null;		// log data on WP errors

		private $current_user	= null;
		private $wpConfig		= null;

		/**
		 * previous error/exception handlers
		 */
		 private $previous_error_handler;
		 private $previous_exception_handler;

		/**
		 * constructor method
		 *
		 * @param	object	$plugin main plugin object
		 * @return	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL /*| self::ALLOW_NON_PHP*/ | self::DEFAULT_DISABLED);

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
		 * @return	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled
			$this->current_user = wp_get_current_user();

		/* using PSR-3 logging
			$this->plugin->log('critical','This is a critical message');
			$this->plugin->log('emergency','This is an emergency message');
			$this->plugin->log('notice','This is a notice message');
			$this->plugin->log('data','This is a data message',['@variable'=>array()]);
			$this->plugin->log()->alert('This is an alert message',['@source'=>$this->className]);
			$logger = $this->plugin->log();
			$logger->warning('This is a warning message');

			$this->plugin->error('error())','WP_ERROR passed as $variable');
			//$this->plugin->fatal('fatal()','fatal message',['some data']);
		*/
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
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$this->update_option('debug_to_file_allowed','yes');
				}
				// add additional css when our settings stylesheet loads.
				$this->add_action('admin_enqueue_styles', function($styleId)
				{
					$style =
						'#debug_backtrace,#debug_purge_time {width: 85%; max-width: 25em;}'.
						'#debug_backtrace-ticks,#debug_purge_time-ticks {'.
							'display: flex; width: 86%; max-width: 32em;'.
						'}';
					wp_add_inline_style( $styleId, $style );
				});
			}

			// subscribe to Logger action - file output
			if ( ($this->is_option('debug_to_file','plugin') && $this->setLoggingPathname(true)) || $this->is_option('debug_to_file','server') )
			{
				$this->plugin->Log()->subscribe([$this,'file_log_data']);
			}

			// subscribe to Logger action - page output
			if ($this->is_option('debug_on_page') && user_can($this->current_user, 'manage_options'))
			{
				$this->plugin->Log()->subscribe([$this,'page_log_data']);
				if ($this->plugin->pluginHelpEnabled())
				{
					add_action( 'current_screen',		array( $this, 'page_debugging_help'), PHP_INT_MAX);
				}
				else
				{
					add_action( ($this->is_admin() ? 'admin_' : 'wp_').'footer',
														array($this, 'page_debugging_output'), PHP_INT_MAX);
				}
			}

			if ($this->is_option('debug_wp_errors'))
			{
				add_action( 'wp_error_added',			array($this, 'capture_wp_error'), 10, 4);
			}

			if ($this->is_option('debug_heartbeat'))
			{
				add_filter( 'heartbeat_received',		array($this, 'capture_heartbeat'), PHP_INT_MAX, 3);
			}

			if ($this->is_option('debug_depricated') &&
				! (defined('WP_DEBUG_LOG') && WP_DEBUG && $this->logLevel & LogLevel::LOG_NOTICE))
			{
				// if logging notices, wp_trigger_error will catch these
				add_action( 'doing_it_wrong_run',		array($this, 'capture_deprecated_wrong'), 10, 3);
				add_action( 'deprecated_function_run',	array($this, 'capture_deprecated_wrong'), 10, 3);
				add_action( 'deprecated_class_run',		array($this, 'capture_deprecated_wrong'), 10, 3);
				add_action( 'deprecated_constructor_run',array($this, 'capture_deprecated_wrong'), 10, 3);
				add_action( 'deprecated_file_included', array($this, 'capture_deprecated_wrong'), 10, 4);
				add_action( 'deprecated_argument_run',	array($this, 'capture_deprecated_wrong'), 10, 3);
				add_action( 'deprecated_hook_run',		array($this, 'capture_deprecated_wrong'), 10, 4);
			}

			/**
			 * action {pluginname}_daily_event to run daily	 - (never runs for network_admin)
			 * @return	void
			 */
			$this->add_action( 'daily_event',			array($this, 'purge_logs'), 10, 1 );

			/**
			 * filter {classname}_debugging add to the debugging arrays
			 * @return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$this->add_filter( 'debugging',				array($this, 'debug_debugging'),5);

			/**
			 * filter {classname}_debuglog get the debugging log
			 * @return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$this->add_filter( 'debuglog',				array($this, 'debug_logdata'));
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
			if ($value == $priorValue) return $value;	// no change
			// wpConfig set in debugging.options.php
			if (!$this->wpConfig) return $value;		// no configurator

			foreach (['WP_DEBUG','WP_DEBUG_DISPLAY','WP_DEBUG_LOG'] as $wpdebug)
			{
				switch ($wpdebug)
				{
					case 'WP_DEBUG_LOG':	// retain debug.log path if set
						$wp_debug_log = (defined('WP_DEBUG_LOG')) ? WP_DEBUG_LOG : false;
						$path = (!is_bool($wp_debug_log))
							? $this->wpConfig->get_value( 'constant', 'WP_DEBUG_LOG' )
							: $this->get_option('wp_debug_log',null);
						if (in_array($wpdebug,$value)) {	// true
							if ($path) {
								$this->wpConfig->update( 'constant', $wpdebug, $path, ['raw'=>true] );
							} else {
								$this->wpConfig->update( 'constant', $wpdebug, 'TRUE', ['raw'=>true] );
							}
						} else {							// false
							$this->wpConfig->update( 'constant', $wpdebug, 'FALSE', ['raw'=>true] );
						}
						if ($path && !is_bool($wp_debug_log)) {
							$this->update_option('wp_debug_log',$path);
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
		 * capture wp 'deprecated' & 'doing_it_wrong' errors
		 *
		 * @param $args arguments vary by hook being captured
		 * @return void
		 */
		public function capture_deprecated_wrong(...$args)
		{
			foreach($args as &$value) {
				$value = $this->clean_filepath($value);
			}
			$error_source 	= basename(current_action(),'_run');
			$error_trigger	= $error_source.'_trigger_error';
			$error_message	= $error_source.'::'.implode(', ',$args);
			$error_trace 	= [ 'trace'=>$this->print_backtrace(debug_backtrace( false )) ];
			$this->plugin->logWrite(E_USER_DEPRECATED,$error_trace,$error_message,['@source'=>$error_source]);
			$this->wpData[] = $error_message;
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
								'[file]'	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_array($fn['function']) && is_object($fn['function'][0]):
							$ref = new \ReflectionMethod($fn['function'][0],$fn['function'][1]);
							$filters[] = [
								'[method] ' => get_class($fn['function'][0])  . '->' . $fn['function'][1] . '()',
								'[file]'	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_array($fn['function']):
							$ref = new \ReflectionMethod($fn['function'][0],$fn['function'][1]);
							$filters[] = [
								'[static] ' => $fn['function'][0]  . '::' . $fn['function'][1] . '()',
								'[file]'	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case $fn['function'] instanceof \Closure:
							$ref = new \ReflectionFunction($fn['function']);
							$filters[] = [
								'[closure]' => 'function()',
								'[file]'	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						case is_object($fn['function']):
							$ref = new \ReflectionMethod($fn['function'],'__invoke');
							$filters[] = [
								'[invokable]' => get_class($fn['function']) . '()',
								'[file]'	=> str_replace(ABSPATH,'',$ref->getFileName()).'('.$ref->getStartLine().')'
							];
							break;
						default:
							$filters[] = [
								'[unknown] ' => gettype($fn['function'])
							];
					}
				}
			}
			$this->plugin->logWrite(E_USER_NOTICE,
				[
				//	'Hooks' =>	max(0, (count($wp_filter['heartbeat_received']->callbacks)-1) ),
					'Callbacks'		=>	$filters,
					'Input Data'	=>	$data,
					'Response Keys' =>	array_keys($response),
					'Screen Id'		=>	$screen_id,
				],'WP_Heartbeat API');
			return $response;
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
				 *	filter {pluginname}_debugging get debugging information
				 *	param	array	current array
				 *	return	array	extended array with [ extension_name => [key=>value array] ]
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
			 *	filter {pluginname}_debugging get debugging information
			 *	param	array	current array
			 *	return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			$response = $this->apply_filters( 'debugging', [] );
			foreach ($response as $name => $value)
			{
				echo "<details><summary>+{$name}</summary>".var_export($value,true)."</details>\n";
			}
			echo "</pre></details>\n";
		}


		/**
		 * purge old log files - (never runs for network_admin)
		 *
		 * @return	void
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
		 * @param	array	current debugging array
		 * @return	array	extended array with [ extension_name => [key=>value array] ]
		 */
		public function debug_debugging($debugging_array)
		{
			$debugging_array['Plugin Detail'] = array_merge(
				[ 	"Plugin Header"		=> $this->plugin->pluginHeaders() ],
				[
					"Plugin Updated"	=> wp_date($this->plugin->date_time_format,filemtime($this->plugin->pluginHeader('PluginDir'))),
					"Plugin Environment"=> $this->get_option('siteEnvironment'),
					"WP Admin"			=> (is_admin()) ? 'True' : 'false',
					"WP Multi Site"		=> (is_multisite()) ? 'True' : 'false',
					"WP Network Admin"	=> (is_network_admin()) ? 'True' : 'false',
				//	"WP Current User"	=> ($this->current_user) ? $this->current_user->data : 'none',
					"WP Page Parent"	=> (function_exists('get_admin_page_parent')) ? get_admin_page_parent() : 'undefined',
				]
			);

			/*
			$debugging_array['Plugin Options']	= (is_network_admin()) ? $this->plugin->networkOptions : $this->plugin->options;

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
				$debugging_array['Log Data'][] = 'Peak Memory Used: '.round((memory_get_peak_usage(true) / 1024) / 1024).'M of '.ini_get('memory_limit');
			}

			if ( class_exists( '\WP_CONSENT_API' ) ) {
				try {
					$debugging_array['WP Consent'] = [
						'Cookie Prefix' => \WP_CONSENT_API::$config->consent_cookie_prefix(),
						'Consent Type'	=> wp_get_consent_type(),
						'Categories'	=> [],
					];
					foreach(\WP_CONSENT_API::$config->consent_categories() as $category) {
						$debugging_array['WP Consent']['Categories'][$category] = wp_has_consent($category);
					}
				//	if ($cookies = wp_get_cookie_info()) {
				//		$debugging_array['WP Consent']['Cookie Info'] = $cookies;
				//	}
				} catch (\Throwable $e) {}
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
		 * show error reporting strings
		 *
		 * @param int	$errReporting or error_reporting()
		 * @param bool	$asArray return array (else string)
		 *
		 * @return array|string
		 */
		public function showErrorReporting(int $errReporting=0, bool $asArray = false)
		{
			$errReporting = $errReporting ?: error_reporting();
			$errLevels = array_filter(LogLevel::PHP_TO_STRING, function($k) use($errReporting)
				{
					return (bool)( ($k & $errReporting) && $k != E_ALL);
				}, ARRAY_FILTER_USE_KEY
			);
			if (!$asArray) {
				$errLevels = implode(', ',$errLevels);
			}
			//$this->plugin->logDebug($errLevels,'error reporting');
			return $errLevels;
		}


		/**
		 * set PHP error handler
		 *
		 * @return void
		 */
		private function setErrorhandler()
		{
			ini_set('zend.exception_ignore_args', 0);
			if ($this->apply_filters('set_error_handler', true)) {
				$this->previous_error_handler =
					set_error_handler( [$this, 'phpErrorHandler'], ($this->is_option('debug_php_errors','all')) ? E_ALL : error_reporting() );
			}
			if ($this->apply_filters('set_exception_handler', true)) {
				$this->previous_exception_handler =
					set_exception_handler( [$this, 'phpErrorHandler'] );	// Uncaught exceptions
			}
		}


		/**
		 * Handle PHP errors
		 *
		 * @param int|object $exception - exception or error type
		 * @param string				- error message
		 * @param string				- error file
		 * @param int					- error line
		 * @return void
		 */
		public function phpErrorHandler($exception)
		{
			if (error_reporting() == 0) return true;	// ignore '@' functions
			$errReporting 				= error_reporting(0);
			$context					= ['@source' => __FUNCTION__];
			$error 						= array();

			if ( $exception instanceof \Throwable )		// exception object
			{
				$level 					= E_ERROR;
				$error['type'] 			= 'Uncaught '.get_class($exception);
				$error['message']		= $exception->getMessage();
				$error['file']			= $this->clean_filepath($exception->getFile());
				$error['line']			= $exception->getLine();
				if ($code = $exception->getCode()) {
					$error['code']		= $code;
				}
				if (($this->logLevel & LogLevel::LOG_DEBUG)) {
					$error['trace'] 	= $this->print_backtrace($exception->getTrace());
				}
				$context['exception'] 	= $exception; 	// as per PSR-3
			}
			else										// PHP error
			{
				$level 					= $exception;
				list(
					$error['type'],
					$error['message'],
					$error['file'],
					$error['line']
				) = func_get_args();
				$error['type'] 			= ucwords(str_replace('_',' ', strtolower(substr(LogLevel::PHP_TO_STRING[$level],2))));
				$error['file'] 			= $this->clean_filepath($error['file']);
				if (($this->logLevel & LogLevel::LOG_DEBUG)) {
					$error['trace']	 	= $this->print_backtrace(debug_backtrace(false),$error);
				}
				$context['php_error'] 	= $error;
			}

			$this->plugin->logWrite($level,$error,$error['message'],$context);

			if (isset($context['exception']))
			{
				$this->logClose();
				if ( $this->previous_exception_handler ) {
					call_user_func( $this->previous_exception_handler, $context['exception'] );
				} else {
					throw $context['exception'];
				}
				exit(1);
			}

			$this->phpData[] = "PHP {$error['type']}: {$error['message']} in {$error['file']} on line {$error['line']}";
			error_reporting($errReporting);
			return false;								// allow PHP to handle the error
		}


		/**
		 * strip leading directory from file
		 *
		 * @return void
		 */
		public function clean_filepath($file)
		{
			static $truncate_paths;

			if ( ! isset( $truncate_paths ) ) {
				$truncate_paths = array(
					wp_normalize_path( WP_CONTENT_DIR ),
					wp_normalize_path( ABSPATH ),
				);
			}

			return str_replace( $truncate_paths, '', wp_normalize_path( $file ) );
		}


		/**
		 * print_backtrace
		 * like wp_debug_backtrace_summary
		 *
		 * @return void
		 */
		public function print_backtrace($trace,$error='')
		{
			$caller      	= array();

			foreach ( $trace as $call )
			{
				$file = $this->clean_filepath( $call['file'] ?? __FILE__ );
				$line = $call['line'] ?? 0;

				if ($error && $error['file'] == $file && $error['line'] == $line) {
					continue; 							// already reported
				}
				$path = $file."({$line})";

				$current = $call['args'] ?? [];
				array_walk($current, function(&$value, $key) {
					if (is_bool($value)) {
						$value = ($value) ? 'true' : 'false';
					} else if (is_null($value)) {
						$value = 'null';
					} else if (is_string($value)) {
						$value = "'".$this->clean_filepath($value)."'";
					} else if (!is_scalar($value)) {
						$value = gettype($value);
					}
				});
				$call['args'] = $current;

				if ( isset( $call['class'] ) ) {
					if ( in_array($call['class'],[__CLASS__,'Debug_Bar_PHP','WP_Hook']) ) {
						continue; 						// Filter out calls.
					}
					$current = $path.": {$call['class']}{$call['type']}{$call['function']}" .
						"(".implode(',',$call['args']).")";
				} else {
					$current = $path.": {$call['function']}" .
						"(".implode(',',$call['args']).")";
				}
				$caller[] = $current;
			}

			if ($backtraceTo = $this->get_option('debug_backtrace')) {
				$caller = array_slice($caller, 0, $backtraceTo);
			}

			return $caller;
		}

		/**
		 * save data to output on page footer
		 *
		 * @param array [level, message, context, code]
		 * @return bool
		 */
		public function page_log_data(array $data)
		{
			$this->logToPage(
				$data['level'],
				$data['context']['@variable'] 	?? '',
				$data['message'],
				$data['context']['@time'] 		?? microtime(true),
				$data['context']['@source'] 	?? '',
				$data['print_code'],
			);
		}


		/**
		 * Save to the page log (called from 'page_log_data' action)
		 * we don't actually "write" anything but prep the string and add it to the text string.
		 *
		 * @param int		 $level - log level (LogLevel::LOG_*)
		 * @param mixed		 $var - variable being logged
		 * @param string	 $message - message/context string
		 * @param float		 $mtime - mico-time (optional)
		 * @param string	 $source - source of log call or  internally set string
		 * @param int|string $code - print_code (text)
		 * @return bool
		 */
		private function logToPage($level, $var, $message='', $mtime=null, $source='', $code = '')
		{
			if ( !($this->logLevel & $level) || $source == 'phpErrorHandler')
			{
				return;	// we already logged this in $phpData
			}
			$this->logData[] =	trim( $message.": ".$this->logVariable($var,false), ' :' );
		}


		/**
		 * Write to the log file.
		 * we don't actually "write" anything but prep the string and add it to the text string.
		 *
		 * @param array [level, message, context, code]
		 * @return bool
		 */
		public function file_log_data(array $data)
		{
			$this->logToFile(
				$data['level'],
				$data['context']['@variable'] 	?? '',
				$data['message'],
				$data['context']['@time'] 		?? microtime(true),
				$data['context']['@source'] 	?? '',
				$data['print_code'],
			);
		}


		/**
		 * Save to the file log (called from 'file_log_data' action)
		 * we don't actually "write" anything but prep the string and add it to the text string.
		 *
		 * @param int		 $level - log level (LogLevel::LOG_*)
		 * @param mixed		 $var - variable being logged
		 * @param string	 $message - message/context string
		 * @param float		 $mtime - mico-time (optional)
		 * @param string	 $source - source of log call or  internally set string
		 * @param int|string $code - print_code (text)
		 * @return bool
		 */
		private function logToFile($level, $var, $message='', $mtime=null, $source='', $code = '')
		{
			if ( !($this->logLevel & $level) )
			{
				return;
			}

			// write to system log file
			if ($this->is_option('debug_to_file','server') && $source != 'phpErrorHandler')
			{
				$errmsg = trim( $message.": ".$this->logVariable($var,false), ' :' );
				error_log( substr($errmsg, 0, $this->logLength) );
			}

			// write to plugin log file
			if ($this->is_option('debug_to_file','plugin') && $this->logPath)
			{
				if (!$this->logFile && !$this->logOpen())
				{
					return ($this->logPath = false);
				}

				if (!$mtime) $mtime = microtime(true);
				$time = new \DateTime("@{$mtime}");
				$time->setTimezone(wp_timezone());
				$time = substr($time->format("H:i:s.u"),0,13);

				$logLine 		= 	sprintf('%-20.20s %-10.10s [%s] - ', $source, $code, $time) .
									trim( $message.": ".$this->logVariable($var,true), ' :' );

				$this->logText .= (empty($this->logText))
					? "+++ " 	. $logLine . "\n"
					: "--- " 	. $logLine . "\n";
			}
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
					if ($var instanceof \DateTimeInterface) {
						return $var->format(\DateTime::RFC3339);
					}
					if (method_exists($var, '__toString')) {
						return (string)$var;
					}
					if (!$detailed) {
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
					return str_replace("\n\n","\n",trim(print_r($var,true)));
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
				$this->plugin->add_admin_notice($this->pluginName.' Debugging: Unable to access log file','error');
				trigger_error('unable to access log file '.$file,E_USER_WARNING);
				return false;
			}

			$this->logFile = $file;

			if ($this->plugin->doing_ajax()) {
				$this->reqType = 'ajax';
			} else if (wp_doing_cron()) {
				$this->reqType = 'cron';
			} else if (defined('REST_REQUEST')) {
				$this->reqType = 'rest';
			} else if (wp_is_json_request()) {
				$this->reqType = 'json';
			} else if (wp_is_jsonp_request()) {
				$this->reqType = 'jsonp';
			} else if (wp_is_xml_request()) {
				$this->reqType = 'xml';
			} else {
				$this->reqType = is_ssl() ? 'https' : 'http';
			}

			$startTime = $this->plugin->pluginHeader('RequestTime');

			$date = new \DateTime("@{$startTime}");
			$date->setTimezone(wp_timezone());
			$date = $date->format("D M d Y T");

			$request_data = [];
			if ($this->logLevel & LogLevel::LOG_DEBUG)
			{
				if ($headers = $this->getHeaders()) {
					$request_data['Request Headers']	= $headers;
				}
				if (!empty($_REQUEST)) {
					$request_data['Request Values']		= $_REQUEST;
				}
			}

			$this->logToFile(
				LogLevel::LOG_ALWAYS,
				$request_data ?: '',
				'IP:'.$this->plugin->getVisitorIP().' '.$this->requestURL(),
				$startTime,
				$date,
				'via '.$this->reqType,
			);

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
			if (!$this->logFile) return;

			$stopTime = microtime(true);

			if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false)) {
				$this->logToFile(
					LogLevel::LOG_DEBUG,
					\EarthAsylumConsulting\eacDoojiggerActionTimer::getArray(),
					'WordPress Timing',
					$stopTime
				);
			}

			$headers = ($this->logLevel & LogLevel::LOG_DEBUG) ? ['Response Headers' => headers_list()] : '';

			$startTime	= $this->plugin->pluginHeader('RequestTime');
			$stopTime	= microtime(true);

			$unit=array('b','K','M','G');
			$memory = memory_get_peak_usage(true);
			$memory = round($memory / pow(1024,($i=floor(log($memory,1024)))),2).$unit[$i];

			$this->logToFile(
				LogLevel::LOG_ALWAYS,
				$headers,
				sprintf("Duration: %01.4f Seconds, Peak Memory Used: %s of %s",
					($stopTime - $startTime),
					$memory,
					ini_get('memory_limit')
				),
				$stopTime,
				"exit ".$this->reqType
			);

			$this->logText .= str_repeat('-',100)."\n";

			// wp_filesystem has no way to append to a file
			file_put_contents($this->logFile, $this->logText."\n", FILE_APPEND|LOCK_EX);
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
				$_SERVER['REQUEST_METHOD']	= 'CLI';
				$_SERVER['HTTP_HOST']		= parse_url(home_url(),PHP_URL_HOST);
				$_SERVER['SERVER_NAME']		= gethostname();
				$_SERVER['REQUEST_URI']		= (!empty($argv)) ? implode(' ',$argv) : '';
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
			if (function_exists('getallheaders')) {		// apache_request_headers
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
		 * @return	bool
		 */
		private function isWriteablePath($fs,$logPath,$exists=false)
		{
			if ($exists && !is_dir($logPath)) return false;
			if	($fs)
			{
				return (($fsLogPath = $fs->find_folder($logPath)) && $fs->is_writable($fsLogPath))
					? $logPath
					: false;
			}
			else
			{
				return (is_writable($logPath))
					? $logPath
					: false;
			}
		}


		/**
		 * set (create) log file path
		 *
		 * @return	bool
		 */
		private function setLoggingPathname($create=false,$asNetAdmin=false)
		{
			if (! $this->is_option('debug_to_file_allowed')) return false;

			if	(! ($fs = $this->fs->load_wp_filesystem()) ) return false;
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
				$logPath	 = trailingslashit($logPath);
				$logPath	.= (is_network_admin() || $asNetAdmin)
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
				$this->plugin->add_admin_notice($this->pluginName.' Debugging: Unable to access log path','error',
					$logPath.'<br>File logging has been disabled');
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

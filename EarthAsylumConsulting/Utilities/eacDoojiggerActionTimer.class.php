<?php
/**
 * {eac}DoojiggerActionTimer - Track the time to load (include) each plugin; Track duration of specific WP actions; Ability to time custom functions execution
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger Utilities\{eac}DoojiggerActionTimer
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.1.9
 * @link		https://eacDoojigger.earthasylum.com/
 *
 * @wordpress-plugin
 * Plugin Name:			{eac}Doojigger ActionTimer
 * Description:			Track the time to load (include) each plugin; Track duration of specific WP actions; Ability to time custom function execution.
 * Version:				1.1.9
 * Requires at least:	5.5.0
 * Tested up to: 		6.5
 * Requires PHP:		7.2
 * Author:				EarthAsylum Consulting
 * Author URI:			http://www.earthasylum.com
 */

/*
 * Should be installed in .../wp-content/mu-plugins
 *
 * use either:
 * 		if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false)) {
 * or:
 *		if (defined('EAC_DOOJIGGER_ACTIONTIMER')) {
 *
 * Add an action to be timed...
 * 		if (class_exists('\EarthAsylumConsulting\eacDoojiggerActionTimer',false)) {
 *			\EarthAsylumConsulting\eacDoojiggerActionTimer::timeAction('my_hook_name');
 *		}
 *
 * Add a timer for specific code...
 *		if (defined('EAC_DOOJIGGER_ACTIONTIMER')) {
 *			\EarthAsylumConsulting\eacDoojiggerActionTimer::start('my_function_timer');
 *		}
 *  	... do something ...
 *		if (defined('EAC_DOOJIGGER_ACTIONTIMER')) {
 *			$result = \EarthAsylumConsulting\eacDoojiggerActionTimer::stop('my_function_timer');
 *		}
 *
 * Get all of the timing data accumulated by this plugin...
 *		if (defined('EAC_DOOJIGGER_ACTIONTIMER')) {
 *			$result = \EarthAsylumConsulting\eacDoojiggerActionTimer::getArray();
 *		}
 * Or add an action handler (fires after WP 'shutdown' action)...
 *		add_action('eacDoojiggerActionTimer_result', function(array $result)
 *			{
 *				// do something with result array
 *			}
 *		);
 */

namespace EarthAsylumConsulting
{
	/**
	 * {eac}DoojiggerActionTimer - {eac}Doojigger for WordPress,
	 * Track the time to load (include) each plugin; Track duration of specific WP actions; Ability to time custom functions execution
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger Utilities\{eac}DoojiggerActionTimer
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 */
	class eacDoojiggerActionTimer
	{
		/**
		 * @var string format parameter for date/time string
		 */
		const FORMAT_TIME = 'm-d H:i:s.u';

		/**
		 * @var array time of initial request [Time]
		 */
		private static $requestTime 	= array();

		/**
		 * @var array time of plugin loads [Time, Duration, Elapsed, Hooks, Memory] by plugin type
		 */
		private static $pluginTime 		= array(
				'mu_plugin_loaded'		=>	[],
				'network_plugin_loaded'	=>	[],
				'plugin_loaded'			=>	[],
		);

		/**
		 * @var array time of actions [Time, Duration, Elapsed, Hooks, Memory] Start and Stop
		 */
		private static $actionTime 		= array();

		/**
		 * @var int time as of each event starting with request time
		 */
		private static $startTime 		= null;

		/**
		 * @var int elapsed time accumulated
		 */
		private static $elapsedTime 	= 0;

		/**
		 * @var array time of action (start) for each action (to calculate duration)
		 */
		private static $eventTime 		= array();

		/**
		 * @var array time of user timers [Start, Stop, Duration, Memory]
		 */
		private static $userTimer 		= array();


		/**
		 * Return data array
		 *
		 * @return array data array
		 */
		public static function getArray(): array
		{
			return array_filter(array(
				'Request Made'			=>	self::$requestTime,
				'Plugins Loaded' 		=>	array_filter(self::$pluginTime),
				'Actions Triggered'		=>	self::$actionTime,
				'User Timers'			=>	self::$userTimer
			));
		}


		/**
		 * Return data array in JSON format
		 *
		 * @return string json formatted data array
		 */
		public static function getJSON(): string
		{
			return wp_json_encode( self::getArray() );
		}


		/**
		 * Add action(s) to track
		 *
		 * @param string|array $hook action hook names(s)
		 * @return void
		 */
		public static function timeAction($hook): void
		{
			if (is_array($hook)) {
				foreach ($hook as $h) self::timeAction($h);
			} else {
				// try to be the first and last hook for an action
				add_action( $hook, self::class.'::addActionTimeStart', PHP_INT_MIN );
				add_action( $hook, self::class.'::addActionTimeStop', PHP_INT_MAX );
			}
		}


		/**
		 * start a user timer
		 *
		 * @param string $id timer id
		 * @return void
		*/
		public function start(string $id): void
		{
			self::$userTimer[$id] = array();
			self::$userTimer[$id]["Start"] 		= microtime(true);
			self::$userTimer[$id]["Stop"] 		= -1;
		}


		/**
		 * end a user timer
		 *
		 * @param string $id timer id
		 * @return array $id => [Start, Stop, Duration, Memory]
		*/
		public function stop(string $id): array
		{
			self::$userTimer[$id]["Stop"]  		= microtime(true);
			self::$userTimer[$id]["Duration"]	= sprintf("%01.6f",(self::$userTimer[$id]["Stop"] - self::$userTimer[$id]["Start"]));
			self::$userTimer[$id]["Memory"] 	= number_format(memory_get_peak_usage(true)/1024).'kb';

			self::$userTimer[$id]["Start"] 		= self::datetime(self::$userTimer[$id]["Start"]);
			self::$userTimer[$id]["Stop"]  		= self::datetime(self::$userTimer[$id]["Stop"]);

			return [ $id => self::$userTimer[$id] ];
		}


		/**
		 * Track time of loading plugins and specific actions to identify potential holdups
		 *
		 * @return void
		 */
		public static function loadPlugin(): void
		{
			// get the initial request time
			self::$startTime = $_SERVER["REQUEST_TIME_FLOAT"] ?: microtime(true);
			self::$requestTime = ['Time'=>self::datetime(self::$startTime)];

			register_shutdown_function( self::class.'::shutdown' );

			// get each plugin loaded (included)
			add_action( 'mu_plugin_loaded', 				self::class.'::addPluginTime' );
			add_action( 'network_plugin_loaded', 			self::class.'::addPluginTime' );
			add_action( 'plugin_loaded', 					self::class.'::addPluginTime' );

			// track start/stop/duration time for these actions
			self::timeAction(
				[
					'plugins_loaded',
					'setup_theme',
					'init',
					'widgets_init',
					'wp_loaded',
					'admin_menu',
					'admin_init',
					'admin_head',
					'admin_bar_menu',
					'current_screen',
					'wp',
					'wp_head',
					'the_post',
					'loop_start',
					'loop_end',
					'wp_meta',
					'wp_footer',
					'admin_footer',
					'shutdown',
				]
			);
		}


		/**
		 * Plugins Loaded
		 *
		 * @param string $plugin plugin file name
		 * @return void
		 */
		public static function addPluginTime(string $plugin): void
		{
			$action = current_action();
			self::$pluginTime[$action][plugin_basename($plugin)] = self::eventTime($action,1);
		}


		/**
		 * Actions Triggered - Start
		 *
		 * @return void
		 */
		public static function addActionTimeStart(): void
		{
			$action = current_action();
			self::$actionTime[$action] = array();
			self::$actionTime[$action]['Start'] = self::eventTime($action,2);
		}


		/**
		 * Actions Triggered - Stop
		 *
		 * @return void
		 */
		public static function addActionTimeStop(): void
		{
			$action = current_action();
			self::$actionTime[$action]['Stop'] = self::eventTime($action,2);
		}


		/**
		 * Get the time, duration, elapsed, hooks, and peak memory at the point of the event (plugin load or action)
		 *
		 * @param string $action action hook or event name
		 * @param int $sub subtract this plugin's callbacks
		 * @return void
		 */
		private static function eventTime(string $action, int $sub): array
		{
			global $wp_filter;

			$time 				= microtime(true);
			$duration 			= $time - ( isset(self::$eventTime[$action]) ? self::$eventTime[$action] : self::$startTime );
			self::$elapsedTime 	+= ($time - self::$startTime);
			self::$startTime 	= $time;
			self::$eventTime[$action] = $time;
			return [
				'Time'			=>	self::datetime($time),
				'Duration'		=>	sprintf("%01.6f",$duration),
				'Elapsed'		=>	sprintf("%01.6f",self::$elapsedTime),
				'Hooks'			=>	max(0, (count($wp_filter[$action]->callbacks)-$sub) ),
				'Memory'		=>	number_format(memory_get_peak_usage(true)/1024).'kb',
			];
		}


		/**
		 * Get time as string in wp timezone
		 *
		 * @param float $time microtime
		 * @return string formatted time
		 */
		private static function datetime(float $time): string
		{
			$time = \DateTime::createFromFormat('U.u', sprintf('%01.6f', $time));
			$time->setTimezone(wp_timezone());
			return $time->format(self::FORMAT_TIME);
		}


		/**
		 * Fire an action on shutdown
		 *
		 * @return void
		 */
		public static function shutdown(): void
		{
			$className = basename(str_replace('\\', '/', self::class));
			\do_action( $className.'_result', self::getArray() );
		}
	}
} // namespace


namespace  // global scope
{
	defined( 'ABSPATH' ) or exit;

	/**
	 * we only want to load the plugin for php files
	 * script_name may always be index.php, check the request uri
	 */
	if (array_key_exists('REQUEST_URI', $_SERVER))
	{
		$ext = explode('?',$_SERVER['REQUEST_URI']);
		$ext = pathinfo(trim($ext[0],'/'),PATHINFO_EXTENSION);
		if (!empty($ext) && $ext != 'php') exit;
	}

	// set option 'eacDoojiggerActionTimer_enabled' to '' to disable
	if ( !get_option('eacDoojiggerActionTimer_enabled', 'Enabled') ) exit;

	/**
	 * Run the plugin loader
	 */
	define('EAC_DOOJIGGER_ACTIONTIMER',true);
	\EarthAsylumConsulting\eacDoojiggerActionTimer::loadPlugin();
}

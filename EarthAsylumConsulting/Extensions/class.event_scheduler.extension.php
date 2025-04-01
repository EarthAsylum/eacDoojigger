<?php
namespace EarthAsylumConsulting\Extensions;

/*
 * By defining EAC_ALLOWED_WP_SCHEDULES as an array of allowed core schedules (intervals)
 * the intervals can be limited in the admin 'Scheduled Events' settings page so that
 * excluded intervals can not be used to set an event time.
 * Schedules are still available/usable elsewhere.
 *
 * Core Schedules allowed: [ 'hourly', 'twicedaily', 'daily', 'weekly', 'monthly' ]
 * * although 'monthly' is not core, it is added and treated as such.
 *
 *	if (!defined('EAC_ALLOWED_WP_SCHEDULES')) {
 *		define ('EAC_ALLOWED_WP_SCHEDULES',['hourly', 'daily']);
 *	}
 *
 * See also `{pluginname}_allowed_schedules` filter to filter out any schedule/interval.
 * 	$this->add_filter('allowed_schedules', function($schedules)
 * 		{
 * 			unset(
 * 				$schedules['twicedaily'],
 * 				$schedules['weekly'],
 * 				$schedules['monthly']
 * 			);
 * 			return $schedules;
 * 		}
 * 	);
 */


if (! class_exists(__NAMESPACE__.'\event_scheduler_extension', false) )
{
	/**
	 * Extension: event_schedule - Schedule timed, repeating event(s).
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger Utilities\{eac}Doojigger Object Cache
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 */

	class event_scheduler_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION		= '25.0401.1';

		/**
		 * @var string alias class name
		 */
		const ALIAS			= 'cron';

		/**
		 * @var string to set default tab name
		 */
		const TAB_NAME		= 'General';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 * 		false 		disable the 'Enabled'' option for this group
		 * 		string 		the label for the 'Enabled' option
		 * 		array 		override options for the 'Enabled' option (label,help,title,info, etc.)
		 */
		const ENABLE_OPTION	= [
				'type'			=> 	'hidden',
				'label'			=> 	"<abbr title='Set regularly scheduled, recurring events. These events do nothing by ".
									"themselves but can be used to trigger other actions by hooking into the event action name.'>".
									"Scheduled Events</abbr>"
		];

		/*
		 * @var array WordPress schedule/interval keys
		 * 		because plugins may redefine, making it look not-core
		 */
		const WP_CORE_SCHEDULES = [
				'hourly'		=> 'Hourly',
				'twicedaily'	=> 'Twice Daily',
				'daily'			=> 'Daily',
				'weekly'		=> 'Weekly',
				'monthly'		=> 'Monthly'	// added by this extension
		];


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL );

			if ($this->is_admin())
			{
				$this->registerExtension( $this->className );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
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
			if (!$this->plugin->isSettingsPage(self::TAB_NAME)) return;

			$setting = [];
			$this->verify_active_events();
			// get core intervals (schedules) and our custom intervals
			foreach ($this->getIntervals('core+self') as $eventName => $interval) {
				if (defined('EAC_ALLOWED_WP_SCHEDULES')
				&& is_array(EAC_ALLOWED_WP_SCHEDULES)
				&& !in_array($eventName,EAC_ALLOWED_WP_SCHEDULES)) {
					continue;
				}
				// keep dates current - update to next schedule time
				$event = $this->plugin->removeClassNamePrefix($eventName);
				$scheduledTime = $this->get_option("event_start_{$event}");
				if (!empty($scheduledTime)) {
					try {
						$scheduledTime = $this->defaultTime($eventName,$scheduledTime,true);
					} catch (\Throwable $e) {continue;}
					$this->update_option("event_start_{$event}",$scheduledTime);
				}

				$action = $this->eventName($event);
				$count = $this->has_action_count($action);
				$name = self::WP_CORE_SCHEDULES[$event] ?? $event;
				$settings["event_start_{$event}"] = array(
							'type'		=>	'datetime-local',
							'label'		=>	"<abbr title='Event action name: {$action}'>{$name}</abbr>",
							'after'		=> 	($count) ? "<small>({$count} actions currently registered)</small>" : "",
							'info'		=> 	"Trigger a scheduled event to run ".strtolower($interval['display'])." starting at...",
							'validate'	=>	[$this,'validate_schedules'],
							'advanced'	=> true,
				);
			}

			$this->registerExtensionOptions( $this->className,$settings );

			/*
			 * action 'verify_active_events' - verify intervals and events after submitting the page.
			 */
			$this->add_action( "options_form_post", [$this,'verify_active_events'] );
		}


		/**
		 * validate/set validate_schedules when form submitted
		 *
		 * @param mixed	$scheduledTime the value POSTed
		 * @param string $fieldName the name of the field/option
		 * @param array	 $metaData the option metadata
		 * @param mixed	$priorValue the previous value
		 * @return	void
		 */
 		public function validate_schedules($scheduledTime, $fieldName, $metaData, $priorValue)
		{
			if ($event = substr($fieldName, 12)) {
				$this->unsetEvent($event);
				if (!empty($scheduledTime)) {
					$interval = $this->intervalName($event);
					try {
						$scheduledTime = $this->defaultTime($interval,$scheduledTime,true);
					} catch (\Throwable $e) {
						$this->add_admin_notice("Invalid schedule time for '{$interval}' event",'error');
						return $priorValue;
					}
				//	$this->setEvent($event,$scheduledTime);
				//	$scheduledTime = substr($scheduledTime->format('c'),0,-6);
				}
			}
			return $scheduledTime;
		}


		/**
		 * Add help tab on admin page
		 *
		 * @todo - add contextual help
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
		//	if (!$this->plugin->isSettingsPage(self::TAB_NAME)) return;
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled

			$this->addActionsAndFilters_early();

			//if ( wp_doing_cron() )
			//{
			//	$this->verify_active_events();
			//}
		}


		/**
		 * verify active events
		 *
		 * @return 	void
		 */
		public function verify_active_events()
		{
			// Using core intervals (schedules) and our custom intervals...

			foreach ($this->getIntervals('core+self') as $eventName => $interval)
			{
				try {
					$eventName = $this->plugin->removeClassNamePrefix($eventName);
					if ($event = $this->getEvent($eventName)) {
					// 1. Check currently scheduled (default) events and update our option start time.
						$scheduledTime = $this->defaultTime($eventName,$event->timestamp,true);
						$this->update_option("event_start_{$eventName}",$scheduledTime);
					} else {
					// 2. Check our option start time and make sure we have a scheduled event.
						if ($scheduledTime = $this->get_option("event_start_{$eventName}")) {
							$this->setEvent($eventName,$scheduledTime);
						}
					}
				} catch (\Throwable $e) {
					$this->add_admin_notice($e->getMessage(),'error');
				}
			}
		}


		/**
		 * deactivated active events
		 *
		 * @return 	void
		 */
		public function remove_active_events()
		{
			// Using core intervals (schedules) and our custom intervals...

			foreach ($this->getIntervals('core+self') as $eventName => $interval)
			{
				$eventName = $this->plugin->removeClassNamePrefix($eventName);
				if ($event = $this->getEvent($eventName)) {
					$this->unsetEvent($eventName);
				}
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters_early()
		{
			//add our custom intervals to wp
			\add_filter('cron_schedules',				function($cron_intervals) {
				$days_this_month = (int)wp_date('t');
				$month_in_seconds = $days_this_month * DAY_IN_SECONDS;
				return array_merge(
					$cron_intervals,
					array('monthly' => [
						'interval' 	=> $month_in_seconds,
						'display'  	=> "Monthly ({$days_this_month} days)",
					]),
					$this->getIntervals()
				);
			},100);

			/**
			 * action '{pluginName}_add_cron_interval' - allow actors to add a custom interval
			 */
			$this->add_action('add_cron_interval',		[$this,'setInterval'],10,3);
			/**
			 * action '{pluginName}_delete_cron_interval' - allow actors to delete a custom interval
			 */
			$this->add_action('delete_cron_interval',	[$this,'unsetInterval'],10,1);

			/**
			 * action '{pluginName}_add_cron_event' - allow actors to add a custom event
			 */
			$this->add_action('add_cron_event',			[$this,'setEvent'],10,4);
			/**
			 * action '{pluginName}_delete_cron_event' - allow actors to delete a custom event
			 */
			$this->add_action('delete_cron_event',		[$this,'unsetEvent'],10,1);

			/**
			 * action '{pluginName}_add_event_task' - allow actors to add a custom event and task
			 */
			$this->add_action('add_event_task',			[$this,'addEventTask'],10,4);

			/**
			 * action '{pluginName}_add_cron_task' - allow actors to add a custom task
			 */
			$this->add_action('add_cron_task',			[$this,'setTask'],10,4);
			/**
			 * action '{pluginName}_delete_cron_task' - allow actors to delete a custom task
			 */
			$this->add_action('delete_cron_task',		[$this,'unsetTask'],10,4);

			if ($this->is_admin() && !$this->is_network_admin())
			{
				/**
				 * action {pluginName}_version_installed when plugin version installed
				 */
				$this->add_action('plugin_activated', 	[$this,'verify_active_events']);

				/**
				 * action {pluginName}_version_updated when plugin version changes
				 */
				$this->add_action('version_updated', 	[$this,'verify_active_events']);

				/**
				 * action {pluginName}_version_deactivated when plugin version is deactivated
				 */
				$this->add_action('plugin_deactivated',	[$this,'remove_active_events']);
			}
		}


		/*
		 * Custom event intervals - named recurrance intervals (aka schedules)
		 */


		/**
		 * Does a custom interval exist
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param int $interval time interval in seconds
		 * @param string $display description for this interval
		 * @return	bool
		 */
		public function isInterval(string $name, int $interval = 0, string $display = ''): bool
		{
			return ($this->getInterval($name, $interval, $display)) ? true : false;
		}


		/**
		 * Get a custom interval
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param int $interval time interval in seconds
		 * @param string $display description for this interval
		 * @return	array|bool interval
		 */
		public function getInterval(string $name, int $interval = 0, string $display = ''): array|bool
		{
			$intervals = $this->getIntervals();
			if (($name = $this->intervalName($name)) && isset($intervals[$name]))
			{
				if ($interval && $interval != $intervals[$name]['interval']) return false;
				if ($display && $display != $intervals[$name]['display']) return false;
				return $intervals[$name];
			}
			return false;
		}


		/**
		 * Create a custom interval
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param int $interval time interval in seconds
		 * @param string $display description for this interval
		 * @return	array custom intervals
		 */
		public function setInterval(string $name, int $interval, string $display): array
		{
			$intervals = $this->getIntervals();
			if ($name = esc_attr( trim($name) ) )
			{
				$name = $this->plugin->addClassNamePrefix($name);
				$intervals[$name] = [
					'interval' => $interval,
					'display'  => sanitize_text_field($display),
				];
				$this->update_option('event_intervals',$intervals);
			} else {
				$this->add_admin_notice(__FUNCTION__.": Interval name '{$name}' is invalid",'error');
			}
			return $intervals;
		}


		/**
		 * Delete a custom interval
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @return	array custom intervals
		 */
		public function unsetInterval(string $name): array
		{
			$intervals = $this->getIntervals();
			if (($interval = $this->intervalName($name)) && isset($intervals[$interval]))
			{
				$this->_unschedule_interval($interval);
				unset($intervals[$interval]);
				if (empty($intervals)) {
					$this->delete_option('event_intervals');
				} else {
					$this->update_option('event_intervals',$intervals);
				}
			} else {
				$this->add_admin_notice(__FUNCTION__.": Interval name '{$name}' not found",'error');
			}
			return $intervals;
		}


		/**
		 * Remove events using an interval (like wp_unschedule_hook)
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @return	int count of removed events
		 */
		private function _unschedule_interval( $interval ): int
		{
			$crons = _get_cron_array();

			$results = 0;

			foreach ( $crons as $timestamp => $args ) {
				foreach ($args as $hook => $schedule) {
					foreach ($schedule as $hash => $event) {
						if ($event['schedule'] == $interval) {
							unset( $crons[ $timestamp ][ $hook ][ $hash ] );
							$results++;
						}
					}
					if ( empty( $crons[ $timestamp ][ $hook ] ) ) {
						unset( $crons[ $timestamp ][ $hook ] );
					}
				}
				if ( empty( $crons[ $timestamp ] ) ) {
					unset( $crons[ $timestamp ] );
				}
			}

			/*
			 * If the results are empty (zero events to unschedule), no attempt
			 * to update the cron array is required.
			 */
			if ( $results ) {
				$set = _set_cron_array( $crons, true );
				if (is_wp_error($set)) {
					$this->add_admin_notice($set->get_error_message(),'error');
					return 0;
				}
			}

			return $results;
		}


		/**
		 * Get a set of defined interval schedules
		 *
		 * @param string $include core = wp core, cron = added by plugins, self = ours
		 * @return	array
		 */
		public function getIntervals(string $include = null, $default = []): array
		{
			switch ($include)
			{
				case 'core+self':
					$cron_schedules = array_merge( $this->getIntervals('core'), $this->getIntervals('self') );
					break;

				case 'all':
					$cron_schedules = wp_get_schedules();
					break;

				case 'cron':
					// get external plugin defined intervals
					$exclude = array_merge(
						array_keys(self::WP_CORE_SCHEDULES),
						array_keys($this->get_option('event_intervals',[]))
					);
					$cron_schedules = apply_filters( 'cron_schedules', array() );
					$cron_schedules = array_filter($cron_schedules, function($key) use ($exclude) {
								return !in_array($key,$exclude);
							},ARRAY_FILTER_USE_KEY);
					break;

				case 'core':
					// get wp core defined intervals
					$include = array_keys(self::WP_CORE_SCHEDULES);
					$cron_schedules = wp_get_schedules();
					$cron_schedules = array_filter($cron_schedules, function($key) use ($include) {
								return in_array($key,$include);
							},ARRAY_FILTER_USE_KEY);
					break;

				case 'self':
				default:
					// get our defined intervals
					$cron_schedules = $this->get_option('event_intervals',$default);
					break;
			}

			/**
			 * filter {pluginname}_apply_filters - filter allowed intervals/schedules
			 *
			 * @param array $intervals [name=>interval,...]
			 * @return array
			 */
			return $this->apply_filters( 'allowed_schedules', $this->sortIntervals( $cron_schedules ) );
		}


		/**
		 * Sort array of defined interval schedules
		 *
		 * @param array intervals
		 * @return	array
		 */
		public function sortIntervals(array $intervals):array
		{
			uasort($intervals, function($a,$b) {
				return ($a['interval'] == $b['interval']) ? 0 : ( ($a['interval'] < $b['interval']) ? -1 : 1);
			});
			return $intervals;
		}


		/*
		 * Custom scheduled events - named recurring events
		 */


		/**
		 * Is a scheduled event already set
		 *
		 * @param string $name event name (e.g.'every_x_hours')
		 * @return	bool
		 */
		public function isEvent(string $name): bool
		{
			return ($this->getEvent( $name )) ? true : false;
		}


		/**
		 * Get a scheduled event
		 *
		 * @param string $name event name (e.g.'every_x_hours')
		 * @return	object|bool event
		 */
		public function getEvent(string $name): object|bool
		{
			$eventName = $this->eventName($name);
			return wp_get_scheduled_event( $eventName );
		}


		/**
		 * Set a scheduled event
		 *
		 * @param string $name event name (e.g.'every_x_hours')
		 * @param DateTime|int|string $scheduledTime custom start time
		 * @param string $interval interval name (e.g.'every_x_hours')
		 * @param array $args arguments to pass to the hook’s callback function
		 * @return	int|bool timestamp
		 */
		public function setEvent(string $name, \DateTime|int|string $scheduledTime = null, string $interval = null, array $args = []): int|bool
		{
			if (empty($interval)) {
				$interval = $name;
			}

			if (! $interval = $this->intervalName($interval)) {
				$this->add_admin_notice("Invalid interval name for event '{$name}'",'error');
				return false;
			}

			try {
				$scheduledTime = $this->defaultTime($interval,$scheduledTime);
			} catch (\Throwable $e) {
				$this->add_admin_notice("Invalid schedule time for event '{$name}'",'error');
				return false;
			}

			$this->unsetEvent($name);
			$eventName = $this->eventName($name);
			wp_schedule_event($scheduledTime->getTimestamp(), $interval, $eventName, $args);
			return $scheduledTime->getTimestamp();
		}


		/**
		 * Remove a scheduled event
		 *
		 * @param string $name event name (e.g.'every_x_hours')
		 * @return	int|bool timestamp
		 */
		public function unsetEvent(string $name): int|bool
		{
			$eventName = $this->eventName($name);
			return wp_unschedule_hook($eventName);
		}


		/*
		 * Custom event tasks (actions)
		 */


		/**
		 * Is there a task hook for a scheduled event (has_action() shortcut)
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param callable $callback callable function/method
		 * @return	bool
		 */
		public function isTask(string $name, ...$args): bool
		{
			if ($eventName = $this->eventName($name))
			{
				return \has_action($eventName, ...$args);
			}
			return false;
		}


		/**
		 * Add a task hook for a scheduled event (add_action() shortcut)
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param callable $callback callable function/method
		 * @param int $priority add_action() priority (10)
		 * @param int $accepted_args add_action() args (1)
		 * @return	bool
		 */
		public function setTask(string $name, ...$args): bool
		{
			if ($eventName = $this->eventName($name))
			{
				return (\has_action($eventName, ...$args))
					? false
					: \add_action($eventName, ...$args);
			}
			return false;
		}


		/**
		 * Remove a task hook for a scheduled event (remove_action() shortcut)
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param callable $callback callable function/method
		 * @param int $priority add_action() priority (10)
		 * @param int $accepted_args add_action() args (1)
		 * @return	bool
		 */
		public function unsetTask(string $name, ...$args): bool
		{
			if ($eventName = $this->eventName($name))
			{
				return \remove_action($eventName, ...$args);
			}
			return false;
		}


		/**
		 * Add an event and task hook for the event
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @param callable $callback callable function/method
		 * @param int $priority add_action() priority (10)
		 * @param int $accepted_args add_action() args (1)
		 * @return	bool
		 */
		public function addEventTask(string $name, ...$args): bool
		{
			if ($eventName = $this->eventName($name))
			{
				if (! $this->isEvent($name)) {
					$this->setEvent($name);
				}

				if (\has_action($eventName, ...$args)) return false;

				return \add_action($eventName, ...$args);
			}
			return false;
		}


		/*
		 * private methods
		 */


		/**
		 * Set the default schedule time
		 *
		 * @param string $interval interval name (hourly, daily, twicedaily, weekly)
		 * @param datetime interval/event time.
		 * @param bool $formatted return formatted ('c') date string
		 * @return	datetime|string (if formatted) false
		 */
		private function defaultTime(string $interval, $scheduledTime = null, $formatted = false)
		{
 			if (!empty($scheduledTime))
 			{
				if (is_string($scheduledTime)) {
					$scheduledTime = new \DateTime( $scheduledTime, wp_timezone() );
				} else if (is_int($scheduledTime)) {
					if ($scheduledTime <= YEAR_IN_SECONDS) {
						$scheduledTime += time();
					}
					$scheduledTime = new \DateTime( "@{$scheduledTime}" );
					$scheduledTime->setTimezone( wp_timezone() );
				}
				if (is_a($scheduledTime,'DateTime')) {
					return $this->futureTime($interval,$scheduledTime,$formatted);
				}
			}

			if ($current = $this->getEvent($interval))
			{
				$scheduledTime = new \DateTime( "@{$current['timestamp']}" );
				$scheduledTime->setTimezone( wp_timezone() );
				if (is_a($scheduledTime,'DateTime')) {
					return $this->futureTime($interval,$scheduledTime,$formatted);
				}
			}

			if ($current = $this->get_option("event_start_{$interval}"))
			{
				$scheduledTime = new \DateTime( $current, wp_timezone() );
				if (is_a($scheduledTime,'DateTime')) {
					return $this->futureTime($interval,$scheduledTime,$formatted);
				}
			}

			switch ($interval)
			{
				case 'hourly':
					$scheduledTime = new \DateTime( wp_date('Y-m-d H:00:00'), wp_timezone() );
					break;

				case 'daily':
					$scheduledTime = new \DateTime( '12:15 am', wp_timezone() );
					break;

				case 'twicedaily':
					$scheduledTime = new \DateTime( '5am', wp_timezone() );
					break;

				case 'weekly':
					$startOfWeekDay = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
					$startOfWeekDay = $startOfWeekDay[ get_option( 'start_of_week' ) ];
					$scheduledTime = new \DateTime( $startOfWeekDay.' midnight', wp_timezone() );
					break;

				case 'monthly':
					$scheduledTime = new \DateTime( wp_date('Y-m-01 00:00:00'), wp_timezone() );
					break;

				default: // custom
					$scheduledTime = new \DateTime( 'now', wp_timezone() );
			}

			return $this->futureTime($interval,$scheduledTime,$formatted);
		}


		/**
		 * validate/set date/time is future
		 *
		 * @param string $interval full interval name (e.g.'hourly')
		 * @param DateTime $scheduledTime scheduled event time
		 * @param bool $formatted return formatted ('c') date string
		 * @return	datetime|string (if formatted)
		 */
 		private function futureTime(string $interval, \DateTime $scheduledTime, $formatted = false)
		{
			$schedules = wp_get_schedules();
			$modify = $schedules[$interval]['interval'];
			if ($scheduledTime->getTimestamp() <= time()) {
				$scheduledTime->modify('today '.$scheduledTime->format('H:i'));
			}
			if ($modify) {
				while ($scheduledTime->getTimestamp() <= time()) {
					$scheduledTime->modify('+'.$modify.' seconds');
				}
			}
			return ($formatted)
				? substr( $scheduledTime->format('c'), 0,-6 )
				: $scheduledTime;
		}


		/**
		 * Format & validate a custome interval name.
		 * Prefixed with the plugin short class name.
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @return	bool|string
		 */
		private function intervalName(string $name): bool|string
		{
			$intervals = wp_get_schedules();
			$name = esc_attr( trim($name) );
			if (in_array($name,array_keys($intervals))) return $name;
			$name = $this->plugin->addClassNamePrefix($name);
			if (in_array($name,array_keys($intervals))) return $name;
			return false;
		}


		/**
		 * set the event name from interval name.
		 * Prefixed with the plugin short class name, suffixed with "_event".
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @return	string
		 */
		private function eventName(string $name): string
		{
			$name = esc_attr( trim($name) );
			return $this->plugin->addClassNamePrefix("{$name}_event");
		}


		/**
		 * Get the eventName of a scheduled event.
		 *
		 * @param string $name interval name (e.g.'every_x_hours')
		 * @return	bool|string
		 */
		public function scheduledEventName(string $name): bool|string
		{
			$eventName = $this->eventName($name);
			return (wp_next_scheduled( $eventName )) ? $eventName : false;
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new event_scheduler_extension($this);
?>

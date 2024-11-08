<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\session_extension', false) )
{
	/**
	 * Extension: session - simple session manager - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class session_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const 	VERSION	= '24.1105.1';

		/**
		 * @var string supported session managers
		 */
		const 	SESSION_DISABLED 	= 'disabled',
				SESSION_GENERIC		= 'generic PHP session',
				SESSION_TRANSIENT	= 'transient storage',
				SESSION_PANTHION	= 'wp-native-php-sessions/pantheon-sessions.php',
				SESSION_WOOCOMMERCE = 'woocommerce/woocommerce.php';

		/**
		 * @var session object
		 */
		protected $session 			= null;
		/**
		 * @var session id
		 */
		protected $session_id 		= null;
		/**
		 * @var session id
		 */
		protected $session_cookie 	= null;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::DEFAULT_DISABLED | self::ALLOW_ADMIN);
			$this->session_cookie 	= sanitize_key('wp_'.$this->pluginName.'_session');

			if ($this->is_admin())
			{
				$this->registerExtension( $this->className );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
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
			$sessionManagers = [];
			if (session_status() !== PHP_SESSION_DISABLED ) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				$sessionManagers[self::SESSION_TRANSIENT]			= ucwords(self::SESSION_TRANSIENT);
				$sessionManagers[self::SESSION_GENERIC]				= ucwords(self::SESSION_GENERIC);
				if ( is_plugin_active(self::SESSION_WOOCOMMERCE) ) {
					$sessionManagers[self::SESSION_WOOCOMMERCE] 	= 'WooCommerce Session Manager';
				}
				if ( is_plugin_active(self::SESSION_PANTHION) ) {
					$sessionManagers[self::SESSION_PANTHION] 		= 'Panthion Native PHP Sessions';
				}
			} else {
				$sessionManagers[self::SESSION_DISABLED]			= self::SESSION_DISABLED;
			}

			/* register this extension with group name on default tab, and settings fields */
			$max_session_time = (MONTH_IN_SECONDS / HOUR_IN_SECONDS);
			$this->registerExtensionOptions( $this->className,
				[
					'session_manager'		=> array(
											'type'		=> 	'select',
											'label'		=> 	'Session Manager',
											'options'	=> 	array_flip($sessionManagers),
											'default'	=> 	(count($sessionManagers) > 2) ? array_keys($sessionManagers)[2] : array_keys($sessionManagers)[0],
											'info'		=> 	'Select available method or plugin for managing sessions.',
											'help'		=> 	'[info] <em>'.ucwords(self::SESSION_TRANSIENT).'</em> uses WordPress transients to store session data. '.
															'<em>'.ucwords(self::SESSION_GENERIC).'</em> should work with any plugin that provides session storage via standard PHP methods. '.
															'Other supported plugins: <em>WooCommerce</em> and <em>Panthion</em>.',
										),
					'session_expiration'	=> array(
											'type'		=> 'number',
											'label'		=> 'Session Expiration',
											'default'	=> '1',
											'after'		=> 'Hours',
											'info'		=> 'In hours (from 0.5 to '.$max_session_time.'), the time to retain a session with no activity.',
											'attributes'=> ['min=".5"', 'max="'.$max_session_time.'"','step=".5"']
										),
				]
			);
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			if ( session_status() === PHP_SESSION_DISABLED ) {
				$this->set_option('session_manager',self::SESSION_DISABLED);
			}

			switch ($this->get_option('session_manager'))
			{
				case self::SESSION_DISABLED:
				case self::SESSION_GENERIC:
				case self::SESSION_TRANSIENT:
					break;

				case self::SESSION_WOOCOMMERCE:
					// woocommerce session
					add_filter( 'wc_session_expiring',			array( $this, 'session_set_expiring' ) );				// when about to expire, update if active (47 hrs)
					add_filter( 'wc_session_expiration',		array( $this, 'session_set_expiration' ) );				// when to expire if no activity (48 hrs)
					break;

				case self::SESSION_PANTHION:
					add_filter( 'pantheon_session_expiration',	array( $this, 'session_set_expiring' ) );
					break;

				default:
					return;
			}

		//	add_action( 'init', 								array( $this, 'session_start'), -1 );
			add_action( 'shutdown', 							array( $this, 'session_save_data'), 8 );				// save the session data (before WC_Session @20)

			/*
			 * filter {classname}_get_session get the session array
			 *  $value = apply_filter( {className}_get_session', [] );
			 * @return	array	session array
			 */
			$this->add_filter( 'get_session', 					array($this, 'get_session'), 10, 2);

			/*
			 * filter {classname}_get_session_id get the session id
			 *  $value = apply_filter( {className}_get_session_id', false );
			 * @return	array	session array
			 */
			$this->add_filter( 'get_session_id', 				array($this, 'sessionId'));

			/*
			 * filter {classname}_is_new_session
			 *  $value = apply_filter( {className}_is_new_session', false );
			 * @return	array	session array
			 */
			$this->add_filter( 'is_new_session', 				array($this, 'isNewSession'));

			/*
			 * filter {classname}_get_variable get a stored value
			 *  $value = apply_filters( '{className}_get_variable', default, key );
			 * @return	string	session value
			 */
			$this->add_filter( 'get_variable', 					array($this,'get_session_variable'), 10, 2 );

			/*
			 * filter {classname}_set_variable set a stored value
			 *  $value = apply_filters( '{className}_set_variable', value, key );
			 * @return	string	session value
			 */
			$this->add_filter( 'set_variable', 					array($this,'set_session_variable'), 10, 2 );

			/*
			 * filter {classname}_debugging add the session array to debugging array (see debugging extension)
			 * @return	array	extended array with [ extension_name => [key=>value array] ]
			 */
			//$this->add_filter( 'debugging', 					array($this, 'session_debugging'));
			add_filter( 'eacDoojigger_debugging', 				array($this, 'session_debugging'));
		}


		/**
		 * Setup the session instance
		 *
		 * @return	void
		 */
		public function session_start()
		{
			if ( ! empty( $this->session_id ) ) return true;

			switch ($this->get_option('session_manager'))
			{
				case self::SESSION_DISABLED:
					return false;

				case self::SESSION_TRANSIENT:
					if ( isset( $_COOKIE[$this->session_cookie] ) ) {
						$this->session_id 		= $_COOKIE[$this->session_cookie];
						$this->session 			= $this->get_transient($this->session_id);
					}
					if (empty($this->session)) {
						$this->session_id 		= bin2hex(random_bytes(16));
						$this->session 			= new \stdclass();
					}
					$this->set_cookie( $this->session_cookie, $this->session_id, $this->get_session_expiration(), [/* default options */],
						[
							'category' => 'necessary',
							'function' => __( '%s sets this cookie to store a unique session ID', $this->plugin->PLUGIN_TEXTDOMAIN )
						]
					);
					break;

				case self::SESSION_GENERIC:
				case self::SESSION_PANTHION:
					if ( session_status() === PHP_SESSION_NONE ) {
						session_name($this->session_cookie);
						session_set_cookie_params($this->apply_filters('session_cookie_params',[
							'lifetime' 	=> $this->get_session_expiration(),
							'path' 		=> '/',
							'domain' 	=> $this->varServer('HTTP_HOST'),
							'secure' 	=> is_ssl(),
							'httponly' 	=> true,
							'samesite' 	=> 'strict'
						]));
						session_start();
					}
					if ( ! isset($_SESSION[$this->pluginName]) ) {
						$_SESSION[$this->pluginName] = new \stdclass();
					}
					$this->session_id 			= session_id();
					$this->session 				=& $_SESSION[$this->pluginName];		// direct access, no __get
					break;

				case self::SESSION_WOOCOMMERCE:
					if (function_exists('WC')) {
						$WC = \WC();
						$WC->initialize_session();
						if ( ! $WC->session->has_session() ) {
							$WC->session->set_customer_session_cookie( true );
						}
						if ( ! isset($WC->session->{$this->pluginName}) ) {
							$WC->session->set($this->pluginName,new \stdclass());
						}
						$this->session_id 		= $WC->session->get_customer_id();
						$this->session 			= $WC->session->get($this->pluginName);	// copy of object due to __get
					}
					break;
			}

			if ($this->session)
			{
				$this->session->session_id		= $this->session_id;
				$this->session->session_manager	= $this->get_option('session_manager');
				$this->session->session_is_new	= (!isset($this->session->session_is_new));

				/*
				 * action {classname}_session_start start the session
				 * @return	void
				 */
				$this->do_action( 'session_start' );

				return true;
			}

			return false;
		}


		/**
		 * is this a new session?
		 *
		 * @return	bool
		 */
		public function isNewSession()
		{
			if ( ! $this->session_start() ) return false;
			return $this->session->session_is_new;
		}


		/**
		 * get the session id
		 *
		 * @return	string	the session id
		 */
		public function sessionId()
		{
			if ( ! $this->session_start() ) return false;
			return $this->session_id;
		}


		/**
		 * Set or Retrieve a session variable - depreciated
		 *
		 * @param	string	$key session key
		 * @return 	string 	session variable
		 */
		public function session( $key, $value=null )
		{
			if ( ! is_null( $value ) ) {
				return $this->set_session_variable($value,$key);
			} else {
				return $this->get_session_variable(null,$key);
			}
		}


		/**
		 * Get the session array (or key)
		 * filter {classname}_get_session get the session array or key
		 *
		 * @param 	mixed 	$default - the default value
		 * @param 	string 	$key - the name of the variable
		 * @return 	array|mixed	session array or key value
		 */
		public function get_session($default = [], $key = null)
		{
			return $this->getSession($key,$default);
		}


		/**
		 * Retrieve a session variable
		 *
		 * @param	string	$key session key
		 * @param 	mixed 	$default the default value
		 * @return 	string 	session variable
		 */
		public function getSession( $key=null, $default=null )
		{
			if ( ! empty($key) ) return $this->get_session_variable($default,$key);
			if ( ! $this->session_start() ) return $default;
			return (array)$this->session;
		}


		/**
		 * set a session variable
		 *
		 * @param	string	$key session key
		 * @param 	mixed 	$value the value to set
		 * @return 	string 	session variable
		 */
		public function setSession( $key, $value )
		{
			return $this->set_session_variable($value,$key);
		}


		/**
		 * get a session value - {pluginname}_get_variable
		 *
		 * @param 	mixed 	$default - the value of the variable
		 * @param 	string 	$key - the name of the variable
		 * @return 	mixed 	the value or null
		 */
		public function get_session_variable($default, $key)
		{
			if ( ! $this->session_start() ) return $default;
			$key = \sanitize_key( $key );
			$value = $this->session->{$key} ?? $default;
			return maybe_unserialize( $value );
		}


		/**
		 * set a session value - {pluginname}_set_variable
		 *
		 * @param 	mixed 	$value - the value to set
		 * @param 	string 	$key - the name of the variable
		 * @return 	mixed 	the value or null
		 */
		public function set_session_variable($value, $key)
		{
			if ( ! $this->session_start() ) return false;
			$key = \sanitize_key( $key );
			$this->session->{$key} = maybe_serialize( $value );
			return $value;
		}


		/**
		 * Filter response to set the cookie expiration variant time to n seconds
		 *
		 * @param 	int 	$exp default expiration
		 * @return 	int
		 */
		public function session_set_expiring( $exp )
		{
			$exp = $this->get_session_expiration();
			return max( ($exp - 1800), 1800 );
		}


		/**
		 * Filter response to set the cookie expiration time to n seconds
		 *
		 * @param 	int 	$exp default expiration
		 * @return 	int
		 */
		public function session_set_expiration( $exp )
		{
			return $this->get_session_expiration();
		}


		/**
		 * Push back to transient or WC()->session on shutdown
		 *
		 * @return	void
		 */
		public function session_save_data()
		{
			if ( empty( $this->session_id ) ) return;
			/*
			 * action {classname}_session_stop stop the session
			 * @return	void
			 */
			$this->do_action( 'session_stop' );

			switch ($this->session->session_manager)
			{
				case self::SESSION_TRANSIENT:
					$exp = max($this->get_session_expiration(),30*MINUTE_IN_SECONDS);
					$this->set_transient($this->session_id,$this->session,$exp);
					break;
				case self::SESSION_WOOCOMMERCE:
					if (function_exists('WC')) {
						WC()->session->set($this->pluginName,$this->session);
					}
					break;
			}
		}


		/**
		 * Get the session expiration time in seconds
		 *
		 * @return 	int
		 */
		private function get_session_expiration( )
		{
			$exp = floatval($this->get_option('session_expiration',1));
			/*
			 * filter {classname}_session_expiration set the session expiration time
			 * @param	float	$exp in hours
			 * @return	int		$exp in hours
			 */
			$exp = floatval( $this->apply_filters( 'session_expiration', $exp ) );
			return intval(HOUR_IN_SECONDS * $exp);
		}


		/**
		 * Get the session array for debugging
		 *
		 * @param	array 	current debugging array
		 * @return	array	extended array with [ extension_name => [key=>value array] ]
		 */
		public function session_debugging($debugging_array)
		{
			if ( !empty( $this->session_id ) ) {
				$debugging_array[$this->pluginName.' Session'] = (array)$this->session;
			}
			return $debugging_array;
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new session_extension($this);
?>

<?php
namespace EarthAsylumConsulting;

/**
 * {eac}Doojigger for WordPress - Plugin front-end (public) methods and hooks.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 * @used-by		\EarthAsylumConsulting\abstract_context
 * @uses		\EarthAsylumConsulting\abstract_core
 */

abstract class abstract_frontend extends abstract_core
{
	/**
	 * @var array extension transient data
	 * @used-by loadextensions()
	 */
	protected $extensionTransient = array();


	/**
	 * Plugin constructor method
	 *
	 * {@inheritDoc}
	 *
	 * @param array header passed from loader script
	 * @return	void
	 */
	protected function __construct(array $header)
	{
		parent::__construct($header);
	}


	/**
	 * Called after instantiation of this class to load all extension classes
	 * frontend uses transient cache to load extensions
	 *
	 * @return	void
	 */
	public function loadAllExtensions(): void
	{
		$this->logInfo(__FUNCTION__,__CLASS__);

		// when loading plugin extensions, use transient for front-end
		$this->extensionTransient = $this->get_transient(self::PLUGIN_EXTENSION_TRANSIENT, []);

		parent::loadAllExtensions();

		// when loading plugin extensions from disk, save transient for front-end
		if ( count($this->extensionTransient) >= 1 ) // plugin & default extensions
		{
			$this->set_transient( self::PLUGIN_EXTENSION_TRANSIENT, $this->extensionTransient, DAY_IN_SECONDS );
		}
		unset($this->extensionTransient);
	}


	/**
	 * class initialization
	 *
	 * Called after instantiating and loading extensions
	 *
	 * @return	void
	 */
	public function initialize(): void
	{
		/**
		 * action {classname}_http_headers_ready triggered on PHP header_register_callback()
		 * add_action( '{className}_http_headers_ready', function(){...} );
		 */
		header_register_callback( function()
			{
				$this->do_action('http_headers_ready');
			}
		);

		parent::initialize();
	}


	/**
	 * Add plugin actions and filter
	 *
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @see https://codex.wordpress.org/Plugin_API
	 *
	 * @return	void
	 */
	public function addActionsAndFilters(): void
	{
		/**
		 * filter
		 * get plugin method result	- [{classname} method='getVariable' args='varName']
		 * get plugin option value 	- [{classname} option='optionName']
		 * get site bloginfo value 	- [{classname} bloginfo='bloginfoName']
		 */
		\add_filter( $this->className, 	array($this,'filter_plugin_access'), 10, 2 );

		parent::addActionsAndFilters();
	}


	/**
	 * Add plugin shortcodes
	 *
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @see https://codex.wordpress.org/Shortcode_API
	 *
	 * @return	void
	 */
	public function addShortcodes(): void
	{
		/*
		 * shortcode
		 * get plugin method result	- [{classname} method='getVariable' args='varName']
		 * get plugin option value 	- [{classname} option='optionName']
		 * get site bloginfo value 	- [{classname} bloginfo='bloginfoName']
		 */
		\add_shortcode( $this->className, array($this,'shortcode_plugin_access') );

		parent::addShortcodes();
	}


	/**
	 * filter to execute a plugin or extension method OR get a plugin or wordpress option
	 *
	 * @param string	$default 	default value if no request variable
  	 * @param array 	$args		array of arguments
  	 *					method		methodName or [extension,methodName] or extension.methodName
  	 *					args		arguments passed to method (array or string separated by ',')
  	 *					option		option name
  	 *					bloginfo	bloginfo name
  	 *					index		if array, index or key to array value
	 * @return mixed 	filter output
	 */
	public function filter_plugin_access($default, array $args)
	{
		$a = wp_parse_args(
			$args,
			[
				'method'	=> false,
				'args' 		=> [],
				'option'	=> false,
				'bloginfo'	=> false,
				'index' 	=> null,
				'default'	=> $default,
			]
		);

		$result = $this->plugin_access($a);

		if (!is_null($a['index']))
		{
			if (is_array($result))
			{
				$result = $result[ $a['index'] ] ?? $a['default'];
			}
			else if (is_object($result))
			{
				$result = $result->{$a['index']} ?? $a['default'];
			}
		}
		return $result;
	}


	/**
	 * shortcode to execute a plugin or extension method OR get a plugin or wordpress option
	 *
  	 * @param array 	$atts		Shortcode attributes (use 'method' or 'option')
  	 *					method		methodName or extension.methodName
  	 *					args		arguments passed to method (string separated by ',')
  	 *					option		option name
  	 *					bloginfo	bloginfo name
  	 *					index		if array, index or key to array value
	 *					default		default return value
	 * @param string 	$content 	default content
	 * @param string 	$tag     	The shortcode which invoked the callback
	 * @return string 	Shortcode output
	 */
	public function shortcode_plugin_access(array $atts = null, string $content = '', string $tag = ''): string
	{
		$a = shortcode_atts(
			[
				'method'	=> false,
				'args' 		=> [],
				'option'	=> false,
				'bloginfo'	=> false,
				'index' 	=> null,
				'default'	=> $content,
			],
			$atts, $tag
		);

		$result = $this->plugin_access($a);

		if (!is_null($a['index']))
		{
			if (is_array($result))
			{
				$result = $result[ $a['index'] ] ?? $a['default'];
			}
			else if (is_object($result))
			{
				$result = $result->{$a['index']} ?? $a['default'];
			}
		}
		if (is_array($result))
		{
			$result = array_shift($result);
		}

		return (string)$result;
	}


	/**
	 * execute a plugin or extension method OR get a plugin or wordpress option
	 *
  	 * @param array 	$a			array of arguments
  	 *					method		methodName or [extension,methodName] or extension.methodName
  	 *					args		arguments passed to method (array or string separated by ',')
  	 *					option		option name
  	 *					bloginfo	bloginfo name
  	 *					index		if array, index or key to array value
	 *					default		default return value
	 * @return mixed 	method output
	 */
	private function plugin_access(array $a)
	{
		$result = $a['default'];

		if ($a['method'])
		{
			if (is_string($a['method']) && strpos($a['method'],'.')) {
				$a['method'] = explode('.',$a['method']);
			}
			if (!is_array($a['args'])) {
				$a['args'] 	= array_map('trim', explode(',', $a['args']));
			}
			$result = $this->callMethodIgnore( $a['method'], ...$a['args'] ) ?? $a['default'];
		}
		else if ($a['option'])
		{
			$result = $this->get_option( $a['option'], false );
			if ($result === false)
			{
				$result = \get_option( $a['option'], $a['default'] );
			}
		}
		else if ($a['bloginfo'])
		{
			$result = \get_bloginfo( $a['bloginfo'] ) ?? $a['default'];
		}

		return $result;
	}


	/**
 	* get a post id
 	*
 	* @param int|WP_Post $post_id
 	* @return int post ID or null
 	*/
	public function get_the_id($post_id = 0)
	{
		if (empty($post_id))
		{
			$post_id = \get_the_ID() ?: \get_queried_object();
		}
		if (is_object($post_id))
		{
			$post_id = $post_id->ID;
		}
		return $post_id;
	}


	/**
 	* get a post
 	*
 	* @param int|WP_Post $post_id
 	* @return object post or null
 	*/
	public function get_the_post($post_id = 0)
	{
		if ($post_id = $this->get_the_id($post_id))
		{
			return \get_post($post_id);
		}
		return null;
	}


	/**
 	* get a post field
 	*
 	* @param string $field the custom meta field name
 	* @param int|WP_Post $post_id
 	* @param string|callable $format sprintf format string or callable function
 	* @return object  post
 	*/
	public function get_the_field(string $field, $post_id = 0, $format = "%s")
	{
		if ($post_id = $this->get_the_id($post_id))
		{
			if ($value = \get_post_meta($post_id,$field,true))
			{
				if (is_callable($format)) {
					return call_user_func($format,$value);
				}
				if (is_callable([$this,$format])) {
					return call_user_func([$this,$format],$value);
				}
				return sprintf($format, $value);
			}
		}
		return null;
	}
}

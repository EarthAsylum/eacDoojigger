<?php
namespace EarthAsylumConsulting\Extensions;

/*
 * An Ajax request can be initiated with:
 *		eacDoojigger.AjaxRequest(
 * 			'className.methodName',
 *			{parameters}
 *		);
 *
 * className can be: this class (ajaxAction), the main plugin class (eacDoojigger), or an extension class (with/without '_extension')
 * methodName is the method name within that class
 * parameters is an optional JSON object
 *
 * Example:
 *		eacDoojigger.AjaxRequest( 'ajaxAction.testNotice', {testData: 'test value'} );
 */

if (! class_exists(__NAMESPACE__.'\ajaxAction', false) )
{
	/**
	 * Extension: ajaxAction - Ajax Responder - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		24.0522.1
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class ajaxAction extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '24.0524.1';

		/**
		 * @var string action (script) id
		 */
		private $actionId;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, (self::ALLOW_ADMIN | self::ALLOW_NETWORK) );

			if ($this->is_admin())
			{
				$this->registerExtension( false );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
			}

			$this->actionId = sanitize_title($this->pluginName.'-'.$this->className);
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			if ( ! $this->plugin->is_network_admin() )
			{
				$this->registerExtensionOptions( 'plugin_settings',
					[
						'ajax_device_id' 	=> array(
								'type'		=> 	'checkbox',
								'label'		=> 	'Device Fingerprint',
								'options'	=> 	['Enabled'],
								'info'		=>	'The fingerprint uses JavaScript to capture browser &amp; devices details.',
							),
					]
				);
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			add_action( ($this->is_admin() ? 'admin' : 'wp').'_enqueue_scripts',
															array($this, 'enqueue_javascript' ) );
			add_action( 'wp_ajax_'.$this->actionId, 		array($this, 'ajax_dispatcher') );
			add_action( 'wp_ajax_nopriv_'.$this->actionId,	array($this, 'ajax_dispatcher') );
		}


		/**
		 * enqueue_scripts handler
		 *
		 * @return	void
		 */
		public function enqueue_javascript()
		{
			wp_enqueue_script('jquery');

			$url 		= admin_url('admin-ajax.php');
			$nonce 		= wp_create_nonce($this->actionId);
			$javascript = "
				if (typeof {$this->pluginName} == 'undefined') {$this->pluginName} = {};
				{$this->pluginName}.AjaxRequest = function (method,params,callback) {
					return jQuery.post('{$url}', {
						nonce	: '{$nonce}',
						action	: '{$this->actionId}',
						method	: method,
						params	: params || {}
					}, callback||null)";
			$javascript .= ($this->plugin->is_frontend())
				? ";}"
				: ".done(function( data ) {
						try {
							if (data.adminNotice) {
								['success','info','warning','error'].forEach(function(type) {
									if (data.adminNotice[type]) eacDoojigger.adminNotice(data.adminNotice[type], type);
								});
							}
						} catch(e){}
					}).fail(function( jqxhr ) {
						try {
							eacDoojigger.adminNotice(jqxhr.status +' '+ jqxhr.statusText +' '+ jqxhr.responseJSON.data||'' , 'error');
						} catch(e){}
					});
				}";

			$scriptId = sanitize_key( $this->actionId.'-'.$this->getVersion() );
			wp_register_script( $scriptId, false, ['jquery'] );
			wp_enqueue_script( $scriptId );
			wp_add_inline_script( $scriptId, $this->minifyString($javascript) );

			if ($this->is_option('ajax_device_id') && !$this->plugin->getVariable('fingerprint_hash'))
			{
				// https://github.com/thumbmarkjs/thumbmarkjs
				$scriptId = sanitize_key($this->className.'-fingerprint');
				wp_register_script( $scriptId,
					"https://cdn.jsdelivr.net/npm/@thumbmarkjs/thumbmarkjs/dist/thumbmark.umd.js",
					['jquery'],
					EACDOOJIGGER_VERSION,
				//	['strategy' => 'defer']
				);
				wp_enqueue_script( $scriptId );

				$javascript =
					"(function(){".
					"ThumbmarkJS.getFingerprint(true).then(function(fp){".
					"{$this->pluginName}.AjaxRequest( 'ajaxAction.deviceFingerprint', fp );".
					"});".
					"}());";
				wp_add_inline_script( $scriptId, $javascript );
			}
		}


		/**
		 * Execute an ajax request here or in a loaded extension
		 *
		 * @return	mixed
		 */
		public function ajax_dispatcher()
		{
			check_ajax_referer( $this->actionId, 'nonce' );

			list($className,$methodName) = explode('.',sanitize_text_field($_POST['method']));

			/**
			 * filter: {pluginname}_{classname}_{methodname}
			 *
			 * @param params from ajax request (un-sanitized)
			 */
			$params = $this->apply_filters($className.'_'.$methodName,$_POST['params'] ?: []);

			$class = ($className == $this->className)
				? $this
				: $this->plugin->getClassObject($className);

			if (is_object($class))
			{
				if (method_exists($class, $methodName))
				{
					wp_send_json(call_user_func( [$class,$methodName], $params ));
					return;
				}
			}

			if ($this->has_action($className.'_'.$methodName))
			{
				wp_send_json($params);
				return;
			}

			wp_send_json_error(
				"From ".$this->className." - unknown method: '{$className}::{$methodName}'",
				400
			);
		}


		/**
		 * Save JSON object key->value pairs
		 * eacDoojigger.AjaxRequest('ajaxAction.saveObject',{object});
		 *
		 * @param array - parameters passed from the browser
		 *
		 * @return void
		 */
		public function saveObject($params)
		{
			foreach($params as $name => $value)
			{
				$this->plugin->setVariable($name,$value);
			}
		}


		/**
		 * get device fingerprint
		 * eacDoojigger.AjaxRequest('ajaxAction.saveObject',{object});
		 *
		 * @param array - parameters passed from the browser
		 * 		'hash' => '1ea7vd2',
		 *		'data' => array (
		 *			'audio' => array(...)
		 *			'canvas' => array(...)
		 *			'fonts' => array(...)
		 *			'hardware' => array(...)
		 *			'locales' => array(...)
		 *			'permissions' => array(...)
		 *			'plugins' => array(...)
		 *			'screen' => array(...)
		 *			'system' => array(...)
		 *			'webgl' => array(...)
		 *			'math' => array(...)
		 *		)
		 * @return void
		 */
		public function deviceFingerprint($params)
		{
			$this->plugin->setVariable('fingerprint_hash',$params['hash']);
			$this->plugin->setVariable('fingerprint_data',$params['data']);
			$this->logDebug($params['hash'],__METHOD__);
		}


		/**
		 * Test response
		 * eacDoojigger.AjaxRequest('ajaxAction.testNotice',{object});
		 *
		 * @param array - parameters passed from the browser
		 * @return void
		 */
		public function testNotice($params)
		{
			return [
				'data'			=> $params,
				'adminNotice' 	=> [
					'success' 	=> 'success message',
					'info' 		=> 'info message',
					'warning' 	=> 'warning message',
					'error' 	=> 'error message',
				]
			];
		}
	}
}
/*
 * return a new instance of this class
 */
if (isset($this)) return new ajaxAction($this);
?>

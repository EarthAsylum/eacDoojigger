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
	 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class ajaxAction extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '23.0421.1';

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
			/* register this extension with no options */
			$this->registerExtension( false );
			$this->actionId = sanitize_title($this->pluginName.'-'.$this->className);
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			add_action( ($this->is_admin() ? 'admin_' : 'wp_') .'enqueue_scripts',
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
			$url 		= admin_url('admin-ajax.php');
			$nonce 		= wp_create_nonce($this->actionId);
			$javascript = "
				if (typeof {$this->pluginName} == 'undefined') {$this->pluginName} = {};

				{$this->pluginName}.AjaxRequest = function (method,params,callback) {
					var ajaxRequest = {
						nonce	: '{$nonce}',
						action	: '{$this->actionId}',
						method	: method,
						params	: params || {}
					}
					return jQuery.post('{$url}', ajaxRequest, callback||null)";
			$javascript .= ($this->plugin->is_frontend())
				? ";
				}"
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

			wp_register_script( $this->actionId, false );
			wp_enqueue_script( $this->actionId );
			wp_add_inline_script( $this->actionId, $this->minifyString($javascript) );
		}


		/**
		 * Execute an ajax request here or in a loaded extension
		 *
		 * @return	mixed
		 */
		public function ajax_dispatcher()
		{
			check_ajax_referer( $this->actionId, 'nonce' );

			list($className,$methodName) = explode('.',$_POST['method']);

			if ($className == $this->className) {
				$class = $this;
			} else {
				$class = $this->plugin->getClassObject($className);
			}

			if (is_object($class) && method_exists($class, $methodName)) {
				wp_send_json(
					call_user_func( [$class,$methodName], $_POST['params'] ?: [] )
				);
			}
			wp_send_json_error("From ".$this->className." - unknown method: '{$className}::{$methodName}'",400);
		}


		/**
		 * Save JSON object key->value pairs
		 * eacDoojigger.AjaxRequest('ajaxAction.saveObject',{object});
		 *
		 * @param array - parameters passed from the browser
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

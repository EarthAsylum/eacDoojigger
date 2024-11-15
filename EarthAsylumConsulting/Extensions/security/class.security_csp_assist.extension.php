<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\security_csp_assistant', false) )
{
	/**
	 * Extension: Content Security Assistant - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 */

	class security_csp_assistant extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION 			= '24.1115.1';

		/**
		 * @var string extension version
		 */
		const TAB_NAME 			= 'Security';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		=
			"<abbr title='Facilitates the creation of a comprehensive <em>Content Security Policy</em> (CSP) ".
			"by adding a security nonce to script and style link tags.'>Content Security Assistant</abbr>";

		/**
		 * @var string content security nonce
		 */
		private $csp_nonce		= null;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::DEFAULT_DISABLED | self::ALLOW_ADMIN | self::ALLOW_NETWORK | self::ALLOW_NON_PHP );

			// must have security extension enabled
			if (! $this->isEnabled('security')) return false;

			if ($this->is_admin())
			{
				$this->registerExtension( $this->className );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}
			$this->rename_option('secContentSecurity','sec_CSP_nonce');
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			require 'includes/security_csp.options.php';
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

			if ($this->security->isPolicyEnabled('sec_CSP_nonce'))
			{
				// load scripts individually so we can access the tags
				if (!defined('CONCATENATE_SCRIPTS')) define('CONCATENATE_SCRIPTS',false);

				// create the nonce we'll use
				$this->csp_nonce = $this->apply_filters('set_security_nonce',wp_create_nonce());

				/**
				 * filter {pluginname}_security_nonce - get CSP security nonce for this request
				 */
				$this->add_filter('security_nonce', function() {return $this->csp_nonce;});
			}
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 */
		public function addActionsAndFilters()
		{
			// script nonces for Content Security Policy (CSP) headers
			if ($this->security->isPolicyEnabled('sec_CSP_nonce','script'))
			{
				add_filter( 'wp_inline_script_attributes',	function($attributes, $data) {
					$attributes['nonce'] = $this->csp_nonce;
					return $attributes;
				},10,2);
				add_filter( 'script_loader_tag',			array($this, 'script_csp_nonce'),101,3);
			}
			if ($this->security->isPolicyEnabled('sec_CSP_nonce','style'))
			{
				add_filter( 'style_loader_tag',				array($this, 'style_csp_nonce'),101,4);
			}

			if ($this->security->isPolicyEnabled('sec_CSP_action','action'))
			{
				add_action('init', function() {
					$this->do_action('content_security_policy',$this->csp_nonce);
				});
			}
		/*
			$csp	 = //"default-src 'self' 'unsafe-inline' 'strict-dynamic'; ".
					   "base-uri 'self'; img-src 'self' *.earthasylum.com; form-action 'self'; frame-ancestors 'none'; ";
			$csp	.= "script-src 'nonce-{$this->csp_nonce}' 'unsafe-inline' 'strict-dynamic'; ";
		//	$csp	.= "style-src 'nonce-{$this->csp_nonce}' 'strict-dynamic'; ";
		//	$csp	.= "style-src-attr 'unsafe-inline' 'strict-dynamic'; ";
			header("Content-Security-Policy: {$csp}");
		*/
		}


		/**
		 * Add nonce to js tags
		 *
		 * @param string $tab script tag
		 * @param string $handle script/style name
		 * @param string $src tag src= or href=
		 */
		public function script_csp_nonce(string $tag, string $handle, string $src): string
		{
			if (!str_contains($tag,' nonce')){
				$tag = str_replace("<script ","<script nonce=\"{$this->csp_nonce}\" ",$tag);
			}
			return $tag;
		}


		/**
		 * Add nonce to css tags
		 *
		 * @param string $tab script tag
		 * @param string $handle script/style name
		 * @param string $src tag src= or href=
		 * @param string $media tag media=
		 */
		public function style_csp_nonce(string $tag, string $handle, string $src, string $media): string
		{
			if (!str_contains($tag,' nonce')){
				$tag = str_replace("<link ","<link nonce=\"{$this->csp_nonce}\" ",$tag);
			}
			return $tag;
		}
	}
}

/**
 * return a new instance of this class
 */
if (isset($this)) return new security_csp_assistant($this);
?>

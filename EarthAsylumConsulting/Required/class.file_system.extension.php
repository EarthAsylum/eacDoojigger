<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * Extension: file_system - expands on the WordPress WP_filesystem - {eac}Doojigger for WordPress
 *
 * Note: see eacDoojigger_ftp_credentials.php
 * filters exist as part of the {eac}Doojigger autoloader to populate filesystem
 * credentials when accessed (get/update) the 'ftp_credentials' option.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

class file_system_extension extends \EarthAsylumConsulting\abstract_extension
{
	/**
	 * @var string extension version
	 */
	const VERSION	= '24.0416.1';

	/**
	 * @var string extension alias
	 */
	const ALIAS		= 'fs';

	/**
	 * @var object WordPress filesystem
	 */
	public $filesystem			= false;

	/**
	 * @var string WordPress filesystem method
	 */
	public $filesystem_method	= null;

	/**
	 * @var object WordPress filesystem default owner
	 */
	public $filesystem_owner	= null;

	/**
	 * @var object WordPress filesystem default group
	 */
	public $filesystem_group	= null;


	/**
	 * constructor method
	 *
	 * @param 	object	$plugin main plugin object
	 * @return 	void
	 */
	public function __construct($plugin)
	{
		$this->enable_option = false;
		parent::__construct($plugin, self::ALLOW_ALL|self::ALLOW_NON_PHP);

		// store as separate option keys
		$this->isReservedOption('filesystem_multisite',true);
		$this->isReservedOption('filesystem_credentials',true);

		if ($this->is_admin())
		{
			$this->registerExtension( $this->className );
			// Register plugin options when needed
			$this->add_action( "options_settings_page", 		array( $this, 'admin_options_settings') );
			// Add contextual help
			$this->add_action( 'options_settings_help', 		array( $this, 'admin_options_help') );
		}
	}


	/**
	 * Add extension actions and filter
	 *
	 * Called after loading, instantiating, and initializing all extensions
	 *
	 * @return	void
	 */
	public function addActionsAndFilters()
	{
		parent::addActionsAndFilters();

		$this->add_filter( 'load_filesystem', function($wpfs, $useForm = false, string $notice = '', array $args = [])
		{
			return (is_a($wpfs,'WP_Filesystem_Base'))
				? $wpfs
				: $this->load_wp_filesystem($useForm,$notice,$args);
		}, 10, 4 );

		$this->add_filter( 'link_filesystem', function($wpfs, $useForm = false, string $notice = '', array $args = [])
		{
			return (is_a($wpfs,'WP_Filesystem_Base'))
				? $wpfs
				: $this->link_wp_filesystem($useForm,$notice,$args);
		}, 10, 4 );
	}


	/**
	 * register options on options_settings_page
	 *
	 * @return void
	 */
	public function admin_options_settings()
	{
		if ($this->get_filesystem_method() == 'direct') return;

		include 'file_system/file_system.options.php';
	}


	/**
	 * Add help tab on admin page
	 *
	 * @return	void
	 */
	public function admin_options_help()
	{
		if (!$this->plugin->isSettingsPage('General')) return;

		if ($this->get_filesystem_method() == 'direct') return;

		include 'file_system/file_system.help.php';
	}


	/**
	 * get current filesystem_method (cached)
	 *
	 * @return	string
	 */
	public function get_filesystem_method()
	{
		if (is_null($this->filesystem_method))
		{
			$this->filesystem_method = \get_filesystem_method();
		}
		return $this->filesystem_method;
	}


	/**
	 * Check for & use WP_Filesystem.
	 * Use as part of a settings page (or not) to get WP_Filesystem credentials when needed.
	 *
	 * @example if ($fs = $this->load_wp_filesystem(true,'WordPress file access is required to...'));
	 * @example if ($fs = $this->load_wp_filesystem());
	 *
	 * @param bool|mixed $useForm (false) when truthy, prompt for credentials if needed
	 * @param string $notice ('') display an 'admin notice' message before the form
	 * @param array $args ([]) request_filesystem_credentials() arguments (override defaults)
	 * @return object|bool \WP_Filesystem or false
	 */
	public function load_wp_filesystem($useForm = false, string $notice = '', array $args = [])
	{
		static $fs_form_pending = false; // if multiple calls on a page, only display the form once

		if (!function_exists('\WP_Filesystem'))
		{
			include(ABSPATH . 'wp-admin/includes/file.php');
		}

		if ($this->filesystem) return $this->filesystem;

		$useForm = ($useForm && !$fs_form_pending);

		$fsCredentials = \get_option('ftp_credentials');

		if ( !($this->filesystem = \WP_Filesystem( $fsCredentials )) && $useForm && $this->is_admin() )
		{
			// $args may override request_filesystem_credentials() defaults
			$args = wp_parse_args($args,[
				'form_post'		=> remove_query_arg('fs'),	// The URL to post the form to
				'type'			=> '',						// Chosen type of filesystem
				'error'			=> false,					// Whether the current request has failed to connect, or an error object
				'context'		=> '',						// Full path to the directory that is tested for being writable
				'extra_fields'	=> '',						// Extra POST fields to be checked for inclusion in the post
				'allow_relaxed_file_ownership' => false,	// Whether to allow Group/World writable
			]);
			$args['form_post'] = sanitize_url($args['form_post']);

			ob_start();
			$fsCredentials 	= request_filesystem_credentials(
				$args['form_post'], $args['type'], $args['error'], $args['context'], $args['extra_fields'], $args['allow_relaxed_file_ownership']
			);
			$form = false;
			// check for filesystem access and credentials form
			if ( ! ($this->filesystem = \WP_Filesystem( $fsCredentials ))
			and  ! ($form = ob_get_contents()) )
			{
				request_filesystem_credentials(
					$args['form_post'], $args['type'], true, $args['context'], $args['extra_fields'], $args['allow_relaxed_file_ownership']
				);
				$form = ob_get_contents();
			}
			ob_end_clean();
			// show the form in an admin notice block moved to the top of the page
			if ($form)
			{
				$fs_form_pending = true;
				echo "<style>#load-wp-filesystem {max-width: 48em; background: transparent; border-width: 0 0 0 4px;}</style>\n";
				echo "<div style='display:block'>\n";
				if ($notice)
				{
					$notice = wp_kses_post(nl2br($notice));
					echo "<div class='notice notice-info notice-custom'><h4>".$notice."</h4></div>\n";
				}
				$form = str_replace("This password will not be stored on the server.","",$form);
				echo "<div id='load-wp-filesystem' class='notice notice-custom'>\n".$form.
					 "<div><em>* Your credentials will be encrypted when stored.</em></div>\n</div>";
				echo "</div>\n";
				die();
			}
		}

		if ($this->filesystem)
		{
			$this->filesystem 			= $GLOBALS['wp_filesystem'];
			$this->filesystem_method 	= $this->filesystem->method;
			$this->filesystem_owner 	= defined('FTP_USER') ? FTP_USER : (fileowner(WP_CONTENT_DIR) ?: null);
			$this->filesystem_group 	= defined('FTP_GROUP') ? FTP_GROUP : (filegroup(WP_CONTENT_DIR) ?: null);
		}
		return $this->filesystem;
	}


	/**
	 * Inform and link to connection information form (via load_wp_filesystem)
	 * Use when we don't want the form but to give user link to the form (if needed).
	 *
	 * @example if ($fs = $this->link_wp_filesystem('WordPress file access is required to...'));
	 *
	 * @param bool|mixed $useForm (true) when truthy, display notice with link
	 * @param string $notice ('') display an 'admin notice' message before the form
	 * @param array $args ([]) request_filesystem_credentials() arguments (override defaults)
	 * @return object|bool \WP_Filesystem or false
	 */
	public function link_wp_filesystem($useForm = true, string $notice = '', array $args = [])
	{
		if (isset($_GET['fs']) && wp_verify_nonce($_GET['fs'],__FUNCTION__))
		{
			// our link has been clicked to complete the action
			$fs = $this->load_wp_filesystem(true,$notice,$args);
		}
		else
		if (isset($_POST['_fs_nonce']) && wp_verify_nonce($_POST['_fs_nonce'],'filesystem-credentials'))
		{
			// the connection form has been submitter
			$fs = $this->load_wp_filesystem(true,$notice,$args);
		}
		else
		{
			// connect or display a notice, including link to continue action
			if (!($fs = $this->load_wp_filesystem()) && $useForm)
			{
				$args = wp_parse_args($args,[
					'form_post'		=> $_SERVER['REQUEST_URI'],	// The URL to post the form to
				]);
				$form 	= sanitize_url($args['form_post']);
				$nonce  = wp_create_nonce(__FUNCTION__);
				$this->add_admin_notice($notice,'error',
					'<span class="dashicons dashicons-admin-network"></span> '.
					'[<a href="'.add_query_arg(['fs'=>$nonce],$form ).
					'">Enter your file system connection information to complete this action</a>]'
				);
			}
		}
		return $fs;
	}


	/**
	 * Add admin notice
	 *
	 * @param string $message message text
	 * @param string $errorType 'error', 'warning', 'info', 'success'
	 * @param string $moreInfo additional message text
	 * @return void
	 */
// 	public function add_admin_notice(string $message, string $errorType='info', string $moreInfo=''): void
// 	{
// 		$message = "<strong>".wp_kses_post(nl2br(__($message)))."</strong>";
// 		if (!empty($moreInfo)) {
// 			$message .= '<br>'.wp_kses_post(nl2br(__($moreInfo)));
// 		}
// 		echo "<div class='notice notice-{$errorType} is-dismissible'>" . "<p>{$message}</p>" . "</div>";
// 	}
}
/*
 * return a new instance of this class
 */
if (isset($this)) return new file_system_extension($this);
?>

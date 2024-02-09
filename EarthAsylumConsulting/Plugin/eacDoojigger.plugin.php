<?php
namespace EarthAsylumConsulting\Plugin;
require "eacDoojigger.trait.php";

/**
 * Primary plugin file - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		2.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see			https://eacDoojigger.earthasylum.com/phpdoc/
 * @uses		\EarthAsylumConsulting\abstract_context
 * @uses		\EarthAsylumConsulting\abstract_frontend
 * @uses		\EarthAsylumConsulting\abstract_backend
 */

class eacDoojigger extends \EarthAsylumConsulting\abstract_context
{
	/**
	 * @trait eacDoojigger, loads only admin methods
	 */
	use \EarthAsylumConsulting\Plugin\eacDoojigger_administration;

	/**
	 * @var int folder permission
	 * @deprecated
	 */
	const FOLDER_PERMISSION			= 0775; //FS_CHMOD_DIR;

	/**
	 * @var int file permission
	 * @deprecated
	 */
	const FILE_PERMISSION			= 0664; //FS_CHMOD_FILE;

	/**
	 * @var object wp-config-transformer
	 */
	private $wpConfig = false;


	/**
	 * constructor method
	 *
	 * @access public
	 * @param array header passed from loader script
	 * @return void
	 */
	public function __construct(array $header)
	{
		parent::__construct($header);

		$this->logAlways('version '.$this->getVersion().' '.wp_date('Y-m-d H:i:s',filemtime(__FILE__)),__CLASS__);

		if ($this->is_admin())
		{
			$this->admin_construct($header); 	// in admin trait
		}
	}


	/**
	 * Called after instantiating, loading extensions and initializing
	 *
	 * @see https://codex.wordpress.org/Plugin_API
	 *
	 * @return	void
	 */
	public function addActionsAndFilters(): void
	{
		parent::addActionsAndFilters();

		// add admin bar menu
		if ($this->is_option('adminSettingsMenu','Menu Bar') && current_user_can('manage_options'))
		{
			\add_action( 'admin_bar_init', 				array( $this, 'get_admin_bar_menu') );
			\add_action( 'admin_bar_menu', 				array( $this, 'set_admin_bar_menu') );
		}
	}


	/**
	 * Called after instantiating, loading extensions and initializing
	 *
	 * @see https://codex.wordpress.org/Shortcode_API
	 *
	 * @return	void
	 */
	public function addShortcodes(): void
	{
		parent::addShortcodes();
	}


	/**
	 * process the admin bar item
	 *
	 * @param object $admin_bar wp_admin_bar
	 * @return void
	 */
	public function get_admin_bar_menu($admin_bar)
	{
		if (!isset($_GET['_eacfn']) || !isset($_GET['_wpnonce'])) return;

		$menuFN 	= $this->varGet('_eacfn');
		$wpnonce 	= $this->varGet('_wpnonce');
		if (wp_verify_nonce($wpnonce,$this->className))
		{
			switch ($menuFN)
			{
				case 'flush_cache':
					$this->do_action('flush_caches');
					break;
			}
		}
		// so a reload doesn't initiate again
		wp_safe_redirect( remove_query_arg(['_eacfn','_wpnonce']) );
		exit;
	}


	/**
	 * add the admin bar menu
	 *
	 * @param object $admin_bar wp_admin_bar
	 * @return void
	 */
	public function set_admin_bar_menu($admin_bar)
	{
		$admin_bar->add_menu(
			[
				'id'     	=> $this->className,
				'parent' 	=> 'top-secondary',
				'title' 	=> '{eac}',
				'href'		=> $this->getSettingsURL(true),
			]
		);
		$admin_bar->add_menu(
			[
				'id'     	=> $this->className.'-node',
				'parent'    => $this->className,
				'title' 	=> $this->pluginHeader('Title'),
				'href'		=> $this->getSettingsURL(true),
			]
		);

		if (method_exists($this, 'getTabNames'))
		{
			$admin_bar->add_group(
				[
					'id'     	=> $this->className.'-group',
					'parent'    => $this->className,
				]
			);
			$tabNames 	= $this->getTabNames();
			foreach ($tabNames as $tabName)
			{
				$admin_bar->add_menu(
					[
						'id'     	=> $this->className.'-'.$this->toKeyString($tabName),
						'parent'    => $this->className.'-group',
						'title' 	=> '&raquo; '.$tabName,
						'href'		=> $this->getSettingsURL(true,$this->toKeyString($tabName)),
					]
				);
			}
		}

		$admin_bar->add_group(
			[
				'id'     	=> $this->className.'-menu',
				'parent'    => $this->className,
			]
		);
		$admin_bar->add_menu(
			[
				'id'     	=> $this->className.'-flush',
				'parent'   	=> $this->className.'-menu',
				'title' 	=> "Flush Caches",
				'href'   	=> wp_nonce_url( add_query_arg(['_eacfn'=>'flush_cache']),$this->className ),
			]
		);
	}


	/*
	 *
	 * Check valid registration license level.
	 *
	 * Without eacDoojigger_registration :
	 * 		$this->Registration->isRegistryValue(...) 	fails with runtime error.
	 * 		$this->apply_filters('registry_value',...) 	gracefully returns default.
	 *
	 */


	/**
	 * is license L2 (basic) or better
	 *
	 * @return	bool
	 */
	public function isBasicLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'L2', 'ge');
	//	return $this->apply_filters('registry_value',false,'license', 'L2', 'ge');
	}


	/**
	 * is license L3 (standard) or better
	 *
	 * @return	bool
	 */
	public function isStandardLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'L3', 'ge');
	//	return $this->apply_filters('registry_value',false,'license', 'L3', 'ge');
	}


	/**
	 * is license L4 (professional) or better
	 *
	 * @return	bool
	 */
	public function isProfessionalLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'L4', 'ge');
	//	return $this->apply_filters('registry_value',false,'license', 'L4', 'ge');
	}


	/**
	 * is license L5 (enterprise) or better
	 *
	 * @return	bool
	 */
	public function isEnterpriseLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'L5', 'ge');
	//	return $this->apply_filters('registry_value',false,'license', 'L5', 'ge');
	}


	/**
	 * is advanced mode - aids in complexity and/or licensing limits.
	 * default uses EACDOOJIGGER_ADVANCED_MODE to set global and settings.
	 *
	 * @param string $what - what is in advanced mode
	 * @param string $level - what level is in advanced mode
	 * @return	bool
	 */
	public function isAdvancedMode(string $what = null, string $level = null): bool
	{
		// specifically turned off
		if ( defined( 'EACDOOJIGGER_ADVANCED_MODE' ) && $this->isFalse(EACDOOJIGGER_ADVANCED_MODE) ) {
			return false;
		}

		// enterprise always advanced mode
		if ($this->isEnterpriseLicense()) {
			return true;
		}
		// if professional required
		if ($level == 'pro') {
			return $this->isProfessionalLicense();
		}
		// standard always required + defined constant
		return ($this->isStandardLicense())
			? parent::isAdvancedMode($what, $level)
			: false;
	}
}

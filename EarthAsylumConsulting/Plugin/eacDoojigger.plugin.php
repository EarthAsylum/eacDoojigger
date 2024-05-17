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

		// allow advanced mode
		$this->add_filter('allow_advanced_mode',		array( $this, 'allow_advanced_mode'), PHP_INT_MAX);
		$this->allowAdvancedMode(true);

		// add admin bar menu
		if ($this->is_option('adminSettingsMenu','Menu Bar') && current_user_can('manage_options'))
		{
		//	\add_action( 'admin_bar_init', 				array( $this, 'get_admin_bar_menu') );
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
/*
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
				case 'advanced_mode_enable':
					if ($wpConfig = $this->wpconfig_handle()) {
						$constant = strtoupper($this->pluginName).'_ADVANCED_MODE';
						$wpConfig->update( 'constant', $constant, 'TRUE', ['raw'=>true] );
					}
					break;
				case 'advanced_mode_disable':
					if ($wpConfig = $this->wpconfig_handle()) {
						$constant = strtoupper($this->pluginName).'_ADVANCED_MODE';
						$wpConfig->update( 'constant', $constant, 'FALSE', ['raw'=>true] );
					}
					break;
			}
		}
		// so a reload doesn't initiate again
		wp_safe_redirect( remove_query_arg(['_eacfn','_wpnonce']) );
		exit;
*/
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

		// $actionKey functions built in to abstract class
		if ($this->is_admin() && $this->allowAdvancedMode())
		{
			$switchTo = $this->isAdvancedMode() ? 'Disable' : 'Enable';
			$admin_bar->add_menu(
				[
					'id'     	=> 'advanced-mode',
					'parent'   	=> $this->className.'-menu',
					'title' 	=> "{$switchTo} Advanced Mode",
					'href'   	=> $this->add_admin_action_link( strtolower($switchTo).'_advanced_mode' ),
				]
			);
		}

		$admin_bar->add_menu(
			[
				'id'     	=> $this->className.'-flush',
				'parent'   	=> $this->className.'-menu',
				'title' 	=> "Flush Caches",
				'href'   	=> $this->add_admin_action_link( 'flush_caches' ),
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
	 * alllow advanced mode - aids in complexity and/or licensing limits.
	 * standard license or better to enable advanced mose
	 *
	 * @param bool $allow - allow or not
	 * @return	bool
	 */
	public function allow_advanced_mode(bool $allow): bool
	{
		return $allow && $this->isStandardLicense();
	}


	/**
	 * set advanced mode - aids in complexity and/or licensing limits.
	 * allow settings 'advanced' attribute of 'standard', 'professional', 'enterprise'
	 *
	 * @param bool $is - is or is not
	 * @param string $what - what is in advanced mode (global, settings, ...)
	 * @param string $level - what level is in advanced mode (default, basic, standard, pro)
	 * @return	void
	 */
	public function setAdvancedMode( $is = true, string $what = null,string $level = null): void
	{
		if ($is === true && $what == 'settings')
		{
			// set after extensions have loaded (including registration extension)
			$this->add_action('extensions_loaded', function()
				{
					$this->advanced_mode['settings']['standard'] 		= $this->isStandardLicense();
					$this->advanced_mode['settings']['professional']	= $this->isProfessionalLicense();
					$this->advanced_mode['settings']['enterprise'] 		= $this->isEnterpriseLicense();
				}
			);
		}
		parent::setAdvancedMode($is,$what,$level);
	}
}

<?php
namespace EarthAsylumConsulting\Plugin;
require "eacDoojigger.trait.php";

/**
 * Primary plugin file - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.earthasylum.com>
 * @version		3.x
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
	use \EarthAsylumConsulting\Plugin\eacDoojigger_admin_traits;

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
	 * @var array style handles excluded from preloading
	 */
	private $style_preload_exclude 	= [
	//		'woocommerce',
			'debug-bar',
			'query-monitor',
	];

	/**
	 * @var array style handles excluded from async-ing
	 */
	private $style_async_exclude 	= [
			'woocommerce',
	];

	/**
	 * @var array script handles excluded from preloading
	 */
	private $script_preload_exclude = [
			'wpemoji',
			'concatemoji',
	];

	/**
	 * @var array script handles excluded from async-ing
	 */
	private $script_async_exclude 	= [
			'wp-i18n',
			'wpemoji',
			'concatemoji',
			'jquery',
	];

	/**
	 * @var array preload sources
	 */
	private $optimize_preload 		= [];

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

		$this->logInfo('version '.$this->getVersion().' '.wp_date('Y-m-d H:i:s',filemtime(__FILE__)),__CLASS__);

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

		/**
		 * add logging actions
		 */
		$this->add_action('log_info',					array($this,'logInfo'),10,3);
		$this->add_action('log_notice',					array($this,'logNotice'),10,3);
		$this->add_action('log_warning',				array($this,'logWarning'),10,3);
		$this->add_action('log_error',					array($this,'logError'),10,3);
		$this->add_action('log_debug',					array($this,'logDebug'),10,3);
		$this->add_action('log_always',					array($this,'logAlways'),10,3);

		if (! is_admin() )
		{
			if ($this->is_option('optimize_options','style')) {
				/**
				 * filter {pluginname}_exclude_style_preload css handles to exclude from preloading
				 * @param array handle name(s)
				 */
				$this->style_preload_exclude = $this->apply_filters('exclude_style_preload',$this->style_preload_exclude);
				add_filter('style_loader_src',	array($this, 'optimize_hints'),100,2);
			}
			if ($this->is_option('optimize_options','style-async')) {
				/**
				 * filter {pluginname}_exclude_style_async css handles to exclude from async-ing
				 * @param array handle name(s)
				 */
				$this->style_async_exclude = $this->apply_filters('exclude_style_async',$this->style_async_exclude);
				add_filter('style_loader_tag', array($this, 'optimize_async_style'),100,4);
			}

			if ($this->is_option('optimize_options','script')) {
				/**
				 * filter {pluginname}_exclude_script_preload js handles to exclude from preloading
				 * @param array handle name(s)
				 */
				$this->script_preload_exclude = $this->apply_filters('exclude_script_preload',$this->script_preload_exclude);
				add_filter('script_loader_src', array($this, 'optimize_hints'),100,2);
			}
			if ($this->is_option('optimize_options','script-async')) {
				/**
				 * filter {pluginname}_exclude_script_async js handles to exclude from async-ing
				 * @param array handle name(s)
				 */
				$this->script_async_exclude = $this->apply_filters('exclude_script_async',$this->script_async_exclude);
				add_filter('script_loader_tag', array($this, 'optimize_async_script'),100,3);
			}
			//add_action('wp_head', 			array($this, 'optimize_preload'));
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

		// place holder for actors to add items
		$admin_bar->add_group(
			[
				'id'     	=> $this->className.'-menu',
				'parent'    => $this->className,
			]
		);

		$admin_bar->add_group(
			[
				'id'     	=> $this->className.'-util',
				'parent'    => $this->className,
			]
		);

		// $actionKey functions built in to abstract class
		if ($this->is_admin() && $this->isSettingsPage() && $this->allowAdvancedMode())
		{
			$switchTo = $this->isAdvancedMode() ? 'Disable' : 'Enable';
			$admin_bar->add_menu(
				[
					'id'     	=> 'advanced-mode',
					'parent'   	=> $this->className.'-util',
					'title' 	=> "{$switchTo} Advanced Mode",
					'href'   	=> $this->add_admin_action_link( strtolower($switchTo).'_advanced_mode' ),
				]
			);
		}

		$admin_bar->add_menu(
			[
				'id'     	=> $this->className.'-flush',
				'parent'   	=> $this->className.'-util',
				'title' 	=> "Flush Caches",
				'href'   	=> $this->add_admin_action_link( 'flush_caches' ),
			]
		);
	}


	/**
	 * Pre-load headers for js & css
	 *
	 * @param string $src tag src= or href=
	 * @param string $handle script/style name
	 */
	public function optimize_hints(string $src, string $handle): string
	{
		if (strpos($src, home_url()) === false || headers_sent()) return $src;
		// limit the size of the headers
		$headerSize = strlen(implode('	', headers_list()));
		if ($headerSize > 3072) return $src;

		$type	= strtok(current_filter(),'_');

		$excluded = "{$type}_preload_exclude";
		foreach ($this->{$excluded} as $exclude) {
			if (str_starts_with($handle,$exclude)) return $src;
		}

		$src	= (substr($src, 0, 2) == '//')
					? preg_replace('/^\/\/([^\/]*)\//', '/', $src)
					: preg_replace('/^http(s)?:\/\/[^\/]*/', '', $src);

		//if (str_starts_with($src,'/wp-includes')) {
		//	$this->optimize_preload[$type][] = $src;
		//}

		$linkHeader = sprintf('Link: <%s>; rel=preload; as=%s',esc_url($src),$type);
		header($linkHeader, false);

		return $src;
	}


	/**
	 * Make css async
	 *
	 * @param string $tab style tag
	 * @param string $handle script/style name
	 * @param string $src tag src= or href=
	 * @param string $media tag media=
	 */
	public function optimize_async_style(string $tag, string $handle, string $src, string $media): string
	{
		foreach ($this->style_async_exclude as $exclude) {
			if (str_starts_with($handle,$exclude)) return $tag;
		}

		$nonce = $this->apply_filters('security_nonce',null);
		$nonce = ($nonce) ? " nonce='{$nonce}'" : "";
		$tag = "<link rel='preload' id='{$handle}-css' href='{$src}' as='style' media='{$media}'{$nonce}>\n".
				"<script{$nonce}>document.getElementById('{$handle}-css').addEventListener(".
					"'load',(e)=>{e.currentTarget.rel='stylesheet';},{once:true});".
				"</script><noscript>" . trim($tag) . "</noscript>\n";

		return $tag;
	}


	/**
	 * Make js async
	 *
	 * @param string $tab script tag
	 * @param string $handle script/style name
	 * @param string $src tag src= or href=
	 */
	public function optimize_async_script(string $tag, string $handle, string $src): string
	{
		if (str_contains($tag,' async')) return $tag;
		if (str_contains($tag,' defer')) return $tag;

		foreach ($this->script_async_exclude as $exclude) {
			if (str_starts_with($handle,$exclude)) return $tag;
		}

		$tag = str_replace("<script ","<script async ",$tag);
		return $tag;
	}


	/**
	 * Pre-load meta tags for js & css
	 *
	 */
	/*
	public function optimize_preload($src)
	{
		foreach ($this->optimize_preload as $type => $preloads) {
			array_walk($preloads, function ($src) use ($type) {
				printf("<link rel='preload' href='%s' as='%s'>\n", esc_url($src), $type);
			});
		}
		return $src;
	}
	*/


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
	 * is license LD (developer)
	 *
	 * @return	bool
	 */
	public function isDeveloperLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'LD', 'eq');
	//	return $this->apply_filters('registry_value',false,'license', 'LD', 'eq');
	}


	/**
	 * is license LU (unlimited)
	 *
	 * @return	bool
	 */
	public function isUnlimitedLicense(): bool
	{
		return $this->Registration->isRegistryValue('license', 'LU', 'eq');
	//	return $this->apply_filters('registry_value',false,'license', 'LU', 'eq');
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
					$this->advanced_mode['settings']['developer'] 		= $this->isDeveloperLicense();
					$this->advanced_mode['settings']['unlimited'] 		= $this->isUnlimitedLicense();
				}
			);
		}
		parent::setAdvancedMode($is,$what,$level);
	}
}

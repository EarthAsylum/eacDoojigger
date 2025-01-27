<?php
namespace EarthAsylumConsulting\Traits;

/**
 * advanced mode trait - advanced mode settings & features
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.1215.1
 * @see 		https://github.com/EarthAsylum/docs.eacDoojigger/wiki/How-To#implementing-and-using-advanced-mode
 */
trait advanced_mode
{
	/**
	 * @var array Advanced Mode.
	 * use setAdvancedMode(bool, what, level) to set,
	 * use isAdvancedMode(what, level) to check
	 */
	public $advanced_mode				= array(
		'global'		=> array(
			'default'	=> false,
		),
		'settings'		=> array(
			'default'	=> false,
		),
	);

	/**
	 * @var bool Advanced Mode allowed.
	 */
	public $advanced_mode_allowed		= false;


	/**
	 * initialize advanced mode settings (on _plugin_startup)
	 *
	 */
	private function advanced_mode_init(): void
	{
		$this->config_advanced_mode(); 	// before user loaded - backward compatible with define(plugin_ADVANCED_MODE)
		add_action( 'set_current_user', array ($this, 'config_advanced_mode' ), 5 );

		/**
		 * filter {classname}_is_advanced_mode check for advanced mode
		 * @param bool 		$bool 	ignored
		 * @param string 	$what 	what is in advanced mode
		 * @param string 	$level	what level is in advanced mode (default, basic, standard, pro)
		 * @return	bool
		 */
		$this->add_filter('is_advanced_mode', 	array($this,'is_advanced_mode'),10,3);

		/**
		 * action {classname}_enable_advanced_mode enable advanced mode (from admin menu or link)
		 */
		$this->add_action('enable_advanced_mode',function()
		{
			if ($this->is_admin() && $this->allowAdvancedMode() && $user = get_current_user_id()) {
				$option = $this->addClassNamePrefix('advanced_mode');
				\update_user_meta($user,$option,'true');
			}
		});
		/**
		 * action {classname}_disable_advanced_mode disable advanced mode (from admin menu or link)
		 */
		$this->add_action('disable_advanced_mode',function()
		{
			if ($this->is_admin() && $this->allowAdvancedMode() && $user = get_current_user_id()) {
				$option = $this->addClassNamePrefix('advanced_mode');
				\delete_user_meta($user,$option);
			}
		});
	}


	/*
	 *
	 * Advanced mode
	 *
	 */


	/**
	 * config advanced mode - aids in complexity and/or licensing limits.
	 * set 'advanced mode' based on defined constant or user meta
	 *
	 * @return void
	 */
	public function config_advanced_mode(): void
	{
		// user preference
		$user 	= false;
		$option = $this->addClassNamePrefix('advanced_mode');
		if (function_exists('\wp_get_current_user') && ($user = \wp_get_current_user()))
		{
			$option = $this->isTrue(\get_user_meta($user->ID,$option,true));
			$this->setAdvancedMode($option,'global');
			$this->setAdvancedMode($option,'settings');
		}

		// wp-config constant allows and overrides advanced mode
		$option = strtoupper($this->pluginName).'_ADVANCED_MODE';
		if ( defined( $option ) )
		{
			$option = $this->isTrue(constant($option));
			$this->allowAdvancedMode(true);
			$this->setAdvancedMode($option,'global');
			$this->setAdvancedMode($option,'settings');
		}

		// set user role(s)
		if ($user)
		{
			$roles = \wp_roles()->get_names();
			foreach ($roles as $role=>$name) {
				$is = in_array($role,$user->roles);
				foreach ($this->advanced_mode as $what => $mode) {
					$this->setAdvancedMode($is,$what,$role);
				}
			}
		}
		//echo "<div class='notice'><pre>advanced_mode ".var_export($this->advanced_mode,true)."</pre></div>";
	}


	/**
	 * allow advanced mode - aids in complexity and/or licensing limits.
	 * @example $this->allowAdvancedMode(false);
	 *
	 * @param bool $allow - allow or not
	 * @return bool - allowed or not
	 */
	public function allowAdvancedMode(bool $allow = null): bool
	{
		if (is_bool($allow))
		{
			$this->advanced_mode_allowed = $allow;
		}
		/**
		 * filter {classname}_allow_advanced_mode to set advanced mode
		 * @param string $allow - allow or not
		 * @return	bool
		 */
		$this->advanced_mode_allowed = $this->apply_filters('allow_advanced_mode',$this->advanced_mode_allowed);
		return $this->advanced_mode_allowed;
	}


	/**
	 * set advanced mode - aids in complexity and/or licensing limits.
	 * @example: $this->setAdvancedMode(true,'settings');
	 *
	 * @param bool $is - is or is not
	 * @param string $what - what is in advanced mode (global, settings, custom ...)
	 * @param string $level - what level is in advanced mode (default, basic, standard, pro)
	 * @return	void
	 */
	public function setAdvancedMode( $is = true, string $what = null, string $level = null): void
	{
		$what	= strtolower($what ?? 'global');
		$level	= ltrim(strtolower($level ?? 'default'),'-');
		$is		= $this->isTrue($is);

		$this->advanced_mode[$what][$level] = $is;

		if (! isset( $this->advanced_mode[$what]['default'] ))
		{
			$this->advanced_mode[$what]['default'] = ! $this->advanced_mode[$what][$level];
		}
	}


	/**
	 * is advanced mode - aids in complexity and/or licensing limits.
	 * @example $this->isAdvancedMode('settings');
	 * @example $this->isAdvancedMode('settings',['administrator','editor']);
	 *
	 * @param string $what - what is in advanced mode
	 * @param string|array $level - what level(s) is in advanced mode (default, basic, standard, pro)
	 * @return	bool - is or is not
	 */
	public function isAdvancedMode(string $what = null, string|array $level = null): bool
	{
		$what	= strtolower($what ?? 'global');

		if (is_array($level))
		{
			$is	= false;
			foreach ($level as $l) {
				$is = $is || $this->isAdvancedMode($what,$l);
			}
			return $is;
		}

		$level	= ltrim(strtolower($level ?? 'default'),'-');

		foreach ([$what,'global'] as $w)
		{
			foreach ([$level,'default'] as $l)
			{
				if (isset( $this->advanced_mode[$w], $this->advanced_mode[$w][$l] ))
				{
					return $this->advanced_mode[$w][$l] && $this->allowAdvancedMode();
				}
			}
		}
		return false;
	}


	/**
	 * is advanced mode filter - aids in complexity and/or licensing limits.
	 * @example $this->apply_filters('is_advanced_mode',false,'settings');
	 *
	 * @param bool $bool - ignored
	 * @param string $what - what is in advanced mode
	 * @param string $level - what level is in advanced mode (default, basic, standard, pro)
	 * @return	bool - is or is not
	 */
	public function is_advanced_mode(bool $bool = false, string $what = null, string $level = null): bool
	{
		return $this->isAdvancedMode($what, $level);
	}
}

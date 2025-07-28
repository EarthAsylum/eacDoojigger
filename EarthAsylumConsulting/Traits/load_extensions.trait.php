<?php
namespace EarthAsylumConsulting\Traits;

/**
 * load_extensions trait - {eac}Doojigger for WordPress
 *
 * Used to load all (required/internal/external/custom) extensions
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		25.0717.1
 */

trait load_extensions
{
	/**
	 * Called after instantiation of this class to load all extension classes.
	 * backend tracks extension versions and upgrades, frontend loads from transient
	 *
	 * @internal - called from plugin_loader
	 *
	 * extension directories...
	 *	{this directory}/Extensions			- built-in extensions
	 *	{plugin directory}/Extensions		- global extensions
	 *	{plugin directory}/{site-name}		- site extensions
	 * sub-directories...
	 *	'./frontend' or '/public'			- only load for front-end
	 *	'./backend' or ./admin;				- only load for back-end (admin)
	 *	'./network'							- only load for network back-end (admin)
	 *	any other sub-directory				- load *.extension.php files
	 *
	 * @return	void
	 */
	public function loadAllExtensions(): void
	{
		/**
		 * filter {classname}_required_extensions to get required extension directories
		 * @param	array	$extensionDirectories array of [plugin_slug => plugin_directory(s)]
		 * @return	array	updated $extensionDirectories
		*/
		$dirNames	= $this->apply_filters( 'required_extensions', [] );

		foreach ($dirNames as $slug => $directories)
		{
			// restricted to plugin sub-directories
			$pluginDir = $this->pluginHeader('PluginDir');
			$directories = array_filter($directories, function($dir) use($pluginDir)
				{
					return ( str_starts_with( $dir, $pluginDir ) );
				}
			);
			if ($this->loadExtensions( $slug, $directories ) == 0)
			{
				$this->fatal('required_extensions',
					$this->className . ' failed to load required extensions',
					[$slug=>$directories]
				);
			}
		}

		/**
		 * filter {classname}_allow_extensions to (dis)allow any extensions
		 * @param	bool $allow default = true
		 * @return	bool $allow
		 */
		if ( ! $this->apply_filters( 'allow_extensions', true ) ) return;

		/**
		 * filter {classname}_allow_internal_extensions to (dis)allow internal extensions
		 * @param	bool $allow default = true
		 * @return	bool $allow
		 */
		if ( $this->apply_filters( 'allow_internal_extensions', true ) )
		{
			$this->loadExtensions( $this->PLUGIN_SLUG, [] );
		}

		/**
		 * filter {classname}_allow_external_extensions to (dis)allow external extensions
		 * @param	bool $allow default = true
		 * @return	bool $allow
		 */
		if ( $limit = $this->apply_filters( 'allow_external_extensions', true ) )
		{
			$limit = (is_bool($limit)) ? 10000 : intval($limit);

			/**
			 * filter {classname}_load_extensions to get additional extension directories
			 * @param	array	$extensionDirectories array of [plugin_slug => plugin_directory(s)]
			 * @return	array	updated $extensionDirectories
			*/
			$dirNames	= $this->apply_filters( 'load_extensions', [] );

			// check theme directory
			foreach([ "Extensions", "Doolollys" ] as $themeType)
			{
				$themeDir = \get_stylesheet_directory() . "/{$this->className}/{$themeType}";
				if (is_dir($themeDir))
				{
					$dirNames[$this->toKeyString(\get_stylesheet().'-'.$themeType)] = [$themeDir];
				}
			}

			if ($limit)
			{
				foreach ($dirNames as $slug => $directories)
				{
					$limit -= $this->loadExtensions( $slug, $directories );
					// should we assume that all extension plugins are consent compliant?
					if ( class_exists( '\WP_CONSENT_API' ) )
					{
						if (is_file( WP_PLUGIN_DIR . '/' . $slug )
						&& !has_filter("wp_consent_api_registered_{$slug}"))
						{
							// declare compliance with WP Consent API
							add_filter( "wp_consent_api_registered_{$slug}", '__return_true' );
						}
					}
					if ($limit < 1) break;
				}
			}
		}
	}


	/**
	 * Load a set of extensions
	 *
	 * @param	string	$id - plugin slug (plugindir/pluginfile.php)
	 * @param	array	$source - the source directory(s)
	 * @return	int		count of extensions loaded
	 */
	private function loadExtensions(string $id, array $source): int
	{
		$count = 0;
		if ($this->is_frontend())
		{
			if (isset($this->extensionTransient[$id])) {
				// got extension list from transient (front-end)
				$extensions = $this->extensionTransient[$id];
				unset($this->extensionTransient[$id]);
			} else {
				// get extensions on disk, save to transient (front-end)
				$extensions = $this->loadExtensionsFromDisk($id,$source);
				$this->extensionTransient[$id] = $extensions;
				// abstract_frontend saves transient (when set)
			}
		}
		else
		{
			$extensions = $this->loadExtensionsFromDisk($id,$source);
		}

		foreach ($extensions as $slug => $dirs)
		{
			foreach ($dirs as $dir => $files)
			{
				$this->logInfo(str_replace(WP_PLUGIN_DIR,'...',$dir),__FUNCTION__." ({$slug})");

				foreach ($files as $extension)
				{
					$count += $this->loadExtension($extension);
				}
			}
		}
		return $count;
	}


	/**
	 * Load a single extension
	 *
	 * @param	string	$extension - full pathname of extension
	 * @return	int	 1=loaded
	 */
	private function loadExtension(string $extension): int
	{
		if (!file_exists($extension)) return 0;
		$object = include($extension);								// must return instantiated object when loaded

		if (!is_a($object,self::EXTENSION_BASE_CLASS)) {			// must be a extension class
			$this->logError(str_replace(WP_PLUGIN_DIR,'',$extension).' not a '.self::EXTENSION_BASE_CLASS,__METHOD__);
			return 0;
		}

		$className	= $object->getClassName();						// classname_extension (short name)
		$this->extension_objects[ $className ] = $object;

		$aliasName	= basename($className,'_extension');			// classname
		$aliasName	= basename($aliasName,'_doololly');				// classname
		if ($aliasName != $className ) {
			$this->extension_aliases[ $aliasName ] = $object;
		}
		if ($aliasName = $object->getAlias()) {						// aliasname
			$this->extension_aliases[ $aliasName ] = $object;
		}

		$extVersion = $object->getVersion() ?: 'unknown';
		if ($this->is_backend()) {									// abstract_backend
			$this->checkExtensionUpgrade($className, $extVersion);	// check for upgraded extension
		}
		if (!$object->isEnabled()) $extVersion .= ' (disabled)';	// disabled in constructor
		$this->logInfo('version '. $extVersion, $className);
		return 1;
	}


	/**
	 * get plugin extensions from directory.
	 * only backend or missing transient.
	 *
	 * @param	string	$id - default' - used in transient array
	 * @param	array	$source - the source directory(s)
	 * @return	array	slug => [dir => [extensions]]
	 */
	private function loadExtensionsFromDisk(string $id, array $source): array
	{
		if ( empty($source) && $id == $this->PLUGIN_SLUG )
		{
			$source = $this->defaultExtensionDirectories();
		}

		$dirNames	= $this->addExtensionSubDirectories( [ $id => $source ] );
		$fileTypes	= array('extension','doololly');					// <something>.extension.php
		$extensions = array();

		foreach ($dirNames as $slug => $dirs)
		{
			$extensions[ $slug ] = array();
			foreach ($dirs as $dir)
			{
				if (! $this->isLoadableExtension($dir) ) continue;	// hidden/disabled directory
				if ($files = glob($dir."*.{".implode(',',$fileTypes)."}.php",GLOB_BRACE))
				{
					$extensions[ $slug ][ $dir ] = array_filter($files, function($file) {
						return ($this->isLoadableExtension($file));
					});
				}
			}
		}

		return $extensions;
	}


	/**
	 * get default extensions directories
	 *
	 * @return	array	directories
	 */
	private function defaultExtensionDirectories(): array
	{
		$pluginDir 	= $this->pluginHeader('PluginDir');
		$vendorDir 	= $this->pluginHeader('VendorDir');
		$classDir 	= strtok(get_class($this), '\\');
		$blogDir 	= $this->toKeyString(\get_option('blogname'));

		$directories = array_unique([
			// default extensions - plugin-dir/extensions
			$pluginDir . '/Extensions',
			$pluginDir . '/Doolollys',
			// built-in vendor extensions - plugin-dir/vendor/extensions
			$vendorDir . '/Extensions',
			$vendorDir . '/Doolollys',
			// default extensions - plugin-dir/namespace/extensions
			$pluginDir . '/' . __NAMESPACE__ . '/Extensions',
			$pluginDir . '/' . __NAMESPACE__ . '/Doolollys',
			// class namespace may be different than this namespace, look for a corresponding folder
			$pluginDir . '/' . $classDir . '/Extensions',
			$pluginDir . '/' . $classDir . '/Doolollys',
			// custom site extensions - plugin-dir/site-name ('My WordPress Site' == 'my-wordpress-site')
			$pluginDir . '/' . $blogDir . '/Extensions',
			$pluginDir . '/' . $blogDir . '/Doolollys',
		]);
		return array_filter($directories, function($dir){return is_dir($dir);});
	}



	/**
	 * get sub-directories of $dirNames
	 *
	 * @param	array	$extensionDirectories array of [plugin_slug => plugin_directory(s)]
	 * @return	array	slug => [directories]
	 */
	private function addExtensionSubDirectories(array $dirNames): array
	{
		$result = array();
		foreach ($dirNames as $slug => $dirs)
		{
			if (! is_array($dirs) ) $dirs = array($dirs);
			foreach ($dirs as $dir)
			{
				if ( !is_dir($dir) ) continue;
				if ( strpos($dir, WP_PLUGIN_DIR) === false && strpos($dir, get_theme_root()) === false ) continue;
				$dir = trailingslashit($dir);
				$result[ $slug ][] = $dir;
				foreach (@glob($dir."*",GLOB_ONLYDIR) as $subDir)
				{
					$base = strtolower(basename(trim($subDir,DIRECTORY_SEPARATOR)));
					if ($base == 'admin' && !$this->is_backend()) continue;			// back-end only
					if ($base == 'backend' && !$this->is_backend()) continue;		// back-end only
					if ($base == 'public' && !$this->is_frontend()) continue;		// front-end only
					if ($base == 'frontend' && !$this->is_frontend()) continue;		// front-end only
					if ($base == 'network' && !$this->is_network_admin()) continue; // network admin only
					$result[ $slug ][] = trailingslashit($subDir);
				}
			}
		}
		return $result;
	}


	/**
	 * should we load this extension
	 *
	 * @param	string	$extension extension path name
	 * @return	bool
	 */
	private function isLoadableExtension(string $extension): bool
	{
		$name = basename($extension);											// something.extension.php or class.something.extension.php
		if ( in_array( $name[0], ['.','_','-'] ) ) return false;				// hidden/disabled extension/directory
		if ( is_dir($extension) ) return true;
		if (!is_file($extension)) return false;

		if ( $this->isPluginsPage() || $this->isSettingsPage() ) return true;	// always load for admin settings & plugins page

		/*
		 * Look for 'enable' setting based on file name.
		 * This only works when the extension file name contains the class name.
		 * classname.extension.php | class.classname.extension.php
		 */

		$className = explode('.',$name);
		if ($className[0] == 'class') array_shift($className);

		$optionNames = [];

		// get either saved (when registered) option name or default option name
		$optionNames[] =
				$this->get_option($className[0].'_enable_option_name',
				$this->get_option(basename($className[0],'_extension').'_enable_option_name',
				basename($this->toKeyString($className[0],'_'),'_extension')));

		if ($className[1] != 'extension' && $className[2] == 'extension')		// check for <something>.<somethingelse>.extension.php and use <somethingelse>
		{
			$optionNames[] =
				$this->get_option($className[1].'_enable_option_name',
				$this->get_option(basename($className[1],'_extension').'_enable_option_name',
				basename($this->toKeyString($className[1],'_'),'_extension')));
		}

		foreach ($optionNames as $optionName)
		{
			$enabled = $this->get_option( $optionName );						// (<classname>) [enable_option_name]
			if ($enabled !== false) return ($enabled != '');					// 'Enabled' or 'Enabled (admin)' or 'Network Enabled'

			$enabled = $this->get_option( $optionName.'_extension_enabled' );	// (<classname>_extension_enabled) - as set in abstract_extension
			if ($enabled !== false) return ($enabled != '');					// 'Enabled' or 'Enabled (admin)' or 'Network Enabled'
		}

		return true;															// default to enabled when no '_enabled' option found
	}
}

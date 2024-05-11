<?php
namespace EarthAsylumConsulting\Extensions;

/*
 * Wordpress installer extension using WP_Filesystem to install a file within the
 * WordPress directory structure by ftp/ssh access.
 *
 * Prmarily intended to install a "must use" plugin into the mu_plugins folder but can
 * easily be used for other purposes.
 *
 * example
 *		$myInstaller =
 *		[
 *			'title'			=> 'My Awesome MU Plugin',	// title, false=silent
 *			'sourcePath'	=> dirname(__FILE__),		// from this directory (defults to plugin dir)
 *			'sourceFile'	=> 'myawesomemu.class.php',	// source file to copy
 *			'targetPath'	=> WPMU_PLUGIN_DIR,			// destination folder (default to WPMU_PLUGIN_DIR)
 *			'targetFile'	=> 'myawesomemu.php',		// destination file (defaults to sourceFile)
 *			'connectForm'	=> true,					// allow automatic redirect to file system connection form
 *		];
 *
 *		$action = 'install'; // or 'update' or 'uninstall' or 'delete'
 *		$this->installer->invoke($action,[__METHOD__],$myInstaller);
 * or
 *		$this->installer->install([$this,__FUNCTION__],$myInstaller);
 * or
 *		$this->installer->uninstall(false,$myInstaller);
 * or
 *		$this->installer->enqueue($action,false,$myInstaller);
 *		$this->installer->enqueue($action,false,$myInstaller2);
 *		$this->installer->invoke();
 *
 * sourceFile may be a single file name or a wildcard to handle multiple files, in which case, targetFile is not used.
 *
 */


/**
 * Extension: wpmu_installer - install scripts/files using WP_Filesystem
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

class wpmu_installer extends \EarthAsylumConsulting\abstract_extension
{
	/**
	 * @var string extension version
	 */
	const VERSION				= '24.0416.1';

	/**
	 * @var string extension alias
	 */
	const ALIAS					= 'installer';

	/**
	 * @var string installer transient name
	 */
	const INSTALLER_TRANSIENT 	= 'wpmu_installer';

	/**
	 * @var bool can an install be allowwed
	 */
	private $can_install 		= false;


	/**
	 * constructor method
	 *
	 * @param 	object	$plugin main plugin object
	 * @return 	void
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin, self::ALLOW_ALL|self::ONLY_ADMIN);

		$this->can_install = ( $this->is_admin() && (!is_multisite() || $this->is_network_admin()) );

		if ($this->can_install)
		{
			$this->registerExtension( false );
			// check/restart enqueued installation(s) - with connection form
			\add_action('all_admin_notices', 			array( $this, 'wpmu_despool' ), 25 );
		}
		//$this->delete_site_transient(self::INSTALLER_TRANSIENT.'_lock');
		//$this->delete_site_transient(self::INSTALLER_TRANSIENT);
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

		/*
		 * invoke allows all arguments passed through to wpmu_invoke()
		 */
		$this->add_action( 'installer_invoke', 		array($this,'invoke'), 10, 4 );

		/*
		 * installer actions only support simple options (no method or callback)
		 */
		$this->add_action( 'installer_install', 	function($installOptions)
		{
			return $this->install(false,$installOptions);
		}, 10, 1);

		$this->add_action( 'installer_update', 		function($installOptions)
		{
			return $this->install(false,$installOptions);
		}, 10, 1);

		$this->add_action( 'installer_uninstall', 	function($installOptions)
		{
			return $this->install(false,$installOptions);
		}, 10, 1);

		$this->add_action( 'installer_delete', 		function($installOptions)
		{
			return $this->install(false,$installOptions);
		}, 10, 1);
	}


	/*
	 * The primary entry points to install/uninstall
	 *	->invoke(action,...), with shortcuts: ->install(...), ->update(...), ->uninstall(...), ->delete(...)
	 */


	/**
	 * invoke - with no arguments, start de-spooling the queue,
	 * 			with arguments, invoke installer
	 *
	 * @example - $this->installer->invoke($action,__METHOD__,$myInstaller);
	 *
	 * @param string		$installAction - install/update, uninstall/delete
	 * @param string|array|bool	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 *						must be a static method or false to use wpmu_installer::invoke (once queued)
	 *						this method is called when despooled after leaving the page for connection information
	 *						* this can almost always be FALSE (unless using $onSuccess or some pre/post processing)
	 * @param array 		$installOptions - (un)install options
	 *		'title'			=> the title shown to the user (what we're installing), false=silent
	 *		'sourcePath'	=> source folder path (plugin dir)
	 *		'sourceFile'	=> source file name or wildcard
	 *		'targetPath'	=> destination folder path (WPMU_PLUGIN_DIR)
	 *		'targetFile'	=> destination file name (sourceFile)
	 *		'connectForm'	=> (bool) allow automatic redirect to file system connection form
	 *							otherwise, show link to form (in admin notice)
	 *		optional values passed to *_wp_filesystem, no defaults
	 * 		'form_post'		=> $_SERVER['REQUEST_URI'] / $this->getSettingsUrl()
	 * 		'return_url'	=> redirect here after successful (un)install
	 * @param object|null	$onSuccess - callback on successful install,
	 * 		function($installAction,$installOptions) return false on failure
	 * @return bool 		success/failure
	 */
	public function invoke(): bool
	{
		$args = func_get_args();
		return (empty($args))
			? $this->wpmu_despool()
			: $this->wpmu_invoke(...$args);
	}


	/**
	 * install - installer invoke('install',...) shortcut
	 *
	 * @param string|array	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @param object|null	$onSuccess - callback on successful install,
	 * @return bool 		success/failure
	 */
	public function install($installMethod, array $installOptions, $onSuccess=null): bool
	{
		return $this->wpmu_invoke('install',$installMethod,$installOptions,$onSuccess);
	}


	/**
	 * update - installer invoke('update',...) shortcut
	 *
	 * @param string|array	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @param object|null	$onSuccess - callback on successful install,
	 * @return bool 		success/failure
	 */
	public function update($installMethod, array $installOptions, $onSuccess=null): bool
	{
		return $this->wpmu_invoke('update',$installMethod,$installOptions,$onSuccess);
	}


	/**
	 * uninstall - installer invoke('uninstall',...) shortcut
	 *
	 * @param string|array	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @param object|null	$onSuccess - callback on successful install,
	 * @return bool 		success/failure
	 */
	public function uninstall($installMethod, array $installOptions, $onSuccess=null): bool
	{
		return $this->wpmu_invoke('uninstall',$installMethod,$installOptions,$onSuccess);
	}


	/**
	 * delete - installer invoke('delete',...) shortcut
	 *
	 * @param string|array	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @param object|null	$onSuccess - callback on successful install,
	 * @return bool 		success/failure
	 */
	public function delete($installMethod, array $installOptions, $onSuccess=null): bool
	{
		return $this->wpmu_invoke('delete',$installMethod,$installOptions,$onSuccess);
	}


	/*
	 * Private methods, installs/uninstalls the plugin file(s)
	 */


	/**
	 * wpmu_invoke - run install/uninstall
	 *
	 * @param string		$installAction - install/update, uninstall/delete
	 * @param string|array	$installMethod - calling method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @param object|null	$onSuccess - callback on successful install
	 * @return bool 		success/failure
	 */
	private function wpmu_invoke(string $installAction, $installMethod, array $installOptions, $onSuccess=null): bool
	{
		if (!$this->can_install) return false;

		$installAction = strtolower($installAction);

		$opts = wp_parse_args($installOptions,[
			'title'			=> '',
			'sourcePath'	=> $this->pluginHeader('PluginDir'),
			'sourceFile'	=> '',
			'targetPath'	=> WPMU_PLUGIN_DIR,
			'targetFile'	=> '',
		]);

		if (empty($opts['targetFile'])) $opts['targetFile'] = $opts['sourceFile'];

		$installMethod  = $this->wpmu_method_string($installMethod);

		// if we're already waiting for an installer to run...
		if ($this->wpmu_lock($installMethod)) return false;
		// lock the installer when running
		$this->wpmu_lock($installMethod,true);

		/**
		 * action {className}_pre_install - before install action
		 * @param string		$installAction - install/update, uninstall/delete
		 * @param array 		$installOptions - (un)install options
		 * @return 	void
		 */
		$this->do_action('pre_install',$installAction,$opts);

		// push to top of queue so if we leave the page for credentials, we can restart
		$this->wpmu_enqueue($installAction,$installMethod,$opts);

		// if not set, default to true on settings page, else false
		if (!isset($opts['connectForm'])) $opts['connectForm'] = $this->isSettingsPage();

		// load WP_Filesystem - may leave the page to get credentials
		$message  = $opts['title'] ?: pathinfo($opts['targetFile'], PATHINFO_FILENAME);
		$message .= " {$installAction} requires WordPress file system access.";
		$fs = ($opts['connectForm']) 						// only show connection form...
			? $this->fs->load_wp_filesystem(true,$message,$opts)	// ...on our settings pages
			: $this->fs->link_wp_filesystem(true,$message,$opts);	// ...when user clicks link

		if (!$fs) return false;

		$result = $this->wpmu_action($fs, $installAction, $opts, $onSuccess);

		// remove from queue
		$this->wpmu_dequeue($installMethod);

		/**
		 * action {className}_post_install - after install action
		 * @param string		$installAction - install/update, uninstall/delete
		 * @param array 		$installOptions - (un)install options
		 * @param 	bool 		$result - result of install
		 * @return 	void
		 */
		$this->do_action('post_install',$installAction,$opts,$result);

		// unlock the installer
		$this->wpmu_lock($installMethod,false);

		if ($result && ($returnUrl = ($opts['return_url'] ?? false)))
		{
			$this->page_redirect($returnUrl,302,true);
		}

		$this->wpmu_despool(); // next action?

		return $result;
	}


	/**
	 * wpmu_action - run install/uninstall action
	 *
	 * @param object		$fs - WP_Filesystem object
	 * @param string		$action - install/uninstall
	 * @param array 		$opts - installOptions
	 * @param object|null	$onSuccess - callback on successful install,
	 * @return bool 		success/failure
	 */
	private function wpmu_action(object $fs, string $action, array $opts, $onSuccess=null): bool
	{
		// validate/find target & source folders
		$opts['targetPath'] = ABSPATH . str_replace(ABSPATH,'',$opts['targetPath']);
		$opts['sourcePath'] = ABSPATH . str_replace(ABSPATH,'',$opts['sourcePath']);
		$opts['originPath'] = trailingslashit($opts['sourcePath']);
		$targetBase = basename($opts['targetPath']);

		if (
			(! $opts['targetPath'] = $fs->find_folder(dirname($opts['targetPath']))) ||
			(! $opts['sourcePath'] = $fs->find_folder($opts['sourcePath']))
		) {
			$this->wpmu_admin_notice($opts['title'],"%s {$action} error.",'error',
				'Unable to locate installation folder(s).');
			return false;
		}
		$opts['targetPath'] .= trailingslashit($targetBase);

		$actioned = rtrim($action,'e').'ed';

		switch ($action)
		{
			case 'install':
			case 'update':

				if ( ! $fs->is_dir( $opts['targetPath'] ) )
				{
					$fs->mkdir($opts['targetPath'],FS_CHMOD_DIR/*,$this->filesystem_owner,$this->filesystem_group*/);
				}
				if ($this->wpmu_copy($fs, $opts))
				{
					if ( (is_callable($onSuccess)) &&
						 (call_user_func($onSuccess,$action,$opts) === false)
					) {
						$fs->delete($opts['targetPath'].$opts['targetFile']);
						$this->wpmu_admin_notice($opts['title'],"%s could not be {$actioned}.",'error',
							"Could not configure the {$opts['targetFile']} file.");
						return false;
					}
					else
					{
						$fs->touch($opts['targetPath'].$opts['targetFile']); // set modification time
						$this->wpmu_admin_notice($opts['title'],"%s has been {$actioned}.",'success');
						return true;
					}
				}
				$this->wpmu_admin_notice($opts['title'],"%s could not be {$actioned}.",'error',
					"Please make sure we have write access to the '{$targetBase}' folder.");
				return false;

			case 'uninstall':
			case 'delete':

				if ($this->wpmu_delete($fs, $opts))
				{
					if ( (is_callable($onSuccess)) &&
						 (call_user_func($onSuccess,$action,$opts) === false)
					) {
						$this->wpmu_admin_notice($opts['title'],"%s {$action} was not completed.",'warning');
						return false;
					}
					else
					{
						$this->wpmu_admin_notice($opts['title'],"%s has been {$actioned}.",'success');
						return true;
					}
				}
				$this->wpmu_admin_notice($opts['title'],"%s could not be {$actioned}.",'error',
					"The file(s) do not exist or could not be deleted.");
				return false;
		}

		return false;
	}


	/**
	 * wpmu_copy - copy file(s) from sourcePath to targetPath
	 *
	 * @param object		$fs - WP_Filesystem object
	 * @param array 		$opts - installOptions
	 * @return int|bool 	success/failure
	 */
	private function wpmu_copy(object $fs, array $opts)
	{
		if (empty($opts['sourceFile'])) return false;

		// copy a single file to targetFile
		if (file_exists($opts['originPath'].$opts['sourceFile']))
		{
			$files = $fs->copy(
					$opts['sourcePath'].$opts['sourceFile'],
					$opts['targetPath'].$opts['targetFile'],
					true,
					FS_CHMOD_FILE);
		}
		// copy multiple files to targetPath
		else if ($files = glob($opts['originPath'].$opts['sourceFile']))
		{
			foreach ($files as $file)
			{
				if (!$fs->copy(
						$opts['sourcePath'].basename($file),
						$opts['targetPath'].basename($file),
						true,
						FS_CHMOD_FILE)
				) {
					$this->wpmu_delete($fs,$action,$opts);
					return false;
				}
			}
		}
		return (int) $files;
	}


	/**
	 * wpmu_delete - delete file(s) from targetPath
	 *
	 * @param object		$fs - WP_Filesystem object
	 * @param array 		$opts - installOptions
	 * @return int|bool 	success/failure
	 */
	private function wpmu_delete(object $fs, array $opts)
	{
		if (empty($opts['targetFile'])) return false;

		// delete a single file
		if ($fs->exists($opts['targetPath'].$opts['targetFile']))
		{
			$files = $fs->delete($opts['targetPath'].$opts['targetFile']);
		}
		// delete multiple files
		else if ($files = glob($opts['originPath'].$opts['sourceFile']))
		{
			foreach ($files as $file)
			{
				if ($fs->exists($opts['targetPath'].basename($file)))
				{
					if (!$fs->delete($opts['targetPath'].basename($file)))
					{
						return false;
					}
				}
			}
		}
		if (dirname($opts['targetPath']) != $fs->wp_content_dir()
		&&  dirname($opts['targetPath']) != dirname($fs->wp_content_dir()) )
		{
			$fs->rmdir($opts['targetPath']);	// only if empty
		}
		return (int) $files;
	}


	/*
	 * Utility entry points
	 *	->enqueue(...), ->dequeue(...), ->isQueued(...)
	 */


	/**
	 * enqueue (append) an installer action
	 *
	 * @param string		$installAction - install/uninstall
	 * @param string|array	$installMethod - installer method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @return object		$this
	 */
	public function enqueue(string $installAction, $installMethod, array $installOptions = [])
	{
		if (!$this->can_install) return false;
		$installMethod  = $this->wpmu_method_string($installMethod);
		$wpmu_installer = $this->wpmu_dequeue($installMethod,true);
	//	$wpmu_installer = $this->get_site_transient(self::INSTALLER_TRANSIENT,[]);
		$wpmu_installer[] = [ $installAction, $installMethod, $installOptions ];

		$this->set_site_transient(self::INSTALLER_TRANSIENT,$wpmu_installer,HOUR_IN_SECONDS);
		return $this;
	}


	/**
	 * dequeue (remove) an installer action
	 *
	 * @param string		$installAction - install/uninstall
	 * @param string|array	$installMethod - installer method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options (not used)
	 * @return object		$this
	 */
	public function dequeue(string $installAction, $installMethod, array $installOptions = [])
	{
		if (!$this->can_install) return false;
		$installMethod  = $this->wpmu_method_string($installMethod);
		$this->wpmu_dequeue($installMethod);
		return $this;
	}


	/**
	 * isQueued is an installer action queued
	 *
	 * @param string		$installAction - install/uninstall
	 * @param string|array	$installMethod - installer method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options (not used)
	 * @return bool
	 */
	public function isQueue(string $installAction, $installMethod, array $installOptions = []): object
	{
		$installMethod  = $this->wpmu_method_string($installMethod);
		$wpmu_installer = $this->get_site_transient(self::INSTALLER_TRANSIENT,[]);
		$wpmu_installer = array_filter($wpmu_installer, function($installer) use($installMethod)
			{
				return ($installer[1] == $installMethod);
			}
		);
		return (!empty($wpmu_installer));
	}


	/*
	 * Private/internal methods
	 */


	/**
	 * push an installer action to the top of the queue
	 *
	 * @param string		$installAction - install/uninstall
	 * @param string|array	$installMethod - installer method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @param array 		$installOptions - (un)install options
	 * @return string		$installMethod
	 */
	private function wpmu_enqueue(string $installAction, $installMethod, array $installOptions = []): string
	{
		$wpmu_installer = $this->wpmu_dequeue($installMethod,true);
	//	$wpmu_installer = $this->get_site_transient(self::INSTALLER_TRANSIENT,[]);
		array_unshift($wpmu_installer,[ $installAction,$installMethod,$installOptions ]);

		$this->set_site_transient(self::INSTALLER_TRANSIENT,$wpmu_installer,HOUR_IN_SECONDS);
		return $installMethod;
	}


	/**
	 * remove completed (or duplicate) installer action from the queue
	 *
	 * @param string		$installMethod - installer method name
	 * @param string		$getTransient - return (don't save) transient
	 * @return string|array	$installMethod or installer transient
	 */
	private function wpmu_dequeue(string $installMethod, bool $getTransient=false)
	{
		$wpmu_installer = $this->get_site_transient(self::INSTALLER_TRANSIENT,[]);
		$wpmu_installer = array_filter($wpmu_installer, function($installer) use($installMethod)
			{
				return !($installer[1] == $installMethod);
			}
		);
		if ($getTransient) return $wpmu_installer;

		if (empty($wpmu_installer))
		{
			$this->delete_site_transient(self::INSTALLER_TRANSIENT);
		}
		else
		{
			$this->set_site_transient(self::INSTALLER_TRANSIENT,$wpmu_installer,HOUR_IN_SECONDS);
		}
		return $installMethod;
	}


	/**
	 * lock/unlock installer when active/idle
	 *
	 * @param string	$installMethod
	 * @param bool|null $lock lock/unlock
	 * @return	bool
	 */
	private function wpmu_lock(string $installMethod,bool $lock=null): bool
	{
		if (is_null($lock))
		{
			$locked = $this->get_site_transient(self::INSTALLER_TRANSIENT.'_lock');
			return ($locked && $locked != $installMethod);
		}

		if ($lock)
		{
			return $this->set_site_transient(self::INSTALLER_TRANSIENT.'_lock',$installMethod,HOUR_IN_SECONDS);
		}
		else
		{
			return $this->delete_site_transient(self::INSTALLER_TRANSIENT.'_lock');
		}
	}


	/**
	 * check transient for queued installer action and run
	 * (all_admin_notices action and invoke() method)
	 *
	 * @return	bool
	 */
	public function wpmu_despool(): bool
	{
		if ($wpmu_installer = $this->get_site_transient(self::INSTALLER_TRANSIENT))
		{
			$installOptions = array_shift($wpmu_installer);
			$installMethod 	= $installOptions[1];
			if (empty($wpmu_installer))
			{
				$this->delete_site_transient(self::INSTALLER_TRANSIENT);
			}
			else
			{
				$this->set_site_transient(self::INSTALLER_TRANSIENT,$wpmu_installer,HOUR_IN_SECONDS);
			}
			// callable static method/function
			if (is_callable($installMethod))
			{
				try {
					call_user_func_array($installMethod, $installOptions);
					return true;
				} catch (\Throwable $e) {}
			}
			// try a plugin extension method
			$installMethod 		= explode('::',$installMethod); // may split to 3 values (w/uniqud())
			$installMethod[0] 	= basename(str_replace('\\','/',$installMethod[0]));
			$this->callMethod([$installMethod[0],$installMethod[1]],...$installOptions);
			return true;
		}
		return false;
	}


	/**
	 * transform the installer method to a string (class_name :: function_name [, :: uniqid()])
	 * this becomes the defacto key for this installer action
	 *
	 * @param string|array	$installMethod - installer method name (__METHOD__, or [__CLASS__,__FUNCTION__])
	 * @return string		$installMethod
	 */
	private function wpmu_method_string( $installMethod ): string
	{
		return (is_callable($installMethod, true, $installMethod))
			? $installMethod
			: uniqid(__CLASS__.'::invoke::',true); // make it unique
	}


	/**
	 * display 'admin_notices' message
	 *
	 * @param	string	$title the installer title
	 * @param	string	$message (kses'd when displayed)
	 * @param	string	$type (error,warning,info,success)
	 * @param	string	$more additional info
	 * @return	void
	 */
	private function wpmu_admin_notice($title,$message,$type='notice',$more='')
	{
		if ($title)
		{
			$this->add_admin_notice(sprintf($message,$title),$type,$more);
		}
	}
}
/**
* return a new instance of this class
*/
if (isset($this)) return new wpmu_installer($this);
?>

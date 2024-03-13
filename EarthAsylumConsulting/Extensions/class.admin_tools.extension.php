<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\admin_tools_extension', false) )
{
	/**
	 * Extension: admin_tools - tools/utility functions - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2022 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class admin_tools_extension extends \EarthAsylumConsulting\abstract_extension
	{
	 	use \EarthAsylumConsulting\Traits\standard_options;

		/**
		 * @var string extension version
		 */
		const VERSION	= '23.0915.1';


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			$this->enable_option = false;
			parent::__construct($plugin, self::ALLOW_ADMIN|self::ALLOW_NETWORK|self::ONLY_ADMIN);

			if ($this->is_admin())
			{
				$this->registerExtension( ['administration_tools', 'Tools'] );

				// used by optionExport in standard_options trait to process export url (admin_post)
				$this->standard_options('optionExport_action');

				// Register plugin options when needed
				$this->add_action( "options_settings_page", 	array($this, 'admin_options_settings') );
			}
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			$this->registerExtensionOptions( 'administration_tools',
				$this->standard_options(['clearCache','backupOptions','restoreOptions','optionExport','optionImport','noSubmit'])
			);

			if ( $this->plugin->is_network_admin() )
			{
				$this->registerExtensionOptions( 'network_administration',
					$this->standard_options(['networkCache','backupNetwork','restoreNetwork'])
				);
			}

			if ( $this->isAdvancedMode('settings') && (!is_multisite() || $this->plugin->is_network_admin()) )
			{
				$this->registerExtensionOptions( 'software_updates',
					$this->standard_options(['updateChannel','checkForUpdates'])
				);
			}
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new admin_tools_extension($this);
?>

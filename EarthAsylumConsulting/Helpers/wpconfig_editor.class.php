<?php
namespace EarthAsylumConsulting\Helpers;

require_once('vendor/wp-config-transformer-main/src/WPConfigTransformer.php');

/**
 * wpconfig editor class - uses WPConfigTransformer to edit wp-config.php using WP_Filesystem
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Helpers
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		1.x
 */
class wpconfig_editor extends \WPConfigTransformer
{
	/**
	 * @var string version
	 */
	const VERSION				= '23.1013.1';

	/**
	 * @var object WP_Filesystem
	 */
	private $fs;


	/**
	 * constructor method
	 *
	 * @param string $wp_config_path Path to a wp-config.php file.
	 * @return 	void
	 */
	public function __construct( $wp_config_path )
	{
		global $wp_filesystem;

		if (!isset($wp_filesystem)) {
			throw new Exception( "wp_filesystem is not defined." );
		}

		$this->fs = $wp_filesystem;

		/* from WPConfigTransformer changing to $this->fs... */
		$basename = basename( $wp_config_path );

		if ( ! $this->fs->exists( $wp_config_path ) ) {
			throw new Exception( "{$basename} does not exist." );
		}

		if ( ! $this->fs->is_writable( $wp_config_path ) ) {
			throw new Exception( "{$basename} is not writable." );
		}

		$this->wp_config_path = $wp_config_path;
	}


	/**
	 * Directly copied from WPConfigTransformer
	 * changing only file_put_contents() to $this->fs->put_contents()
	 *
	 * Saves new contents to the wp-config.php file.
	 *
	 * @throws Exception If the config file content provided is empty.
	 * @throws Exception If there is a failure when saving the wp-config.php file.
	 *
	 * @param string $contents New config contents.
	 *
	 * @return bool
	 */
	protected function save( $contents ) {
		if ( ! trim( $contents ) ) {
			throw new Exception( 'Cannot save the config file with empty contents.' );
		}

		if ( $contents === $this->wp_config_src ) {
			return false;
		}

		//$result = file_put_contents( $this->wp_config_path, $contents, LOCK_EX );
		$result = $this->fs->put_contents( $this->wp_config_path, $contents, FS_CHMOD_FILE & ~0007 );

		if ( false === $result ) {
			throw new Exception( 'Failed to update the config file.' );
		}

		return true;
	}
}

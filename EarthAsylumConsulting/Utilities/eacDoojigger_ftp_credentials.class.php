<?php
namespace EarthAsylumConsulting;
use \EarthAsylumConsulting\Extensions\encryption_extension as encryption;

/**
 * Extension: file_system - expands on the WordPress WP_filesystem - {eac}Doojigger for WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger Utilities\{eac}Doojigger_ftp_credentials
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version: 	1.2.0
 */

/*
 * {eac}DoojiggerAutoloader file system - {eac}Doojigger for WordPress,
 *
 * Part of the eacDoojigger file_system extension and
 * included in the eacDoojigger auto-loader.
 *
 * We have these 'ftp_credentials' filter here so that they are active
 * when WordPress runs core updates with no plugins loaded (except "must use").
 */


class eacDoojigger_ftp_credentials
{
	/**
	 * set filters needed to get & save ftp_credentials
	 * override WP credentials by encrypting/decrypting and storing complete credentials
	 */
	public static function addFilters()
	{
		// store encrypted credentials when WordPress updates 'ftp_credentials' option.
		add_filter( 'pre_update_option_ftp_credentials', 	__CLASS__.'::set_ftp_credentials', 10, 3 );
		// get complete credentials when WordPress gets 'ftp_credentials' default values
		add_filter( 'default_option_ftp_credentials', 		__CLASS__.'::get_ftp_credentials', 10, 2 );
		// get complete credentials when WordPress gets 'ftp_credentials'
		add_filter( 'option_ftp_credentials', 				__CLASS__.'::get_ftp_credentials', 10, 2 );
	}


	/**
	 * set extended saved ftp_credentials (on pre_update_option_ftp_credentials).
	 * includes all available fields, encrypted when stored.
	 *
	 * @param array $value new 'ftp_credentials' array
	 * @param array $old_value old 'ftp_credentials' array
	 * @param string $option 'ftp_credentials'
	 * @return array $value
	 */
	public static function set_ftp_credentials($value,$old_value,$option)
	{
		$credentials = $value;
		// modified from request_filesystem_credentials() to get & save additional credentials
		$submitted_form = wp_unslash( $_POST );
		$ftp_constants = array( // not included by WordPress
			'password'    => 'FTP_PASS',
			'public_key'  => 'FTP_PUBKEY',
			'private_key' => 'FTP_PRIKEY',
		);
		foreach ( $ftp_constants as $key => $constant ) {
			if ( defined( $constant ) ) {
				$credentials[ $key ] = constant( $constant );
			} elseif ( isset( $submitted_form[ $key ] ) ) {
				$credentials[ $key ] = $submitted_form[ $key ];
			} elseif ( ! empty( $old_value[ $key ] ) ) {
				$credentials[ $key ] = $old_value[ $key ];
			} elseif ( ! isset( $credentials[ $key ] ) ) {
				$credentials[ $key ] = '';
			}
		}

		$shared = get_option('eacDoojigger_filesystem_multisite',
				  get_site_option('eacDoojigger_filesystem_multisite','Enabled'));
		$credentials = encryption::encode(maybe_serialize($credentials),null,$shared);

		if ($credentials && !is_wp_error($credentials))
		{
			if ($shared)
				update_site_option('eacDoojigger_filesystem_credentials',$credentials);
			else
				update_option('eacDoojigger_filesystem_credentials',$credentials);
		}

		return $value;
	}


	/**
	 * get saved ftp_credentials (on option_ftp_credentials).
	 * override WP credentials after decrypting stored credentials
	 *
	 * @param array $ftp_credentials current 'ftp_credentials' array
	 * @param string $option 'ftp_credentials'
	 * @return array updated 'ftp_credentials' array
	 */
	public static function get_ftp_credentials($ftp_credentials,$option)
	{
		$shared = get_option('eacDoojigger_filesystem_multisite',
				  get_site_option('eacDoojigger_filesystem_multisite','Enabled'));
		$credentials = ($shared)
			? get_site_option('eacDoojigger_filesystem_credentials')
			: get_option('eacDoojigger_filesystem_credentials');

		if ($credentials)
		{
			$credentials = encryption::decode($credentials,$shared);
		}

		if ($credentials && !is_wp_error($credentials))
		{
			$credentials = array_merge(maybe_unserialize($credentials),$ftp_credentials ?: []);
			return $credentials;
		}

		return $ftp_credentials;
	}
}

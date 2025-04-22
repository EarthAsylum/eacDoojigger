<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\encryption_extension', false) )
{
	/**
	 * Extension: encryption - string encryption/decryption - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class encryption_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '25.0419.1';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 * 		false 		disable the 'Enabled'' option for this group
		 * 		string 		the label for the 'Enabled' option
		 * 		array 		override options for the 'Enabled' option (label,help,title,info, etc.)
		 */
		const ENABLE_OPTION	= false;

		/**
		 * @var array key(s)
		 */
		private static $key = null;

		/**
		 * @var string salt
		 */
		private static $salt = null;

		/**
		 * @var string cipher
		 */
		private static $cipher = null;

		/**
		 * @var string cipher
		 */
		private static $option_prefix = 'eacDoojigger';

		/**
		 * @var object this
		 */
		private static $instance = null;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL);

			if (!function_exists('openssl_encrypt'))
			{
				return $this->isEnabled(false);
			}

			self::$instance = $this;
			self::$option_prefix = $this->pluginName;

			// store as separate option keys
			$this->isReservedOption('encryption_key',true);
			$this->isReservedOption('encryption_salt',true);
			$this->isReservedOption('encryption_cipher',true);

			$this->registerExtension();
			add_action('admin_init', function()
			{
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			});
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			$this->plugin->rename_option('encryptionKey',	'encryption_key');
			$this->plugin->rename_option('encryptionSalt',	'encryption_salt');
			$this->registerExtensionOptions( true,
				[
					'encryption_key'	=> array(
							'type'		=> 	'password',
							'label'		=> 	'Encryption Key',
							'default'	=> 	defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : wp_generate_password( 32, true, true ),
							'info'		=> 	'The encryption key, combined with encryption salt, used to create an OpenSSL digest key.',
							'attributes'=> 	['autocomplete'=>'new-password'],
							'class'		=> 	'input-readonly', // like readonly field
							'sanitize'	=> 	false,
							'validate'	=> 	[$this,'validate_encryption_key'],
							'advanced'	=> 	true,
					),
					'encryption_salt'	=> array(
					//		'type'		=> 	'readonly',
							'type'		=> 	'hidden',
							'label'		=> 	'Encryption Salt',
							'default'	=> 	defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : wp_generate_password( 32, true, true ),
							'sanitize'	=> 	false,
					//		'info'		=> 	'Once in use, this key should not be changed as previously encrypted values will be lost.',
					//		'attributes'=> 	['autocomplete'=>'new-password'],
							'advanced'	=> 	true,
					),
					'encryption_cipher'	=> array(
							'type'		=> 	'select',
							'label'		=> 	'Encryption Cipher',
							'options' 	=> 	['AES-256'=>'256','AES-192'=>'192','AES-128'=>'128'],
							'default'	=> 	'128',
							'info'		=> 	'AES-256 is the most secure and most resource intensive. '.
											'AES-128 is less resource intensive yet highly secure and suitable for most environments.',
							'advanced'	=> 	true,
					),
				]
			);
		}


		/**
		 * Add help tab on admin page
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
			if (!$this->plugin->isSettingsPage('General')) return;
			include 'includes/encryption.help.php';
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled

			/* add  filters early */

			/**
			 * filter {className}_encrypt_string - encrypt a string
			 * @param 	string 	value to be encrypted
			 * @param 	int 	cipher bit size (256, 192, 128) optional
			 * @param 	bool 	$site - use site keys
			 * @return 	string encrypted and base64 encoded string
			 */
			$this->add_filter( 'encrypt_string', 		array(__CLASS__,'encode'), 10, 3 );

			/**
			 * filter {className}_decrypt_string - decrypt a string
			 * @param 	string encrypted and base64 encoded string
			 * @param 	bool 	$site - use site keys
			 * @return 	string decrypted value
			 */
			$this->add_filter( 'decrypt_string', 		array(__CLASS__,'decode'), 10, 2 );

			/**
			 * filter {className}_site_encrypt_string - encrypt a string
			 * @param 	string 	value to be encrypted
			 * @param 	int 	cipher bit size (256, 192, 128) optional
			 * @return 	string encrypted and base64 encoded string
			 */
			$this->add_filter( 'site_encrypt_string', 	function($data,$bits=null)
			{
				return static::encode($data,$bits,true);
			}, 10, 2 );

			/**
			 * filter {className}_site_decrypt_string - decrypt a string
			 * @param 	string encrypted and base64 encoded string
			 * @return 	string decrypted value
			 */
			$this->add_filter( 'site_decrypt_string', 	function($data)
			{
				return static::decode($data,true);
			}, 10, 1 );
		}


		/**
		 * get key/salt
		 *
		 * @param 	bool 	$site - use site keys when truthy
		 * @return 	void
		 */
		public static function setKeys($site=false)
		{
			if ($site)
			{
				self::$key 		= get_site_option(self::$option_prefix.'_encryption_key');
				self::$salt 	= get_site_option(self::$option_prefix.'_encryption_salt');
			}
			else
			{
				self::$key 		= get_option(self::$option_prefix.'_encryption_key',self::$option_prefix.'_encryption_key');
				self::$salt 	= get_option(self::$option_prefix.'_encryption_salt');
			}
			if ( ! is_array(self::$key)) self::$key = array(self::$key);
		}


		/**
		 * encryption key validation
		 *
		 * @param 	string	$value - the value POSTed
		 * @param 	string	$fieldName - the name of the field/option
		 * @param 	array	$metaData - the option metadata
		 * @return 	mixed
		 */
		public function validate_encryption_key($value, $fieldName, $metaData, $priorValue)
		{
			// retain unique list of current and prior keys
			$value = ( ! empty($value) ) ? array($value) : array();
			if ( ! empty($priorValue) )
			{
				if ( ! is_array($priorValue) ) $priorValue = array($priorValue);
				$priorValue = array_diff($priorValue, $value);
				if ( ! empty($priorValue) ) $value = array_merge($value,$priorValue);
			}
			return $value;
		}


		/*
		 * Points of entry
		 */


		/**
		 * the string is returned base 64 encoded (for transport)
		 *
		 * @param 	string 	$data - data to be encrypted
		 * @param 	int 	$bits - cipher bit size (256, 192, 128)
		 * @param 	bool 	$site - use site keys
		 * @return 	string | WP_Error
		 */
		public static function encode($data,$bits=null,$site=false)
		{
			$data = self::encrypt($data,$bits,$site);
			return ( ! is_wp_error($data) ) ? base64_encode($data) : $data;
		}


		/**
		 * encrypt the string
		 *
		 * @param 	string 	$data - data to be encrypted
		 * @param 	int 	$bits - cipher bit size (256, 192, 128)
		 * @param 	bool 	$site - use site keys
		 * @return 	string | WP_Error
		 */
		public static function encrypt($data,$bits=null,$site=false)
		{
			self::setKeys($site);
			$bits = $bits ?: get_option(self::$option_prefix.'_encryption_cipher','128');
			self::$cipher = 'aes-' . $bits . '-gcm'; 					// 'aes-256-gcm';

			return (!in_array(self::$cipher, openssl_get_cipher_methods()))
				? self::encrypt_v2($data)								// fallback
				: self::encrypt_v3($data,$bits);						// current version
		}


		/**
		 * the string was base64 encoded (for transport)
		 *
		 * @param 	string 	$encrypted_string - data to be decrypted
		 * @param 	bool 	$site - use site keys
		 * @return 	string | WP_Error
		 */
		public static function decode($encrypted_string,$site=false)
		{
			if ($encrypted_string = base64_decode($encrypted_string))
			{
				return self::decrypt($encrypted_string,$site);
			}
			return self::openssl_error(__FUNCTION__, 'base64_decode failure');
		}


		/**
		 * decrypt the string
		 *
		 * @param 	string 	$encrypted_string - data to be decrypted
		 * @param 	bool 	$site - use site keys
		 * @return 	string | WP_Error
		 */
		public static function decrypt($encrypted_string,$site=false)
		{
			self::setKeys($site);
			// first version had no version indicator
			$version = ( $encrypted_string[0] != 'V' || !is_numeric($encrypted_string[1]) )
				? 'V1'
				: substr($encrypted_string,0,2);

			switch ($version)
			{
				case 'V3':
					return self::decrypt_v3($encrypted_string);
				case 'V2':
					return self::decrypt_v2($encrypted_string);
				case 'V1':
					return self::decrypt_v1($encrypted_string);
			}
			return self::openssl_error(__FUNCTION__, 'unknown or invalid encryption version '.$version);
		}


		/*
		 * Encrypt/Decrypt version 3
		 * uses gcm cipher with OpenSSL authentication tag
		 */


		/**
		 * encrypt the string
		 *
		 * @param 	string 	$data - data to be encrypted
		 * @param 	int 	$bits - cipher bit size (256, 192, 128)
		 * @return 	string
		 */
		private static function encrypt_v3($data,$bits)
		{
			$ivSize	= openssl_cipher_iv_length(self::$cipher);
			$iv		= openssl_random_pseudo_bytes($ivSize);
			$key 	= self::encryptionKey(self::$key[0],$bits);		// encrypt with current key
			$tag	= null;

			$data = openssl_encrypt(
					$data,
					self::$cipher,
					$key,
					OPENSSL_RAW_DATA,
					$iv,		// random Initialization Vector
					$tag,		// &$tag
					"",			// additional auth data
					8			// tag length
			);
			if ($data === false)
			{
				return self::openssl_error(__FUNCTION__, 'encryption failed');
			}

			return 'V3' . $bits . $iv . $data . $tag;
		}


		/**
		 * decrypt the string
		 *
		 * @param 	string 	$encrypted_string - data to be decrypted
		 * @return 	string
		 */
		private static function decrypt_v3($encrypted_string)
		{
			$bits 	= substr($encrypted_string,2,3);				// Recover cipher bits
			self::$cipher = 'aes-' . $bits . '-gcm'; 				// 'aes-256-gcm';

			$ivSize	= openssl_cipher_iv_length(self::$cipher);
			$iv 	= substr($encrypted_string, 5, $ivSize);		// Recover the initialization vector
			$tag	= substr($encrypted_string, -8);				// recover the tag
			$data	= substr($encrypted_string, 5+$ivSize, -8);		// recover the data

			foreach (self::$key as $key)							// decrypt trying all keys
			{
				$key 	= self::encryptionKey($key,$bits);
				$result = openssl_decrypt(
						$data,
						self::$cipher,
						$key,
						OPENSSL_RAW_DATA,
						$iv,
						$tag
				);
				if ( $result !== false ) return $result;
			}
			return self::openssl_error(__FUNCTION__, 'decryption failed');
		}


		/*
		 * Encrypt/Decrypt version 2
		 * uses ctr cipher with authentication hash at the end of the string before encryption
		 */


		/**
		 * encrypt the string using version 2
		 *
		 * @param 	string 	$data - data to be encrypted
		 * @return 	string
		 */
		private static function encrypt_v2($data)
		{
			self::$cipher 	= 'aes-256-ctr';

			$ivSize	= openssl_cipher_iv_length(self::$cipher);
			$iv		= openssl_random_pseudo_bytes($ivSize);
			$data 	.= self::hash($data); 							// 8-byte checksum appended to data

			$key 	= self::encryptionKey(self::$key[0]);			// encrypt with current key
			$data = openssl_encrypt(
					$data,
					self::$cipher,
					$key,
					OPENSSL_RAW_DATA,
					$iv
			);
			if ($data === false)
			{
				return self::openssl_error(__FUNCTION__, 'encryption failed');
			}

			return 'V2' . $iv . $data;
		}


		/**
		 * decrypt the string using version 2 of encrypt/decrypt logic
		 *
		 * @param 	string 	$encrypted_string - data to be decrypted
		 * @return 	string
		 */
		private static function decrypt_v2($encrypted_string)
		{
			self::$cipher 	= 'aes-256-ctr';

			$ivSize	= openssl_cipher_iv_length(self::$cipher);
			$iv 	= substr($encrypted_string, 2, $ivSize);		// Recover the initialization vector
			$data	= substr($encrypted_string, 2+ $ivSize);		// recover the data

			foreach (self::$key as $key)							// decrypt trying all keys
			{
				$key 	= self::encryptionKey($key);
				$result = openssl_decrypt(
						$data,
						self::$cipher,
						$key,
						OPENSSL_RAW_DATA,
						$iv
				);
				if ( $result !== false ) break;
			}

			if ( $result === false )
			{
				return self::openssl_error(__FUNCTION__, 'decryption failed');
			}

			$hashinp	= substr($result,-8);						// recover the checksum
			$result 	= substr($result,0,-8);
			$hashout	= self::hash($result);						// calculate checksum
			if ( $hashinp !== $hashout )
			{
				return self::openssl_error(__FUNCTION__, 'cyclic redundancy check failed');
			}

			return $result;
		}


		/*
		 * Decrypt version 1
		 * uses ctr cipher with authentication hash at the end of the string after encryption
		 */


		/**
		 * encrypt the string using version 1
		 *
		 * @param 	string 	$data - data to be encrypted
		 * @return 	string
		 */
		private static function encrypt_v1($data)
		{
			return self::openssl_error(__FUNCTION__, 'no longer supported');
		}


		/**
		 * decrypt the string using version 1 of encrypt/decrypt logic
		 *
		 * @param 	string 	$encrypted_string - data to be decrypte
		 * @return 	string
		 */
		private static function decrypt_v1($encrypted_string)
		{
			self::$cipher 	= 'aes-256-ctr';

			$ivSize		= openssl_cipher_iv_length(self::$cipher);
			$data 		= $encrypted_string;
			$iv 		= substr($data, 0, $ivSize);				// Recover the initialization vector
			$hashinp	= substr($data,-8);							// recover the checksum
			$data		= substr($data, $ivSize, -8);				// recover the data
			$hashout	= hash("crc32b",$data);						// calculate checksum
			if ( $hashinp !== $hashout )
			{
				return self::openssl_error(__FUNCTION__, 'cyclic redundancy check failed');
			}

			foreach (self::$key as $key)							// decrypt trying all keys
			{
				$key 	= self::encryptionKey($key);
				$result = openssl_decrypt(
						$data,
						self::$cipher,
						$key,
						OPENSSL_RAW_DATA,
						$iv
				);
				if ( $result !== false ) return $result;
			}
			return self::openssl_error(__FUNCTION__, 'decryption failed');
		}


		/*
		 * Private/internal methods
		 */


		/**
		 * set encryption key using openssl_digest with key/salt
		 *
		 * @param 	string 	$encryption_key - encryption key
		 * @param 	int 	$bits - cipher bits
		 * @return 	string
		 */
		private static function encryptionKey($encryption_key,$bits=256)
		{
			$key 		= openssl_digest(self::$salt.$encryption_key,'sha256');
			$keySize 	= ($bits > 0) ? $bits / 8 : 32;
			return substr($key, 0, $keySize);
		}


		/**
		 * openssl error
		 *
		 * @param 	string 	$code (__FUNCTION__)
		 * @param 	string 	$msg error message
		 * @return 	string	$message
		 */
		private static function openssl_error(string $code, string $msg, array $data=[])
		{
			$msg = trim( self::$cipher . ' ' . __($msg) );
			if (self::$instance)
			{
				while ($err = openssl_error_string()) {$data[] = $err;}
				self::$instance->add_admin_notice($msg,'error');
				return self::$instance->error($code, $msg, $data);
			}
			else
			{
				throw new Exception($msg);
			}
		}


		/**
		 * calculate hash/cyclic redundancy check (V1 & V2)
		 *
		 * @param 	string 	data
		 * @param 	bool 	as binary
		 * @return 	string
		 */
		private static function hash($string,$binary=false)
		{
			return hash("crc32b",$string,$binary);
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new encryption_extension($this);
?>

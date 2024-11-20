<?php
namespace EarthAsylumConsulting\Helpers;

/**
 * Utility to check IPv4 or IPv6 address to a list of addresses or networks (using CIDR)
 *
 * Parts derived from
 * 		Cloudflare https://github.com/cloudflare/Cloudflare-WordPress/
 * 		Symphony https://github.com/symfony/http-foundation/blob/7.1/IpUtils.php
 *
 * @example \EarthAsylumConsulting\Helpers\ipUtil::checkIp($myIP,$listOfIPs);
 * @example $remote_ip = \EarthAsylumConsulting\Helpers\ipUtil::getRemoteIP();
 */
class ipUtil
{
	/**
	 * @var array private subnets
	 */
	public const PRIVATE_SUBNETS = [
		'127.0.0.0/8',	  // RFC1700 (Loopback)
		'10.0.0.0/8',	  // RFC1918
		'192.168.0.0/16', // RFC1918
		'172.16.0.0/12',  // RFC1918
		'169.254.0.0/16', // RFC3927
		'0.0.0.0/8',	  // RFC5735
		'240.0.0.0/4',	  // RFC1112
		'::1/128',		  // Loopback
		'fc00::/7',		  // Unique Local Address
		'fe80::/10',	  // Link Local Address
		'::ffff:0:0/96',  // IPv4 translations
		'::/128',		  // Unspecified address
	];

	/**
	 * @var array http headers containing remote IP address
	 */
	public const HTTP_IP_HEADERS = [
		'HTTP_X_REAL_IP',
		'HTTP_CF_CONNECTING_IP',
		'HTTP_AKAMAI_ORIGIN_HOP',
		'HTTP_FASTLY_CLIENT_IP',
		'HTTP_INCAP_CLIENT_IP',
		'HTTP_TRUE_CLIENT_IP',
		'HTTP_X_IP_TRAIL',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED',
		'HTTP_CLIENT_IP',
		'REMOTE_ADDR',
	];

	/**
	 * @var array remote IP address (once obtained)
	 */
	private static $remote_ip = null;


	/**
	 * This class should not be instantiated.
	 */
	private function __construct()
	{
	}

	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
	 *
	 * @param string	   $requestIp	IP to check
	 * @param string|array $ipList		List of IPs or subnets (can be a string if only a single one)
	 *
	 * @return bool Whether the IP is valid
	 */
	public static function checkIp(string $requestIp, $ipList): bool
	{
		if (!is_array($ipList)) {
			$ipList = array($ipList);
		}

		$method = self::isIpv4($requestIp) ? 'checkIp4' : 'checkIp6';

		foreach ($ipList as $ip) {
			if (self::$method($requestIp, $ip)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the ip is v4.
	 *
	 * @param string $ip IP to check
	 * @param bool $validate validate with filter_var
	 *
	 * @return bool return true if ipv4
	 */
	public static function isIpv4(string $ip, bool $validate=false): bool
	{
		return ($validate)
			? filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
			: ! str_contains($ip,':');
	}

	/**
	 * Checks if the ip is v6.
	 *
	 * @param string $ip IP to check
	 * @param bool $validate validate with filter_var
	 *
	 * @return bool return true if ipv6
	 */
	public static function isIpv6(string $ip, bool $validate=false): bool
	{
		return ($validate)
			? filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
			: str_contains($ip,':');
	}

	/**
	 * Compares two IPv4 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $requestIp IPv4 address to check
	 * @param string $ip		IPv4 address or subnet in CIDR notation
	 *
	 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
	 */
	public static function checkIp4(string $requestIp, string $ip): bool
	{
		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask === '0') {
				// Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
				return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}

			if ($netmask < 0 || $netmask > 32) {
				return false;
			}
			$netmask = (int)$netmask;
		} else {
			$address = $ip;
			$netmask = 32;
		}

		return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
	}

	/**
	 * Compares two IPv6 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * author David Soria Parra <dsp at php dot net>
	 *
	 * @see https://github.com/dsp/v6tools
	 *
	 * @param string $requestIp IPv6 address to check
	 * @param string $ip		IPv6 address or subnet in CIDR notation
	 *
	 * @return bool Whether the IP is valid
	 *
	 * @throws \RuntimeException When IPV6 support is not enabled
	 */
	public static function checkIp6(string $requestIp, string $ip): bool
	{
		if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
			throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
		}

		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask < 1 || $netmask > 128) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}

		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIp));

		if (!$bytesAddr || !$bytesTest) {
			return false;
		}

		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xffff >> $left) & 0xffff;
			if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of private IP subnets.
	 */
	public static function isPrivateIp(string $requestIp): bool
	{
		return self::checkIp($requestIp, self::PRIVATE_SUBNETS);
	}


	/**
	 * Get the request IP address
	 *
	 * @return	string	IP address or null
	 */
	public static function getRemoteIP(): ?string
	{
		if (self::$remote_ip) return self::$remote_ip;

		foreach(self::HTTP_IP_HEADERS as $header)
		{
			if ( isset($_SERVER[$header]) )
			{
				// may have multiple addresses (forwards)
				$addr = array_filter(
					array_map('trim', explode("\n", str_replace([',',';',' '],"\n",$_SERVER[$header]) ) )
				);
				foreach($addr as self::$remote_ip) {
					self::$remote_ip = \filter_var(
									self::$remote_ip,
									FILTER_VALIDATE_IP,
									FILTER_FLAG_IPV4|FILTER_FLAG_IPV6
					);
					if (!empty(self::$remote_ip)) return self::$remote_ip; // got it
				}
			}
		}
		return null;
	}
}

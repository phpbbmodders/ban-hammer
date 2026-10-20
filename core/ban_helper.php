<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\core;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Adds bans the right way for the running phpBB version.
*
* phpBB 3.3 bans through user_ban(). phpBB 4.0 replaced the ban system with
* the ban.manager service: user_ban() is only a deprecated wrapper there
* (to be removed in 4.1) and takes one argument fewer, so calling it with
* 3.3's arguments would store the wrong value as the ban reason.
*/
class ban_helper
{
	/** @var ContainerInterface */
	protected $container;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string phpEx */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param ContainerInterface	$container	Service container
	* @param string				$root_path	phpBB root path
	* @param string				$php_ext	PHP file extension
	* @access public
	*/
	public function __construct(ContainerInterface $container, $root_path, $php_ext)
	{
		$this->container	= $container;
		$this->root_path	= $root_path;
		$this->php_ext		= $php_ext;
	}

	/**
	* Whether this board runs phpBB 4.0's ban system.
	*
	* @return bool
	* @access public
	*/
	public function has_ban_manager()
	{
		return $this->container->has('ban.manager');
	}

	/**
	* Ban one username, email address (wildcards allowed) or IP address.
	*
	* @param string	$mode			One of 'user', 'email' or 'ip'
	* @param string	$item			The username, email address or IP address
	* @param int	$minutes		Ban length in minutes, 0 for a permanent ban
	* @param string	$reason			Reason shown to staff
	* @param string	$display_reason	Reason shown to the banned user
	* @return bool True if the ban was added
	* @access public
	*/
	public function ban($mode, $item, $minutes, $reason, $display_reason = '')
	{
		if (!$this->has_ban_manager())
		{
			if (!function_exists('user_ban'))
			{
				include($this->root_path . 'includes/functions_user.' . $this->php_ext);
			}

			return (bool) user_ban($mode, $item, (int) $minutes, '', false, $reason, $display_reason);
		}

		$now = time();
		$start = new \DateTime();
		$start->setTimestamp($now);
		// The ban manager treats an end timestamp of 0 as a permanent ban.
		$end = new \DateTime();
		$end->setTimestamp(($minutes > 0) ? $now + ((int) $minutes * 60) : 0);

		try
		{
			/** @var \phpbb\ban\manager $ban_manager */
			$ban_manager = $this->container->get('ban.manager');

			return $ban_manager->ban($mode, array($item), $start, $end, $reason, $display_reason);
		}
		catch (\phpbb\exception\runtime_exception $e)
		{
			// Nothing valid to ban, for example an excluded user or a
			// malformed email address: report it as a failed ban, the same
			// as user_ban() returning false on 3.3.
			return false;
		}
	}
}

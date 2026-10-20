<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\cron\task;

/**
* Moves users back out of the Ban Hammer group once their ban is over.
*
* On phpBB 3.3 this also happens on the user's next visit, through the
* core.session_set_custom_ban event. phpBB 4.0 only fires that event for
* users who are still banned, so there this task is what restores them; on
* 3.3 it also covers users who never come back.
*/
class ban_group_restore extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbbmodders\banhammer\core\ban_group_restore */
	protected $ban_group_restore;

	/**
	* Constructor
	*
	* @param \phpbb\config\config								$config				Config object
	* @param \phpbbmodders\banhammer\core\ban_group_restore	$ban_group_restore	Group-move restore helper
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbbmodders\banhammer\core\ban_group_restore $ban_group_restore)
	{
		$this->config				= $config;
		$this->ban_group_restore	= $ban_group_restore;
	}

	/**
	* Restore every tracked user who is no longer banned.
	*
	* @return void
	* @access public
	*/
	public function run()
	{
		$this->config->set('bh_ban_group_last_run', time(), false);

		$this->ban_group_restore->restore_unbanned();
	}

	/**
	* {@inheritdoc}
	*/
	public function is_runnable()
	{
		return true;
	}

	/**
	* At most once every five minutes, same as restriction_expiry: restoring
	* a group a little late is harmless and this should not run on every
	* page load.
	*
	* {@inheritdoc}
	*/
	public function should_run()
	{
		return (int) $this->config['bh_ban_group_last_run'] < (time() - 300);
	}
}

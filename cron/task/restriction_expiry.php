<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2026 phpBB Modders <https://phpbbmodders.net/>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\banhammer\cron\task;

/**
* Restores a restricted user's original group once their restriction period
* ends. Not a ban, so phpBB's own ban-expiry handling never sees these users,
* this is the extension's own equivalent for the "restrict instead" action.
*/
class restriction_expiry extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $restrict_table;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string phpEx */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param \phpbb\config\config				$config			Config object
	* @param \phpbb\db\driver\driver_interface	$db				Database object
	* @param string								$restrict_table	Restriction tracking table
	* @param string								$root_path		phpBB root path
	* @param string								$php_ext		PHP file extension
	* @access public
	*/
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		$restrict_table,
		$root_path,
		$php_ext
	)
	{
		$this->config			= $config;
		$this->db				= $db;
		$this->restrict_table	= $restrict_table;
		$this->root_path		= $root_path;
		$this->php_ext			= $php_ext;
	}

	/**
	* Restore every restriction whose time has passed.
	*
	* @return void
	* @access public
	*/
	public function run()
	{
		$this->config->set('bh_restrict_last_run', time(), false);

		$sql = 'SELECT restrict_id, user_id, original_group_id
			FROM ' . $this->restrict_table . '
			WHERE restrict_until > 0
				AND restrict_until <= ' . time();
		$result = $this->db->sql_query($sql);

		$expired = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$expired[] = $row;
		}
		$this->db->sql_freeresult($result);

		if (empty($expired))
		{
			return;
		}

		if (!function_exists('group_user_add') || !function_exists('group_user_del'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$restrict_group_id = (int) $this->config['bh_restrict_group_id'];

		foreach ($expired as $row)
		{
			$user_id = (int) $row['user_id'];
			$original_group_id = (int) $row['original_group_id'];

			if ($restrict_group_id)
			{
				group_user_del($restrict_group_id, array($user_id));
			}

			if ($original_group_id)
			{
				group_user_add($original_group_id, array($user_id), false, false, true);
			}

			$this->db->sql_query('DELETE FROM ' . $this->restrict_table . ' WHERE restrict_id = ' . (int) $row['restrict_id']);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function is_runnable()
	{
		return true;
	}

	/**
	* At most once every five minutes, restoring a group a little late is
	* harmless and this should not run on every page load.
	*
	* {@inheritdoc}
	*/
	public function should_run()
	{
		return (int) $this->config['bh_restrict_last_run'] < (time() - 300);
	}
}

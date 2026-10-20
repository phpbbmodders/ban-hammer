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

/**
* Once a ban is over, undoes whatever group move that specific ban made -
* not "the group currently configured in the ACP", which may have changed
* since, or matter to some other, unrelated group membership entirely.
*
* Called from two places: the core.session_set_custom_ban listener, which
* on phpBB 3.3 runs on every ban check, banned or not; and the
* ban_group_restore cron task, needed because phpBB 4.0 only fires that
* event for users who are still banned, and has no event at all when a ban
* is removed or runs out.
*/
class ban_group_restore
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string phpEx */
	protected $php_ext;

	/** @var string */
	protected $restrict_table;

	/** @var string */
	protected $ban_group_table;

	/**
	* Constructor
	*
	* @param \phpbb\db\driver\driver_interface	$db					Database object
	* @param string								$root_path			phpBB root path
	* @param string								$php_ext			PHP file extension
	* @param string								$restrict_table		Restriction tracking table
	* @param string								$ban_group_table	Ban group-move tracking table
	* @access public
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, $root_path, $php_ext, $restrict_table, $ban_group_table)
	{
		$this->db				= $db;
		$this->root_path		= $root_path;
		$this->php_ext			= $php_ext;
		$this->restrict_table	= $restrict_table;
		$this->ban_group_table	= $ban_group_table;
	}

	/**
	* Undo the group move for one user. The caller must already know the
	* user is no longer banned.
	*
	* @param int $user_id
	* @return void
	* @access public
	*/
	public function restore_user($user_id)
	{
		$sql = 'SELECT ban_id, user_id, original_group_id, move_group_id, move_new_membership
			FROM ' . $this->ban_group_table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->restore_row($row);
		}
	}

	/**
	* Undo the group move for every tracked user who is no longer banned,
	* whether their ban was removed or ran out.
	*
	* @return void
	* @access public
	*/
	public function restore_unbanned()
	{
		$sql = 'SELECT ban_id, user_id, original_group_id, move_group_id, move_new_membership
			FROM ' . $this->ban_group_table;
		$result = $this->db->sql_query($sql);

		$rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[(int) $row['user_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		if (empty($rows))
		{
			return;
		}

		$banned = $this->banned_user_ids(array_keys($rows));

		foreach ($rows as $user_id => $row)
		{
			if (!isset($banned[$user_id]))
			{
				$this->restore_row($row);
			}
		}
	}

	/**
	* Which of the given users are still banned.
	*
	* phpbb_get_banned_user_ids() covers username bans, but not wildcard
	* email bans: phpBB 3.3's checks username bans only, and 4.0's ban
	* manager matches email bans by exact address. So also count active
	* email bans matching the user's email address, with * as a wildcard,
	* the way phpBB's own ban check matches them.
	*
	* IP bans are left out on purpose: the only IP stored for a user is the
	* one they registered from, not where they visit from now, so it can't
	* show they are still banned. A user whose username ban is over is
	* restored even if their old IP is still banned; phpBB's own IP check
	* keeps blocking that address regardless of group.
	*
	* @param array $user_ids
	* @return array Banned user ids as keys
	* @access protected
	*/
	protected function banned_user_ids(array $user_ids)
	{
		if (!function_exists('phpbb_get_banned_user_ids'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$banned = array_flip(array_map('intval', phpbb_get_banned_user_ids($user_ids)));

		// phpBB 4.0 keeps one row per banned item, typed by ban_mode; 3.3
		// keeps the email in its own column.
		if (defined('BANS_TABLE'))
		{
			$sql = 'SELECT ban_item AS ban_email
				FROM ' . BANS_TABLE . "
				WHERE ban_mode = 'email'
					AND (ban_end = 0 OR ban_end > " . time() . ')';
		}
		else
		{
			$sql = 'SELECT ban_email
				FROM ' . BANLIST_TABLE . "
				WHERE ban_exclude = 0
					AND ban_email <> ''
					AND (ban_end = 0 OR ban_end > " . time() . ')';
		}
		$result = $this->db->sql_query($sql);

		$email_patterns = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$email_patterns[] = $this->ban_regex($row['ban_email']);
		}
		$this->db->sql_freeresult($result);

		if (empty($email_patterns))
		{
			return $banned;
		}

		$sql = 'SELECT user_id, user_email
			FROM ' . USERS_TABLE . '
			WHERE ' . $this->db->sql_in_set('user_id', array_map('intval', $user_ids));
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($this->matches_any($email_patterns, $row['user_email']))
			{
				$banned[(int) $row['user_id']] = true;
			}
		}
		$this->db->sql_freeresult($result);

		return $banned;
	}

	/**
	* Whether a value matches any of the given ban patterns.
	*
	* @param array	$patterns	Regular expressions from ban_regex()
	* @param string	$value		Email address
	* @return bool
	* @access protected
	*/
	protected function matches_any(array $patterns, $value)
	{
		if ($value === '')
		{
			return false;
		}

		foreach ($patterns as $pattern)
		{
			if (preg_match($pattern, $value))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* An email ban as a regular expression, with * as a wildcard - the same
	* matching phpBB's own ban check uses (3.3's session::check_ban(), 4.0's
	* ban manager).
	*
	* @param string $pattern
	* @return string
	* @access protected
	*/
	protected function ban_regex($pattern)
	{
		return '#^' . str_replace('\\*', '.*?', preg_quote($pattern, '#')) . '$#i';
	}

	/**
	* Undo one tracked group move and drop its tracking row.
	*
	* @param array $row A row from the ban group-move tracking table
	* @return void
	* @access protected
	*/
	protected function restore_row(array $row)
	{
		if (!function_exists('group_user_del') || !function_exists('group_user_attributes'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$user_id = (int) $row['user_id'];
		$move_group_id = (int) $row['move_group_id'];

		if ($move_group_id && $row['move_new_membership'])
		{
			// A restriction can be configured to use the same group as a
			// ban's move-to group. If this user also has an active
			// restriction recorded against this exact group, leave their
			// membership alone - it's still needed for the restriction,
			// checked against its own recorded group, not current config.
			$sql = 'SELECT restrict_group_id
				FROM ' . $this->restrict_table . '
				WHERE user_id = ' . $user_id;
			$result = $this->db->sql_query_limit($sql, 1);
			$restrict_group_id = (int) $this->db->sql_fetchfield('restrict_group_id');
			$this->db->sql_freeresult($result);

			if ($restrict_group_id !== $move_group_id)
			{
				group_user_del($move_group_id, array($user_id));
			}
		}

		if ($row['original_group_id'])
		{
			group_user_attributes('default', (int) $row['original_group_id'], array($user_id));
		}

		$this->db->sql_query('DELETE FROM ' . $this->ban_group_table . ' WHERE ban_id = ' . (int) $row['ban_id']);
	}
}

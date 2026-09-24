<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\migrations;

/**
 * undo_bh_group() used to compare a banned user's group membership against
 * the *current* bh_group_id ACP setting, with no record of what a given ban
 * actually did. Changing that setting (or disabling group-moving) while a
 * ban is still active strands the user in whatever group they were actually
 * moved into; conversely, an unrelated legitimate member of the *currently*
 * configured group could have their membership stripped on their next
 * non-banned session check, even though ban-hammer never added them there.
 * This table gives bans the same per-action tracking the restrict feature
 * already has (see restrict_group.php and friends), including the unique
 * index from the start this time - restrict_group.php's own retrofit needed
 * a whole separate dedupe migration to add one after the fact.
 */
class ban_group extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'banhammer_ban_group');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\restrict_membership_column');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'banhammer_ban_group' => array(
					'COLUMNS' => array(
						'ban_id'				=> array('UINT', null, 'auto_increment'),
						'user_id'				=> array('UINT', 0),
						'original_group_id'	=> array('UINT', 0),
						'move_group_id'			=> array('UINT', 0),
						'move_new_membership'	=> array('BOOL', 0),
					),
					'PRIMARY_KEY'	=> 'ban_id',
					'KEYS'			=> array(
						'user_id'	=> array('UNIQUE', 'user_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'banhammer_ban_group',
			),
		);
	}

	public function update_data()
	{
		return array(
			// A user already banned-and-moved under the old code has no
			// tracking row (the table didn't exist yet), so undo_bh_group()
			// would never clean them up once unbanned. Back-fill one for
			// anyone currently banned and a member of the currently
			// configured move-to group.
			array('custom', array(array($this, 'backfill_active_bans'))),
		);
	}

	/**
	 * @return void
	 * @access public
	 */
	public function backfill_active_bans()
	{
		$move_group_id = (int) $this->config['bh_group_id'];

		if (!$move_group_id)
		{
			return;
		}

		// There's no record of what these users' default group was before
		// being banned (original_group_id = 0, so undo_bh_group() won't
		// attempt to restore one), and no way to tell whether the ban
		// itself put them in this group or they already belonged for an
		// unrelated reason - conservatively assume the latter
		// (move_new_membership = 0), same reasoning as
		// restrict_membership_column's own legacy default: leaving a
		// genuinely ban-created membership in place is a much smaller
		// problem than stripping one the ban never granted.
		$sql = 'SELECT DISTINCT ug.user_id
			FROM ' . USER_GROUP_TABLE . ' ug, ' . BANLIST_TABLE . ' b
			WHERE ug.group_id = ' . $move_group_id . '
				AND b.ban_userid = ug.user_id
				AND b.ban_exclude = 0
				AND (b.ban_end = 0 OR b.ban_end > ' . time() . ')';
		$result = $this->db->sql_query($sql);

		$sql_ary = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$sql_ary[] = array(
				'user_id'				=> (int) $row['user_id'],
				'original_group_id'	=> 0,
				'move_group_id'			=> $move_group_id,
				'move_new_membership'	=> 0,
			);
		}
		$this->db->sql_freeresult($result);

		if (!empty($sql_ary))
		{
			$this->db->sql_multi_insert($this->table_prefix . 'banhammer_ban_group', $sql_ary);
		}
	}

	public function revert_data()
	{
		return array(
			// A purge is about to drop this tracking. Restore anyone still
			// actively tracked now, rather than stranding them in whatever
			// group a ban moved them into with no way back.
			array('custom', array(array($this, 'restore_active_ban_groups'))),
		);
	}

	/**
	 * @return void
	 * @access public
	 */
	public function restore_active_ban_groups()
	{
		if (!function_exists('group_user_del') || !function_exists('group_user_attributes'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$sql = 'SELECT user_id, original_group_id, move_group_id, move_new_membership
			FROM ' . $this->table_prefix . 'banhammer_ban_group';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_id = (int) $row['user_id'];
			$move_group_id = (int) $row['move_group_id'];
			$original_group_id = (int) $row['original_group_id'];

			if ($move_group_id && $row['move_new_membership'])
			{
				group_user_del($move_group_id, array($user_id));
			}

			if ($original_group_id)
			{
				group_user_attributes('default', $original_group_id, array($user_id));
			}
		}
		$this->db->sql_freeresult($result);

		$this->sql_query('DELETE FROM ' . $this->table_prefix . 'banhammer_ban_group');
	}
}

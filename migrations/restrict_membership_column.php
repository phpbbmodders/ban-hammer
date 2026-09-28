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
 * do_restrict_stuff() can find the target already belongs to the restrict
 * group for an unrelated, legitimate reason (group_user_add() returns
 * GROUP_USERS_EXIST). Without recording that, expiry and purge couldn't
 * tell "this restriction created the membership" from "the user already had
 * it", and would remove it either way - stripping a membership the
 * restriction never granted in the first place. This column records which
 * case applied, per restriction, at the point it was created.
 */
class restrict_membership_column extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'banhammer_restrict', 'restrict_new_membership');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\restrict_unique_user');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					// Existing rows predate this column. Before this fix's
					// own group_user_add() result check existed, the
					// tracking row was inserted unconditionally regardless
					// of whether the user was already a member - so a
					// legacy row could represent either case, and there's
					// no way to tell which after the fact. Default to 0
					// (don't remove membership): leaving a genuinely
					// ban-hammer-created membership in place a little too
					// long is a much smaller problem than stripping a
					// membership this restriction never granted.
					'restrict_new_membership' => array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'restrict_new_membership',
				),
			),
		);
	}

	public function revert_data()
	{
		return array(
			// A purge is about to drop this tracking (this column, then the
			// whole table once earlier migrations revert next - migrations
			// revert newest-first, so this is the last point the full,
			// per-row data is still available). Restore anyone still
			// actively restricted now, rather than stranding them
			// mid-restriction with no way back. This lives here rather than
			// in the older restrict_group_id_column migration because that
			// one reverts after this one, by which point
			// restrict_new_membership would already be gone.
			array('custom', array(array($this, 'restore_active_restrictions'))),
		);
	}

	/**
	 * @return void
	 * @access public
	 */
	public function restore_active_restrictions()
	{
		if (!function_exists('group_user_del') || !function_exists('group_user_attributes'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$sql = 'SELECT user_id, original_group_id, restrict_group_id, restrict_new_membership
			FROM ' . $this->table_prefix . 'banhammer_restrict';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_id = (int) $row['user_id'];
			$restrict_group_id = (int) $row['restrict_group_id'];
			$original_group_id = (int) $row['original_group_id'];

			// Only remove membership this restriction actually created -
			// see restrict_new_membership's own docs above.
			if ($restrict_group_id && $row['restrict_new_membership'])
			{
				group_user_del($restrict_group_id, array($user_id));
			}

			if ($original_group_id)
			{
				group_user_attributes('default', $original_group_id, array($user_id));
			}
		}
		$this->db->sql_freeresult($result);

		$this->sql_query('DELETE FROM ' . $this->table_prefix . 'banhammer_restrict');
	}
}

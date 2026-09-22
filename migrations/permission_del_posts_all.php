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
 * "Delete users posts" was, until now, an implicit side effect of m_ban:
 * any moderator who could ban could also wipe the target's posts across
 * every forum, including ones they have no delete permission in. Making
 * that its own permission keeps the current behaviour for anyone who
 * already has m_ban (copy_from below), but as an explicit, independently
 * revocable grant going forward instead of a hidden side effect of m_ban.
 */
class permission_del_posts_all extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT auth_option_id
			FROM ' . ACL_OPTIONS_TABLE . "
			WHERE auth_option = 'm_banhammer_del_posts_all'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\restrict_group_id_column');
	}

	public function update_data()
	{
		return array(
			array('permission.add', array('m_banhammer_del_posts_all', true, 'm_ban')),
		);
	}

	public function revert_data()
	{
		return array(
			array('permission.remove', array('m_banhammer_del_posts_all', true)),
		);
	}
}

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
 * A concurrent-restriction race (fixed in do_restrict_stuff(), see
 * restrict_unique_user.php) could leave more than one tracking row for the
 * same user_id on a site that hit it before upgrading. restrict_unique_user
 * adds a unique index on user_id right after this migration; creating that
 * index would fail outright against any such leftover duplicates. Since
 * phpBB applies a migration's own update_schema() before its update_data(),
 * the cleanup has to happen here, in a migration that runs first, not inside
 * restrict_unique_user itself.
 */
class restrict_dedupe extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\permission_del_posts_all');
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'dedupe_restrictions'))),
		);
	}

	/**
	 * Keep only the most recently created tracking row per user_id,
	 * discarding any others. Which one "actually" won the original race is
	 * unrecoverable at this point; keeping the newest is a reasonable,
	 * simple choice, and every real install should have zero duplicates to
	 * begin with.
	 *
	 * @return void
	 * @access public
	 */
	public function dedupe_restrictions()
	{
		$sql = 'SELECT user_id, MAX(restrict_id) as keep_id
			FROM ' . $this->table_prefix . 'banhammer_restrict
			GROUP BY user_id
			HAVING COUNT(*) > 1';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . 'banhammer_restrict
				WHERE user_id = ' . (int) $row['user_id'] . '
					AND restrict_id <> ' . (int) $row['keep_id'];
			$this->sql_query($sql);
		}
		$this->db->sql_freeresult($result);
	}
}

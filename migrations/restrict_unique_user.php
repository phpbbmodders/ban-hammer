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
 * restrict_group.php's own user_id index is a plain (non-unique) index,
 * which let two concurrent restriction requests for the same user both
 * pass the "already restricted?" check and insert their own tracking row.
 * A unique index turns the loser's insert into a caught error instead of
 * a second, silently conflicting row - see do_restrict_stuff().
 */
class restrict_unique_user extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_unique_index_exists($this->table_prefix . 'banhammer_restrict', 'user_id');
	}

	static public function depends_on()
	{
		// restrict_dedupe removes any leftover duplicate user_id rows from
		// the concurrent-restriction race this index closes off; without
		// running first, creating a unique index over pre-existing
		// duplicates would fail outright.
		return array('\phpbbmodders\banhammer\migrations\restrict_dedupe');
	}

	public function update_schema()
	{
		return array(
			'drop_keys' => array(
				$this->table_prefix . 'banhammer_restrict' => array('user_id'),
			),
			'add_unique_index' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'user_id' => array('user_id'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_keys' => array(
				$this->table_prefix . 'banhammer_restrict' => array('user_id'),
			),
			'add_index' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'user_id' => array('user_id'),
				),
			),
		);
	}
}

<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\banhammer\acp;

class banhammer_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $request, $template, $user, $config;

		$user->add_lang('acp/groups');

		$this->page_title = $user->lang['ACP_BH_TITLE'];
		$this->tpl_name = 'banhammer_body';

		add_form_key('banhammer');

		if ($request->is_set_post('submit'))
		{
			// Test if form key is valid
			if (!check_form_key('banhammer'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$this->set_options();
			trigger_error($user->lang['SETTINGS_SUCCESS'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'BAN_EMAIL'		=> (!empty($config['bh_ban_email'])) ? true : false,
			'BAN_IP'		=> (!empty($config['bh_ban_ip'])) ? true : false,
			'DEL_AVATAR'	=> (!empty($config['bh_del_avatar'])) ? true : false,
			'DEL_PRIVMSGS'	=> (!empty($config['bh_del_privmsgs'])) ? true : false,
			'DEL_POSTS'		=> (!empty($config['bh_del_posts'])) ? true : false,
			'DEL_PROFILE'	=> (!empty($config['bh_del_profile'])) ? true : false,
			'DEL_SIGNATURE'	=> (!empty($config['bh_del_signature'])) ? true : false,
			'MOVE_GROUP'	=> $this->get_groups($request->variable('move_group', $config['bh_group_id'])),
			'SFS_API_KEY'	=> (!empty($config['bh_sfs_api_key'])) ? $config['bh_sfs_api_key'] : '',
			'SFS_CURL'		=> (function_exists('curl_init')) ? true : false,
		));
	}

	/**
	 * Set the options a user can configure
	 *
	 * @return null
	 * @access protected
	 */
	protected function set_options()
	{
		global $config, $request;

		$config->set('bh_ban_email', $request->variable('ban_email', 0));
		$config->set('bh_ban_ip', $request->variable('ban_ip', 0));
		$config->set('bh_del_avatar', $request->variable('del_avatar', 0));
		$config->set('bh_del_privmsgs', $request->variable('del_privmsgs', 0));
		$config->set('bh_del_posts', $request->variable('del_posts', 0));
		$config->set('bh_del_profile', $request->variable('del_profile', 0));
		$config->set('bh_del_signature', $request->variable('del_signature', 0));
		$config->set('bh_group_id', $request->variable('move_group', 0));
		$config->set('bh_sfs_api_key', $request->variable('sfs_api_key', '', true));
	}

	/**
	 * function to return groups that are allowed
	 */
	private function get_groups($group_selected)
	{
		global $db, $user;

		// Don't display any of the default groups
		// highly doubt an admin would want to ban someone into a default group
		$ignore_groups = array('BOTS', 'GUESTS', 'REGISTERED', 'REGISTERED_COPPA', 'NEWLY_REGISTERED', 'ADMINISTRATORS', 'GLOBAL_MODERATORS');

		$sql = 'SELECT group_name, group_id, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $db->sql_in_set('group_name', $ignore_groups, true) . '
			ORDER BY group_name ASC';
		$result = $db->sql_query($sql);

		$selected = ($group_selected == 0) ? ' selected="selected"' : '';
		$s_group_options = "<option value='0'$selected>&nbsp;{$user->lang['NO_GROUP']}&nbsp;</option>";
		while ($row = $db->sql_fetchrow($result))
		{
			$selected = ($row['group_id'] == $group_selected) ? ' selected="selected"' : '';
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];
			$s_group_options .= "<option value='{$row['group_id']}'$selected>$group_name</option>";
		}
		$db->sql_freeresult($result);

		return $s_group_options;
	}
}

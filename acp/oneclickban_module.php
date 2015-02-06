<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\oneclickban\acp;

class oneclickban_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $db, $request, $template, $user;

		$user->add_lang('acp/groups');

		$this->page_title = $user->lang['ACP_OCB_TITLE'];
		$this->tpl_name = 'oneclickban_body';

		add_form_key('oneclickban');

		// Get saved settings.
		$sql = 'SELECT * FROM ' . CONFIG_TEXT_TABLE . "
				WHERE config_name = 'oneclickban_settings'";
		$result = $db->sql_query($sql);
		$settings = $db->sql_fetchfield('config_value');
		$db->sql_freeresult($result);

		if (!empty($settings))
		{
			$settings = unserialize($settings);
		}
		else
		{
			// Default settings in case something went wrong with the install.
			$settings = array(
				'ban_email'		=> 1,
				'ban_ip'		=> 0,
				'del_posts'		=> 0,
				'del_avatar'	=> 0,
				'del_signature'	=> 0,
				'del_profile'	=> 0,
				'group_id'		=> 0,
				'sfs_api_key'	=> '',
			);
		}

		if ($request->is_set_post('submit'))
		{
			// Test if form key is valid
			if (!check_form_key('oneclickban'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			$group_id = $request->variable('move_group', 0);

			$settings = array(
				'ban_email'		=> $request->variable('ban_email', 0),
				'ban_ip'		=> $request->variable('ban_ip', 0),
				'del_posts'		=> $request->variable('del_posts', 0),
				'del_avatar'	=> $request->variable('del_avatar', 0),
				'del_signature'	=> $request->variable('del_signature', 0),
				'del_profile'	=> $request->variable('del_profile', 0),
				'group_id'		=> (!empty($group_id)) ? $group_id : 0,
				'sfs_api_key'	=> $request->variable('sfs_api_key', ''),
			);

			$sql_settings	= serialize($settings);
			$sql_settings	= $db->sql_escape($sql_settings);

			$sql = 'UPDATE ' . CONFIG_TEXT_TABLE . "
					SET config_value = '$sql_settings'
					WHERE config_name = 'oneclickban_settings'";
			$success = $db->sql_query($sql);

			if ($success === false)
			{
				trigger_error($user->lang['SETTINGS_ERROR'] . adm_back_link($this->u_action), E_USER_ERROR);
			}
			else
			{
				trigger_error($user->lang['SETTINGS_SUCCESS'] . adm_back_link($this->u_action));
			}
		}

		// Get the groups, so that the user can be added to them
		// don't rely on user input, well just because
		$s_group_options = $this->get_groups($settings['group_id']);

		$template->assign_vars(array(
			'BAN_EMAIL'		=> $settings['ban_email'],
			'BAN_IP'		=> $settings['ban_ip'],
			'DEL_POSTS'		=> $settings['del_posts'],
			'DEL_AVATAR'	=> $settings['del_avatar'],
			'DEL_SIGNATURE'	=> $settings['del_signature'],
			'DEL_PROFILE'	=> $settings['del_profile'],
			'MOVE_GROUP'	=> $s_group_options,
			'SFS_API_KEY'	=> $settings['sfs_api_key'],
			'SFS_CURL'		=> (function_exists('curl_init')) ? true : false,
		));
	}

	//function to return groups that are allowed
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

		$s_group_options = '<option value="0">' . $user->lang['NO_GROUP'] . '</option>';
		while ($row = $db->sql_fetchrow($result))
		{
			$selected = $row['group_id'] == $group_selected ? ' selected="selected"' : '';
			$s_group_options .= '<option value="' . $row['group_id'] . '"' . $selected . '>' . (($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
		}
		$db->sql_freeresult($result);

		return $s_group_options;
	}
}

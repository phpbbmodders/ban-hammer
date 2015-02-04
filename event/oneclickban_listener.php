<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\oneclickban\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class oneclickban_listener implements EventSubscriberInterface
{

	static public function getSubscribedEvents()
	{
		return(array(
			'core.memberlist_view_profile'	=> 'do_ocb_stuff',
		));
	}

	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth,\phpbb\request\request $request, \phpbb\cache\driver\driver_interface $cache, $phpbb_root_path, $phpExt)
	{
		$this->helper		= $helper;
		$this->template		= $template;
		$this->user			= $user;
		$this->db			= $db;
		$this->auth			= $auth;
		$this->request		= $request;
		$this->cache		= $cache;
		$this->root_path	= $phpbb_root_path;
		$this->php_ext		= $phpExt;
		//$this->data			= array();
	}

	public function do_ocb_stuff($event)
	{
		$this->data = $event['member'];
		$this->user_id = (int) $this->data['user_id'];
		$curl_exists = @function_exists('curl_init') ? true : false;
		/**
		 * Split these up and give error messages, later.
		 */
		if (!$this->auth->acl_get('m_ban') || ($this->data['user_type'] == USER_FOUNDER && $this->user->data['user_type'] != USER_FOUNDER) || $this->user_id == $this->user->data['user_id'])
		{
			// Nothing to see here, move on.
			// Only let founders be banned by other founders.
			// And don't allow them to ban them selves
			return;
		}

		$this->user->add_lang_ext('phpbbmodders/oneclickban', 'common');

		// Check if this user already is banned.
		$sql = 'SELECT ban_userid, ban_exclude FROM ' . BANLIST_TABLE . '
				WHERE ban_exclude = 0
					AND ban_userid =' . $this->user_id;
		$result = $this->db->sql_query($sql);
		$banned = $this->db->sql_fetchfield('ban_userid');
		$this->db->sql_freeresult($result);

		if (!empty($banned))
		{
			// It's enough to ban them once.
			$this->template->assign_var('S_IS_BANNED', true);
			return;
		}

		if (!$this->request->is_set('ocb') || ($this->request->is_set('ocb') && $this->request->is_set('confirm_key') && !confirm_box(true)))
		{
			$params = array(
				'mode'	=> 'viewprofile',
				'u'		=> $this->user_id,
				'ocb'	=> 1,
			);

			$this->template->assign_vars(array(
				'S_SHOW_OCB'	=> true,
				'U_OCBAN'		=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, $params),
			));
			return;
		}

		// Get OCB settings
		$sql = 'SELECT * FROM ' . CONFIG_TEXT_TABLE . "
				WHERE config_name = 'oneclickban_settings'";
		$result = $this->db->sql_query($sql);
		$settings = $this->db->sql_fetchfield('config_value');
		$this->db->sql_freeresult($result);
		$settings = unserialize($settings);

		if ($settings['group_id'])
		{
			// Get group name for banned users, if any.
			$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . '
					WHERE group_id = ' . (int) $settings['group_id'];
			$result = $this->db->sql_query($sql);
			$group_name = $this->db->sql_fetchfield('group_name');
			$this->db->sql_freeresult($result);

			if (empty($group_name))
			{
				$settings['group_id'] = 0;
			}
		}

		// Time to ban a user. But are you sure?
		if (!confirm_box(true))
		{
			$hidden_fields = array(
				'mode'	=> 'viewprofile',
			);

			$message = sprintf($this->user->lang['SURE_BAN'], $this->data['username']) . '<br /><br />';
			$message .= $this->user->lang['THIS_WILL'] . ':<br />';
			$message .= (!empty($settings['ban_username']))	? $this->user->lang['OCB_BAN_USERNAME'] . '<br />' : '';
			$message .= (!empty($settings['ban_email']))	? $this->user->lang['OCB_BAN_EMAIL'] . '<br />' : '';
			$message .= (!empty($settings['del_posts']))	? $this->user->lang['OCB_DEL_POSTS'] . '<br />' : '';
			$message .= (!empty($settings['del_avatar']))	? $this->user->lang['OCB_DEL_AVATAR'] . '<br />' : '';
			$message .= (!empty($settings['del_signature']))	? $this->user->lang['OCB_DEL_SIGNATURE'] . '<br />' : '';
			$message .= (!empty($settings['del_profile']))	? $this->user->lang['OCB_DEL_PROFILE'] . '<br />' : '';
			$message .= (!empty($group_name)) ? sprintf($this->user->lang['OCB_MOVE_GROUP'], $group_name) . '<br />' : '';
			$message .= (!empty($settings['sfs_api_key']) && $curl_exists)	? $this->user->lang['OCB_SUBMIT_SFS'] . '<br />' : '';

			confirm_box(false, $message, build_hidden_fields($hidden_fields));
		}

		// We have a user to ban.
		if (!function_exists('user_ban'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$error = array();

		if (!empty($settings['ban_username']))
		{
			$success = user_ban('user', $this->data['username'], 0, '', false, '');

			if (!$success)
			{
				$error[] = $this->user->lang['ERROR_BAN_USERNAME'];
				$error[] = $this->user->lang[''];
			}
		}

		if (!empty($settings['ban_email']))
		{
			$success = user_ban('email', $this->data['user_email'], 0, '', false, '');

			if (!$success)
			{
				$error[] = $this->user->lang['ERROR_BAN_EMAIL'];
			}
		}

		if (!empty($settings['del_posts']))
		{
			$this->ocb_del_posts();
		}

		if (!empty($settings['del_avatar']))
		{
			$success = avatar_delete('user', $this->data, true);

			if (!$success)
			{
				$error[] = $this->user->lang['ERROR_DEL_AVATAR'];
			}
		}

		if (!empty($settings['del_signature']))
		{
			$sql = 'UPDATE ' . USERS_TABLE . "
					SET user_sig ='',
						user_sig_bbcode_uid = '',
						user_sig_bbcode_bitfield = ''
					WHERE user_id = " . $this->user_id;
			$this->db->sql_query($sql);
		}

		if (!empty($settings['del_profile']))
		{
			$sql = 'DELETE FROM ' . PROFILE_FIELDS_DATA_TABLE . '
					WHERE user_id = ' . $this->user_id;
			$this->db->sql_query($sql);
		}

		if (!empty($group_name))
		{
			$return = group_user_add($settings['group_id'], array($this->user_id), array($this->data['username']), $group_name, true);

			if (!$success)
			{
				$error[] = $this->user->lang['ERROR_DEL_AVATAR'];
			}
		}

		if (!empty($settings['sfs_api_key']) && $curl_exists)
		{
			// add the spammer to the SFS database
			$http_request = 'http://www.stopforumspam.com/add.php';
			$http_request .= '?username=' . $this->data['username'];
			$http_request .= '&ip_addr=' . $this->data['user_ip'];
			$http_request .= '&email=' . $this->data['user_email'];
			$http_request .= '&api_key=' . $settings['sfs_api_key'];

			$response = $this->get_file($http_request);

			if (!$response)
			{
				$error[] = $this->user->lang['ERROR_SFS'];
			}
		}

		// Need to purge the cache.
		$this->cache->purge();

		// The page needs to be reloaded.
		$url	= generate_board_url();
		$url	.= ((substr($url, -1) == '/') ? '' : '/') . 'memberlist.' . $this->php_ext;
		$args	= 'mode=viewprofile&amp;u=' . $this->user_id;
		$url	= append_sid($url, $args);
		redirect($url);
	}

	private function ocb_del_posts()
	{
		$user_id = $this->user_id;

		// Close reports.
		// Topics can have more than one reported post so we need to get them first.
		$sql = 'SELECT p.post_id, p.topic_id, p.poster_id, p.post_reported, p.forum_id, t.topic_id, t.topic_first_post_id, t.topic_poster
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
				WHERE p.poster_id = $user_id
					AND t.topic_id = p.topic_id
				ORDER BY t.topic_id ASC, p.post_id ASC";
		$result	= $this->db->sql_query($sql);

		$posts = $topics = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$posts[$row['post_id']] = $row;

			if ($row['post_reported'] == 1)
			{
				$topics[$row['topic_id']] = (isset($topics[$row['topic_id']])) ? ++$topics[$row['topic_id']] : 1;
			}
		}
		$this->db->sql_freeresult($result);

		// Get PMs
		$sql = 'SELECT msg_id, author_id FROM ' . PRIVMSGS_TABLE . "
				WHERE author_id = $user_id";
		$result = $this->db->sql_query($sql);

		$pms = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$pms[] = $row['msg_id'];
		}
		$this->db->sql_freeresult($result);

		// And now handle the reports.
		$sql = 'SELECT report_id, post_id, report_closed, pm_id
				FROM ' . REPORTS_TABLE . '
				WHERE report_closed = 0';
		$result = $this->db->sql_query($sql);

		$close_reports = $unreport_topics = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!empty($row['post_id']) && !empty($posts[$row['post_id']]))
			{
				// A reported post.
				$reports[] = (int) $row['report_id'];

				// Check if the topic has more reported posts
				if (isset($topics[$posts[$row['post_id']]['topic_id']]) && --$topics[$posts[$row['post_id']]['topic_id']] <= 0)
				{
					$unreport_topics[] = (int) $posts[$row['post_id']]['topic_id'];
				}
			}
			else if (!empty($row['pm_id']) && !empty($pms[$row['pm_id']]))
			{
				// It's a reported PM. Only close the report, the PM will be deleted.
				$reports[] = (int) $row['report_id'];
			}
		}
		$this->db->sql_freeresult($result);

		// Close reports. Posts and PMs will be deleted so there is no need to unmark them as reported.
		if (!empty($close_reports))
		{
			$sql = 'UPDATE ' . REPORTS_TABLE . '
					SET report_closed = 1
					WHERE ' . $this->db->sql_in_set('report_id', $close_reports);
			$this->db->sql_query($sql);
		}

		// Unreport topics
		if (!empty($unreport_topics))
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_reported = 0
					WHERE ' . $this->db->sql_in_set('topic_id', $unreport_topics);
			$this->db->sql_query($sql);
		}

		// Delete some posts.
		if (!function_exists('delete_posts'))
		{
			include($this->root_path . 'includes/functions_admin.' . $this->php_ext);
		}

		delete_posts('post_id', array_keys($posts));

		$this->db->sql_query('DELETE FROM ' . BOOKMARKS_TABLE .			" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . DRAFTS_TABLE .			" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . FORUMS_TRACK_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . FORUMS_WATCH_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . MODERATOR_CACHE_TABLE .	" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . NOTIFICATIONS_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . POLL_VOTES_TABLE .		" WHERE vote_user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . PRIVMSGS_TABLE .			" WHERE author_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . PRIVMSGS_FOLDER_TABLE .	" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . PRIVMSGS_RULES_TABLE .	" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . TOPICS_POSTED_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . TOPICS_TRACK_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . TOPICS_WATCH_TABLE .		" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . USER_NOTIFICATIONS_TABLE .	" WHERE user_id = $user_id");
	}

	// use curl to get response from SFS
	private function get_file($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$contents = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// if nothing is returned (SFS is down)
		if($httpcode != 200)
		{
			return false;
		}
		return $contents;
	}
}

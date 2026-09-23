<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\event;

/**
* @ignore
*/
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class banhammer_listener implements EventSubscriberInterface
{
	/**
	 * Target user data
	 */
	private $data = array();

	/**
	 * Target user id
	 */
	private $user_id = 0;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/* @var \phpbbmodders\banhammer\core\bantime */
	protected $bantime;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string phpEx */
	protected $php_ext;

	/** @var ContainerInterface */
	protected $container;

	/** @var string */
	protected $restrict_table;

	/** @var string */
	protected $ban_group_table;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbbmodders\banhammer\core\bantime $bantime,
		$root_path,
		$phpExt,
		ContainerInterface $container,
		$restrict_table,
		$ban_group_table
	)
	{
		$this->auth			= $auth;
		$this->cache		= $cache;
		$this->config 		= $config;
		$this->db			= $db;
		$this->request		= $request;
		$this->template		= $template;
		$this->user			= $user;
		$this->bantime		= $bantime;
		$this->root_path	= $root_path;
		$this->php_ext		= $phpExt;
		$this->container	= $container;
		$this->restrict_table	= $restrict_table;
		$this->ban_group_table	= $ban_group_table;
	}

	static public function getSubscribedEvents()
	{
		return(array(
			'core.memberlist_view_profile'	=> array(
				array('do_ban_hammer_stuff'),
				array('do_restrict_stuff'),
			),
			'core.session_set_custom_ban'				=> 'undo_bh_group',
			'core.mcp_queue_approve_details_template'	=> 'add_mcp_queue_banhammer_link',
			'core.permissions'							=> 'add_permission',
		));
	}

	/**
	 * Register m_banhammer_del_posts_all with phpBB's permission system.
	 * Without this, \phpbb\permissions::permission_defined() never
	 * recognises it, and the ACP permission editor filters it out of
	 * every screen - the migration-granted permission would work but be
	 * invisible and unrevokable through the normal UI.
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 * @access public
	 */
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['m_banhammer_del_posts_all'] = array('lang' => 'ACL_M_BANHAMMER_DEL_POSTS_ALL', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	/**
	 * Add a Ban Hammer link to the MCP post-approval detail page (mcp_post.html),
	 * so a moderator can ban the poster without leaving the approval workflow to
	 * find their profile first.
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 * @access public
	 */
	public function add_mcp_queue_banhammer_link($event)
	{
		$post_info = $event['post_info'];
		$target_user_id = (int) $post_info['user_id'];

		if (!$this->auth->acl_get('m_ban'))
		{
			return;
		}

		$this->user->add_lang_ext('phpbbmodders/banhammer', 'banhammer');

		$template_vars = array();

		if ($post_info['user_type'] != USER_FOUNDER && $target_user_id != $this->user->data['user_id'] && $target_user_id > 0)
		{
			// Deliberately not 'bh' => 1: that shortcut jumps straight to the
			// confirmation step with none of the ban options set (permanent,
			// no email/IP ban, no deletions, no group move, no SFS report),
			// silently ignoring the ACP-configured defaults. Link to the
			// profile page instead, which shows the real options form.
			$params = array(
				'mode'	=> 'viewprofile',
				'u'		=> $target_user_id,
			);

			$template_vars['S_MCP_SHOW_BANHAMMER'] = true;
			$template_vars['U_MCP_BANHAMMER'] = append_sid($this->root_path . 'memberlist.' . $this->php_ext, $params);
		}

		$domain = $this->email_domain($post_info['user_email']);

		if ($domain !== '')
		{
			$template_vars['S_MCP_SHOW_BAN_DOMAIN'] = true;
			$template_vars['MCP_BAN_DOMAIN'] = htmlspecialchars($domain, ENT_QUOTES);
			$template_vars['U_MCP_BAN_DOMAIN'] = append_sid(generate_board_url() . '/app.' . $this->php_ext . '/banhammer/ban_domain', 'domain=' . urlencode($domain));
		}

		if (!empty($template_vars))
		{
			$this->template->assign_vars($template_vars);
		}
	}

	/**
	 * Extract the domain part of an email address.
	 *
	 * @param string $email
	 * @return string Lowercase domain, or an empty string when unusable.
	 * @access protected
	 */
	protected function email_domain($email)
	{
		$email = trim((string) $email);
		$at_pos = strrpos($email, '@');

		if ($at_pos === false || $at_pos === strlen($email) - 1)
		{
			return '';
		}

		return strtolower(substr($email, $at_pos + 1));
	}

	public function do_ban_hammer_stuff($event)
	{
		$this->data		= $event['member'];
		$this->user_id	= (int) $this->data['user_id'];
		$curl_exists	= (function_exists('curl_init')) ? true : false;

		/**
		 * Split these up and give error messages? Later maybe.
		 */
		if (!$this->auth->acl_get('m_ban') || $this->data['user_type'] == USER_FOUNDER || $this->user_id == $this->user->data['user_id'])
		{
			// Nothing to see here, move on.
			return;
		}

		$this->user->add_lang_ext('phpbbmodders/banhammer', array('banhammer', 'banhammer_acp'));
		$this->user->add_lang('acp/ban');

		// Check if this user already is banned.
		if (!function_exists('phpbb_get_banned_user_ids'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$banned = phpbb_get_banned_user_ids(array($this->user_id));

		if (!empty($banned))
		{
			$bh_result = $this->request->variable('bh_res', '');

			if (!empty($bh_result))
			{
				if ($bh_result == 'success')
				{
					$bh_message = $this->user->lang['BANNED_SUCCESS'];
				}
				else
				{
					// One or more actions failed. $bh_result only ever carries
					// '+'-joined error-code identifiers from the allow-list
					// below - never urldecode() this value or append raw
					// pieces of it to a template variable.
					$known_errors = array('ERROR_BAN_USER', 'ERROR_BAN_EMAIL', 'ERROR_BAN_IP', 'ERROR_SFS', 'ERROR_MOVE_GROUP');
					$message_ary = explode('+', $bh_result);
					$bh_message = $this->user->lang['BANNED_ERROR'];

					foreach ($message_ary as $error)
					{
						if (in_array($error, $known_errors, true))
						{
							$bh_message .= '<br />' . $this->user->lang[$error];
						}
					}
				}

				$this->template->assign_vars(array(
					'BH_STYLE'		=> (($bh_result == 'success') ? 'green' : '#a92c2c') . '; color: white;',
					'BH_MESSAGE'	=> $bh_message,
				));
			}
			else
			{
				// It's enough to ban them once.
				$this->template->assign_var('BH_MESSAGE', $this->user->lang['BH_BANNED']);
			}

			return;
		}

		// Re-validated here, not just trusted from when it was saved in the
		// ACP: the group may have been deleted, or made founder-managed,
		// since then.
		$group_name = $this->safe_group_name($this->config['bh_group_id']);

		if ($group_name === '')
		{
			$this->config['bh_group_id'] = 0;
		}

		if (!$this->request->is_set('bh') || ($this->request->is_set('bh') && $this->request->is_set('confirm_key') && !confirm_box(true)))
		{
			$params = array(
				'mode'	=> 'viewprofile',
				'u'		=> $this->user_id,
				'bh'	=> 1,
			);

			$this->template->assign_vars(array(
				'BH_BAN_EMAIL'		=> $this->config['bh_ban_email'],
				'BH_BAN_IP'			=> $this->config['bh_ban_ip'],
				'BH_DEL_AVATAR'		=> $this->config['bh_del_avatar'],
				'BH_DEL_PRIVMSGS'	=> $this->config['bh_del_privmsgs'],
				'BH_DEL_POSTS'		=> $this->config['bh_del_posts'],
				'BH_DEL_PROFILE'	=> $this->config['bh_del_profile'],
				'BH_DEL_SIGNATURE'	=> $this->config['bh_del_signature'],
				'BAN_TIME'		=> $this->bantime->display_ban_time($this->config['bh_ban_time']),

				'L_BH_MOVE_GROUP'	=> (!empty($group_name)) ? sprintf($this->user->lang['BH_MOVE_GROUP'], $group_name) : '',

				'S_BH_SFS'	=> (!empty($this->config['bh_sfs_api_key']) && $curl_exists) ? true : false,
				'S_SHOW_BH'	=> true,

				'U_HAMMERBAN'	=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, $params),
			));
			return;
		}

		// Time to ban a user. But are you sure?
		if (!confirm_box(true))
		{
			$hidden_fields = array(
				'ban_email'			=> $this->request->variable('ban_email', 0),
				'ban_ip'			=> $this->request->variable('ban_ip', 0),
				'ban_time'			=> $this->request->variable('ban_time', 0),
				'bh_reason'			=> $this->request->variable('bh_reason', '', true),
				'bh_reason_user'	=> $this->request->variable('bh_reason_user', '', true),
				'del_avatar'		=> $this->request->variable('del_avatar', 0),
				'del_privmsgs'		=> $this->request->variable('del_privmsgs', 0),
				'del_posts'			=> $this->request->variable('del_posts', 0),
				'del_profile'		=> $this->request->variable('del_profile', 0),
				'del_signature'		=> $this->request->variable('del_signature', 0),
				'mode'				=> 'viewprofile',
				'move_group'		=> $this->request->variable('move_group', 0),
				'sfs_report'		=> $this->request->variable('sfs_report', 0),
			);

			// we state how long the user will be banned for
			$ban_length_options = $this->bantime->ban_length_options();
			$length = '';
			$permanent = false;
			foreach ($ban_length_options as $key => $value)
			{
				if ($key == $hidden_fields['ban_time'])
				{
					$length = $value;
					if ($key == 0)
					{
						$permanent = true;
					}
				}
			}

			$message = sprintf($this->user->lang['SURE_BAN'], $this->data['username']) . '<br><br>';
			$message .= $this->user->lang['THIS_WILL'] . $this->user->lang['COLON'] . '<br>';
			$message .= ($length)							? (($permanent) ? $this->user->lang['BH_BAN_USER_PERM'] . '<br>' : $this->user->lang('BH_BAN_USER', $length). '<br>') : '';
			$message .= ($hidden_fields['ban_email'])		? (($permanent) ? $this->user->lang['BH_BAN_EMAIL_PERM'] . '<br>' : $this->user->lang('BH_BAN_EMAIL_FOR', $length) . '<br>') : '';
			$message .= ($hidden_fields['ban_ip'])			? (($permanent) ? $this->user->lang['BH_BAN_IP_PERM'] . '<br>' : $this->user->lang('BH_BAN_IP_FOR', $length) . '<br>') : '';
			$message .= ($hidden_fields['bh_reason'])		? $this->user->lang('BH_REASON', $hidden_fields['bh_reason']) . '<br>' : '';
			$message .= ($hidden_fields['bh_reason_user'])	? $this->user->lang('BH_REASON_USER', $hidden_fields['bh_reason_user']) . '<br>' : '';
			$message .= ($hidden_fields['del_avatar'])		? $this->user->lang['BH_DEL_AVATAR'] . '<br>' : '';
			$message .= ($hidden_fields['del_privmsgs'])	? $this->user->lang['BH_DEL_PRIVMSGS'] . '<br>' : '';
			$message .= ($hidden_fields['del_posts'])		? $this->user->lang['BH_DEL_POSTS'] . '<br>' : '';
			$message .= ($hidden_fields['del_profile'])		? $this->user->lang['BH_DEL_PROFILE'] . '<br>' : '';
			$message .= ($hidden_fields['del_signature'])	? $this->user->lang['BH_DEL_SIGNATURE'] . '<br>' : '';
			$message .= (!empty($group_name) && $hidden_fields['move_group'])	? $this->user->lang('BH_MOVE_GROUP', $group_name) . '<br>' : '';
			$message .= ($hidden_fields['sfs_report'] && $curl_exists)			? $this->user->lang['BH_SUBMIT_SFS'] . '<br>' : '';

			confirm_box(false, $message, build_hidden_fields($hidden_fields));

			// confirm_box(false, ...) above only returns instead of exiting
			// when the request was actually a cancellation (POST 'cancel'),
			// in which case we must not fall through to the ban below.
			return;
		}

		// We have a user to ban.
		$error = array();

		// Any reason for this ban?
		$bh_reason		= $this->request->variable('bh_reason', '', true);
		$bh_reason_user	= $this->request->variable('bh_reason_user', '', true);
		$ban_time		= $this->request->variable('ban_time', 0);

		// The username is the user so it's always banned.
		$success = user_ban('user', $this->data['username'], $ban_time, '', false, $bh_reason, $bh_reason_user);

		if (!$success)
		{
			$error[] = 'ERROR_BAN_USER';
		}

		if ($this->request->variable('ban_email', 0))
		{
			$success = user_ban('email', $this->data['user_email'], $ban_time, '', false, $bh_reason, $bh_reason_user);

			if (!$success)
			{
				$error[] = 'ERROR_BAN_EMAIL';
			}
		}

		if ($this->request->variable('ban_ip', 0) && !empty($this->data['user_ip']))
		{
			$success = user_ban('ip', $this->data['user_ip'], $ban_time, '', false, $bh_reason, $bh_reason_user);

			if (!$success)
			{
				$error[] = 'ERROR_BAN_IP';
			}
		}

		if ($this->request->variable('del_posts', 0))
		{
			$this->bh_del_posts();
		}

		if ($this->request->variable('del_privmsgs', 0))
		{
			$this->bh_del_privmsgs();
		}

		if ($this->request->variable('del_avatar', 0))
		{
			$phpbb_avatar_manager = $this->container->get('avatar.manager');
			$phpbb_avatar_manager->handle_avatar_delete($this->db, $this->user, $phpbb_avatar_manager->clean_row($this->data, 'user'), USERS_TABLE, 'user_');
		}

		if ($this->request->variable('del_signature', 0))
		{
			$sql = 'UPDATE ' . USERS_TABLE . "
					SET user_sig = '',
						user_sig_bbcode_uid = '',
						user_sig_bbcode_bitfield = ''
					WHERE user_id = " . (int) $this->user_id;
			$this->db->sql_query($sql);
		}

		if ($this->request->variable('del_profile', 0))
		{
			$sql = 'DELETE FROM ' . PROFILE_FIELDS_DATA_TABLE . '
					WHERE user_id = ' . (int) $this->user_id;
			$this->db->sql_query($sql);
		}

		if ($this->request->variable('move_group', 0) && !empty($group_name))
		{
			$move_group_id = (int) $this->config['bh_group_id'];
			$return = group_user_add($move_group_id, array($this->user_id), array($this->data['username']), $group_name, true);
			$move_new_membership = 1;

			if ($return === 'GROUP_USERS_EXIST')
			{
				// Already a member for some unrelated reason: group_user_add()
				// returns before setting the default group in that case, so
				// set it directly instead (same fix as do_restrict_stuff()'s
				// equivalent case).
				$return = group_user_attributes('default', $move_group_id, array($this->user_id));
				$move_new_membership = 0;
			}

			if ($return !== false)
			{
				$error[] = 'ERROR_MOVE_GROUP';
			}
			else
			{
				// Record which group this ban actually moved them into (and
				// whether that membership was newly created or pre-existing),
				// and their default group beforehand, so undo_bh_group() can
				// clean up precisely this action later instead of comparing
				// against the *current* ACP setting.
				$sql_ary = array(
					'user_id'				=> $this->user_id,
					'original_group_id'	=> (int) $this->data['group_id'],
					'move_group_id'			=> $move_group_id,
					'move_new_membership'	=> $move_new_membership,
				);
				$sql = 'INSERT INTO ' . $this->ban_group_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);

				// A second ban+move on the same already-banned user is
				// blocked well before this point (see the banned-user check
				// above), but guard the unique index anyway rather than let
				// a genuine race surface as an uncaught SQL error.
				$this->db->sql_return_on_error(true);
				$this->db->sql_query($sql);
				$this->db->sql_return_on_error(false);
			}
		}

		if ($this->request->variable('sfs_report', 0) && !empty($this->config['bh_sfs_api_key']) && $curl_exists)
		{
			// add the spammer to the SFS database. HTTPS unless the admin
			// has explicitly opted into HTTP for it in the ACP.
			$sfs_scheme = (!empty($this->config['bh_sfs_allow_http'])) ? 'http://' : 'https://';
			$http_request = $sfs_scheme . 'www.stopforumspam.com/add.php';
			$http_request .= '?username=' . urlencode($this->data['username']);
			$http_request .= '&ip_addr=' . urlencode($this->data['user_ip']);
			$http_request .= '&email=' . urlencode($this->data['user_email']);
			$http_request .= '&api_key=' . urlencode($this->config['bh_sfs_api_key']);

			$response = $this->get_file($http_request);

			if (!$response)
			{
				$error[] = 'ERROR_SFS';
			}
		}

		// Need to purge the cache.
		$this->cache->destroy('sql', BANLIST_TABLE);

		// The page needs to be reloaded to show the new banned status.
		$args = array(
			'mode'		=> 'viewprofile',
			'u'			=> $this->user_id,
			'bh_res'	=> (empty($error)) ? 'success' : urlencode(implode('+', $error)),
		);

		$url	= generate_board_url();
		$url	.= ((substr($url, -1) == '/') ? '' : '/') . 'memberlist.' . $this->php_ext;
		$url	= append_sid($url, $args);

		redirect($url);
	}

	/**
	 * Move a user into a restricted group for a set time, instead of banning
	 * them. They keep the ability to log in, just with whatever reduced
	 * permissions the admin has given the restricted group.
	 *
	 * Answers CDB topic 240381 ("would it be possible to slightly change
	 * this to avoid the ban... add the user from profile to a different
	 * group, severely limited, for a given time").
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 * @access public
	 */
	public function do_restrict_stuff($event)
	{
		$data = $event['member'];
		$user_id = (int) $data['user_id'];
		$restrict_group_id = (int) $this->config['bh_restrict_group_id'];

		// Re-validated here, not just trusted from ACP-save time: the group
		// may have been deleted, made founder-managed, or turned into an
		// Open/Free group (which would let the restricted user just resign
		// via UCP - see includes/ucp/ucp_groups.php) since then.
		if ($restrict_group_id && $this->safe_group_name($restrict_group_id, true) === '')
		{
			$restrict_group_id = 0;
		}

		if (!$this->auth->acl_get('m_ban') || $data['user_type'] == USER_FOUNDER || $user_id == $this->user->data['user_id'] || !$restrict_group_id)
		{
			// Nothing to see here, move on. No group configured in the ACP
			// means this feature is simply off.
			return;
		}

		// A banned user can't log in regardless of group, so restricting
		// them on top of a ban is meaningless - same check do_ban_hammer_stuff
		// already makes before showing its own options.
		if (!function_exists('phpbb_get_banned_user_ids'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		if (!empty(phpbb_get_banned_user_ids(array($user_id))))
		{
			return;
		}

		$this->user->add_lang_ext('phpbbmodders/banhammer', 'banhammer');
		$this->user->add_lang('acp/ban');

		if ($this->active_restriction($user_id) !== null)
		{
			$this->template->assign_var('RESTRICT_MESSAGE', $this->user->lang['BH_ALREADY_RESTRICTED']);

			return;
		}

		if (!$this->request->is_set('restrict') || ($this->request->is_set('restrict') && $this->request->is_set('confirm_key') && !confirm_box(true)))
		{
			$params = array(
				'mode'		=> 'viewprofile',
				'u'			=> $user_id,
				'restrict'	=> 1,
			);

			$this->template->assign_vars(array(
				'RESTRICT_TIME'		=> $this->bantime->display_ban_time(0),
				'S_SHOW_RESTRICT'	=> true,
				'U_RESTRICT_USER'	=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, $params),
			));

			return;
		}

		if (!confirm_box(true))
		{
			$hidden_fields = build_hidden_fields(array(
				'restrict_time'	=> $this->request->variable('restrict_time', 0),
				'mode'			=> 'viewprofile',
			));

			$ban_length_options = $this->bantime->ban_length_options();
			$length = isset($ban_length_options[$this->request->variable('restrict_time', 0)]) ? $ban_length_options[$this->request->variable('restrict_time', 0)] : '';

			$message = sprintf($this->user->lang['BH_SURE_RESTRICT'], $data['username']);
			$message .= ($length) ? '<br><br>' . $this->user->lang('BH_RESTRICT_FOR', $length) : '<br><br>' . $this->user->lang['BH_RESTRICT_PERM'];

			confirm_box(false, $message, $hidden_fields);

			// confirm_box(false, ...) above only returns instead of exiting
			// when the request was actually a cancellation (POST 'cancel'),
			// in which case we must not fall through to the restriction below.
			return;
		}

		if (!function_exists('group_user_add') || !function_exists('group_user_attributes'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$restrict_time = $this->request->variable('restrict_time', 0);
		// $restrict_time is minutes (ban_length_options()'s keys, same unit
		// user_ban() expects), not days.
		$restrict_until = ($restrict_time > 0) ? time() + ($restrict_time * 60) : 0;
		$original_group_id = (int) $data['group_id'];

		$sql_ary = array(
			'user_id'			=> $user_id,
			'original_group_id'	=> $original_group_id,
			'restrict_group_id'	=> $restrict_group_id,
			'restrict_until'	=> $restrict_until,
			// Corrected below to 0 if group_user_add() finds the user
			// already a member; default of 1 matches the common case.
			'restrict_new_membership'	=> 1,
		);
		$sql = 'INSERT INTO ' . $this->restrict_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);

		// Two moderators confirming a restriction on the same user at
		// nearly the same moment could both pass the active_restriction()
		// check above before either commits. The unique index on user_id
		// (see the restrict_unique_user migration) turns the loser's
		// insert into a caught error here instead of a second, silently
		// conflicting tracking row.
		$this->db->sql_return_on_error(true);
		$this->db->sql_query($sql);
		$insert_failed = (bool) $this->db->get_sql_error_triggered();
		$this->db->sql_return_on_error(false);

		if ($insert_failed)
		{
			$this->template->assign_var('RESTRICT_MESSAGE', $this->user->lang['BH_ALREADY_RESTRICTED']);

			return;
		}

		$result = group_user_add($restrict_group_id, array($user_id), false, false, true);
		$group_action_failed = ($result !== false);

		if ($result === 'GROUP_USERS_EXIST')
		{
			// Already a member for some unrelated reason: group_user_add()
			// returns before setting the default group in that case (see
			// the same fix in restriction_expiry's restore path), so set it
			// directly instead - but only approved members can be made a
			// default group (see group_user_attributes()'s 'default' case);
			// a pending join request returns NO_USERS and changes nothing,
			// which is still a failure to actually apply the restriction.
			$group_action_failed = (group_user_attributes('default', $restrict_group_id, array($user_id)) !== false);

			if (!$group_action_failed)
			{
				// The membership predates this restriction; don't let
				// expiry/purge remove it later on this restriction's
				// account - only the default-group change and the
				// tracking row itself belong to it.
				$this->db->sql_query('UPDATE ' . $this->restrict_table . '
					SET restrict_new_membership = 0
					WHERE user_id = ' . $user_id);
			}
		}

		if ($group_action_failed)
		{
			// The group action never took effect - pending membership,
			// or NO_USER/GROUP_USERS_INVALID (shouldn't happen; $user_id
			// comes from the profile this event fired for). Don't leave a
			// tracking row behind for a restriction that isn't actually in
			// place.
			$this->db->sql_query('DELETE FROM ' . $this->restrict_table . ' WHERE user_id = ' . $user_id);

			return;
		}

		$args = array(
			'mode'	=> 'viewprofile',
			'u'		=> $user_id,
		);

		$url = append_sid(generate_board_url() . '/memberlist.' . $this->php_ext, $args);

		redirect($url);
	}

	/**
	 * Whether a user currently has an active restriction.
	 *
	 * @param int $user_id
	 * @return array|null The restriction row, or null when there is none.
	 * @access protected
	 */
	protected function active_restriction($user_id)
	{
		$sql = 'SELECT restrict_id, restrict_group_id
			FROM ' . $this->restrict_table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return ($row) ?: null;
	}

	/**
	 * The group-move tracking row for a user's currently active ban, if any.
	 *
	 * @param int $user_id
	 * @return array|null The tracking row, or null when there is none.
	 * @access protected
	 */
	protected function active_ban_group($user_id)
	{
		$sql = 'SELECT ban_id, original_group_id, move_group_id, move_new_membership
			FROM ' . $this->ban_group_table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return ($row) ?: null;
	}

	/**
	 * A configured move/restrict group's name, re-validated at the point
	 * it's about to be used rather than trusted from ACP-save time: the
	 * group may have been deleted, made founder-managed, or (when
	 * $reject_self_service is set) turned into an Open/Free group, since
	 * then.
	 *
	 * @param int $group_id
	 * @param bool $reject_self_service
	 * @return string Group name, or '' if unset, gone, founder-managed and
	 *                the acting moderator isn't the founder, or (when
	 *                $reject_self_service is set) Open/Free.
	 * @access protected
	 */
	protected function safe_group_name($group_id, $reject_self_service = false)
	{
		if (!$group_id)
		{
			return '';
		}

		$sql = 'SELECT group_name, group_type, group_founder_manage
			FROM ' . GROUPS_TABLE . '
			WHERE group_id = ' . (int) $group_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row
			|| ($this->user->data['user_type'] != USER_FOUNDER && $row['group_founder_manage'])
			|| ($reject_self_service && ($row['group_type'] == GROUP_OPEN || $row['group_type'] == GROUP_FREE)))
		{
			return '';
		}

		return $row['group_name'];
	}

	/**
	 * Once a ban is cleared, undo whatever group move that specific ban
	 * actually made - not "the group currently configured in the ACP",
	 * which may have changed since, or matter to some other, unrelated
	 * group membership entirely.
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 * @access public
	 */
	public function undo_bh_group($event)
	{
		if ($event['banned'] || $this->user->data['user_type'] == USER_IGNORE)
		{
			return;
		}

		$ban_group = $this->active_ban_group($this->user->data['user_id']);

		if ($ban_group === null)
		{
			return;
		}

		if (!function_exists('group_user_del') || !function_exists('group_user_attributes'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$move_group_id = (int) $ban_group['move_group_id'];

		if ($move_group_id && $ban_group['move_new_membership'])
		{
			// A restriction can be configured to use the same group as a
			// ban's move-to group. If this user also has an active
			// restriction recorded against this exact group, leave their
			// membership alone - it's still needed for the restriction,
			// checked against its own recorded group, not current config.
			$restriction = $this->active_restriction($this->user->data['user_id']);

			if ($restriction === null || (int) $restriction['restrict_group_id'] !== $move_group_id)
			{
				group_user_del($move_group_id, array($this->user->data['user_id']));
			}
		}

		if ($ban_group['original_group_id'])
		{
			group_user_attributes('default', (int) $ban_group['original_group_id'], array($this->user->data['user_id']));
		}

		$this->db->sql_query('DELETE FROM ' . $this->ban_group_table . ' WHERE ban_id = ' . (int) $ban_group['ban_id']);
	}

	private function bh_del_privmsgs()
	{
		$user_id = $this->user_id;

		// phpBB's own bulk PM cleanup (used when deleting a user account
		// entirely): correctly adjusts recipients' unread/new counts,
		// removes attachments and notifications, and anonymizes already-
		// delivered sent messages instead of deleting them out from under
		// their recipients. A hand-rolled DELETE here previously left all
		// of that bookkeeping inconsistent.
		if (!function_exists('phpbb_delete_users_pms'))
		{
			include($this->root_path . 'includes/functions_privmsgs.' . $this->php_ext);
		}
		phpbb_delete_users_pms(array($user_id));

		// The account itself isn't deleted, only its own folder structure
		// and rules, which don't affect any other user.
		$this->db->sql_query('DELETE FROM ' . PRIVMSGS_FOLDER_TABLE .	" WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . PRIVMSGS_RULES_TABLE .	" WHERE user_id = $user_id");
	}

	private function bh_del_posts()
	{
		$user_id = (int) $this->user_id;

		// Close reports.
		// Topics can have more than one reported post so we need to get them first.
		$sql = 'SELECT p.post_id, p.topic_id, p.poster_id, p.post_reported, p.forum_id, t.topic_id, t.topic_first_post_id, t.topic_poster
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . " t
				WHERE p.poster_id = $user_id
					AND t.topic_id = p.topic_id
				ORDER BY t.topic_id ASC, p.post_id ASC";
		$result	= $this->db->sql_query($sql);
		$posts = $topics = array();

		// Step through the topics and count reported posts in each topic where this user is reported.
		while ($row = $this->db->sql_fetchrow($result))
		{
			$posts[$row['post_id']] = $row;

			if ($row['post_reported'] == 1)
			{
				$topics[$row['topic_id']] = (isset($topics[$row['topic_id']])) ? ++$topics[$row['topic_id']] : 1;
			}
		}
		$this->db->sql_freeresult($result);

		// m_ban alone doesn't grant delete rights in every forum; only the
		// extension's own explicit permission does. Without it, only touch
		// posts in forums the acting moderator could delete in anyway.
		if (!$this->auth->acl_get('m_banhammer_del_posts_all'))
		{
			foreach ($posts as $post_id => $post_row)
			{
				if (!$this->auth->acl_get('m_delete', (int) $post_row['forum_id']))
				{
					// Only gates $posts: the report-closing loop below only
					// ever reads $topics through a $posts[$post_id] lookup,
					// so leaving a stale count here for a topic we're no
					// longer touching has no effect.
					unset($posts[$post_id]);
				}
			}
		}

		if (empty($posts))
		{
			// No posts to delete - either there were none to begin with, or
			// permission filtered out all of them. Either way, stop here
			// rather than still wiping unrelated account data (bookmarks,
			// drafts, notifications, ...) below for a del_posts request
			// that has nothing to actually act on, regardless of which
			// permission granted access. A moderator whose authority
			// covers at least one of the target's posts still gets the
			// full account cleanup, same as always.
			return;
		}

		// And now handle the reports.
		$sql = 'SELECT report_id, post_id, report_closed
				FROM ' . REPORTS_TABLE . '
				WHERE report_closed = 0
					AND post_id <> 0';
		$result = $this->db->sql_query($sql);
		$close_reports = $unreport_topics = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!empty($row['post_id']) && !empty($posts[$row['post_id']]))
			{
				// A reported post.
				$close_reports[] = (int) $row['report_id'];

				// Check if the topic has more reported posts
				if (isset($topics[$posts[$row['post_id']]['topic_id']]) && --$topics[$posts[$row['post_id']]['topic_id']] <= 0)
				{
					$unreport_topics[] = (int) $posts[$row['post_id']]['topic_id'];
				}
			}
		}
		$this->db->sql_freeresult($result);

		// Close reports. Posts will be deleted so there is no need to unmark them as reported.
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

		// Delete from other tables.
		$this->db->sql_query('DELETE FROM ' . BOOKMARKS_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . DRAFTS_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . FORUMS_TRACK_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . FORUMS_WATCH_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . MODERATOR_CACHE_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . NOTIFICATIONS_TABLE .	" WHERE user_id = $user_id");
		// Poll votes are deliberately left alone, same as phpBB's own
		// user_delete() (which doesn't touch POLL_VOTES_TABLE either):
		// removing them here without decrementing poll_option_total would
		// corrupt the poll's totals and let the user vote again later.
		// TOPICS_POSTED_TABLE is also deliberately left alone: delete_posts()
		// above already calls update_posted_info() to rebuild it correctly
		// for the topics it actually touched. A blanket delete here would
		// also wipe the "posted in this topic" marker for topics where the
		// user's post survived (not deletable in this pass), which
		// delete_posts() never touched and has no reason to be wrong.
		$this->db->sql_query('DELETE FROM ' . TOPICS_TRACK_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . TOPICS_WATCH_TABLE . " WHERE user_id = $user_id");
		$this->db->sql_query('DELETE FROM ' . USER_NOTIFICATIONS_TABLE . " WHERE user_id = $user_id");
	}

	// use curl to get response from SFS
	private function get_file($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// curl_exec() returns false on a transport failure (e.g. the
		// connection dropped after headers were already sent), which the
		// HTTP code alone would not catch.
		if ($response === false || $httpcode != 200)
		{
			return false;
		}

		return(true);
	}
}

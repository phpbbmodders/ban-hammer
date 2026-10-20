<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\controller;

/**
* The ban list page: every user with an active username ban, with the ban
* dates, the reason shown to the banned user, and their warnings.
*
* Shown only when the ban list is turned on in the ACP, and only to users
* with the "Can view ban list" permission.
*/
class banlist_controller
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_prefix;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth			Auth object
	* @param \phpbb\config\config				$config			Config object
	* @param \phpbb\db\driver\driver_interface	$db				Database object
	* @param \phpbb\controller\helper			$helper			Controller helper
	* @param \phpbb\language\language			$language		Language object
	* @param \phpbb\pagination					$pagination		Pagination object
	* @param \phpbb\request\request				$request		Request object
	* @param \phpbb\template\template			$template		Template object
	* @param \phpbb\user						$user			User object
	* @param string								$table_prefix	Table prefix
	* @access public
	*/
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\pagination $pagination,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		$table_prefix
	)
	{
		$this->auth			= $auth;
		$this->config		= $config;
		$this->db			= $db;
		$this->helper		= $helper;
		$this->language		= $language;
		$this->pagination	= $pagination;
		$this->request		= $request;
		$this->template		= $template;
		$this->user			= $user;
		$this->table_prefix	= $table_prefix;
	}

	/**
	* Display the ban list.
	*
	* @return \Symfony\Component\HttpFoundation\Response
	* @access public
	*/
	public function display()
	{
		// Off in the ACP: behave as if the page doesn't exist.
		if (empty($this->config['bh_banlist_enable']))
		{
			throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND');
		}

		$this->language->add_lang('banlist', 'phpbbmodders/banhammer');

		if (!$this->auth->acl_get('u_banhammer_viewbanlist'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		// phpBB 4.0 replaced the banlist table by a bans table, with one row
		// per banned item typed by ban_mode and its own column names.
		if (defined('BANS_TABLE'))
		{
			$from = $this->table_prefix . 'bans';
			$sql_where = "b.ban_mode = 'user'";
			$reason_column = 'b.ban_reason_display';
		}
		else
		{
			$from = $this->table_prefix . 'banlist';
			$sql_where = 'b.ban_exclude = 0';
			$reason_column = 'b.ban_give_reason';
		}

		$sql_where .= '
			AND b.ban_userid = u.user_id
			AND u.user_type <> ' . USER_IGNORE . '
			AND (b.ban_end = 0 OR b.ban_end > ' . time() . ')';

		$sql = 'SELECT COUNT(b.ban_userid) AS total
			FROM ' . $from . ' b, ' . USERS_TABLE . ' u
			WHERE ' . $sql_where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		// Same page size phpBB's own member list uses.
		$per_page = max(1, (int) $this->config['topics_per_page']);
		$start = $this->pagination->validate_start($this->request->variable('start', 0), $per_page, $total);
		$base_url = $this->helper->route('phpbbmodders_banhammer_banlist');
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $per_page, $start);

		$sql = 'SELECT b.ban_start, b.ban_end, ' . $reason_column . ' AS ban_reason_user,
				u.user_id, u.username, u.user_colour, u.user_warnings, u.user_last_warning
			FROM ' . $from . ' b, ' . USERS_TABLE . ' u
			WHERE ' . $sql_where . '
			ORDER BY b.ban_start DESC, b.ban_id DESC';
		$result = $this->db->sql_query_limit($sql, $per_page, $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('banlist_row', array(
				'BAN_START'		=> $this->user->format_date($row['ban_start']),
				'BAN_END'		=> ($row['ban_end']) ? $this->user->format_date($row['ban_end']) : $this->language->lang('BH_BANLIST_PERMANENT'),
				'BAN_REASON'	=> $row['ban_reason_user'],
				'LAST_WARNING'	=> ($row['user_last_warning']) ? $this->user->format_date($row['user_last_warning']) : '',
				'USERNAME_FULL'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'WARNINGS'		=> (int) $row['user_warnings'],
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'TOTAL_BANNED'	=> $this->language->lang('BH_BANLIST_TOTAL', $total),
		));

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->language->lang('BH_BANLIST'),
			'U_VIEW_FORUM'	=> $base_url,
		));

		return $this->helper->render('banhammer_banlist.html', $this->language->lang('BH_BANLIST'));
	}
}

<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2026 phpBB Modders <https://phpbbmodders.net/>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\banhammer\controller;

use Symfony\Component\HttpFoundation\Response;

/**
* Bans every address at a given email domain (*@domain.tld).
*
* Reachable only from the MCP approve-details page's "Ban email domain"
* link (see event\banhammer_listener::add_mcp_queue_banhammer_link), never
* from the notification email itself: phpBB's messenger has no supported
* way for an extension to add a placeholder to a core .txt email template,
* see docs/events/messenger_email_template_ext_override.txt.
*/
class ban_domain_controller
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth			$auth		Auth object
	* @param \phpbb\language\language	$language	Language object
	* @param \phpbb\request\request		$request	Request object
	* @param \phpbb\user					$user		User object
	* @access public
	*/
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\language\language $language,
		\phpbb\request\request $request,
		\phpbb\user $user
	)
	{
		$this->auth		= $auth;
		$this->language	= $language;
		$this->request	= $request;
		$this->user		= $user;
	}

	/**
	* Handle the ban-domain confirm/submit cycle.
	*
	* @return Response
	* @access public
	*/
	public function handle()
	{
		if (!$this->auth->acl_get('m_ban'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('phpbbmodders/banhammer', 'banhammer');

		$domain = trim((string) $this->request->variable('domain', ''));

		// A conservative domain shape: labels of letters/digits/hyphens,
		// separated by dots, ending in a letters-only TLD. Good enough to
		// keep the wildcard ban pattern sane without trying to be a full
		// RFC 1035 validator.
		if ($domain === '' || !preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*\.[a-z]{2,}$/i', $domain))
		{
			trigger_error($this->language->lang('BH_INVALID_DOMAIN'), E_USER_WARNING);
		}

		// A domain ban is a broad, deliberate action (typically a known spam
		// or disposable-email provider), so this quick action is
		// permanent-only. An admin who wants a time-limited domain ban can
		// still enter the same *@domain.tld pattern by hand in the ACP.
		$ban_pattern = '*@' . $domain;

		if (confirm_box(true))
		{
			$reason = $this->request->variable('bh_reason', '', true);

			$success = user_ban('email', $ban_pattern, 0, '', false, $reason);

			if (!$success)
			{
				trigger_error('ERROR_BAN_EMAIL', E_USER_WARNING);
			}

			trigger_error($this->language->lang('BH_DOMAIN_BANNED', $domain));
		}
		else
		{
			$hidden_fields = build_hidden_fields(array(
				'domain'	=> $domain,
			));

			confirm_box(false, $this->language->lang('BH_CONFIRM_BAN_DOMAIN', $ban_pattern), $hidden_fields);
		}

		return new Response('', 200);
	}
}

<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
* Translated By : Bassel Taha Alhitary - www.alhitary.net
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'BANNED_ERROR'		=> 'هناك بعض الأخطاء أثناء عملية حظر هذا العضو',
	'BANNED_SUCCESS'	=> 'تم حظر هذا العضو بنجاح.',

	'ERROR_BAN_EMAIL'	=> 'فشل في حظر البريد الإلكتروني.',
	'ERROR_BAN_IP'		=> 'فشل في حظر رقم الـIP .',
	'ERROR_BAN_USER'	=> 'فشل في حظر العضو.',
	'ERROR_DEL_POSTS'	=> 'فشل في حذف مشاركات العضو.',
	'ERROR_MOVE_GROUP'	=> 'فشل في نقل العضو إلى المجموعة المُحددة.',
	'ERROR_SFS'			=> 'منع المُشاركات المُزعجة بالمنتدى',

	'BH_BAN_EMAIL'			=> 'حظر البريد الإلكتروني لهذا العضو ',
	'BH_BAN_GIVE_REASON'	=> 'سبب الحظر ( يظهر للعضو ) ',
	'BH_BAN_IP'				=> 'حظر عنوان الـIP لهذا العضو ',
	'BH_BAN_IP_EXPLAIN'		=> '<strong>انتبه لهذه الخطوة.</strong> مُعظم المستخدمين العاديين بالمنازل لديهم عناوين IP فعال و سيكونوا بحاجة فقط إلى إعادة تشغيل جهاز الروتر / الموديم الخاص بهم للحصول على عنوان IP جديد. وفي اليوم التالي قد يتم تخصيص ذلك العنوان الـ IP إلى عضو تريده في منتداك. الأشخاص المُزعجين أيضا يستخدمون منافذ إنترنت مجهولة ( بروكسي ) أو أن شبكة Tor تحظر عنوان الـ IP غير حقيقي ( مزيف ).',
	'BH_BAN_LENGTH'			=> 'حظر هذا العضو لمُدة %s',
	'BH_BAN_REASON'			=> 'سبب الحظر ( يظهر للمدراء والمشرفين ) ',
	'BH_BAN_USER'			=> 'حظر هذا العضو لمُدة %s',
	'BH_BAN_USER_PERM'		=> 'حظر هذا العضو بشكل دائم',
	'BH_BAN_EMAIL_PERM'		=> 'حظر البريد الإلكتروني لهذا العضو بشكل دائم',
	'BH_BAN_EMAIL_FOR'		=> 'حظر البريد الإلكتروني لهذا العضو لمُدة %s',
	'BH_BAN_IP_PERM'		=> 'حظر عنوان الـIP لهذا العضو بشكل دائم',
	'BH_BAN_IP_FOR'			=> 'حظر عنوان الـIP لهذا العضو لمُدة %s',
	'BH_BANNED'				=> 'تم حظر هذا العضو',

	'BH_DEL_AVATAR'		=> 'حذف الصورة الشخصية لهذا العضو',
	'BH_DEL_PRIVMSGS'	=> 'حذف الرسائل الخاصة لهذا العضو',
	'BH_DEL_POSTS'		=> 'حذف مُشاركات هذا العضو',
	'BH_DEL_PROFILE'	=> 'حذف حقول الملف الشخصي لهذا العضو',
	'BH_DEL_SIGNATURE'	=> 'حذف التوقيع لهذا العضو',

	'BH_MOVE_GROUP'	=> 'نقل هذا العضو إلى المجموعة &quot;%s&quot;', // %s will be a group name

	'BH_REASON'		=> 'سبب الحظر ( يظهر للمدراء والمشرفين ) &quot;%s&quot;', // %s will be the reason
	'BH_REASON_USER'	=> 'سبب الحظر ( يظهر للعضو ) &quot;%s&quot;', // %s will be the reason

	'BH_SUBMIT_SFS'	=> 'الإرسال إلى خدمة : منع المشاركات المُزعجة بالمنتدى',

	'BH_THIS_USER'	=> 'الحظر المتقدم لهذا العضو',

	'SFS_REPORT'	=> 'التبليغ بهذا العضو إلى خدمة : منع المشاركات المُزعجة بالمنتدى',
	'SURE_BAN'		=> 'هل أنت متأكد من حظر العضو <strong>%s</strong> ؟', // %s will be a username.

	'THIS_WILL'	=> 'النتيجة ستكون ',
));

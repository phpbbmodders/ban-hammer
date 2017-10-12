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
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
$lang = array_merge($lang, array(
	'ACP_BAN_EMAIL'		=> 'حظر البريد الإلكتروني للأعضاء ',
	'ACP_BAN_IP'		=> 'حظر عنوان الآي بي IP للأعضاء ',
	'ACP_BAN_IP_EXPLAIN'	=> '<strong>انتبه لهذه الخطوة.</strong> مُعظم المستخدمين العاديين بالمنازل لديهم عناوين IP فعال و سيكونوا بحاجة فقط إلى إعادة تشغيل جهاز الروتر / الموديم الخاص بهم للحصول على عنوان IP جديد. وفي اليوم التالي قد يتم تخصيص ذلك العنوان الـ IP إلى عضو تريده في منتداك. الأشخاص المُزعجين أيضا يستخدمون منافذ إنترنت مجهولة ( بروكسي ) أو أن شبكة Tor تحظر عنوان الـ IP غير حقيقي ( مزيف ).',

	'ACP_DEL_AVATAR'	=> 'حذف الصورة الشخصية للأعضاء ',
	'ACP_DEL_PRIVMSGS'	=> 'حذف الرسائل الخاصة للأعضاء ',
	'ACP_DEL_POSTS'		=> 'حذف مُشاركات الأعضاء ',
	'ACP_DEL_PROFILE'	=> 'حذف حقول الملف الشخصي للأعضاء ',
	'ACP_DEL_SIGNATURE'	=> 'حذف توقيع الأعضاء ',

	'ACP_GROUP_MISSING'	=> 'المجموعة &quot;%s&quot; غير موجودة.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'النقل إلى المجموعة ',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'حدد إسم المجموعة التي سيتم نقل الأعضاء المحظورين إليها. هذه المجموعة أيضاً ستكون مجموعتهم الإفتراضية.<br /><strong>إذا وجدت القائمة المُنسدلة خالية وفيها فقط <em>“لا توجد مجموعة مُحددة.”</em> , فهذا يعني أنك لم تقم بإنشاء أي مجموعات.</strong>',
	'BAN_LENGTH_EXPLAIN'	=> 'سيتم حظر المُستخدم بحسب تحديدك لأحد الخيارات المُتوفرة هنا في القائمة المُنسدلة. يُمكن أيضاً ضبطها عند حظر المُستخدم.',
	'SETTINGS_ERROR'		=> 'هناك خطأ أثناء حفظ الإعدادات. نرجوا التبليغ عن هذا الخطأ.',
	'SFS_API_KEY'			=> 'مفتاح الـAPI ',
	'SFS_API_KEY_EXPLAIN'	=> 'مفتاح الـAPI لخدمة : منع المُشاركات المُزعجة بالمنتدى. أنقر على هذا الرابط للحصول على المفتاح <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a> في حالة رغبتك بالتبليغ تلقائياً بالشخص المُزعج إلى خدمة StopForumSpam.',
	'SFS_NEEDS_CURL'		=> '<span style="color:red;">يجب تثبيت مكتبة اتصال السيرفرات cURL في سيرفرك لكي تستطيع إستخدام الخدمة : منع المُشاركات المُزعجة بالمنتدى.</span>',
));

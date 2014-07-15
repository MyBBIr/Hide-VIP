<?php
/**
 * MyBB 1.6
 * Copyright 2014 http://my-bb.ir , All Rights Reserved
 *
 * Website: http://my-bb.ir , http://www.iran-spe.com
 *
 */
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function hidevip_info()
{
	return array(
		"name"			=> "پنهان کردن محتوا برای افراد غیر وی ای پی",
		"description"	=> 'ویرایش به‌دست: <a href="http://my-bb.ir" target="_blank">AliReza_Tofighi</a>',
		"website"		=> "http://my-bb.ir",
		"author"		=> "homayoon ghasemi",
		"authorsite"	=> "http://www.iran-spe.com",
		"version"		=> "1.5",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}

function hidevip_activate()
{
	global $db, $mybb;

	// DELETE ALL SETTINGS TO AVOID DUPLICATES
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'hidevip_groups',
		'hidevip_fids'
	)");
	$db->delete_query("settinggroups", "name = 'hidevip'");

	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");
	
	$insertarray = array(
		'name' => 'hidevip',
		'title' => 'پنهان سازی محتوا از کاربران عادی',
		'description' => '',
		'disporder' => $rows+1,
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);

	$insertarray = array(
		'name' => 'hidevip_groups',
		'title' => 'گروه‌های مجاز برای دیدن',
		'description' => 'با کاما از هم جدا کنید',
		'optionscode' => 'textarea',
		'value' => '4,3',
		'disporder' => 0,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'hidevip_fids',
		'title' => 'انجمن‌هایی که بازدیدشان محدود شود',
		'description' => 'با کاما از هم جدا کنید',
		'optionscode' => 'textarea',
		'value' => '1,2',
		'disporder' => 0,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'hidevip_msg',
		'title' => 'پیام خطا',
		'description' => '',
		'optionscode' => 'textarea',
		'value' => 'شما نمی‌توانید انجمن‌های وی‌آی‌پی رو ببینید.',
		'disporder' => 0,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	rebuild_settings();
}

function hidevip_deactivate()
{
	global $db, $mybb;

	// DELETE ALL SETTINGS TO AVOID DUPLICATES
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'hidevip_groups',
		'hidevip_fids'
	)");
	$db->delete_query("settinggroups", "name = 'hidevip'");
	rebuild_settings();
}

$plugins->add_hook("parse_message", "hidevip");
function hidevip($m)
{
	global $mybb, $fid, $post, $postrow, $hidevipno;
	if(isset($hidevipno) && $hidevipno == 1)
	{
		return $m;
	}
	$forumid = 0;
	if(isset($fid))
	{
		$forumid = $fid;
	}
	elseif(isset($post))
	{
		$forumid = $post['fid'];
	}
	elseif(isset($postrow))
	{
		$forumid = $postrow['fid'];
	}
	
	if($forumid > 0)
	{
		if(
			in_array($forumid, explode(',' ,$mybb->settings['hidevip_fids'])) && 
			!in_array($mybb->user['usergroup'], explode(',', $mybb->settings['hidevip_groups']))
		  )
		{
			$m = $mybb->settings['hidevip_msg'];
		}
	}
	return $m;
}

$plugins->add_hook("postbit", "hidevip_postbit");

function hidevip_postbit($post)
{
	global $mybb, $usergroup, $hidevipno, $templates;
	$parser = new postParser;
	if(!empty($post['signature']))
	{
		$user = get_user($post['uid']);
		$post['signature'] = $user['signature'];
		$hidevipno = 1;
		$sig_parser = array(
			"allow_html" => $mybb->settings['sightml'],
			"allow_mycode" => $mybb->settings['sigmycode'],
			"allow_smilies" => $mybb->settings['sigsmilies'],
			"allow_imgcode" => $mybb->settings['sigimgcode'],
			"me_username" => $post['username'],
			"filter_badwords" => 1
		);

		if($usergroup['signofollow'])
		{
			$sig_parser['nofollow_on'] = 1;
		}

		$post['signature'] = $parser->parse_message($post['signature'], $sig_parser);
		eval("\$post['signature'] = \"".$templates->get("postbit_signature")."\";");
		$hidevipno = 0;
		unset($hidevipno);
	}
	return $post;
}

?>

<?php

/***************************************************************************
 *
 *	OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/forum_hooks.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2021 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	Adds the option to filter members by custom profile fields in the advanced member list page.
 *
 ***************************************************************************
 
****************************************************************************
	This program is protected software: you can make use of it under
	the terms of the OUGC Network EULA as detailed by the included
	"EULA.TXT" file.

	This program is distributed with the expectation that it will be
	useful, but WITH LIMITED WARRANTY; with a limited warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	OUGC Network EULA included in the "EULA.TXT" file for more details.

	You should have received a copy of the OUGC Network EULA along with
	the package which includes this file.  If not, see
	<https://ougc.network/eula.txt>.
****************************************************************************/

namespace OUGCCustomFSearch\ForumHooks;

function global_start()
{
	if(constant('THIS_SCRIPT') == 'memberlist.php')
	{
		global $templatelist;
	
		isset($templatelist) || $templatelist = '';

		$templatelist .= ', ougccustomfsearch_field_text, ougccustomfsearch_field, ougccustomfsearch_field_select_option, ougccustomfsearch_field_select, ougccustomfsearch';
	}
}

function memberlist_search()
{
	global $templates, $lang, $mybb;
	global $ougc_customfsearch, $filter;

	\OUGCCustomFSearch\Core\load_language();

	$fields = '';

	$pfcache = $mybb->cache->read('profilefields');

	if(is_array($pfcache))
	{
		foreach($pfcache as $profilefield)
		{
			if(!is_member($profilefield['viewableby']))
			{
				continue;
			}

			$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);

			$profilefield['name'] = htmlspecialchars_uni($profilefield['name']);

			$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);

			$thing = explode("\n", $profilefield['type'], "2");

			$type = $thing[0];

			if(isset($thing[1]))
			{
				$options = $thing[1];
			}
			else
			{
				$options = array();
			}

			$field = "fid{$profilefield['fid']}";

			$select = '';

			if($errors)
			{
				if(!isset($mybb->input['profile_fields'][$field]))
				{
					$mybb->input['profile_fields'][$field] = '';
				}
				$userfield = $mybb->input['profile_fields'][$field];
			}
			else
			{
				$userfield = $user[$field];
			}

			if($type == 'multiselect' || $type == 'select' || $type == 'radio' || $type == 'checkbox')
			{
				$expoptions = explode("\n", $options);

				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);
						$sel = '';
						if($val == htmlspecialchars_uni($userfield))
						{
							$sel = ' selected="selected"';
						}

						$select .= eval($templates->render('ougccustomfsearch_field_select_option'));
					}

					if(!$profilefield['length'])
					{
						$profilefield['length'] = 1;
					}

					$input = eval($templates->render('ougccustomfsearch_field_select'));
				}
			}
			else
			{
				$value = htmlspecialchars_uni($filter[$field]);

				$maxlength = '';

				if($type != 'textarea' && $profilefield['maxlength'] > 0)
				{
					$maxlength = " maxlength=\"{$profilefield['maxlength']}\"";
				}

				$input = eval($templates->render('ougccustomfsearch_field_text'));
			}

			$fields .= eval($templates->render('ougccustomfsearch_field'));

			$code = $select = $val = $options = $expoptions = $useropts = '';

			$seloptions = [];
		}
	}

	$ougc_customfsearch = eval($templates->render('ougccustomfsearch'));
}

function memberlist_start()
{
	memberlist_intermediate();
}

function memberlist_intermediate()
{
	global $mybb, $db;
	GLOBAL $filter, $search_query, $search_url;

	if(!$mybb->get_input('do_customfsearch', \MyBB::INPUT_INT))
	{
		return;
	}

	$filter = $mybb->get_input('profile_fields', \MyBB::INPUT_ARRAY);

	$where = [];

	foreach($filter as $key => $value)
	{
		if(empty($value))
		{
			continue;
		}

		$field = 'fid'.(int)str_replace('fid', '', $key);

		$input['do_customfsearch'] = 1;

		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				$input["profile_fields[{$field}][{$k}]"] = htmlspecialchars_uni($v);
			}
	
			$values = implode("', '", array_map([$db, 'escape_string'], array_map('my_strtolower', $value)));

			$where[$field] = "LOWER(f.{$field}) IN ('{$values}')";
		}
		else
		{
			$input["profile_fields[{$field}]"] = htmlspecialchars_uni($value);

			$where[$field] = "LOWER(f.{$field}) LIKE '%{$db->escape_string_like(my_strtolower($value))}%'";
		}
	}

	if($where)
	{
		\OUGCCustomFSearch\Core\set_url($search_url);

		$search_url = \OUGCCustomFSearch\Core\build_url($input);

		$where = implode(' AND ', $where);

		$search_query .= " AND {$where}";

		control_object($db, '
			function simple_select($table, $fields="*", $conditions="", $options=array())
			{
				static $done = false;
		
				if(!$done && $table == "users u" && $fields == "COUNT(*) AS users")
				{
					global $db;

					$table .= " LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)";

					$done = true;
				}
		
				return parent::simple_select($table, $fields, $conditions, $options);
			}
		');
	}
}
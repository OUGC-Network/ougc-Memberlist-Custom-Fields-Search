<?php

/***************************************************************************
 *
 *	OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/core.php)
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

namespace OUGCCustomFSearch\Core;

function load_language()
{
	global $lang;

	isset($lang->setting_group_ougc_customfsearch) || $lang->load('ougc_customfsearch');
}

function load_pluginlibrary()
{
	global $PL, $lang;

	\OUGCCustomFSearch\Core\load_language();

	if($file_exists = file_exists(PLUGINLIBRARY))
	{
		global $PL;
	
		$PL or require_once PLUGINLIBRARY;
	}

	$_info = \OUGCCustomFSearch\Admin\_info();

	if(!$file_exists || $PL->version < $_info['pl']['version'])
	{
		flash_message($lang->sprintf($lang->ougc_customfsearch_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']), 'error');

		admin_redirect('index.php?module=config-plugins');
	}
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

	foreach($definedUserFunctions as $callable)
	{
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

		if(substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase.'\\')
		{
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

			if(is_numeric(substr($hookName, -2)))
			{
                $hookName = substr($hookName, 0, -2);
			}
			else
			{
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

// Set url
function set_url($url=null)
{
	static $current_url = '';

	if(($url = trim($url)))
	{
		$current_url = $url;
	}

	return $current_url;
}

// Set url
function get_url()
{
	return set_url();
}

// Build an url parameter
function build_url($urlappend=[], $amp='&amp;')
{
	global $PL;

	$PL or require_once PLUGINLIBRARY;

	if($urlappend && !is_array($urlappend))
	{
		$urlappend = explode('=', $urlappend);

		$urlappend = [$urlappend[0] => $urlappend[1]];
	}

	return $PL->url_append(get_url(), $urlappend, $amp, true);
}
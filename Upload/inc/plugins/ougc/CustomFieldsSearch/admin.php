<?php

/***************************************************************************
 *
 *    OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2021 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Adds the option to filter members by custom profile fields in the advanced member list page.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is protected software: you can make use of it under
 * the terms of the OUGC Network EULA as detailed by the included
 * "EULA.TXT" file.
 *
 * This program is distributed with the expectation that it will be
 * useful, but WITH LIMITED WARRANTY; with a limited warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * OUGC Network EULA included in the "EULA.TXT" file for more details.
 *
 * You should have received a copy of the OUGC Network EULA along with
 * the package which includes this file.  If not, see
 * <https://ougc.network/eula.txt>.
 ****************************************************************************/

namespace ougc\CustomFieldsSearch\Admin;

use DirectoryIterator;

use function ougc\CustomFieldsSearch\Core\load_language;
use function ougc\CustomFieldsSearch\Core\load_pluginlibrary;

use const ougc\CustomFieldsSearch\ROOT;

function _info()
{
    global $lang;

    load_language();

    return [
        'name' => 'OUGC Member List Advanced Search',
        'description' => $lang->setting_group_ougc_customfsearch_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.2',
        'versioncode' => 1802,
        'compatibility' => '18*',
        'codename' => 'ougc_customfsearch',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ]
    ];
}

function _activate()
{
    global $PL, $lang, $cache, $db, $settings;

    load_pluginlibrary();

    /*$PL->settings('ougc_customfsearch', $lang->setting_group_ougc_customfsearch, $lang->setting_group_ougc_customfsearch_desc, array(
        'forums'				=> array(
           'title'			=> $lang->setting_ougc_customfsearch_forums,
           'description'	=> $lang->setting_ougc_customfsearch_forums_desc,
           'optionscode'	=> 'forumselect',
           'value'			=> -1
        ),
    ));*/

    // Add settings group
    $settingsContents = file_get_contents(ROOT . '/settings.json');

    $settingsData = json_decode($settingsContents, true);

    foreach ($settingsData as $settingKey => &$settingData) {
        if (empty($lang->{"setting_ougcCustomFieldsSearch_{$settingKey}"})) {
            continue;
        }

        if ($settingData['optionscode'] == 'select' || $settingData['optionscode'] == 'checkbox') {
            foreach ($settingData['options'] as $optionKey) {
                $settingData['optionscode'] .= "\n{$optionKey}={$lang->{"setting_ougcCustomFieldsSearch_{$settingKey}_{$optionKey}"}}";
            }
        }

        $settingData['title'] = $lang->{"setting_ougcCustomFieldsSearch_{$settingKey}"};
        $settingData['description'] = $lang->{"setting_ougcCustomFieldsSearch_{$settingKey}_desc"};
    }

    $PL->settings(
        'ougcCustomFieldsSearch',
        $lang->setting_group_ougcCustomFieldsSearch,
        $lang->setting_group_ougcCustomFieldsSearch_desc,
        $settingsData
    );

    // Add templates
    $templatesDirIterator = new DirectoryIterator(ROOT . '/templates');

    $templates = [];

    foreach ($templatesDirIterator as $template) {
        if (!$template->isFile()) {
            continue;
        }

        $pathName = $template->getPathname();

        $pathInfo = pathinfo($pathName);

        if ($pathInfo['extension'] === 'html') {
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
        }
    }

    if ($templates) {
        $PL->templates('ougccustomfsearch', 'OUGC Custom Fields Search', $templates);
    }

    // Insert/update version into cache
    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    $_info = _info();

    if (!isset($plugins['customfsearch'])) {
        $plugins['customfsearch'] = $_info['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/

    /*~*~* RUN UPDATES END *~*~*/

    $plugins['customfsearch'] = $_info['versioncode'];

    $cache->update('ougc_plugins', $plugins);
}

function _deactivate()
{
}

function _install()
{
}

function _is_installed()
{
    global $cache;

    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    return isset($plugins['customfsearch']);
}

function _uninstall()
{
    global $db, $PL, $cache;

    load_pluginlibrary();

    $PL->settings_delete('ougc_customfsearch');

    $PL->settings_delete('ougcPrivateThreads');

    $PL->templates_delete('ougccustomfsearch');

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['customfsearch'])) {
        unset($plugins['customfsearch']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $cache->delete('ougc_plugins');
    }

    $cache->delete('ougc_customfsearch');

    $cache->delete('ougcCustomFieldsSearch');
}
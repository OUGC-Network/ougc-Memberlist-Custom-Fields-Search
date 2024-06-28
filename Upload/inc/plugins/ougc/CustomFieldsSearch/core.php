<?php

/***************************************************************************
 *
 *    OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/core.php)
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

declare(strict_types=1);

namespace ougc\CustomFieldsSearch\Core;

use function ougc\CustomFieldsSearch\Admin\_info;

use const ougc\CustomFieldsSearch\ROOT;
use const ougc\CustomFieldsSearch\Core\SETTINGS;
use const ougc\CustomFieldsSearch\Core\DEBUG;

const URL = 'memberlist.php';

function load_language()
{
    global $lang;

    isset($lang->setting_group_ougc_customfsearch) || $lang->load('ougc_customfsearch');
}

function load_pluginlibrary()
{
    global $PL, $lang;

    load_language();

    if ($file_exists = file_exists(PLUGINLIBRARY)) {
        global $PL;

        $PL or require_once PLUGINLIBRARY;
    }

    $_info = _info();

    if (!$file_exists || $PL->version < $_info['pl']['version']) {
        flash_message(
            $lang->sprintf($lang->ougc_customfsearch_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function getSetting(string $settingKey = '')
{
    global $mybb;

    return isset(SETTINGS[$settingKey]) ? SETTINGS[$settingKey] : (
    isset($mybb->settings['ougcCustomFieldsSearch_' . $settingKey]) ? $mybb->settings['ougcCustomFieldsSearch_' . $settingKey] : false
    );
}

function getTemplateName(string $templateName = ''): string
{
    $templatePrefix = '';

    if ($templateName) {
        $templatePrefix = '_';
    }

    return "ougccustomfsearch{$templatePrefix}{$templateName}";
}

function getTemplate(string $templateName = '', bool $enableHTMLComments = true): string
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/templates/{$templateName}.html";

        $templateContents = file_get_contents($filePath);

        $templates->cache[getTemplateName($templateName)] = $templateContents;
    } elseif (my_strpos($templateName, '/') !== false) {
        $templateName = substr($templateName, strpos($templateName, '/') + 1);
    }

    return $templates->render(getTemplateName($templateName), true, $enableHTMLComments);
}

function urlHandler(string $newUrl = ''): string
{
    static $setUrl = URL;

    if (($newUrl = trim($newUrl))) {
        $setUrl = $newUrl;
    }

    return $setUrl;
}

function urlHandlerSet(string $newUrl): string
{
    return urlHandler($newUrl);
}

function urlHandlerGet(): string
{
    return urlHandler();
}

function urlHandlerBuild(array $urlAppend = [], bool $fetchImportUrl = false, bool $encode = true): string
{
    global $PL;

    if (!is_object($PL)) {
        $PL or require_once PLUGINLIBRARY;
    }

    if ($fetchImportUrl === false) {
        if ($urlAppend && !is_array($urlAppend)) {
            $urlAppend = explode('=', $urlAppend);
            $urlAppend = [$urlAppend[0] => $urlAppend[1]];
        }
    }

    return $PL->url_append(urlHandlerGet(), $urlAppend, '&amp;', $encode);
}

function cachedSearchClauseGet(string $uniqueIdentifier, bool $refreshUpdateStamp = false): string
{
    global $mybb;

    static $searchResultsCache = null;

    if ($searchResultsCache === null) {
        $searchResultsCache = $mybb->cache->read('ougcCustomFieldsSearch');
    }

    if (isset($searchResultsCache[$uniqueIdentifier]) && ($searchResultsCache[$uniqueIdentifier]['timeStamp'] + getSetting(
                'cacheIntervalSeconds'
            ) > \TIME_NOW)) {
        $queryClause = $searchResultsCache[$uniqueIdentifier]['queryClause'];

        if ($refreshUpdateStamp) {
            cachedSearchClauseUpdate($uniqueIdentifier);
        }

        return $queryClause;
    }

    return '';
}

function cachedSearchClausePut(string $uniqueIdentifier, string $queryClause): bool
{
    global $mybb;

    static $searchResultsCache = null;

    if ($searchResultsCache === null) {
        $searchResultsCache = $mybb->cache->read('ougcCustomFieldsSearch');
    }

    if (!is_array($searchResultsCache)) {
        $searchResultsCache = [];
    }

    if (!isset($searchResultsCache[$uniqueIdentifier])) {
        $searchResultsCache[$uniqueIdentifier] = [];
    }

    $searchResultsCache[$uniqueIdentifier] = [
        'timeStamp' => \TIME_NOW,
        'queryClause' => $queryClause,
    ];

    $mybb->cache->update('ougcCustomFieldsSearch', $searchResultsCache);

    return true;
    // TODO, purge obsolete objects
}

function cachedSearchClauseUpdate(string $uniqueIdentifier, array $updateData = ['timeStamp' => \TIME_NOW]): bool
{
    global $mybb;

    static $searchResultsCache = null;

    if ($searchResultsCache === null) {
        $searchResultsCache = $mybb->cache->read('ougcCustomFieldsSearch');
    }

    if (!is_array($searchResultsCache)) {
        $searchResultsCache = [];
    }

    $updateCache = false;

    if (isset($searchResultsCache[$uniqueIdentifier]['queryClause'])) {
        $searchResultsCache[$uniqueIdentifier] = array_merge(
            $searchResultsCache[$uniqueIdentifier],
            $updateData
        );

        $updateCache = true;
    } elseif (isset($searchResultsCache[$uniqueIdentifier])) {
        unset($searchResultsCache[$uniqueIdentifier]);

        $updateCache = true;
    }

    if ($updateCache) {
        $mybb->cache->update('ougcCustomFieldsSearch', $searchResultsCache);
    }

    return $updateCache;
}

function cachedSearchClausesPurge(): bool
{
    global $mybb;

    if (!($cacheIntervalSeconds = getSetting('cacheIntervalSeconds'))) {
        return false;
    }

    static $searchResultsCache = null;

    if ($searchResultsCache === null) {
        $searchResultsCache = $mybb->cache->read('ougcCustomFieldsSearch');
    }

    $updateCache = false;

    if (is_array($searchResultsCache)) {
        foreach ($searchResultsCache as $uniqueIdentifier => $querySearchData) {
            if (!($querySearchData['timeStamp'] + getSetting(
                    'cacheIntervalSeconds'
                ) > \TIME_NOW)) {
                unset($searchResultsCache[$uniqueIdentifier]);

                $updateCache = true;
            }
        }
    }

    if ($updateCache) {
        $mybb->cache->update('ougcCustomFieldsSearch', $searchResultsCache);
    }

    return $updateCache;
}
<?php

/***************************************************************************
 *
 *    ougc Member List Advanced Search plugin (/inc/plugins/ougc/CustomFieldsSearch/core.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2021 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Allow more complex searches in the member list advanced search page.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\CustomFieldsSearch\Core;

use AbstractPdoDbDriver;
use DB_SQLite;
use OUGC_ProfiecatsCache;

use ReflectionProperty;

use function ougc\CustomFieldsSearch\Admin\_info;

use const ougc\CustomFieldsSearch\ROOT;
use const TIME_NOW;

const URL = 'memberlist.php';

const FIELDS_DATA = [
    'usergroups' => [
        'ougcCustomFieldsSearchCanSearchGroupIDs' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'ougcCustomFieldsSearchCanViewProfilesGroupIDs' => [
            'type' => 'TEXT',
            'null' => true,
        ],
    ],
    'users' => [
        'ougcCustomFieldsSearchProfilePrivacy' => [
            'type' => 'VARCHAR',
            'size' => 10,
            'default' => '',
        ],
    ]
];

const PRIVACY_TYPES = [
    1 => 'BuddyList',
    2 => 'IgnoreList',
    3 => 'Guests',
    4 => 'Users',
];

const PRIVACY_TYPE_ONLY_BUDDY_LIST = 1;

const PRIVACY_TYPE_BLOCK_IGNORE_LIST = 2;

const PRIVACY_TYPE_BLOCK_GUESTS = 3;

const PRIVACY_TYPE_BLOCK_USERS = 4;

function load_language()
{
    global $lang;

    isset($lang->ougc_customfsearch) || $lang->load('ougc_customfsearch');
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

function sanitizeIntegers(array $dataObject): array
{
    foreach ($dataObject as &$objectValue) {
        $objectValue = (int)$objectValue;
    }

    return array_filter($dataObject);
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
            ) > TIME_NOW)) {
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
        'timeStamp' => TIME_NOW,
        'queryClause' => $queryClause,
    ];

    $mybb->cache->update('ougcCustomFieldsSearch', $searchResultsCache);

    return true;
    // TODO, purge obsolete objects
}

function cachedSearchClauseUpdate(string $uniqueIdentifier, array $updateData = ['timeStamp' => TIME_NOW]): bool
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
                ) > TIME_NOW)) {
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

function profilePrivacyTypes(): array
{
    static $profilePrivacyTypes = PRIVACY_TYPES;

    $enabledProfilePrivacyTypes = array_flip(explode(',', getSetting('profilePrivacyTypes')));

    foreach ($profilePrivacyTypes as $privacyTypeKey => $privacyTypeValue) {
        if (!isset($enabledProfilePrivacyTypes[$privacyTypeValue])) {
            unset($profilePrivacyTypes[$privacyTypeKey]);
        }
    }

    return $profilePrivacyTypes;
}

function getProfileFieldsCache(): array
{
    global $mybb;
    global $profiecats;

    if (class_exists('OUGC_ProfiecatsCache') && $profiecats instanceof OUGC_ProfiecatsCache) {
        return $profiecats->cache['original'];
    }

    return (array)$mybb->cache->read('profilefields');
}

function getUserAvatarLink(array $userData): string
{
    global $mybb, $plugins;

    $avatarUrl = $mybb->settings['bburl'] . '/images/default_avatar.png';

    if (!empty($userData['avatar'])) {
        $avatarUrl = $userData['avatar'];

        if ($userData['avatartype'] === 'upload' && my_strpos($userData['avatar'], '://') === false) {
            $avatarUrl = "{$mybb->settings['bburl']}/{$avatarUrl}";
        }
    }

    $hook_arguments = [
        'userData' => &$userData,
        'avatarUrl' => &$avatarUrl
    ];

    $plugins->run_hooks('ougc_member_list_custom_fields_search_get_user_avatar_link_end', $hook_arguments);

    return $avatarUrl;
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com )
function control_object(&$obj, $code)
{
    static $cnt = 0;
    $newname = '_objcont_ougc_member_list_advanced_search_' . (++$cnt);
    $objserial = serialize($obj);
    $classname = get_class($obj);
    $checkstr = 'O:' . strlen($classname) . ':"' . $classname . '":';
    $checkstr_len = strlen($checkstr);
    if (substr($objserial, 0, $checkstr_len) == $checkstr) {
        $vars = array();
        // grab resources/object etc, stripping scope info from keys
        foreach ((array)$obj as $k => $v) {
            if ($p = strrpos($k, "\0")) {
                $k = substr($k, $p + 1);
            }
            $vars[$k] = $v;
        }
        if (!empty($vars)) {
            $code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
        }
        eval('class ' . $newname . ' extends ' . $classname . ' {' . $code . '}');
        $obj = unserialize('O:' . strlen($newname) . ':"' . $newname . '":' . substr($objserial, $checkstr_len));
        if (!empty($vars)) {
            $obj->___setvars($vars);
        }
    }
    // else not a valid object or PHP serialize has changed
}

// explicit workaround for PDO, as trying to serialize it causes a fatal error (even though PHP doesn't complain over serializing other resources)
if ($GLOBALS['db'] instanceof AbstractPdoDbDriver) {
    $GLOBALS['AbstractPdoDbDriver_lastResult_prop'] = new ReflectionProperty('AbstractPdoDbDriver', 'lastResult');
    $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setAccessible(true);
    function control_db($code)
    {
        global $db;
        $linkvars = array(
            'read_link' => $db->read_link,
            'write_link' => $db->write_link,
            'current_link' => $db->current_link,
        );
        unset($db->read_link, $db->write_link, $db->current_link);
        $lastResult = $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->getValue($db);
        $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setValue($db, null); // don't let this block serialization
        control_object($db, $code);
        foreach ($linkvars as $k => $v) {
            $db->$k = $v;
        }
        $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setValue($db, $lastResult);
    }
} elseif ($GLOBALS['db'] instanceof DB_SQLite) {
    function control_db($code)
    {
        global $db;
        $oldLink = $db->db;
        unset($db->db);
        control_object($db, $code);
        $db->db = $oldLink;
    }
} else {
    function control_db($code)
    {
        control_object($GLOBALS['db'], $code);
    }
}

<?php

/***************************************************************************
 *
 *    ougc Member List Advanced Search plugin (/inc/plugins/ougc/CustomFieldsSearch/forum_hooks.php)
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

namespace ougc\CustomFieldsSearch\Hooks\Forum;

use MyBB;

use function ougc\CustomFieldsSearch\Core\cachedSearchClauseGet;
use function ougc\CustomFieldsSearch\Core\cachedSearchClausePut;
use function ougc\CustomFieldsSearch\Core\cachedSearchClausesPurge;
use function ougc\CustomFieldsSearch\Core\control_db;
use function ougc\CustomFieldsSearch\Core\getProfileFieldsCache;
use function ougc\CustomFieldsSearch\Core\getSetting;
use function ougc\CustomFieldsSearch\Core\getTemplate;
use function ougc\CustomFieldsSearch\Core\getUserAvatarLink;
use function ougc\CustomFieldsSearch\Core\profilePrivacyTypes;
use function ougc\CustomFieldsSearch\Core\sanitizeIntegers;
use function ougc\CustomFieldsSearch\Core\urlHandlerBuild;
use function ougc\CustomFieldsSearch\Core\load_language;
use function ougc\CustomFieldsSearch\Core\urlHandler;

use const ougc\CustomFieldsSearch\Core\PRIVACY_TYPE_BLOCK_GUESTS;
use const ougc\CustomFieldsSearch\Core\PRIVACY_TYPE_BLOCK_IGNORE_LIST;
use const ougc\CustomFieldsSearch\Core\PRIVACY_TYPE_BLOCK_USERS;
use const ougc\CustomFieldsSearch\Core\PRIVACY_TYPE_ONLY_BUDDY_LIST;

function global_start()
{
    if (constant('THIS_SCRIPT') === 'memberlist.php') {
        global $templatelist;

        isset($templatelist) || $templatelist = '';

        $templatelist .= ', ougccustomfsearch_field_text, ougccustomfsearch_field, ougccustomfsearch_field_select_option, ougccustomfsearch_field_select, ougccustomfsearch, ougccustomfsearch_groupsSelectOption, ougccustomfsearch_groupsSelect, ougccustomfsearch_field_checkbox, ougccustomfsearch_urlDescription';
    }

    cachedSearchClausesPurge();
}

function usercp_profile_end(&$userData = []): bool
{
    global $mybb;
    global $ougcCustomFieldsSearchProfilePrivacyInput;

    $ougcCustomFieldsSearchProfilePrivacyInput = '';

    if (!is_array($userData)) {
        $userData = &$mybb->user;
    }

    if (!is_member(getSetting('groupsCanManageProfilePrivacy'), $userData)) {
        return false;
    }

    global $lang;

    load_language();

    $privacyItemRows = '';

    if ($mybb->request_method === 'post') {
        $privacySettings = sanitizeIntegers(
            $mybb->get_input('ougcCustomFieldsSearchProfilePrivacyInput', MyBB::INPUT_ARRAY)
        );
    } else {
        $privacySettings = array_flip(
            sanitizeIntegers(explode(',', (string)$userData['ougcCustomFieldsSearchProfilePrivacy']))
        );
    }

    foreach (profilePrivacyTypes() as $privacyType => $privacyKey) {
        $langString = $lang->{"ougcCustomFieldsSearchProfilePrivacy{$privacyKey}"};

        $checkedElement = '';

        if (isset($privacySettings[$privacyType])) {
            $checkedElement = ' checked="checked"';
        }

        $privacyItemRows .= eval(getTemplate('controlPanelProfilePrivacyRow'));
    }

    $ougcCustomFieldsSearchProfilePrivacyInput = eval(getTemplate('controlPanelProfilePrivacy'));

    return true;
}

function modcp_editprofile_end(): bool
{
    global $user;

    usercp_profile_end($user);

    return true;
}

function member_profile_start(): bool
{
    global $memprofile;

    require_once MYBB_ROOT . 'inc/functions_modcp.php';

    if (modcp_can_manage_user($memprofile['uid'])) {
        return false;
    }

    global $mybb;

    $allowedGroupsToSearch = sanitizeIntegers(
        explode(',', (string)$mybb->usergroup['ougcCustomFieldsSearchCanViewProfilesGroupIDs'])
    );

    global $lang;

    if ($allowedGroupsToSearch && !is_member($allowedGroupsToSearch, $memprofile)) {
        error($lang->error_nomember);
    }

    $currentUserID = (int)$mybb->user['uid'];

    if (!is_member(
            getSetting('groupsCanManageProfilePrivacy'),
            $memprofile
        ) || $currentUserID === (int)$memprofile['uid'] || empty($memprofile['ougcCustomFieldsSearchProfilePrivacy'])) {
        return false;
    }

    $userPrivacySettings = array_flip(
        sanitizeIntegers(explode(',', (string)$memprofile['ougcCustomFieldsSearchProfilePrivacy']))
    );

    $enabledProfilePrivacyTypes = profilePrivacyTypes();

    if (isset($userPrivacySettings[PRIVACY_TYPE_ONLY_BUDDY_LIST]) && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_ONLY_BUDDY_LIST])) {
        if (!in_array($currentUserID, sanitizeIntegers(explode(',', (string)$memprofile['buddylist'])))) {
            error($lang->error_nomember);
        }
    }

    if (isset($userPrivacySettings[PRIVACY_TYPE_BLOCK_IGNORE_LIST]) && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_IGNORE_LIST])) {
        if (in_array($currentUserID, sanitizeIntegers(explode(',', (string)$memprofile['ignorelist'])))) {
            error($lang->error_nomember);
        }
    }

    if (isset($userPrivacySettings[PRIVACY_TYPE_BLOCK_GUESTS]) && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_GUESTS])) {
        if (!$currentUserID) {
            error($lang->error_nomember);
        }
    }

    if (isset($userPrivacySettings[PRIVACY_TYPE_BLOCK_USERS]) && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_USERS])) {
        if ($currentUserID) {
            error($lang->error_nomember);
        }
    }

    return true;
}

function memberlist_search()
{
    global $templates, $lang, $mybb;
    global $ougc_customfsearch, $ougcCustomFieldSearchFilters, $errors;

    load_language();

    $searchableFields = '';

    $alternativeBackgroundGroups = alt_trow();

    $groupSelect = $groupSelectOptions = $searchGroupsCheckedPrimary = $searchGroupsCheckedAdditional = $searchGroupsCheckedBoth = '';

    if ($mybb->get_input('searchGroups') === 'primary') {
        $searchGroupsCheckedPrimary = 'checked="checked"';
    } elseif ($mybb->get_input('searchGroups') === 'additional') {
        $searchGroupsCheckedAdditional = 'checked="checked"';
    } else {
        $searchGroupsCheckedBoth = 'checked="checked"';
    }

    $allowedGroupsToSearch = sanitizeIntegers(
        explode(',', (string)$mybb->usergroup['ougcCustomFieldsSearchCanSearchGroupIDs'])
    );

    foreach ($mybb->cache->read('usergroups') as $groupID => $groupData) {
        $groupID = (int)$groupID;

        if (empty($groupData['showmemberlist']) || $groupID === 1 || ($allowedGroupsToSearch && !in_array(
                    $groupID,
                    $allowedGroupsToSearch
                ))) {
            continue;
        }

        $optionSelect = '';

        if (isset($mybb->input['customSearchGroups'][$groupID])) {
            $optionSelect = 'selected="selected"';
        }

        $groupName = htmlspecialchars_uni($groupData['title']);

        $groupSelectOptions .= eval(getTemplate('groupsSelectOption'));
    }

    $groupSelect = eval(getTemplate('groupsSelect'));

    $customFieldsCache = getProfileFieldsCache();

    if ($customFieldsCache) {
        $searchTypeFields = $mybb->get_input('searchTypeField', MyBB::INPUT_ARRAY);

        $alternativeBackground = alt_trow(true);

        $ignoredProfileFieldsIDs = array_flip(explode(',', (string)getSetting('ignoredProfileFieldsIDs')));

        foreach ($customFieldsCache as $customFieldData) {
            if (!is_member(
                    $customFieldData['viewableby']
                ) || ((isset($ignoredProfileFieldsIDs[$customFieldData['fid']]) || isset($ignoredProfileFieldsIDs[-1])) && !is_member(
                        getSetting('bypassIgnoredProfileFields')
                    ))) {
                continue;
            }

            $customFieldData['type'] = htmlspecialchars_uni($customFieldData['type']);

            $customFieldData['name'] = htmlspecialchars_uni($customFieldData['name']);

            $customFieldData['description'] = htmlspecialchars_uni($customFieldData['description']);

            $fieldTypeValues = explode("\n", $customFieldData['type'], 2);

            $fieldType = $fieldTypeValues[0] ?? '';

            $fieldOptions = $fieldTypeValues[1] ?? [];

            $customFieldKey = "fid{$customFieldData['fid']}";

            $selectOptions = '';

            $userFieldValue = '';

            if (!empty($errors)) {
                if (!isset($mybb->input['customSearchFields'][$customFieldKey])) {
                    $mybb->input['customSearchFields'][$customFieldKey] = '';
                }

                $userFieldValue = $mybb->input['customSearchFields'][$customFieldKey];
            }

            if (in_array($fieldType, ['multiselect', 'select', 'radio', 'checkbox'])) {
                foreach (explode("\n", (string)$fieldOptions) as $optionValue) {
                    $optionValue = trim($optionValue);
                    $optionValue = str_replace("\n", "\\n", $optionValue);
                    $optionSelect = '';

                    if ($optionValue == $userFieldValue) {
                        $optionSelect = ' selected="selected"';
                    }

                    $selectOptions .= eval(getTemplate('field_select_option'));
                }

                if (!$customFieldData['length']) {
                    $customFieldData['length'] = 1;
                }

                $searchTypeCheckedExactly = 'checked="checked"';

                $searchTypeCheckedDoesNotHave = '';

                if (isset($searchTypeFields[$customFieldKey]) && $searchTypeFields[$customFieldKey] === 'doesNotHave') {
                    $searchTypeCheckedExactly = '';

                    $searchTypeCheckedDoesNotHave = 'checked="checked"';
                }

                $inputField = eval(getTemplate('field_select'));
            } elseif (in_array($fieldType, ['file'])) {
                $filterValue = htmlspecialchars_uni($ougcCustomFieldSearchFilters[$customFieldKey]);

                $searchTypeCheckedIgnore = 'checked="checked"';

                $searchTypeCheckedHas = $searchTypeCheckedDoesNotHave = '';

                if ($searchTypeFields[$customFieldKey] === 'has') {
                    $searchTypeCheckedHas = 'checked="checked"';

                    $searchTypeCheckedIgnore = '';
                } elseif ($searchTypeFields[$customFieldKey] === 'doesNotHave') {
                    $searchTypeCheckedDoesNotHave = 'checked="checked"';

                    $searchTypeCheckedIgnore = '';
                }

                $labelTitleHas = $lang->sprintf(
                    $lang->ougc_customfsearch_formFieldTypeHasUploaded,
                    $customFieldData['name']
                );

                $labelTitleDoesNotHave = $lang->sprintf(
                    $lang->ougc_customfsearch_formFieldTypeDoesNotHaveUpload,
                    $customFieldData['name']
                );

                $inputField = eval(getTemplate('field_checkbox'));
            } else {
                //text, textarea
                $filterValue = htmlspecialchars_uni($ougcCustomFieldSearchFilters[$customFieldKey] ?? '');

                $maxLength = '';

                if ($fieldType != 'textarea' && $customFieldData['maxlength'] > 0) {
                    $maxLength = " maxlength=\"{$customFieldData['maxlength']}\"";
                }

                $searchTypeCheckedMatches = $searchTypeCheckedDoesNotMatch = '';

                if (isset($searchTypeFields[$customFieldKey]) && $searchTypeFields[$customFieldKey] === 'has') {
                    $searchTypeCheckedMatches = 'checked="checked"';
                } elseif (isset($searchTypeFields[$customFieldKey]) && $searchTypeFields[$customFieldKey] === 'doesNotMatch') {
                    $searchTypeCheckedDoesNotMatch = 'checked="checked"';
                }

                $inputField = eval(getTemplate('field_text'));
            }

            $searchableFields .= eval(getTemplate('field'));

            $selectOptions = $optionValue = $fieldOptions = $expoptions = $useropts = '';

            $alternativeBackground = alt_trow();
        }
    }

    $searchTypeCheckedStrict = 'checked="checked"';

    $searchTypeCheckedAny = $searchTypeCheckedXor = '';

    if ($mybb->get_input('searchType') === 'any') {
        $searchTypeCheckedStrict = '';

        $searchTypeCheckedAny = 'checked="checked"';
    } elseif ($mybb->get_input('searchType') === 'xor') {
        $searchTypeCheckedStrict = '';

        $searchTypeCheckedXor = 'checked="checked"';
    }

    $ougc_customfsearch = eval(getTemplate());
}

function memberlist_start()
{
    global $mybb;
    //memberlist_intermediate90();

    $mybb->input['customSearchFields'] = $mybb->get_input('customSearchFields', MyBB::INPUT_ARRAY);

    $mybb->input['customSearchGroups'] = $mybb->get_input('customSearchGroups', MyBB::INPUT_ARRAY);

    if (isset($mybb->input['doCustomFieldsSearchGlobal'])) {
        $searchField = $mybb->get_input('searchField');

        switch ($searchField) {
            case'username':
                if (mb_strpos(getSetting('searchFields'), 'username') !== false) {
                    $mybb->input['username'] = $mybb->get_input('keyword');
                    $mybb->input['sort'] = 'username';
                }
            case'website':
                if (mb_strpos(getSetting('searchFields'), 'website') !== false) {
                    $mybb->input['website'] = $mybb->get_input('keyword');
                    $mybb->input['sort'] = 'website';
                }
                break;
        }

        if (mb_strpos($searchField, 'fid') === 0) {
            $fieldID = (int)str_replace('fid', '', $searchField);

            $customFieldKey = "fid{$fieldID}";

            $mybb->input['customSearchFields'][$customFieldKey] = $mybb->get_input('keyword');

            $mybb->input['searchTypeField'][$customFieldKey] = 'matches';

            $mybb->input['sort'] = 'username';
        }
    }

    control_db(
        'function simple_select($table, $fields = "*", $conditions = "", $options = array())
{
    static $done = false;

    if (!$done && $table == "users u" && $fields == "COUNT(*) AS users") {
        global $db;

        $table .= " LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)";

        $done = true;
    }

    return parent::simple_select($table, $fields, $conditions, $options);
}'
    );
}

function memberlist_intermediate90(): bool
{
    global $mybb, $db;
    global $ougcCustomFieldSearchFilters, $ougcCustomFieldSearchUrlDescription, $search_query, $ougcCustomFieldSearchUrl, $ougcCustomFieldSearchUrlParams;

    $ougcCustomFieldSearchUrlDescription = '';

    $allowedGroupsToSearch = sanitizeIntegers(
        explode(',', (string)$mybb->usergroup['ougcCustomFieldsSearchCanSearchGroupIDs'])
    );

    if ($allowedGroupsToSearch) {
        $whereClausesGroups = [];

        foreach ($allowedGroupsToSearch as $groupID) {
            $whereClausesGroups[] = "u.usergroup='{$groupID}'";

            switch ($db->type) {
                case 'pgsql':
                case 'sqlite':
                    $whereClausesGroups[] = "','||u.additionalgroups||',' LIKE '%,{$groupID},%'";
                    break;
                default:
                    $whereClausesGroups[] = "CONCAT(',',u.additionalgroups,',') LIKE '%,{$groupID},%'";
                    break;
            }
        }

        $whereClausesGroups = implode(' OR ', $whereClausesGroups);

        if ($whereClausesGroups) {
            $search_query .= " AND ({$whereClausesGroups})";
        }
    }

    $whereClauses = [];

    $allowedGroupsManagePrivacy = sanitizeIntegers(
        explode(',', (string)getSetting('groupsCanManageProfilePrivacy'))
    );

    $currentUserID = (int)$mybb->user['uid'];

    $enabledProfilePrivacyTypes = profilePrivacyTypes();

    if ($allowedGroupsManagePrivacy && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_ONLY_BUDDY_LIST])) {
        $profilePrivacySettingClause = '';

        $privacyTypeValue = PRIVACY_TYPE_ONLY_BUDDY_LIST;

        switch ($db->type) {
            case 'pgsql':
            case 'sqlite':
                $profilePrivacySettingClause .= "','||u.ougcCustomFieldsSearchProfilePrivacy||',' NOT LIKE '%,{$privacyTypeValue},%'";
                break;
            default:
                $profilePrivacySettingClause .= "CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') NOT LIKE '%,{$privacyTypeValue},%'";
                break;
        }

        $profilePrivacySettingClause .= " OR u.uid='{$currentUserID}' OR ";

        switch ($db->type) {
            case 'pgsql':
            case 'sqlite':
                $profilePrivacySettingClause .= "(','||u.ougcCustomFieldsSearchProfilePrivacy||',' LIKE '%,{$privacyTypeValue},%' AND ','||u.buddylist||',' LIKE '%,{$currentUserID},%')";
                break;
            default:
                $profilePrivacySettingClause .= "(CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') LIKE '%,{$privacyTypeValue},%' AND CONCAT(',',u.buddylist,',') LIKE '%,{$currentUserID},%')";
                break;
        }

        $whereClauses[] = "({$profilePrivacySettingClause})";
    }

    if ($allowedGroupsManagePrivacy && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_IGNORE_LIST])) {
        $profilePrivacySettingClause = '';

        $privacyTypeValue = PRIVACY_TYPE_BLOCK_IGNORE_LIST;

        switch ($db->type) {
            case 'pgsql':
            case 'sqlite':
                $profilePrivacySettingClause .= "','||u.ougcCustomFieldsSearchProfilePrivacy||',' NOT LIKE '%,{$privacyTypeValue},%'";
                break;
            default:
                $profilePrivacySettingClause .= "CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') NOT LIKE '%,{$privacyTypeValue},%'";
                break;
        }

        $profilePrivacySettingClause .= " OR u.uid='{$currentUserID}' OR ";

        switch ($db->type) {
            case 'pgsql':
            case 'sqlite':
                $profilePrivacySettingClause .= "(','||u.ougcCustomFieldsSearchProfilePrivacy||',' LIKE '%,{$privacyTypeValue},%' AND ','||u.ignorelist||',' NOT LIKE '%,{$currentUserID},%')";
                break;
            default:
                $profilePrivacySettingClause .= "(CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') LIKE '%,{$privacyTypeValue},%' AND CONCAT(',',u.ignorelist,',') NOT LIKE '%,{$currentUserID},%')";
                break;
        }

        $whereClauses[] = "({$profilePrivacySettingClause})";
    }

    if ($allowedGroupsManagePrivacy && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_GUESTS])) {
        if (!$currentUserID) {
            $profilePrivacySettingClause = '';

            $privacyTypeValue = PRIVACY_TYPE_BLOCK_GUESTS;

            switch ($db->type) {
                case 'pgsql':
                case 'sqlite':
                    $profilePrivacySettingClause .= "','||u.ougcCustomFieldsSearchProfilePrivacy||',' NOT LIKE '%,{$privacyTypeValue},%'";
                    break;
                default:
                    $profilePrivacySettingClause .= "CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') NOT LIKE '%,{$privacyTypeValue},%'";
                    break;
            }

            $whereClauses[] = "({$profilePrivacySettingClause})";
        }
    }

    if ($allowedGroupsManagePrivacy && isset($enabledProfilePrivacyTypes[PRIVACY_TYPE_BLOCK_USERS])) {
        if ($currentUserID) {
            $profilePrivacySettingClause = '';

            $privacyTypeValue = PRIVACY_TYPE_BLOCK_USERS;

            switch ($db->type) {
                case 'pgsql':
                case 'sqlite':
                    $profilePrivacySettingClause .= "(','||u.ougcCustomFieldsSearchProfilePrivacy||',' NOT LIKE '%,{$privacyTypeValue},%' OR u.uid='{$currentUserID}')";
                    break;
                default:
                    $profilePrivacySettingClause .= "(CONCAT(',',u.ougcCustomFieldsSearchProfilePrivacy,',') NOT LIKE '%,{$privacyTypeValue},%' OR u.uid='{$currentUserID}')";
                    break;
            }

            $whereClauses[] = "({$profilePrivacySettingClause})";
        }
    }

    if ($whereClauses) {
        $whereClauses = implode(' AND ', $whereClauses);

        $search_query .= " AND {$whereClauses}";
    }

    if (empty($mybb->input['doCustomFieldsSearch'])) {
        return false;
    }

    $ougcCustomFieldSearchFilters = $mybb->get_input('customSearchFields', MyBB::INPUT_ARRAY);

    $searchTypeFields = $mybb->get_input('searchTypeField', MyBB::INPUT_ARRAY);

    $whereClauses = $whereClausesGroups = $ougcCustomFieldSearchUrlParams = [];

    $ougcCustomFieldSearchGroups = array_flip(
        array_map('intval', $mybb->get_input('customSearchGroups', MyBB::INPUT_ARRAY))
    );

    $searchGroups = $mybb->get_input('searchGroups');

    if (!isset($ougcCustomFieldSearchGroups[-1]) && in_array($searchGroups, ['primary', 'additional', 'both'])) {
        foreach ($ougcCustomFieldSearchGroups as $groupID => $arrayValue) {
            if (in_array($searchGroups, ['primary', 'both'])) {
                $whereClausesGroups[] = "u.usergroup='{$groupID}'";
            }

            if (in_array($searchGroups, ['additional', 'both'])) {
                switch ($db->type) {
                    case 'pgsql':
                    case 'sqlite':
                        $whereClausesGroups[] = "','||u.additionalgroups||',' LIKE '%,{$groupID},%'";
                        break;
                    default:
                        $whereClausesGroups[] = "CONCAT(',',u.additionalgroups,',') LIKE '%,{$groupID},%'";
                        break;
                }
            }

            $ougcCustomFieldSearchUrlParams["customSearchGroups[{$groupID}]"] = $groupID;
        }

        $ougcCustomFieldSearchUrlParams['searchGroups'] = $searchGroups;

        $whereClauses[] = implode(' OR ', $whereClausesGroups);

        $ougcCustomFieldSearchUrlParams['doCustomFieldsSearch'] = 1;
    }

    $customFieldsCache = getProfileFieldsCache();

    $customFieldsCacheIDs = array_column($customFieldsCache, 'fid');

    $ignoredProfileFieldsIDs = array_flip(explode(',', (string)getSetting('ignoredProfileFieldsIDs')));

    $profileFieldsCache = getProfileFieldsCache();

    $profileFieldsIDs = array_column($profileFieldsCache, 'fid');

    foreach ($ougcCustomFieldSearchFilters as $filterKey => $filterValue) {
        if (empty($filterValue)) {
            continue;
        }

        $fieldID = (int)str_replace('fid', '', $filterKey);

        if (!in_array($fieldID, $profileFieldsIDs)) {
            continue;
        }

        $customFieldKey = "fid{$fieldID}";

        $customFieldsCacheIndex = array_search($fieldID, $customFieldsCacheIDs);

        if (empty($customFieldsCache[$customFieldsCacheIndex])) {
            continue;
        }

        $customFieldData = $customFieldsCache[$customFieldsCacheIndex];

        if (!is_member(
                $customFieldData['viewableby']
            ) || ((isset($ignoredProfileFieldsIDs[$customFieldData['fid']]) || isset($ignoredProfileFieldsIDs[-1])) && !is_member(
                    getSetting('bypassIgnoredProfileFields')
                ))) {
            continue;
        }

        $fieldTypeValues = explode("\n", (string)$customFieldData['type'], 2);

        $fieldType = $fieldTypeValues[0] ?? '';

        $searchType = '';

        if (isset($searchTypeFields[$filterKey])) {
            $searchType = $searchTypeFields[$filterKey];
        }

        $ougcCustomFieldSearchUrlParams['doCustomFieldsSearch'] = 1;

        if (in_array($fieldType, ['multiselect', 'select', 'radio', 'checkbox']) && is_array($filterValue)) {
            foreach ($filterValue as $k => $v) {
                $ougcCustomFieldSearchUrlParams["customSearchFields[{$customFieldKey}][{$k}]"] = htmlspecialchars_uni(
                    $v
                );
            }

            $filterValueIDs = implode(
                "', '",
                array_map([$db, 'escape_string'], array_map('my_strtolower', $filterValue))
            );

            $comparisonOperator = 'IN';

            if ($searchType === 'doesNotHave') {
                $comparisonOperator = 'NOT IN';

                $ougcCustomFieldSearchUrlParams["searchTypeField[{$customFieldKey}]"] = 'doesNotHave';
            }

            $whereClauses[$customFieldKey] = "LOWER(f.{$customFieldKey}) {$comparisonOperator} ('{$filterValueIDs}')";
        } elseif ($fieldType === 'file' && $filterValue !== 'ignore') {
            $comparisonOperator = '>=';

            $comparisonOperatorSecondary = 'NOT';

            if ($filterValue === 'doesNotHave') {
                $comparisonOperator = '<';

                $comparisonOperatorSecondary = '';
            }

            $ougcCustomFieldSearchUrlParams["customSearchFields[{$customFieldKey}]"] = htmlspecialchars_uni(
                $filterValue
            );

            $whereClauses[$customFieldKey] = "f.{$customFieldKey} {$comparisonOperator} 1 OR f.{$customFieldKey} IS {$comparisonOperatorSecondary} NULL";
        } elseif ($fieldType !== 'file') {
            $comparisonOperator = 'LIKE';

            if ($searchType === 'doesNotMatch') {
                $comparisonOperator = 'NOT LIKE';

                $ougcCustomFieldSearchUrlParams["searchTypeField[{$customFieldKey}]"] = 'doesNotMatch';
            }

            $ougcCustomFieldSearchUrlParams["customSearchFields[{$customFieldKey}]"] = htmlspecialchars_uni(
                $filterValue
            );

            $whereClauses[$customFieldKey] = "LOWER(f.{$customFieldKey}) {$comparisonOperator} '%{$db->escape_string_like(my_strtolower($filterValue))}%'";
        }
    }

    if ($whereClauses) {
        global $lang;
        global $sorturl, $search_url;

        load_language();

        if ($mybb->get_input('searchType') === 'any') {
            $whereClauses = implode(') OR (', $whereClauses);

            $ougcCustomFieldSearchUrlParams['searchType'] = 'any';
        } elseif ($mybb->get_input('searchType') === 'xor') {
            $whereClauses = implode(') XOR (', $whereClauses);

            $ougcCustomFieldSearchUrlParams['searchType'] = 'xor';
        } else {
            $whereClauses = implode(') AND (', $whereClauses);
        }

        foreach (
            [
                'letter',
                'username_match',
                'username',
                'website',
                'skype',
                'google',
                'icq',
                'sort', // todo, later add option to sort by profile field
                'order',
                'perpage'
            ] as $searchOption
        ) {
            if (isset($mybb->input[$searchOption])) {
                $ougcCustomFieldSearchUrlParams[$searchOption] = htmlspecialchars_uni($mybb->input[$searchOption]);
            }
        }

        $ougcCustomFieldSearchUrl = urlHandlerBuild($ougcCustomFieldSearchUrlParams);

        $ougcCustomFieldSearchUrlDescription = eval(getTemplate('urlDescription'));

        if ($whereClauses) {
            $search_query .= " AND (({$whereClauses}))";
        }

        if (getSetting('cacheIntervalSeconds')) {
            $uniqueIdentifier = md5($search_query);

            $cachedSearchClause = cachedSearchClauseGet($uniqueIdentifier, true);
        }

        if (!empty($cachedSearchClause)) {
            $search_query = $cachedSearchClause;
        } else {
            $dbQuery = $db->simple_select(
                "users u LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)",
                'u.uid',
                "{$search_query}"
            );

            $userIDs = [];

            while ($userIDs[] = (int)$db->fetch_field($dbQuery, 'uid')) {
            }

            $userIDs = implode("','", array_filter($userIDs));

            $search_query = "u.uid IN ('{$userIDs}')";

            if (!empty($uniqueIdentifier)) {
                cachedSearchClausePut($uniqueIdentifier, $search_query);
            }
        }
    }

    return true;
}

function multipage(array &$paginationArguments): array
{
    if (constant('THIS_SCRIPT') !== 'memberlist.php') {
        return $paginationArguments;
    }

    global $ougcCustomFieldSearchUrl, $ougcCustomFieldSearchUrlParams;

    if (!isset($ougcCustomFieldSearchUrl) || !isset($ougcCustomFieldSearchUrlParams)) {
        return $paginationArguments;
    }

    urlHandler($paginationArguments['url']);

    $paginationArguments['url'] = urlHandlerBuild($ougcCustomFieldSearchUrlParams);

    return $paginationArguments;
}

function pre_output_page(string $pageContents): string
{
    if (mb_strpos($pageContents, '<!--OUGC_MEMBERLISTSEARCH-->') === false) {
        return $pageContents;
    }

    global $theme, $lang, $cache, $mybb;

    load_language();

    $searchFieldOptions = '';

    foreach (explode(',', (string)getSetting('searchFields')) as $searchField) {
        $optionValue = $searchField;

        $optionSelect = '';

        $fieldKey = ucfirst($searchField);

        $optionName = $lang->{"ougc_customfsearch_globalSearchFormFieldName{$fieldKey}"};

        $searchFieldOptions .= eval(getTemplate('globalSearchFormSelectOption'));
    }

    foreach (getProfileFieldsCache() as $profileField) {
        $fieldID = (int)$profileField['fid'];

        if (!is_member(getSetting('searchCustomFields'), ['usergroup' => $fieldID, 'additionalgroups' => ''])) {
            continue;
        }

        $optionValue = "fid{$fieldID}";

        //$optionValue = "customSearchFields[{$customFieldKey}]";

        $optionSelect = '';

        $optionName = htmlspecialchars_uni($profileField['name']);
        $searchFieldOptions .= eval(getTemplate('globalSearchFormSelectOption'));
    }

    $searchForm = eval(getTemplate('globalSearchForm'));

    $pageContents = str_replace('<!--OUGC_MEMBERLISTSEARCH-->', $searchForm, $pageContents);

    return $pageContents;
}

function xmlhttp(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== 'ougcCustomFieldsSearch') {
        return false;
    }

    $mybb->input['query'] = trim($mybb->get_input('query'));

    if (my_strlen($mybb->input['query']) < 2) {
        return false;
    }

    global $db, $lang;

    if ($lang->settings['charset']) {
        $charset = $lang->settings['charset'];
    } else {
        $charset = 'UTF-8';
    }

    header("Content-type: application/json; charset={$charset}");

    $searchField = $mybb->get_input('searchField');

    $dbFields = [];

    $leftJoin = '';

    $ougcCustomFieldSearchUrlParams = [];

    switch ($searchField) {
        case'username':
            if (mb_strpos(getSetting('searchFields'), 'username') !== false) {
                $dbSearchField = 'u.username';

                $filterField = 'username';

                $dbFields[] = 'DISTINCT u.username';

                $ougcCustomFieldSearchUrlParams[$filterField] = htmlspecialchars_uni($mybb->input['query']);

                $ougcCustomFieldSearchUrlParams['username_match'] = 'contains';
            }
            break;
        case'website':
            if (mb_strpos(getSetting('searchFields'), 'website') !== false) {
                $dbSearchField = 'u.website';

                $filterField = 'website';

                $dbFields[] = 'DISTINCT u.website';

                $ougcCustomFieldSearchUrlParams[$filterField] = urlencode($mybb->input['query']);
            }
            break;
        default:
            if (mb_strpos($searchField, 'fid') === 0) {
                $fieldID = (int)str_replace('fid', '', $searchField);

                $customFieldKey = "fid{$fieldID}";

                $leftJoin = " LEFT JOIN {$db->table_prefix}userfields f ON (f.ufid=u.uid)";

                $dbSearchField = "f.{$customFieldKey}";

                $dbFields[] = "DISTINCT f.{$customFieldKey}";

                $filterField = $customFieldKey;

                $ougcCustomFieldSearchUrlParams["customSearchFields[{$filterField}]"] = htmlspecialchars_uni(
                    $mybb->input['query']
                );
            }
    }

    //$leftJoin = " LEFT JOIN {$db->table_prefix}users ud ON (ud.uid=u.uid)";

    $dbFields[] = 'u.uid';

    $dbFields[] = 'u.avatar';

    $dbFields[] = 'u.avatartype';

    if (!isset($dbSearchField) || empty($filterField)) {
        echo json_encode([]);

        return true;
    }

    global $lang;

    load_language();

    $returnObjects = ['results' => []];

    if (!empty($ougcCustomFieldSearchUrlParams)) {
        $dbQuery = $db->simple_select(
            'users u' . $leftJoin,
            'COUNT(u.uid) as totalUsers',
            "LOWER({$dbSearchField}) LIKE '%{$db->escape_string_like(mb_strtolower($mybb->input['query']))}%'"
        );

        $totalUsers = $db->fetch_field($dbQuery, 'totalUsers');

        $ougcCustomFieldSearchUrlParams['doCustomFieldsSearch'] = 1;

        $returnObjects['action'] = [
            'url' => "{$mybb->settings['bburl']}/" . urlHandlerBuild($ougcCustomFieldSearchUrlParams),
            'text' => $lang->sprintf(
                $lang->ougcCustomFieldsSearchGlobalSearchResultsViewAll,
                my_number_format($totalUsers)
            )
        ];
    }

    $dbQuery = $db->simple_select(
        'users u' . $leftJoin,
        implode(',', $dbFields),
        "LOWER({$dbSearchField}) LIKE '%{$db->escape_string_like(mb_strtolower($mybb->input['query']))}%'",
        [
            'order_by' => 'u.username',
            'order_dir' => 'asc',
            'limit_start' => 0,
            'limit' => 15
        ]
    );

    while ($searchData = $db->fetch_array($dbQuery)) {
        $userData = get_user($searchData['uid']);

        $returnObjects['results'][] = [
            'id' => $searchData[$filterField],
            'text' => $searchData[$filterField],
            'title' => $searchData[$filterField],
            'url' => "{$mybb->settings['bburl']}/" . get_profile_link($userData['uid']),
            'image' => getUserAvatarLink($userData)
        ];
    }

    echo json_encode($returnObjects);

    return true;
}
<?php

/***************************************************************************
 *
 *    OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/forum_hooks.php)
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

namespace ougc\CustomFieldsSearch\Hooks\Forum;

use MyBB;

use function ougc\CustomFieldsSearch\Core\getSetting;
use function ougc\CustomFieldsSearch\Core\getTemplate;
use function ougc\CustomFieldsSearch\Core\urlHandlerBuild;
use function ougc\CustomFieldsSearch\Core\load_language;
use function ougc\CustomFieldsSearch\Core\urlHandler;

function global_start()
{
    if (constant('THIS_SCRIPT') === 'memberlist.php') {
        global $templatelist;

        isset($templatelist) || $templatelist = '';

        $templatelist .= ', ougccustomfsearch_field_text, ougccustomfsearch_field, ougccustomfsearch_field_select_option, ougccustomfsearch_field_select, ougccustomfsearch, ougccustomfsearch_groupsSelectOption, ougccustomfsearch_groupsSelect, ougccustomfsearch_field_checkbox, ougccustomfsearch_urlDescription';
    }
}

function memberlist_search()
{
    global $templates, $lang, $mybb;
    global $ougc_customfsearch, $ougcCustomFieldSearchFilters, $errors, $user;

    $mybb->input['customSearchFields'] = $mybb->get_input('customSearchFields', MyBB::INPUT_ARRAY);

    $mybb->input['customSearchGroups'] = $mybb->get_input('customSearchGroups', MyBB::INPUT_ARRAY);

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

    foreach ($mybb->cache->read('usergroups') as $groupID => $groupData) {
        $groupID = (int)$groupID;

        if (empty($groupData['showmemberlist']) || $groupID === 1) {
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

    $customFieldsCache = $mybb->cache->read('profilefields');

    if (is_array($customFieldsCache)) {
        $searchTypeFields = $mybb->get_input('searchTypeField', MyBB::INPUT_ARRAY);

        $alternativeBackground = alt_trow(true);

        $ignoredProfileFieldsIDs = array_flip(explode(',', getSetting('ignoredProfileFieldsIDs')));

        foreach ($customFieldsCache as $customFieldData) {
            if (!is_member(
                    $customFieldData['viewableby']
                ) || (isset($ignoredProfileFieldsIDs[$customFieldData['fid']]) && !is_member(
                        getSetting('bypassIgnoredProfileFields')
                    ))) {
                continue;
            }

            $customFieldData['type'] = htmlspecialchars_uni($customFieldData['type']);

            $customFieldData['name'] = htmlspecialchars_uni($customFieldData['name']);

            $customFieldData['description'] = htmlspecialchars_uni($customFieldData['description']);

            $fieldTypeValues = explode("\n", $customFieldData['type'], '2');

            $fieldType = $fieldTypeValues[0] ?? '';

            $fieldOptions = $fieldTypeValues[1] ?? [];

            $customFieldKey = "fid{$customFieldData['fid']}";

            $selectOptions = '';

            if ($errors) {
                if (!isset($mybb->input['customSearchFields'][$customFieldKey])) {
                    $mybb->input['customSearchFields'][$customFieldKey] = '';
                }

                $userFieldValue = $mybb->input['customSearchFields'][$customFieldKey];
            } else {
                $userFieldValue = $user[$customFieldKey];
            }

            if (in_array($fieldType, ['multiselect', 'select', 'radio', 'checkbox'])) {
                foreach (explode("\n", $fieldOptions) as $optionValue) {
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

                if ($searchTypeFields[$customFieldKey] === 'doesNotHave') {
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
                $filterValue = htmlspecialchars_uni($ougcCustomFieldSearchFilters[$customFieldKey]);

                $maxLength = '';

                if ($fieldType != 'textarea' && $customFieldData['maxlength'] > 0) {
                    $maxLength = " maxlength=\"{$customFieldData['maxlength']}\"";
                }

                $searchTypeCheckedMatches = $searchTypeCheckedDoesNotMatch = '';

                if ($searchTypeFields[$customFieldKey] === 'has') {
                    $searchTypeCheckedMatches = 'checked="checked"';
                } elseif ($searchTypeFields[$customFieldKey] === 'doesNotMatch') {
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
    memberlist_intermediate();
}

function memberlist_intermediate(): bool
{
    global $mybb, $db;
    global $ougcCustomFieldSearchFilters, $ougcCustomFieldSearchUrlDescription, $search_query, $ougcCustomFieldSearchUrl, $ougcCustomFieldSearchUrlParams;

    $ougcCustomFieldSearchUrlDescription = '';

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

    $customFieldsCache = $mybb->cache->read('profilefields');

    $customFieldsCacheIDs = array_column($customFieldsCache, 'fid');

    $ignoredProfileFieldsIDs = array_flip(explode(',', getSetting('ignoredProfileFieldsIDs')));

    foreach ($ougcCustomFieldSearchFilters as $filterKey => $filterValue) {
        if (empty($filterValue)) {
            continue;
        }

        $fieldID = (int)str_replace('fid', '', $filterKey);

        $customFieldKey = "fid{$fieldID}";

        $customFieldsCacheIndex = array_search($fieldID, $customFieldsCacheIDs);

        if (empty($customFieldsCache[$customFieldsCacheIndex])) {
            continue;
        }

        $customFieldData = $customFieldsCache[$customFieldsCacheIndex];

        if (!is_member(
                $customFieldData['viewableby']
            ) || (isset($ignoredProfileFieldsIDs[$customFieldData['fid']]) && !is_member(
                    getSetting('bypassIgnoredProfileFields')
                ))) {
            continue;
        }

        $fieldTypeValues = explode("\n", $customFieldData['type'], '2');

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

        $search_query .= " AND (({$whereClauses}))";

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
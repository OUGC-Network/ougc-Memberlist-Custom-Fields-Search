<?php

/***************************************************************************
 *
 *    ougc Member List Advanced Search plugin (/inc/plugins/ougc/CustomFieldsSearch/admin_hooks.php)
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

namespace ougc\CustomFieldsSearch\Hooks\Admin;

use FormContainer;
use MyBB;

use function ougc\CustomFieldsSearch\Core\getTemplate;
use function ougc\CustomFieldsSearch\Core\load_language;
use function ougc\CustomFieldsSearch\Core\profilePrivacyTypes;
use function ougc\CustomFieldsSearch\Core\sanitizeIntegers;

use const ougc\CustomFieldsSearch\Core\FIELDS_DATA;

function admin_config_plugins_deactivate(): bool
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') != 'deactivate' ||
        $mybb->get_input('plugin') != 'ougc_customfsearch' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return false;
    }

    if ($mybb->request_method != 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=ougc_customfsearch'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function admin_config_settings_start()
{
    load_language();
}

function admin_style_templates_set()
{
    load_language();
}

function admin_config_settings_change()
{
    load_language();
}

function admin_formcontainer_output_row(array $rowArguments): array
{
    global $lang;

    load_language();

    if (!(
        !empty($rowArguments['title']) &&
        (
            $rowArguments['title'] === $lang->setting_ougcCustomFieldsSearch_ignoredProfileFieldsIDs ||
            $rowArguments['title'] === $lang->setting_ougcCustomFieldsSearch_searchCustomFields
        )
    )) {
        return $rowArguments;
    }

    global $db, $form;
    global $setting, $element_id;

    $dbQuery = $db->simple_select('profilefields', '*', '', ['order_by' => 'name, disporder']);

    $profileFieldsObjects = [
        -1 => $lang->ougc_customfsearch_allCustomFields,
        0 => '----------'
    ];

    while ($profileFieldData = $db->fetch_array($dbQuery)) {
        $profileFieldsObjects[(int)$profileFieldData['fid']] = htmlspecialchars_uni($profileFieldData['name']);
    }

    $rowArguments['content'] = $form->generate_select_box(
        "select[{$setting['name']}][]",
        $profileFieldsObjects,
        explode(',', $setting['value']),
        ['id' => $element_id, 'multiple' => true, 'size' => 5]
    );

    return $rowArguments;
}

function admin_config_settings_change_commit()
{
    global $mybb;

    if (!isset($mybb->input['select'])) {
        return;
    }

    global $db;

    if (isset($mybb->input['select']['ougcCustomFieldsSearch_ignoredProfileFieldsIDs'])) {
        $profileFieldsIDs = implode(
            ',',
            array_filter(array_map('intval', $mybb->input['select']['ougcCustomFieldsSearch_ignoredProfileFieldsIDs']))
        );

        $db->update_query(
            'settings',
            ['value' => $db->escape_string($profileFieldsIDs)],
            "name='ougcCustomFieldsSearch_ignoredProfileFieldsIDs'"
        );
    }

    if (isset($mybb->input['select']['ougcCustomFieldsSearch_searchCustomFields'])) {
        $profileFieldsIDs = implode(
            ',',
            array_filter(array_map('intval', $mybb->input['select']['ougcCustomFieldsSearch_searchCustomFields']))
        );

        $db->update_query(
            'settings',
            ['value' => $db->escape_string($profileFieldsIDs)],
            "name='ougcCustomFieldsSearch_searchCustomFields'"
        );
    }
}

function admin_formcontainer_end(array &$hook_arguments): array
{
    global $run_module, $lang;

    if ($run_module == 'user' && isset($lang->general) && $hook_arguments['this']->_title == $lang->general) {
        global $form, $mybb;

        load_language();

        $pluginPermissions = [];

        foreach (FIELDS_DATA['usergroups'] as $fieldName => $fieldDefinition) {
            if (in_array(
                $fieldName,
                ['ougcCustomFieldsSearchCanSearchGroupIDs', 'ougcCustomFieldsSearchCanViewProfilesGroupIDs']
            )) {
                $inputField = $form->generate_group_select(
                    "{$fieldName}[]",
                    sanitizeIntegers(explode(',', $mybb->get_input($fieldName))),
                    ['multiple' => true, 'size' => 5],
                    $lang->{$fieldName}
                );

                $pluginPermissions[] = "<br />{$lang->{$fieldName}}<br /><small>{$lang->{"{$fieldName}Description"}}</small><br />{$inputField}";
            } else {
                $pluginPermissions[] = $form->generate_check_box(
                    $fieldName,
                    1,
                    $lang->{$fieldName},
                    ['checked' => $mybb->get_input($fieldName, MyBB::INPUT_INT)]
                );
            }
        }

        $hook_arguments['this']->output_row(
            $lang->ougcCustomFieldsSearchGroupPermissionsMemberList,
            '',
            '<div class="group_settings_bit">' . implode(
                '</div><div class="group_settings_bit">',
                $pluginPermissions
            ) . '</div>'
        );
    }

    return $hook_arguments;
}

function admin_user_groups_edit_commit()
{
    global $mybb, $db;
    global $updated_group;

    foreach (FIELDS_DATA['usergroups'] as $fieldName => $fieldDefinition) {
        if (in_array(
            $fieldName,
            ['ougcCustomFieldsSearchCanSearchGroupIDs', 'ougcCustomFieldsSearchCanViewProfilesGroupIDs']
        )) {
            $updated_group[$fieldName] = $db->escape_string(
                implode(
                    ',',
                    sanitizeIntegers($mybb->get_input($fieldName, MyBB::INPUT_ARRAY))
                )
            );
        } else {
            $updated_group[$fieldName] = $mybb->get_input($fieldName, MyBB::INPUT_INT);
        }
    }
}

function admin_user_users_edit_profile()
{
    global $mybb, $lang;
    global $form, $user;

    $form_container = new FormContainer($lang->ougcCustomFieldsSearchProfilePrivacyTitle);

    $privacyItemRows = [];

    if ($mybb->request_method === 'post') {
        $privacySettings = sanitizeIntegers(
            $mybb->get_input('ougcCustomFieldsSearchProfilePrivacyInput', MyBB::INPUT_ARRAY)
        );
    } else {
        $privacySettings = array_flip(
            sanitizeIntegers(explode(',', $user['ougcCustomFieldsSearchProfilePrivacy']))
        );
    }

    foreach (profilePrivacyTypes() as $privacyType => $privacyKey) {
        $privacyItemRows[] = $form->generate_check_box(
            "ougcCustomFieldsSearchProfilePrivacyInput[{$privacyType}]",
            1,
            $lang->{"ougcCustomFieldsSearchProfilePrivacy{$privacyKey}"},
            ['checked' => isset($privacySettings[$privacyType])]
        );
    }

    $form_container->output_row(
        $lang->ougcCustomFieldsSearchProfilePrivacyTitle,
        '',
        "<div class=\"user_settings_bit\">" . implode(
            "</div><div class=\"user_settings_bit\">",
            $privacyItemRows
        ) . '</div>'
    );

    $form_container->end();
}
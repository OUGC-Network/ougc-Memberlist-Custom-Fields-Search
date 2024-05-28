<?php

/***************************************************************************
 *
 *    OUGC Private Threads plugin (/inc/plugins/ougc/PrivateThreads/admin_hooks.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2020 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Allow users to mark individual threads as private to be visible for specific users only.
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

use MyBB;

use function ougc\PrivateThreads\Core\load_language;
use function ougc\PrivateThreads\MyAlerts\getAvailableLocations;
use function ougc\PrivateThreads\MyAlerts\installLocation;
use function ougc\PrivateThreads\MyAlerts\MyAlertsIsIntegrable;

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
    \ougc\CustomFieldsSearch\Core\load_language();
}

function admin_style_templates_set()
{
    \ougc\CustomFieldsSearch\Core\load_language();
}

function admin_config_settings_change()
{
    \ougc\CustomFieldsSearch\Core\load_language();
}

function admin_formcontainer_output_row(array $rowArguments): array
{
    global $lang;

    \ougc\CustomFieldsSearch\Core\load_language();

    if (empty($rowArguments['title']) || $rowArguments['title'] !== $lang->setting_ougcCustomFieldsSearch_ignoredProfileFieldsIDs) {
        return $rowArguments;
    }

    global $db, $form;
    global $element_name, $option_list, $setting, $element_id;

    $dbQuery = $db->simple_select('profilefields', '*', '', ['order_by' => 'name, disporder']);

    $profileFieldsObjects = [];

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

    if (!isset($mybb->input['select']) || !isset($mybb->input['select']['ougcCustomFieldsSearch_ignoredProfileFieldsIDs'])) {
        return;
    }

    global $db;

    $profileFieldsIDs = implode(
        ',',
        array_map('intval', $mybb->input['select']['ougcCustomFieldsSearch_ignoredProfileFieldsIDs'])
    );

    $db->update_query(
        'settings',
        ['value' => $db->escape_string($profileFieldsIDs)],
        "name='ougcCustomFieldsSearch_ignoredProfileFieldsIDs'"
    );
}
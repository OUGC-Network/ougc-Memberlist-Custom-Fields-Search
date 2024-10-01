<?php

/***************************************************************************
 *
 *    ougc Member List Advanced Search plugin (/inc/plugins/ougc/CustomFieldsSearch/shared_hooks.php)
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

namespace ougc\CustomFieldsSearch\Hooks\Shared;

use MyBB;

use UserDataHandler;

use function ougc\CustomFieldsSearch\Core\sanitizeIntegers;

function datahandler_user_update(UserDataHandler $userDataHandler): UserDataHandler
{
    global $mybb;

    if (!isset($mybb->input['ougcCustomFieldsSearchProfilePrivacyInput'])) {
        //return $userDataHandler;
    }

    global $db;

    $privacySettings = $mybb->get_input('ougcCustomFieldsSearchProfilePrivacyInput', MyBB::INPUT_ARRAY);

    $privacySettings = $db->escape_string(implode(',', sanitizeIntegers(array_keys($privacySettings))));

    $userDataHandler->user_update_data['ougcCustomFieldsSearchProfilePrivacy'] = $privacySettings;

    return $userDataHandler;
}
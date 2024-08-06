<?php

/***************************************************************************
 *
 *    OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch/shared_hooks.php)
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
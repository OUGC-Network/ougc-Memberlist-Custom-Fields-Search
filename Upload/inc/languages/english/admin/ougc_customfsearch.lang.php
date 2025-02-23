<?php

/***************************************************************************
 *
 *    ougc Member List Custom plugin (/inc/languages/english/admin/ougc_customfsearch.lang.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2021 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Allow more complex searches in the member list advanced search page.
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

$l = [
    'ougc_customfsearch' => 'ougc Member List Advanced',
    'ougc_customfsearch_description' => 'Allow more complex searches in the member list advanced search page.',

    'setting_group_ougc_customfsearch' => 'Member List Custom Advanced Search',
    'setting_group_ougc_customfsearch_desc' => 'Allow more complex searches in the member list advanced search page.',

    'setting_group_ougcCustomFieldsSearch' => 'Member List Advanced Search',
    'setting_group_ougcCustomFieldsSearch_desc' => 'Allow more complex searches in the member list advanced search page.',
    'setting_ougcCustomFieldsSearch_ignoredProfileFieldsIDs' => 'Disabled Profile Fields',
    'setting_ougcCustomFieldsSearch_ignoredProfileFieldsIDs_desc' => 'Select which custom profile fields to ignore from the advanced search feature. Users will not be able to search by these selected fields.',
    'setting_ougcCustomFieldsSearch_bypassIgnoredProfileFields' => 'Bypass Disabled Profile Fields',
    'setting_ougcCustomFieldsSearch_bypassIgnoredProfileFields_desc' => 'Select which groups are allowed to bypass the above setting.',
    'setting_ougcCustomFieldsSearch_cacheIntervalSeconds' => 'Cache Interval',
    'setting_ougcCustomFieldsSearch_cacheIntervalSeconds_desc' => "Select the seconds search queries should be kept into the cache before being deleted. This is useful the most the change on users count, frequency of updates of custom profile fields, and search criteria remain constant. The larger the search criteria and users' data variations, it is recommended to keep this to a low seconds count.",
    'setting_ougcCustomFieldsSearch_enableGlobalSearch' => 'Enable Global Search',
    'setting_ougcCustomFieldsSearch_enableGlobalSearch_desc' => 'If you enable this, a global search form wil be added to templates. <a href="https://github.com/Sama34/OUGC-Memberlist-Custom-Fields-Search">Read the documentation for more information.</a>',
    'setting_ougcCustomFieldsSearch_searchFields' => 'Global Search Fields',
    'setting_ougcCustomFieldsSearch_searchFields_desc' => 'Select the allowed fields to search for in the global search form.',
    'setting_ougcCustomFieldsSearch_searchFields_username' => 'Username',
    'setting_ougcCustomFieldsSearch_searchFields_website' => 'Website',
    'setting_ougcCustomFieldsSearch_searchCustomFields' => 'Global Search Custom Fields',
    'setting_ougcCustomFieldsSearch_searchCustomFields_desc' => 'Select the allowed custom fields to search for in the global search form.',
    'setting_ougcCustomFieldsSearch_groupsCanManageProfilePrivacy' => 'Manage Profile Privacy',
    'setting_ougcCustomFieldsSearch_groupsCanManageProfilePrivacy_desc' => 'Select which groups are allowed to manage their profile privacy from the User Control Panel.',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes' => 'Profile Privacy Types',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes_desc' => 'Select which profile privacy permissions can be set.',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes_BuddyList' => 'BuddyList',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes_IgnoreList' => 'IgnoreList',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes_Guests' => 'Guests',
    'setting_ougcCustomFieldsSearch_profilePrivacyTypes_Users' => 'Users',

    'ougc_customfsearch_allCustomFields' => 'All custom fields',

    'ougcCustomFieldsSearchGroupPermissionsMemberList' => 'Member List Settings',
    'ougcCustomFieldsSearchCanSearchGroupIDs' => 'Can Search Selected Groups',
    'ougcCustomFieldsSearchCanSearchGroupIDsDescription' => 'Select the user groups that users in this groups are allowed to search for in the member list. Select none for all groups.',
    'ougcCustomFieldsSearchCanViewProfilesGroupIDs' => 'Can View Selected Groups Profiles',
    'ougcCustomFieldsSearchCanViewProfilesGroupIDsDescription' => 'Select the user groups that users in this groups are allowed to view profiles from. Select none for all groups.',

    'ougcCustomFieldsSearchProfilePrivacyTitle' => 'Profile Privacy',
    'ougcCustomFieldsSearchProfilePrivacyBuddyList' => 'Allow only users from my buddy list.',
    'ougcCustomFieldsSearchProfilePrivacyIgnoreList' => 'Block users from my ignore list.',
    'ougcCustomFieldsSearchProfilePrivacyGuests' => 'Block guests or unregistered users.',
    'ougcCustomFieldsSearchProfilePrivacyUsers' => 'Block registered users.',

    'ougc_customfsearch_pluginlibrary' => 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or above, please upload this plugin to your forum and then try again.'
];
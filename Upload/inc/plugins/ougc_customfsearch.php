<?php

/***************************************************************************
 *
 *    ougc Member List Advanced Search plugin (/inc/plugins/ougc_customfsearch.php)
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

use function ougc\CustomFieldsSearch\Admin\_activate;
use function ougc\CustomFieldsSearch\Admin\_deactivate;
use function ougc\CustomFieldsSearch\Admin\_info;
use function ougc\CustomFieldsSearch\Admin\_install;
use function ougc\CustomFieldsSearch\Admin\_is_installed;
use function ougc\CustomFieldsSearch\Admin\_uninstall;
use function ougc\CustomFieldsSearch\Core\addHooks;

use const ougc\CustomFieldsSearch\ROOT;

defined('IN_MYBB') || die('This file cannot be accessed directly.');

// You can uncomment the lines below to avoid storing some settings in the DB
define('ougc\CustomFieldsSearch\Core\SETTINGS', [
    //'key' => '',
]);

define('ougc\CustomFieldsSearch\Core\DEBUG', false);

define('ougc\CustomFieldsSearch\ROOT', constant('MYBB_ROOT') . 'inc/plugins/ougc/CustomFieldsSearch');

require_once ROOT . '/core.php';

defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

// Add our hooks
if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';
    require_once ROOT . '/admin_hooks.php';

    addHooks('ougc\CustomFieldsSearch\Hooks\Admin');
} else {
    require_once ROOT . '/forum_hooks.php';

    addHooks('ougc\CustomFieldsSearch\Hooks\Forum');
}

require_once ROOT . '/shared_hooks.php';

addHooks('ougc\CustomFieldsSearch\Hooks\Shared');

// Plugin API
function ougc_customfsearch_info(): array
{
    return _info();
}

// Activate the plugin.
function ougc_customfsearch_activate(): bool
{
    return _activate();
}

// Deactivate the plugin.
function ougc_customfsearch_deactivate(): bool
{
    return _deactivate();
}

// Install the plugin.
function ougc_customfsearch_install(): bool
{
    return _install();
}

// Check if installed.
function ougc_customfsearch_is_installed(): bool
{
    return _is_installed();
}

// Unnstall the plugin.
function ougc_customfsearch_uninstall(): bool
{
    return _uninstall();
}
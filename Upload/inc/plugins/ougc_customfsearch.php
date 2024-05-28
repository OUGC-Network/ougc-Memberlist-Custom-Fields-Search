<?php

/***************************************************************************
 *
 *    OUGC Custom Fields Search plugin (/inc/plugins/ougc_customfsearch.php)
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

// Die if IN_MYBB is not defined, for security reasons.
use function ougc\CustomFieldsSearch\Admin\_activate;
use function ougc\CustomFieldsSearch\Admin\_deactivate;
use function ougc\CustomFieldsSearch\Admin\_info;
use function ougc\CustomFieldsSearch\Admin\_install;
use function ougc\CustomFieldsSearch\Admin\_is_installed;
use function ougc\CustomFieldsSearch\Admin\_uninstall;
use function ougc\CustomFieldsSearch\Core\addHooks;

use const ougc\CustomFieldsSearch\ROOT;

defined('IN_MYBB') || die('This file cannot be accessed directly.');

define('ougc\CustomFieldsSearch\Core\DEBUG', false);

define('ougc\CustomFieldsSearch\ROOT', constant('MYBB_ROOT') . 'inc/plugins/ougc/CustomFieldsSearch');

require_once ROOT . '/core.php';

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

// Add our hooks
if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';
} else {
    require_once ROOT . '/forum_hooks.php';

    addHooks('ougc\CustomFieldsSearch\ForumHooks');
}

// Plugin API
function ougc_customfsearch_info()
{
    return _info();
}

// Activate the plugin.
function ougc_customfsearch_activate()
{
    _activate();
}

// Deactivate the plugin.
function ougc_customfsearch_deactivate()
{
    _deactivate();
}

// Install the plugin.
function ougc_customfsearch_install()
{
    _install();
}

// Check if installed.
function ougc_customfsearch_is_installed()
{
    return _is_installed();
}

// Unnstall the plugin.
function ougc_customfsearch_uninstall()
{
    _uninstall();
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com )
if (!function_exists('control_object')) {
    function control_object(&$obj, $code)
    {
        static $cnt = 0;
        $newname = '_objcont_' . (++$cnt);
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
}

if (!function_exists('control_db')) {
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
}
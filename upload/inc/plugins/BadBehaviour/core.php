<?php

/**
 * @package    Bad Behavior
 * @author    Eric Sizemore <admin@secondversion.com>
 * @version    1.0.1
 * @license    GNU LGPL https://www.gnu.org/licenses/lgpl.txt
 *
 *    Bad Behavior - Integrates MyBB and Bad Behavior
 *    Copyright (C) 2011 - 2014 Eric Sizemore
 *
 *    Bad Behavior is free software; you can redistribute it and/or modify it under
 *    the terms of the GNU Lesser General Public License as published by the Free
 *    Software Foundation; either version 3 of the License, or (at your option) any
 *    later version.
 *
 *    This program is distributed in the hope that it will be useful, but WITHOUT ANY
 *    WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 *    PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public License along
 *    with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
Bad Behavior - detects and blocks unwanted Web accesses
Copyright (C) 2005,2006,2007,2008,2009,2010,2011,2012,2013 Michael Hampton

Bad Behavior is free software; you can redistribute it and/or modify it under
the terms of the GNU Lesser General Public License as published by the Free
Software Foundation; either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License along
with this program. If not, see <https://www.gnu.org/licenses/>.

Please report any problems to bad . bots AT ioerror DOT us
https://www.bad-behavior.ioerror.us/
*/

declare(strict_types=1);

namespace BadBehaviour\Core;

function languageLoad(): void
{
    global $lang;

    if (isset($lang->badBehaviour)) {
        return;
    }

    $lang->load('badbehavior');
}

function addHooks(string $namespace): void
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

function settingsGet(string $setting_key = '')
{
    global $mybb;

    return $mybb->settings['badbehavior_' . $setting_key] ?? false;
}
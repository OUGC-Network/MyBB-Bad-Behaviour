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

namespace BadBehaviour\Admin;

use DirectoryIterator;

use function BadBehaviour\Core\languageLoad;

use const BadBehaviour\ROOT;
use const BadBehaviour\Core\VERSION;
use const BadBehaviour\Core\VERSION_CODE;

const TABLES_DATA = [
    'badbehavior' => [
        'id' => [
            'type' => 'INT',
            'size' => 11,
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'ip' => [
            'type' => 'TEXT',
        ],
        'date' => [
            'type' => 'DATETIME',
            'default' => '0000-00-00 00:00:00'
        ],
        'request_method' => [
            'type' => 'TEXT',
        ],
        'request_uri' => [
            'type' => 'TEXT',
        ],
        'server_protocol' => [
            'type' => 'TEXT',
        ],
        'http_headers' => [
            'type' => 'TEXT',
        ],
        'user_agent' => [
            'type' => 'TEXT',
        ],
        'request_entity' => [
            'type' => 'TEXT',
        ],
        'key' => [
            'type' => 'TEXT',
        ],
        'index_keys' => ['id' => 'id', 'user_agent' => 'user_agent'],
    ],
];

function pluginInformation(): array
{
    global $lang;

    languageLoad();

    return [
        'name' => 'Bad Behaviour',
        'description' => $lang->badBehaviourDescription,
        'website' => 'https://www.secondversion.com/',
        'author' => 'SecondV',
        'authorsite' => 'https://www.secondversion.com/',
        'version' => VERSION,
        'versioncode' => VERSION_CODE,
        'compatibility' => '18*',
        'codename' => 'ougc_badbehavior',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ],
    ];
}

function pluginActivation(): void
{
    global $PL, $lang;

    loadPluginLibrary();

    $settingsContents = file_get_contents(ROOT . '/settings.json');

    $settingsData = json_decode($settingsContents, true);

    foreach ($settingsData as $settingKey => &$settingData) {
        if (empty($lang->{"setting_badbehavior_{$settingKey}"})) {
            continue;
        }

        if (in_array($settingData['optionscode'], ['select', 'checkbox', 'radio'])) {
            foreach ($settingData['options'] as $optionKey) {
                $settingData['optionscode'] .= "\n{$optionKey}={$lang->{"setting_badbehavior_{$settingKey}_{$optionKey}"}}";
            }
        }

        $settingData['title'] = $lang->{"setting_badbehavior_{$settingKey}"};
        $settingData['description'] = $lang->{"setting_badbehavior_{$settingKey}_desc"};
    }

    $PL->settings(
        'badbehavior',
        $lang->badBehaviour,
        $lang->badBehaviourDescription,
        $settingsData
    );

    dbVerifyTables();

    change_admin_permission('tools', 'badbehavior');

    /*~*~* RUN UPDATES START *~*~*/
    /*~*~* RUN UPDATES END *~*~*/
}

function pluginDeactivation(): void
{
    // Update administrator permissions
    change_admin_permission('tools', 'badbehavior', 0);
}

function loadPluginLibrary(): void
{
    global $PL, $lang;

    languageLoad();

    if ($fileExists = file_exists(PLUGINLIBRARY)) {
        global $PL;

        $PL or require_once PLUGINLIBRARY;
    }

    $pluginInformation = pluginInformation();

    if (!$fileExists || $PL->version < $pluginInformation['pl']['version']) {
        flash_message(
            $lang->sprintf(
                $lang->badBehaviourPluginLibrary,
                $pluginInformation['pl']['url'],
                $pluginInformation['pl']['version']
            ),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }
}

function pluginInstallation(): void
{
    loadPluginLibrary();

    dbVerifyTables();
}

function pluginIsInstalled(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        $isInstalledEach = true;

        foreach (TABLES_DATA as $tableName => $tableColumns) {
            $isInstalledEach = $db->table_exists($tableName) && $isInstalledEach;

            if (!$isInstalledEach) {
                break;
            }

            foreach ($tableColumns as $fieldName => $fieldDefinition) {
                if ($fieldName === 'primary_key' || $fieldName === 'unique_key' || $fieldName === 'index_keys') {
                    continue;
                }

                $isInstalledEach = $db->field_exists($fieldName, $tableName);

                if (!$isInstalledEach) {
                    break;
                }
            }
        }

        $isInstalled = $isInstalledEach;
    }

    return $isInstalled;
}

function pluginUninstallation(): void
{
    global $db, $PL;

    loadPluginLibrary();

    foreach (TABLES_DATA as $tableName => $tableFields) {
        $db->drop_table($tableName);
    }

    $PL->settings_delete('badbehavior');

    // Remove administrator permissions
    change_admin_permission('tools', 'badbehavior', -1);
}

function dbTables(): array
{
    $tablesData = [];

    foreach (TABLES_DATA as $tableName => $tableColumns) {
        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            $tablesData[$tableName][$fieldName] = dbBuildFieldDefinition($fieldData);
        }

        foreach ($tableColumns as $fieldName => $fieldData) {
            if (isset($fieldData['primary_key'])) {
                $tablesData[$tableName]['primary_key'] = $fieldName;
            }

            if ($fieldName === 'unique_key') {
                $tablesData[$tableName]['unique_key'] = $fieldData;
            }

            if ($fieldName === 'index_keys') {
                $tablesData[$tableName]['index_keys'] = $fieldData;
            }
        }
    }

    return $tablesData;
}

function dbVerifyTables(): void
{
    global $db;

    $collation = $db->build_create_table_collation();

    foreach (dbTables() as $tableName => $tableColumns) {
        if ($db->table_exists($tableName)) {
            foreach ($tableColumns as $fieldName => $fieldData) {
                if ($fieldName === 'primary_key' || $fieldName === 'unique_key' || $fieldName === 'index_keys') {
                    continue;
                }

                if ($db->field_exists($fieldName, $tableName)) {
                    $db->modify_column($tableName, "`{$fieldName}`", $fieldData);
                } else {
                    $db->add_column($tableName, $fieldName, $fieldData);
                }
            }
        } else {
            $query_string = "CREATE TABLE IF NOT EXISTS `{$db->table_prefix}{$tableName}` (";

            foreach ($tableColumns as $fieldName => $fieldData) {
                if ($fieldName === 'primary_key') {
                    $query_string .= "PRIMARY KEY (`{$fieldData}`)";
                } elseif ($fieldName !== 'unique_key' && $fieldName !== 'index_keys') {
                    $query_string .= "`{$fieldName}` {$fieldData},";
                }
            }

            $query_string .= ") ENGINE=MyISAM{$collation};";

            $db->write_query($query_string);
        }
    }

    dbVerifyIndexes();
}

function dbVerifyIndexes(): void
{
    global $db;

    foreach (dbTables() as $tableName => $tableColumns) {
        if (!$db->table_exists($tableName)) {
            continue;
        }

        if (isset($tableColumns['unique_key'])) {
            foreach ($tableColumns['unique_key'] as $keyName => $keyValue) {
                if ($db->index_exists($tableName, $keyName)) {
                    continue;
                }

                $db->write_query(
                    "CREATE UNIQUE INDEX IF NOT EXISTS {$keyName} ON {$db->table_prefix}{$tableName} ({$keyName})"
                );
            }
        }

        if (isset($tableColumns['index_keys'])) {
            foreach ($tableColumns['index_keys'] as $indexName => $indexValue) {
                if ($db->index_exists($tableName, $indexName)) {
                    continue;
                }

                $db->write_query(
                    "CREATE INDEX IF NOT EXISTS {$indexName} ON {$db->table_prefix}{$tableName} ({$indexName})"
                );
            }
        }
    }
}

function dbBuildFieldDefinition(array $fieldData): string
{
    $field_definition = '';

    $field_definition .= $fieldData['type'];

    if (isset($fieldData['size'])) {
        $field_definition .= "({$fieldData['size']})";
    }

    if (isset($fieldData['unsigned'])) {
        if ($fieldData['unsigned'] === true) {
            $field_definition .= ' UNSIGNED';
        } else {
            $field_definition .= ' SIGNED';
        }
    }

    if (!isset($fieldData['null'])) {
        $field_definition .= ' NOT';
    }

    $field_definition .= ' NULL';

    if (isset($fieldData['auto_increment'])) {
        $field_definition .= ' AUTO_INCREMENT';
    }

    if (isset($fieldData['default'])) {
        $field_definition .= " DEFAULT '{$fieldData['default']}'";
    }

    return $field_definition;
}
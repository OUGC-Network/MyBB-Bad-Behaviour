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

// Disallow direct access to this file for security reasons
use function BadBehaviour\Admin\pluginActivation;
use function BadBehaviour\Admin\pluginDeactivation;
use function BadBehaviour\Admin\pluginInformation;
use function BadBehaviour\Admin\pluginInstallation;
use function BadBehaviour\Admin\pluginIsInstalled;

use function BadBehaviour\Admin\pluginUninstallation;

use function BadBehaviour\Core\addHooks;

use const BadBehaviour\ROOT;

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

define('BadBehaviour\ROOT', MYBB_ROOT . 'inc/plugins/BadBehaviour');

require_once ROOT . '/core.php';

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';
    require_once ROOT . '/hooks/admin.php';

    addHooks('BadBehaviour\Hooks\Admin');
}

define('BadBehaviour\Core\VERSION', '2.0.1');

define('BadBehaviour\Core\VERSION_CODE', 2001);

/**
 * Functions/options needed by Bad Behaviour
 */
define('BB2_CWD', dirname(__FILE__, 2));

// Settings you can adjust for Bad Behaviour.
// Most of these are unused in non-database mode.
// DO NOT EDIT HERE; instead make changes in MyBB Options
$bb2_settings_defaults = [
    'log_table' => TABLE_PREFIX . 'vb_badbehavior',
    'display_stats' => false,
    'strict' => false,
    'verbose' => false,
    'logging' => true,
    'httpbl_key' => '',
    'httpbl_threat' => '25',
    'httpbl_maxage' => '30',
    'offsite_forms' => true,
    'eu_cookie' => false,
    'reverse_proxy' => false,
    'reverse_proxy_header' => 'X-Forwarded-For',
    'reverse_proxy_addresses' => []
];

// Return the current time in the format preferred by your database.
function bb2_db_date(): string
{
    return gmdate('d-m-Y H:i:s');
}

// Return affected rows from the most recent query.
function bb2_db_affected_rows(): int
{
    global $db;

    return $db->affected_rows();
}

// Escape a string for database usage
function bb2_db_escape(string $string): string
{
    global $db;

    return $db->escape_string($string);
}

// Return the number of rows in a particular query.
function bb2_db_num_rows($result): int
{
    if ($result !== false) {
        return count($result);
    }
    return 0;
}

// Run a query and return the results, if any.
// Should return FALSE if an error occurred.
// Bad Behaviour will use the return value here in other callbacks.
function bb2_db_query(string $query): int|bool|array
{
    global $db;

    $db->error_reporting = 0;

    if (preg_match("/^\\s*(insert|delete|update|replace|alter|set) /i", $query)) {
        $db->query($query);

        return $db->affected_rows();
    }

    $result = [];
    $results = [];

    $query = $db->query($query);

    if (!$query) {
        return false;
    }

    $i = 0;

    while ($row = $db->fetch_array($query)) {
        $results[$i] = (object)$row;
        $i++;
    }

    if (!$results) {
        return false;
    }

    foreach ((array)$results as $row) {
        $result[] = get_object_vars($row);
    }

    $db->error_reporting = 1;

    return $result;
}

// Return all rows in a particular query.
// Should contain an array of all rows generated by calling mysql_fetch_assoc()
// or equivalent and appending the result of each call to an array.
function bb2_db_rows(array $result): array
{
    return $result;
}

// Our log table structure
function bb2_table_structure(string $name): string
{
    // It's not paranoia if they really are out to get you.
    $name_escaped = bb2_db_escape($name);
    return "CREATE TABLE IF NOT EXISTS `$name_escaped` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`ip` TEXT NOT NULL,
	`date` DATETIME NOT NULL default '0000-00-00 00:00:00',
	`request_method` TEXT NOT NULL,
	`request_uri` TEXT NOT NULL,
	`server_protocol` TEXT NOT NULL,
	`http_headers` TEXT NOT NULL,
	`user_agent` TEXT NOT NULL,
	`request_entity` TEXT NOT NULL,
	`key` TEXT NOT NULL,
	INDEX (`ip`(15)),
	INDEX (`user_agent`(10)),
	PRIMARY KEY (`id`)
);";
}

// Create the SQL query for inserting a record in the database.
function bb2_insert(array $settings, array $package, string $key): string
{
    $ip = bb2_db_escape($package['ip']);
    $request_method = bb2_db_escape($package['request_method']);
    $request_uri = bb2_db_escape($package['request_uri']);
    $server_protocol = bb2_db_escape($package['server_protocol']);
    $user_agent = bb2_db_escape($package['user_agent']);
    $headers = "$request_method $request_uri $server_protocol\n";

    foreach ($package['headers'] as $h => $v) {
        $headers .= bb2_db_escape("$h: $v\n");
    }

    $request_entity = '';

    if (!strcasecmp($request_method, 'POST')) {
        foreach ($package['request_entity'] as $h => $v) {
            $request_entity .= bb2_db_escape("$h: $v\n");
        }
    }

    return 'INSERT INTO `' . bb2_db_escape($settings['log_table']) . "`
	(`ip`, `date`, `request_method`, `request_uri`, `server_protocol`, `http_headers`, `user_agent`, `request_entity`, `key`)
VALUES
	('$ip', NOW(), '$request_method', '$request_uri', '$server_protocol', '$headers', '$user_agent', '$request_entity', '$key')";
}

// Return emergency contact email address.
function bb2_email(): string
{
    global $mybb;

    if (!empty($mybb->settings['adminemail'])) {
        return str_replace(['@', '.'], ['(&#64;)', '(&#46;)'], $mybb->settings['adminemail']);
    } else {
        return '';
    }
}

// Converts yes/no in MyBB Options to true/false
function __bb2_read_settings_helper(int $value): bool
{
    return $value === 1;
}

// retrieve settings from database
function bb2_read_settings(): array
{
    global $mybb;

    // http:BL Do we have an API Key?
    // All Access Keys are 12-characters in length, lower case, and contain only alpha characters (no numbers).
    if (strlen($mybb->settings['badbehavior_httpbl_key']) !== 12 || !ctype_lower(
            $mybb->settings['badbehavior_httpbl_key']
        )) {
        $mybb->settings['badbehavior_httpbl_key'] = '';
    }

    // http:BL Threat Level needs to be an integer
    if ((int)$mybb->settings['badbehavior_httpbl_threat'] === 0) {
        $mybb->settings['badbehavior_httpbl_threat'] = 25;
    }

    // http:BL Max. Age needs to be an integer as well
    if ((int)$mybb->settings['badbehavior_httpbl_maxage'] === 0) {
        $mybb->settings['badbehavior_httpbl_maxage'] = 30;
    }

    // Make sure that the proxy addresses are split into an array, and if it's empty - make sure reverse proxy is disabled
    if (!empty($mybb->settings['badbehavior_reverse_proxy_addresses'])) {
        $mybb->settings['badbehavior_reverse_proxy_addresses'] = preg_split(
            "#\n#",
            trim($mybb->settings['badbehavior_reverse_proxy_addresses']),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
    } else {
        $mybb->settings['badbehavior_reverse_proxy_addresses'] = [];
        $mybb->settings['badbehavior_reverse_proxy'] = 0;
    }

    // also, make sure the header is set
    if (empty($mybb->settings['badbehavior_reverse_proxy_header'])) {
        $mybb->settings['badbehavior_reverse_proxy_header'] = 'X-Forwarded-For';
    }

    // return settings
    return [
        'log_table' => TABLE_PREFIX . 'badbehavior',
        'display_stats' => false,
        'strict' => __bb2_read_settings_helper((int)$mybb->settings['badbehavior_strict']),
        'verbose' => __bb2_read_settings_helper((int)$mybb->settings['badbehavior_verbose']),
        'logging' => __bb2_read_settings_helper((int)$mybb->settings['badbehavior_logging']),
        'httpbl_key' => $mybb->settings['badbehavior_httpbl_key'],
        'httpbl_threat' => $mybb->settings['badbehavior_httpbl_threat'],
        'httpbl_maxage' => $mybb->settings['badbehavior_httpbl_maxage'],
        'offsite_forms' => true,
        'eu_cookie' => __bb2_read_settings_helper((int)$mybb->settings['badbehavior_eu_cookie']),
        'reverse_proxy' => __bb2_read_settings_helper((int)$mybb->settings['badbehavior_reverse_proxy']),
        'reverse_proxy_header' => $mybb->settings['badbehavior_reverse_proxy_header'],
        'reverse_proxy_addresses' => $mybb->settings['badbehavior_reverse_proxy_addresses']
    ];
}

// write settings to a database
function bb2_write_settings(array $settings): bool
{
    return false;
}

// installation
function bb2_install(): bool
{
    return false;
}

// Screener
// Inserts this into the <head> section of your HTML through a template call
// or whatever is appropriate. This is optional we'll fall back to cookies
// if you don't use it.
function bb2_insert_head(): string
{
    global $bb2_javascript;

    if (!isset($bb2_javascript)) {
        return '';
    }

    return $bb2_javascript;
}

// Display stats? This is optional.
function bb2_insert_stats(bool $force = false): string
{
    return '';
}

// Return the top-level relative path of wherever we are (for cookies)
// You should provide in $url the top-level URL for your site.
function bb2_relative_path(): string
{
    global $mybb;

    $url = parse_url($mybb->settings['bburl']);
    return $url['path'] . '/';
}

// Calls inward to Bad Behavor itself.
require_once(BB2_CWD . '/bad-behavior/core.inc.php');

// ##################################################################
function badbehavior_info(): array
{
    return pluginInformation();
}

function badbehavior_install(): void
{
    pluginInstallation();
}

function badbehavior_uninstall(): void
{
    pluginUninstallation();
}

function badbehavior_is_installed(): bool
{
    return pluginIsInstalled();
}

function badbehavior_activate(): void
{
    pluginActivation();
}

function badbehavior_deactivate(): void
{
    pluginDeactivation();
}

global $mybb;

$activePlugins = $mybb->cache->read('plugins');

if (!empty($activePlugins['active']['badbehavior'])) {
    bb2_start(bb2_read_settings());
}
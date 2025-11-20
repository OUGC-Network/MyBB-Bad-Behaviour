<?php

/**
 * @package    Bad Behavior
 * @author    Eric Sizemore <admin@secondversion.com>
 * @version    1.0.1
 * @license    GNU LGPL http://www.gnu.org/licenses/lgpl.txt
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
 *    with this program. If not, see <http://www.gnu.org/licenses/>.
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
with this program. If not, see <http://www.gnu.org/licenses/>.

Please report any problems to bad . bots AT ioerror DOT us
http://www.bad-behavior.ioerror.us/
*/

$l = [
    'badBehaviour' => 'Bad Behaviour',
    'badBehaviourDescription' => 'This is an integration of the Bad Behaviour software with MyBB. Bad Behaviour is a PHP-based solution for blocking link spam and the robots which deliver it.',

    'setting_group_badBehaviour' => 'Bad Behaviour',
    'setting_group_badBehaviour_desc' => 'Bad Behaviour is a PHP-based solution for blocking link spam and the robots which deliver it.',

    'setting_badbehavior_strict' => 'Operating Mode',
    'setting_badbehavior_strict_desc' => 'Bad Behaviour operates in two blocking modes: normal and strict. When strict mode is enabled, some additional checks for buggy software which have been spam sources are enabled, but occasional legitimate users using the same software (usually corporate or government users using very old software) may be blocked as well.',
    'setting_badbehavior_logging' => 'Logging',
    'setting_badbehavior_logging_desc' => 'Should Bad Behaviour keep a log of requests? On by default, and it is not recommended to disable it, since it will cause additional spam to get through.',
    'setting_badbehavior_verbose' => 'Verbose Logging',
    'setting_badbehavior_verbose_desc' => 'Turning on verbose mode causes all HTTP requests to be logged. When verbose mode is off, only blocked requests and a few suspicious (but permitted) requests are logged.<br /><br />Verbose mode is off by default. Using verbose mode is not recommended as it can significantly slow down your site; it exists to capture data from live spammers which are not being blocked.',
    'setting_badbehavior_httpbl_key' => 'http:BL Api Key',
    'setting_badbehavior_httpbl_key_desc' => 'Bad Behaviour is capable of using data from the <a href="https://www.projecthoneypot.org/faq.php#g" target="_blank">http:BL</a> service provided by <a href="https://www.projecthoneypot.org/" target="_blank">Project Honey Pot</a> to screen requests.<br /><br />This is purely optional; however if you wish to use it, you must <a href="https://www.projecthoneypot.org/httpbl_configure.php" target="_blank">sign up for the service</a> and obtain an API key. To disable http:BL use, remove the API key from your settings.',
    'setting_badbehavior_httpbl_threat' => 'http:BL Thread Level',
    'setting_badbehavior_httpbl_threat_desc' => 'This number provides a measure of how suspicious an IP address is, based on activity observed at Project Honey Pot. Bad Behaviour will block requests with a threat level equal or higher to this setting. Project Honey Pot has <a href="https://www.projecthoneypot.org/threat_info.php" target="_blank">more information on this parameter</a>.',
    'setting_badbehavior_httpbl_maxage' => 'http:BL Maximum Age',
    'setting_badbehavior_httpbl_maxage_desc' => 'This is the number of days since suspicious activity was last observed from an IP address by Project Honey Pot. Bad Behaviour will block requests with a maximum age equal to or less than this setting.',
    'setting_badbehavior_eu_cookie' => 'EU Cookie',
    'setting_badbehavior_eu_cookie_desc' => 'Set this option to "yes" if you believe Bad Behaviour\'s site security cookie is not exempt from the 2012 EU cookie regulation. <a href="https://bad-behavior.ioerror.us/2012/05/03/bad-behavior-2-2-4/">[more info]</a>',
    'setting_badbehavior_reverse_proxy' => 'Reverse Proxy',
    'setting_badbehavior_reverse_proxy_desc' => 'When enabled, Bad Behaviour will assume it is receiving a connection from a reverse proxy, when a specific HTTP header is received.',
    'setting_badbehavior_reverse_proxy_header' => 'Reverse Proxy Header',
    'setting_badbehavior_reverse_proxy_header_desc' => 'When Reverse Proxy is enabled, Bad Behaviour checks this header to locate the true IP address of the connecting client.',
    'setting_badbehavior_reverse_proxy_addresses' => 'Reverse Proxy Addresses',
    'setting_badbehavior_reverse_proxy_addresses_desc' => 'IP address or CIDR netblocks which Bad Behaviour trusts to provide reliable information in the HTTP header given above. If no addresses are given, Bad Behaviour will assume that the HTTP header given is always trustworthy and that the right-most IP address appearing in the header is correct.<br /><br />If you have a chain of two or more proxies, this is probably not what you want; in this scenario you should either set this option and provide all proxy server IP addresses (or ranges) which could conceivably handle the request, or have your edge servers set a unique HTTP header with the client\'s IP address.<br /><br />For instance, when using CloudFlare, it is impossible to provide a list of IP addresses, so you would set the HTTP header to CloudFlares provided "CF-Connecting-IP" header instead.<br /><br /><strong style="color: #ff0000;">NOTE: Enter one ip address/CIDR netblock per line.</strong>',

    'badBehaviourPluginLibrary' => 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.',

    'badbehavior_logs_index' => 'Bad Behavior Logs',
    'badbehavior_logs_canmanage' => 'Can manage Bad Behavior Logs?',
    'badbehavior_logs' => 'Bad Behavior Log',
    'badbehavior_logs_desc' => 'View bad behavior logs.',
    'badbehavior_logs_prune' => 'Prune Log',
    'badbehavior_logs_prune_days' => 'Older than',
    'badbehavior_logs_prune_days_desc' => 'Prune log entries older than the number of days you enter.',
    'badbehavior_logs_ipaddress' => 'IP Address',
    'badbehavior_logs_key' => 'Key',
    'badbehavior_logs_useragent' => 'User Agent',
    'badbehavior_logs_request_method' => 'Method',
    'badbehavior_logs_server_protocol' => 'Protocol',
    'badbehavior_logs_request_uri' => 'URI',
    'badbehavior_logs_request_entity' => 'Entity',
    'badbehavior_logs_http_headers' => 'Headers',
    'badbehavior_logs_date' => 'Date',
    'badbehavior_logs_options' => 'Options',
    'badbehavior_logs_delete' => 'Delete',
    'badbehavior_logs_none' => 'No logs found.',
    'badbehavior_logs_error' => 'An unknown error has occurred.',
    'badbehavior_logs_invalid' => 'Invalid log entry.',
    'badbehavior_logs_deleted' => 'Log entry deleted successfully.',
    'badbehavior_logs_pruned' => 'Log pruned successfully.',
    'badbehavior_logs_pruneconfirm' => 'Are you sure you want to prune the log?',
    'badbehavior_logs_deleteconfirm' => 'Are you sure you want to delete the selected log entry?',
    'badbehavior_logs_submit' => 'Submit',
    'badbehavior_logs_reset' => 'Reset',
];
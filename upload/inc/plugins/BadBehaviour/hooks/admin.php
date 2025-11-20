<?php

/**
 * BadBehaviour 1.8.0
 * Copyright 2010 Matthew Rogowski
 * https://matt.rogow.ski/
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 ** http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

declare(strict_types=1);

namespace BadBehaviour\Hooks\Admin;

use Form;
use FormContainer;
use MyBB;
use Table;

use function BadBehaviour\Core\languageLoad;

function admin_config_plugins_deactivate(): void
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') !== 'deactivate' ||
        $mybb->get_input('plugin') !== 'badbehavior' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return;
    }

    if ($mybb->request_method !== 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=badbehavior'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }
}

function admin_config_settings_begin(): void
{
    languageLoad();
}

function admin_load(): void
{
    global $db, $lang, $mybb, $page, $run_module, $action_file;

    $lang->load('badbehavior', false, true);

    $sub_tabs = [];

    if ($run_module === 'tools' && $action_file === 'badbehavior') {
        if ($mybb->get_input('action') === 'keycheck') {
            define('BB2_CORE', dirname(__FILE__, 2) . '/bad-behavior/');
            require_once(BB2_CORE . '/responses.inc.php');

            $response = bb2_get_response($mybb->get_input('key'));

            if ($response[0] === '00000000') {
                echo 'Unknown';
            } else {
                echo <<<KEY
HTTP Response: {$response['response']}<br />\n
Explanation: {$response['explanation']}<br />\n
Log Message: {$response['log']}<br />\n
KEY;
            }
            exit;
        }

        if (!$mybb->get_input('action')) {
            $page->add_breadcrumb_item($lang->badbehavior_logs, 'index.php?module=tools-badbehavior');

            $page->output_header($lang->badbehavior_logs);

            $sub_tabs['badbehavior_logs'] = [
                'title' => $lang->badbehavior_logs,
                'link' => 'index.php?module=tools-badbehavior',
                'description' => $lang->badbehavior_logs_desc
            ];
        }

        if (!$mybb->get_input('action')) {
            $page->output_nav_tabs($sub_tabs, 'badbehavior_logs');

            $per_page = 15;

            if ($mybb->get_input('page', MyBB::INPUT_INT) > 1) {
                $start = ($mybb->get_input('page', MyBB::INPUT_INT) * $per_page) - $per_page;
            } else {
                $mybb->input['page'] = 1;
                $start = 0;
            }

            $query = $db->simple_select('badbehavior', 'COUNT(id) AS logs');
            $total_rows = $db->fetch_field($query, 'logs');

            echo '<br />' . draw_admin_pagination(
                    $mybb->get_input('page', MyBB::INPUT_INT),
                    $per_page,
                    $total_rows,
                    'index.php?module=tools-badbehavior&amp;page={page}'
                );

            // table
            $table = new Table();
            $table->construct_header($lang->badbehavior_logs_ipaddress);
            $table->construct_header($lang->badbehavior_logs_date);
            $table->construct_header($lang->badbehavior_logs_key);
            $table->construct_header($lang->badbehavior_logs_useragent);
            $table->construct_header(
                $lang->badbehavior_logs_request_method . '/' . $lang->badbehavior_logs_server_protocol
            );
            $table->construct_header($lang->badbehavior_logs_request_uri);
            $table->construct_header(
                $lang->badbehavior_logs_request_entity . '/' . $lang->badbehavior_logs_http_headers
            );
            $table->construct_header($lang->badbehavior_logs_options);

            $query = $db->simple_select(
                'badbehavior',
                '*',
                options: ['order_by' => 'date', 'order_dir' => 'desc', 'limit_start' => $start, 'limit' => $per_page],
            );

            if (!$db->num_rows($query)) {
                $table->construct_cell($lang->badbehavior_logs_none, ['colspan' => 10]);
                $table->construct_row();
            } else {
                while ($log = $db->fetch_array($query)) {
                    $table->construct_cell($log['ip']);
                    $table->construct_cell($log['date']);
                    $table->construct_cell(
                        "<a href=\"#\" onclick=\"window.open('index.php?module=tools-badbehavior&amp;action=keycheck&amp;key={$log['key']}', 'keycheck', 'width=200,height=200');return false;\">{$log['key']}</a>"
                    );
                    $table->construct_cell(
                        "<input type=\"text\" value=\"{$log['user_agent']}\" onclick=\"alert(this.value);\" />"
                    );
                    $table->construct_cell($log['request_method'] . '<br />' . $log['server_protocol']);
                    $table->construct_cell(
                        "<input type=\"text\" value=\"{$log['request_uri']}\" onclick=\"alert(this.value);\" />"
                    );
                    $table->construct_cell(
                        ($log['request_entity'] ? "<textarea style=\"width: 80%;\" onclick=\"alert(this.value);\">{$log['request_entity']}</textarea>" : '') . '<br />' . "<textarea style=\"width: 80%;\" onclick=\"alert(this.value);\">{$log['http_headers']}</textarea>"
                    );
                    $table->construct_cell(
                        "<a href=\"index.php?module=tools-badbehavior&amp;action=delete_log&amp;id={$log['id']}\">$lang->badbehavior_logs_delete</a>"
                    );
                    $table->construct_row();
                }
            }

            $table->output($lang->badbehavior_logs);

            echo '<br />';

            $form = new Form('index.php?module=tools-badbehavior&amp;action=prune', 'post', 'badbehavior_logs');

            echo $form->generate_hidden_field('my_post_key', $mybb->post_code);

            $form_container = new FormContainer($lang->badbehavior_logs_prune);
            $form_container->output_row(
                $lang->badbehavior_logs_prune_days,
                $lang->badbehavior_logs_prune_days_desc,
                $form->generate_text_box('days', 30, ['id' => 'days']),
                'days'
            );
            $form_container->end();

            $buttons = [];
            $buttons[] = $form->generate_submit_button($lang->badbehavior_logs_submit);
            $buttons[] = $form->generate_reset_button($lang->badbehavior_logs_reset);

            $form->output_submit_wrapper($buttons);
            $form->end();
        } elseif ($mybb->get_input('action') === 'delete_log') {
            if (isset($mybb->input['no'])) {
                admin_redirect('index.php?module=tools-badbehavior');
            }

            if ($mybb->request_method === 'post') {
                if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
                    $mybb->request_method = 'get';
                    flash_message($lang->badbehavior_logs_error, 'error');
                    admin_redirect('index.php?module=tools-badbehavior');
                }

                if (!$db->fetch_field(
                    $db->simple_select(
                        'badbehavior',
                        'id',
                        'id=' . $mybb->get_input('id', MyBB::INPUT_INT),
                        ['limit' => 1]
                    ),
                    'id'
                )) {
                    flash_message($lang->badbehavior_logs_invalid, 'error');
                    admin_redirect('index.php?module=tools-badbehavior');
                } else {
                    $db->delete_query('badbehavior', 'id=' . $mybb->get_input('id', MyBB::INPUT_INT));
                    flash_message($lang->badbehavior_logs_deleted, 'success');
                    admin_redirect('index.php?module=tools-badbehavior');
                }
            } else {
                $page->add_breadcrumb_item($lang->badbehavior_logs, 'index.php?module=tools-badbehavior');

                $page->output_header($lang->badbehavior_logs);

                $form = new Form(
                    "index.php?module=tools-badbehavior&amp;action=delete_log&amp;id={$mybb->get_input('id', MyBB::INPUT_INT)}&amp;my_post_key={$mybb->post_code}",
                    'post'
                );
                echo "<div class=\"confirm_action\"><p>{$lang->badbehavior_logs_deleteconfirm}</p>\n<br />\n";
                echo "<p class=\"buttons\">\n";
                echo $form->generate_submit_button($lang->yes, ['class' => 'button_yes']);
                echo $form->generate_submit_button($lang->no, ['name' => 'no', 'class' => 'button_no']);
                echo "</p>\n";
                echo "</div>\n";
                $form->end();
            }
        } elseif ($mybb->get_input('action') === 'prune') {
            if (isset($mybb->input['no'])) {
                admin_redirect('index.php?module=tools-badbehavior');
            }

            if ($mybb->request_method === 'post') {
                if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
                    $mybb->request_method = 'get';
                    flash_message($lang->badbehavior_logs_error, 'error');
                    admin_redirect('index.php?module=tools-badbehavior');
                }

                $db->delete_query(
                    'badbehavior',
                    'UNIX_TIMESTAMP(date) < ' . (TIME_NOW - $mybb->get_input('days', MyBB::INPUT_INT) * 60 * 60 * 24)
                );
                flash_message($lang->badbehavior_logs_pruned, 'success');
                admin_redirect('index.php?module=tools-badbehavior');
            } else {
                $page->add_breadcrumb_item($lang->badbehavior_logs, 'index.php?module=tools-badbehavior');

                $page->output_header($lang->badbehavior_logs);

                $form = new Form(
                    "index.php?module=tools-badbehavior&amp;action=prune&amp;days={$mybb->get_input('days', MyBB::INPUT_INT)}&amp;my_post_key={$mybb->post_code}",
                    'post'
                );
                echo "<div class=\"confirm_action\">\n<p>{$lang->badbehavior_logs_pruneconfirm}</p>\n<br />\n";
                echo "<p class=\"buttons\">\n";
                echo $form->generate_submit_button($lang->yes, ['class' => 'button_yes']);
                echo $form->generate_submit_button($lang->no, ['name' => 'no', 'class' => 'button_no']);
                echo "</p>\n";
                echo "</div>\n";
                $form->end();
            }
        }
        $page->output_footer();
        exit;
    }
}

function admin_tools_menu_logs(array &$menuItems): array
{
    global $lang;

    $lang->load('badbehavior');

    $menuItems[] = [
        'id' => 'badbehavior',
        'title' => $lang->badbehavior_logs_index,
        'link' => 'index.php?module=tools-badbehavior'
    ];

    return $menuItems;
}

function admin_tools_action_handler(array &$actionItems): array
{
    $actionItems['badbehavior'] = ['active' => 'badbehavior', 'file' => 'badbehavior'];

    return $actionItems;
}

function admin_tools_permissions(array &$adminPermissions): void
{
    global $lang;

    $lang->load('badbehavior', false, true);

    $adminPermissions['badbehavior'] = $lang->badbehavior_logs_canmanage;
}
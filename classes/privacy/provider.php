<?php
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
// SPDX-License-Identifier: GPL-3.0-or-later
//
// This plugin is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this plugin. If not, see <https://www.gnu.org/licenses/>.
/**
 * certificateelement_coursefieldimg.
 * @package certificateelement_coursefieldimg
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace certificateelement_coursefieldimg\privacy;
use certificateelement_coursefieldimg\snapshot;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
defined('MOODLE_INTERNAL') || die();

/** Export and delete immutable certificate image copies belonging to certificate recipients. */
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {
    /** Describe snapshot ownership and attached files. */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table(snapshot::TABLE, ['userid' => 'privacy:userid', 'issueid' => 'privacy:issueid',
            'elementid' => 'privacy:elementid', 'filename' => 'privacy:filename', 'timecreated' => 'privacy:timecreated'],
            'privacy:snapshots');
        $collection->add_subsystem_link('core_files', [], 'privacy:files');
        return $collection;
    }

    /** Snapshot images live in system context and remain owned by their issue recipient. */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        if ($DB->record_exists(snapshot::TABLE, ['userid' => $userid])) {
            $list->add_system_context();
        }
        return $list;
    }

    /** Include every recipient represented in this context. */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel === CONTEXT_SYSTEM) {
            $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {' . snapshot::TABLE . '}', []);
        }
    }

    /** Export metadata and images together with the recipient's certificate references. */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (!in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            return;
        }
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        foreach ($DB->get_records(snapshot::TABLE, ['userid' => $contextlist->get_user()->id]) as $record) {
            $subcontext = [get_string('pluginname', 'certificateelement_coursefieldimg'), (string)$record->issueid,
                (string)$record->elementid];
            $writer->export_data($subcontext, $record);
            if ($record->filename !== '') {
                $file = get_file_storage()->get_file(\context_system::instance()->id,
                    'certificateelement_coursefieldimg', 'snapshot', $record->issueid,
                    '/' . $record->elementid . '/', $record->filename);
                if ($file) {
                    $writer->export_file($subcontext, $file);
                }
            }
        }
    }

    /** Remove every private snapshot in the approved context. */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            self::delete_users(null);
        }
    }

    /** Remove one approved recipient's copies. */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            self::delete_users([$contextlist->get_user()->id]);
        }
    }

    /** Remove only recipients explicitly selected in a bulk privacy request. */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($userlist->get_context()->contextlevel === CONTEXT_SYSTEM) {
            self::delete_users($userlist->get_userids());
        }
    }

    /** Narrow deletions by recipient, leaving other users' issues intact. */
    private static function delete_users(?array $userids): void {
        global $DB;
        if ($userids === []) {
            return;
        }
        $records = $userids === null ? $DB->get_records(snapshot::TABLE) :
            $DB->get_records_list(snapshot::TABLE, 'userid', $userids);
        foreach (array_unique(array_column($records, 'issueid')) as $issueid) {
            snapshot::delete_issue((int)$issueid);
        }
    }
}

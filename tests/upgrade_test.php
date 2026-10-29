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
namespace certificateelement_coursefieldimg;
defined('MOODLE_INTERNAL') || die();

/** Verify retained snapshot rows and files across the table-name migration. */
final class upgrade_test extends \advanced_testcase {
    public function test_legacy_snapshot_table_is_renamed_without_data_loss(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $this->setAdminUser();
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/admin/tool/certificate/element/coursefieldimg/db/upgrade.php');
        $dbman = $DB->get_manager();
        $table = new \xmldb_table(snapshot::TABLE);
        $legacy = new \xmldb_table('certel_cfimg_snapshot');
        $this->assertFalse($dbman->table_exists($legacy));
        $this->assertSame('certificateelement_coursefieldimg_snapshot', snapshot::TABLE);
        $DB->insert_record(snapshot::TABLE, (object)['issueid' => 7101, 'elementid' => 21,
            'userid' => 2, 'filename' => 'signature.png', 'timecreated' => 1700000000]);
        $DB->insert_record(snapshot::TABLE, (object)['issueid' => 7101, 'elementid' => 0,
            'userid' => 2, 'filename' => '', 'timecreated' => 1700000000]);
        $before = $DB->get_records(snapshot::TABLE, null, 'id');
        $file = get_file_storage()->create_file_from_string(['contextid' => \context_system::instance()->id,
            'component' => 'certificateelement_coursefieldimg', 'filearea' => 'snapshot',
            'itemid' => 7101, 'filepath' => '/21/', 'filename' => 'signature.png'], 'retained snapshot bytes');
        $hash = $file->get_contenthash();
        $dbman->rename_table($table, $legacy->getName());
        try {
            set_config('version', 2026102901, 'certificateelement_coursefieldimg');
            $this->assertTrue(xmldb_certificateelement_coursefieldimg_upgrade(2026102901));
            $this->assertFalse($dbman->table_exists($legacy));
            $this->assertSame(serialize($before), serialize($DB->get_records(snapshot::TABLE, null, 'id')));
            $this->assertTrue($dbman->index_exists($table, new \xmldb_index('issueelement', XMLDB_INDEX_UNIQUE, ['issueid', 'elementid'])));
            $this->assertTrue($dbman->index_exists($table, new \xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid'])));
            $retained = get_file_storage()->get_file_by_id($file->get_id());
            $this->assertSame($hash, $retained->get_contenthash());
            $this->assertSame('retained snapshot bytes', $retained->get_content());
            // Simulate interruption after rename but before the version savepoint.
            set_config('version', 2026102901, 'certificateelement_coursefieldimg');
            $this->assertTrue(xmldb_certificateelement_coursefieldimg_upgrade(2026102901));
            $this->assertSame(serialize($before), serialize($DB->get_records(snapshot::TABLE, null, 'id')));
        } finally {
            if ($dbman->table_exists($legacy) && !$dbman->table_exists($table)) {
                $dbman->rename_table($legacy, $table->getName());
            }
        }
    }
}

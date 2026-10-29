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
defined('MOODLE_INTERNAL') || die();

/** Preserve issued-image snapshots while adopting the component table prefix.
 * @param int $oldversion installed build
 * @return bool
 */
function xmldb_certificateelement_coursefieldimg_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026102902) {
        $dbman = $DB->get_manager();
        $legacy = new xmldb_table('certel_cfimg_snapshot');
        $current = new xmldb_table('certificateelement_coursefieldimg_snapshot');
        if ($dbman->table_exists($legacy)) {
            if ($dbman->table_exists($current)) {
                throw new coding_exception('Both course field image snapshot tables exist; manual reconciliation required.');
            }
            $dbman->rename_table($legacy, $current->getName());
        }
        if (!$dbman->table_exists($current)) {
            throw new coding_exception('Course field image snapshot table is missing.');
        }
        upgrade_plugin_savepoint(true, 2026102902, 'certificateelement', 'coursefieldimg');
    }
    return true;
}

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

/** Immutable image copies keyed by native issue and original element IDs. */
final class snapshot {
    public const TABLE = 'certel_cfimg_snapshot';

    /** Freeze all configured images, plus an issue-level marker, at most once. */
    public static function capture(int $issueid): void {
        global $DB;
        if ($issueid <= (int)get_config('certificateelement_coursefieldimg', 'legacymaxissueid') ||
                $DB->record_exists(self::TABLE, ['issueid' => $issueid, 'elementid' => 0])) {
            return;
        }
        $lock = \core\lock\lock_config::get_lock_factory('certificateelement_coursefieldimg')->get_lock('issue_' . $issueid, 10);
        if (!$lock) {
            debugging('Course field image: could not acquire issue snapshot lock.', DEBUG_DEVELOPER);
            return;
        }
        try {
            if ($DB->record_exists(self::TABLE, ['issueid' => $issueid, 'elementid' => 0])) {
                return;
            }
            $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid]);
            if (!$issue) {
                return;
            }
            $issuedata = json_decode($issue->data ?: '{}', true);
            if (!is_array($issuedata) || !empty($issuedata['_coursefieldimg_frozen'])) {
                return;
            }
            $template = \tool_certificate\template::instance($issue->templateid);
            $transaction = $DB->start_delegated_transaction();
            foreach ($template->get_pages() as $page) {
                foreach ($page->get_elements() as $element) {
                    if (!$element instanceof element) {
                        continue;
                    }
                    $filename = '';
                    try {
                        $file = image_source::resolve((int)$issue->courseid, $element->fieldid());
                        if ($file) {
                            $filename = $file->get_filename();
                            get_file_storage()->create_file_from_storedfile([
                                'contextid' => \context_system::instance()->id,
                                'component' => 'certificateelement_coursefieldimg', 'filearea' => 'snapshot',
                                'itemid' => $issueid, 'filepath' => '/' . $element->get_id() . '/',
                                'filename' => $filename, 'userid' => $issue->userid,
                            ], $file);
                        }
                    } catch (\Throwable $e) {
                        // A missing/corrupt source is deliberately frozen as empty; never interrupt the certificate.
                        $filename = '';
                        debugging('Course field image: image unavailable for element ' . $element->get_id() . '.', DEBUG_DEVELOPER);
                    }
                    self::record($issue, $element->get_id(), $filename);
                }
            }
            self::record($issue, 0, '');
            // A parent-owned marker prevents recreation after Privacy API deletion of our files/rows.
            // Preserve all existing issue data; image URLs and course identity are never read from it.
            $issuedata['_coursefieldimg_frozen'] = 1;
            $DB->set_field('tool_certificate_issues', 'data', json_encode($issuedata), ['id' => $issueid]);
            $transaction->allow_commit();
        } finally {
            $lock->release();
        }
    }

    /** Record empty states as well as image filenames. */
    private static function record(\stdClass $issue, int $elementid, string $filename): void {
        global $DB;
        $DB->insert_record(self::TABLE, (object)['issueid' => $issue->id, 'elementid' => $elementid,
            'userid' => $issue->userid, 'filename' => $filename, 'timecreated' => time()]);
    }

    /** Return only the saved image; a lost copy never falls back to a mutable course field. */
    public static function image(int $issueid, int $elementid): ?\stored_file {
        global $DB;
        self::capture($issueid);
        $record = $DB->get_record(self::TABLE, ['issueid' => $issueid, 'elementid' => $elementid]);
        if (!$record || $record->filename === '') {
            return null;
        }
        $file = get_file_storage()->get_file(\context_system::instance()->id, 'certificateelement_coursefieldimg',
            'snapshot', $issueid, '/' . $elementid . '/', $record->filename);
        return $file && image_source::valid($file) ? $file : null;
    }

    /** Delete one issue's files and metadata, also used by the Privacy API. */
    public static function delete_issue(int $issueid): void {
        global $DB;
        get_file_storage()->delete_area_files(\context_system::instance()->id, 'certificateelement_coursefieldimg',
            'snapshot', $issueid);
        $DB->delete_records(self::TABLE, ['issueid' => $issueid]);
    }
}

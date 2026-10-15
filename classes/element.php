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

/** Certificate image sourced from the issue's course. */
class element extends \tool_certificate\element {
    /** Short label in the native chooser. */
    public static function get_element_type_name() {
        return get_string('elementname', 'certificateelement_coursefieldimg');
    }

    /** Normalise legacy/incomplete JSON without relying on issue.data. */
    private function settings(): array {
        $data = json_decode($this->get_data() ?: '{}', true);
        return (is_array($data) ? $data : []) + ['fieldid' => 0, 'width' => 0, 'height' => 0, 'previewcourseid' => 0];
    }

    /** Selected field ID, used while freezing the issue. */
    public function fieldid(): int {
        return (int)$this->settings()['fieldid'];
    }

    /** Native template controls plus a preview-only course selector. */
    public function render_form_elements($mform) {
        $choices = [0 => get_string('choosefield', 'certificateelement_coursefieldimg')];
        foreach (image_source::fields() as $id => $field) {
            $choices[$id] = $field->get_formatted_name() . ' (' . $field->get('shortname') . ')';
        }
        $mform->addElement('select', 'fieldid', get_string('coursefield', 'certificateelement_coursefieldimg'), $choices);
        $mform->addHelpButton('fieldid', 'coursefield', 'certificateelement_coursefieldimg');
        $mform->addElement('static', 'firstimage', '', get_string('firstimage', 'certificateelement_coursefieldimg'));
        $mform->addElement('static', 'snapshotinfo', get_string('snapshots', 'certificateelement_coursefieldimg'),
            get_string('snapshotssummary', 'certificateelement_coursefieldimg'));
        $mform->addHelpButton('snapshotinfo', 'snapshots', 'certificateelement_coursefieldimg');
        $mform->addElement('course', 'previewcourseid', get_string('previewcourse', 'certificateelement_coursefieldimg'),
            ['requiredcapabilities' => ['moodle/course:update']]);
        $mform->addHelpButton('previewcourseid', 'previewcourse', 'certificateelement_coursefieldimg');
        \tool_certificate\element_helper::render_form_element_width($mform, 'certificateelement_image');
        \tool_certificate\element_helper::render_form_element_height($mform, 'certificateelement_image');
        \tool_certificate\element_helper::render_form_element_position($mform);
    }

    /** Validate selected source, sizes and preview permission. */
    public function validate_form_elements($data, $files) {
        $errors = [];
        if (!isset(image_source::fields()[(int)($data['fieldid'] ?? 0)])) {
            $errors['fieldid'] = get_string('choosefield', 'certificateelement_coursefieldimg');
        }
        foreach (['width', 'height'] as $name) {
            if (!is_numeric($data[$name] ?? 0) || (float)$data[$name] < 0 || (float)$data[$name] > 2000) {
                $errors[$name] = get_string('invalidsize', 'certificateelement_coursefieldimg');
            }
        }
        $courseid = (int)($data['previewcourseid'] ?? 0);
        $context = $courseid > SITEID ? \context_course::instance($courseid, IGNORE_MISSING) : false;
        if ($courseid && (!$context || !has_capability('moodle/course:update', $context))) {
            $errors['previewcourseid'] = get_string('nopermissions', 'error', 'moodle/course:update');
        }
        return $errors;
    }

    /** Persist element-specific settings; native parent saves position and width. */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['fieldid' => (int)$data->fieldid, 'width' => (float)($data->width ?? 0),
            'height' => (float)($data->height ?? 0), 'previewcourseid' => (int)($data->previewcourseid ?? 0)]);
        parent::save_form_data($data);
    }

    /** Populate native editing controls. */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        foreach ($this->settings() as $key => $value) {
            $record->$key = $value;
        }
        return $record;
    }

    /** Live preview is explicitly limited to a course the editor may update. */
    private function preview_image(): ?\stored_file {
        $courseid = (int)$this->settings()['previewcourseid'];
        $context = $courseid > SITEID ? \context_course::instance($courseid, IGNORE_MISSING) : false;
        return $context && has_capability('moodle/course:update', $context) ?
            image_source::resolve($courseid, $this->fieldid()) : null;
    }

    /** Draw stored bytes, retaining aspect ratio when only one dimension is set. */
    public function render($pdf, $preview, $user, $issue) {
        try {
            $file = $preview ? $this->preview_image() :
                ($issue && !empty($issue->id) ? snapshot::image((int)$issue->id, $this->get_id()) : null);
            if (!$file) {
                return;
            }
            $settings = $this->settings();
            \tool_certificate\element_helper::render_image($pdf, $this, $file, [],
                (float)$settings['width'], (float)$settings['height']);
        } catch (\Throwable $e) {
            // Image availability must never prevent issue generation. No protected URL is fetched.
            debugging('Course field image: unable to render element ' . $this->get_id() . '.', DEBUG_DEVELOPER);
        }
    }

    /** Native drag-and-drop preview; this does not create a snapshot. */
    public function render_html() {
        $settings = $this->settings();
        $file = $this->preview_image();
        if (!$file) {
            return \html_writer::span(get_string('previewplaceholder', 'certificateelement_coursefieldimg'));
        }
        // The template manager may preview a hidden field; do not weaken its pluginfile permissions.
        $info = $file->get_imageinfo();
        $url = 'data:' . $info['mimetype'] . ';base64,' . base64_encode($file->get_content());
        return \tool_certificate\element_helper::render_image_html($url, $file->get_imageinfo(),
            (float)$settings['width'], (float)$settings['height'], $this->get_display_name());
    }
}

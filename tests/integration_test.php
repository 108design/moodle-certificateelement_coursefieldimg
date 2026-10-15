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

/** Native Moodle state, actual PDF generation and certificate lifecycle regression cases. */
final class integration_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('legacymaxissueid', 0, 'certificateelement_coursefieldimg');
    }

    private function field(string $type = 'textarea', array $config = []): \core_customfield\field_controller {
        $gen = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        $category = $gen->create_category();
        return $gen->create_field(['categoryid' => $category->get('id'), 'type' => $type, 'configdata' => $config]);
    }

    private function png(int $red): string {
        $image = imagecreatetruecolor(120, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, $red, 30, 90));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        return $bytes;
    }

    private function value(\core_customfield\field_controller $field, int $courseid, string $html): \core_customfield\data_controller {
        $data = \core_customfield\data_controller::create(0, (object)['instanceid' => $courseid], $field);
        $data->set('contextid', \context_course::instance($courseid)->id);
        $data->set('value', $html);
        $data->set('valueformat', FORMAT_HTML);
        $data->save();
        return $data;
    }

    private function file(int $contextid, string $component, string $area, int $itemid, string $name, string $content): \stored_file {
        return get_file_storage()->create_file_from_string(['contextid' => $contextid, 'component' => $component,
            'filearea' => $area, 'itemid' => $itemid, 'filepath' => '/', 'filename' => $name], $content);
    }

    private function template(int $fieldid): array {
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $template = $gen->create_template(['name' => 'Shared signature']);
        $page = $gen->create_page($template);
        $element = $gen->create_element($page->get_id(), 'coursefieldimg', ['fieldid' => $fieldid,
            'width' => 45, 'height' => 0, 'posx' => 10, 'posy' => 20, 'name' => 'Signature']);
        return [\tool_certificate\template::instance($template->get_id()), $element];
    }

    public function test_two_courses_same_template_and_regeneration_use_saved_bytes(): void {
        global $DB;
        $field = $this->field();
        $a = $this->getDataGenerator()->create_course();
        $b = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $da = $this->value($field, $a->id, '<img src="@@PLUGINFILE@@/a.png"><img src="@@PLUGINFILE@@/second.png">');
        $db = $this->value($field, $b->id, '<img src="@@PLUGINFILE@@/b.png">');
        $fa = $this->file($da->get_context()->id, 'customfield_textarea', 'value', $da->get('id'), 'a.png', $this->png(20));
        $fb = $this->file($db->get_context()->id, 'customfield_textarea', 'value', $db->get('id'), 'b.png', $this->png(200));
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        // Stale HTML in issue.data must never override the actual course field.
        $ia = $gen->issue($template, $user, null, ['legacyimage' => '<img src="https://invalid/a.png">'], 'tool_certificate', $a->id);
        $ib = $gen->issue($template, $user, null, [], 'tool_certificate', $b->id);
        $this->assertSame($fa->get_contenthash(), snapshot::image($ia->id, $element->get_id())->get_contenthash());
        $this->assertSame($fb->get_contenthash(), snapshot::image($ib->id, $element->get_id())->get_contenthash());
        $this->assertNotSame(snapshot::image($ia->id, $element->get_id())->get_contenthash(), snapshot::image($ib->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($ia)->get_content());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($ib)->get_content());
        $oldhash = $fa->get_contenthash();
        $fa->delete();
        $replacement = $this->file($da->get_context()->id, 'customfield_textarea', 'value', $da->get('id'), 'a.png', $this->png(90));
        $this->assertSame($oldhash, snapshot::image($ia->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->create_issue_file($ia, true)->get_content());
        $new = $gen->issue($template, $user, null, [], 'tool_certificate', $a->id);
        $this->assertSame($replacement->get_contenthash(), snapshot::image($new->id, $element->get_id())->get_contenthash());
        $data = json_decode($DB->get_field('tool_certificate_issues', 'data', ['id' => $ia->id]), true);
        $this->assertSame('<img src="https://invalid/a.png">', $data['legacyimage']);
        $template->revoke_issue($new->id);
        $this->assertFalse($DB->record_exists(snapshot::TABLE, ['issueid' => $new->id]));
        $this->assertEmpty(get_file_storage()->get_area_files(\context_system::instance()->id,
            'certificateelement_coursefieldimg', 'snapshot', $new->id, 'id', false));
    }

    public function test_textarea_default_and_explicit_empty(): void {
        $field = $this->field('textarea', ['defaultvalue' => '<img src="@@PLUGINFILE@@/default.png">']);
        $course = $this->getDataGenerator()->create_course();
        $file = $this->file(\context_system::instance()->id, 'customfield_textarea', 'defaultvalue',
            $field->get('id'), 'default.png', $this->png(60));
        $this->assertSame($file->get_contenthash(), image_source::resolve($course->id, $field->get('id'))->get_contenthash());
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $issue = $gen->issue($template, $this->getDataGenerator()->create_user(), null, [], 'tool_certificate', $course->id);
        $this->assertSame($file->get_contenthash(), snapshot::image($issue->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($issue)->get_content());
        $this->value($field, $course->id, '');
        $this->assertNull(image_source::resolve($course->id, $field->get('id')));
    }

    public function test_empty_missing_invalid_and_no_course_never_abort_pdf(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $field = $this->field();
        $data = $this->value($field, $course->id, '');
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        foreach (['', '<p>No image</p>', '<img src="@@PLUGINFILE@@/missing.png">',
                '<img src="https://example.invalid/image.png">', '<img src="@@PLUGINFILE@@/corrupt.png">'] as $html) {
            $data->set('value', $html);
            $data->save();
            $issue = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
            $this->assertNull(snapshot::image($issue->id, $element->get_id()));
            $this->assertStringStartsWith('%PDF-', $template->get_issue_file($issue)->get_content());
        }
        $this->file($data->get_context()->id, 'customfield_textarea', 'value', $data->get('id'), 'corrupt.png', 'not an image');
        $issue = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
        $this->assertNull(snapshot::image($issue->id, $element->get_id()));
        $nocourse = $gen->issue($template, $user);
        $this->assertNull(snapshot::image($nocourse->id, $element->get_id()));
        $data->set('value', '<img src="@@PLUGINFILE@@/missing.png">');
        $data->save();
        $this->file($data->get_context()->id, 'customfield_textarea', 'value', $data->get('id'), 'missing.png', $this->png(80));
        $this->assertNotNull(image_source::resolve($course->id, $field->get('id')));
        $this->assertNull(snapshot::image($issue->id, $element->get_id()));
        $fresh = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
        $this->assertNotNull(snapshot::image($fresh->id, $element->get_id()));
    }

    public function test_image_field_and_default_are_supported(): void {
        if (!\core_component::get_plugin_directory('customfield', 'courseimage')) {
            $this->markTestSkipped('Optional customfield_courseimage is not installed.');
        }
        $field = $this->field('courseimage', ['defaultfilename' => 'default.png']);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $default = $this->file(\context_system::instance()->id, 'customfield_courseimage', 'defaultvalue',
            $field->get('id'), 'default.png', $this->png(60));
        $this->assertSame($default->get_contenthash(), image_source::resolve($course->id, $field->get('id'))->get_contenthash());
        $data = $this->value($field, $course->id, 'custom.png');
        $file = $this->file($data->get_context()->id, 'customfield_courseimage', 'value', $data->get('id'), 'custom.png', $this->png(160));
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $issue = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
        $this->assertSame($file->get_contenthash(), snapshot::image($issue->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($issue)->get_content());
        $othercourse = $this->getDataGenerator()->create_course();
        $otherdata = $this->value($field, $othercourse->id, 'other.png');
        $otherfile = $this->file($otherdata->get_context()->id, 'customfield_courseimage', 'value',
            $otherdata->get('id'), 'other.png', $this->png(220));
        $otherissue = $gen->issue($template, $user, null, [], 'tool_certificate', $othercourse->id);
        $this->assertSame($otherfile->get_contenthash(), snapshot::image($otherissue->id, $element->get_id())->get_contenthash());
        $this->assertNotSame(snapshot::image($issue->id, $element->get_id())->get_contenthash(),
            snapshot::image($otherissue->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($otherissue)->get_content());
        $defaultcourse = $this->getDataGenerator()->create_course();
        $defaultissue = $gen->issue($template, $user, null, [], 'tool_certificate', $defaultcourse->id);
        $this->assertSame($default->get_contenthash(), snapshot::image($defaultissue->id, $element->get_id())->get_contenthash());
        $this->assertStringContainsString('/Subtype /Image', $template->get_issue_file($defaultissue)->get_content());
        $field->delete();
        $this->assertNotNull(snapshot::image($issue->id, $element->get_id()));
        $this->assertStringContainsString('/Subtype /Image', $template->create_issue_file($issue, true)->get_content());
    }

    public function test_legacy_missing_copy_and_privacy_deletion_do_not_use_current_image(): void {
        global $DB;
        $field = $this->field();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $data = $this->value($field, $course->id, '<img src="@@PLUGINFILE@@/s.png">');
        $this->file($data->get_context()->id, 'customfield_textarea', 'value', $data->get('id'), 's.png', $this->png(100));
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $issue = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
        snapshot::image($issue->id, $element->get_id())->delete();
        $this->assertNull(snapshot::image($issue->id, $element->get_id()));
        $other = $gen->issue($template, $this->getDataGenerator()->create_user(), null, [], 'tool_certificate', $course->id);
        $approved = new \core_privacy\local\request\approved_contextlist($user, 'certificateelement_coursefieldimg',
            [\context_system::instance()->id]);
        \certificateelement_coursefieldimg\privacy\provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists(snapshot::TABLE, ['userid' => $user->id]));
        $this->assertNull(snapshot::image($issue->id, $element->get_id()));
        $this->assertNotNull(snapshot::image($other->id, $element->get_id()));
        // Simulate an installation watermark covering an issue without a pre-existing snapshot.
        snapshot::delete_issue($other->id);
        set_config('legacymaxissueid', $other->id, 'certificateelement_coursefieldimg');
        $this->assertNull(snapshot::image($other->id, $element->get_id()));
    }

    public function test_privacy_export_includes_only_the_recipients_image(): void {
        $field = $this->field();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $data = $this->value($field, $course->id, '<img src="@@PLUGINFILE@@/signature.png">');
        $file = $this->file($data->get_context()->id, 'customfield_textarea', 'value',
            $data->get('id'), 'signature.png', $this->png(100));
        [$template, $element] = $this->template($field->get('id'));
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $issue = $gen->issue($template, $user, null, [], 'tool_certificate', $course->id);
        $other = $gen->issue($template, $this->getDataGenerator()->create_user(), null, [], 'tool_certificate', $course->id);
        $context = \context_system::instance();
        $contexts = \certificateelement_coursefieldimg\privacy\provider::get_contexts_for_userid($user->id);
        $this->assertContains((int)$context->id, array_map('intval', $contexts->get_contextids()));
        $users = new \core_privacy\local\request\userlist($context, 'certificateelement_coursefieldimg');
        \certificateelement_coursefieldimg\privacy\provider::get_users_in_context($users);
        $this->assertContains((int)$user->id, array_map('intval', $users->get_userids()));
        $approved = new \core_privacy\local\request\approved_contextlist($user, 'certificateelement_coursefieldimg', [$context->id]);
        \core_privacy\local\request\writer::reset();
        \certificateelement_coursefieldimg\privacy\provider::export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $subcontext = [get_string('pluginname', 'certificateelement_coursefieldimg'), (string)$issue->id,
            (string)$element->get_id()];
        $this->assertSame((int)$issue->id, (int)$writer->get_data($subcontext)->issueid);
        $files = $writer->get_files($subcontext);
        $this->assertCount(1, $files);
        $this->assertFalse($writer->has_any_data([get_string('pluginname', 'certificateelement_coursefieldimg'), (string)$other->id]));
        \core_privacy\local\request\writer::reset();
    }

    public function test_native_forms_use_question_help_without_custom_panels(): void {
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/admin/tool/certificate/edit.php'));
        $field = $this->field();
        $gen = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $form = $gen->create_template_and_edit_element_form('coursefieldimg');
        $html = $form->render();
        $this->assertStringContainsString('fa-circle-question', $html);
        $this->assertStringContainsString('A separate image copy is kept for each new issue.', $html);
        $this->assertStringContainsString('only the first image is used', $html);
        $this->assertStringNotContainsString('context-help', $html);
        $this->assertStringNotContainsString('context_help', $html);
    }

    public function test_native_image_size_helper_keeps_single_dimension_ratio(): void {
        $field = $this->field();
        $course = $this->getDataGenerator()->create_course();
        $data = $this->value($field, $course->id, '<img src="@@PLUGINFILE@@/s.png">');
        $file = $this->file($data->get_context()->id, 'customfield_textarea', 'value', $data->get('id'), 's.png', $this->png(100));
        $this->assertEqualsWithDelta(['width' => 60, 'height' => 20],
            \tool_certificate\element_helper::calculate_image_size($file, [], 60, 0), .001);
        $this->assertEqualsWithDelta(['width' => 30, 'height' => 10],
            \tool_certificate\element_helper::calculate_image_size($file, [], 0, 10), .001);
    }
}

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

/** Pure HTML extraction and URL containment cases; no database resets needed. */
final class image_source_test extends \basic_testcase {
    public function test_first_image_and_entities(): void {
        $this->assertSame('@@PLUGINFILE@@/a&b.png', image_source::first_image_src('<p>Text</p><img src="@@PLUGINFILE@@/a&amp;b.png"><img src="second.png">'));
        $this->assertNull(image_source::first_image_src('<p>No image</p>'));
        $this->assertSame('', image_source::first_image_src('<img><img src="later.png">'));
    }

    public function test_relative_paths_are_limited_to_selected_area(): void {
        global $CFG;
        $url = $CFG->wwwroot . '/pluginfile.php/12/customfield_textarea/value/34/sub/sign%20one.png';
        $this->assertSame('sub/sign one.png', image_source::relative_path($url, 12, 'value', 34));
        $this->assertSame('a.png', image_source::relative_path('@@PLUGINFILE@@/a.png', 12, 'value', 34));
        $this->assertSame('a.png', image_source::relative_path($CFG->wwwroot . '/pluginfile.php?file=%2F12%2Fcustomfield_textarea%2Fvalue%2F34%2Fa.png', 12, 'value', 34));
        foreach (['https://elsewhere.invalid/pluginfile.php/12/customfield_textarea/value/34/a.png',
                'file:///tmp/a.png', '@@PLUGINFILE@@/../secret.png', '@@PLUGINFILE@@/%2e%2e/a.png',
                '@@PLUGINFILE@@/a%00.png', '@@PLUGINFILE@@/sub\\a.png',
                $CFG->wwwroot . '/pluginfile.php/12/customfield_textarea/value/35/a.png',
                $CFG->wwwroot . '/pluginfile.php/12/customfield_textarea/defaultvalue/34/a.png'] as $bad) {
            $this->assertNull(image_source::relative_path($bad, 12, 'value', 34), $bad);
        }
    }
}

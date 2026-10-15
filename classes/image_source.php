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

/** Resolves only the selected field's internal image, without HTTP requests. */
final class image_source {
    /** Supported course fields, including hidden fields explicitly selected by a template manager. */
    public static function fields(): array {
        $result = [];
        foreach (\core_course\customfield\course_handler::create()->get_fields() as $field) {
            if (in_array($field->get('type'), ['textarea', 'courseimage'], true)) {
                $result[$field->get('id')] = $field;
            }
        }
        return $result;
    }

    /** First img in document order; a broken first image must not silently choose another signature. */
    public static function first_image_src(string $html): ?string {
        if (trim($html) === '') {
            return null;
        }
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $img = $dom->getElementsByTagName('img')->item(0);
            return $img ? trim($img->getAttribute('src')) : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** Match embedded placeholders or this site's exact expected pluginfile URL. */
    public static function relative_path(string $src, int $contextid, string $area, int $itemid): ?string {
        global $CFG;
        if (str_starts_with($src, '@@PLUGINFILE@@/')) {
            $relative = substr($src, strlen('@@PLUGINFILE@@/'));
        } else {
            $url = parse_url($src);
            $site = parse_url($CFG->wwwroot);
            if ($url === false || isset($url['user']) || isset($url['pass']) || isset($url['fragment'])) {
                return null;
            }
            if (isset($url['host']) && (strtolower($url['host']) !== strtolower($site['host']) ||
                    ($url['scheme'] ?? $site['scheme']) !== $site['scheme'] ||
                    ($url['port'] ?? null) !== ($site['port'] ?? null))) {
                return null;
            }
            $base = ($site['path'] ?? '') . '/pluginfile.php';
            $prefix = '/' . $contextid . '/customfield_textarea/' . $area . '/' . $itemid . '/';
            $urlpath = $url['path'] ?? '';
            if ($urlpath === $base) {
                parse_str($url['query'] ?? '', $params);
                $filearg = $params['file'] ?? '';
                if (!is_string($filearg) || !str_starts_with($filearg, $prefix)) {
                    return null;
                }
                $relative = substr($filearg, strlen($prefix));
            } else if (str_starts_with($urlpath, $base . $prefix)) {
                $relative = substr($urlpath, strlen($base . $prefix));
            } else {
                return null;
            }
        }
        $relative = rawurldecode($relative);
        if ($relative === '' || preg_match('~[\\\\\x00-\x1f]~', $relative)) {
            return null;
        }
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }
        return $relative;
    }

    /** Accept local raster content only, within a bounded decoding size. */
    public static function valid(\stored_file $file): bool {
        if ($file->is_directory() || $file->is_external_file() || $file->get_filesize() > 10485760) {
            return false;
        }
        $info = @getimagesizefromstring($file->get_content());
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF], true)
                || $info[0] <= 0 || $info[1] <= 0 || $info[0] * $info[1] > 16000000) {
            return false;
        }
        $image = @imagecreatefromstring($file->get_content());
        if (!$image) {
            return false;
        }
        imagedestroy($image);
        return true;
    }

    /** Course identity must come from the certificate issue, never from ambient $COURSE. */
    public static function resolve(int $courseid, int $fieldid): ?\stored_file {
        global $DB;
        if ($courseid <= SITEID || !$DB->record_exists('course', ['id' => $courseid])) {
            return null;
        }
        $field = self::fields()[$fieldid] ?? null;
        if (!$field) {
            return null;
        }
        $data = \core_customfield\data_controller::create(0, (object)['fieldid' => $fieldid, 'instanceid' => $courseid], $field);
        // create() with an explicit data object does not fetch existing course data.
        $record = $DB->get_record('customfield_data', ['fieldid' => $fieldid, 'instanceid' => $courseid]);
        if ($record) {
            $data = \core_customfield\data_controller::create(0, $record, $field);
        }
        if ($field->get('type') === 'courseimage') {
            $file = $data->get_image_file();
            return $file && self::valid($file) ? $file : null;
        }
        $src = self::first_image_src((string)$data->get_value());
        if (!$src) {
            return null;
        }
        $contextid = $record ? $data->get_context()->id : $field->get_handler()->get_configuration_context()->id;
        $area = $record ? 'value' : 'defaultvalue';
        $itemid = $record ? $data->get('id') : $fieldid;
        $relative = self::relative_path($src, $contextid, $area, $itemid);
        if ($relative === null) {
            return null;
        }
        $filename = basename($relative);
        $filepath = substr($relative, 0, strlen($relative) - strlen($filename));
        $file = get_file_storage()->get_file($contextid, 'customfield_textarea', $area, $itemid, '/' . $filepath, $filename);
        return $file && self::valid($file) ? $file : null;
    }
}

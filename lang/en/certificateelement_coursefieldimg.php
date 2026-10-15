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
$string['pluginname'] = 'Course field image for Certificate manager';
$string['elementname'] = 'Image from course field';
$string['choosefield'] = 'Choose an image or text area course field';
$string['coursefield'] = 'Course field';
$string['coursefield_help'] = 'Select an Image or Text area course field. Text areas use their first image; an unavailable first image does not select a later image. Only local Moodle PNG, JPEG and GIF files are supported, up to 10 MiB and 16 million pixels. External URLs and repository aliases are ignored.

Defaults apply only when no course value has been saved. Cleared values remain empty. Missing or invalid images do not prevent the certificate. A selected image appears in the PDF even if the course field is hidden: select only images intended for recipients.';
$string['firstimage'] = 'For Text area fields, only the first image is used. An empty field or unavailable image is omitted from the certificate.';
$string['previewcourse'] = 'Preview course';
$string['previewcourse_help'] = 'Used only in the template designer and preview PDF. You must be allowed to update this course. Save the element after changing the preview course. Real issues always use the course assigned by the issuing plugin; issues without a course have no course-field image. For repeat tests, create a fresh issue because regeneration intentionally uses its saved image.';
$string['previewplaceholder'] = 'Choose a preview course to display its image.';
$string['invalidsize'] = 'Enter a size between 0 and 2000 mm. Use 0 for automatic sizing.';
$string['privacy:userid'] = 'Recipient of the certificate.';
$string['privacy:issueid'] = 'Native certificate issue identifier.';
$string['privacy:elementid'] = 'Template element identifier; zero marks the frozen issue.';
$string['privacy:filename'] = 'Filename of the saved image, or an empty selection.';
$string['privacy:timecreated'] = 'Time the selection was saved.';
$string['privacy:snapshots'] = 'Images retained for reproducible certificate issues.';
$string['privacy:files'] = 'Private image copies associated with certificate issues.';

$string['snapshots'] = 'Issued images';
$string['snapshotssummary'] = 'A separate image copy is kept for each new issue.';
$string['snapshots_help'] = 'The image, or its absence, is saved at issuance. Changing the course image affects fresh issues; regeneration uses the saved selection.

Older issues without a saved copy remain empty. A missing or privacy-deleted copy is never replaced by the current course image. Revocation removes the issue’s image copy. This does not freeze the template layout.';

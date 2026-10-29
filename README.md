# Image from course field

Use one Certificate manager template with a different signature or image for each course. Select an existing Text area course field or an optional Image field supplied by the separate Course image plugin.
<details>
<summary>Screenshots</summary>

<table><tr><td><a href="https://raw.githubusercontent.com/108design/moodle-certificateelement_coursefieldimg/main/docs/screenshots/image-element.png"><img src="https://raw.githubusercontent.com/108design/moodle-certificateelement_coursefieldimg/main/docs/screenshots/image-element.png" alt="Course image selection and issued-image help" width="420"></a><br>Course image selection and issued-image help</td><td><a href="https://raw.githubusercontent.com/108design/moodle-certificateelement_coursefieldimg/main/docs/screenshots/course-certificate.png"><img src="https://raw.githubusercontent.com/108design/moodle-certificateelement_coursefieldimg/main/docs/screenshots/course-certificate.png" alt="A different course image on the same certificate template" width="420"></a><br>A different course image on the same certificate template</td></tr></table>

</details>



## Certificate Multitool set

Use the text pair for course-managed wording and the image pair for course-specific pictures in a shared certificate template. Flexible Date supplies the date formatting. Choose the parts your workflow needs.

| Plugin | Role and companion |
| --- | --- |
| [Certificate Multitool – Custom Field](https://github.com/108design/moodle-customfield_multiline) | Stores course text, text mode and alignment. Pair it with Cert. Element for certificate output. |
| [Certificate Multitool – Cert. Element](https://github.com/108design/moodle-certificateelement_coursefieldtext) | Renders that course text and supplies the course HTML preview. Requires Custom Field and Certificate manager. |
| [Course image](https://github.com/108design/moodle-customfield_courseimage) | Provides a single-image course field. Pair it with Course field image for certificate output. |
| [Course field image](https://github.com/108design/moodle-certificateelement_coursefieldimg) | Places a course image on the certificate and preserves the issued image copy. Requires Certificate manager; it accepts Course image or a native textarea field with an internal image. |
| [Flexible Date](https://github.com/108design/moodle-certificateelement_flexdate) | Formats certificate issue and expiry dates. Requires Certificate manager. |

**Text pair:** install Custom Field and Cert. Element together for the course-text certificate workflow. **Image pair:** install Course image and Course field image together for the single-image upload workflow. The image element can also use a native textarea image without Course image. Course certificate is needed for course-based issuance and the text pair’s course HTML preview.

### Moodle 5.3 qualification

Moodle 5.3 integration is provisional: it has been tested with Certificate manager (tool_certificate) 5.0.10/5.0.11 outside those versions’ declared Moodle 5.0–5.2 support range. Check the compatibility of Certificate manager and, when used, Course certificate before deploying on Moodle 5.3. The course custom fields themselves do not require Certificate manager.


## Requirements and installation

- Moodle 4.5 to 5.3 and Certificate manager (tool_certificate) 4.5.7 or later. The Certificate manager version must suit your Moodle installation.
- Install this plugin in admin/tool/certificate/element/coursefieldimg and complete the Moodle upgrade.
- Optionally install customfield_courseimage in customfield/field/courseimage. It is not required for Text area fields.

This stable release has native Moodle 4.5 integration evidence and provisional Moodle 5.3 evidence. Moodle 5.3 integration is provisional: it has been tested with Certificate manager (tool_certificate) 5.0.10/5.0.11 outside those versions’ declared Moodle 5.0–5.2 support range. Check the compatibility of Certificate manager and, when used, Course certificate before deploying on Moodle 5.3. The course custom fields themselves do not require Certificate manager. Validate the complete issuing workflow on your staging site before production use.

## Use

Add **Image from course field** to a certificate template and select the course field. In a Text area, the first image in document order is used. Only locally stored Moodle PNG, JPEG and GIF images are supported (up to 10 MiB and 16 million pixels). External image addresses, repository aliases and SVG files are ignored. The image is read directly through Moodle's File API.

Set X/Y, width and height in millimetres. Set one dimension to 0 to preserve the aspect ratio; two dimensions specify the exact size. Select a preview course you may edit to display its current image in the designer. The preview course never decides the image in a real issue.

Real issues require a course assignment from the issuing plugin. Manual issues without a course omit this element. A field default is used only when no course value has been saved; a deliberately cleared value remains empty. Empty, missing or invalid images do not prevent the rest of the certificate from being generated.

The image, including an empty selection, is saved for each new issue. Changing the course field affects fresh issues. Regeneration uses the saved copy. If a saved copy is missing, the current course image is not substituted. Images for old issues predating installation cannot be reconstructed reliably and are omitted if no snapshot exists. Template layout itself is not frozen by this plugin.

Course field visibility controls course display, but an image selected in a certificate template is included in the recipient's PDF even if the field is hidden. Template managers must select only images intended for recipients. Private image copies are removed on certificate revocation, Privacy API deletion or plugin uninstallation.

## Example

Create a course field named **Speaker signature**. Give Course A and Course B different images. Select that field once in a shared certificate template. Each fresh course-linked issue uses its course's image; regeneration retains the saved image.

## License

Copyright 2026 Andreas Giesen. See [GNU GPL v3 or later](LICENSE.md). Moodle and Certificate manager retain their respective licences.

This release is available free of charge under the GNU GPL v3 or later.

Available free of charge under the terms of the applicable license.

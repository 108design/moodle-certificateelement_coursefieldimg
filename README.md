# Image from course field

Use one Certificate manager template with a different signature or image for each course. Select an existing Text area course field or an optional Image field supplied by the separate Course image plugin.

## Requirements and installation

- Moodle 4.5 to 5.3 and Certificate manager (tool_certificate) 4.5.7 or later. The Certificate manager version must suit your Moodle installation.
- Install this plugin in admin/tool/certificate/element/coursefieldimg and complete the Moodle upgrade.
- Optionally install customfield_courseimage in customfield/field/courseimage. It is not required for Text area fields.

This is a beta release. Integration has been verified on Moodle 4.5 with Certificate manager 4.5.7 and on Moodle 5.3 with Certificate manager 5.0.10. The upstream Certificate manager 5.0.10 declares Moodle 5.0–5.2 support; the Moodle 5.3 result is this plugin's tested integration, not an upstream support statement. Validate the complete issuing workflow on your staging site before production use.

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

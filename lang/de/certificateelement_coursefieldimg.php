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
$string['pluginname'] = 'Kursfeld-Bild für Certificate manager';
$string['elementname'] = 'Bild aus Kursfeld';
$string['choosefield'] = 'Kursfeld vom Typ Bild oder Textbereich auswählen';
$string['coursefield'] = 'Kursfeld';
$string['coursefield_help'] = 'Wähle ein Bild- oder Textbereich-Kursfeld. Bei Textbereichen zählt das erste Bild; ist dieses nicht verfügbar, wird kein späteres Bild eingesetzt. Unterstützt werden lokale Moodle-Dateien vom Typ PNG, JPEG und GIF bis 10 MiB und 16 Millionen Pixel. Externe Adressen und Repository-Verknüpfungen werden ignoriert.

Der Standardwert gilt nur ohne gespeicherten Kurswert. Bewusst geleerte Werte bleiben leer. Fehlende oder ungültige Bilder verhindern die Ausstellung nicht. Das Bild erscheint auch bei verborgenem Kursfeld im PDF: Wähle nur Bilder, die für die Empfänger bestimmt sind.';
$string['firstimage'] = 'Bei Textbereich-Feldern wird nur das erste Bild verwendet. Bei einem leeren Feld oder nicht verfügbaren Bild entfällt das Bild im Zertifikat.';
$string['previewcourse'] = 'Vorschaukurs';
$string['previewcourse_help'] = 'Gilt nur für den Template-Editor und das Vorschau-PDF. Du musst den Kurs bearbeiten dürfen. Speichere das Element nach einer Änderung des Vorschaukurses. Echte Ausstellungen verwenden immer den vom ausstellenden Plugin zugeordneten Kurs; ohne Kurs entfällt das Kursfeld-Bild. Erzeuge für Wiederholungstests eine frische Ausstellung, da eine Regeneration absichtlich das gesicherte Bild verwendet.';
$string['previewplaceholder'] = 'Wähle einen Vorschaukurs, um dessen Bild anzuzeigen.';
$string['invalidsize'] = 'Gib eine Größe zwischen 0 und 2000 mm ein. 0 steht für automatische Größe.';
$string['privacy:userid'] = 'Empfänger des Zertifikats.';
$string['privacy:issueid'] = 'Kennung der Zertifikatsausstellung.';
$string['privacy:elementid'] = 'Kennung des Template-Elements; null markiert die gesicherte Ausstellung.';
$string['privacy:filename'] = 'Dateiname der Bildkopie oder eine leere Auswahl.';
$string['privacy:timecreated'] = 'Zeitpunkt der Sicherung.';
$string['privacy:snapshots'] = 'Bildkopien für reproduzierbare Zertifikatsausstellungen.';
$string['privacy:files'] = 'Private Bildkopien zu Zertifikatsausstellungen.';

$string['snapshots'] = 'Ausgestellte Bilder';
$string['snapshotssummary'] = 'Je neuer Ausstellung bleibt eine eigene Bildkopie erhalten.';
$string['snapshots_help'] = 'Bei der Ausstellung wird das Bild oder sein Fehlen gesichert. Änderungen am Kursbild gelten für frische Ausstellungen; die Regeneration verwendet die gesicherte Auswahl.

Ältere Ausstellungen ohne Bildkopie bleiben leer. Eine fehlende oder per Datenschutz-Anfrage gelöschte Kopie wird niemals durch das aktuelle Kursbild ersetzt. Ein Widerruf entfernt die Bildkopie dieser Ausstellung. Das Template-Layout wird dadurch nicht eingefroren.';

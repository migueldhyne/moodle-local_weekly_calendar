<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PDF export for local_advanced_calendar plugin
 *
 * @package     local_advanced_calendar
 * @copyright   2024 Patrick ROCHET <prochet.94@free.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 1. Include Moodle config for environment.
require_once(__DIR__ . '/../../config.php');

// 2. Ensure the user is logged in
require_login();

// 3. Include core calendar and plugin libraries
require_once($CFG->dirroot . '/calendar/lib.php');
require_once(__DIR__ . '/lib.php');

// 4. Include Dompdf via Composer autoloader
require_once($CFG->dirroot . '/local/dompdf/vendor/autoload.php');
use Dompdf\Dompdf;
use Dompdf\Options;

// 5. Retrieve parameters
$categoryid = optional_param('category', null, PARAM_INT);
$courseid   = optional_param('course', SITEID, PARAM_INT);
$view       = optional_param('view', 'week', PARAM_ALPHA);
$time       = optional_param('time', 0, PARAM_INT);
$lookahead  = optional_param('lookahead', null, PARAM_INT);
if (empty($time)) {
    $time = time();
}

// 6. Prepare calendar data and renderer
$calendar = calendar_information::create($time, $courseid, $categoryid);
$renderer = $PAGE->get_renderer('core_calendar');
list($data, ) = local_advanced_calendar_get_view($calendar, $view, true, false, $lookahead);

// 7. Render PDF-specific Mustache template
$template = 'local_advanced_calendar/calendar_week_pdf';
$content  = $renderer->render_from_template($template, $data);

// 8. Inline CSS for PDF output
$css     = file_get_contents(__DIR__ . '/styles.css');
$ccslink = '<style>@page { margin:5mm !important; padding:0; }' . $css . '</style>';
$html    = $ccslink . $content;

// 9. Configure and instantiate Dompdf
$options = new Options();
$options->setTempDir($CFG->dataroot . '/temp');
$options->setIsRemoteEnabled(true);
$dompdf = new Dompdf($options);

// 10. Load HTML, set paper, render PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// 11. Clean buffer to prevent extra output
if (ob_get_length()) {
    ob_end_clean();
}

// 12. Stream PDF to browser as download and exit
$filename = clean_filename($data->periodname) . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;

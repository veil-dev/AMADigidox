<?php
/**
 * DMS v2 — Excel Export Handler
 *
 * Generates an .xlsx file with separate sheets per grade category:
 *   - Grade 11  (SHS docs only)
 *   - Grade 12  (SHS docs only)
 *   - College   (College docs only)
 * Each cell shows YES (green) or NO (red) based on upload status.
 */

require_once __DIR__ . '/db.php';

// ── Session Auth Guard ──
// Only logged-in staff may export data.
session_start();
require_auth(['admin', 'registrar', 'officer']);

$pdo = pdo();

// Fetch students
$studentsStmt = $pdo->query("SELECT id, usn, name, grade FROM students ORDER BY name ASC");
$allStudents = $studentsStmt->fetchAll();

// Fetch student documents
$sdStmt = $pdo->query("SELECT sd.student_id, sd.doc_def_id, sd.status FROM student_documents sd");
$sdRows = $sdStmt->fetchAll();

// Build lookup: [student_id][doc_def_id] = status
$lookup = [];
foreach ($sdRows as $row) {
    $lookup[$row['student_id']][$row['doc_def_id']] = $row['status'];
}

// ── Group students by category ──
function getCategory($grade) {
    if (preg_match('/^Grade\s*11$/i', $grade)) return 'Grade 11';
    if (preg_match('/^Grade\s*12$/i', $grade)) return 'Grade 12';
    return 'College';
}

$groups = [];
foreach ($allStudents as $s) {
    $cat = getCategory($s['grade']);
    if (!isset($groups[$cat])) $groups[$cat] = [];
    $groups[$cat][] = $s;
}

// ── Fetch doc definitions per student type ──
$shsDefs = $pdo->prepare("SELECT id, name, is_required FROM doc_definitions WHERE student_type = 'shs' ORDER BY is_required DESC, name ASC");
$shsDefs->execute();
$shsDocDefs = $shsDefs->fetchAll();

$colDefs = $pdo->prepare("SELECT id, name, is_required FROM doc_definitions WHERE student_type = 'college' ORDER BY is_required DESC, name ASC");
$colDefs->execute();
$colDocDefs = $colDefs->fetchAll();

// Sheet configs: name, students, doc defs
$sheetConfigs = [];
if (isset($groups['Grade 11']) && count($groups['Grade 11']) > 0) {
    $sheetConfigs[] = ['name' => 'Grade 11', 'students' => $groups['Grade 11'], 'docDefs' => $shsDocDefs];
}
if (isset($groups['Grade 12']) && count($groups['Grade 12']) > 0) {
    $sheetConfigs[] = ['name' => 'Grade 12', 'students' => $groups['Grade 12'], 'docDefs' => $shsDocDefs];
}
if (isset($groups['College']) && count($groups['College']) > 0) {
    $sheetConfigs[] = ['name' => 'College', 'students' => $groups['College'], 'docDefs' => $colDocDefs];
}

if (empty($sheetConfigs)) {
    die('No students found to export.');
}

// ── Shared Strings ──
$sharedStrings = [];

function sstIndex($str) {
    global $sharedStrings;
    if (!in_array($str, $sharedStrings, true)) {
        $sharedStrings[] = $str;
    }
    return array_search($str, $sharedStrings, true);
}

// Pre-register YES/NO strings
$yesIdx = sstIndex('YES');
$noIdx  = sstIndex('NO');

// ── Build sheet data ──
$sheets = [];
$sheetIndex = 1;
foreach ($sheetConfigs as &$sheet) {
    $sheet['index'] = $sheetIndex;
    $docDefs = $sheet['docDefs'];

    // Build headers for this sheet
    $headers = ['USN', 'Student Name', 'Grade'];
    foreach ($docDefs as $def) {
        $headers[] = $def['name'] . ($def['is_required'] ? ' *' : '');
    }

    // Header row
    $data = '';
    $row = '';
    foreach ($headers as $h) {
        $idx = sstIndex($h);
        $row .= "<c t='s'><v>$idx</v></c>";
    }
    $data .= "<row r='1'>$row</row>\n";

    // Data rows
    $rowNum = 2;
    foreach ($sheet['students'] as $s) {
        $sid = (int)$s['id'];
        $cells = '';
        $cells .= "<c t='s'><v>" . sstIndex($s['usn'] ?? 'N/A') . "</v></c>";
        $cells .= "<c t='s'><v>" . sstIndex($s['name']) . "</v></c>";
        $cells .= "<c t='s'><v>" . sstIndex($s['grade']) . "</v></c>";

        foreach ($docDefs as $def) {
            $did = (int)$def['id'];
            $status = $lookup[$sid][$did] ?? 'missing';
            $hasFile = ($status === 'uploaded');
            if ($hasFile) {
                $cells .= "<c t='s' s='1'><v>$yesIdx</v></c>";
            } else {
                $cells .= "<c t='s' s='2'><v>$noIdx</v></c>";
            }
        }

        $data .= "<row r='$rowNum'>$cells</row>\n";
        $rowNum++;
    }
    $sheet['data'] = $data;
    $sheetIndex++;
}
unset($sheet);

// ── Build sharedStrings.xml ──
$ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
       . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
foreach ($sharedStrings as $str) {
    $ssXml .= '<si><t>' . htmlspecialchars($str, ENT_XML1, 'UTF-8') . '</t></si>';
}
$ssXml .= '</sst>';

// ── Build styles.xml ──
$styleXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="3">'
    . '<font><sz val="11"/><name val="Calibri"/></font>'
    . '<font><sz val="11"/><color rgb="FF1D9E75"/><name val="Calibri"/><b/></font>'
    . '<font><sz val="11"/><color rgb="FFA32D2D"/><name val="Calibri"/><b/></font>'
    . '</fonts>'
    . '<fills count="3">'
    . '<fill><patternFill patternType="none"/></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FFE1F5EE"/></patternFill></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCEBEB"/></patternFill></fill>'
    . '</fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="3">'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
    . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
    . '</cellXfs>'
    . '</styleSheet>';

// ── Build worksheet XMLs ──
$sheetXmls = [];
foreach ($sheetConfigs as $sheet) {
    $sheetXmls[$sheet['index']] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . $sheet['data'] . '</sheetData>'
        . '</worksheet>';
}

// ── Build Content_Types.xml ──
$ctXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
       . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
       . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
       . '<Default Extension="xml" ContentType="application/xml"/>'
       . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
       . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
foreach ($sheetConfigs as $sheet) {
    $ctXml .= '<Override PartName="/xl/worksheets/sheet' . $sheet['index'] . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
}
$ctXml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/_rels/.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Override PartName="/xl/_rels/workbook.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '</Types>';

// ── Build workbook.xml with sheet references ──
$sheetTags = '';
$sheetId = 1;
$relId = 1;
foreach ($sheetConfigs as $sheet) {
    $safeName = htmlspecialchars($sheet['name'], ENT_XML1, 'UTF-8');
    $sheetTags .= '<sheet name="' . $safeName . '" sheetId="' . $sheetId . '" r:id="rId' . $relId . '"/>';
    $sheetId++;
    $relId++;
}

$wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
       . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
       . '<sheets>' . $sheetTags . '</sheets>'
       . '</workbook>';

// ── Build .rels ──
$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
         . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
         . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
         . '</Relationships>';

// ── Build workbook.xml.rels ──
$wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
$relNum = 1;
foreach ($sheetConfigs as $sheet) {
    $wbRelsXml .= '<Relationship Id="rId' . $relNum . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheet['index'] . '.xml"/>';
    $relNum++;
}
$wbRelsXml .= '<Relationship Id="rId' . $relNum . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
$wbRelsXml .= '<Relationship Id="rId' . ($relNum + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
$wbRelsXml .= '</Relationships>';

// ── Create ZIP and serve ──
$zipPath = tempnam(sys_get_temp_dir(), 'dms_export_') . '.xlsx';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml', $ctXml);
$zip->addFromString('_rels/.rels', $relsXml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);
$zip->addFromString('xl/workbook.xml', $wbXml);
$zip->addFromString('xl/sharedStrings.xml', $ssXml);
$zip->addFromString('xl/styles.xml', $styleXml);
foreach ($sheetConfigs as $sheet) {
    $zip->addFromString('xl/worksheets/sheet' . $sheet['index'] . '.xml', $sheetXmls[$sheet['index']]);
}
$zip->close();

// Serve file
$filename = 'Student_Documents_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile($zipPath);
unlink($zipPath);
exit;

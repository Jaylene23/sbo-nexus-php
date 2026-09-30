<?php
// SBO Nexus — Officer Accomplishment Report
// Standalone page (not part of the index.php view router) so it renders as
// a clean document with no sidebar/nav to fight with when the officer
// prints it or saves it as a PDF from the browser's print dialog.
//
// Two modes:
//   accomplishment_report.php                -> summary across all completed events
//   accomplishment_report.php?event_id=123   -> single event, with an editable narrative

require 'config.php';
require 'includes/functions.php';
require 'includes/auth.php';
require 'includes/report_editor.php';

require_login(['officer']);
$me = current_user();
$officer = get_user($conn, $me['id']);

$event_id = (int) ($_GET['event_id'] ?? 0);
$singleEvent = null;
$report = null;

if ($event_id > 0) {
    $singleEvent = get_event($conn, $event_id);
    if (!$singleEvent || (int)$singleEvent['created_by'] !== (int)$me['id']) {
        header('Location: index.php?view=myEvents');
        exit;
    }
    $report = get_event_report($conn, $event_id);
}

$msgText = null;
$errText = null;
if (($_GET['msg'] ?? '') === 'report_saved') {
    $msgText = 'Report saved.';
}
if (($_GET['error'] ?? '') === 'report_too_large') {
    $errText = 'The report is too large to save. Remove some text, tables, or photos and try again.';
} elseif (($_GET['error'] ?? '') === 'report_save_failed') {
    $errText = 'The report could not be saved. Please try again.';
}

function report_row($ev, $conn) {
    $regCount = count_registrations($conn, $ev['id']);
    $attendedCount = count_attended($conn, $ev['id']);
    $rate = $regCount > 0 ? round(($attendedCount / $regCount) * 100) : 0;
    $organizers = get_event_organizers($conn, $ev['id']);
    $committees = array_values(array_unique(array_filter(array_map(fn($o) => $o['committee'], $organizers))));
    return [
        'event' => $ev,
        'reg_count' => $regCount,
        'attended_count' => $attendedCount,
        'rate' => $rate,
        'committees' => $committees,
    ];
}

$from = '';
$to = '';

if ($singleEvent) {
    $rows = [report_row($singleEvent, $conn)];
    $periodLabel = fmt_event_date_range($singleEvent);
} else {
    // Optional date range filter, e.g. ?from=2026-06-01&to=2026-10-31
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';

    $allEvents = get_events($conn, 'approved', $me['id']);
    $events = array_values(array_filter($allEvents, function ($ev) use ($from, $to) {
        if (!event_is_past($ev)) return false;
        if ($from !== '' && $ev['event_date'] < $from) return false;
        if ($to !== '' && $ev['event_date'] > $to) return false;
        return true;
    }));
    usort($events, fn($a, $b) => strcmp($a['event_date'], $b['event_date']));
    $rows = array_map(fn($ev) => report_row($ev, $conn), $events);
    $periodLabel = ($from || $to)
        ? trim(($from ? fmt_date($from) : 'the start') . ' to ' . ($to ? fmt_date($to) : 'present'))
        : 'All completed events on record';
}

$totalRegs = array_sum(array_column($rows, 'reg_count'));
$totalAttended = array_sum(array_column($rows, 'attended_count'));
$overallRate = $totalRegs > 0 ? round(($totalAttended / $totalRegs) * 100) : 0;
$generatedAt = date('F j, Y g:i A');
$reportTitle = $singleEvent ? $singleEvent['title'] : $officer['full_name'];

// ---------------------------------------------------------------------
// Editable document: saved version, or a fresh one built from a template
// ---------------------------------------------------------------------
$templates = report_templates();
$templateParam = (string) ($_GET['template'] ?? '');
$freshRequested = isset($templates[$templateParam]);
$periodRequested = !$singleEvent && ($from !== '' || $to !== '');
$saved = get_report_document($conn, (int) $me['id'], $singleEvent ? $event_id : null);

$ctx = [
    'officer_name' => $officer['full_name'],
    'position' => (string) ($officer['position_title'] ?? ''),
    'generated' => date('F j, Y'),
    'single' => $singleEvent,
    'rows' => $rows,
    'period' => $periodLabel,
    'reg_count' => $totalRegs,
    'attended_count' => $totalAttended,
    'rate' => $overallRate,
    'committees' => $singleEvent ? ($rows[0]['committees'] ?? []) : [],
    'narrative' => $report['narrative'] ?? '',
    'recommendations' => $report['recommendations'] ?? '',
];

if ($freshRequested || $periodRequested || !$saved) {
    $docTemplate = $freshRequested ? $templateParam : ($saved['template'] ?? 'standard');
    if (!isset($templates[$docTemplate])) $docTemplate = 'standard';
    $docHtml = report_build_template($docTemplate, $ctx);
    $isSavedCopy = false;
} else {
    $docTemplate = isset($templates[$saved['template']]) ? $saved['template'] : 'standard';
    $docHtml = class_exists('DOMDocument') ? sanitize_report_html($saved['content']) : $saved['content'];
    $isSavedCopy = true;
}

$liveValues = [
    'reg_count' => (string) $totalRegs,
    'attended_count' => (string) $totalAttended,
    'rate' => (string) $overallRate,
    'event_count' => (string) count($rows),
];

$baseUrl = 'accomplishment_report.php?' . ($singleEvent ? 'event_id=' . (int) $event_id . '&' : '')
    . ($from !== '' ? 'from=' . urlencode($from) . '&' : '')
    . ($to !== '' ? 'to=' . urlencode($to) . '&' : '');
$photos = $singleEvent ? get_event_photos($conn, $event_id) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accomplishment Report | <?= h($reportTitle) ?></title>
<style>
  :root{ --ics-green:#1f6f4a; --slate:#5b6472; --line:#dfe3e6; }
  *{box-sizing:border-box;}
  body{
    font-family:'Segoe UI', Arial, sans-serif;
    color:#1c2126;
    max-width:960px;
    margin:0 auto;
    padding:20px 24px 70px;
    line-height:1.5;
    background:#fff;
  }
  .toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;}
  .toolbar a.back{color:var(--slate);font-size:13.5px;text-decoration:none;}
  .toolbar-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
  .tbtn, .toolbar select{
    font:inherit;font-size:13px;font-weight:600;
    padding:7px 13px;border-radius:8px;border:1px solid var(--line);
    background:#fff;color:#1c2126;cursor:pointer;text-decoration:none;
  }
  .tbtn:hover{background:#f3f6f4;}
  .tbtn.primary{background:var(--ics-green);color:#fff;border-color:var(--ics-green);}
  .tbtn.primary:hover{background:#185a3c;}
  .flash{background:#eaf5ee;color:var(--ics-green);border:1px solid #bfe2cc;border-radius:8px;padding:10px 14px;font-size:13.5px;margin-bottom:12px;}
  .flash.err{background:#fdecec;color:#a02828;border-color:#f3c2c2;}
  .status{font-size:12.5px;color:var(--slate);margin:0 0 10px;}
  .fmt-bar{
    position:sticky;top:0;z-index:5;background:#fbfcfa;border:1px solid var(--line);border-radius:10px;
    padding:8px 10px;display:flex;flex-wrap:wrap;gap:6px 8px;align-items:center;margin-bottom:12px;
  }
  .fmt-bar .grp{display:flex;gap:4px;align-items:center;padding-right:8px;margin-right:2px;border-right:1px solid var(--line);}
  .fmt-bar .grp:last-child{border-right:none;}
  .fmt-bar .glabel{font-size:11px;color:var(--slate);margin-right:2px;}
  .fb{
    font:inherit;font-size:13px;min-width:32px;height:32px;padding:0 9px;border:1px solid var(--line);
    background:#fff;border-radius:7px;cursor:pointer;color:#1c2126;
  }
  .fb:hover{background:#eef4f0;}
  .fb.danger{color:#a02828;}
  .fmt-bar select, .fmt-bar input[type=number]{
    font:inherit;font-size:13px;height:32px;border:1px solid var(--line);border-radius:7px;background:#fff;padding:0 6px;
  }
  .fmt-bar input[type=number]{width:52px;}
  .photo-panel{border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:12px;background:#fbfcfa;}
  .photo-panel h4{margin:0 0 8px;font-size:13px;}
  .photo-row{display:flex;gap:8px;flex-wrap:wrap;}
  .photo-row button{border:1px solid var(--line);border-radius:8px;padding:3px;background:#fff;cursor:pointer;}
  .photo-row img{height:62px;width:auto;border-radius:5px;display:block;}

  /* The document itself */
  .doc{
    min-height:520px;padding:34px 38px;border:1px dashed #b9c4bc;border-radius:6px;outline:none;
    font-size:14px;background:#fff;
  }
  .doc:focus{border-color:var(--ics-green);}
  .doc h1{font-size:20px;margin:14px 0 6px;}
  .doc h2{font-size:18px;margin:14px 0 6px;}
  .doc h3{font-size:15px;margin:18px 0 6px;color:var(--ics-green);}
  .doc h4{font-size:13px;margin:12px 0 4px;}
  .doc p{margin:0 0 9px;}
  .doc ul, .doc ol{margin:0 0 10px 22px;padding:0;}
  .doc img{max-width:100%;height:auto;}
  .doc hr{border:none;border-top:1px solid var(--line);margin:14px 0;}
  .doc .letterhead{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--ics-green);padding-bottom:14px;margin-bottom:18px;}
  .doc .letterhead img{height:56px;width:auto;}
  .doc .letterhead h1{font-size:15px;margin:0 0 2px;color:var(--ics-green);}
  .doc .letterhead p{margin:0;font-size:12.5px;color:var(--slate);}
  .doc h2.report-title{text-align:center;font-size:20px;margin:6px 0 2px;letter-spacing:.02em;color:#1c2126;}
  .doc p.report-sub{text-align:center;color:var(--slate);font-size:13px;margin:0 0 20px;}
  .doc table{width:100%;border-collapse:collapse;font-size:13px;margin:6px 0 18px;}
  .doc th, .doc td{border:1px solid var(--line);padding:7px 9px;text-align:left;vertical-align:top;}
  .doc th{background:#f0f4f1;font-size:12px;color:var(--slate);}
  .doc table.meta-table td:nth-child(odd){color:var(--slate);width:18%;background:#f7f8f6;}
  .doc table.sign-table{margin-top:48px;}
  .doc table.sign-table td{border:none;padding:0 30px 0 0;width:50%;}
  .doc .sign-line{border-top:1px solid #1c2126;margin:40px 0 0;padding-top:6px;font-weight:600;}
  .doc .sign-role{font-size:12px;color:var(--slate);margin:0;}
  .doc .page-break{border-top:2px dashed #b9c4bc;margin:20px 0;height:0;page-break-after:always;}
  .doc td:focus-within, .doc th:focus-within{background:#fbfdfb;}
  .hint{font-size:12.5px;color:var(--slate);margin:10px 0 0;}

  @media print{
    .toolbar,.fmt-bar,.photo-panel,.flash,.status,.hint{display:none !important;}
    body{padding:0;max-width:100%;}
    .doc{border:none;padding:0;min-height:0;}
    .doc .page-break{border:none;}
    a[href]{color:inherit;text-decoration:none;}
  }
</style>
</head>
<body>

  <div class="toolbar">
    <a class="back" href="index.php?view=myEvents">&larr; Back to My Events</a>
    <div class="toolbar-actions">
      <select id="tplSelect" title="Template">
        <?php foreach ($templates as $k => $t): ?>
          <option value="<?= h($k) ?>" <?= $k === $docTemplate ? 'selected' : '' ?>><?= h($t[0]) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="tbtn" id="useTemplate" title="Replace the page with this template">Use template</button>
      <button type="button" class="tbtn" id="refreshLive" title="Refresh the registration and attendance numbers from the system">Update numbers</button>
      <button type="button" class="tbtn" onclick="window.print()">Print or save as PDF</button>
      <button type="button" class="tbtn primary" id="saveBtn">Save report</button>
    </div>
  </div>

  <?php if ($msgText): ?><div class="flash"><?= h($msgText) ?></div><?php endif; ?>
  <?php if ($errText): ?><div class="flash err"><?= h($errText) ?></div><?php endif; ?>

  <p class="status" id="statusLine">
    <?php if ($isSavedCopy): ?>
      Saved version<?= !empty($saved['updated_at']) ? ' from ' . h(date('M j, Y g:i A', strtotime($saved['updated_at']))) : '' ?>. Click anywhere in the page to edit.
    <?php else: ?>
      Not saved yet. Click anywhere in the page to edit, then press Save report.
    <?php endif; ?>
  </p>

  <div class="fmt-bar" id="fmtBar">
    <div class="grp">
      <button type="button" class="fb" data-cmd="undo" title="Undo">&#8630;</button>
      <button type="button" class="fb" data-cmd="redo" title="Redo">&#8631;</button>
    </div>
    <div class="grp">
      <select id="blockFormat" title="Text style">
        <option value="p">Paragraph</option>
        <option value="h2">Heading 1</option>
        <option value="h3">Heading 2</option>
        <option value="h4">Heading 3</option>
      </select>
      <select id="fontSize" title="Text size">
        <option value="">Size</option>
        <option value="2">Small</option>
        <option value="3">Normal</option>
        <option value="5">Large</option>
        <option value="6">Extra large</option>
      </select>
    </div>
    <div class="grp">
      <button type="button" class="fb" data-cmd="bold" title="Bold"><strong>B</strong></button>
      <button type="button" class="fb" data-cmd="italic" title="Italic"><em>I</em></button>
      <button type="button" class="fb" data-cmd="underline" title="Underline"><u>U</u></button>
      <button type="button" class="fb" data-cmd="removeFormat" title="Clear formatting">Clear</button>
    </div>
    <div class="grp">
      <button type="button" class="fb" data-cmd="justifyLeft" title="Align left">Left</button>
      <button type="button" class="fb" data-cmd="justifyCenter" title="Center">Center</button>
      <button type="button" class="fb" data-cmd="justifyRight" title="Align right">Right</button>
      <button type="button" class="fb" data-cmd="justifyFull" title="Justify">Justify</button>
    </div>
    <div class="grp">
      <button type="button" class="fb" data-cmd="insertUnorderedList" title="Bulleted list">&bull; List</button>
      <button type="button" class="fb" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
    </div>
    <div class="grp">
      <span class="glabel">Table</span>
      <input type="number" id="tblRows" value="3" min="1" max="30" title="Rows">
      <span class="glabel">x</span>
      <input type="number" id="tblCols" value="3" min="1" max="10" title="Columns">
      <button type="button" class="fb" id="insTable" title="Insert a table below the current block">Insert</button>
      <button type="button" class="fb" id="rowAdd" title="Add a row below">+ Row</button>
      <button type="button" class="fb danger" id="rowDel" title="Delete the current row">- Row</button>
      <button type="button" class="fb" id="colAdd" title="Add a column to the right">+ Col</button>
      <button type="button" class="fb danger" id="colDel" title="Delete the current column">- Col</button>
      <button type="button" class="fb danger" id="tblDel" title="Delete the whole table">Delete table</button>
    </div>
    <div class="grp">
      <button type="button" class="fb" id="secAdd" title="Add a new section below the current block">+ Section</button>
      <button type="button" class="fb danger" id="secDel" title="Delete the section or block you are in">Delete section</button>
      <button type="button" class="fb" id="lineAdd" title="Insert a line">Line</button>
      <button type="button" class="fb" id="pageBreak" title="Start a new page when printing">Page break</button>
    </div>
  </div>

  <?php if (!empty($photos)): ?>
    <div class="photo-panel">
      <h4>Insert a photo from this event</h4>
      <div class="photo-row">
        <?php foreach ($photos as $p): ?>
          <button type="button" class="photoBtn" data-src="<?= h($p['file_path']) ?>" title="Insert this photo">
            <img src="<?= h($p['file_path']) ?>" alt="">
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div id="doc" class="doc" contenteditable="true" spellcheck="true"><?= $docHtml ?></div>

  <p class="hint">
    You can change every word on this page. Type to add text, select text and press Delete to remove it,
    and use the bar above to format, add or remove sections and tables, or start from a different template.
    Your changes are only kept after you press Save report.
  </p>

  <form id="saveForm" method="post" action="actions.php" style="display:none;">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="save_report_document">
    <input type="hidden" name="event_id" value="<?= $singleEvent ? (int) $event_id : 0 ?>">
    <input type="hidden" name="template" id="tplField" value="<?= h($docTemplate) ?>">
    <input type="hidden" name="content" id="contentField">
  </form>

<script>
(function () {
  var doc = document.getElementById('doc');
  var statusLine = document.getElementById('statusLine');
  var LIVE = <?= json_encode($liveValues, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var BASE = <?= json_encode($baseUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var dirty = false;
  var savedRange = null;

  try {
    document.execCommand('styleWithCSS', false, true);
    document.execCommand('defaultParagraphSeparator', false, 'p');
  } catch (e) {}

  function markDirty() {
    dirty = true;
    statusLine.textContent = 'You have unsaved changes. Press Save report to keep them.';
  }

  doc.addEventListener('input', function () {
    if (doc.innerHTML.trim() === '') { doc.innerHTML = '<p><br></p>'; }
    markDirty();
  });

  // Remember where the cursor was so the dropdowns and buttons can use it.
  document.addEventListener('selectionchange', function () {
    var sel = window.getSelection();
    if (sel.rangeCount && doc.contains(sel.anchorNode)) {
      savedRange = sel.getRangeAt(0).cloneRange();
    }
  });

  function restoreSelection() {
    doc.focus();
    if (savedRange) {
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(savedRange);
    }
  }

  function run(cmd, value) {
    restoreSelection();
    document.execCommand(cmd, false, value === undefined ? null : value);
    markDirty();
  }

  // Keep the text selection when a toolbar button is pressed.
  document.getElementById('fmtBar').addEventListener('mousedown', function (e) {
    var t = e.target;
    if (t.tagName !== 'SELECT' && t.tagName !== 'INPUT' && t.tagName !== 'OPTION') { e.preventDefault(); }
  });

  document.querySelectorAll('[data-cmd]').forEach(function (b) {
    b.addEventListener('click', function () { run(b.getAttribute('data-cmd')); });
  });

  document.getElementById('blockFormat').addEventListener('change', function () {
    run('formatBlock', this.value);
  });
  document.getElementById('fontSize').addEventListener('change', function () {
    if (this.value) { run('fontSize', this.value); }
    this.value = '';
  });

  function topBlock() {
    var sel = window.getSelection();
    var n = (sel.rangeCount && doc.contains(sel.anchorNode)) ? sel.anchorNode : (savedRange ? savedRange.startContainer : null);
    if (!n || !doc.contains(n)) { return null; }
    while (n && n.parentNode !== doc) { n = n.parentNode; }
    return n && n !== doc ? n : null;
  }

  function currentCell() {
    var sel = window.getSelection();
    var n = (sel.rangeCount && doc.contains(sel.anchorNode)) ? sel.anchorNode : (savedRange ? savedRange.startContainer : null);
    while (n && n !== doc) {
      if (n.nodeType === 1 && (n.nodeName === 'TD' || n.nodeName === 'TH')) { return n; }
      n = n.parentNode;
    }
    return null;
  }

  function insertAfterCurrent(node) {
    var ref = topBlock();
    if (ref && ref.nextSibling) { doc.insertBefore(node, ref.nextSibling); }
    else { doc.appendChild(node); }
  }

  function ensureNotEmpty() {
    if (doc.innerHTML.trim() === '') { doc.innerHTML = '<p><br></p>'; }
  }

  function placeCaretIn(el) {
    var r = document.createRange();
    r.selectNodeContents(el);
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(r);
    savedRange = r.cloneRange();
  }

  // ----- sections -----
  document.getElementById('secAdd').addEventListener('click', function () {
    var s = document.createElement('div');
    s.className = 'sec';
    s.innerHTML = '<h3>New section title</h3><p>Write here.</p>';
    insertAfterCurrent(s);
    placeCaretIn(s.firstChild);
    markDirty();
  });

  document.getElementById('secDel').addEventListener('click', function () {
    var b = topBlock();
    if (!b) { alert('Click inside the section you want to delete first.'); return; }
    if (!confirm('Delete this section or block from the report?')) { return; }
    doc.removeChild(b);
    ensureNotEmpty();
    markDirty();
  });

  document.getElementById('lineAdd').addEventListener('click', function () { run('insertHorizontalRule'); });

  document.getElementById('pageBreak').addEventListener('click', function () {
    var br = document.createElement('div');
    br.className = 'page-break';
    var p = document.createElement('p');
    p.innerHTML = '<br>';
    insertAfterCurrent(p);
    doc.insertBefore(br, p);
    markDirty();
  });

  // ----- tables -----
  document.getElementById('insTable').addEventListener('click', function () {
    var rows = Math.max(1, Math.min(30, parseInt(document.getElementById('tblRows').value, 10) || 3));
    var cols = Math.max(1, Math.min(10, parseInt(document.getElementById('tblCols').value, 10) || 3));
    var html = '<table class="report-table"><thead><tr>';
    for (var c = 0; c < cols; c++) { html += '<th>Heading</th>'; }
    html += '</tr></thead><tbody>';
    for (var r = 1; r < rows; r++) {
      html += '<tr>';
      for (var c2 = 0; c2 < cols; c2++) { html += '<td><br></td>'; }
      html += '</tr>';
    }
    html += '</tbody></table>';
    var wrap = document.createElement('div');
    wrap.innerHTML = html + '<p><br></p>';
    var ref = topBlock();
    var anchor = ref ? ref.nextSibling : null;
    while (wrap.firstChild) {
      if (anchor) { doc.insertBefore(wrap.firstChild, anchor); } else { doc.appendChild(wrap.firstChild); }
    }
    markDirty();
  });

  function needCell() {
    var cell = currentCell();
    if (!cell) { alert('Click inside a table cell first.'); }
    return cell;
  }

  function cellsOf(row) { return Array.prototype.filter.call(row.children, function (x) { return x.nodeName === 'TD' || x.nodeName === 'TH'; }); }

  document.getElementById('rowAdd').addEventListener('click', function () {
    var cell = needCell(); if (!cell) return;
    var row = cell.parentNode;
    var copy = document.createElement('tr');
    cellsOf(row).forEach(function () {
      var td = document.createElement('td'); td.innerHTML = '<br>'; copy.appendChild(td);
    });
    row.parentNode.insertBefore(copy, row.nextSibling);
    markDirty();
  });

  document.getElementById('rowDel').addEventListener('click', function () {
    var cell = needCell(); if (!cell) return;
    var row = cell.parentNode, table = row.closest('table');
    if (table.rows.length <= 1) { table.parentNode.removeChild(table); } else { row.parentNode.removeChild(row); }
    ensureNotEmpty(); markDirty();
  });

  document.getElementById('colAdd').addEventListener('click', function () {
    var cell = needCell(); if (!cell) return;
    var idx = cell.cellIndex, table = cell.closest('table');
    Array.prototype.forEach.call(table.rows, function (row) {
      var ref = row.cells[idx];
      var isHead = ref && ref.nodeName === 'TH';
      var nc = document.createElement(isHead ? 'th' : 'td');
      nc.innerHTML = isHead ? 'Heading' : '<br>';
      row.insertBefore(nc, ref ? ref.nextSibling : null);
    });
    markDirty();
  });

  document.getElementById('colDel').addEventListener('click', function () {
    var cell = needCell(); if (!cell) return;
    var idx = cell.cellIndex, table = cell.closest('table');
    if (table.rows[0].cells.length <= 1) { table.parentNode.removeChild(table); }
    else { Array.prototype.forEach.call(table.rows, function (row) { if (row.cells[idx]) { row.removeChild(row.cells[idx]); } }); }
    ensureNotEmpty(); markDirty();
  });

  document.getElementById('tblDel').addEventListener('click', function () {
    var cell = needCell(); if (!cell) return;
    if (!confirm('Delete this whole table?')) return;
    var table = cell.closest('table');
    table.parentNode.removeChild(table);
    ensureNotEmpty(); markDirty();
  });

  // ----- photos -----
  document.querySelectorAll('.photoBtn').forEach(function (b) {
    b.addEventListener('mousedown', function (e) { e.preventDefault(); });
    b.addEventListener('click', function () {
      var img = document.createElement('img');
      img.src = b.getAttribute('data-src');
      img.alt = '';
      img.style.maxWidth = '100%';
      var p = document.createElement('p');
      p.appendChild(img);
      insertAfterCurrent(p);
      markDirty();
    });
  });

  // ----- template and live numbers -----
  document.getElementById('useTemplate').addEventListener('click', function () {
    var key = document.getElementById('tplSelect').value;
    if (!confirm('Replace everything on this page with the selected template? Your saved report is not changed until you press Save report.')) { return; }
    dirty = false;
    window.location.href = BASE + 'template=' + encodeURIComponent(key);
  });

  document.getElementById('refreshLive').addEventListener('click', function () {
    var spans = doc.querySelectorAll('[data-live]');
    if (!spans.length) { alert('This report has no automatic numbers to update. Templates add them for you.'); return; }
    spans.forEach(function (s) {
      var k = s.getAttribute('data-live');
      if (Object.prototype.hasOwnProperty.call(LIVE, k)) { s.textContent = LIVE[k]; }
    });
    markDirty();
  });

  // ----- saving -----
  function save() {
    document.getElementById('contentField').value = doc.innerHTML;
    document.getElementById('tplField').value = document.getElementById('tplSelect').value;
    dirty = false;
    document.getElementById('saveForm').submit();
  }
  document.getElementById('saveBtn').addEventListener('click', save);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
})();
</script>

</body>
</html>

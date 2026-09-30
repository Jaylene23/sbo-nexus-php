<?php
// SBO Nexus: editable accomplishment report helpers.
//
// The report is a free-form document the officer can edit completely.
// It can start from a template or from a blank page. The finished document
// is stored as HTML in the report_documents table. Everything that comes
// back from the browser is passed through sanitize_report_html() before it is
// saved, so only simple formatting survives (no scripts, no outside links).

function report_text($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Date range written with the word "to" instead of a dash. */
function report_plain_range($event) {
    return str_replace(['–', '—'], ' to ', fmt_event_date_range($event));
}

/** The templates an officer can start from. The key is stored with the document. */
function report_templates() {
    return [
        'standard' => ['Standard report', 'Letterhead, details, figures, narrative, recommendations and signatures'],
        'detailed' => ['Detailed report', 'Standard report plus objectives, activities, budget, challenges and photos'],
        'simple' => ['Simple memo', 'A short memo style report with a letterhead'],
        'blank' => ['Blank page (no template)', 'An empty page. Type everything yourself'],
    ];
}

/** Turns plain text into paragraphs. Empty text becomes one placeholder paragraph. */
function report_paragraphs($text, $placeholder) {
    $text = trim((string) $text);
    if ($text === '') {
        return '<p>' . report_text($placeholder) . '</p>';
    }
    $out = '';
    foreach (preg_split('/\R{1,}/', $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out .= '<p>' . report_text($line) . '</p>';
        }
    }
    return $out;
}

function report_letterhead() {
    return '<div class="letterhead">'
        . '<img src="assets/nbsc-logo.png" alt="">'
        . '<img src="assets/ics-logo.png" alt="">'
        . '<div><h1>Northern Bukidnon State College, Institute of Computer Studies</h1>'
        . '<p>ICS Supreme Body Organization</p></div></div>';
}

function report_signatures($name, $position) {
    return '<table class="sign-table"><tbody><tr>'
        . '<td><p class="sign-line">' . report_text($name) . '</p><p class="sign-role">Prepared by, ' . report_text($position) . '</p></td>'
        . '<td><p class="sign-line">&nbsp;</p><p class="sign-role">Noted by, SBO Adviser</p></td>'
        . '</tr></tbody></table>';
}

function report_live($key, $value) {
    return '<span data-live="' . report_text($key) . '">' . report_text($value) . '</span>';
}

/** The events table used by the all-events summary. */
function report_events_table(array $rows) {
    if (!$rows) {
        return '<p>No completed events fall within this period yet.</p>';
    }
    $html = '<table class="report-table"><thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Category</th><th>Committees</th><th>Registered</th><th>Attended</th><th>Rate</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $ev = $row['event'];
        $html .= '<tr>'
            . '<td>' . report_text($ev['title']) . '</td>'
            . '<td>' . report_text(report_plain_range($ev)) . '</td>'
            . '<td>' . report_text($ev['venue']) . '</td>'
            . '<td>' . report_text($ev['category']) . '</td>'
            . '<td>' . report_text($row['committees'] ? implode(', ', $row['committees']) : 'None') . '</td>'
            . '<td>' . (int) $row['reg_count'] . '</td>'
            . '<td>' . (int) $row['attended_count'] . '</td>'
            . '<td>' . (int) $row['rate'] . '%</td>'
            . '</tr>';
    }
    return $html . '</tbody></table>';
}

/**
 * Builds the starting HTML of a report from a template.
 * $c keys: officer_name, position, generated, single (event array or null),
 * rows, period, reg_count, attended_count, rate, committees, narrative, recommendations.
 */
function report_build_template($key, array $c) {
    if ($key === 'blank') {
        return '<p><br></p>';
    }

    $single = $c['single'];
    $position = $c['position'] !== '' ? $c['position'] : 'SBO Officer';
    $html = report_letterhead();
    $html .= '<h2 class="report-title">Accomplishment Report</h2>';
    $html .= '<p class="report-sub">' . report_text($single ? $single['title'] : 'Period covered: ' . $c['period']) . '</p>';

    if ($key === 'simple') {
        $html .= '<p><strong>Date prepared:</strong> ' . report_text($c['generated']) . '</p>';
        if ($single) {
            $html .= '<p><strong>Event:</strong> ' . report_text($single['title']) . '</p>';
            $html .= '<p><strong>Date:</strong> ' . report_text(report_plain_range($single)) . '</p>';
            $html .= '<p><strong>Venue:</strong> ' . report_text($single['venue']) . '</p>';
        } else {
            $html .= '<p><strong>Period covered:</strong> ' . report_text($c['period']) . '</p>';
        }
        $html .= '<p><strong>Prepared by:</strong> ' . report_text($c['officer_name']) . ', ' . report_text($position) . '</p>';
        $html .= '<hr>';
        $html .= report_paragraphs($c['narrative'], 'Write what happened here.');
        $html .= '<p>A total of ' . report_live('attended_count', $c['attended_count']) . ' of '
            . report_live('reg_count', $c['reg_count']) . ' registered students attended, which is an attendance rate of '
            . report_live('rate', $c['rate']) . '%.</p>';
        if (!$single) {
            $html .= report_events_table($c['rows']);
        }
        if (trim((string) $c['recommendations']) !== '') {
            $html .= '<h3>Recommendations</h3>' . report_paragraphs($c['recommendations'], '');
        }
        return $html . report_signatures($c['officer_name'], $position);
    }

    // Standard and detailed share the same top part.
    $html .= '<table class="meta-table"><tbody>';
    $html .= '<tr><td>Prepared by</td><td>' . report_text($c['officer_name']) . '</td><td>Position</td><td>' . report_text($position) . '</td></tr>';
    if ($single) {
        $html .= '<tr><td>Event date</td><td>' . report_text(report_plain_range($single)) . '</td><td>Venue</td><td>' . report_text($single['venue']) . '</td></tr>';
        $html .= '<tr><td>Category</td><td>' . report_text($single['category']) . '</td><td>Date prepared</td><td>' . report_text($c['generated']) . '</td></tr>';
    } else {
        $html .= '<tr><td>Events reported</td><td>' . report_live('event_count', count($c['rows'])) . '</td><td>Date prepared</td><td>' . report_text($c['generated']) . '</td></tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<h3>Attendance Summary</h3>';
    $html .= '<table class="report-table"><thead><tr><th>Total registrations</th><th>Total attendees</th><th>Attendance rate</th></tr></thead><tbody><tr>'
        . '<td>' . report_live('reg_count', $c['reg_count']) . '</td>'
        . '<td>' . report_live('attended_count', $c['attended_count']) . '</td>'
        . '<td>' . report_live('rate', $c['rate']) . '%</td></tr></tbody></table>';

    if (!$single) {
        $html .= '<h3>Events Conducted</h3>' . report_events_table($c['rows']);
    }

    if ($key === 'detailed') {
        $html .= '<div class="sec"><h3>Objectives</h3><ul><li>Write the first objective here.</li><li>Write the second objective here.</li></ul></div>';
    }

    $html .= '<div class="sec"><h3>' . ($single ? 'Narrative Report' : 'Overview') . '</h3>'
        . report_paragraphs($c['narrative'], 'Describe what happened: activities conducted and notable highlights.') . '</div>';

    if ($key === 'detailed') {
        $html .= '<div class="sec"><h3>Activities Conducted</h3><ol><li>Write the first activity here.</li><li>Write the second activity here.</li></ol></div>';
        $html .= '<div class="sec"><h3>Participants</h3><p>Describe who took part: students, guests, and organizers.</p></div>';
    }

    if ($single && !empty($c['committees'])) {
        $html .= '<div class="sec"><h3>Committees Involved</h3><p>' . report_text(implode(', ', $c['committees'])) . '</p></div>';
    }

    if ($key === 'detailed') {
        $html .= '<div class="sec"><h3>Budget and Expenses</h3><table class="report-table"><thead><tr><th>Item</th><th>Quantity</th><th>Amount</th></tr></thead><tbody>'
            . '<tr><td>Item name</td><td>0</td><td>0.00</td></tr>'
            . '<tr><td>Item name</td><td>0</td><td>0.00</td></tr>'
            . '<tr><td><strong>Total</strong></td><td></td><td><strong>0.00</strong></td></tr>'
            . '</tbody></table></div>';
        $html .= '<div class="sec"><h3>Challenges Encountered</h3><p>Write the problems you faced and how they were handled.</p></div>';
        $html .= '<div class="sec"><h3>Documentation</h3><p>Use the Insert photo button to add pictures of the event here.</p></div>';
    }

    $html .= '<div class="sec"><h3>Recommendations</h3>'
        . report_paragraphs($c['recommendations'], 'Write what to improve or carry forward for the next event.') . '</div>';

    return $html . report_signatures($c['officer_name'], $position);
}

// ---------------------------------------------------------------------
// Safety filter for saved report HTML
// ---------------------------------------------------------------------

function sanitize_report_style($style) {
    $allowed = ['text-align', 'font-size', 'font-weight', 'font-style', 'text-decoration', 'color',
                'background-color', 'width', 'height', 'margin-left', 'text-indent', 'line-height', 'vertical-align'];
    $out = [];
    foreach (explode(';', (string) $style) as $decl) {
        $parts = explode(':', $decl, 2);
        if (count($parts) !== 2) continue;
        $prop = strtolower(trim($parts[0]));
        $val = trim($parts[1]);
        if (!in_array($prop, $allowed, true)) continue;
        if (!preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[0-9.]+\s*)?\)|[a-zA-Z0-9%.\s,-]+)$/', $val)) continue;
        $out[] = $prop . ': ' . $val;
    }
    return implode('; ', $out);
}

function sanitize_report_node(DOMNode $node) {
    static $allowedTags = ['p', 'br', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        'img', 'a', 'hr', 'blockquote', 'sup', 'sub', 'small'];
    static $dropTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea',
        'select', 'option', 'link', 'meta', 'base', 'svg', 'math', 'audio', 'video', 'canvas', 'noscript', 'template'];

    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            $node->removeChild($child);
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (in_array($tag, $dropTags, true)) {
            $node->removeChild($child);
            continue;
        }

        sanitize_report_node($child);

        if (!in_array($tag, $allowedTags, true)) {
            // Keep the text inside an unknown tag, drop the tag itself.
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;
            $keep = false;

            if ($name === 'class') {
                $keep = (bool) preg_match('/^[a-zA-Z0-9_ -]{1,80}$/', $value);
            } elseif ($name === 'style') {
                $clean = sanitize_report_style($value);
                if ($clean !== '') {
                    $child->setAttribute('style', $clean);
                    $keep = true;
                }
            } elseif ($name === 'data-live') {
                $keep = (bool) preg_match('/^[a-z_]{1,30}$/', $value);
            } elseif (in_array($name, ['colspan', 'rowspan', 'width', 'height'], true)) {
                $keep = (bool) preg_match('/^\d{1,4}%?$/', $value);
            } elseif ($name === 'alt' && $tag === 'img') {
                $keep = true;
            } elseif ($name === 'src' && $tag === 'img') {
                $keep = (bool) preg_match('#^(assets|uploads)/[A-Za-z0-9_./-]+$#', $value) && strpos($value, '..') === false;
            } elseif ($name === 'href' && $tag === 'a') {
                $keep = (bool) preg_match('#^(https?://|mailto:)#i', $value);
            }

            if (!$keep) {
                $child->removeAttribute($attr->nodeName);
            }
        }

        if ($tag === 'a') {
            $child->setAttribute('rel', 'noopener noreferrer');
            $child->setAttribute('target', '_blank');
        }
        if ($tag === 'img' && !$child->hasAttribute('src')) {
            $node->removeChild($child);
        }
    }
}

/**
 * Used only when the server has no PHP DOM extension (php-xml), so the real
 * filter cannot run. It keeps the words and drops all formatting, which is
 * always safe.
 */
function report_plain_text_fallback($html) {
    $html = preg_replace('#<(script|style)\b.*?</\1>#is', '', (string) $html);
    $html = preg_replace('#</(p|div|h[1-6]|li|tr)>|<br\s*/?>#i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(report_paragraphs($text, ''));
}

/** Returns a cleaned copy of report HTML that is safe to store and show again. */
function sanitize_report_html($html) {
    $html = (string) $html;
    if (trim($html) === '') {
        return '';
    }
    if (!class_exists('DOMDocument')) {
        return report_plain_text_fallback($html);
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="sbo-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = null;
    foreach ($doc->getElementsByTagName('div') as $div) {
        if ($div->getAttribute('id') === 'sbo-root') {
            $root = $div;
            break;
        }
    }
    if (!$root) {
        return '';
    }

    sanitize_report_node($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function get_report_document($conn, $userId, $eventId) {
    ensure_event_management_schema($conn);
    if ($eventId) {
        $stmt = $conn->prepare("SELECT * FROM report_documents WHERE user_id = ? AND event_id = ? LIMIT 1");
        $stmt->bind_param('ii', $userId, $eventId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM report_documents WHERE user_id = ? AND event_id IS NULL LIMIT 1");
        $stmt->bind_param('i', $userId);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function save_report_document($conn, $userId, $eventId, $template, $html) {
    ensure_event_management_schema($conn);
    $existing = get_report_document($conn, $userId, $eventId);

    if ($existing) {
        $stmt = $conn->prepare("UPDATE report_documents SET template = ?, content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('ssi', $template, $html, $existing['id']);
    } else {
        $eid = $eventId ?: null;
        $stmt = $conn->prepare("INSERT INTO report_documents (user_id, event_id, template, content, updated_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('iiss', $userId, $eid, $template, $html);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

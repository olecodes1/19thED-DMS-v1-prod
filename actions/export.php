<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
audit_log('DATA_EXPORT', 'CSV export type=' . ($_GET['type'] ?? 'unknown'));

$type = $_GET['type'] ?? '';
$conferenceId = (int)($_GET['conference_id'] ?? 0) ?: null;
$today = date('Ymd_His');

function open_csv(string $filename): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

if (!in_array($type, ['members', 'churches', 'areas', 'stats'], true)) {
    http_response_code(400);
    exit('Invalid export type.');
}

open_csv($type . '_export_' . $today . '.csv');
$out = fopen('php://output', 'w');

if ($type === 'members') {
    $search     = trim($_GET['search']                ?? '');
    $gender     = trim($_GET['gender']                ?? '');
    $component  = trim($_GET['component']             ?? '');
    $joinedYpd  = trim($_GET['joined_ypd']            ?? '');
    $fullChurch = trim($_GET['full_member_of_church'] ?? '');
    $occStatus  = trim($_GET['occupational_status']   ?? '');

    $selectCols = "m.member_id, m.member_no, m.name, m.surname_name, m.gender, m.component,
                   CASE m.component
                     WHEN 'MB' THEN 'Mother Sunbeam'
                     WHEN 'AS' THEN 'Allen Stars'
                     WHEN 'Y'  THEN 'Youth'
                     WHEN 'YA' THEN 'Young Adults'
                     ELSE 'Unknown'
                   END AS component_name,
                   c.conference_name, a.area_name, ch.local_church_name, m.contact,
                   m.joined_ypd, m.full_member_of_church, m.occupational_status,
                   m.eligible_to_vote_conference, m.eligible_to_vote_episcopal,
                   m.robbed, m.year_robbed, m.current_status";

    $sql = "SELECT $selectCols
            FROM members m
            LEFT JOIN conferences c ON m.conference_id = c.conference_id
            LEFT JOIN areas a       ON m.area_id       = a.area_id
            LEFT JOIN churches ch   ON m.church_id     = ch.church_id
            WHERE m.deleted_at IS NULL";
    $params = [];

    if ($conferenceId)             { $sql .= " AND m.conference_id = ?";         $params[] = $conferenceId; }
    if ($search !== '')            { $sql .= " AND (m.name LIKE ? OR m.surname_name LIKE ? OR m.contact LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($gender !== '')            { $sql .= " AND m.gender = ?";                $params[] = $gender; }
    if ($component !== '')         { $sql .= " AND m.component = ?";             $params[] = $component; }
    if ($joinedYpd  !== '')        { $sql .= " AND m.joined_ypd = ?";            $params[] = $joinedYpd; }
    if ($fullChurch !== '')       { $sql .= " AND m.full_member_of_church = ?"; $params[] = $fullChurch; }
    if ($occStatus  !== '')       { $sql .= " AND m.occupational_status = ?";   $params[] = $occStatus; }

    $sql .= " ORDER BY c.conference_name, m.surname_name, m.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $header = ['ID', 'Member No.', 'First Name', 'Surname', 'Gender', 'Component Code', 'Component', 'Conference', 'Area', 'Church', 'Contact', 'Joined YPD', 'Full Member of Church', 'Occupational Status', 'Vote (Conference)', 'Vote (Episcopal)', 'Robed', 'Year Robed', 'Current Status'];
    fputcsv($out, $header);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, array_values($row));
    }
}

if ($type === 'churches') {
    $sql = "SELECT ch.church_id, ch.local_church_name, c.conference_name, a.area_name,
                   ch.local_church_president_name, ch.local_church_director_name, ch.status
            FROM churches ch
            LEFT JOIN conferences c ON ch.conference_id = c.conference_id
            LEFT JOIN areas a ON ch.area_id = a.area_id
            WHERE 1=1";
    $params = [];
    if ($conferenceId) { $sql .= " AND ch.conference_id = ?"; $params[] = $conferenceId; }
    $sql .= " ORDER BY c.conference_name, CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED), a.area_name, ch.local_church_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    fputcsv($out, ['ID', 'Church', 'Conference', 'Area', 'President', 'Director', 'Status']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
}

if ($type === 'areas') {
    $sql = "SELECT a.area_id, a.area_name, c.conference_name, a.area_president_name, a.area_director_name
            FROM areas a
            LEFT JOIN conferences c ON a.conference_id = c.conference_id
            WHERE 1=1";
    $params = [];
    if ($conferenceId) { $sql .= " AND a.conference_id = ?"; $params[] = $conferenceId; }
    $sql .= " ORDER BY c.conference_name, CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED), a.area_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    fputcsv($out, ['ID', 'Area', 'Conference', 'President', 'Director']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
}

if ($type === 'stats') {
    $memberSubSelect = "SELECT conference_id,
                 COUNT(*) AS total_members,
                 SUM(component = 'MB') AS MB,
                 SUM(component = 'AS') AS AS_,
                 SUM(component = 'Y')  AS Y,
                 SUM(component = 'YA') AS YA,
                 SUM(gender = 'M') AS male,
                 SUM(gender = 'F') AS female,
                 SUM(eligible_to_vote_conference = 'Yes') AS vote_conf_yes,
                 SUM(eligible_to_vote_episcopal  = 'Yes') AS vote_epi_yes,
                 SUM(joined_ypd = 'Yes') AS joined_ypd_yes,
                 SUM(full_member_of_church = 'Yes') AS full_church_yes
                 FROM members GROUP BY conference_id";

    $sql = "SELECT c.conference_name,
                   COALESCE(m.total_members, 0) AS total_members,
                   COALESCE(m.MB, 0) AS MB,
                   COALESCE(m.AS_, 0) AS AS_,
                   COALESCE(m.Y, 0) AS Y,
                   COALESCE(m.YA, 0) AS YA,
                   COALESCE(m.male, 0) AS male,
                   COALESCE(m.female, 0) AS female,
                   COALESCE(m.vote_conf_yes, 0) AS vote_conf_yes,
                   COALESCE(m.vote_epi_yes, 0) AS vote_epi_yes,
                   COALESCE(m.joined_ypd_yes, 0) AS joined_ypd_yes,
                   COALESCE(m.full_church_yes, 0) AS full_church_yes,
                   COALESCE(ev.events_count, 0) AS events_count,
                   COALESCE(ev.total_attendance, 0) AS total_attendance
            FROM conferences c
            LEFT JOIN ($memberSubSelect) m ON m.conference_id = c.conference_id
            LEFT JOIN (
              SELECT conference_id,
                     COUNT(*) AS events_count,
                     COALESCE(SUM(attendance_count),0) AS total_attendance
              FROM events
              GROUP BY conference_id
            ) ev ON ev.conference_id = c.conference_id
            WHERE 1=1";
    $params = [];
    if ($conferenceId) { $sql .= " AND c.conference_id = ?"; $params[] = $conferenceId; }
    $sql .= " ORDER BY c.conference_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $header = ['Conference', 'Total Members', 'MB (Mother Sunbeam)', 'AS (Allen Stars)', 'Y (Youth)', 'YA (Young Adults)', 'Male', 'Female', 'Vote Conf (Yes)', 'Vote Episcopal (Yes)', 'Joined YPD (Yes)', 'Full Church Member (Yes)', 'Events', 'Attendance'];
    fputcsv($out, $header);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $line = [
            $row['conference_name'],
            $row['total_members'],
            $row['MB'], $row['AS_'], $row['Y'], $row['YA'],
            $row['male'], $row['female'],
            $row['vote_conf_yes'], $row['vote_epi_yes'],
            $row['joined_ypd_yes'] ?? 0,
            $row['full_church_yes'] ?? 0,
            $row['events_count'],
            $row['total_attendance']
        ];
        fputcsv($out, $line);
    }

    // Append occupational status breakdown as a separate section
    fputcsv($out, []);
    fputcsv($out, ['--- Occupational Status Breakdown ---']);
    fputcsv($out, ['Conference', 'Occupational Status', 'Count']);
    $occSql = "SELECT c.conference_name, m.occupational_status, COUNT(*) AS cnt
               FROM members m
               LEFT JOIN conferences c ON m.conference_id = c.conference_id
               WHERE m.occupational_status IS NOT NULL AND m.occupational_status != ''";
    $occParams = [];
    if ($conferenceId) { $occSql .= " AND m.conference_id = ?"; $occParams[] = $conferenceId; }
    $occSql .= " GROUP BY c.conference_name, m.occupational_status ORDER BY c.conference_name, cnt DESC";
    $occStmt = $pdo->prepare($occSql);
    $occStmt->execute($occParams);
    while ($r = $occStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$r['conference_name'], $r['occupational_status'], $r['cnt']]);
    }
}

fclose($out);
exit;

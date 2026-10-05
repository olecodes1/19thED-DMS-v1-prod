<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forms/add_member.php"); exit;
}

$name         = trim($_POST['name'] ?? '');
$surname      = trim($_POST['surname_name'] ?? '');
$gender       = $_POST['gender']         ?: null;
$dob          = $_POST['dob']            ?: null;
$contact      = trim($_POST['contact']   ?? '');
$status       = $_POST['current_status'] ?? 'Other';
$component    = $_POST['component']      ?: null;
$conf_id      = (int)($_POST['conference_id'] ?? 0) ?: null;
$area_id      = (int)($_POST['area_id']       ?? 0) ?: null;
$church_id    = (int)($_POST['church_id']     ?? 0) ?: null;
$vote_conf    = $_POST['eligible_to_vote_conference'] ?? 'No';
$vote_epis    = $_POST['eligible_to_vote_episcopal']  ?? 'No';
$robbed       = $_POST['robbed']       ?? 'No';
$year_robbed  = ($robbed === 'Yes' && !empty($_POST['year_robbed'])) ? (int)$_POST['year_robbed'] : null;
// New fields from CSV
$member_no          = trim($_POST['member_no']           ?? '') ?: null;
$joined_ypd         = $_POST['joined_ypd']                ?? null ?: null;
$full_member_church = $_POST['full_member_of_church']     ?? null ?: null;
$occ_status         = trim($_POST['occupational_status']  ?? '') ?: null;

// district_id is always 19 (the 19th Episcopal District)
$district_id = 19;

if (!$name || !$surname || !$conf_id) {
    header("Location: ../forms/add_member.php?error=missing_fields"); exit;
}
if ($contact !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $contact)) {
    header("Location: ../forms/add_member.php?error=invalid_contact"); exit;
}
if ($dob && strtotime($dob) > time()) {
    header("Location: ../forms/add_member.php?error=invalid_dob"); exit;
}
if ($component && !in_array($component, ['MB', 'AS', 'Y', 'YA'], true)) {
    header("Location: ../forms/add_member.php?error=invalid_component"); exit;
}

if ($dob && $church_id) {
    $dup = $pdo->prepare("
        SELECT member_id
        FROM members
        WHERE church_id = ? AND dob = ? AND LOWER(name) = LOWER(?) AND LOWER(surname_name) = LOWER(?)
        LIMIT 1
    ");
    $dup->execute([$church_id, $dob, $name, $surname]);
    if ($dup->fetchColumn()) {
        header("Location: ../forms/add_member.php?error=duplicate_member"); exit;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO members
            (district_id, conference_id, area_id, church_id, name, surname_name, gender, dob,
             contact, current_status, component, eligible_to_vote_conference,
             eligible_to_vote_episcopal, robbed, year_robbed,
             member_no, joined_ypd, full_member_of_church, occupational_status)
        VALUES
            (:district_id, :conference_id, :area_id, :church_id, :name, :surname_name, :gender, :dob,
             :contact, :current_status, :component, :vote_conf, :vote_epis, :robbed, :year_robbed,
             :member_no, :joined_ypd, :full_member_of_church, :occupational_status)
    ");
    $stmt->execute([
        ':district_id'          => $district_id,
        ':conference_id'        => $conf_id,
        ':area_id'              => $area_id,
        ':church_id'            => $church_id,
        ':name'                 => $name,
        ':surname_name'         => $surname,
        ':gender'               => $gender,
        ':dob'                  => $dob,
        ':contact'              => $contact,
        ':current_status'       => $status,
        ':component'            => $component,
        ':vote_conf'            => $vote_conf,
        ':vote_epis'            => $vote_epis,
        ':robbed'               => $robbed,
        ':year_robbed'          => $year_robbed,
        ':member_no'            => $member_no,
        ':joined_ypd'           => $joined_ypd,
        ':full_member_of_church'=> $full_member_church,
        ':occupational_status'  => $occ_status,
    ]);
    header("Location: ../views/members.php?success=1"); exit;
} catch (PDOException $e) {
    error_log("Database error in process_member.php: " . $e->getMessage());
    header("Location: ../forms/add_member.php?error=database_error");
    exit;
}

<?php
require_once '../db.php';
require_once '../includes/auth.php';
require_auth();
require_once '../includes/soft_delete.php';

$memberIds = $_POST['member_ids'] ?? [];
if (!is_array($memberIds)) {
    $memberIds = [];
}

$memberIds = array_map('intval', array_filter($memberIds, 'is_numeric'));

if (empty($memberIds)) {
    $redirect = $_SERVER['HTTP_REFERER'] ?? '../views/members.php';
    header('Location: ' . $redirect . '?batch_error=No members selected');
    exit;
}

$bulkAction = $_POST['bulk_action'] ?? '';
$affected = 0;

try {
    $pdo->beginTransaction();

    $inQuery = implode(',', array_fill(0, count($memberIds), '?'));

    switch ($bulkAction) {
        case 'update_component':
            $newComp = $_POST['new_component'] ?? '';
            if ($newComp === 'NONE' || $newComp === '') {
                $stmt = $pdo->prepare("UPDATE members SET component = NULL WHERE member_id IN ($inQuery)");
                $stmt->execute($memberIds);
            } else if (in_array($newComp, ['MB', 'AS', 'Y', 'YA'], true)) {
                $params = array_merge([$newComp], $memberIds);
                $stmt = $pdo->prepare("UPDATE members SET component = ? WHERE member_id IN ($inQuery)");
                $stmt->execute($params);
            }
            $affected = count($memberIds);
            break;

        case 'update_conference':
            $newConfId = (int)($_POST['new_conference_id'] ?? 0);
            $newAreaId = (int)($_POST['new_area_id'] ?? 0) ?: null;
            $newChurchId = (int)($_POST['new_church_id'] ?? 0) ?: null;

            if ($newConfId > 0) {
                $sql = "UPDATE members SET conference_id = ?";
                $params = [$newConfId];
                if ($newAreaId) {
                    $sql .= ", area_id = ?";
                    $params[] = $newAreaId;
                }
                if ($newChurchId) {
                    $sql .= ", church_id = ?";
                    $params[] = $newChurchId;
                }
                $sql .= " WHERE member_id IN ($inQuery)";
                $params = array_merge($params, $memberIds);

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $affected = count($memberIds);
            }
            break;

        case 'update_voting_conference':
            $voteConf = ($_POST['vote_conference'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
            $params = array_merge([$voteConf], $memberIds);
            $stmt = $pdo->prepare("UPDATE members SET eligible_to_vote_conference = ? WHERE member_id IN ($inQuery)");
            $stmt->execute($params);
            $affected = count($memberIds);
            break;

        case 'update_voting_episcopal':
            $voteEpis = ($_POST['vote_episcopal'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
            $params = array_merge([$voteEpis], $memberIds);
            $stmt = $pdo->prepare("UPDATE members SET eligible_to_vote_episcopal = ? WHERE member_id IN ($inQuery)");
            $stmt->execute($params);
            $affected = count($memberIds);
            break;

        case 'update_status':
            $newStatus = trim($_POST['new_status'] ?? 'Active');
            if ($newStatus !== '') {
                $params = array_merge([$newStatus], $memberIds);
                $stmt = $pdo->prepare("UPDATE members SET current_status = ? WHERE member_id IN ($inQuery)");
                $stmt->execute($params);
                $affected = count($memberIds);
            }
            break;

        case 'bulk_delete':
            foreach ($memberIds as $id) {
                soft_delete_row($pdo, 'members', 'member_id', (int)$id, '../views/members.php');
                $affected++;
            }
            break;

        default:
            throw new Exception('Invalid bulk action specified');
    }

    $pdo->commit();
    $redirect = $_SERVER['HTTP_REFERER'] ?? '../views/members.php';
    // Clean query params if needed
    $parts = parse_url($redirect);
    $path = $parts['path'] ?? '../views/members.php';
    parse_str($parts['query'] ?? '', $queryParams);
    $queryParams['batch_success'] = $affected;
    $queryParams['action_done'] = $bulkAction;

    header('Location: ' . $path . '?' . http_build_query($queryParams));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $redirect = $_SERVER['HTTP_REFERER'] ?? '../views/members.php';
    header('Location: ' . $redirect . '?batch_error=' . urlencode($e->getMessage()));
    exit;
}
